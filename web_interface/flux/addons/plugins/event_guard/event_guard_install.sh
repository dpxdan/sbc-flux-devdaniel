#!/usr/bin/env bash
# ##############################################################################
# Flux SBC - Unindo pessoas e negócios
#
# Copyright (C) 2026 Flux Telecom
# Daniel Paixao <daniel@flux.net.br>
# FluxSBC Version 4.2 and above
# License https://www.gnu.org/licenses/agpl-3.0.html
#
# Script de instalação do módulo Protetor SIP (event_guard)
# Testado em: Debian 11 (Bullseye)
#
# Uso:
#   chmod +x event_guard_install.sh
#   sudo ./event_guard_install.sh
# ##############################################################################

set -euo pipefail

# ── Cores ─────────────────────────────────────────────────────────────────────
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# ── Caminhos ──────────────────────────────────────────────────────────────────
FLUXSBC_FS_DIR="/opt/flux/freeswitch/fs"
FLUXSBC_MODULE_DIR="/opt/flux/web_interface/flux/application/modules/event_guard"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Origens dentro do diretório do addon (layout FluxSBC)
SRC_MODULES_DIR="${SCRIPT_DIR}/web_interface/flux/application/modules/event_guard"
SRC_FS_DIR="${SCRIPT_DIR}/freeswitch/fs"
SRC_SQL_DIR="${SCRIPT_DIR}/database"
SRC_FAIL2BAN_DIR="${SCRIPT_DIR}/fail2ban"

# Estrutura esperada ao lado do script:
# event_guard_install.sh
# module.xml
# static/images/event_guard_icon.png
# database/
#   event_guard_1.0.0.sql
#   uninstall.sql
# fail2ban/
#   event_guard.conf          → /etc/fail2ban/jail.d/event_guard.conf
#   sip-auth-fail.conf        → /etc/fail2ban/filter.d/sip-auth-fail.conf
#   sip-auth-ip.conf          → /etc/fail2ban/filter.d/sip-auth-ip.conf
#   fluxsbc-event-guard       → /etc/sudoers.d/fluxsbc-event-guard
#   event_guard.service       → /etc/systemd/system/event_guard.service
# freeswitch/fs/
#   event_guard_daemon.php
#   lib/flux.eventsocket.php
# web_interface/flux/application/modules/event_guard/
#   controllers/event_guard.php
#   libraries/event_guard_form.php
#   models/event_guard_model.php
#   views/view_event_guard_*.php

# ── Config de banco (lida do flux-config.conf) ────────────────────────────────
# Localização padrão em produção; /opt/flux/config/ é fallback para instalações alternativas.
FLUX_CONF_CANDIDATES=(
    "/var/lib/flux/flux-config.conf"
    "/opt/flux/config/flux-config.conf"
)
FLUX_CONF=""
for candidate in "${FLUX_CONF_CANDIDATES[@]}"; do
    if [[ -f "$candidate" ]]; then
        FLUX_CONF="$candidate"
        break
    fi
done

# ── Funções de log ─────────────────────────────────────────────────────────────
log_info()    { echo -e "${BLUE}[INFO]${NC}  $*"; }
log_ok()      { echo -e "${GREEN}[OK]${NC}    $*"; }
log_warn()    { echo -e "${YELLOW}[WARN]${NC}  $*"; }
log_error()   { echo -e "${RED}[ERROR]${NC} $*"; }
log_section() { echo -e "\n${BLUE}══════════════════════════════════════════${NC}"; echo -e "${BLUE} $*${NC}"; echo -e "${BLUE}══════════════════════════════════════════${NC}"; }

# ── Pré-requisitos ─────────────────────────────────────────────────────────────
check_root() {
    if [[ $EUID -ne 0 ]]; then
        log_error "Este script deve ser executado como root."
        exit 1
    fi
}

check_debian() {
    if [[ ! -f /etc/debian_version ]]; then
        log_error "Este script suporta apenas Debian."
        exit 1
    fi
    log_ok "Debian detectado: $(cat /etc/debian_version)"
}

