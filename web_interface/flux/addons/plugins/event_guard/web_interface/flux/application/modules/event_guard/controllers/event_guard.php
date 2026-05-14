<?php

// ##############################################################################
// Flux SBC - Unindo pessoas e negócios
//
// Copyright (C) 2026 Flux Telecom
// Daniel Paixao <daniel@flux.net.br>
// Flux SBC Version 4.0 and above
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

class Event_guard extends MX_Controller
{

    function __construct()
    {
        parent::__construct();
        $this->load->helper('template_inheritance');
        $this->load->library('session');
        $this->load->library('flux_log');
        $this->load->library('event_guard_form');
        $this->load->library('flux/form', 'event_guard_form');
        $this->load->library('flux/permission');
        $this->load->library('FLUX_Sms');
        $this->load->model('event_guard_model');
        if ($this->session->userdata('user_login') == FALSE)
            redirect(base_url() . '/flux/login');
    }

    // ── Logs ─────────────────────────────────────────────────────────────────

    function event_guard_list()
    {
        $data['username']    = $this->session->userdata('user_name');
        $data['page_title']  = gettext('Event Guard - Blocked IPs');
        $data['search_flag'] = true;
        $this->session->set_userdata('advance_search', 0);
        $data['grid_fields']  = $this->event_guard_form->build_event_guard_list_for_admin();
        $data['grid_buttons'] = $this->event_guard_form->build_grid_buttons();
        $data['form_search'] = $this->form->build_serach_form($this->event_guard_form->get_event_guard_search_form());
        $this->load->view('view_event_guard_list', $data);
    }

    function event_guard_list_json()
    {
        $json_data   = array();
        $count_all   = $this->event_guard_model->get_event_guard_list(false);
        $paging_data = $this->form->load_grid_config($count_all, $_GET['rp'], $_GET['page']);
        $json_data   = $paging_data['json_paging'];
        $query       = $this->event_guard_model->get_event_guard_list(true, $paging_data['paging']['start'], $paging_data['paging']['page_no']);
        $grid_fields = json_decode($this->event_guard_form->build_event_guard_list_for_admin());
        $json_data['rows'] = $this->form->build_grid($query, $grid_fields);
        echo json_encode($json_data);
    }

    function event_guard_list_search()
    {
        $ajax_search = $this->input->post('ajax_search', 0);

        if ($this->input->post('advance_search', TRUE) == 1) {
            $this->session->set_userdata('advance_search', $this->input->post('advance_search'));
            $action = $this->input->post();
            unset($action['action']);
            unset($action['advance_search']);
            $this->session->set_userdata('event_guard_list_search', $action);
        }

        if (@$ajax_search != 1) {
            redirect(base_url() . 'event_guard/event_guard_list/');
        }
    }

    function event_guard_clearsearchfilter()
    {
        $this->session->set_userdata('advance_search', 0);
        $this->session->set_userdata('event_guard_list_search', '');
        redirect(base_url() . 'event_guard/event_guard_list/');
    }

