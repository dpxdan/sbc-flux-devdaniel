<?php

// ##############################################################################
// Flux SBC - Unindo pessoas e negócios
//
// Copyright (C) 2022 Flux Telecom
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
// #############################################################################
class Freeswitch_model extends CI_Model
{

    function __construct()
    {
		parent::__construct();
        $this->load->library('flux_log');
	}

    function get_sipdevices_list($flag, $accountid = "", $entitytype = '', $start = "", $limit = "")
    {
        if ($accountid != "") {
            $where = array(
                "accountid" => $accountid
            );
        }
        $this->db_model->build_search('fssipdevices_list_search');
        $instant_search = $this->session->userdata('left_panel_search_' . $entitytype . '_sipdevices');
        if (! empty($instant_search)) {
            $like_str = "(username like '%$instant_search%' OR creation_date like '%$instant_search%' OR last_modified_date like '%$instant_search%')";
            $this->db->where($like_str);
        }
        $query = array();
        if ($flag) {
            $deviceinfo = $this->db_model->select("*", "sip_devices", $where, "id", "ASC", $limit, $start);
            if ($deviceinfo->num_rows() > 0) {
                $add_array = $deviceinfo->result_array();
                foreach ($add_array as $key => $value) {
                    $vars = json_decode($value['dir_vars']);
                    $vars_new = json_decode($value['dir_params'], true);
                    $passowrds = json_decode($value['dir_params']);
                    $query[] = array(
                        'id' => $value['id'],
                        'username' => $value['username'],
                        'accountid' => $value['accountid'],
                        'status' => $value['status'],
                        'sip_profile_id' => $value['sip_profile_id'],
                        'effective_caller_id_name' => $vars->effective_caller_id_name,
                        'voicemail_enabled' => $vars_new['vm-enabled'],
                        'voicemail_password' => $vars_new['vm-password'],
                        'voicemail_mail_to' => $vars_new['vm-mailto'],
                        'voicemail_attach_file' => $vars_new['vm-attach-file'],
                        'vm_keep_local_after_email' => $vars_new['vm-keep-local-after-email'],
                        'effective_caller_id_number' => $vars->effective_caller_id_number,
                        'password' => $passowrds->password,
                        'creation_date' => $value['creation_date'],
                        'last_modified_date' => $value['last_modified_date']
                    );
                }
            }
        } else {
            $query = $this->db_model->countQuery("*", "sip_devices", $where);
        }
        return $query;
    }

    function add_freeswith($add_array)
    {
        $where = array(
            'id' => $add_array['accountcode']
        );
        $query = $this->db_model->getSelect("*", "accounts", $where);
        $query = $query->result_array();
        $account_data = $query[0];

        $log_type = $this->session->userdata("logintype");
        $add_reseller = isset($add_array['reseller_id']) ? $add_array['reseller_id'] : 0;
        $reseller_id = $log_type == 1 ? $account_data['reseller_id'] : $add_reseller;
        $sip_profile_id = isset($add_array['sip_profile_id']) ? $add_array['sip_profile_id'] : $this->common->get_field_name('id', 'sip_profiles', array(
            'name' => 'default'
        ));

        $parms_array = array(
            'password' => $add_array['fs_password'],
            'vm-enabled' => $add_array['voicemail_enabled'],
            'vm-password' => $add_array['voicemail_password'],
            'vm-mailto' => $add_array['voicemail_mail_to'],
            'vm-attach-file' => $add_array['voicemail_attach_file'],
            'vm-keep-local-after-email' => $add_array['vm_keep_local_after_email'],
            'vm-email-all-messages' => $add_array['vm_send_all_message']
        );

        $add_array['status'] = isset($add_array['status']) ? $add_array['status'] : "0";
        $parms_array_vars = array(
            'effective_caller_id_name' => $add_array['effective_caller_id_name'],
            'effective_caller_id_number' => $add_array['effective_caller_id_number'],
            'user_context' => 'default'
        );
        $new_array = array(
            'creation_date' => gmdate('Y-m-d H:i:s'),
            'last_modified_date' => gmdate('Y-m-d H:i:s'),
            'username' => $add_array['fs_username'],
            'reseller_id' => $reseller_id,
            'accountid' => $add_array['accountcode'],
            'status' => $add_array['status'],
            'dir_params' => json_encode($parms_array),
            'dir_vars' => json_encode($parms_array_vars),
            'sip_profile_id' => $sip_profile_id,
            'codec' => $add_array['codec']

        );
        $this->db->insert('sip_devices', $new_array);
        $mail = (isset($add_array['voicemail_mail_to']) && $add_array['voicemail_mail_to'] != "") ? $add_array['voicemail_mail_to'] : $account_data['email'];

        $add_array['id'] = $add_array['accountcode'];
        $add_array['reseller_id'] = $reseller_id;
        $add_array['email'] = $mail;
        $add_array['first_name'] = $account_data['first_name'];
        $add_array['last_name'] = $account_data['last_name'];
        $add_array['number'] = $add_array['fs_username'];
        $add_array['password'] = $add_array['fs_password'];
        $this->common->mail_to_users('create_sip_device', $add_array);
        return true;
    }

