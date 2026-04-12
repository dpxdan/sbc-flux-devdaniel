<?php

// ##############################################################################
// Flux Telecom - Unindo pessoas e negócios
//
// Copyright (C) 2021 Flux Telecom
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
class user_model extends CI_Model
{

    function __construct()
    {
        parent::__construct();
        if (Common_model::$global_config['system_config']['opensips'] == 0) {
            $db_config = Common_model::$global_config['system_config'];
            $opensipdsn = "mysqli://" . $db_config['opensips_dbuser'] . ":" . $db_config['opensips_dbpass'] . "@" . $db_config['opensips_dbhost'] . "/" . $db_config['opensips_dbname'] . "?char_set=utf8&dbcollat=utf8_general_ci&cache_on=true&cachedir=";
            $this->opensips_db = $this->load->database($opensipdsn, true);
        }
    }

    function validate_password($pass, $id)
    {
        $this->db->select('password');
        $this->db->where('number', $id);
        $query = $this->db->get('accounts');
        $count = $query->num_rows();
        return $count;
    }

    function update_password($newpass, $id)
    {
        $this->db->update('password', $newpass);
        $this->db->where('number', $id);
        $result = $this->db->get('accounts');
        return $result->result();
    }

    function change_password($id)
    {
        $this->db->select('password');
        $this->db->where('id', $id);
        $query = $this->db->get('accounts');
        $result = $query->result();
        return $result;
    }

    function change_db_password($update, $id)
    {
        $this->db->where('id', $id);
        $this->db->update('accounts', array(
            'password' => $update
        ));
    }

    function edit_account($accountinfo, $edit_id)
    {
        unset($accountinfo['action']);
        $update_profile_arr = array(
            "company_name" => $accountinfo['company_name'],
            "first_name" => $accountinfo['first_name'],
            "last_name" => $accountinfo['last_name'],
            "telephone_1"=>$accountinfo['telephone_1'],
            "telephone_2"=>$accountinfo['telephone_2'],
            "email"=>$accountinfo['email'],
            "address_1"=>$accountinfo['address_1'],
            "address_2"=>$accountinfo['address_2'],
            "city"=>$accountinfo['city'],
            "province"=>$accountinfo['province'],
            "postal_code"=>$accountinfo['postal_code'],
            "tax_number"=>$accountinfo['tax_number'],
            "timezone_id"=>$accountinfo['timezone_id']
        );
        $this->db->where('id', $edit_id);
        $result = $this->db->update('accounts', $update_profile_arr);
        return true;
    }

    function get_user_packages_list($flag, $start = 0, $limit = 0)
    {
        $this->db_model->build_search('package_list_search');
        $account_data = $this->session->userdata("accountinfo");
        $where = array(
            "pricelist_id" => $account_data['pricelist_id']
        );
        if ($flag) {
            $query = $this->db_model->getSelect("*", "packages", $where, "id", "ASC", $limit, $start);
        } else {
            $query = $this->db_model->countQuery("*", "packages", $where);
        }
        return $query;
    }

    function get_user_invoices_list($flag, $start = 0, $limit = 0)
    {
        $this->db_model->build_search('user_invoice_list_search');
        $accountinfo = $this->session->userdata('accountinfo');
        $reseller_id = $accountinfo['id'];
        $this->db->where('accountid', $reseller_id);
        if ($flag) {
            $this->db->select('*');
        } else {
            $this->db->select('count(id) as count');
        }
        if ($flag) {
            if (isset($_GET['sortname']) && $_GET['sortname'] != 'undefined') {
                $this->db->order_by($_GET['sortname'], ($_GET['sortorder'] == 'undefined') ? 'desc' : $_GET['sortorder']);
            } else {
                $this->db->order_by('invoice_date', 'desc');
            }
        }
        $result = $this->db->get('invoices');
        if ($result->num_rows() > 0) {
            if ($flag) {
                return $result;
            } else {
                $result = $result->result_array();
                return $result[0]['count'];
            }
        } else {
            if ($flag) {
                $query = (object) array(
                    'num_rows' => 0
                );
            } else {
                $query = 0;
            }
            return $query;
        }
    }


