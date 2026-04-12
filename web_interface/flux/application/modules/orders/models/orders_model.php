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
class Orders_model extends CI_Model
{

    function __construct()
    {
        parent::__construct();
    }

    function getorders_list($flag, $start = 0, $limit = 0)
    {
        $this->db_model->build_search('orders_list_search', 'orders.');
        if ($this->session->userdata('logintype') == 1 || $this->session->userdata('logintype') == 5) {
            $account_data = $this->session->userdata("accountinfo");
            $where_arr = array(
                "orders.reseller_id" => $account_data['id']
            );
        } else {
            $where_arr = array();
        }
        if (isset($_GET['sortname']) && $_GET['sortname'] != 'undefined') {
            $this->db->order_by($_GET['sortname'], ($_GET['sortorder'] == 'undefined') ? 'desc' : $_GET['sortorder']);
        } else {
            $where = $this->db->order_by("order_date", "desc");
        }
        if ($flag) {
            $query = $this->db_model->getJionQuery('orders', 'orders.id,orders.order_id ,orders.order_date,(CASE WHEN orders.`payment_gateway`="Account Balance" THEN CONCAT("Saldo em Conta") ELSE orders.`payment_gateway` END) AS payment_gateway,(CASE WHEN order_items.`is_terminated`=0 THEN CONCAT("Ativo") ELSE CONCAT("Inativo") END) AS order_status,(CASE WHEN orders.`payment_status`="PAID" THEN CONCAT("Pago") ELSE orders.`payment_status` END) AS payment_status,orders.reseller_id,orders.accountid,order_items.termination_date,order_items.next_billing_date,order_items.product_id,order_items.setup_fee,order_items.price', $where_arr, 'order_items', 'orders.id=order_items.order_id', 'inner', $limit, $start, '', '');
        } else {
            $query = $this->db_model->getJionQueryCount('orders', 'orders.id,orders.order_id,orders.order_date,(CASE WHEN orders.`payment_gateway`="Account Balance" THEN CONCAT("Saldo em Conta") ELSE orders.`payment_gateway` END) AS payment_gateway,(CASE WHEN order_items.`is_terminated`=0 THEN CONCAT("Ativo") ELSE CONCAT("Inativo") END) AS order_status,(CASE WHEN orders.`payment_status`="PAID" THEN CONCAT("Pago") ELSE orders.`payment_status` END) AS payment_status,orders.reseller_id,orders.accountid,order_items.termination_date,order_items.next_billing_date,order_items.product_id,order_items.setup_fee,order_items.price', $where_arr, 'order_items', 'orders.id=order_items.order_id', 'inner', "", "", '', '');
        }

        return $query;
    }

    function remove_order($id)
    {
        $this->db->where("id", $id);
        $this->db->delete("orders");
        return true;
    }

    function get_reseller_orders_list($flag, $start = 0, $limit = 0)
    {
        $this->db_model->build_search('reseller_orders_list_search', 'orders.');
        if ($this->session->userdata('logintype') == 1 || $this->session->userdata('logintype') == 5) {
            $account_data = $this->session->userdata("accountinfo");
            $where_arr = array(
                "orders.accountid" => $account_data['id']
            );
        } else {
            $where_arr = array();
        }
        if (isset($_GET['sortname']) && $_GET['sortname'] != 'undefined') {
            $this->db->order_by($_GET['sortname'], ($_GET['sortorder'] == 'undefined') ? 'desc' : $_GET['sortorder']);
        } else {
            $where = $this->db->order_by("order_date", "desc");
        }
        if ($flag) {
            $query = $this->db_model->getJionQuery('orders', 'orders.id,orders.order_id ,orders.order_date,orders.accountid,orders.payment_gateway,orders.payment_status,orders.reseller_id,orders.accountid,order_items.setup_fee,order_items.price', $where_arr, 'order_items', 'orders.id=order_items.order_id', 'inner', $limit, $start, '', '');
        } else {
            $query = $this->db_model->getJionQueryCount('orders', 'orders.id,orders.order_id,orders.order_date,orders.payment_gateway,orders.payment_status,orders.reseller_id,orders.accountid,order_items.setup_fee,order_items.price', $where_arr, 'order_items', 'orders.id=order_items.order_id', 'inner', "", "", '', '');
        }
        return $query;
    }

    function normalize_ids($selected_ids)
    {
        return array_values(array_filter(array_map('intval', array_map('trim', explode(',', $selected_ids)))));
    }

    function delete_multiple_orders($ids)
    {
        $id_list = $this->normalize_ids($ids);
        if (empty($id_list)) {
            return false;
        }
        $this->db->where_in('order_id', $id_list);
        $this->db->delete("order_items");
        $this->db->where_in('id', $id_list);
        return $this->db->delete("orders");
    }





