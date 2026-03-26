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

class Detraf extends MX_Controller
{

    function __construct()
    {
        parent::__construct();
        $this->load->helper('template_inheritance');
        $this->load->library('session');
        $this->load->library('detraf_form');
        $this->load->library('flux/form', 'detraf_form');
        $this->load->model('detraf_model');
        $this->load->library('csvreader');
        $this->load->library('flux/email_lib');
        $this->load->library('flux_log');

        if ($this->session->userdata('user_login') == FALSE)
            redirect(base_url() . '/flux/login');
    }

    function detraf_list()
    {
        $data['page_title']  = gettext('Relatório DETRAF');
        $data['search_flag'] = true;
        $this->session->set_userdata('advance_search', 0);

        $data['grid_fields']  = $this->detraf_form->build_detraf();
        $data['grid_buttons'] = $this->detraf_form->build_grid_buttons_detraf();
        $data['carrier_list'] = $this->detraf_model->get_carrier_list();
        $data['eot_credora']  = $this->detraf_model->get_eot_credora();
        $data['form_search']  = $this->form->build_serach_form(
            $this->detraf_form->get_search_detraf_form()
        );

        $this->load->view('view_detraf_report', $data);
    }
    

    function detraf_list_search()
    {
        $ajax_search = $this->input->post('ajax_search', 0);

        if ($this->input->post('advance_search', TRUE) == 1) {
            $this->session->set_userdata('advance_search', $this->input->post('advance_search'));
            $action = $this->input->post();
            $this->session->set_userdata('detraf_search', $action);
        }

        if (@$ajax_search != 1) {
            redirect(base_url() . 'detraf/detraf_list/');
        }
    }

    function detraf_list_clearsearchfilter()
    {
        $this->session->set_userdata('advance_search', 0);
        $this->session->set_userdata('detraf_search', '');
    }

    function detraf_list_json()
    {
        $search_arr = $this->_build_detraf_search();

        $count_all = $this->detraf_model->get_detraf_report_list(
            false, 0, 0,
            $search_arr['data_inicio'],
            $search_arr['data_fim'],
            $search_arr['eot_devedora'],
            $search_arr['eot_credora'],
            false
        );

        $paging_data = $this->form->load_grid_config($count_all, $_GET['rp'], $_GET['page']);
        $json_data   = $paging_data['json_paging'];

        $query = $this->detraf_model->get_detraf_report_list(
            true,
            $paging_data['paging']['start'],
            $paging_data['paging']['page_no'],
            $search_arr['data_inicio'],
            $search_arr['data_fim'],
            $search_arr['eot_devedora'],
            $search_arr['eot_credora'],
            false
        );

        if ($query && $query->num_rows() > 0) {
            $json_data['rows'] = $this->_detraf_report_grid($query);
        }

        $this->session->set_userdata('detraf_export', $search_arr);

        echo json_encode($json_data);
    }

    function detraf_export_cdr_xls()
    {
        $search_arr = $this->session->userdata('detraf_export');
        if (empty($search_arr)) {
            redirect(base_url() . '/detraf');
        }

        $query = $this->detraf_model->get_detraf_report_list(
            true, '', '',
            $search_arr['data_inicio'],
            $search_arr['data_fim'],
            $search_arr['eot_devedora'],
            $search_arr['eot_credora'],
            true
        );

        $outbound_array = array();
        ob_clean();

        $outbound_array[] = array(
            gettext('EOT Credora'),
            gettext('Operadora Credora'),
            gettext('EOT Devedora'),
            gettext('Operadora Devedora'),
            gettext('Referência'),
            gettext('Período Tráfego'),
            gettext('POI'),
            gettext('Tipo Rel.'),
            gettext('Descritor'),
            gettext('Grupo Horário'),
            gettext('Chamadas'),
            gettext('Minutos'),
        );

        if ($query && $query->num_rows() > 0) {
            foreach ($query->result_array() as $row) {
                $outbound_array[] = array(
                    $row['EOT Credora'],
                    $row['Operadora Credora'],
                    $row['EOT Devedora'],
                    $row['Operadora Devedora'],
                    $row['Referência'],
                    $row['Período Tráfego'],
                    $row['POI'],
                    $row['Tipo Rel.'],
                    $row['Descritor'],
                    $row['Grupo Horário'],
                    $row['Chamadas'],
                    $row['Minutos'],
                );
            }
        }

        $this->load->helper('csv');
        $filename = 'DETRAF_' . $search_arr['eot_credora'] . '_'
                  . str_replace('-', '', $search_arr['data_inicio']) . '_'
                  . str_replace('-', '', $search_arr['data_fim'])
                  . '.csv';

        array_to_csv($outbound_array, $filename);
    }