check_files() {
    log_section "Verificando arquivos de instalação"

    local missing=0
    local required_files=(
        "web_interface/flux/application/modules/event_guard/controllers/event_guard.php"
        "web_interface/flux/application/modules/event_guard/models/event_guard_model.php"
        "web_interface/flux/application/modules/event_guard/libraries/event_guard_form.php"
        "web_interface/flux/application/modules/event_guard/views/view_event_guard_list.php"
        "web_interface/flux/application/modules/event_guard/views/view_event_guard_whitelist_list.php"
        "web_interface/flux/application/modules/event_guard/views/view_event_guard_whitelist_add.php"
        "web_interface/flux/application/modules/event_guard/views/view_event_guard_edit.php"
        "freeswitch/fs/event_guard_daemon.php"
        "freeswitch/fs/lib/flux.eventsocket.php"
        "database/event_guard_1.0.0.sql"
        "fail2ban/event_guard.conf"
        "fail2ban/sip-auth-fail.conf"
        "fail2ban/sip-auth-ip.conf"
        "fail2ban/fluxsbc-event-guard"
        "fail2ban/event_guard.service"
    )

    for f in "${required_files[@]}"; do
        if [[ ! -f "${SCRIPT_DIR}/${f}" ]]; then
            log_error "Arquivo não encontrado: ${SCRIPT_DIR}/${f}"
            missing=1
        else
            log_ok "  ${f}"
        fi
    done

    if [[ $missing -eq 1 ]]; then
        log_error "Arquivos obrigatórios ausentes. Abortando."
        exit 1
    fi
}

# ── fail2ban ──────────────────────────────────────────────────────────────────
install_fail2ban() {
    log_section "Instalando fail2ban"

    if dpkg -l fail2ban &>/dev/null; then
        log_ok "fail2ban já instalado: $(fail2ban-client --version 2>&1 | head -1)"
    else
        log_info "Instalando fail2ban via apt..."
        apt-get update -qq
        apt-get install -y fail2ban
        log_ok "fail2ban instalado."
    fi

    if ! systemctl is-enabled fail2ban &>/dev/null; then
        systemctl enable fail2ban
        log_ok "fail2ban habilitado no boot."
    fi

    if ! systemctl is-active --quiet fail2ban; then
        systemctl start fail2ban
        log_ok "fail2ban iniciado."
    fi
}

configure_fail2ban() {
    log_section "Configurando fail2ban"

    # Cria jail.local se não existir (evita sobrescrever customizações)
    if [[ ! -f /etc/fail2ban/jail.local ]]; then
        cp /etc/fail2ban/jail.conf /etc/fail2ban/jail.local
        log_ok "jail.local criado a partir de jail.conf."
    else
        log_ok "jail.local já existe, mantendo."
    fi

    # Filtros
    for filter in sip-auth-fail sip-auth-ip; do
        local src="${SRC_FAIL2BAN_DIR}/${filter}.conf"
        local dst="/etc/fail2ban/filter.d/${filter}.conf"

        if [[ -f "$dst" ]]; then
            if diff -q "$src" "$dst" &>/dev/null; then
                log_ok "filter.d/${filter}.conf sem alterações."
            else
                cp "$dst" "${dst}.bak.$(date +%Y%m%d%H%M%S)"
                cp "$src" "$dst"
                log_ok "filter.d/${filter}.conf atualizado (backup criado)."
            fi
        else
            cp "$src" "$dst"
            log_ok "filter.d/${filter}.conf instalado."
        fi
    done

    # Jail
    local jail_src="${SRC_FAIL2BAN_DIR}/event_guard.conf"
    local jail_dst="/etc/fail2ban/jail.d/event_guard.conf"

    if [[ -f "$jail_dst" ]]; then
        if diff -q "$jail_src" "$jail_dst" &>/dev/null; then
            log_ok "jail.d/event_guard.conf sem alterações."
        else
            cp "$jail_dst" "${jail_dst}.bak.$(date +%Y%m%d%H%M%S)"
            cp "$jail_src" "$jail_dst"
            log_ok "jail.d/event_guard.conf atualizado (backup criado)."
        fi
    else
        cp "$jail_src" "$jail_dst"
        log_ok "jail.d/event_guard.conf instalado."
    fi

    # Valida configuração antes de recarregar
    log_info "Validando configuração do fail2ban..."
    if fail2ban-client --test 2>&1 | grep -qi "error"; then
        log_error "Erro na configuração do fail2ban. Verifique os arquivos e tente novamente."
        fail2ban-client --test
        exit 1
    fi

    systemctl reload fail2ban || systemctl restart fail2ban
    sleep 2

    # Verifica jails
    for jail in sip-auth-fail sip-auth-ip; do
        if fail2ban-client status "$jail" &>/dev/null; then
            log_ok "Jail '${jail}' ativo."
        else
            log_error "Jail '${jail}' não iniciou. Verifique /var/log/fail2ban.log"
            exit 1
        fi
    done
}