    function edit_freeswith($add_array, $id)
    {
        $this->db->select('accountid');
        $accountid = (array) $this->db->get_where('sip_devices', array(
            "username" => $add_array['fs_username']
        ))->first_row();
        $parms_array = array(
            'password' => $add_array['fs_password'],
            'vm-enabled' => $add_array['voicemail_enabled'],
            'vm-password' => $add_array['voicemail_password'],
            'vm-mailto' => $add_array['voicemail_mail_to'],
            'vm-attach-file' => $add_array['voicemail_attach_file'],
            'vm-keep-local-after-email' => $add_array['vm_keep_local_after_email'],
            'vm-email-all-messages' => $add_array['vm_send_all_message']
        );

        $parms_array_vars = array(
            'effective_caller_id_name' => $add_array['effective_caller_id_name'],
            'effective_caller_id_number' => $add_array['effective_caller_id_number']
        );
        $log_type = $this->session->userdata("logintype");
        if ($log_type == 0 || $log_type == 3 || $log_type == 1) {
            $add_array['sip_profile_id'] = $this->common->get_field_name('id', 'sip_profiles', array(
                'name' => 'default'
            ));
            
        }

        $add_array['status'] = isset($add_array['status']) ? $add_array['status'] : "0";
        $add_array['sip_domain'] = $this->common->get_field_name('domain', 'domain', array(
                        'id' => $add_array['sip_domain_id']
                    ));
        $accountid = $log_type == 1 ? $accountid['accountid'] : $add_array['accountcode'];
        $accountcode = (isset($add_array['accountcode'])) ? $add_array['accountcode'] : $accountid;
        // Busca o domain do sip_profile para calcular ha1
        $profile = (array) $this->db->select('domain_name')
            ->get_where('sip_profiles', ['id' => $add_array['sip_profile_id']])
            ->first_row();
        $domain = !empty($add_array['sip_domain']) ? $add_array['sip_domain'] : '';
        
        $ha1 = md5($add_array['fs_username'] . ':' . $domain . ':' . $add_array['fs_password']);
        $new_array = array(
            'last_modified_date' => gmdate('Y-m-d H:i:s'),
            'username' => $add_array['fs_username'],
            'accountid' => $accountcode,
            'status' => $add_array['status'],
            'dir_params' => json_encode($parms_array),
            'dir_vars' => json_encode($parms_array_vars),
            'sip_profile_id' => $add_array['sip_profile_id'],
            'codec' => $add_array['codec'],
            'ha1' => $ha1,
            'domain' => $add_array['sip_domain'],
            'sip_domain_id' => $add_array['sip_domain_id']

        );

        $this->db->where('id', $id);
        $this->db->update('sip_devices', $new_array);
        return true;
    }