    function detraf_export_cdr_pdf()
    {
        $search_arr = $this->session->userdata('detraf_export');
        if (empty($search_arr)) {
            redirect(base_url() . '/detraf');
        }

        $query = $this->detraf_model->get_detraf_report_list(
            true, '', '',
            $search_arr['data_inicio'],
            $search_arr['data_fim'],
            $search_arr['eot_devedora'],
            $search_arr['eot_credora'],
            true
        );

        $outbound_array = array();

        $this->load->library('fpdf');
        $this->load->library('pdf');
        $this->fpdf = new PDF('P', 'pt');
        $this->fpdf->initialize('P', 'mm', 'A4');
        $this->fpdf->tablewidths = array(15, 45, 15, 45, 18, 18, 18, 12, 12, 18, 18, 18);

        $outbound_array[] = array(
            gettext('EOT Credora'),
            gettext('Operadora Credora'),
            gettext('EOT Devedora'),
            gettext('Operadora Devedora'),
            gettext('Referência'),
            gettext('Período Tráfego'),
            gettext('POI'),
            gettext('Tipo Rel.'),
            gettext('Descritor'),
            gettext('Grupo Horário'),
            gettext('Chamadas'),
            gettext('Minutos'),
        );

        if ($query && $query->num_rows() > 0) {
            foreach ($query->result_array() as $row) {
                $outbound_array[] = array(
                    $row['EOT Credora'],
                    $row['Operadora Credora'],
                    $row['EOT Devedora'],
                    $row['Operadora Devedora'],
                    $row['Referência'],
                    $row['Período Tráfego'],
                    $row['POI'],
                    $row['Tipo Rel.'],
                    $row['Descritor'],
                    $row['Grupo Horário'],
                    $row['Chamadas'],
                    $row['Minutos'],
                );
            }
        }

        $this->fpdf->AliasNbPages();
        $this->fpdf->AddPage();

        $this->fpdf->SetFont('Arial', '', 15);
        $this->fpdf->SetXY(60, 5);
        $this->fpdf->Cell(100, 10, 'Relatorio DETRAF ' . date('Y-m-d'));

        $this->fpdf->SetY(20);
        $this->fpdf->SetFont('Arial', '', 7);
        $this->fpdf->SetFillColor(255, 255, 255);
        $this->fpdf->lMargin = 2;

        $this->fpdf->detraf_export_cdr_pdf($outbound_array, '7');

        $filename = 'DETRAF_' . $search_arr['eot_credora'] . '_'
                  . str_replace('-', '', $search_arr['data_inicio']) . '_'
                  . str_replace('-', '', $search_arr['data_fim'])
                  . '.pdf';

        $this->fpdf->Output($filename, 'D');
    }

