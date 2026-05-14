<?php
// ##############################################################################
// Flux SBC - Unindo pessoas e negócios
//
// Copyright (C) 2026 Flux Telecom
// Daniel Paixao <daniel@flux.net.br>
// FluxSBC Version 4.2 and above
// License https://www.gnu.org/licenses/agpl-3.0.html
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as
// published by the Free Software Foundation, either version 3 of the
// License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU Affero General Public License for more details.
//
// You should have received a copy of the GNU Affero General Public License
// along with this program. If not, see <http://www.gnu.org/licenses/>.
// ##############################################################################

define('ENVIRONMENT', 'production');

if (defined('ENVIRONMENT')) {
    switch (ENVIRONMENT) {
        case 'development':
            error_reporting(E_ALL);
            break;
        case 'testing':
        case 'production':
            error_reporting(0);
            break;
        default:
            exit('The application environment is not set correctly.');
    }
}

set_time_limit(0);
ini_set('max_execution_time', '0');
ini_set('memory_limit', '256M');

include("lib/flux.db.php");
include("lib/flux.logger.php");
include("lib/flux.fslogger.php");
include("lib/flux.lib.php");
include("lib/flux.constants.php");
include("lib/flux.custom.php");
include("lib/flux.eventsocket.php");

$db       = new db();
$lib      = new lib();
$config   = $lib->get_configurations($db);
$fslogger = new fslogger($lib);

// ── Config via tabela system (group_title='event_guard') ──────────────────────
$esl_host     = $config['esl_host']     ?? '127.0.0.1';
$esl_port     = (int)($config['esl_port'] ?? 8021);
$esl_password = $config['esl_password'] ?? 'ClueCon';
$hostname     = $config['hostname']     ?? gethostname();

// Filtro → nome do jail fail2ban
$f2b_jails = array(
    'sip-auth-fail' => 'sip-auth-fail',
    'sip-auth-ip'   => 'sip-auth-ip',
);

// ── Verifica jails no fail2ban ────────────────────────────────────────────────
foreach ($f2b_jails as $jail) {
    fail2ban_jail_check($jail, $fslogger);
}

$allowed_cache = array();

// ── Conexão ESL ───────────────────────────────────────────────────────────────
$socket = new EventSocket();

if (!$socket->connect($esl_host, $esl_port, $esl_password)) {
    $fslogger->log('event_guard: não foi possível conectar ao Event Socket. Abortando.');
    exit(1);
}

esl_subscribe($socket, $fslogger);
$fslogger->log('event_guard: daemon iniciado. Aguardando eventos...');

// ── Loop principal ────────────────────────────────────────────────────────────
while (true) {

    if (!$socket->connected()) {
        $fslogger->log('event_guard: Event Socket desconectado. Reconectando...');
        sleep(1);
        if ($socket->connect($esl_host, $esl_port, $esl_password)) {
            esl_subscribe($socket, $fslogger);
            $fslogger->log('event_guard: reconectado.');
        } else {
            $fslogger->log('event_guard: falha ao reconectar. Nova tentativa em 1s...');
            continue;
        }
    }

    $json_response = $socket->read_event();

    if (empty($json_response['$'])) {
        continue;
    }

    $event = json_decode($json_response['$'], true);
    unset($json_response);

    if (!is_array($event)) {
        continue;
    }

    $subclass = $event['Event-Subclass'] ?? '';
    $fslogger->log('event_guard: subclass=' . $subclass);

    if ($subclass === 'sofia::register_failure') {
        $ip = $event['network-ip'] ?? '';
        if ($ip !== '' && !access_allowed($ip, $db, $fslogger, $allowed_cache)) {
            event_guard_block($ip, 'sip-auth-fail', $event, $db, $fslogger, $hostname);
        }
    }

    if ($subclass === 'sofia::pre_register') {
        $to_host = $event['to-host'] ?? '';
        if (filter_var($to_host, FILTER_VALIDATE_IP)) {
            $ip = $event['network-ip'] ?? '';
            if ($ip !== '' && !access_allowed($ip, $db, $fslogger, $allowed_cache)) {
                event_guard_block($ip, 'sip-auth-ip', $event, $db, $fslogger, $hostname);
            }
        }
    }

    if ($subclass === 'event_guard:unblock') {
        process_unblock_queue($db, $fslogger, $hostname, $allowed_cache);
    }

    unset($event);
}