# ── sudoers ───────────────────────────────────────────────────────────────────
configure_sudoers() {
    log_section "Configurando sudoers"

    local src="${SRC_FAIL2BAN_DIR}/fluxsbc-event-guard"
    local dst="/etc/sudoers.d/fluxsbc-event-guard"

    cp "$src" "$dst"
    chmod 440 "$dst"

    # Valida sintaxe
    if ! visudo -c -f "$dst" &>/dev/null; then
        log_error "Erro de sintaxe no sudoers. Removendo arquivo."
        rm -f "$dst"
        exit 1
    fi

    log_ok "sudoers configurado e validado."
}

# ── Banco de dados ─────────────────────────────────────────────────────────────
run_sql() {
    log_section "Executando scripts SQL"

    if [[ -z "$FLUX_CONF" ]]; then
        log_warn "flux-config.conf não encontrado em nenhum dos caminhos padrão:"
        for candidate in "${FLUX_CONF_CANDIDATES[@]}"; do
            log_warn "  - ${candidate}"
        done
        log_info "Informe os dados de conexão manualmente:"
        read -rp "  Host MySQL: " DB_HOST
        read -rp "  Banco:      " DB_NAME
        read -rp "  Usuário:    " DB_USER
        read -rsp "  Senha:      " DB_PASS
        echo
    else
        DB_HOST=$(grep -Po '^\s*dbhost\s*=\s*\K.*' "$FLUX_CONF" | tr -d '[:space:]')
        DB_NAME=$(grep -Po '^\s*dbname\s*=\s*\K.*' "$FLUX_CONF" | tr -d '[:space:]')
        DB_USER=$(grep -Po '^\s*dbuser\s*=\s*\K.*' "$FLUX_CONF" | tr -d '[:space:]')
        DB_PASS=$(grep -Po '^\s*dbpass\s*=\s*\K.*' "$FLUX_CONF" | tr -d '[:space:]')

        if [[ -z "$DB_HOST" || -z "$DB_NAME" || -z "$DB_USER" || -z "$DB_PASS" ]]; then
            log_error "Não foi possível ler todas as credenciais de ${FLUX_CONF}."
            log_error "  dbhost='${DB_HOST}' dbname='${DB_NAME}' dbuser='${DB_USER}' dbpass=$([[ -n "$DB_PASS" ]] && echo '***' || echo '(vazio)')"
            exit 1
        fi
        log_ok "Credenciais lidas de ${FLUX_CONF}"
    fi

    # Testa conexão
    if ! mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SELECT 1" &>/dev/null; then
        log_error "Não foi possível conectar ao MySQL. Verifique as credenciais."
        exit 1
    fi

    local sql_file="${SRC_SQL_DIR}/event_guard_1.0.0.sql"
    local filename
    filename=$(basename "$sql_file")
    log_info "Executando ${filename}..."

    if mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$sql_file"; then
        log_ok "${filename} executado com sucesso."
    else
        log_error "Erro ao executar ${filename}. Abortando."
        exit 1
    fi
}

# ── Arquivos fs/ ──────────────────────────────────────────────────────────────
install_fs_files() {
    log_section "Instalando arquivos fs/"

    if [[ ! -d "$FLUXSBC_FS_DIR" ]]; then
        log_error "Diretório fs/ não encontrado: ${FLUXSBC_FS_DIR}"
        exit 1
    fi

    # Daemon
    local daemon_src="${SRC_FS_DIR}/event_guard_daemon.php"
    local daemon_dst="${FLUXSBC_FS_DIR}/event_guard_daemon.php"

    if [[ -f "$daemon_dst" ]]; then
        cp "$daemon_dst" "${daemon_dst}.bak.$(date +%Y%m%d%H%M%S)"
        log_info "Backup do daemon criado."
    fi
    cp "$daemon_src" "$daemon_dst"
    chown www-data:www-data "$daemon_dst"
    log_ok "event_guard_daemon.php instalado."

    # flux.eventsocket.php
    local esl_src="${SRC_FS_DIR}/lib/flux.eventsocket.php"
    local esl_dst="${FLUXSBC_FS_DIR}/lib/flux.eventsocket.php"

    if [[ -f "$esl_dst" ]]; then
        cp "$esl_dst" "${esl_dst}.bak.$(date +%Y%m%d%H%M%S)"
        log_info "Backup do flux.eventsocket.php criado."
    fi
    cp "$esl_src" "$esl_dst"
    chown www-data:www-data "$esl_dst"
    log_ok "flux.eventsocket.php instalado."
}

