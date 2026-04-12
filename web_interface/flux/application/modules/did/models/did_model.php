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
class DID_model extends CI_Model
{

    function __construct()
    {
        parent::__construct();
        $this->load->library("flux_log");
    }

    function getdid_list($flag, $start = 0, $limit = 0)
    {
        $this->db_model->build_search('did_list_search');
        $accountinfo = $this->session->userdata('accountinfo');
        $where = array();
        if ($this->session->userdata('logintype') == 1 || $this->session->userdata('logintype') == 5) {
            $where['account_id'] = $accountinfo['id'];
            if ($flag) {
                if ($accountinfo['reseller_id'] > 0) {
                    // HP: PBX_ADDON
                    $query = $this->db_model->select("*,id as did_id_new,product_id as id", "view_dids_reseller", $where, "number", "desc", $limit, $start);
                }
                else {
                    $query = $this->db_model->getJionQuery('dids', 'dids.province,dids.city,dids.product_id as id,dids.number,dids.status,dids.accountid,dids.country_id,dids.last_modified_date,dids.cost,dids.call_type,dids.leg_timeout,dids.maxchannels,dids.extensions,dids.init_inc,dids.inc, dids.setup,dids.monthlycost,dids.reverse_rate,view_dids.buy_cost,view_dids.setup_fee,view_dids.price,view_dids.billing_type,view_dids.billing_days,
                    ,view_dids.product_id,view_dids.account_id', array(
                        'dids.status' => 0,
                        'dids.parent_id' => $accountinfo['id']
                    ), 'view_dids', 'dids.product_id=view_dids.product_id', 'inner', $limit, $start, 'DESC', 'dids.id');
                }
            } 
            else {
                if ($accountinfo['reseller_id'] > 0) {
                    $query = $this->db_model->countQuery("*,product_id as id", "view_dids_reseller", $where);
                } else {
                    $query = $this->db_model->getJionQueryCount('dids', 'dids.province,dids.city,dids.product_id as id,dids.number,dids.status,dids.accountid,dids.country_id,dids.last_modified_date,dids.cost,dids.call_type,dids.leg_timeout,dids.maxchannels,dids.extensions,dids.init_inc,dids.inc, dids.setup,dids.monthlycost,dids.reverse_rate,view_dids.buy_cost,view_dids.setup_fee,view_dids.price,view_dids.billing_type,view_dids.billing_days,
                    ,view_dids.product_id,view_dids.account_id', array(
                        'dids.status' => 0,
                        'dids.parent_id' => $accountinfo['id']
                    ), 'view_dids', 'dids.product_id=view_dids.product_id', 'inner', $limit, $start, 'DESC', 'dids.id');
                }
            }
        } 
        else {
            if ($flag) {
                $this->db->order_by("did_id", "desc");
                // HP: PBX_ADDON
                $query = $this->db_model->select("id as did_id_new,product_id as id,product_id as productid,id as did_id,number,status,country_id,accountid,parent_id,cost,setup,monthlycost,maxchannels,leg_timeout,init_inc,inc,call_type,extensions,last_modified_date,province,city,reverse_rate", "dids", $where, "", "", $limit, $start);
            } else {
                $query = $this->db_model->countQuery("*", "dids", $where);
            }
        }
        return $query;
    }

    function getavailable_did_list($flag, $start = 0, $limit = 0)
    {
        $accountinfo = $this->session->userdata('accountinfo');
        $this->db_model->build_search('did_available_list_search');
        $where['parent_id'] = $accountinfo['reseller_id'];
        $where['accountid'] = 0;
        $where['status'] = 0;
        if ($accountinfo['reseller_id'] > 0) {
            if ($flag) {
                $query = $this->db_model->getJionQuery('dids', 'dids.product_id as id,dids.number,dids.accountid,dids.country_id,dids.cost,dids.call_type,dids.leg_timeout,dids.maxchannels,dids.extensions,reseller_products.buy_cost,reseller_products.setup_fee,
reseller_products.price,reseller_products.billing_type,reseller_products.billing_days,dids.province,dids.city
,reseller_products.product_id', array(
                    'reseller_products.account_id' => $accountinfo['reseller_id'],
                    'dids.status' => 0,
                    'dids.parent_id' => $accountinfo['reseller_id'],
                    'dids.accountid' => 0
                ), 'reseller_products', 'dids.product_id=reseller_products.product_id', 'inner', $limit, $start, 'DESC', 'dids.id');
            } else {
                $query = $this->db_model->getJionQueryCount('dids', 'dids.product_id as id,dids.number,dids.accountid,dids.country_id,dids.cost,dids.call_type,dids.leg_timeout,dids.maxchannels,dids.extensions,reseller_products.buy_cost,reseller_products.setup_fee,
reseller_products.price,reseller_products.billing_type,reseller_products.billing_days,dids.province,dids.city
,reseller_products.product_id', array(
                    'reseller_products.account_id' => $accountinfo['reseller_id'],
                    'dids.status' => 0,
                    'dids.parent_id' => $accountinfo['reseller_id'],
                    'dids.accountid' => 0
                ), 'reseller_products', 'dids.product_id=reseller_products.product_id', 'inner', $limit, $start, 'DESC', 'dids.id');
            }
            return $query;
        } else {
            if ($flag) {
                $query = $this->db_model->select("*,product_id as productid", "dids", $where, "id", "desc", $limit, $start);
            } else {

                $query = $this->db_model->countQuery("*", "dids", $where);
            }
            return $query;
        }
    }

    function did_number_release($did_info, $accountinfo, $action)
    {
        if ($this->session->userdata['userlevel_logintype'] == '-1' || $this->session->userdata['userlevel_logintype'] == '2' ) {
            $did_update_array = array(
                "accountid" => 0,
                "parent_id" => 0,
                "call_type" => 0,
                "extensions" => "",
                "always" => 0,
                "always_destination" => "",
                "user_busy" => 0,
                "user_busy_destination" => "",
                "user_not_registered" => 0,
                "user_not_registered_destination" => "",
                "no_answer" => 0,
                "no_answer_destination" => "",
                "call_type_vm_flag" => 1,
                "failover_call_type" => 1,
                "always_vm_flag" => 1,
                "user_busy_vm_flag" => 1,
                "user_not_registered_vm_flag" => 1,
                "no_answer_vm_flag" => 1,
                "failover_extensions" => ""
            );
            $order_where = array(
                "is_terminated" => 0,
                "product_id" => $did_info['product_id']
            );
            
            if ($did_info['accountid'] > 0) {
                unset($did_update_array['parent_id']);
                $order_where['accountid']  =  $did_info['accountid'];       
            }
        } else {
            if ($action == 'release') {
                $did_update_array = array(
                    "accountid" => 0,
                    "call_type" => 0,
                    "extensions" => "",
                    "always" => 0,
                    "always_destination" => "",
                    "user_busy" => 0,
                    "user_busy_destination" => "",
                    "user_not_registered" => 0,
                    "user_not_registered_destination" => "",
                    "no_answer" => 0,
                    "no_answer_destination" => "",
                    "call_type_vm_flag" => 1,
                    "failover_call_type" => 1,
                    "always_vm_flag" => 1,
                    "user_busy_vm_flag" => 1,
                    "user_not_registered_vm_flag" => 1,
                    "no_answer_vm_flag" => 1,
                    "failover_extensions" => ""
                );
            } else {
                $did_update_array = array(
                    "accountid" => 0,
                    "parent_id" => 0,
                    "call_type" => 0,
                    "extensions" => "",
                    "always" => 0,
                    "always_destination" => "",
                    "user_busy" => 0,
                    "user_busy_destination" => "",
                    "user_not_registered" => 0,
                    "user_not_registered_destination" => "",
                    "no_answer" => 0,
                    "no_answer_destination" => "",
                    "call_type_vm_flag" => 1,
                    "failover_call_type" => 1,
                    "always_vm_flag" => 1,
                    "user_busy_vm_flag" => 1,
                    "user_not_registered_vm_flag" => 1,
                    "no_answer_vm_flag" => 1,
                    "failover_extensions" => ""
                );
            }
            $order_where = array(
                "is_terminated" => 0,
                "product_id" => $did_info['product_id'],
                "accountid" => $did_info['accountid']
            );
        }
        if($did_info['parent_id'] > 0 && $did_info['accountid'] == 0){
            
            $this->db->delete("reseller_products",array("product_id" => $did_info['product_id']));
        }
        $this->db->where(array(
            "product_id" => $did_info['product_id']
        ));
        $this->db->update("dids", $did_update_array);

        $order_update_array = array(
            "is_terminated" => 1,
            "termination_date" => gmdate('Y-m-d H:i:s'),
            "termination_note" => "DID(" . $did_info['number'] . ") has been released by " . $accountinfo['number'] . "( " . $accountinfo['first_name'] . " " . $accountinfo['last_name'] . ") "
        );
        $this->db->where($order_where);
        $this->db->update("order_items", $order_update_array);
        return true;
    }

    function did_forward($id)
    {
        $where = array(
            'id' => $id
        );
        $query = $this->db_model->getSelect("*", "dids", $where);
        $result = $query->result_array();
        return $result;
    }

    function update_did_forward($data)
    {

	if (isset($data['call_type_vm_flag'])) {
            $data['call_type_vm_flag'] = 0;
        } else {
            $data['call_type_vm_flag'] = 1;
        }
      
        unset($data["action"]);
        $this->db->where("id", $data['id']);
        $this->db->update("dids", $data);
//echo $this->db->last_query(); exit;
    }

    function bulk_insert_dids($field_value)
    {
        $accountinfo = $this->session->userdata('accountinfo');
        foreach ($field_value as $key => $value) {
            $product_insert_array = array(
                "name" => $value['number'],
                "description" => isset($value['product_description']) ? $value['product_description'] : "",
                "product_category" => "4",
                "buy_cost" => isset($value['product_buy_cost']) ? $this->common_model->add_calculate_currency($value['product_buy_cost'], "", '', false, false) : 0.00,
                "price" => isset($value['monthlycost']) ? $this->common_model->add_calculate_currency($value['monthlycost'], "", '', false, false) : "0.00",
                "setup_fee" => isset($value['setup']) ? $this->common_model->add_calculate_currency($value['setup'], "", '', false, false) : "0.00",
                "can_purchase" => isset($value['can_purchase']) ? $value['can_purchase'] : "0",
                "can_resell" => isset($value['can_resell']) ? $value['can_resell'] : "0",
                "commission" => isset($value['commission']) ? $this->common_model->add_calculate_currency($value['commission'], '', '', false, false) : "0.00",
                "billing_type" => isset($value['billing_type']) ? $value['billing_type'] : "1",
                "billing_days" => isset($add_array['billing_days']) ? $add_array['billing_days'] : "0",
                "free_minutes" => isset($add_array['free_minutes']) ? $add_array['free_minutes'] : "0",
                "apply_on_rategroups" => isset($value['product_rate_group']) ? implode(",", $value['product_rate_group']) : "",
                "destination_rategroups" => isset($value['destination_rategroups']) ? implode(",", $value['destination_rategroups']) : "",
                "destination_countries" => isset($value['destination_countries']) ? implode(",", $value['destination_countries']) : "",
                "destination_calltypes" => isset($value['destination_calltypes']) ? implode(",", $value['destination_calltypes']) : "",
                "apply_on_existing_account" => isset($value['apply_on_existing_account']) ? $value['apply_on_existing_account'] : 1,
                "applicable_for" => isset($value['applicable_for']) ? $value['applicable_for'] : 0,
                "release_no_balance" => isset($value['release_no_balance']) ? $value['release_no_balance'] : "0",
                "created_by" => "1",
                "reseller_id" => isset($value['reseller_id']) ? $value['reseller_id'] : 0,
                "creation_date" => gmdate("Y-m-d H:i:s"),
                "last_modified_date" => ''
            );
            $this->db->insert("products", $product_insert_array);
            $last_id = $this->db->insert_id();
            $did_insert_array = array(
                "number" => $value['number'],
                "country_id" => $value['country_id'],
                "provider_id" => $value['provider_id'],
                'city' => $value['city'],
                'province' => $value['province'],
                "accountid" => $value['accountid'],
                "cost" => $value['cost'],
                "init_inc" => $value['init_inc'],
                "inc" => $value['inc'],
                "setup" => $value['setup'],
                "monthlycost" => $value['monthlycost'],
                "call_type" => $value['call_type'],
                "extensions" => $value['extensions'],
                "includedseconds" => $value['includedseconds'],
                "connectcost" => $value['connectcost'],
                "maxchannels" => isset($value['maxchannels']) ? $value['maxchannels'] : "0",
                "leg_timeout" => isset($value['leg_timeout']) ? $value['leg_timeout'] : "0",
                "reverse_rate" => isset($value['reverse_rate']) ? $value['reverse_rate'] : "1",
                "rate_group" => isset($value['rate_group']) ? $value['rate_group'] : "1",
                "area_code" => isset($value['area_code']) ? $value['area_code'] : "51",
                "product_id" => $last_id
            );
            $this->db->insert("dids", $did_insert_array);
            $affected_row = $this->db->insert_id();

            if ($value['accountid'] > 0) {
                $add_array['is_parent_billing'] = 'true';
                $add_array['product_id'] = $last_id;
                $add_array['payment_by'] = "Account Balance";
                $add_array['charge_type'] = "DID";
                $add_array['create_invoice'] = "false";
                $order_id = $this->order->confirm_order($add_array, $value['accountid'], $accountinfo);
            }
        }
    }
    
    function get_account($accountdata) {
    $q = "SELECT * FROM accounts WHERE number = '" . $this->db->escape_str ( $accountdata ) . "' AND status = 0";
    $query = $this->db->query ( $q );
    if ($query->num_rows () > 0) {
    $row = $query->row_array ();
    return $row;
    }
    
    $q = "SELECT * FROM accounts WHERE cc = '" . $this->db->escape_str ( $accountdata ) . "' AND status = 0";
    $query = $this->db->query ( $q );
    if ($query->num_rows () > 0) {
    $row = $query->row_array ();
    return $row;
    }
    
    $q = "SELECT * FROM accounts WHERE accountid = '" . $this->db->escape_str ( $accountdata ) . "' AND status = 0";
    $query = $this->db->query ( $q );
    if ($query->num_rows () > 0) {
    $row = $query->row_array ();
    return $row;
    }
    
    return NULL;
    }

    function get_did_by_number($number) {
    $this->db->where ( "id", $number );
    $this->db->or_where ( "number", $number );
    $query = $this->db->get ( "dids" );
    if ($query->num_rows () > 0)
    return $query->row_array ();
    else
    return false;
    }
    
    function update_dids_reseller($post) {
    $where = array (
    'id' => $post ['did_id']
    );
    $update_array = array (
    'dial_as' => $post ['dial_as'],
    'extensions' => $post ['extension']
    );
    $this->db->where ( $where );
    $this->db->update ( 'dids', $update_array );
    }

    function delete_routes($id, $number, $pricelist_id) {
    $number = "^" . $number . ".";
    $where = array (
    'pricelist_id' => $pricelist_id,
    'pattern' => $number
    );
    $this->db->where ( $where );
    $this->db->delete ( 'routes' );
    }

    function insert_routes($post, $pricelist_id) {
    $commment = "DID:" . $post ['country'] . "," . $post ['province'] . "," . $post ['city'];
    $insert_array = array (
    'pattern' => "^" . $post ['number'] . ".",
    'comment' => $commment,
    'pricelist_id' => $pricelist_id,
    'connectcost' => $post ['connectcost'],
    'includedseconds' => $post ['included'],
    'cost' => $post ['cost'],
    'inc' => $post ['inc']
    );
    $this->db->insert ( 'routes', $insert_array );
    return true;
    }

    function add_invoice_data($accountid, $charge_type, $description, $credit) {
    $insert_array = array (
    'accountid' => $accountid,
    'charge_type' => $charge_type,
    'description' => $description,
    'credit' => $credit,
    'charge_id' => '0',
    'package_id' => '0'
    );
    
    $this->db->insert ( 'invoice_item', $insert_array );
    return true;
    }

    function check_unique_did($number) {
    $where = array (
    'number' => $number
    );
    $query = $this->db_model->countQuery ( "", "dids", $where );
    return $query;
    }
     
    
    function upsert_reseller_product_old($productid, $accountinfo, $add_array)
    {
        $data = array(
            'product_id' => (int) $productid,
            'account_id' => (int) $accountinfo['id'],
            'reseller_id' => (int) $accountinfo['reseller_id'],
            'buy_cost' => $add_array['buy_cost'],
            'commission' => $add_array['commission'],
            'setup_fee' => $add_array['setup_fee'],
            'price' => $add_array['price'],
            'free_minutes' => $add_array['free_minutes'],
            'billing_type' => $add_array['billing_type'],
            'billing_days' => $add_array['billing_days'],
            'status' => 0,
            'is_optin' => 0,
            'is_owner' => 1,
            'optin_date' => gmdate('Y-m-d H:i:s')
        );
        $sql = $this->db->set($data)->get_compiled_insert('reseller_products') .
            " ON DUPLICATE KEY UPDATE product_id = VALUES(product_id), account_id = VALUES(account_id), reseller_id = VALUES(reseller_id), buy_cost = VALUES(buy_cost), commission = VALUES(commission), setup_fee = VALUES(setup_fee), price = VALUES(price), free_minutes = VALUES(free_minutes), billing_type = VALUES(billing_type), billing_days = VALUES(billing_days), status = VALUES(status), is_optin = VALUES(is_optin), is_owner = VALUES(is_owner), optin_date = VALUES(optin_date)";
        return $this->db->query($sql);
    }
    
    function upsert_reseller_product($productid, $accountinfo, $add_array)
    {
        $data = array(
            'product_id'    => (int) $productid,
            'account_id'    => (int) $accountinfo['id'],
            'reseller_id'   => (int) $accountinfo['reseller_id'],
            'buy_cost'      => $add_array['buy_cost'],
            'commission'    => $add_array['commission'],
            'setup_fee'     => $add_array['setup_fee'],
            'price'         => $add_array['price'],
            'free_minutes'  => $add_array['free_minutes'],
            'billing_type'  => $add_array['billing_type'],
            'billing_days'  => $add_array['billing_days'],
            'status'        => 0,
            'is_optin'      => 0,
            'is_owner'      => 1,
            'optin_date'    => gmdate('Y-m-d H:i:s')
        );
    
        $sql = $this->db->insert_string('reseller_products', $data) . "
            ON DUPLICATE KEY UPDATE
                product_id = VALUES(product_id),
                account_id = VALUES(account_id),
                reseller_id = VALUES(reseller_id),
                buy_cost = VALUES(buy_cost),
                commission = VALUES(commission),
                setup_fee = VALUES(setup_fee),
                price = VALUES(price),
                free_minutes = VALUES(free_minutes),
                billing_type = VALUES(billing_type),
                billing_days = VALUES(billing_days),
                status = VALUES(status),
                is_optin = VALUES(is_optin),
                is_owner = VALUES(is_owner),
                optin_date = VALUES(optin_date)";
    
        return $this->db->query($sql);
    }
    
    function update_did_parent_by_product($productid, $parent_id)
    {
        $this->db->where('product_id', $productid);
        return $this->db->update('dids', array('parent_id' => $parent_id));
    }
    
    function get_assignment_product_info($product_id, $is_reseller = false)
    {
        if ($is_reseller) {
            return (array) $this->db->get_where('reseller_products', array('product_id' => $product_id))->first_row();
        }
        return (array) $this->db->get_where('products', array('id' => $product_id))->first_row();
    }
    
    function update_did_account_by_product($productid, $accountid)
    {
        $this->db->where('product_id', $productid);
        return $this->db->update('dids', array('accountid' => $accountid));
    }
    
    function get_did_release_context($id)
    {
        $did_info = (array) $this->db->get_where('dids', array('id' => $id))->first_row();
        if (empty($did_info)) {
            return array('did_info' => array(), 'accountinfo' => array());
        }
        $owner_id = !empty($did_info['accountid']) ? $did_info['accountid'] : $did_info['parent_id'];
        $accountinfo = (array) $this->db->get_where('accounts', array('id' => $owner_id))->first_row();
        return array('did_info' => $did_info, 'accountinfo' => $accountinfo);
    }
    
    function get_dids_by_product_ids($ids)
    {
        if (trim($ids) === '') {
            return array();
        }
        return $this->db->query("SELECT * FROM dids WHERE product_id IN (" . $ids . ")")->result_array();
    }
    
    function cleanup_released_dids($ids, $accountinfo, $did_info)
    {
        $where = "product_id IN (" . $ids . ")";
        if ($this->session->userdata['userlevel_logintype'] == '1' || $this->session->userdata['userlevel_logintype'] == '5') {
            if ($accountinfo['reseller_id'] > 0) {
                $this->db->where($where, null, false);
                $this->db->where('reseller_id', $accountinfo['reseller_id']);
                $this->db->where('account_id', $accountinfo['id']);
                $this->db->delete('reseller_products');
                $this->db->where($where, null, false);
                $this->db->update('dids', array('parent_id' => $accountinfo['reseller_id']));
            } else {
                $this->db->where($where, null, false);
                $this->db->where('account_id', $accountinfo['id']);
                $this->db->delete('reseller_products');
            }
            return array();
        }
    
        if ($this->session->userdata['userlevel_logintype'] != '-1' && $this->session->userdata['userlevel_logintype'] != '2') {
            return array();
        }
    
        $mail_payload = array();
        $product = (array) $this->db->get_where('products', array('id' => $did_info['product_id']))->first_row();
        if (!empty($product)) {
            $mail_payload['product_name'] = $product['name'];
            $mail_payload['category_name'] = $this->common->get_field_name('name', 'category', array('id' => $product['product_category']));
            $acc_id = $this->common->get_field_name('accountid', 'order_items', array('product_id' => $did_info['product_id']));
            $order_items_id = $this->common->get_field_name('order_id', 'order_items', array('product_id' => $did_info['product_id']));
            $mail_payload['order_id'] = $this->common->get_field_name('order_id', 'orders', array('id' => $order_items_id));
            $mail_payload['next_billing_date'] = gmdate('Y-m-d H:i:s');
            $acc_info = (array) $this->db->get_where('accounts', array('id' => $acc_id))->first_row();
            if (!empty($acc_info)) {
                $mail_payload = array_merge($acc_info, $mail_payload);
            }
        }
    
        $this->db->where("id IN (" . $ids . ")", null, false);
        $this->db->delete('products');
        $this->db->where(array('id' => $did_info['id']));
        $this->db->delete('dids');
        return $mail_payload;
    }
    
    function get_customer_did_listing($accountid, $accounttype, $instant_search, $rp, $page)
    {
        $account_arr = (array) $this->db->get_where('accounts', array('id' => $accountid))->first_row();
        if (empty($account_arr)) {
            return array('count' => 0, 'query' => false);
        }
    
        $offset = max(((int) $page - 1), 0) * (int) $rp;
    
        if ($account_arr['reseller_id'] != 0 && $accounttype != 'reseller') {
            $base_sql = " FROM dids INNER JOIN reseller_products ON dids.product_id = reseller_products.product_id
                WHERE dids.accountid = ? AND dids.parent_id = ? AND dids.status = 0";
            $params = array((int) $accountid, (int) $account_arr['reseller_id']);
            if (!empty($instant_search)) {
                $base_sql .= " AND (dids.number LIKE ? OR dids.inc LIKE ? OR dids.cost LIKE ? OR dids.includedseconds LIKE ? OR reseller_products.setup_fee LIKE ? OR reseller_products.price LIKE ? OR dids.connectcost LIKE ? OR dids.province LIKE ? OR dids.city LIKE ?)";
                $like = '%' . $instant_search . '%';
                $params = array_merge($params, array_fill(0, 9, $like));
            }
            $count = (array) $this->db->query("SELECT COUNT(*) AS count" . $base_sql, $params)->first_row();
            $query = $this->db->query("SELECT dids.id as did_id,dids.product_id as id,dids.number,dids.cost,dids.inc,dids.country_id,dids.call_type,dids.extensions,dids.connectcost,dids.init_inc,dids.includedseconds,reseller_products.buy_cost,reseller_products.commission,reseller_products.setup_fee,reseller_products.price,reseller_products.billing_type,reseller_products.billing_days,reseller_products.status,dids.last_modified_date,dids.city,dids.province" . $base_sql . " ORDER BY dids.id DESC LIMIT " . (int)$offset . "," . (int)$rp, $params);
            return array('count' => (int) $count['count'], 'query' => $query);
        }
    
        $field_name = $accounttype == "reseller" ? "parent_id" : 'accountid';
        $base_sql = " FROM dids INNER JOIN products ON dids.product_id = products.id WHERE dids." . $field_name . " = ?";
        $params = array((int) $accountid);
        if (!empty($instant_search)) {
            $base_sql .= " AND (dids.number LIKE ? OR dids.inc LIKE ? OR dids.cost LIKE ? OR dids.includedseconds LIKE ? OR dids.setup LIKE ? OR dids.monthlycost LIKE ? OR dids.connectcost LIKE ? OR dids.province LIKE ? OR dids.city LIKE ?)";
            $like = '%' . $instant_search . '%';
            $params = array_merge($params, array_fill(0, 9, $like));
        }
        $count = (array) $this->db->query("SELECT COUNT(*) AS count" . $base_sql, $params)->first_row();
        $query = $this->db->query("SELECT dids.id as did_id,dids.product_id as id,dids.number,dids.cost,dids.inc,dids.country_id,dids.call_type,dids.extensions,dids.connectcost,dids.init_inc,dids.includedseconds,products.buy_cost,products.commission,products.setup_fee,products.price,products.billing_type,products.billing_days,products.status,dids.last_modified_date,dids.city,dids.province" . $base_sql . " ORDER BY dids.id DESC LIMIT " . (int)$offset . "," . (int)$rp, $params);
        return array('count' => (int) $count['count'], 'query' => $query);
    }
    
    function get_currency_info($currency_id)
    {
        $this->db->select('currency');
        $this->db->where('id', $currency_id);
        return (array) $this->db->get('currency')->first_row();
    }

    function get_import_account_by_number($account_number)
    {
        $this->db->where('type IN(0,1,3)', null, false);
        $this->db->where('reseller_id', 0);
        $this->db->where('deleted', 0);
        $this->db->where('status', 0);
        return (array) $this->db->get_where('accounts', array(
            'number' => $account_number
        ))->first_row();
    }


}
