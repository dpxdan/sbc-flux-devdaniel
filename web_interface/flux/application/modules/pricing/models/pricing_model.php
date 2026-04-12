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
class pricing_model extends CI_Model
{

    function __construct()
    {
        parent::__construct();
    }

    function getpricing_list($flag, $start = 0, $limit = 0)
    {
        $this->db_model->build_search('price_list_search');
        if ($this->session->userdata('logintype') == 1 || $this->session->userdata('logintype') == 5) {
            $account_data = $this->session->userdata("accountinfo");
            $reseller = $account_data['id'];
            $where = array(
                "reseller_id" => $reseller,
                "status != " => "2"
            );
        } else {
            $where = array(
                "status != " => "2"
            );
        }
        if ($flag) {
            $query = $this->db_model->Select("*", "pricelists", $where, "id", "ASC", $limit, $start);
        } else {
            $query = $this->db_model->countQuery("*", "pricelists", $where);
        }
        return $query;
    }

    function getpricing_refactor_list($flag, $start = 0, $limit = 0)
    {        
        $this->db_model->build_search('price_refactor_search');
        if ($this->session->userdata('logintype') == 1 || $this->session->userdata('logintype') == 5) {
            $account_data = $this->session->userdata("accountinfo");
            $reseller = $account_data['id'];
            $where = array(
                "reseller_id" => $reseller
            );
        }
        if ($flag) {
            $query = $this->db_model->Select("*", "refactor" , $where = "", "id", "ASC", $limit, $start);
        } else {
            $query = $this->db_model->countQuery("*", "refactor", $where = "");
        }
        return $query;
    }

    function add_price($add_array)
    {
        unset($add_array["action"]);
        $add_array['creation_date'] = gmdate("Y-m-d H:i:s");
        $add_array['last_modified_date'] = gmdate("Y-m-d H:i:s");
        if(isset($add_array['routing_type']) && $add_array['routing_type']!=""){
				$add_array['routing_type'] = ($add_array['routing_type'] > 1)?1:$add_array['routing_type'];
		}
        if ($this->session->userdata('logintype') == 1 || $this->session->userdata('logintype') == 5) {
            $account_data = $this->session->userdata("accountinfo");
            $add_array["reseller_id"] = $account_data['id'];
        }
        $this->db->insert("pricelists", $add_array);

        return $this->db->insert_id();
    }

    function edit_price($data, $id)
    {
        unset($data["action"]);
        $data['routing_type'] = ($data['routing_type'] > 10)?1:$data['routing_type'];
        $data['last_modified_date'] = gmdate("Y-m-d H:i:s");
        $this->db->where("id", $id);
        $this->db->update("pricelists", $data);
        return true;
    }

    function get_price_list_for_cdrs()
    {
        if ($this->session->userdata('username') != "" && $this->session->userdata('logintype') != 2) {
            $this->db->where('reseller', $this->session->userdata('username'));
        } else {
            $this->db->where(array(
                'reseller' => "0"
            ));
        }
        $this->db->where('status <', 2);
        $this->db->order_by('name', 'desc');
        $query = $this->db->get("pricelists");
        $price_list = array();
        $result = $query->result_array();
        foreach ($result as $row) {
            $price_list[$row['name']] = $row['name'];
        }
        return $price_list;
    }

    function add_origination($add_array)
    {
        unset($add_array["action"]);
        $add_array['creation_date'] = gmdate("Y-m-d H:i:s");
        if ($this->session->userdata('logintype') == 1 || $this->session->userdata('logintype') == 5) {
            $account_data = $this->session->userdata("accountinfo");
            $add_array["reseller_id"] = $account_data['id'];
        } else {
            $add_array["reseller_id"] = "0";
        }
        $this->db->insert("routes", $add_array);

        return $this->db->insert_id();
    }

    function check_unique_prefix_for_edit($routing_prefix)
    {
        $where = array(
            'routing_prefix' => $routing_prefix,
            "status != " => 2
        );
        $this->db->where($where);
        $this->db->select("*");
        $this->db->from('pricelists');
        $query = $this->db->get();
        return $query;
    }

    function check_unique_prefix($routing_prefix)
    {
        $where = array(
            'routing_prefix' => $routing_prefix,
            "status != " => 2
        );
        $query = $this->db_model->countQuery("*", "pricelists", $where);
        return $query;
    }

    function add_refactor($add_array)
    {
        unset($add_array["action"]);

        if ($this->session->userdata('logintype') == 1 || $this->session->userdata('logintype') == 5) {
            $account_data = $this->session->userdata("accountinfo");
            $add_array["reseller_id"] = $account_data['id'];
        } else {
            $add_array["reseller_id"] = "0";
        }

        $add_array['creation_date'] = gmdate("Y-m-d H:i:s");
        $add_array['from_date'] = $add_array['callstart'][0];
        $add_array['to_date'] = $add_array['callstart'][1];
        $add_array['account_id'] = $add_array['accountcode'];

        unset($add_array["callstart"]);
        unset($add_array["accountcode"]);
        
        $where = array(
            'id' => $add_array['account_id'],
        );
        $this->db->where($where);
        $this->db->select("pricelist_id");
        $this->db->from('accounts');
        $query = $this->db->get();
        $result = $query->row();
        $add_array['pricelist_id'] = $result->pricelist_id;

        $this->db->insert("refactor", $add_array);

        return $this->db->insert_id();
    }

