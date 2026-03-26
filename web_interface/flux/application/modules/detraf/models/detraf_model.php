<?php
// ##############################################################################
// Flux Telecom - Unindo pessoas e neg—cios
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
class Detraf_model extends CI_Model
{

    function __construct()
    {
        parent::__construct();
        $this->load->library('flux_log');
    }

    function get_detraf_report_list(
    $flag,
    $start        = 0,
    $limit        = 0,
    $data_inicio  = '',
    $data_fim     = '',
    $eot_devedora = '',
    $eot_credora  = '',
    $export       = false
) {
    if (empty($data_inicio)) $data_inicio = date('Y-m-01');
    if (empty($data_fim))    $data_fim    = date('Y-m-t');

    $dt_inicio_esc = $this->db->escape($data_inicio);
    $dt_fim_esc    = $this->db->escape($data_fim);

    $eot_filter = !empty($eot_devedora)
        ? 'AND c.carrier_eot = ' . $this->db->escape($eot_devedora)
        : '';

    $grupo_horario_expr = "
        CASE
            WHEN DAYOFWEEK(CONVERT_TZ(c.callstart,'+00:00','-03:00')) BETWEEN 2 AND 6
             AND HOUR(CONVERT_TZ(c.callstart,'+00:00','-03:00')) BETWEEN 9 AND 17
            THEN 'N' ELSE 'R'
        END";

    $sql = "
        SELECT
            -- EOT Credora: outbound = lado A (caller_carrier_eot via carrier_routing)
            --              inbound  = lado B (c.carrier_eot)
            CASE
                WHEN c.call_direction = 'outbound' THEN COALESCE(NULLIF(ea.carrier_eot,''), '')
                ELSE                                    COALESCE(NULLIF(c.carrier_eot,''),  '')
            END AS `EOT Credora`,
            CASE
                WHEN c.call_direction = 'outbound' THEN COALESCE(NULLIF(eo_c.nm_grupo_holding,''), '')
                ELSE                                    COALESCE(NULLIF(eo_c2.nm_grupo_holding,''), '')
            END AS `Operadora Credora`,

            -- EOT Devedora: outbound = lado B (c.carrier_eot)
            --               inbound  = lado A (caller_carrier_eot via carrier_routing)
            CASE
                WHEN c.call_direction = 'outbound' THEN COALESCE(NULLIF(c.carrier_eot,''),  '')
                ELSE                                    COALESCE(NULLIF(ea.carrier_eot,''), '')
            END AS `EOT Devedora`,
            CASE
                WHEN c.call_direction = 'outbound' THEN COALESCE(NULLIF(eo_d.nm_grupo_holding,''), '')
                ELSE                                    COALESCE(NULLIF(eo_d2.nm_grupo_holding,''), '')
            END AS `Operadora Devedora`,

            DATE_FORMAT(CONVERT_TZ(c.callstart,'+00:00','-03:00'),'%Y%m') AS `Referência`,
            DATE_FORMAT(CONVERT_TZ(c.callstart,'+00:00','-03:00'),'%Y%m') AS `Período Tráfego`,
            CASE
                WHEN c.call_direction = 'inbound'  THEN 'SPO.IB'
                WHEN c.call_direction = 'outbound' THEN 'SPO.CO'
                ELSE 'OUTRO'
            END AS `POI`,
            '000'  AS `Tipo Rel.`,
            'LENL' AS `Descritor`,
            {$grupo_horario_expr} AS `Grupo Horário`,
            COUNT(*)                         AS `Chamadas`,
            CEILING(SUM(c.billseconds) / 60) AS `Minutos`

        FROM cdrs c

        -- Lado A: único JOIN restante — carrier_routing, 514 linhas, sem cadup
        LEFT JOIN (
            SELECT carrier_id, MAX(carrier_eot) AS carrier_eot
            FROM carrier_routing
            WHERE carrier_eot != ''
            GROUP BY carrier_id
        ) ea ON ea.carrier_id = c.caller_carrier_id

        -- Nomes via eot_operadoras — outbound
        LEFT JOIN eot_operadoras eo_c  ON eo_c.cd_eot  = ea.carrier_eot   -- credora outbound = lado A
        LEFT JOIN eot_operadoras eo_d  ON eo_d.cd_eot  = c.carrier_eot    -- devedora outbound = lado B
        -- Nomes via eot_operadoras — inbound (lados invertidos)
        LEFT JOIN eot_operadoras eo_c2 ON eo_c2.cd_eot = c.carrier_eot    -- credora inbound = lado B
        LEFT JOIN eot_operadoras eo_d2 ON eo_d2.cd_eot = ea.carrier_eot   -- devedora inbound = lado A

        WHERE c.callstart  >= {$dt_inicio_esc}
          AND c.callstart  <  {$dt_fim_esc}
          AND c.disposition  = 'NORMAL_CLEARING [16]'
          AND c.billseconds  > 0
          AND c.carrier_eot != ''
          {$eot_filter}
          AND NOT (
              COALESCE(NULLIF(ea.carrier_eot,''),'')
              = COALESCE(NULLIF(c.carrier_eot,''),'')
              AND c.carrier_eot != ''
          )

        GROUP BY
            `EOT Credora`,
            `Operadora Credora`,
            `EOT Devedora`,
            `Operadora Devedora`,
            `Referência`,
            `POI`,
            `Grupo Horário`
        ORDER BY
            `Referência`,
            `POI`,
            `Grupo Horário`
    ";

    $this->flux_log->write_log('get_detraf_report_list', json_encode(array(
        'data_inicio'  => $data_inicio,
        'data_fim'     => $data_fim,
        'eot_devedora' => $eot_devedora,
        'eot_credora'  => $eot_credora,
    )));

    $result = $this->db->query($sql);

    if (!$result) {
        $this->flux_log->write_log('get_detraf_report_list_error', json_encode($this->db->error()));
        return $flag ? false : 0;
    }

    $all_rows = $result->result_array();

    if (!$flag) return count($all_rows);

    if (!$export && (int)$limit > 0) {
        return $this->_result_to_object(array_slice($all_rows, (int)$start, (int)$limit));
    }

    return $this->_result_to_object($all_rows);
}