    function event_guard_unblock($id = '')
    {
        $this->flux_log->write_log('event_guard', json_encode(array(
            'action' => 'event_guard_unblock',
            'id'     => $id,
        )));

        $log_id = trim((string) $this->input->post('id'));

        if (empty($log_id)) {
            $id = (int) $id;
            if ($id > 0) {
                $log_id = $id;
            } else {
                $this->session->set_flashdata('flux_notification', gettext('log_uuid required.'));
                redirect(base_url() . 'event_guard/event_guard_list/');
            }
        }

        $log = $this->event_guard_model->get_log_by_id($log_id);

        if (empty($log)) {
            $this->session->set_flashdata('flux_notification', gettext('Record not found.'));
            redirect(base_url() . 'event_guard/event_guard_list/');
        }

        if ($log['log_status'] !== 'blocked') {
            $this->session->set_flashdata('flux_notification', $ip . ' ' . gettext('IP is not blocked.'));
            redirect(base_url() . 'event_guard/event_guard_list/');
        }

        $ip       = $log['ip_address'];
        $filter   = $log['filter'];
        $log_uuid = $log['log_uuid'];

        $cmd    = "sudo fail2ban-client set {$filter} unbanip {$ip} 2>&1";
        $output = trim((string) shell_exec($cmd));
        $this->flux_log->write_log('event_guard', json_encode(array(
            'action' => 'fail2ban_unbanip',
            'cmd'    => $cmd,
            'output' => $output,
        )));

        $ok = $this->event_guard_model->set_unblocked($log_uuid);

        if ($ok) {
            @exec("fs_cli -x 'sendevent CUSTOM Event-Subclass::event_guard:unblock' 2>/dev/null");
            $this->flux_log->write_log('event_guard', json_encode(array(
                'action'   => 'unblock',
                'log_uuid' => $log_uuid,
            )));
        }

        echo json_encode(array('success' => $ok, 'f2b_output' => $output));
        $this->session->set_flashdata('flux_notification', $ip . ' ' . gettext('removed from block.'));
        redirect(base_url() . 'event_guard/event_guard_list/');
    }

    function event_guard_block($id = '')
    {
        $this->flux_log->write_log('event_guard', json_encode(array(
            'action' => 'event_guard_block',
            'id'     => $id,
        )));

        $log_id = trim((string) $this->input->post('id'));

        if (empty($log_id)) {
            $id = (int) $id;
            if ($id > 0) {
                $log_id = $id;
            } else {
                $this->session->set_flashdata('flux_notification', gettext('log_uuid required.'));
                redirect(base_url() . 'event_guard/event_guard_list/');
            }
        }

        $log = $this->event_guard_model->get_log_by_id($log_id);

        if (empty($log)) {
            $this->session->set_flashdata('flux_notification', gettext('Record not found.'));
            redirect(base_url() . 'event_guard/event_guard_list/');
        }

        if ($log['log_status'] !== 'unblocked') {
            $this->session->set_flashdata('flux_notification', $ip . ' ' . gettext('IP is not unblocked.'));
            redirect(base_url() . 'event_guard/event_guard_list/');
        }

        $ip       = $log['ip_address'];
        $filter   = $log['filter'];
        $log_uuid = $log['log_uuid'];

        $cmd    = "sudo fail2ban-client set {$filter} banip {$ip} 2>&1";
        $output = trim((string) shell_exec($cmd));
        $this->flux_log->write_log('event_guard', json_encode(array(
            'action' => 'fail2ban_banip',
            'cmd'    => $cmd,
            'output' => $output,
        )));

        $ok = $this->event_guard_model->set_blocked($log_uuid);

        if ($ok) {
            @exec("fs_cli -x 'sendevent CUSTOM Event-Subclass::event_guard:block' 2>/dev/null");
            $this->flux_log->write_log('event_guard', json_encode(array(
                'action'   => 'block',
                'log_uuid' => $log_uuid,
            )));
        }

        echo json_encode(array('success' => $ok, 'f2b_output' => $output));
        $this->session->set_flashdata('flux_notification', $ip . ' ' . gettext('added to block.'));
        redirect(base_url() . 'event_guard/event_guard_list/');
    }

    function event_guard_edit($id = '')
    {
        $data['page_title'] = gettext('Event Guard - Edit Log Entry');

        $id     = (int) $id;
        $record = array();

        if ($id > 0) {
            $record = $this->event_guard_model->get_log_by_id($id);
        }

        if (empty($record)) {
            $this->session->set_flashdata('flux_notification', gettext('Record not found.'));
            redirect(base_url() . 'event_guard/event_guard_list');
            return;
        }

        $data['form'] = $this->form->build_form(
            $this->event_guard_form->get_log_edit_form_fields($id), $record
        );

        $this->load->view('view_event_guard_edit', $data);
    }