    function get_edited_data($edit_id)
    {
        $deviceinfo = array();
        $where = array(
            'id' => $edit_id
        );
        $deviceinfo = $this->db_model->getSelect("*", "sip_devices", $where);
        $add_array = $deviceinfo->result_array();
        foreach ($add_array as $key => $value) {
            $vars = json_decode($value['dir_vars']);
            $vars_new = json_decode($value['dir_params'], true);
            $passowrds = json_decode($value['dir_params']);
            $query = array(
                'id' => $value['id'],
                'reseller_id' => $value['reseller_id'],
                'fs_username' => $value['username'],
                'accountcode' => $value['accountid'],
                'status' => $value['status'],
                'codec' => $value['codec'],
                'sip_profile_id' => $value['sip_profile_id'],
                'effective_caller_id_name' => $vars->effective_caller_id_name,
                'voicemail_enabled' => $vars_new['vm-enabled'],
                'voicemail_password' => $vars_new['vm-password'],
                'voicemail_mail_to' => $vars_new['vm-mailto'],
                'voicemail_attach_file' => $vars_new['vm-attach-file'],
                'vm_keep_local_after_email' => $vars_new['vm-keep-local-after-email'],
                'vm_send_all_message' => $vars_new['vm-email-all-messages'],
                'effective_caller_id_number' => $vars->effective_caller_id_number,
                'fs_password' => $passowrds->password,
                'ha1'         => $value['ha1'],
                'domain' => $value['domain'],
                'sip_domain_id' => $value['sip_domain_id'],
            );
        }
        return $query;
    }

    function delete_freeswith_devices($id)
    {
        $this->db->where('id', $id);
        $this->db->delete('sip_devices');
        return true;
    }

    function fs_retrieve_sip_user($flag, $start = 0, $limit = 0)
    {
        $deviceinfo = array();
        $this->db_model->build_search('fssipdevices_list_search');
        $accountinfo = $this->session->userdata("accountinfo");
        $reseller_id = $accountinfo['type'] == 1 ? $accountinfo['id'] : 0;
        $query = array();
        $logintype = $this->session->userdata("logintype");
        $where = array();
        if ($this->session->userdata('logintype') == 1 || $this->session->userdata('logintype') == 5) {
            $where['reseller_id'] = $reseller_id;
        }
        if ($flag) {
            $deviceinfo = $this->db_model->select("*", "sip_devices", $where, "id", "ASC", $limit, $start);
            if ($deviceinfo->num_rows() > 0) {
                $add_array = $deviceinfo->result_array();
                foreach ($add_array as $key => $value) {
                    $vars = json_decode($value['dir_vars']);
                    $vars_new = json_decode($value['dir_params'], true);
                    $passowrds = json_decode($value['dir_params']);
                    $query[] = array(
                        'id' => $value['id'],
                        'username' => $value['username'],
                        'accountid' => $value['accountid'],
                        'reseller_id' => $value['reseller_id'],
                        'status' => $value['status'],
                        'creation_date' => $value['creation_date'],
                        'last_modified_date' => $value['last_modified_date'],
                        'sip_profile_id' => $value['sip_profile_id'],
                        'effective_caller_id_name' => $vars->effective_caller_id_name,
                        'voicemail_enabled' => $vars_new['vm-enabled'],
                        'voicemail_password' => $vars_new['vm-password'],
                        'voicemail_mail_to' => $vars_new['vm-mailto'],
                        'voicemail_attach_file' => $vars_new['vm-attach-file'],
                        'vm_keep_local_after_email' => $vars_new['vm-keep-local-after-email'],
                        'effective_caller_id_number' => $vars->effective_caller_id_number,
                        'password' => $passowrds->password,
                        'live_status' => $value['live_status'],
                        'ha1'         => $value['ha1'],
                        'domain' => $value['domain'],
                        'sip_domain_id' => $value['sip_domain_id']
                    );
                }
            }
        } else {
            $query = $this->db_model->countQuery("*", 'sip_devices', $where);
        }
        return $query;
    }

    function get_gateway_list($flag, $start = 0, $limit = 0)
    {
        $this->db_model->build_search('fsgateway_list_search');
        if ($flag) {
            $query = $this->db_model->select("*", "gateways", "", "id", "desc", $limit, $start);
        } else {
            $query = $this->db_model->countQuery("*", "gateways", "");
        }
        return $query;
    }