// ─────────────────────────────────────────────────────────────────────────────
// Funções
// ─────────────────────────────────────────────────────────────────────────────

function esl_subscribe($socket, $fslogger)
{
    $socket->request('event json ALL');
    $socket->request('filter Event-Name CUSTOM');
    $fslogger->log('event_guard: inscrito no Event Socket.');
}

// ── Controle de acesso ────────────────────────────────────────────────────────

function access_allowed($ip, $db, $fslogger, &$allowed_cache)
{
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        return false;
    }

    if (isset($allowed_cache[$ip])) {
        $fslogger->log('event_guard: cache hit para ' . $ip);
        return true;
    }

    if (whitelist_allowed($ip, $db, $fslogger)) {
        $allowed_cache[$ip] = true;
        $fslogger->log('event_guard: ' . $ip . ' permitido pela whitelist.');
        return true;
    }

    if (is_registered($ip, $fslogger)) {
        $allowed_cache[$ip] = true;
        $fslogger->log('event_guard: ' . $ip . ' permitido por registro ativo.');
        return true;
    }

    return false;
}

function whitelist_allowed($ip, $db, $fslogger)
{
    $query = "SELECT cidr FROM event_guard_whitelist";
    $fslogger->log('event_guard: whitelist query: ' . $query);
    $rows = $db->run($query);

    if (empty($rows)) {
        return false;
    }

    foreach ($rows as $row) {
        if (cidr_match($row['cidr'], $ip)) {
            return true;
        }
    }

    return false;
}

function cidr_match($cidr, $ip)
{
    if (strpos($cidr, '/') === false) {
        return $ip === $cidr;
    }

    list($subnet, $bits) = explode('/', $cidr, 2);
    $bits        = (int) $bits;
    $ip_long     = ip2long($ip);
    $subnet_long = ip2long($subnet);

    if ($ip_long === false || $subnet_long === false) {
        return false;
    }

    $mask = $bits === 0 ? 0 : (~0 << (32 - $bits));

    return ($ip_long & $mask) === ($subnet_long & $mask);
}

function is_registered($ip, $fslogger)
{
    $output = shell_exec("fs_cli -x 'show registrations as json' 2>/dev/null");

    if (empty($output)) {
        return false;
    }

    $data = json_decode($output, true);

    if (!is_array($data['rows'] ?? null)) {
        return false;
    }

    foreach ($data['rows'] as $row) {
        if (($row['network_ip'] ?? '') === $ip) {
            return true;
        }
    }

    return false;
}

// ── fail2ban ──────────────────────────────────────────────────────────────────

/**
 * Verifica se o jail existe no fail2ban. Loga aviso se não encontrar.
 */
function fail2ban_jail_check($jail, $fslogger)
{
    $output = shell_exec("sudo fail2ban-client status {$jail} 2>&1");

    if (strpos((string)$output, 'Sorry') !== false || strpos((string)$output, 'ERROR') !== false) {
        $fslogger->log('event_guard: AVISO — jail "' . $jail . '" não encontrado. Verifique /etc/fail2ban/jail.d/');
    } else {
        $fslogger->log('event_guard: jail ' . $jail . ' OK.');
    }
}

/**
 * Bane o IP no jail fail2ban e persiste o log no banco.
 */