    function event_guard_log_save()
    {
        $add_array = $this->input->post();
        $edit_id   = (int) ($add_array['id'] ?? 0);

        $data['form'] = $this->form->build_form(
            $this->event_guard_form->get_log_edit_form_fields($edit_id), $add_array
        );

        if ($edit_id <= 0) {
            echo json_encode(array('event_guard_error' => gettext('Invalid ID.')));
            exit();
        }

        if ($this->form_validation->run() == FALSE) {
            $data['validation_errors'] = validation_errors();
            echo $data['validation_errors'];
            exit();
        }

        $ip = trim((string) ($add_array['ip_address'] ?? ''));
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            echo json_encode(array('event_guard_error' => gettext('Invalid IP address.')));
            exit();
        }

        $allowed_filters  = array('sip-auth-fail', 'sip-auth-ip');
        $allowed_statuses = array('blocked', 'pending', 'unblocked');

        $filter     = trim((string) ($add_array['filter']     ?? ''));
        $log_status = trim((string) ($add_array['log_status'] ?? ''));

        if (!in_array($filter, $allowed_filters)) {
            echo json_encode(array('event_guard_error' => gettext('Invalid chain.')));
            exit();
        }

        if (!in_array($log_status, $allowed_statuses)) {
            echo json_encode(array('event_guard_error' => gettext('Invalid status.')));
            exit();
        }

        $safe_data = array(
            'ip_address' => $ip,
            'filter'     => $filter,
            'log_status' => $log_status,
            'extension'  => trim((string) ($add_array['extension']  ?? '')),
            'user_agent' => trim((string) ($add_array['user_agent'] ?? '')),
        );

        $this->event_guard_model->update_log($safe_data, $edit_id);
        $this->flux_log->write_log('event_guard', json_encode(array(
            'action' => 'log_edit',
            'data'   => $safe_data,
        )));