    function get_sipprofile_list($flag, $start = 0, $limit = 0)
    {
        $this->db_model->build_search('fssipprofile_list_search');
        if ($flag) {
            $query = $this->db_model->select("*", "sip_profiles", "", "id", "desc", $limit, $start);
        } else {
            $query = $this->db_model->countQuery("*", "sip_profiles", "");
        }
        return $query;
    }

    function profile_authentication($profile_data)
    {
        if ($profile_data['id'] != '') {
            $where = array(
                'id <>' => $profile_data["id"],
                'sip_ip' => $profile_data["sip_ip"],
                "sip_port" => $profile_data["sip_port"]
            );
        } else {

            $where = array(
                'sip_ip' => $profile_data["sip_ip"],
                "sip_port" => $profile_data["sip_port"]
            );
        }
        $query = $this->db_model->getSelect("*", "sip_profiles", $where);

        return $query;
    }
    
    function get_fsserver_list($flag, $start = 0, $limit = 0)
    {
        $this->db_model->build_search('fsserver_list_search');
        if ($flag) {
            $query = $this->db_model->select("*", "freeswich_servers", "", "id", "desc", $limit, $start);
        } else {
            $query = $this->db_model->countQuery("*", "freeswich_servers", "");
        }
        return $query;
    }
    
    function add_fssever($data)
    {
        $data['creation_date'] = gmdate('Y-m-d H:i:s');
        $data['last_modified_date'] = gmdate('Y-m-d H:i:s');
        unset($data['action']);
        $this->db->insert('freeswich_servers', $data);
        return true;
    }
    
    function edit_fsserver($data, $id)
    {
        unset($data['action']);
        $data['last_modified_date'] = gmdate('Y-m-d H:i:s');
        $this->db->where('id', $id);
        $this->db->update('freeswich_servers', $data);
        return true;
    }
    
    function fsserver_delete($id)
    {
        $this->db->where('id', $id);
        $this->db->delete('freeswich_servers');
        return true;
    }

     function reload_freeswitch($command, $server_host = "")
    {
        $response = '';
        $query = $this->db_model->getSelect("*", "freeswich_servers", "");
        $fs_data = $query->result_array();
        foreach ($fs_data as $fs_key => $fs_value) {
            $fp = $this->freeswitch_lib->event_socket_create($fs_value["freeswitch_host"], $fs_value["freeswitch_port"], $fs_value["freeswitch_password"]);
            if ($fp) {
                $response .= $this->freeswitch_lib->event_socket_request($fp, $command);
                fclose($fp);
            }
        }
        return $response;
    }

    function reload_live_freeswitch($command)
    {
        $response = '';
        $query = $this->db_model->getSelect("*", "freeswich_servers", "");
        $fs_data = $query->result_array();

        foreach ($fs_data as $fs_key => $fs_value) {
            $fp = $this->freeswitch_lib->event_socket_create($fs_value["freeswitch_host"], $fs_value["freeswitch_port"], $fs_value["freeswitch_password"]);
            if ($fp) {
                $response .= $this->freeswitch_lib->event_socket_request($fp, $command);
                fclose($fp);
            }
        }
        $response = str_replace("0 total.", "", $response);
        return $response;
    }

    function delete_freeswith_channel($id)
    {
        $this->db->where('uuid', $id);
        $this->db->delete('channels');
        return true;
    }

    function check_unique_gateway_name($action, $Select, $value)
    {
        $where = array(
            $Select => $value,
            "status" => 0
        );
        if ($action == 'edit') {
            $this->db->where($where);
            $this->db->select("*");
            $this->db->from('gateways');
            $query = $this->db->get();
        } else {
            $query = $this->db_model->countQuery("*", "gateways", $where);
        }

        return $query;
    }
    function get_sip_device_username($id)
    {
        $this->db->select('username');
        $result = (array) $this->db->get_where('sip_devices', array('id' => $id))->first_row();
        return isset($result['username']) ? $result['username'] : '';
    }

    function get_active_sip_profile_names()
    {
        return $this->db->query("SELECT name FROM sip_profiles where status = 0")->result_array();
    }

