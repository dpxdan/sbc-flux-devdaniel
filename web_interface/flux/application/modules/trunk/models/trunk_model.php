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
class trunk_model extends CI_Model
{

    function __construct()
    {
        parent::__construct();
    }

    function gettrunk_list($flag, $start = 0, $limit = 0)
    {
        $this->db_model->build_search('trunk_list_search');
        $where = array(
            "status != " => "2"
        );
        if ($flag) {
            $query = $this->db_model->select("*", "trunks", $where, "id", "ASC", $limit, $start);
        } else {
            $query = $this->db_model->countQuery("*", "trunks", $where);
        }
        return $query;
    }

    function add_trunk($add_array)
    {
        unset($add_array["action"]);
        $add_array['creation_date'] = gmdate('Y-m-d H:i:s');
        $add_array['last_modified_date'] = gmdate('Y-m-d H:i:s');
        $this->db->insert("trunks", $add_array);
        return true;
    }

    function edit_trunk($data, $id)
    {
        unset($data["action"]);
        $data['last_modified_date'] = gmdate('Y-m-d H:i:s');
        $this->db->where("id", $id);
        $this->db->update("trunks", $data);
    }

    function remove_trunk($id)
    {
        $this->db->where("id", $id);
        $this->db->update("trunks", array(
            "status" => 2
        ));
        $this->db->where('trunk_id', $id);
        $this->db->delete('outbound_routes');
        $this->db->delete("routing", array(
            "trunk_id" => $id
        ));
        return true;
    }

    function normalize_ids($selected_ids)
    {
        return array_values(array_filter(array_map('intval', array_map('trim', explode(',', $selected_ids)))));
    }

    function delete_multiple_trunks($selected_ids)
    {
        $ids = $this->normalize_ids($selected_ids);
        if (empty($ids)) {
            return false;
        }
        $update_data = array('status' => '2');
        $this->db->where_in('trunk_id', $ids);
        $this->db->delete('outbound_routes');
        $this->db->where_in('id', $ids);
        return $this->db->update('trunks', $update_data);
    }

    function get_trunk_delete_summary($selected_ids)
    {
        $ids = $this->normalize_ids($selected_ids);
        $data = array('selected_ids' => $selected_ids);
        if (empty($ids)) {
            return $data;
        }
        $trunk_arr = array();
        $this->db->select('id,name');
        $this->db->where_in('id', $ids);
        foreach ($this->db->get('trunks')->result_array() as $value) {
            $trunk_arr[$value['id']]['name'] = $value['name'];
        }
        $this->db->where_in('trunk_id', $ids);
        $this->db->select('count(id) as cnt,trunk_id');
        $this->db->group_by('trunk_id');
        $outbound_routes_res = $this->db->get('outbound_routes');
        if ($outbound_routes_res->num_rows() > 0) {
            foreach ($outbound_routes_res->result_array() as $value) {
                $trunk_arr[$value['trunk_id']]['outbound_routes'] = $value['cnt'];
            }
        }
        $str = null;
        foreach ($trunk_arr as $value) {
            if (isset($value['outbound_routes'])) {
                $str .= $value['name'] . "trunk using by " . $value['outbound_routes'] . " termination rates 
";
            }
        }
        if (! empty($str)) {
            $data['str'] = $str;
        }
        return $data;
    }

}