        echo json_encode(array(
            'SUCCESS' => $ip . ' ' . gettext('Updated Successfully!')
        ));
        exit();
    }

    // ── Whitelist ─────────────────────────────────────────────────────────────

    function event_guard_whitelist_add()
    {
        $data['page_title'] = gettext('Event Guard - Add to Whitelist');
        $data['form'] = $this->form->build_form(
            $this->event_guard_form->get_whitelist_form_fields(), ''
        );
        $this->load->view('view_event_guard_whitelist_add', $data);
    }

    function event_guard_whitelist_edit($id = '')
    {
        $data['page_title'] = gettext('Event Guard - Edit Whitelist');

        $id     = (int) $id;
        $record = array();

        if ($id > 0) {
            $record = $this->event_guard_model->get_whitelist_by_id($id);
        }

        $data['form'] = $this->form->build_form(
            $this->event_guard_form->get_whitelist_form_fields($id), $record
        );
        $this->load->view('view_event_guard_whitelist_add', $data);
    }

    function event_guard_whitelist_save()
    {
        $add_array = $this->input->post();

        $edit_id = isset($add_array['id']) ? (int) $add_array['id'] : 0;
        $data['form'] = $this->form->build_form(
            $this->event_guard_form->get_whitelist_form_fields($edit_id), $add_array
        );

        if ($this->form_validation->run() == FALSE) {
            $data['validation_errors'] = validation_errors();
            echo $data['validation_errors'];
            exit();
        }

        $cidr = trim((string) ($add_array['cidr'] ?? ''));
        if (!$this->_valid_cidr($cidr)) {
            echo json_encode(array('event_guard_error' => gettext('Invalid CIDR format.')));
            exit();
        }

        $safe_data = array(
            'cidr'        => $cidr,
            'description' => trim((string) ($add_array['description'] ?? '')),
        );

        if ($edit_id > 0) {
            if ($this->event_guard_model->whitelist_cidr_exists($cidr, $edit_id)) {
                echo json_encode(array('event_guard_error' => gettext('IP already exists in system.')));
                exit();
            }

            $this->event_guard_model->edit_whitelist($safe_data, $edit_id);
            echo json_encode(array(
                'SUCCESS' => $cidr . ' ' . gettext('Updated Successfully!')
            ));
            exit();

        } else {
            if ($this->event_guard_model->whitelist_cidr_exists($cidr)) {
                echo json_encode(array('event_guard_error' => gettext('IP already exists in system.')));
                exit();
            }

            $this->event_guard_model->add_whitelist($safe_data);
            $this->session->set_flashdata('flux_errormsg', gettext('IP Added Successfully!'));
            echo json_encode(array(
                'SUCCESS' => $cidr . ' ' . gettext('Added Successfully!')
            ));
            exit();
        }
    }

    function event_guard_whitelist_delete($id = 0)
    {
        $id = (int) $id;

        if ($id <= 0) {
            $this->session->set_flashdata('flux_notification', gettext('Invalid ID.'));
            redirect(base_url() . 'event_guard/event_guard_list');
            return;
        }

        $cidr = $this->event_guard_model->get_whitelist_cidr($id);
        $this->event_guard_model->delete_whitelist($id);
        $this->flux_log->write_log('event_guard', json_encode(array(
            'action' => 'whitelist_delete',
            'cidr'   => $cidr,
        )));
        $this->session->set_flashdata('flux_notification', $cidr . ' ' . gettext('removed from whitelist.'));
        redirect(base_url() . 'event_guard/event_guard_whitelist_list/');
    }

    // ── Helpers privados ──────────────────────────────────────────────────────

    private function _valid_cidr($cidr)
    {
        if (strpos($cidr, '/') === false) {
            return (bool) filter_var($cidr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
        }

        $parts = explode('/', $cidr, 2);
        $ip    = $parts[0];
        $bits  = $parts[1];

        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false
            && is_numeric($bits)
            && (int) $bits >= 0
            && (int) $bits <= 32;
    }

    function event_guard_whitelist_list()
    {
        $data['page_title']   = gettext('Event Guard - Whitelist');
        $data['grid_fields']  = $this->event_guard_form->build_whitelist_list_for_admin();
        $data['grid_buttons'] = $this->event_guard_form->build_whitelist_grid_buttons();
        $this->load->view('view_event_guard_whitelist_list', $data);
    }

    function event_guard_whitelist_list_json()
    {
        $count_all   = $this->event_guard_model->get_whitelist_list(false);
        $paging_data = $this->form->load_grid_config($count_all, $_GET['rp'], $_GET['page']);
        $json_data   = $paging_data['json_paging'];
        $query       = $this->event_guard_model->get_whitelist_list(true, $paging_data['paging']['start'], $paging_data['paging']['page_no']);
        $grid_fields = json_decode($this->event_guard_form->build_whitelist_list_for_admin());
        $json_data['rows'] = $this->form->build_grid($query, $grid_fields);
        echo json_encode($json_data);
    }

    function event_guard_whitelist_search()
    {
        $ajax_search = $this->input->post('ajax_search', 0);

        if ($this->input->post('advance_search', TRUE) == 1) {
            $this->session->set_userdata('advance_search', $this->input->post('advance_search'));
            $action = $this->input->post();
            unset($action['action']);
            unset($action['advance_search']);
            $this->session->set_userdata('event_guard_whitelist_list_search', $action);
        }

        if (@$ajax_search != 1) {
            redirect(base_url() . 'event_guard/event_guard_whitelist_list/');
        }
    }

    function event_guard_whitelist_clearsearchfilter()
    {
        $this->session->set_userdata('advance_search', 0);
        $this->session->set_userdata('event_guard_whitelist_list_search', '');
        redirect(base_url() . 'event_guard/event_guard_whitelist_list/');
    }

    function event_guard_delete_multiple()
    {
        $ids = $this->input->post('selected_ids', true);
        $this->flux_log->write_log('event_guard', json_encode(array(
            'action'       => 'event_guard_delete_multiple',
            'selected_ids' => $ids,
        )));

        if ($ids) {
            foreach ($ids as $id) {
                $this->event_guard_unblock($id);
            }
        }

        echo $this->event_guard_model->delete_multiple_logs($ids);
    }

}

?>