    function send_email()
    {

        $email_to   = trim($this->input->post('email_to'));
        $search_arr = $this->session->userdata('detraf_export');
        $response   = array('status' => 'error', 'message' => '');

        if (!filter_var($email_to, FILTER_VALIDATE_EMAIL)) {
            $response['message'] = gettext('E-mail de destino invalido.');
            echo json_encode($response);
            return;
        }

        if (empty($search_arr)) {
            $response['message'] = gettext('Nenhum relatorio gerado. Execute a pesquisa primeiro.');
            echo json_encode($response);
            return;
        }

        $query = $this->detraf_model->get_detraf_report_list(
            true, '', '',
            $search_arr['data_inicio'],
            $search_arr['data_fim'],
            $search_arr['eot_devedora'],
            $search_arr['eot_credora'],
            true
        );

        $detraf_array = array();
        $detraf_array[] = array(
            gettext('EOT Credora'),
            gettext('Operadora Credora'),
            gettext('EOT Devedora'),
            gettext('Operadora Devedora'),
            gettext('Referencia'),
            gettext('Periodo Trafego'),
            gettext('POI'),
            gettext('Tipo Rel.'),
            gettext('Descritor'),
            gettext('Grupo Horario'),
            gettext('Chamadas'),
            gettext('Minutos'),
        );

        if ($query && $query->num_rows() > 0) {
            foreach ($query->result_array() as $row) {
                $detraf_array[] = array(
                    (isset($row['EOT Credora'])       && $row['EOT Credora']       != '') ? $row['EOT Credora']       : ' ',
                    (isset($row['Operadora Credora'])  && $row['Operadora Credora']  != '') ? $row['Operadora Credora']  : ' ',
                    (isset($row['EOT Devedora'])       && $row['EOT Devedora']       != '') ? $row['EOT Devedora']       : ' ',
                    (isset($row['Operadora Devedora']) && $row['Operadora Devedora'] != '') ? $row['Operadora Devedora'] : ' ',
                    (isset($row['Referência'])         && $row['Referência']         != '') ? $row['Referência']         : ' ',
                    (isset($row['Período Tráfego'])    && $row['Período Tráfego']    != '') ? $row['Período Tráfego']    : ' ',
                    (isset($row['POI'])                && $row['POI']                != '') ? $row['POI']                : ' ',
                    (isset($row['Tipo Rel.'])          && $row['Tipo Rel.']          != '') ? $row['Tipo Rel.']          : ' ',
                    (isset($row['Descritor'])          && $row['Descritor']          != '') ? $row['Descritor']          : ' ',
                    (isset($row['Grupo Horário'])      && $row['Grupo Horário']      != '') ? $row['Grupo Horário']      : ' ',
                    (isset($row['Chamadas'])           && $row['Chamadas']           != '') ? $row['Chamadas']           : ' ',
                    (isset($row['Minutos'])            && $row['Minutos']            != '') ? $row['Minutos']            : ' ',
                );
            }
        }

        $filename    = 'DETRAF_' . $search_arr['eot_credora'] . '_'
                     . str_replace('-', '', $search_arr['data_inicio']) . '_'
                     . str_replace('-', '', $search_arr['data_fim']) . '.csv';

        $attach_path = getcwd() . '/attachments/' . $filename;

        $fh = fopen($attach_path, 'w');
        if (!$fh) {
            $response['message'] = gettext('Falha ao criar arquivo CSV.');
            echo json_encode($response);
            return;
        }
        fprintf($fh, chr(0xEF) . chr(0xBB) . chr(0xBF));
        foreach ($detraf_array as $line) {
            fputcsv($fh, $line, ';');
        }
        fclose($fh);

        $add_array = array(
            'accountid'   => 1,
            'reseller_id' => 1,
            'subject'     => 'Relatorio DETRAF - ' . $search_arr['eot_credora']
                           . ' - ' . $search_arr['data_inicio']
                           . ' a '  . $search_arr['data_fim'],
            'body'        => $this->_email_body($search_arr),
            'from'        => $this->common->get_field_name('emailaddress', 'invoice_conf', array()),
            'to'          => $email_to,
            'status'      => 1,
            'template'    => 0,
            'attachment'  => $filename,
        );

        if ($this->detraf_model->add_email($add_array)) {
            $response['status']  = 'success';
            $response['message'] = gettext('Relatorio agendado para envio para ') . $email_to;
        } else {
            $response['message'] = gettext('Falha ao registrar envio de e-mail.');
            $this->flux_log->write_log('detraf_send_email_error', 'add_email failed for ' . $email_to);
        }

        echo json_encode($response);
    }

    function _build_detraf_search()
    {
        $search = $this->session->userdata('detraf_search');
    
        if (empty($search)) {
            $search = array(
                'data_inicio'  => date('Y-m-01') . ' 00:00:00',
                'data_fim'     => date('Y-m-t')  . ' 23:59:59',
                'eot_devedora' => '',
                'eot_credora'  => $this->detraf_model->get_eot_credora(),
            );
        } else {
            if (empty($search['eot_credora'])) {
                $search['eot_credora'] = $this->detraf_model->get_eot_credora();
            }
        }
    
        return $search;
    }

