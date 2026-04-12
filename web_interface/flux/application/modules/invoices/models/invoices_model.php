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
// ##############################################################################
class Invoices_model extends CI_Model
{

    function __construct()
    {
        parent::__construct();
    }

    function get_invoice_list($flag, $start = 100, $limit = 100)
    {
        $account_data = $this->session->userdata("accountinfo");
        $this->db_model->build_search('invoice_list_search');
        $where = '';
        if ($account_data['type'] == 1) {
            $where = array(
                "reseller_id" => $account_data['id'],
                "is_deleted" => '0'
            );
        }else{
            $where = array(
                "is_deleted" => '0'
            );
        }
        if ($flag) {
            $query = $this->db_model->select("*", "view_invoices", $where, "due_date", "DESC", $limit, $start);
        } else {
            $query = $this->db_model->countQuery("*", "view_invoices", $where);
        }
        return $query;
    }

    function getCdrs_invoice($invoiceid)
    {
        $this->db->where('invoiceid', $invoiceid);
        $this->db->from('cdrs');
        $query = $this->db->get();
        return $query;
    }

    function get_account_including_closed($accountdata)
    {
        $q = "SELECT * FROM accounts WHERE number = '" . $this->db->escape_str($accountdata) . "'";
        $query = $this->db->query($q);
        if ($query->num_rows() > 0) {
            $row = $query->row_array();
            return $row;
        }
        $q = "SELECT * FROM accounts WHERE accountid = '" . $this->db->escape_str($accountdata) . "'";
        $query = $this->db->query($q);
        if ($query->num_rows() > 0) {
            $row = $query->row_array();
            return $row;
        }

        return NULL;
    }

    function get_user_invoice_list($flag, $start = 0, $limit = 0)
    {
        $this->db_model->build_search('invoice_list_search');
        $accountinfo = $this->session->userdata('accountinfo');
        $where = array(
            "accountid" => $accountinfo['id'],
            'confirm' => 1,
        );
        if ($flag) {
            $query = $this->db_model->select("*", "invoices", $where, "", "", $limit, $start);
        } else {
            $query = $this->db_model->countQuery("*", "invoices", $where);
        }

        return $query;
    }

    function getinvoiceconf_list($flag, $start = 0, $limit = 0)
    {
        $where = array();
        $logintype = $this->session->userdata('logintype');

        if ($logintype == 1 || $logintype == 5) {

            $where = array(
                "accountid" => $this->session->userdata["accountinfo"]['id']
            );
        }
        $this->db_model->build_search('invoice_conf_search');
        if ($flag) {
            $query = $this->db_model->select("*", "invoice_conf", $where, "id", "ASC", $limit, $start);
        } else {
            $query = $this->db_model->countQuery("*", "invoice_conf", $where);
        }
        return $query;
    }

    function get_invoiceconf($edit_id)
    {
        $return_array = array();
        $logintype = $this->session->userdata('logintype');
        if ($logintype == 1 || $logintype == 5) {

            $where = array(
                "accountid" => $this->session->userdata["accountinfo"]['id']
            );
        } else {
            if ($logintype == - 1 || $logintype == 2) {
                $accountid = '1';
            }
            $where = array(
                'id' => $edit_id
            );
        }
        $query = $this->db_model->getSelect("*", "invoice_conf", $where);
        foreach ($query->result_array() as $key => $value) {
            $return_array = $value;
        }
        return $return_array;
    }

    function check_invoiceconf_exist($accountid)
    {
        $count = $this->db_model->countQuery("*", "invoice_conf", array(
            "accountid" => $accountid
        ));
        return $count;
    }