    function normalize_ids($selected_ids)
    {
        return array_values(array_filter(array_map('intval', array_map('trim', explode(',', $selected_ids)))));
    }

    function delete_routing_by_pricelist($pricelist_id)
    {
        return $this->db->delete("routing", array("pricelist_id" => $pricelist_id));
    }

    function set_force_routing($priceid, $trunkid)
    {
        foreach ((array) $trunkid as $id) {
            $routing_arr = array(
                "trunk_id" => $id,
                "pricelist_id" => $priceid
            );
            $this->db->insert("routing", $routing_arr);
        }
        return true;
    }

    function delete_multiple_pricelists($selected_ids)
    {
        $ids = $this->normalize_ids($selected_ids);
        if (empty($ids)) {
            return false;
        }
        $update_data = array('status' => '2');
        $this->db->where_in('pricelist_id', $ids);
        $this->db->delete('routes');
        $this->db->where_in('pricelist_id', $ids);
        $this->db->delete('routing');
        $this->db->where_in('id', $ids);
        return $this->db->update('pricelists', $update_data);
    }

    function get_pricelist_delete_summary($selected_ids)
    {
        $ids = $this->normalize_ids($selected_ids);
        $data = array('selected_ids' => $selected_ids);
        if (empty($ids)) {
            return $data;
        }
        $pricelist_arr = array();
        $this->db->select('id,name');
        $this->db->where_in('id', $ids);
        foreach ($this->db->get('pricelists')->result_array() as $value) {
            $pricelist_arr[$value['id']]['name'] = $value['name'];
        }
        $this->db->where_in('pricelist_id', $ids);
        $this->db->where('deleted', 0);
        $this->db->select('count(id) as cnt,pricelist_id');
        $this->db->group_by('pricelist_id');
        $account_res = $this->db->get('accounts');
        if ($account_res->num_rows() > 0) {
            foreach ($account_res->result_array() as $value) {
                $pricelist_arr[$value['pricelist_id']]['account'] = $value['cnt'];
            }
        }
        $this->db->where_in('pricelist_id', $ids);
        $this->db->select('count(id) as cnt,pricelist_id');
        $this->db->group_by('pricelist_id');
        $routes_res = $this->db->get('routes');
        if ($routes_res->num_rows() > 0) {
            foreach ($routes_res->result_array() as $value) {
                $pricelist_arr[$value['pricelist_id']]['routes'] = $value['cnt'];
            }
        }
        $str = null;
        foreach ($pricelist_arr as $value) {
            $custom_str = null;
            if (isset($value['account']) || isset($value['routes'])) {
                if (isset($value['account'])) {
                    $custom_str .= $value['account'] . " accounts and ";
                }
                if (isset($value['routes'])) {
                    $custom_str .= $value['routes'] . " origination rates and ";
                }
                $str .= " Rate group Name : " . $value['name'] . " using by " . rtrim($custom_str, " and ") . "
";
            }
        }
        if (! empty($str)) {
            $data['str'] = $str;
        }
        return $data;
    }

    function duplicate_pricelist($selected_pricegroup_id, $new_name)
    {
        $this->db->where('id', $selected_pricegroup_id);
        $this->db->select('*');
        $price_grp_res = $this->db->get('pricelists');
        if ($price_grp_res->num_rows() <= 0) {
            return false;
        }
        $price_grp = $price_grp_res->row_array();
        $add_price_array = array(
            'name' => $new_name,
            'markup' => $price_grp['markup'],
            'routing_prefix' => $price_grp['routing_prefix'],
            'routing_type' => $price_grp['routing_type'],
            'initially_increment' => $price_grp['initially_increment'],
            'inc' => $price_grp['inc'],
            'status' => $price_grp['status'],
            'reseller_id' => $price_grp['reseller_id'],
            'creation_date' => date('Y-m-d H:i:s')
        );
        $insert_id = $this->add_price($add_price_array);
        $this->db->where('pricelist_id', $price_grp['id']);
        $this->db->select('*');
        $routes_grp_res = $this->db->get('routes');
        if ($routes_grp_res->num_rows() > 0) {
            foreach ($routes_grp_res->result_array() as $value) {
                unset($value['id']);
                $value['pricelist_id'] = $insert_id;
                $this->add_origination($value);
            }
        }
        return true;
    }

    function delete_multiple_refactors($selected_ids)
    {
        $ids = $this->normalize_ids($selected_ids);
        if (empty($ids)) {
            return false;
        }
        $this->db->where_in('id', $ids);
        return $this->db->delete('refactor');
    }

}