    function _detraf_report_grid($query)
    {
        $json_data = array();
        foreach ($query->result_array() as $row) {
            $json_data[] = array(
                'cell' => array(
                    $row['EOT Credora'],
                    $row['Operadora Credora'],
                    $row['EOT Devedora'],
                    $row['Operadora Devedora'],
                    $row['Referência'],
                    $row['Período Tráfego'],
                    $row['POI'],
                    $row['Tipo Rel.'],
                    $row['Descritor'],
                    $row['Grupo Horário'],
                    $row['Chamadas'],
                    $row['Minutos'],
                )
            );
        }
        return $json_data;
    }

    function _email_body($search_arr)
    {
        return '
        <html><body style="font-family:Arial,sans-serif;font-size:13px;color:#333">
        <h3 style="color:#2c6fad">Relatorio DETRAF - FluxSBC</h3>
        <table cellpadding="6" cellspacing="0" style="border-collapse:collapse">
            <tr><td style="font-weight:bold">EOT Credora:</td><td>' . htmlspecialchars($search_arr['eot_credora']) . '</td></tr>
            <tr><td style="font-weight:bold">Periodo:</td><td>' . htmlspecialchars($search_arr['data_inicio']) . ' a ' . htmlspecialchars($search_arr['data_fim']) . '</td></tr>
        </table>
        <p>O relatorio DETRAF esta anexo a este e-mail no formato CSV.</p>
        <hr style="border:1px solid #eee;margin-top:20px">
        <small style="color:#999">FluxSBC - gerado em ' . date('d/m/Y H:i:s') . '</small>
        </body></html>';
    }
    
    function detraf_contestacao()
    {
        $data['page_title']   = gettext('DETRAF — Contestação');
        $data['search_flag']  = false;
        $data['batch_list']   = $this->detraf_model->get_import_batch_list();
    
        $this->load->view('view_detraf_contestacao', $data);
    }
    
    function contestacao_upload()
    {
        $response = array('status' => 'error', 'message' => '', 'batch_id' => '');
    
        if ($_FILES['detraf_csv']['error'] !== UPLOAD_ERR_OK) {
            $response['message'] = gettext('Falha no upload do arquivo.');
            echo json_encode($response);
            return;
        }
    
        $ext = strtolower(pathinfo($_FILES['detraf_csv']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, array('csv', 'txt'))) {
            $response['message'] = gettext('Apenas arquivos CSV são aceitos.');
            echo json_encode($response);
            return;
        }
    
        $tmp  = $_FILES['detraf_csv']['tmp_name'];
        $rows = $this->_parse_detraf_csv($tmp);
        
        if (empty($rows)) {
            $response['message'] = gettext('Arquivo vazio ou formato inválido. Verifique se o CSV segue o padrão DETRAF (colunas separadas por ; ou ,).');
            echo json_encode($response);
            return;
        }
        
        $all_eots = array();
        foreach ($rows as $r) {
            if (!empty($r['eot_credora']))  $all_eots[] = $r['eot_credora'];
            if (!empty($r['eot_devedora'])) $all_eots[] = $r['eot_devedora'];
        }
        
        $eot_names = $this->detraf_model->get_operator_names_by_eot(array_unique($all_eots));
        
        foreach ($rows as &$r) {
            $r['operadora_credora']  = isset($eot_names[$r['eot_credora']])
                                       ? $eot_names[$r['eot_credora']]
                                       : '';
            $r['operadora_devedora'] = isset($eot_names[$r['eot_devedora']])
                                       ? $eot_names[$r['eot_devedora']]
                                       : '';
        }
        unset($r);
        
        $batch_id   = md5(uniqid('detraf_', true));
        $filename   = basename($_FILES['detraf_csv']['name']);
        $account    = $this->session->userdata('accountinfo');
        $created_by = isset($account['id']) ? (int)$account['id'] : 0;
    
        $now = date('Y-m-d H:i:s');
        foreach ($rows as &$r) {
            $r['batch_id']   = $batch_id;
            $r['created_at'] = $now;
        }
        unset($r);
    
        $saved = $this->detraf_model->save_import_batch($batch_id, $filename, $rows, $created_by);
    
        if (!$saved) {
            $response['message'] = gettext('Erro ao salvar lote no banco.');
            echo json_encode($response);
            return;
        }
    
        $response['status']   = 'success';
        $response['batch_id'] = $batch_id;
        $response['message']  = sprintf(gettext('%d linhas importadas com sucesso.'), count($rows));
        echo json_encode($response);
    }
    