    function get_order_add_product_data($accountinfo, $add_array)
    {
        $where_str = '(reseller_products.is_optin=0 OR reseller_products.is_owner=0)';
        $this->db->where($where_str);
        if ($accountinfo['reseller_id'] > 0) {
            return $this->db_model->getJionQuery('products', 'products.id,products.name,products.product_category,reseller_products.buy_cost,products.commission,reseller_products.price,reseller_products.setup_fee,reseller_products.billing_type,reseller_products.billing_days,reseller_products.free_minutes,products.status,products.last_modified_date,reseller_products.product_id', array(
                'products.status' => 0,
                'products.id' => $add_array['product_id'],
                'reseller_products.account_id' => $accountinfo['id'],
                'reseller_products.reseller_id' => $accountinfo['reseller_id']
            ), 'reseller_products', 'products.id=reseller_products.product_id', 'inner', "", "", 'DESC', 'products.id');
        }
        if ($add_array['reseller_id'] != '0') {
            return $this->db_model->getJionQuery('products', 'products.id,products.name,products.product_category,reseller_products.buy_cost,products.commission,reseller_products.price,reseller_products.setup_fee,reseller_products.billing_type,reseller_products.billing_days,reseller_products.free_minutes,products.status,products.last_modified_date,reseller_products.product_id', array(
                'products.status' => 0,
                'products.id' => $add_array['product_id'],
                'reseller_products.account_id' => $add_array['reseller_id']
            ), 'reseller_products', 'products.id=reseller_products.product_id', 'inner', "", "", 'DESC', 'products.id');
        }
        return $this->db_model->getJionQuery('products', 'products.id,products.name,products.product_category,reseller_products.buy_cost,products.commission,reseller_products.price,reseller_products.setup_fee,reseller_products.billing_type,reseller_products.billing_days,reseller_products.free_minutes,products.status,products.last_modified_date,reseller_products.product_id', array(
            'products.status' => 0,
            'products.id' => $add_array['product_id'],
            'reseller_products.account_id' => $accountinfo['id']
        ), 'reseller_products', 'products.id=reseller_products.product_id', 'inner', "", "", 'DESC', 'products.id');
    }

    function get_non_refill_product_dropdown($category_ids)
    {
        $where_arr = array();
        $where_arr['where'] = $this->db->where('reseller_id', 0);
        $where_arr['where'] = $this->db->where("product_category IN (" . $category_ids . ")", NULL, false);
        return $this->db_model->build_dropdown("id,name", "products", "", $where_arr);
    }

    function get_order_with_items($orderid)
    {
        $this->db->where('orders.id', $orderid);
        $this->db->or_where('orders.order_id', $orderid);
        return $this->db_model->getJionQuery('orders', '*,orders.order_id as orderid', '', 'order_items', 'orders.id=order_items.order_id', 'inner', '', '', '', '');
    }

    function get_reseller_product_data($accountinfo, $product_id)
    {
        $where_str = '(reseller_products.is_optin=0 OR reseller_products.is_owner=0)';
        $this->db->where($where_str);
        if ($accountinfo['reseller_id'] > 0) {
            return $this->db_model->getJionQuery('products', 'products.id,products.name,products.product_category,reseller_products.status,reseller_products.buy_cost,products.commission,reseller_products.price,reseller_products.setup_fee,reseller_products.billing_type,reseller_products.billing_days,reseller_products.free_minutes,products.status,products.last_modified_date,reseller_products.product_id', array(
                'products.status' => 0,
                'products.id' => $product_id,
                'reseller_products.account_id' => $accountinfo['id'],
                'reseller_products.reseller_id' => $accountinfo['reseller_id']
            ), 'reseller_products', 'products.id=reseller_products.product_id', 'inner', '', '', 'DESC', 'products.id');
        }
        return $this->db_model->getJionQuery('products', 'products.id,products.name,products.product_category,reseller_products.status,reseller_products.buy_cost,products.commission,reseller_products.price,reseller_products.setup_fee,reseller_products.billing_type,reseller_products.billing_days,reseller_products.free_minutes,products.status,products.last_modified_date,reseller_products.product_id', array(
            'products.status' => 0,
            'products.id' => $product_id
        ), 'reseller_products', 'products.id=reseller_products.product_id', 'inner', '', '', 'DESC', 'products.id');
    }