    function get_carrier_list()
    {
        $result = $this->db->query(
            "SELECT DISTINCT vc.eot, vc.nomePrestadora
             FROM view_carriers vc
             WHERE vc.eot IS NOT NULL AND vc.eot <> ''
             ORDER BY vc.nomePrestadora ASC, vc.eot ASC"
        );

        if (!$result || $result->num_rows() === 0) {
            return array();
        }

        $list = array('' => gettext('-- Selecione --'));
        foreach ($result->result_array() as $row) {
            $list[$row['eot']] = $row['eot'] . ' — ' . $row['nomePrestadora'];
        }

        return $list;
    }

    function get_carrier_id_by_eot($eot)
    {
        if (empty($eot)) {
            return 0;
        }

        $result = $this->db->query(
            "SELECT cr.carrier_id
             FROM carrier_routing cr
             INNER JOIN view_carriers vc
                ON vc.rn1 = cr.carrier_rn1
               AND vc.nomePrestadora = cr.carrier_name
             WHERE vc.eot = ?
             LIMIT 1",
            array($eot)
        );

        if ($result && $result->num_rows() > 0) {
            return (int)$result->row()->carrier_id;
        }

        $this->flux_log->write_log('get_carrier_id_by_eot_notfound', 'eot=' . $eot);
        return 0;
    }

    function get_eot_credora()
    {
        $result = $this->db->query(
            "SELECT value FROM `system` WHERE name = 'eot_credora' LIMIT 1"
        );

        if ($result && $result->num_rows() > 0) {
            return trim($result->row()->value);
        }

        return $this->config->item('detraf_eot_credora') ?: '';
    }

    private function _result_to_object(array $rows)
    {
        return new class($rows) {
            private $rows;
            public function __construct($r) { $this->rows = $r; }
            public function num_rows()      { return count($this->rows); }
            public function result_array()  { return $this->rows; }
        };
    }
    
    function add_email($add_array)
    {
        $this->db->insert("mail_details", $add_array);
        return true;
    }
    
    function save_import_batch($batch_id, $filename, $rows, $created_by)
    {
    if (empty($rows)) return false;

    $eots_credora  = array_unique(array_filter(array_column($rows, 'eot_credora')));
    $eots_devedora = array_unique(array_filter(array_column($rows, 'eot_devedora')));
    $referencias   = array_unique(array_filter(array_column($rows, 'referencia')));

    $this->db->insert('detraf_import_batch', array(
        'batch_id'     => $batch_id,
        'filename'     => $filename,
        'eot_credora'  => implode(',', $eots_credora),
        'eot_devedora' => implode(',', $eots_devedora),
        'referencia'   => implode(',', $referencias),
        'total_rows'   => count($rows),
        'created_at'   => date('Y-m-d H:i:s'),
        'created_by'   => (int)$created_by,
    ));

    foreach (array_chunk($rows, 100) as $chunk) {
        $this->db->insert_batch('detraf_import', $chunk);
    }

    return true;
}
    
    function get_import_batch_list()
    {
        return $this->db
            ->select('id, batch_id, filename, eot_credora, eot_devedora, referencia, total_rows, created_at')
            ->from('detraf_import_batch')
            ->order_by('created_at', 'DESC')
            ->get()
            ->result_array();
    }
    