    function save_invoiceconf($post_array)
    {
        $logintype = $this->session->userdata('logintype');
        $where_arr = array(
            'id' => $post_array['id']
        );
        unset($post_array['action']);
        if ($post_array['id'] != "") {
            $this->db->where($where_arr);
            unset($post_array['logo_main']);
            if ($logintype == 1) {
                unset($post_array['accountid']);
            }
            $this->db->update('invoice_conf', $post_array);
        } else {
            unset($post_array['logo_main']);

            $accountdata = $this->session->userdata('accountinfo');

            $post_array['accountid'] = $post_array['reseller_id'];
            $this->db->select('id');
            $this->db->where_in('accountid', $post_array['accountid']);
            $get_id = $this->db->get('invoice_conf')->row_array();
            unset($post_array['reseller_id']);
            $q = "SELECT reseller_id FROM accounts WHERE id = '" . $post_array['accountid'] . "'";
            $query = (array) $this->db->query($q)->first_row();
            $post_array['reseller_id'] = $query['reseller_id'] ? $query['reseller_id'] : 0;
            if (isset($get_id['id']) && $get_id['id'] != '') {
                unset($post_array['id']);
                $where_arr = array(
                    'id' => $get_id['id']
                );
                $this->db->where($where_arr);
                $this->db->update('invoice_conf', $post_array);
                return true;
            } else {

                $this->db->insert('invoice_conf', $post_array);
                return true;
            }
        }
    }

    function create_receipt_invoice($invoice_data)
    {
        $this->db->insert("invoices", $invoice_data);
        return $this->db->insert_id();
    }

    function get_invoice_payment_summary($invoice_id)
    {
        $invoice_info = $this->db->query("SELECT * from view_invoices where id='" . $this->db->escape_str($invoice_id) . "' ORDER BY generate_date ASC");
        $debit = 0;
        if ($invoice_info->num_rows() > 0) {
            $invoice_info = $invoice_info->result_array();
            $debit = $invoice_info[0]['debit'];
        }

        $credit_total = 0;
        $invoice_total_query = $this->db->query("select sum(credit) as credit from invoice_details where invoiceid = " . (int)$invoice_id . " Group By invoiceid");
        if ($invoice_total_query->num_rows() > 0) {
            $invoice_total_query = $invoice_total_query->result_array();
            $credit_total = $invoice_total_query[0]['credit'];
        }

        return array('debit' => $debit, 'credit_total' => $credit_total);
    }

    function update_invoice_admin_payment_status($invoice_id, $notes, $status)
    {
        $this->db->where('id', $invoice_id);
        $this->db->update("invoices", array("notes" => $notes, 'status' => $status));
        return true;
    }

    function create_payment_transaction($data)
    {
        $this->db->insert("payment_transaction", $data);
        return $this->db->insert_id();
    }

    function create_invoice_detail($data)
    {
        return $this->db->insert("invoice_details", $data);
    }

    function delete_invoice_conf($id)
    {
        $this->db->where('id', $id);
        return $this->db->delete("invoice_conf");
    }

    function get_invoice_conf_media($id)
    {
        $logo_data = $this->db_model->getSelect("logo,favicon", "invoice_conf", array("id" => $id));
        return (array) $logo_data->first_row();
    }

    function clear_invoice_conf_media($id, $field)
    {
        $allowed = array('logo', 'favicon');
        if (!in_array($field, $allowed)) {
            return false;
        }
        $this->db->where('id', $id);
        return $this->db->update("invoice_conf", array($field => ''));
    }

    function get_view_invoice_aggregates($accountid)
    {
        $credit_query = $this->db->query("select sum(credit) as total from view_invoices where confirm=1 and accountid=".(int)$accountid);
        $debit_query = $this->db->query("select sum(debit) as total from view_invoices where accountid=".(int)$accountid);
        $credit_total = ($credit_query->num_rows() > 0) ? (float)$credit_query->result_array()[0]['total'] : 0;
        $debit_total = ($debit_query->num_rows() > 0) ? (float)$debit_query->result_array()[0]['total'] : 0;
        return array('credit_total' => $credit_total, 'debit_total' => $debit_total);
    }