    function contestacao_json($batch_id = '')
    {
        $batch_id = $this->db->escape_str($batch_id);
        $apenas_div = (int)$this->input->get('apenas_div');
    
        $count_all   = $this->detraf_model->get_detraf_compare($batch_id, false, 0, 0, (bool)$apenas_div);
        $paging_data = $this->form->load_grid_config($count_all, $_GET['rp'], $_GET['page']);
        $json_data   = $paging_data['json_paging'];
    
        $rows = $this->detraf_model->get_detraf_compare(
            $batch_id, true,
            $paging_data['paging']['start'],
            $paging_data['paging']['page_no'],
            (bool)$apenas_div
        );
    
        if (!empty($rows)) {
            $json_data['rows'] = $this->_contestacao_grid($rows);
        }
    
        echo json_encode($json_data);
    }
    
    function contestacao_export_csv($batch_id = '', $apenas_div = 0)
    {
        $batch_id = $this->db->escape_str($batch_id);
        $rows     = $this->detraf_model->get_detraf_compare($batch_id, true, 0, 0, (bool)$apenas_div);
    
        ob_clean();
    
        $out   = array();
        $out[] = array(
            gettext('EOT Credora'),      gettext('Operadora Credora'),
            gettext('EOT Devedora'),     gettext('Operadora Devedora'),
            gettext('Referência'),       gettext('POI'),
            gettext('Grupo Horário'),
            gettext('Chamadas Deles'),   gettext('Minutos Deles'),
            gettext('Chamadas Nossos'),  gettext('Minutos Nossos'),
            gettext('Diff Chamadas'),    gettext('Diff Minutos'),
            gettext('Status'),
        );
    
        foreach ($rows as $r) {
            $out[] = array(
                $r['eot_credora'],      $r['operadora_credora'],
                $r['eot_devedora'],     $r['operadora_devedora'],
                $r['referencia'],       $r['poi'],
                $r['grupo_horario'],
                $r['chamadas_deles'],   $r['minutos_deles'],
                $r['chamadas_nossos'],  $r['minutos_nossos'],
                $r['diff_chamadas'],    $r['diff_minutos'],
                $r['status_contesta'],
            );
        }
    
        $batch  = $this->detraf_model->get_batch_info($batch_id);
        $suffix = isset($batch['referencia']) ? $batch['referencia'] : date('Ym');
        $flag   = $apenas_div ? '_divergencias' : '';
    
        $this->load->helper('csv');
        array_to_csv($out, 'CONTESTACAO_DETRAF_' . $suffix . $flag . '_' . date('Ymd_His') . '.csv');
    }
    
    function contestacao_delete($batch_id = '')
    {
        $this->detraf_model->delete_import_batch($this->db->escape_str($batch_id));
        $response = array('status' => 'success');
        echo json_encode($response);
    }
    
    function _contestacao_grid($rows)
    {
        $json_data = array();
        $status_labels = array(
            'OK'        => '<span class="badge badge-success">OK</span>',
            'CONTESTAR' => '<span class="badge badge-danger">CONTESTAR</span>',
            'INDEVIDO'  => '<span class="badge badge-warning">INDEVIDO</span>',
            'EM_FALTA'  => '<span class="badge badge-info">EM FALTA</span>',
        );
    
        foreach ($rows as $r) {
            $status  = isset($status_labels[$r['status_contesta']]) 
                       ? $status_labels[$r['status_contesta']]
                       : $r['status_contesta'];
    
            $diff_ch = (int)$r['diff_chamadas'];
            $diff_mn = (int)$r['diff_minutos'];
            $diff_ch_fmt = ($diff_ch > 0 ? '+' : '') . $diff_ch;
            $diff_mn_fmt = ($diff_mn > 0 ? '+' : '') . $diff_mn;
    
            $json_data[] = array('cell' => array(
                $r['eot_credora'],
                $r['operadora_credora'],
                $r['eot_devedora'],
                $r['operadora_devedora'],
                $r['referencia'],
                $r['poi'],
                $r['grupo_horario'],
                $r['chamadas_deles'],
                $r['minutos_deles'],
                $r['chamadas_nossos'],
                $r['minutos_nossos'],
                $diff_ch !== 0 ? '<b style="color:' . ($diff_ch > 0 ? '#c00' : '#070') . '">' . $diff_ch_fmt . '</b>' : '0',
                $diff_mn !== 0 ? '<b style="color:' . ($diff_mn > 0 ? '#c00' : '#070') . '">' . $diff_mn_fmt . '</b>' : '0',
                $status,
            ));
        }
    
        return $json_data;
    }
    