    function get_available_product_item_list($add_array, $accountinfo, $login_type, $excluded_product_ids = '')
    {
        if ($login_type == 1) {
            $where_str = '(reseller_products.is_optin=0 OR reseller_products.is_owner=0)';
            $this->db->where($where_str);
            if ($accountinfo['reseller_id'] > 0) {
                if ($add_array['reseller_id'] != 0 && $add_array['accountid'] != 0) {
                    $query = $this->db_model->getJionQuery('products', ' products.id,products.name,products.product_category,products.buy_cost,products.commission,reseller_products.price,reseller_products.setup_fee,reseller_products.billing_type,reseller_products.billing_days,reseller_products.free_minutes,products.status,products.last_modified_date,reseller_products.product_id', array(
                        'reseller_products.account_id' => $add_array['reseller_id'],
                        'reseller_products.reseller_id' => $accountinfo['id'],
                        'reseller_products.status' => 0,
                        'products.is_deleted' => 0,
                        'products.product_category' => $add_array['category_id'],
                        'reseller_products.is_optin' => 0
                    ), 'reseller_products', 'products.id=reseller_products.product_id', 'inner', '', '', 'DESC', 'products.id');
                } else {
                    $query = $this->db_model->getJionQuery('products', ' products.id,products.name,products.product_category,products.buy_cost,products.commission,reseller_products.price,reseller_products.setup_fee,reseller_products.billing_type,reseller_products.billing_days,reseller_products.free_minutes,products.status,products.last_modified_date,reseller_products.product_id', array(
                        'reseller_products.account_id' => $accountinfo['id'],
                        'reseller_products.reseller_id' => $accountinfo['reseller_id'],
                        'reseller_products.status' => 0,
                        'products.product_category' => $add_array['category_id'],
                        'products.is_deleted' => 0
                    ), 'reseller_products', 'products.id=reseller_products.product_id', 'inner', '', '', 'DESC', 'products.id');
                }
            } else {
                if ($add_array['reseller_id'] != 0 && $add_array['accountid'] != 0) {
                    $query = $this->db_model->getJionQuery('products', ' products.id,products.name,products.product_category,products.buy_cost,products.commission,reseller_products.price,reseller_products.setup_fee,reseller_products.billing_type,reseller_products.billing_days,reseller_products.free_minutes,products.status,products.last_modified_date,reseller_products.product_id', array(
                        'reseller_products.account_id' => $add_array['reseller_id'],
                        'reseller_products.reseller_id' => $accountinfo['id'],
                        'reseller_products.status' => 0,
                        'products.product_category' => $add_array['category_id'],
                        'reseller_products.is_optin' => 0,
                        'products.is_deleted' => 0
                    ), 'reseller_products', 'products.id=reseller_products.product_id', 'inner', '', '', 'DESC', 'products.id');
                } else {
                    $query = $this->db_model->getJionQuery('products', ' products.id,products.name,products.product_category,reseller_products.buy_cost,reseller_products.commission,reseller_products.price,reseller_products.setup_fee,reseller_products.billing_type,reseller_products.billing_days,reseller_products.free_minutes,products.status,products.last_modified_date,reseller_products.product_id', array(
                        'reseller_products.reseller_id' => $accountinfo['reseller_id'],
                        'reseller_products.account_id' => $accountinfo['id'],
                        'reseller_products.status' => 0,
                        'products.product_category' => $add_array['category_id'],
                        'products.is_deleted' => 0
                    ), 'reseller_products', 'products.id=reseller_products.product_id', 'inner', '', '', 'DESC', 'products.id');
                }
            }
            return $query->result_array();
        }

        if ($add_array['reseller_id'] != 0 && $add_array['accountid'] != 0) {
            $query = $this->db_model->getJionQuery('products', ' products.id,products.name,products.product_category,products.buy_cost,products.commission,reseller_products.price,reseller_products.setup_fee,reseller_products.billing_type,reseller_products.billing_days,reseller_products.free_minutes,products.status,products.last_modified_date,reseller_products.product_id', array(
                'reseller_products.account_id' => $add_array['reseller_id'],
                'reseller_products.status' => 0,
                'products.product_category' => $add_array['category_id'],
                'reseller_products.is_optin' => 0,
                'products.is_deleted' => 0
            ), 'reseller_products', 'products.id=reseller_products.product_id', 'inner', '', '', 'DESC', 'products.id');
            return $query->result_array();
        }

        $reseller_id = $add_array['reseller_id'] ? $add_array['reseller_id'] : 0;
        $where_arr = array();
        if ($excluded_product_ids) {
            $where_arr['where'] = $this->db->where('id NOT IN (' . $excluded_product_ids . ')', NULL, false);
        }
        $where_arr['where'] = $this->db->where(array('product_category' => $add_array['category_id']));
        $where_arr['where'] = $this->db->where(array('status' => 0));
        $where_arr['where'] = $this->db->where(array('is_deleted' => 0));
        $where_arr['where'] = $this->db->where(array('reseller_id' => $reseller_id));
        return $this->db_model->build_dropdown('id,name', 'products', '', $where_arr);
    }

    function get_commission_order_reference($orderid)
    {
        $this->db->select('order_id');
        return (array) $this->db->get_where("commission", array(
            "id" => $orderid
        ))->first_row();
    }

    function get_order_reference_by_id($id)
    {
        $this->db->select('order_id');
        return (array) $this->db->get_where("orders", array(
            "id" => $id
        ))->first_row();
    }

    function update_order_items_by_order_id($order_id, $data)
    {
        $this->db->where("order_id", $order_id);
        return $this->db->update("order_items", $data);
    }

    function get_did_by_product($where)
    {
        $result = $this->db->get_where("dids", $where)->result_array();
        return isset($result[0]) ? (array) $result[0] : array();
    }

}
?>