    function get_user_refill_list($flag, $start = '', $limit = '')
    {
        $this->db_model->build_search('user_refill_report_search');
        $accountinfo = $this->session->userdata['accountinfo'];

        if ($flag) {
            $query = $this->db_model->getJionQuery('payment_transaction', 'payment_transaction.*,invoice_details.*,', array(
                'invoice_details.product_category' => 3,
                'payment_transaction.accountid' => $accountinfo['id']
            ), 'invoice_details', 'payment_transaction.id=invoice_details.payment_id', 'inner', $limit, $start, 'DESC', 'payment_transaction.id');
        } else {
            $query = $this->db_model->getJionQueryCount('payment_transaction', 'payment_transaction.*', array(
                'invoice_details.product_category' => 3,
                'payment_transaction.accountid' => $accountinfo['id']
            ), 'invoice_details', 'payment_transaction.id=invoice_details.payment_id', 'inner', '', '', 'DESC', 'payment_transaction.id');
        }

        return $query;
    }

    function get_user_emails_list($flag, $start = 0, $limit = 0)
    {
        $account_data = $this->session->userdata("accountinfo");
        $this->db_model->build_search('user_emails_search');

        $where = array(
            'accountid' => $account_data['id']
        );
        if ($flag) {
            $query = $this->db_model->select("*", "mail_details", $where, "id", "DESC", $limit, $start);
        } else {
            $query = $this->db_model->countQuery("*", "mail_details", $where);
        }

        return $query;
    }

    function add_invoice_config($add_array)
    {
        $result = $this->db->insert('invoice_conf', $add_array);
        return true;
    }

    function edit_invoice_config($add_array, $edit_id)
    {
        $this->db->where('id', $edit_id);
        $result = $this->db->update('invoice_conf', $add_array);
        return true;
    }

    function edit_alert_threshold($add_array, $edit_id)
    {
        $this->db->where('id', $edit_id);
        $result = $this->db->update('accounts', $add_array);
        return true;
    }

    function user_ipmap_list($flag, $limit = '', $start = '')
    {
        $accountinfo = $this->session->userdata('accountinfo');
        $where['accountid'] = $accountinfo['id'];
        $this->db_model->build_search('user_ipmap_search', 'ip_map.');
        if ($flag) {
            $query = $this->db_model->select("*", "ip_map", $where, "id", "ASC", $limit, $start);
        } else {
            $query = $this->db_model->countQuery("*", "ip_map", $where);
        }
        return $query;
    }

    function user_sipdevices_list($flag, $accountid = "", $start = "", $limit = "")
    {
        $where = array(
            "accountid" => $accountid
        );
        $this->db_model->build_search('user_sipdevices_search');
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

    function user_sipdevice_info($edit_id)
    {
        $sipdevice_info = $this->db_model->getSelect("*", "sip_devices", array(
            'id' => $edit_id
        ));
        $sipdevice_arr = (array) $sipdevice_info->first_row();
        $vars = (array) json_decode($sipdevice_arr['dir_vars']);
        $params = (array) json_decode($sipdevice_arr['dir_params'], true);
        $query = array(
            'id' => $sipdevice_arr['id'],
            'fs_username' => $sipdevice_arr['username'],
            'accountcode' => $sipdevice_arr['accountid'],
            'status' => $sipdevice_arr['status'],
            'codec' => $sipdevice_arr['codec'],
            'effective_caller_id_name' => $vars['effective_caller_id_name'],
            'effective_caller_id_number' => $vars['effective_caller_id_number'],
            'voicemail_enabled' => $params['vm-enabled'],
            'voicemail_password' => $params['vm-password'],
            'voicemail_mail_to' => $params['vm-mailto'],
            'voicemail_attach_file' => $params['vm-attach-file'],
            'vm_keep_local_after_email' => $params['vm-keep-local-after-email'],
            'vm_send_all_message' => $params['vm-email-all-messages'],
            'fs_password' => $params['password']
        );
        return $query;
    }

    function user_sipdevice_add($add_array)
    {
        $account_data = $this->session->userdata("accountinfo");
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
            'accountid' => $account_data['id'],
            'status' => $add_array['status'],
            'codec' => $add_array['codec'],
            'dir_params' => json_encode($parms_array),
            'dir_vars' => json_encode($parms_array_vars),
            'sip_profile_id' => $this->common->get_field_name('id', 'sip_profiles', array(
                'name' => 'default'
            ))
        );
        $this->db->insert('sip_devices', $new_array);
        $this->common->mail_to_users('create_sip_device', $account_data);
        return true;
    }