    function delete_sip_devices_by_ids($ids)
    {
        $where = "id IN ($ids)";
        $this->db->where($where, null, false);
        return $this->db->delete('sip_devices');
    }

    function update_gateway($gateway_id, $insert_arr)
    {
        $this->db->select('name,sip_profile_id');
        $old_gateway_info = (array) $this->db->get_where('gateways', array('id' => $gateway_id))->first_row();
        $updated = $this->db->update('gateways', $insert_arr, array('id' => $gateway_id));
        $new_profile_id = isset($insert_arr['sip_profile_id']) ? $insert_arr['sip_profile_id'] : $old_gateway_info['sip_profile_id'];
        $this->db->select('name,sip_ip');
        $old_sip_profile_info = (array) $this->db->get_where('sip_profiles', array('id' => $old_gateway_info['sip_profile_id']))->first_row();
        $this->db->select('name,sip_ip');
        $sip_profile_info = (array) $this->db->get_where('sip_profiles', array('id' => $new_profile_id))->first_row();
        return array(
            'updated' => $updated,
            'old_gateway_info' => $old_gateway_info,
            'old_sip_profile_info' => $old_sip_profile_info,
            'sip_profile_info' => $sip_profile_info
        );
    }

    function add_gateway($insert_arr)
    {
        $inserted = $this->db->insert('gateways', $insert_arr);
        $this->db->select('name,sip_ip');
        $sip_profile_info = (array) $this->db->get_where('sip_profiles', array('id' => $insert_arr['sip_profile_id']))->first_row();
        return array(
            'inserted' => $inserted,
            'sip_profile_info' => $sip_profile_info
        );
    }

    function get_gateway_delete_context($gateway_id)
    {
        $this->db->select('name,sip_profile_id');
        $gateway_info = (array) $this->db->get_where('gateways', array('id' => $gateway_id))->first_row();
        $this->db->select('name,sip_ip');
        $profile_info = (array) $this->db->get_where('sip_profiles', array('id' => $gateway_info['sip_profile_id']))->first_row();
        return array(
            'gateway_info' => $gateway_info,
            'profile_info' => $profile_info
        );
    }

    function delete_gateway($gateway_id)
    {
        return $this->db_model->delete('gateways', array('id' => $gateway_id));
    }

    function get_gateway_bulk_delete_context($ids)
    {
        $where = "id IN ($ids)";
        $this->db->where($where, null, false);
        $this->db->select('group_concat(sip_profile_id) as sip_profile_id');
        $this->db->from('gateways');
        $sip_profile_ids = (array) $this->db->get()->first_row();
        $sip_profile_arr = array();
        if (!empty($sip_profile_ids['sip_profile_id'])) {
            $this->db->select('id,name,sip_ip');
            $this->db->where("id IN (" . $sip_profile_ids['sip_profile_id'] . ")", null, false);
            $this->db->from('sip_profiles');
            $result_array = $this->db->get()->result_array();
            foreach ($result_array as $value) {
                $sip_profile_arr[$value['id']] = array(
                    'name' => $value['name'],
                    'sip_ip' => $value['sip_ip']
                );
            }
        }
        $this->db->select('id,name,sip_profile_id');
        $this->db->where($where, null, false);
        $this->db->from('gateways');
        $gateway_info = $this->db->get()->result_array();
        return array(
            'sip_profile_arr' => $sip_profile_arr,
            'gateway_info' => $gateway_info
        );
    }

    function delete_gateways_by_ids($ids)
    {
        $where = "id IN ($ids)";
        $this->db->where($where, null, false);
        return $this->db->delete('gateways');
    }


    function delete_sip_profiles_by_ids($ids)
    {
        $where = "id IN ($ids)";
        $this->db->where($where, null, false);
        return $this->db->delete('sip_profiles');
    }

    function add_sip_profile($data)
    {
        return $this->db->insert('sip_profiles', $data);
    }

    function update_sip_profile($id, $data)
    {
        return $this->db->update('sip_profiles', $data, array('id' => $id));
    }


}