    function delete_import_batch($batch_id)
    {
        $batch_id = $this->db->escape_str($batch_id);
        $this->db->delete('detraf_import',       array('batch_id' => $batch_id));
        $this->db->delete('detraf_import_batch', array('batch_id' => $batch_id));
    }
    
    function get_detraf_compare($batch_id, $flag = true, $start = 0, $limit = 0, $apenas_divergencias = false)
    {
        $batch_id_esc = $this->db->escape($batch_id);
    
        $batch_info = $this->get_batch_info($batch_id);
        $referencia = isset($batch_info['referencia']) ? trim($batch_info['referencia']) : '';
    
        $ref_filter = '';
        if ($referencia !== '') {
            $refs = array_filter(array_map('trim', explode(',', $referencia)));
    
            if (count($refs) === 1) {
                $ref_filter = "AND DATE_FORMAT(CONVERT_TZ(c.callstart,'+00:00','-03:00'),'%Y%m') = "
                            . $this->db->escape($refs[0]);
            } elseif (count($refs) > 1) {
                $escaped = array();
                foreach ($refs as $ref) {
                    $escaped[] = $this->db->escape($ref);
                }
                $ref_filter = "AND DATE_FORMAT(CONVERT_TZ(c.callstart,'+00:00','-03:00'),'%Y%m') IN ("
                            . implode(',', $escaped) . ")";
            }
        }
    
        $caller_eot_expr  = "COALESCE(NULLIF(c.caller_carrier_eot,''), NULLIF(ea.carrier_eot,''), '')";
        $carrier_eot_expr = "COALESCE(NULLIF(c.carrier_eot,''), '')";
    
        $grupo_horario_expr = "
            CASE
                WHEN DAYOFWEEK(CONVERT_TZ(c.callstart,'+00:00','-03:00')) BETWEEN 2 AND 6
                 AND HOUR(CONVERT_TZ(c.callstart,'+00:00','-03:00')) BETWEEN 9 AND 17
                THEN 'N' ELSE 'R'
            END
        ";
    
        $join_ea = "
            LEFT JOIN (
                SELECT carrier_id, MAX(carrier_eot) AS carrier_eot
                FROM carrier_routing
                WHERE carrier_eot <> ''
                GROUP BY carrier_id
            ) ea ON ea.carrier_id = c.caller_carrier_id
        ";
    
        $sql_nossos_direto = "
            SELECT
                CASE
                    WHEN c.call_direction = 'outbound' THEN {$caller_eot_expr}
                    ELSE {$carrier_eot_expr}
                END AS eot_credora,
                CASE
                    WHEN c.call_direction = 'outbound' THEN {$carrier_eot_expr}
                    ELSE {$caller_eot_expr}
                END AS eot_devedora,
                DATE_FORMAT(CONVERT_TZ(c.callstart,'+00:00','-03:00'),'%Y%m') AS referencia,
                CASE
                    WHEN c.call_direction = 'inbound'  THEN 'SPO.IB'
                    WHEN c.call_direction = 'outbound' THEN 'SPO.CO'
                    ELSE 'OUTRO'
                END AS poi,
                {$grupo_horario_expr} AS grupo_horario,
                COUNT(*) AS chamadas_nossos,
                CEILING(SUM(c.billseconds) / 60) AS minutos_nossos
            FROM cdrs c
            {$join_ea}
            WHERE c.disposition = 'NORMAL_CLEARING [16]'
              AND c.billseconds > 0
              AND {$carrier_eot_expr} <> ''
              AND {$caller_eot_expr} <> ''
              {$ref_filter}
              AND {$caller_eot_expr} <> {$carrier_eot_expr}
            GROUP BY eot_credora, eot_devedora, referencia, poi, grupo_horario
        ";
    
        $sql_nossos_invertido = "
            SELECT
                CASE
                    WHEN c.call_direction = 'outbound' THEN {$carrier_eot_expr}
                    ELSE {$caller_eot_expr}
                END AS eot_credora,
                CASE
                    WHEN c.call_direction = 'outbound' THEN {$caller_eot_expr}
                    ELSE {$carrier_eot_expr}
                END AS eot_devedora,
                DATE_FORMAT(CONVERT_TZ(c.callstart,'+00:00','-03:00'),'%Y%m') AS referencia,
                CASE
                    WHEN c.call_direction = 'inbound'  THEN 'SPO.IB'
                    WHEN c.call_direction = 'outbound' THEN 'SPO.CO'
                    ELSE 'OUTRO'
                END AS poi,
                {$grupo_horario_expr} AS grupo_horario,
                COUNT(*) AS chamadas_nossos,
                CEILING(SUM(c.billseconds) / 60) AS minutos_nossos
            FROM cdrs c
            {$join_ea}
            WHERE c.disposition = 'NORMAL_CLEARING [16]'
              AND c.billseconds > 0
              AND {$carrier_eot_expr} <> ''
              AND {$caller_eot_expr} <> ''
              {$ref_filter}
              AND {$caller_eot_expr} <> {$carrier_eot_expr}
            GROUP BY eot_credora, eot_devedora, referencia, poi, grupo_horario
        ";
    
        $expr_chamadas = "COALESCE(nossos_d.chamadas_nossos, nossos_i.chamadas_nossos, 0)";
        $expr_minutos  = "COALESCE(nossos_d.minutos_nossos,  nossos_i.minutos_nossos,  0)";
    
        $filter_div = $apenas_divergencias
            ? "AND (di.chamadas <> {$expr_chamadas} OR di.minutos <> {$expr_minutos})"
            : "";
    
        $sql = "
            SELECT
                di.eot_credora,
                COALESCE(NULLIF(eo_c.nm_grupo_holding,''), di.operadora_credora)  AS operadora_credora,
                di.eot_devedora,
                COALESCE(NULLIF(eo_d.nm_grupo_holding,''), di.operadora_devedora) AS operadora_devedora,
                di.referencia,
                di.poi,
                di.grupo_horario,
                di.chamadas AS chamadas_deles,
                di.minutos AS minutos_deles,
                {$expr_chamadas} AS chamadas_nossos,
                {$expr_minutos}  AS minutos_nossos,
                (di.chamadas - {$expr_chamadas}) AS diff_chamadas,
                (di.minutos - {$expr_minutos})   AS diff_minutos,
                CASE
                    WHEN nossos_d.chamadas_nossos IS NULL
                     AND nossos_i.chamadas_nossos IS NULL
                        THEN 'INDEVIDO'
                    WHEN (di.chamadas - {$expr_chamadas}) = 0
                     AND (di.minutos  - {$expr_minutos})  = 0
                        THEN 'OK'
                    ELSE 'CONTESTAR'
                END AS status_contesta
            FROM detraf_import di
            LEFT JOIN eot_operadoras eo_c ON eo_c.cd_eot = di.eot_credora
            LEFT JOIN eot_operadoras eo_d ON eo_d.cd_eot = di.eot_devedora
    
            LEFT JOIN ({$sql_nossos_direto}) nossos_d
                   ON nossos_d.eot_credora   = TRIM(di.eot_credora)
                  AND nossos_d.eot_devedora  = TRIM(di.eot_devedora)
                  AND nossos_d.referencia    = di.referencia
            LEFT JOIN ({$sql_nossos_invertido}) nossos_i
                   ON nossos_i.eot_credora   = TRIM(di.eot_credora)
                  AND nossos_i.eot_devedora  = TRIM(di.eot_devedora)
                  AND nossos_i.referencia    = di.referencia
                  AND nossos_d.chamadas_nossos IS NULL
    
            WHERE di.batch_id = {$batch_id_esc}
            {$filter_div}
            ORDER BY di.referencia, di.poi, di.grupo_horario
        ";
    
        if (!$flag) {
            $count = $this->db->query("SELECT COUNT(*) AS total FROM ({$sql}) _c");
            return $count ? (int)$count->row()->total : 0;
        }
    
        if ((int)$limit > 0) {
            $sql .= " LIMIT " . (int)$start . ", " . (int)$limit;
        }
    
        $result = $this->db->query($sql);
    
        $this->flux_log->write_log('detraf_compare', json_encode(array(
            'batch_id'   => $batch_id,
            'referencia' => $referencia
        )));
    
        if (!$result) {
            $this->flux_log->write_log('detraf_compare_error', json_encode($this->db->error()));
            return $flag ? array() : 0;
        }
    
        return $result->result_array();
    }
    
    function get_batch_info($batch_id)
    {
        $row = $this->db
            ->where('batch_id', $batch_id)
            ->get('detraf_import_batch')
            ->row_array();
        return $row ?: array();
    }

    function get_operator_names_by_eot(array $eots)
    {
        $eots = array_filter(array_unique($eots));
        if (empty($eots)) return array();
    
        $placeholders = implode(',', array_fill(0, count($eots), '?'));
        $result = $this->db->query(
            "SELECT DISTINCT cd_eot, nm_grupo_holding
             FROM eot_operadoras
             WHERE cd_eot IN ({$placeholders})",
            array_values($eots)
        );
    
        if (!$result || $result->num_rows() === 0) return array();
    
        $map = array();
        foreach ($result->result_array() as $row) {
            $map[$row['cd_eot']] = $row['nm_grupo_holding'];
        }
        return $map;
    }

}
?>