    function user_sipdevice_edit($add_array)
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
        $new_array = array(
            'last_modified_date' => gmdate('Y-m-d H:i:s'),
            'username' => $add_array['fs_username'],
            'status' => $add_array['status'],
            'codec' => $add_array['codec'],
            'dir_params' => json_encode($parms_array),
            'dir_vars' => json_encode($parms_array_vars),
            'sip_profile_id' => $add_array['sip_profile_id']
        );
        $this->db->update('sip_devices', $new_array, array(
            'id' => $add_array['id']
        ));
        return true;
    }

    function getuser_cdrs_list($flag, $start, $limit, $export = true)
    {
        $this->db_model->build_search('user_cdrs_report_search');
        $account_data = $this->session->userdata("accountinfo");
        $field_name = 'debit';
        $cdrs_table = 'cdrs';
        if ($account_data['type'] == 0 || $account_data['type'] == 1) {
            $where['accountid'] = $account_data['id'];
        }
        if ($account_data['type'] == 3) {
            $where['accountid'] = $account_data['id'];
            $field_name = 'cost';
        }
        $table_name = $account_data['type'] != 1 ? 'cdrs' : 'reseller_cdrs';
        if ($this->session->userdata('advance_search_date') == 1) {
            $where['callstart >= '] = date("Y-m-d") . " 00:00:00";
            $where['callstart <='] = date("Y-m-d") . " 23:59:59";
        } else {
            if ($this->session->userdata('cdrs_year') != '' and $this->session->userdata('cdrs_year') != '0') {
                $table_name = $this->session->userdata('cdrs_year');
            }
        }
        $this->db->where($where);
        $this->db->order_by("callstart desc");
        if ($flag) {
            if (! $export)
                $this->db->limit($limit, $start);
            $this->db->select('callstart,is_recording,sip_user,callerid,call_direction,callednum,pattern,notes,billseconds,disposition,debit,cost,accountid,pricelist_id,calltype,is_recording,trunk_id,uniqueid,uniqueid as uid');
        } else {
            $this->db->select('count(*) as count,sum(billseconds) as billseconds,sum(debit) as total_debit,sum(cost) as total_cost,SUM(CASE WHEN calltype = "Gratuita" THEN debit ELSE 0 END) AS free_debit,group_concat(distinct(pricelist_id)) as pricelist_ids,group_concat(distinct(trunk_id)) as trunk_ids');
        }

        $result = $this->db->get($table_name);
        return $result;
    }

    function user_fund_transfer($data, $accountinfo)
    {
        $data["payment_by"] = $accountinfo['reseller_id'] > 0 ? $accountinfo['reseller_id'] : - 1;
        $data['accountid'] = $data['id'];
        $data['payment_mode'] = $data['payment_type'];
        unset($data['action'], $data['id'], $data['account_currency'], $data['payment_type']);
        if (isset($data)) {
            $data['credit'] = $data['credit'] == '' ? 0 : $data['credit'];
            $date = gmdate('Y-m-d H:i:s');
            $accountid = $data['accountid'];
            while ($accountid > 0) {
                $customer_id = $accountid;
                $accountid = $this->common_model->get_parent_info($accountid);
                $parent_id = $accountid > 0 ? $accountid : - 1;

                if ($data['payment_mode'] == 0) {
                    $insert_arr = array(
                        "accountid" => $customer_id,
                        "credit" => $data['credit'],
                        'payment_mode' => $data['payment_mode'],
                        'type' => "SYSTEM",
                        "notes" => $data['notes'],
                        "payment_date" => $date,
                        'payment_by' => $parent_id
                    );
                    return $this->db->insert("payments", $insert_arr);
                }
            }
        }
    }

    function user_dashboard_recent_recharge_info()
    {
        $accountinfo = $this->session->userdata('accountinfo');
        $userlevel_logintype = $this->session->userdata('userlevel_logintype');

        $where_arr = array(
            'payment_by' => - 1
        );
        if ($userlevel_logintype == 1) {
            $where_arr = array(
                'payment_by' => $accountinfo['id']
            );
        }
        if ($userlevel_logintype == 0 || $userlevel_logintype == 3) {
            $where_arr = array(
                'accountid' => $accountinfo['id']
            );
        }
        $this->db->where($where_arr);
        $this->db->select('id,accountid,credit,payment_date,notes');
        $this->db->from('payments');
        $this->db->limit(10);
        $this->db->order_by('payment_date', 'desc');
        return $this->db->get();
    }

    function get_user_rates_list($flag, $start = 0, $limit = 0)
    {
        $this->db_model->build_search('user_rates_list_search');
        $account_data = $this->session->userdata("accountinfo");
        $price_list_id = $account_data['pricelist_id'];
        $where = '(pricelist_id="' . $price_list_id . '" OR accountid="' . $account_data["id"] . '") and status=0';
        if ($flag) {
            $query = $this->db_model->select("*", "routes", $where, "id", "ASC", $limit, $start);
        } else {
            $query = $this->db_model->countQuery("*", "routes", $where);
        }
        return $query;
    }

    function get_user_opensips($flag, $account_number = "", $start = "0", $limit = "0")
    {
        $this->db_model->build_search_opensips($this->opensips_db, 'user_opensips_search');
        $this->opensips_db->where('accountcode', $account_number);
        if ($flag) {
            $this->opensips_db->limit($limit, $start);
        }
        $result = $this->opensips_db->get("subscriber");
        if ($result->num_rows() > 0) {
            if ($flag) {
                return $result;
            } else {
                return $result->num_rows();
            }
        } else {
            if ($flag) {
                $result = (object) array(
                    'num_rows' => 0
                );
            } else {
                $result = 0;
            }
            return $result;
        }
    }

    function user_opensips_add($data)
    {
        unset($data["action"]);
        $data['creation_date'] = gmdate("Y-m-d H:i:s");
        $accountinfo = $this->session->userdata('accountinfo');
        $data['reseller_id'] = $accountinfo['type'] == 1 ? $accountinfo['id'] : 0;
        $this->opensips_db->insert("subscriber", $data);
    }

    function user_opensips_edit($data, $id)
    {
        unset($data["action"]);
        $data = array(
            "username" => $data['username'],
            "password" => $data['password'],
            "accountcode" => $data['accountcode'],
            "domain" => $data['domain']
        );
        $this->opensips_db->where("id", $id);
        $data['last_modified_date'] = gmdate("Y-m-d H:i:s");
        $this->opensips_db->update("subscriber", $data);
    }

    function user_opensips_delete($id)
    {
        $this->opensips_db->where("id", $id);
        $this->opensips_db->delete("subscriber");
        return true;
    }

    function get_user_invoice_list($flag, $start = 0, $limit = 0)
    {
        $this->db_model->build_search('user_invoice_list_search');
        $accountinfo = $this->session->userdata('accountinfo');
        $where = array(
            "accountid" => $accountinfo['id'],
            'confirm' => 1
        );
        $this->db->where($where);
        $or_where = "(type='I' OR type='R')";
        $this->db->where($or_where);
        if ($flag) {
            $query = $this->db_model->select("*", "view_invoices", "", "generate_date", "desc", $limit, $start);
        } else {
            $query = $this->db_model->countQuery("*", "view_invoices", "");
        }

        return $query;
    }

    function get_user_cdrs_info($flag, $accountid, $start = 0, $limit = 0)
    {
        if ($flag) {
            $query = $this->db_model->select("*", "reseller_cdrs", "", "invoice_date", "desc", $limit, $start);
        } else {
            $query = $this->db_model->countQuery("*", "invoices", "");
        }
    }

    function get_invoiceconf($accountid)
    {
        $return_array = array();
        $logintype = $this->session->userdata('logintype');
        if ($logintype == 1 || $logintype == 5) {

            $where = array(
                "accountid" => $this->session->userdata["accountinfo"]['id']
            );
        } else {
            $where = array(
                'id' => $accountid
            );
        }
        $query = $this->db_model->getSelect("*", "invoice_conf", $where);
        foreach ($query->result_array() as $key => $value) {
            $return_array = $value;
        }
        return $return_array;
    }

    function getprovider_cdrs_list($flag, $start = 0, $limit = 0)
    {
        $this->db_model->build_search('user_provider_cdrs_report_search');
        $account_data = $this->session->userdata("accountinfo");
        $where['provider_id'] = $account_data['id'];

        if ($this->session->userdata('advance_search_date') == 1) {
            $where['callstart >= '] = date("Y-m-d") . " 00:00:00";
            $where['callstart <='] = date("Y-m-d") . " 23:59:59";
        }
        if ($flag) {
            $query = $this->db_model->select("*", "cdrs", $where, "callstart", "DESC", $limit, $start);
        } else {
            $query = $this->db_model->countQuery("*", "cdrs", $where);
        }
        return $query;
    }

    function getproducts_list($flag, $start = 0, $limit = 0){
        // $this->db_model->build_search('orders_list_search', 'orders.');
        $account_data = $this->session->userdata("accountinfo");
        $where_arr = array (
            "accountid"=>$account_data['id'],
            "is_terminated" => 0
        );
        
        if ($flag) {
            $query = $this->db_model->select("*", "packages_view", $where_arr, "", "desc", $limit, $start);
        } else {
            $query = $this->db_model->select("*", "packages_view", $where_arr, "", "desc", "", "");
        }
        
        return $query;
    }

    function edit_user_pin($add_array, $id)
    {
        $this->db->where('id', $id);
        $data['pin'] = $add_array['pin'];
        $this->db->update('accounts', $data);
    }

    function getdids_list($flag, $start = 0, $limit = 0)
    {
        $this->db_model->build_search('user_did_search');
        $account_data = $this->session->userdata("accountinfo");
        if ($account_data['reseller_id'] > 0) {
            $where_arr = array(
                "buyer_accountid" => $account_data['id']
            );
            if ($flag) {

                $query = $this->db_model->getJionQuery('dids', 'dids.id as did_id_new,dids.province,dids.city,view_dids_reseller.product_id as productid,view_dids_reseller.*', array(
                    'dids.accountid' => $account_data['id'],
                    'buyer_accountid' => $account_data['id']
                ), 'view_dids_reseller', 'dids.product_id=view_dids_reseller.product_id', 'inner', $limit, $start, 'DESC', 'dids.id');
            } else {

                $query = $this->db_model->getJionQueryCount('dids', 'dids.province,dids.city,view_dids_reseller.product_id as productid,view_dids_reseller.*', array(
                    'dids.accountid' => $account_data['id'],
                    'buyer_accountid' => $account_data['id']
                ), 'view_dids_reseller', 'dids.product_id=view_dids_reseller.product_id', 'inner', '', '', '', '');
            }
        } else {
            $where_arr = array(
                "accountid" => $account_data['id']
            );
            if ($flag) {
                $query = $this->db_model->getJionQuery('dids', 'dids.province,dids.city,dids.id as did_id_new,dids.id,dids.country_id,dids.parent_id,dids.cost,dids.number,products.buy_cost,products.commission,products.setup_fee,products.price,products.billing_type,products.billing_days,products.status,dids.maxchannels,dids.leg_timeout,dids.inc,dids.extensions,dids.call_type,dids.init_inc,dids.last_modified_date,dids.product_id,dids.product_id as productid', array(
                    'dids.accountid' => $account_data['id']
                ), 'products', 'dids.product_id=products.id', 'inner', $limit, $start, 'DESC', 'dids.id');
            } else {
                $query = $this->db_model->getJionQueryCount('dids', 'dids.province,dids.city,dids.id,dids.country_id,dids.parent_id,dids.cost,dids.number,products.buy_cost,products.commission,products.setup_fee,products.price,products.billing_type,products.billing_days,products.status,dids.maxchannels,dids.leg_timeout,dids.inc,dids.extensions,dids.call_type,dids.init_inc,dids.last_modified_date,dids.product_id,dids.product_id as productid', array(
                    'dids.accountid' => $account_data['id']
                ), 'products', 'dids.product_id=products.id', 'inner', '', '', '', '');
            }
        }
        return $query;
    }

    function update_user_did_forward($data)
    {
        unset($data["action"]);
        $this->db->where("id", $data['id']);
        return $this->db->update("dids", $data);
    }

    function reset_empty_payment_transactions($accountid)
    {
        $this->db->where(array(
            "amount" => "0",
            "actual_amount" => "0",
            "user_currency" => "",
            "accountid" => $accountid
        ));
        return $this->db->delete("payment_transaction");
    }

    function create_payment_transaction($data)
    {
        return $this->db->insert("payment_transaction", $data);
    }

    function is_active_account($account_id)
    {
        return (array) $this->db->get_where('accounts', array(
            'id' => $account_id,
            'deleted' => 0,
            'status' => 0
        ))->first_row();
    }

    function get_dashboard_products($account_info, $limit = 10)
    {
        if ($account_info['reseller_id'] > 0) {
            $this->db->where("products.product_category IN (1,2)", null, false);
            return $this->db_model->getJionQuery('products', 'products.id,products.name,products.product_category,products.buy_cost,products.can_purchase,products.can_resell,products.commission,reseller_products.price,reseller_products.setup_fee,products.billing_type,products.billing_days,reseller_products.free_minutes,products.status,products.last_modified_date,reseller_products.product_id,reseller_products.setup_fee,reseller_products.is_optin', array(
                'reseller_products.status' => 0,
                'products.can_purchase' => 0,
                'products.is_deleted' => 0,
                'reseller_products.account_id' => $account_info['reseller_id']
            ), 'reseller_products', 'products.id=reseller_products.product_id', 'inner', $limit, '', 'desc', 'products.id');
        }
        $this->db->where("products.product_category IN (1,2)", null, false);
        return $this->db_model->select('*', 'products', array(
            'status' => 0,
            'can_purchase' => 0,
            'is_deleted' => 0,
            'reseller_id' => 0
        ), 'id', 'desc', $limit, '');
    }

    function get_dashboard_packages($pricelist_id, $limit = 10)
    {
        $this->db->where('pricelist_id', $pricelist_id);
        $this->db->select('*');
        return $this->db->get('packages', $limit);
    }

    function get_dashboard_recent_invoices($accountid, $limit = 10)
    {
        $this->db->where('accountid', $accountid);
        $this->db->where('confirm', 1);
        $this->db->select('*');
        $this->db->order_by('generate_date', 'desc');
        return $this->db->get('invoices', $limit);
    }

    function get_dashboard_recent_subscriptions($accountid, $limit = 10)
    {
        $this->db->where('accountid', $accountid);
        $this->db->select('*');
        $this->db->order_by('assign_date', 'desc');
        $result = $this->db->get('charge_to_account', $limit);
        $charges = array();
        if ($result->num_rows() > 0) {
            $charge_ids = array();
            foreach ($result->result_array() as $row) {
                if (!empty($row['charge_id'])) {
                    $charge_ids[] = $row['charge_id'];
                }
            }
            if (!empty($charge_ids)) {
                $this->db->where_in('id', array_unique($charge_ids));
                $this->db->select('id,description,sweep_id');
                foreach ($this->db->get('charges')->result_array() as $row) {
                    $charges[$row['id']] = $row;
                }
            }
        }
        return array('subscriptions' => $result, 'charges' => $charges);
    }

    function refresh_session_account($account_id)
    {
        return (array) $this->db->get_where('accounts', array(
            'id' => $account_id
        ))->first_row();
    }

    function get_did_info($did_id, $fields = '*')
    {
        $this->db->select($fields);
        return (array) $this->db->get_where('dids', array(
            'id' => $did_id
        ))->first_row();
    }

    function update_user_did($did_id, $update_arr, $did_number = '', $reseller_id = 0)
    {
        $this->db->update('dids', $update_arr, array(
            'id' => $did_id
        ));
        if ($reseller_id > 0 && $did_number !== '') {
            $this->db->update('reseller_pricing', $update_arr, array(
                'note' => $did_number
            ));
        }
        return true;
    }

    function get_refill_coupon_options($reseller_id)
    {
        return $this->db->query("SELECT id,CONCAT(number,'(',amount,')') as details,number FROM refill_coupon WHERE status = '0' and reseller_id=?", array($reseller_id));
    }

    function get_refill_coupon($reseller_id, $number)
    {
        $this->db->where('reseller_id', $reseller_id);
        $this->db->where('number', $number);
        $this->db->select('*');
        return $this->db->get('refill_coupon');
    }

    function mark_refill_coupon_used($number, $account_id, $date)
    {
        $this->db->where('number', $number);
        return $this->db->update('refill_coupon', array(
            'status' => 2,
            'account_id' => $account_id,
            'firstused' => $date
        ));
    }

    function get_currency_info($currency_id)
    {
        return (array) $this->db->get_where('currency', array(
            'id' => $currency_id
        ))->first_row();
    }

    function get_invoice_conf_by_account($accountid)
    {
        return (array) $this->db->get_where('invoice_conf', array(
            'accountid' => $accountid
        ))->first_row();
    }

    function clear_invoice_logo_by_account($accountid)
    {
        $invoiceconf = $this->db_model->getSelect('*', 'invoice_conf', array(
            'accountid' => $accountid
        ));
        $result = $invoiceconf->result_array();
        if (empty($result)) {
            return false;
        }
        $logo = $result[0]['logo'];
        $this->db->where(array('logo' => $logo));
        return $this->db->update('invoice_conf', array('logo' => ''));
    }

    function update_account_password_and_device($accountinfo, $account_id, $password_encode, $new_password)
    {
        $this->db->where('id', $account_id);
        $this->db->update('accounts', array(
            'password' => $password_encode
        ));

        $this->db->where('accountid', $accountinfo['id']);
        $this->db->where('username', $accountinfo['number']);
        $sip_info = (array) $this->db->get_where('sip_devices')->first_row();
        if (!empty($sip_info)) {
            $did_params = (array) json_decode($sip_info['dir_params'], true);
            $sipdevice_array = array(
                'dir_params' => json_encode(array(
                    'password' => $new_password,
                    'vm-enabled' => 'true',
                    'vm-password' => isset($did_params['vm-password']) ? $did_params['vm-password'] : '',
                    'vm-mailto' => isset($did_params['vm-mailto']) ? $did_params['vm-mailto'] : '',
                    'vm-attach-file' => 'true',
                    'vm-keep-local-after-email' => 'true',
                    'vm-email-all-messages' => 'true'
                ))
            );
            $this->db->where('accountid', $accountinfo['id']);
            $this->db->where('username', $accountinfo['number']);
            $this->db->update('sip_devices', $sipdevice_array);
        }
        return true;
    }

    function get_invoice_details_summary($accountid)
    {
        $this->db->where('accountid', $accountid);
        $this->db->select('*');
        return $this->db->get('invoice_details');
    }

    function get_purchase_did_data($country_id, $provience, $city, $account_data)
    {
        $data = array(
            'state_list' => array(),
            'city_list' => array(),
            'did_rows' => array()
        );

        if ($country_id != '') {
            $this->db->where('province NOT LIKE', '');
            $state_list = $this->db_model->getSelect('distinct(province)', 'dids', array(
                'country_id' => $country_id
            ));
            foreach ($state_list->result_array() as $row) {
                foreach ($row as $value) {
                    $data['state_list'][] = $value;
                }
            }

            if ($provience == '') {
                $this->db->where('city NOT LIKE', '');
                $city_list = $this->db_model->getSelect('city', 'dids', array(
                    'country_id' => $country_id
                ));
                foreach ($city_list->result_array() as $row) {
                    foreach ($row as $value) {
                        $data['city_list'][] = $value;
                    }
                }
            }
        }

        if ($provience != '') {
            $this->db->where('city NOT LIKE', '');
            $city_list = $this->db_model->getSelect('distinct(city)', 'dids', array(
                'province' => $provience,
                'country_id' => $country_id
            ));
            $data['city_list'] = array();
            foreach ($city_list->result_array() as $row) {
                foreach ($row as $value) {
                    $data['city_list'][] = $value;
                }
            }
        }

        if ($account_data['reseller_id'] > 0) {
            $this->db->select('dids.id, dids.number, reseller_products.setup_fee, reseller_products.price');
            $this->db->where('dids.accountid', 0);
            $this->db->where('dids.parent_id', $account_data['reseller_id']);
            $this->db->where('dids.country_id', $country_id);
            if ($provience != '') {
                $this->db->where('dids.province', $provience);
            }
            if ($city != '') {
                $this->db->where('dids.city', $city);
            }
            $this->db->where('dids.status', 0);
            $this->db->from('dids');
            $this->db->join('reseller_products', 'dids.product_id = reseller_products.product_id');
            $data['did_rows'] = (array) $this->db->get()->result_array();
        } else {
            $this->db->select('dids.id, dids.number,products.setup_fee,products.price');
            $this->db->where('dids.accountid', '0');
            $this->db->where('dids.parent_id', $account_data['reseller_id']);
            $this->db->where('dids.country_id', $country_id);
            if ($provience != '') {
                $this->db->where('dids.province', $provience);
            }
            if ($city != '') {
                $this->db->where('dids.city', $city);
            }
            $this->db->where('dids.status', 0);
            $this->db->from('dids');
            $this->db->join('products', 'dids.product_id = products.id');
            $data['did_rows'] = (array) $this->db->get()->result_array();
        }

        return $data;
    }

    function get_fund_transfer_context($accountid)
    {
        $account = (array) $this->db->get_where('accounts', array(
            'id' => $accountid
        ))->first_row();
        $currency = array();
        if (!empty($account)) {
            $currency = (array) $this->db->get_where('currency', array(
                'id' => $account['currency_id']
            ))->first_row();
        }
        return array('account' => $account, 'currency' => $currency);
    }

    function find_transfer_target_account($account_number)
    {
        return (array) $this->db->get_where('accounts', array(
            'number' => $account_number,
            'status' => 0,
            'deleted' => 0
        ), 1)->first_row();
    }

    function get_system_config_row($name)
    {
        return (array) $this->db->get_where('system', array(
            'name' => $name
        ), 1)->first_row();
    }

    function get_speed_dial_map($account_id)
    {
        $speeddial_res = $this->db->get_where('speed_dial', array(
            'accountid' => $account_id
        ));
        $speeddial_info = array();
        if ($speeddial_res->num_rows() > 0) {
            foreach ($speeddial_res->result_array() as $value) {
                $speeddial_info[$value['speed_num']] = $value['number'];
            }
        }
        return $speeddial_info;
    }

    function save_speed_dial_number($accountid, $speed_num, $number)
    {
        $this->db->select('count(id) as count');
        $this->db->where(array('accountid' => $accountid));
        $speed_dial_result = (array) $this->db->get('speed_dial')->first_row();
        if ((int) $speed_dial_result['count'] === 0) {
            $data = array();
            for ($i = 0; $i <= 9; $i++) {
                $dest_number = ((string) $speed_num === (string) $i) ? $number : '';
                $data[$i] = array(
                    'number' => $dest_number,
                    'speed_num' => $i,
                    'accountid' => $accountid
                );
            }
            $this->db->insert_batch('speed_dial', $data);
            return 'added';
        }
        $this->db->where('speed_num', $speed_num);
        $this->db->where('accountid', $accountid);
        $this->db->update('speed_dial', array(
            'number' => $number
        ));
        return 'updated';
    }

    function clear_speed_dial_number($accountid, $speed_num)
    {
        $this->db->where('speed_num', $speed_num);
        $this->db->where('accountid', $accountid);
        return $this->db->update('speed_dial', array(
            'number' => ''
        ));
    }

    function get_pin_info($accountid)
    {
        $this->db->where('id', $accountid);
        $this->db->select('*');
        return $this->db->get('accounts');
    }

    function delete_ip_maps_by_ids($ids)
    {
        $ids = preg_replace('/[^0-9,]/', '', (string) $ids);
        if ($ids === '') {
            return false;
        }
        $this->db->where("id IN ($ids)", null, false);
        return $this->db->delete('ip_map');
    }

    function count_ip_map($ip, $prefix)
    {
        $this->db->where('ip', $ip);
        $this->db->where('prefix', $prefix);
        $this->db->select('count(ip) as count');
        return (array) $this->db->get('ip_map')->first_row();
    }

    function delete_ani_maps_by_ids($ids)
    {
        $ids = preg_replace('/[^0-9,]/', '', (string) $ids);
        if ($ids === '') {
            return false;
        }
        $this->db->where("id IN ($ids)", null, false);
        return $this->db->delete('ani_map');
    }

    function get_sip_device_username($id)
    {
        $this->db->select('username');
        return (array) $this->db->get_where('sip_devices', array(
            'id' => $id
        ))->first_row();
    }

    function delete_sip_device($id)
    {
        return $this->db->delete('sip_devices', array(
            'id' => $id
        ));
    }

    function delete_sip_devices_by_ids($ids)
    {
        $ids = preg_replace('/[^0-9,]/', '', (string) $ids);
        if ($ids === '') {
            return false;
        }
        $where = "id IN ($ids)";
        return $this->db->delete('sip_devices', $where);
    }

    function count_animap_number($number)
    {
        $this->db->where('number', $number);
        $this->db->select('count(id) as count');
        $cnt_result = $this->db->get('ani_map')->first_row();
        return isset($cnt_result->count) ? (int) $cnt_result->count : 0;
    }

    function insert_animap($accountinfo, $number)
    {
        return $this->db->insert('ani_map', array(
            'number' => $number,
            'accountid' => $accountinfo['id'],
            'context' => 'default',
            'reseller_id' => $accountinfo['reseller_id'],
            'creation_date' => gmdate('Y-m-d H:i:s'),
            'last_modified_date' => gmdate('Y-m-d H:i:s')
        ));
    }

    function get_charge_map($charge_ids)
    {
        $charges = array();
        if (empty($charge_ids)) {
            return $charges;
        }
        $this->db->where_in('id', array_unique($charge_ids));
        $this->db->select('id,description,sweep_id');
        foreach ($this->db->get('charges')->result_array() as $row) {
            $charges[$row['id']] = $row;
        }
        return $charges;
    }

    function add_ip_map($add_array)
    {
        return $this->db->insert('ip_map', $add_array);
    }

    function delete_ip_map($id)
    {
        return $this->db->delete('ip_map', array(
            'id' => $id
        ));
    }

    function get_account_by_number($number)
    {
        return (array) $this->db->get_where('accounts', array(
            'number' => $number,
            'status' => 0,
            'deleted' => 0
        ), 1)->first_row();
    }

    function count_user_animap($accountid)
    {
        return $this->db_model->countQuery('*', 'ani_map', array(
            'accountid' => $accountid
        ));
    }

    function get_user_animap_list($accountid, $limit, $start)
    {
        return $this->db_model->select('*', 'ani_map', array(
            'accountid' => $accountid
        ), 'id', 'ASC', $limit, $start);
    }


}

?>
