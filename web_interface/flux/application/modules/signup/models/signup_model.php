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
class Signup_model extends CI_Model
{

    function __construct()
    {
        parent::__construct();
        $this->load->model('Flux_common');
        $this->load->helper('form');
        $this->load->model('common_model');
        $this->load->library('session');
    }

    function get_rate()
    {
        $this->load->database();
        $this->db->select("id,name");
        $this->db->from('pricelists');
        $this->db->where("status", "0");
        $query = $this->db->get();
        return $query->row();
    }

    function get_currency_by_code($currency)
    {
        return (array) $this->db->get_where('currency', array('currency' => $currency))->first_row();
    }

    function get_invoice_conf_account_by_domain($domain, $http_host)
    {
        $this->db->select('accountid');
        $this->db->like('domain', $domain);
        $this->db->or_like('domain', $http_host);
        return (array) $this->db->get('invoice_conf')->first_row();
    }

    function get_account_type($account_id)
    {
        $this->db->select('type');
        return (array) $this->db->get_where('accounts', array('id' => $account_id))->first_row();
    }

    function get_active_account_by_id($account_id)
    {
        $this->db->select('*');
        $this->db->where(array(
            'deleted' => '0',
            'status' => '0',
            'id' => $account_id
        ));
        return (array) $this->db->get('accounts')->first_row();
    }

    function count_active_accounts_by_number($number)
    {
        $this->db->select('count(id) as count');
        $this->db->where(array(
            'number' => $number,
            'deleted' => '0'
        ));
        return (array) $this->db->get('accounts')->first_row();
    }

    function count_active_accounts_by_email($email)
    {
        $this->db->select('count(id) as count');
        $this->db->where(array(
            'email' => $email,
            'deleted' => '0'
        ));
        return (array) $this->db->get('accounts')->first_row();
    }

    function get_account_unverified($where)
    {
        return (array) $this->db->get_where('account_unverified', $where)->first_row();
    }

    function update_account_unverified($where, $data)
    {
        $this->db->where($where);
        return $this->db->update('account_unverified', $data);
    }

    function update_account_unverified_by_id($id, $data)
    {
        $this->db->where('id', $id);
        return $this->db->update('account_unverified', $data);
    }

    function insert_account_unverified($data)
    {
        $this->db->insert('account_unverified', $data);
        return $this->db->insert_id();
    }

    function get_account_unverified_brief_by_id($id)
    {
        $this->db->select('number,creation_date,email');
        return (array) $this->db->get_where('account_unverified', array('id' => $id))->first_row();
    }

    function get_signup_inactive_account($account_id)
    {
        return (array) $this->db->get_where('accounts', array(
            'id' => $account_id,
            'deleted' => '1',
            'status' => '1'
        ))->first_row();
    }

    function get_account_by_id($account_id)
    {
        return (array) $this->db->get_where('accounts', array('id' => $account_id))->first_row();
    }

    function get_invoice_conf_by_domain($domain, $http_host)
    {
        $this->db->select('*');
        $this->db->like('domain', $domain);
        $this->db->or_like('domain', $http_host);
        $res = $this->db->get_where('invoice_conf');
        $result = $res->result_array();
        return !empty($result) ? $result[0] : array();
    }

    function get_invoice_conf_with_default($domain, $http_host)
    {
        $this->db->like('domain', $domain);
        $this->db->or_like('domain', $http_host);
        $this->db->or_where('accountid', 1);
        $this->db->order_by('id', 'desc');
        $this->db->limit(1);
        return (array) $this->db->get_where('invoice_conf')->first_row();
    }

    function get_countrycode_array()
    {
        $this->db->select('*');
        $countrycode_info = $this->db->get('countrycode')->result_array();
        $countrycode_array = array();
        foreach ($countrycode_info as $value) {
            $countrycode_array[$value['id']] = $value['countrycode'];
        }
        return $countrycode_array;
    }

    function get_terms_and_conditions()
    {
        $this->db->where('name', 'url');
        $this->db->where('field_type', 'default_system_input');
        $this->db->where('group_title', 'term_and_condition');
        $this->db->or_where('field_type', 'textarea');
        $res = $this->db->get_where('system');
        return $res->result_array();
    }

    function get_default_template($name)
    {
        return (array) $this->db->get_where('default_templates', array('name' => $name))->first_row();
    }

    function insert_mail_detail($data)
    {
        return $this->db->insert('mail_details', $data);
    }

    function insert_otp_number($data)
    {
        return $this->db->insert('otp_number', $data);
    }

    function get_account_by_number_and_email($number, $email)
    {
        $this->db->where('number', $number);
        $this->db->where('email', $email);
        return (array) $this->db->get_where('accounts')->first_row();
    }

    function get_account_unverified_by_number_and_email($number, $email)
    {
        $this->db->where('number', $number);
        $this->db->where('email', $email);
        return (array) $this->db->get_where('account_unverified')->first_row();
    }