    function _parse_detraf_csv($filepath)
    {
    $rows   = array();
    $handle = fopen($filepath, 'r');

    if (!$handle) {
        $this->flux_log->write_log('detraf_parse_csv_error', 'cannot open file');
        return array();
    }

    $first_raw = fgets($handle);
    rewind($handle);

    $first_raw = ltrim($first_raw, "\xEF\xBB\xBF");

    $sep = (substr_count($first_raw, ';') >= substr_count($first_raw, ',')) ? ';' : ',';

    $first_clean = strtolower(trim($first_raw));
    $is_anatel_native = (strpos($first_clean, 'cd_eot_bil') !== false);
    $is_our_format    = (strpos($first_clean, 'eot credora') !== false ||
                         strpos($first_clean, 'eot_credora') !== false);

    $this->flux_log->write_log('detraf_parse_csv_format', json_encode(array(
        'sep'              => $sep,
        'is_anatel_native' => $is_anatel_native,
        'is_our_format'    => $is_our_format,
        'first_line'       => $first_raw,
    )));

    if (!$is_anatel_native && !$is_our_format) {
        fclose($handle);
        return array();
    }

    $line_num = 0;
    $header   = array();

    while (($cols = fgetcsv($handle, 4096, $sep)) !== false) {
        $line_num++;

        if ($line_num === 1) {
            $cols[0] = ltrim($cols[0], "\xEF\xBB\xBF");
        }

        $cols = array_map('trim', $cols);

        if ($line_num === 1) {
            foreach ($cols as $idx => $col) {
                $header[$idx] = strtolower($this->_remove_accents($col));
            }
            continue;
        }

        if ($is_anatel_native) {
            $row = $this->_parse_anatel_native_row($header, $cols);
        } else {
            $row = $this->_parse_our_format_row($header, $cols);
        }

        if ($row === null) continue;

        $rows[] = $row;
    }

    fclose($handle);

    $this->flux_log->write_log('detraf_parse_csv_result', json_encode(array(
        'total_rows' => count($rows),
        'format'     => $is_anatel_native ? 'anatel_native' : 'our_format',
    )));

    return $rows;
}

    function _parse_anatel_native_row($header, $cols)
    {
        $map = array();
        foreach ($header as $idx => $name) {
            $map[$name] = isset($cols[$idx]) ? trim($cols[$idx]) : '';
        }
    
        $tipo = isset($map['tipo_relatorio']) ? trim($map['tipo_relatorio']) : '';
        if ($tipo === '01') return null;
    
        $eot_bil = isset($map['cd_eot_bil']) ? $map['cd_eot_bil'] : '';
        $eot_rel = isset($map['cd_eot_rel']) ? $map['cd_eot_rel'] : '';
    
        if ($eot_bil === '' && $eot_rel === '') return null;
    
        $grupo = isset($map['grupo_horario']) ? $map['grupo_horario'] : '';
        if ($grupo === '') return null;
    
        $minutos_raw = isset($map['minutos']) ? str_replace(',', '.', $map['minutos']) : '0';
        $minutos_int = (int)ceil((float)$minutos_raw);
    
        $chamadas = isset($map['qtde']) ? (int)preg_replace('/[^0-9]/', '', $map['qtde']) : 0;
    
        $poi_raw = isset($map['poi']) ? $map['poi'] : '';
        $poi_normalized = $this->_normalize_poi($poi_raw, $eot_bil);
    
        return array(
            'eot_credora'        => $eot_bil,
            'operadora_credora'  => '',
            'eot_devedora'       => $eot_rel,
            'operadora_devedora' => '',
            'referencia'         => isset($map['mes_ref'])   ? $map['mes_ref']   : '',
            'periodo_trafego'    => isset($map['mes_traf'])  ? $map['mes_traf']  : '',
            'poi'                => $poi_normalized,
            'tipo_rel'           => $tipo,
            'descritor'          => isset($map['descritor']) ? ltrim($map['descritor']) : '',
            'grupo_horario'      => $grupo,
            'chamadas'           => $chamadas,
            'minutos'            => $minutos_int,
        );
    }
    