# ── Módulo web CI2 ────────────────────────────────────────────────────────────
install_module() {
    log_section "Instalando módulo web event_guard"

    local web_base
    web_base=$(dirname "$FLUXSBC_MODULE_DIR")

    if [[ ! -d "$web_base" ]]; then
        log_error "Diretório de módulos não encontrado: ${web_base}"
        exit 1
    fi

    # Cria estrutura de diretórios
    for dir in controllers models libraries views; do
        mkdir -p "${FLUXSBC_MODULE_DIR}/${dir}"
    done

    # Copia arquivos com backup se já existirem
    local src_module="${SRC_MODULES_DIR}"

    find "$src_module" -type f -name "*.php" | while read -r src_file; do
        local rel_path="${src_file#${src_module}/}"
        local dst_file="${FLUXSBC_MODULE_DIR}/${rel_path}"

        if [[ -f "$dst_file" ]]; then
            cp "$dst_file" "${dst_file}.bak.$(date +%Y%m%d%H%M%S)"
        fi

        cp "$src_file" "$dst_file"
        chown www-data:www-data "$dst_file"
        log_ok "  ${rel_path}"
    done

    chown -R www-data:www-data "$FLUXSBC_MODULE_DIR"
    log_ok "Módulo instalado em ${FLUXSBC_MODULE_DIR}"
}

# ── Systemd service ───────────────────────────────────────────────────────────
install_service() {
    log_section "Configurando systemd service"

    local src="${SRC_FAIL2BAN_DIR}/event_guard.service"
    local dst="/etc/systemd/system/event_guard.service"

    if [[ -f "$dst" ]]; then
        cp "$dst" "${dst}.bak.$(date +%Y%m%d%H%M%S)"
        log_info "Backup do service criado."
    fi

    # Ajusta ExecStart para o path real do daemon
    sed "s|/var/www/fluxsbc/scripts/event_guard_daemon.php|${FLUXSBC_FS_DIR}/event_guard_daemon.php|g" \
        "$src" > "$dst"

    systemctl daemon-reload
    systemctl enable event_guard

    # Reinicia se já estiver rodando; inicia caso contrário
    if systemctl is-active --quiet event_guard; then
        systemctl restart event_guard
        log_ok "Serviço event_guard reiniciado."
    else
        systemctl start event_guard
        log_ok "Serviço event_guard iniciado."
    fi

    sleep 2

    if systemctl is-active --quiet event_guard; then
        log_ok "Serviço event_guard rodando."
    else
        log_error "Serviço event_guard não iniciou. Verifique:"
        log_error "  journalctl -u event_guard -n 50"
        exit 1
    fi
}

# ── Verificação final ─────────────────────────────────────────────────────────
verify_installation() {
    log_section "Verificação final"

    local ok=1

    # fail2ban jails
    for jail in sip-auth-fail sip-auth-ip; do
        if fail2ban-client status "$jail" &>/dev/null; then
            log_ok "fail2ban jail '${jail}': ativo"
        else
            log_error "fail2ban jail '${jail}': inativo"
            ok=0
        fi
    done

    # sudoers
    if visudo -c -f /etc/sudoers.d/fluxsbc-event-guard &>/dev/null; then
        log_ok "sudoers: válido"
    else
        log_error "sudoers: inválido"
        ok=0
    fi

    # Daemon
    if systemctl is-active --quiet event_guard; then
        log_ok "Daemon event_guard: rodando"
    else
        log_error "Daemon event_guard: parado"
        ok=0
    fi

    # Tabelas MySQL
    if mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" \
        -e "SELECT COUNT(*) FROM event_guard_logs LIMIT 1;" &>/dev/null; then
        log_ok "Tabela event_guard_logs: OK"
    else
        log_error "Tabela event_guard_logs: não encontrada"
        ok=0
    fi

    if mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" \
        -e "SELECT COUNT(*) FROM event_guard_whitelist LIMIT 1;" &>/dev/null; then
        log_ok "Tabela event_guard_whitelist: OK"
    else
        log_error "Tabela event_guard_whitelist: não encontrada"
        ok=0
    fi

    echo
    if [[ $ok -eq 1 ]]; then
        log_ok "Instalação concluída com sucesso."
    else
        log_warn "Instalação concluída com erros. Verifique os itens acima."
        exit 1
    fi
}

# ── Main ──────────────────────────────────────────────────────────────────────
main() {
    echo
    echo -e "${BLUE}╔════════════════════════════════════════════╗${NC}"
    echo -e "${BLUE}║   FluxSBC — Protetor SIP (event_guard)     ║${NC}"
    echo -e "${BLUE}║   Instalação em produção                   ║${NC}"
    echo -e "${BLUE}╚════════════════════════════════════════════╝${NC}"
    echo

    check_root
    check_debian
    check_files
    install_fail2ban
    configure_fail2ban
    configure_sudoers
    run_sql
    install_fs_files
    install_module
    install_service
    verify_installation
}

main "$@"