    function get_pricelist_by_reseller_id($reseller_id)
    {
        $this->db->select('id');
        return (array) $this->db->get_where('pricelists', array('reseller_id' => $reseller_id))->first_row();
    }

    function get_localization_by_country_id($country_id)
    {
        $this->db->select('id,country_id');
        return (array) $this->db->get_where('localization', array('country_id' => $country_id))->first_row();
    }

    function update_account_password($account_id, $password)
    {
        $this->db->where('id', $account_id);
        return $this->db->update('accounts', array('password' => $password));
    }

    function update_sip_device_password($account_id, $username, $data)
    {
        $this->db->where('accountid', $account_id);
        $this->db->where('username', $username);
        return $this->db->update('sip_devices', $data);
    }

    function get_account_unverified_by_number($number)
    {
        return (array) $this->db->get_where('account_unverified', array('number' => $number))->first_row();
    }

    function update_account_unverified_by_number_and_email($number, $email, $data)
    {
        $this->db->where('number', $number);
        $this->db->where('email', $email);
        return $this->db->update('account_unverified', $data);
    }

    function count_accounts_by_email_or_number($email)
    {
        $this->db->from('accounts');
        $this->db->where('email', $email);
        $this->db->or_where('number', $email);
        return $this->db->count_all_results();
    }

    function get_forgot_password_accounts($email, $number)
    {
        $this->db->where_in('type', array('0', '1', '3'));
        $this->db->where(array('email' => $email));
        $this->db->where('number', $number);
        $this->db->order_by('id', 'DESC');
        $query = $this->db->get('accounts');
        $result = $query->result_array();
        return array(
            'count' => $query->num_rows(),
            'row' => !empty($result) ? $result[0] : array()
        );
    }

    function update_account_pass_link_status($email, $status)
    {
        $this->db->where(array('email' => $email));
        return $this->db->update('accounts', array('pass_link_status' => $status));
    }

    function update_account_unverified_by_number_or_email($value, $data)
    {
        $this->db->where('number', $value);
        $this->db->or_where('email', $value);
        return $this->db->update('account_unverified', $data);
    }

    function add_user($data)
    {
        $data['reseller_id'] = $data['key_unique'];
        unset($data['agreeCheck']);
        unset($data['key_unique']);
        $data['creation'] = gmdate('Y-m-d H:i:s');
        $data['expiry'] = date('Y-m-d H:i:s', strtotime('+10 years'));
        $data['type'] = 0;
        $this->db->insert("accounts", $data);
        $last_id = $this->db->insert_id();
        $tax = common_model::$global_config['system_config']['tax_type'];
        if (!empty($tax)) {
            $query = "select id as taxes_id,taxes_priority from taxes where id IN($tax)";
            $result = $this->db->query($query);
            if ($result->num_rows() > 0) {
                $tax_array = array();
                $taxes_value = $result->result_array();
                $i = 0;
                foreach ($taxes_value as $value) {
                    $tax_array[$i]['id'] = '';
                    $tax_array[$i]['taxes_id'] = $value['taxes_id'];
                    $tax_array[$i]['taxes_priority'] = $value['taxes_priority'];
                    $tax_array[$i]['accountid'] = $last_id;
                    $i++;
                }
                $this->db->insert_batch("taxes_to_accounts", $tax_array);
            }
        }

        return $last_id;
    }
}

function check_user($accno, $email, $balance)
{
    $info = array(
        "number" => $accno,
        "email" => $email,
        "status" => 1
    );
    $this->db->where($info);
    $this->db->select('*');
    $acc_res = $this->db->get('accounts');
    if ($acc_res->num_rows() > 0) {
        $acc_res = $acc_res->result_array();
        $acc_res = $acc_res[0];
        $this->db->where('pricelist_id', $acc_res['pricelist_id']);
        $this->db->select("*");
        $charge_res = $this->db->get('charges');

        if ($charge_res->num_rows() > 0) {
            $charge_res = $charge_res->result_array();
            $charge_res = $charge_res[0];
            $charge_acc_arr = array(
                "charge_id" => $charge_res['id'],
                "accountid" => $acc_res['id'],
                "status" => 0,
                "assign_date" => date('Y-m-d H:i:s')
            );
        } else {
            $charge_res = $charge_res->result_array();
            $charge_acc_arr = array(
                "charge_id" => 'id',
                "accountid" => $acc_res['id'],
                "assign_date" => date('Y-m-d H:i:s')
            );
        }
        $result = $this->db->insert("charge_to_account", $charge_acc_arr);
        $update = array(
            "status" => 0,
            "balance" => $balance
        );
        $this->db->where($info);
        $result = $this->db->update('accounts', $update);
        $sip_device_update = array(
            'accountid' => $acc_res['id']
        );
        $update_sip = array(
            "status" => 0
        );
        $this->db->where($sip_device_update);
        $result = $this->db->update('sip_devices', $update_sip);
        return 1;
    } else {
        return 0;
    }
}

?>