    function get_customer_invoice_aggregates($accountid)
    {
        $invoice_total_query = $this->db->query("select sum(amount) as total from invoices where confirm=1 and accountid=".(int)$accountid);
        $credit_total_query = $this->db->query("select sum(credit) as total from invoice_details where accountid=".(int)$accountid);
        $invoice_total = ($invoice_total_query->num_rows() > 0) ? (float)$invoice_total_query->result_array()[0]['total'] : 0;
        $credit_total = ($credit_total_query->num_rows() > 0) ? (float)$credit_total_query->result_array()[0]['total'] : 0;
        return array('invoice_total' => $invoice_total, 'credit_total' => $credit_total);
    }



function get_invoice_download_context($invoiceid, $http_host, $is_https = false)
{
    $context = array(
        'invoicedata' => null,
        'posttoexternal' => 0,
        'total_calls_amount' => 0,
        'product_services' => 0,
        'accountsdata' => null,
        'country_name' => '',
        'company_data' => null,
        'debit_data' => 0,
        'invoice_details_data' => array(),
        'invoicetax_details_data' => array()
    );

    $context['invoicedata'] = $this->db->get_where('view_invoices', array('id' => $invoiceid))->first_row();
    if (empty($context['invoicedata'])) {
        return $context;
    }

    $context['posttoexternal'] = (int) $this->common->get_field_name("posttoexternal","accounts",array("id"=>$context['invoicedata']->accountid));
    $row = (array) $this->db->query("select sum(debit) as debit from invoice_details where charge_type NOT IN('STANDARD') AND order_item_id = 0 AND invoiceid = ?", array($invoiceid))->first_row();
    $context['total_calls_amount'] = !empty($row) ? (float) $row['debit'] : 0;
    $row = (array) $this->db->query("select sum(debit) as total from invoice_details where order_item_id > 0 AND is_tax = 0 AND product_category != 3 AND invoiceid = ?", array($invoiceid))->first_row();
    $context['product_services'] = !empty($row['total']) ? $row['total'] : 0;
    $context['accountsdata'] = $this->db->get_where('accounts', array('id' => $context['invoicedata']->accountid))->first_row();

    if (!empty($context['accountsdata']) && $context['accountsdata']->country_id != '0') {
        $country = $this->db->select('country')->from('countrycode')->where('id', $context['accountsdata']->country_id)->get()->first_row();
        $context['country_name'] = !empty($country) ? $country->country : '';
    }

    $domain = ($is_https ? "https://" : "http://") . $http_host . "/";
    $invoice_details = (array) $this->db->query("SELECT accountid FROM invoice_conf WHERE domain LIKE ? OR domain LIKE ? LIMIT 1", array('%'.$domain.'%', '%'.$http_host.'%'))->first_row();
    $accountid_invoice = ((!empty($invoice_details)) && ($invoice_details['accountid'] != '')) ? $invoice_details['accountid'] : 1;
    $context['company_data'] = $this->db->get_where('invoice_conf', array('accountid' => $accountid_invoice))->first_row();
    $context['debit_data'] = $this->common->get_field_name("debit","view_invoices",array("id"=>$invoiceid));

    if ($context['posttoexternal'] == 1) {
        $context['invoice_details_data'] = $this->db->query("SELECT * FROM invoice_details WHERE invoiceid = ? AND is_tax = 0 AND charge_type <> 'INVPAY' AND charge_type <> 'REFILL'", array($invoiceid))->result_array();
    } else {
        $context['invoice_details_data'] = $this->db->query("SELECT * FROM invoice_details WHERE invoiceid = ? AND is_tax = 0 AND charge_type <> 'INVPAY'", array($invoiceid))->result_array();
    }
    $context['invoicetax_details_data'] = $this->db->get_where('invoice_details', array('invoiceid' => $invoiceid, 'is_tax' => '1'))->result_array();
    return $context;
}

function save_manual_invoice_edit($response_arr, $confirm)
{
    $this->load->model('common_model');
    $where = array('invoiceid' => $response_arr['invoiceid'], 'generate_type' => 1);
    $this->db->where($where);
    $this->db->delete("invoice_details");
    if ($response_arr['taxes_count'] > 0) {
        for ($a = 0; $a < $response_arr['taxes_count']; $a ++) {
            $add_arr = array(
                'accountid' => $response_arr['accountid'],
                'reseller_id' => $response_arr['reseller_id'],
                'invoiceid' => $response_arr['invoiceid'],
                'order_item_id' => 0,
                'generate_type' => 1,
                'is_tax' => 1,
                'description' => $response_arr['description_total_tax_input_' . $a],
                'debit' => $this->common_model->add_calculate_currency($response_arr['abc_total_tax_input_' . $a], "", "", true, false),
                'created_date' => gmdate("Y-m-d H:i:s")
            );
            $this->db->insert("invoice_details", $add_arr);
        }
    }
    for ($i = 1; $i <= $response_arr['row_count']; $i ++) {
        if ($response_arr['invoice_amount_' . $i] != '') {
            $add_arr = array(
                'accountid' => $response_arr['accountid'],
                'reseller_id' => $response_arr['reseller_id'],
                'invoiceid' => $response_arr['invoiceid'],
                'order_item_id' => 0,
                'generate_type' => 1,
                'description' => $response_arr['invoice_description_' . $i],
                'debit' => $this->common_model->add_calculate_currency($response_arr['invoice_amount_' . $i], "", "", true, false),
                'created_date' => $response_arr['invoice_from_date_' . $i]
            );
            $this->db->insert("invoice_details", $add_arr);
        }
    }
    $this->db->where("id", $response_arr['invoiceid']);
    $this->db->update("invoices", array('confirm' => $confirm, 'notes' => $response_arr['invoice_notes']));
    $mail_context = array();
    if ($confirm == 1) {
        $account_data = $this->db_model->getSelect("*", "accounts", array("id" => $response_arr["accountid"]))->result_array();
        $account_balance = $this->common->get_field_name('balance', 'accounts', $response_arr['accountid']);
        $account_balance = ($account_data[0]['posttoexternal'] == 1) ? ($account_data[0]['credit_limit'] - $account_balance) : $account_balance;
        $invoice_details = $this->db_model->getSelect("*", "invoice_details", array("invoiceid" => $response_arr["invoiceid"]));
        if ($invoice_details->num_rows() > 0) {
            $after_bal = 0;
            foreach ($invoice_details->result_array() as $details_value) {
                if ($details_value['debit'] > 0) {
                    $before_balance_add = $account_balance - $after_bal;
                    $after_balance_add = $before_balance_add - $details_value['debit'];
                    $after_bal += $details_value['debit'];
                } else {
                    $before_balance_add = $account_balance - $after_bal;
                    $after_balance_add = $before_balance_add + $details_value['credit'];
                    $after_bal += $details_value['credit'];
                }
                $this->db->where("id", $details_value['id']);
                $this->db->update("invoice_details", array('before_balance' => $before_balance_add, 'after_balance' => $after_balance_add));
            }
        }
        $amount = $this->common_model->add_calculate_currency($response_arr['total_val_final'], "", "", true, false);
        $this->db->query("update accounts set balance = IF(posttoexternal=1,balance+?,balance-?) where id = ?", array($amount, $amount, $response_arr['accountid']));
        $invoice = (array) $this->db->get_where('invoices', array('id' => $response_arr["invoiceid"]))->first_row();
        $mail_context = array(
            'account_data' => $account_data[0],
            'invoice' => $invoice
        );
    }
    return $mail_context;
}

function save_auto_invoice_edit($response_arr, $confirm)
{
    $this->load->model('common_model');
    $where = array('invoiceid' => $response_arr['invoiceid'], 'generate_type' => 1);
    $this->db->where($where);
    $this->db->delete("invoice_details");
    foreach ($response_arr['auto_invoice_date'] as $key => $val) {
        $data = array(
            'debit' => $this->common_model->add_calculate_currency($response_arr['auto_invoice_amount'][$key], "", "", true, false),
            'created_date' => $response_arr['auto_invoice_date'][$key],
            'description' => $response_arr['auto_invoice_description'][$key],
            'generate_type' => 0
        );
        $this->db->where("id", $key);
        $this->db->update("invoice_details", $data);
    }
    if ($response_arr['taxes_count'] > 0) {
        for ($a = 0; $a < $response_arr['taxes_count']; $a ++) {
            $update_arr = array('debit' => $this->common_model->add_calculate_currency($response_arr['total_tax_id_' . $a], "", "", true, false));
            $this->db->where(array('id' => $response_arr['description_total_tax_input_' . $a]));
            $this->db->update("invoice_details", $update_arr);
        }
    }
    for ($i = 1; $i <= $response_arr['row_count']; $i ++) {
        if ($response_arr['invoice_amount_' . $i] != '') {
            $add_arr = array(
                'accountid' => $response_arr['accountid'],
                'reseller_id' => $response_arr['reseller_id'],
                'invoiceid' => $response_arr['invoiceid'],
                'order_item_id' => 0,
                'generate_type' => 1,
                'description' => $response_arr['invoice_description_' . $i],
                'debit' => $this->common_model->add_calculate_currency($response_arr['invoice_amount_' . $i], "", "", true, false),
                'created_date' => $response_arr['invoice_from_date_' . $i]
            );
            $this->db->insert("invoice_details", $add_arr);
        }
    }
    $this->db->where("id", $response_arr['invoiceid']);
    $this->db->update("invoices", array('confirm' => $confirm, 'notes' => $response_arr['invoice_notes']));
    if ($confirm == 1) {
        $account_balance = $this->common->get_field_name('balance', 'accounts', $response_arr['accountid']);
        $invoice_details = $this->db_model->getSelect("*", "invoice_details", array("invoiceid" => $response_arr["invoiceid"]));
        if ($invoice_details->num_rows() > 0) {
            $after_bal = 0;
            foreach ($invoice_details->result_array() as $details_value) {
                if ($details_value['charge_type'] != 'STANDARD') {
                    $before_balance_add = $account_balance - $after_bal;
                    $after_balance_add = $before_balance_add - $details_value['debit'];
                    $after_bal += $details_value['debit'];
                    $this->db->where("id", $details_value['id']);
                    $this->db->update("invoice_details", array('before_balance' => $before_balance_add, 'after_balance' => $after_balance_add));
                }
            }
        }
        $account_data = $this->db_model->getSelect("*", "accounts", array("id" => $response_arr["accountid"]))->result_array();
        $invoice_not_deduct = $this->db_model->getSelect("*", "invoice_details", array("invoiceid" => $response_arr['invoiceid']))->result_array();
        $standard_call_balance = 0;
        foreach ($invoice_not_deduct as $invoice_nodeduct_val) {
            if ($invoice_nodeduct_val['charge_type'] == 'STANDARD') {
                $standard_call_balance = $invoice_nodeduct_val['debit'];
            }
        }
        if ($account_data[0]['posttoexternal'] == 1) {
            $finaldeduct_bal = $response_arr['total_val_final'] - $standard_call_balance;
            $bal_data = $account_data[0]['balance'] - $finaldeduct_bal;
        } else {
            $bal_data = 0;
        }
        $this->db->where("id", $response_arr['accountid']);
        $this->db->update("accounts", array('balance' => $bal_data));
    }
    return true;
}

function clear_invoice_logo($accountid)
{
    $invoiceconf = $this->db_model->getSelect("*", "invoice_conf", array("id" => $accountid));
    $result = $invoiceconf->result_array();
    $logo = $result[0]['logo'];
    $this->db->where(array('logo' => $logo));
    return $this->db->update('invoice_conf', array('logo' => ''));
}

function create_invoice_screen_entry($invoice_data, $insert_arr)
{
    $this->db->insert("invoices", $invoice_data);
    $invoiceid = $this->db->insert_id();
    $insert_arr['invoiceid'] = $invoiceid;
    $this->db->insert("invoice_details", $insert_arr);
    return $invoiceid;
}

function insert_invoice_total_row($invoice_total_arr)
{
    return $this->db->insert("invoices_total", $invoice_total_arr);
}

function get_tax_info($tax_id)
{
    return $this->db->get_where('taxes', array('id' => $tax_id));
}

function soft_delete_invoice($inv_id)
{
    $this->db->where('id', $inv_id);
    return $this->db->update("invoices", array('is_deleted' => 1));
}

function get_reseller_customer_accounts($reseller_id)
{
    return $this->db->query("SELECT * FROM accounts WHERE reseller_id = ? AND status = 0 AND type IN (0,1)", array((int) $reseller_id));
}
}