    function _parse_our_format_row($header, $cols)
    {
        $col_map = array(
            'eot credora'        => 'eot_credora',
            'eot_credora'        => 'eot_credora',
            'operadora credora'  => 'operadora_credora',
            'operadora_credora'  => 'operadora_credora',
            'eot devedora'       => 'eot_devedora',
            'eot_devedora'       => 'eot_devedora',
            'operadora devedora' => 'operadora_devedora',
            'operadora_devedora' => 'operadora_devedora',
            'referencia'         => 'referencia',
            'referência'         => 'referencia',
            'periodo trafego'    => 'periodo_trafego',
            'período tráfego'    => 'periodo_trafego',
            'periodo_trafego'    => 'periodo_trafego',
            'poi'                => 'poi',
            'tipo rel.'          => 'tipo_rel',
            'tipo rel'           => 'tipo_rel',
            'tipo_rel'           => 'tipo_rel',
            'descritor'          => 'descritor',
            'grupo horario'      => 'grupo_horario',
            'grupo horário'      => 'grupo_horario',
            'grupo_horario'      => 'grupo_horario',
            'chamadas'           => 'chamadas',
            'minutos'            => 'minutos',
        );
    
        $row = array(
            'eot_credora'        => '',
            'operadora_credora'  => '',
            'eot_devedora'       => '',
            'operadora_devedora' => '',
            'referencia'         => '',
            'periodo_trafego'    => '',
            'poi'                => '',
            'tipo_rel'           => '',
            'descritor'          => '',
            'grupo_horario'      => '',
            'chamadas'           => 0,
            'minutos'            => 0,
        );
    
        foreach ($header as $idx => $col_name) {
            $key = isset($col_map[$col_name]) ? $col_map[$col_name] : null;
            if ($key === null || !isset($cols[$idx])) continue;
    
            if (in_array($key, array('chamadas', 'minutos'))) {
                $val = str_replace(',', '.', $cols[$idx]);
                $row[$key] = (int)ceil((float)preg_replace('/[^0-9.,]/', '', $val));
            } else {
                $row[$key] = trim($cols[$idx]);
            }
        }
    
        if (empty($row['eot_credora']) && empty($row['eot_devedora'])) return null;
        if (empty($row['grupo_horario'])) return null;
    
        return $row;
    }
    
    function _remove_accents($str)
    {
        $from = array('á','à','ã','â','ä','é','è','ê','ë','í','ì','î','ï',
                      'ó','ò','õ','ô','ö','ú','ù','û','ü','ç','ñ',
                      'Á','À','Ã','Â','Ä','É','È','Ê','Ë','Í','Ì','Î','Ï',
                      'Ó','Ò','Õ','Ô','Ö','Ú','Ù','Û','Ü','Ç','Ñ');
        $to   = array('a','a','a','a','a','e','e','e','e','i','i','i','i',
                      'o','o','o','o','o','u','u','u','u','c','n',
                      'a','a','a','a','a','e','e','e','e','i','i','i','i',
                      'o','o','o','o','o','u','u','u','u','c','n');
        return str_replace($from, $to, $str);
    }
    
    function _normalize_poi($poi_raw, $eot_bil_is_us = '')
    {
        if (in_array($poi_raw, array('SPO.CO', 'SPO.IB', 'OUTRO'))) {
            return $poi_raw;
        }
        return $poi_raw;
    }

}
?>