function event_guard_block($ip, $filter, $event, $db, $fslogger, $hostname)
{
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        return;
    }

    // Verifica se o IP já está bloqueado para evitar duplicatas
    $ip_safe_chk     = $db->quote($ip);
    $filter_safe_chk = $db->quote($filter);
    $host_safe_chk   = $db->quote($hostname);

    $check = $db->run(
        "SELECT id FROM event_guard_logs
         WHERE ip_address = {$ip_safe_chk}
           AND filter     = {$filter_safe_chk}
           AND hostname   = {$host_safe_chk}
           AND log_status = 'blocked'
         LIMIT 1"
    );

    if (!empty($check)) {
        $fslogger->log('event_guard: ip=' . $ip . ' já bloqueado no jail=' . $filter . ', ignorando duplicata.');
        return;
    }

    // Ban via fail2ban
    $cmd    = "sudo fail2ban-client set {$filter} banip {$ip} 2>&1";
    $output = shell_exec($cmd);
    $fslogger->log('event_guard: fail2ban banip → ' . $cmd . ' : ' . trim((string)$output));

    // Syslog
    openlog('fluxsbc', LOG_PID | LOG_PERROR, LOG_AUTH);
    syslog(
        LOG_WARNING,
        sprintf(
            'event_guard: blocked ip=%s jail=%s ext=%s@%s ua=%s',
            $ip,
            $filter,
            $event['to-user']    ?? '',
            $event['to-host']    ?? '',
            $event['user-agent'] ?? ''
        )
    );
    closelog();

    // Persiste no banco
    $log_uuid    = generate_uuid();
    $extension   = $db->quote(($event['to-user'] ?? '') . '@' . ($event['to-host'] ?? ''));
    $user_agent  = $db->quote($event['user-agent'] ?? '');
    $ip_safe     = $db->quote($ip);
    $filter_safe = $db->quote($filter);
    $host_safe   = $db->quote($hostname);
    $uuid_safe   = $db->quote($log_uuid);

    $query = "INSERT INTO event_guard_logs
                (log_uuid, hostname, log_date, filter, ip_address, extension, user_agent, log_status)
              VALUES
                ({$uuid_safe}, {$host_safe}, NOW(), {$filter_safe}, {$ip_safe}, {$extension}, {$user_agent}, 'blocked')";

    $fslogger->log('event_guard: ' . $query);
    $db->run($query);
}

/**
 * Desbane o IP no jail fail2ban.
 */
function event_guard_unblock($ip, $filter, $fslogger)
{
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        return;
    }

    $cmd    = "sudo fail2ban-client set {$filter} unbanip {$ip} 2>&1";
    $output = shell_exec($cmd);
    $fslogger->log('event_guard: fail2ban unbanip → ' . $cmd . ' : ' . trim((string)$output));
}

/**
 * Processa a fila de IPs pendentes de desbloqueio.
 */
function process_unblock_queue($db, $fslogger, $hostname, &$allowed_cache)
{
    $host_safe = $db->quote($hostname);
    $query     = "SELECT id, ip_address, filter
                  FROM event_guard_logs
                  WHERE log_status = 'pending'
                    AND hostname   = {$host_safe}";

    $fslogger->log('event_guard: unblock queue: ' . $query);
    $rows = $db->run($query);

    if (empty($rows)) {
        return;
    }

    foreach ($rows as $row) {
        event_guard_unblock($row['ip_address'], $row['filter'], $fslogger);

        unset($allowed_cache[$row['ip_address']]);

        openlog('fluxsbc', LOG_PID | LOG_PERROR, LOG_AUTH);
        syslog(
            LOG_WARNING,
            'event_guard: unblocked ip=' . $row['ip_address'] . ' jail=' . $row['filter']
        );
        closelog();

        $id_safe = (int) $row['id'];
        $upd     = "UPDATE event_guard_logs
                    SET log_status = 'unblocked', log_date = NOW()
                    WHERE id = {$id_safe}";

        $fslogger->log('event_guard: ' . $upd);
        $db->run($upd);
    }
}

function generate_uuid()
{
    $data    = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}
