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
class local_number_model extends CI_Model {
   	function __construct() {
		parent::__construct ();
	}
	function get_local_number_list($flag, $start = 0, $limit = 0) {
		$this->db_model->build_search ( 'local_number_list_search' );
		$where = array (
				//"status" => "0" 
		);
		if ($flag) {
			$query = $this->db_model->select ( "*", "local_number", $where, "id", "ASC", $limit, $start );
		} else {
			$query = $this->db_model->countQuery ( "*", "local_number", $where );
		}
		return $query;
	}

	function get_local_number_list_customer($flag, $start = 0, $limit = 0) {

		$accountinfo = $this->session->userdata ( 'accountinfo' );
		$this->db_model->build_search ( 'local_number_list_search' );
		$where_arr = array (
				"local_number_destination.account_id" => $accountinfo['id']
		);
		if ($flag) {
			$query = $this->db_model->getJionQuery('local_number_destination', 'local_number_destination.id,local_number_destination.destination_name,local_number_destination.destination_number,local_number.country_id,local_number.province,local_number.city,local_number_destination.local_number_id,local_number_destination.creation_date',$where_arr, 'local_number','local_number_destination.local_number_id=local_number.id', 'inner',$limit, $start,'','');

			// echo $this->db->last_query();die();

		} else {
			$query = $this->db_model->getJionQueryCount('local_number_destination', '*',$where_arr, 'local_number','local_number_destination.local_number_id=local_number.id', 'inner','', '','','');
		}
		//echo $this->db->last_query();exit;
		return $query;
	}


	function add_local_number($add_array) {


		unset ( $add_array ["action"] );
		unset ( $add_array ["id"] );
		$add_array ['created_date'] = gmdate ( 'Y-m-d H:i:s' );
		// echo "<pre>";
		// print_r($add_array);die();
		$this->db->insert ( "local_number", $add_array );
		return true;
	}
	function edit_local_number($data, $id) {
		unset ( $data ["action"] );
		$data ['last_modified_date'] = gmdate ( 'Y-m-d H:i:s' );
		$this->db->where ( "id", $id );
		$this->db->update ( "local_number", $data );
	}
	function edit_local_number_destination($data, $id) {

//harsh s todo
		// print_r($data);die;

		$accountinfo = $this->session->userdata ( 'accountinfo' );
		unset ( $data ["action"] );
		unset ( $data ["id"] );

		$data ['last_modified_date'] = gmdate ( 'Y-m-d H:i:s' );
		$this->db->where ( "id", $id );
		$this->db->update ( "local_number_destination", $data );
		$local_number_id = $this->db->query("select local_number_id from local_number_destination where id = '$id'");
		$local_number_id = $local_number_id->first_row();
		$local_number_id = $local_number_id->local_number_id;
		

		$local_number    = $this->db->query("select number from local_number where id = '$local_number_id'");
		$local_number    = $local_number->first_row();

		$speed_dial['speed_num'] = $local_number->number;
		$speed_dial['number']    = $data['destination_number'];
		$speed_dial['accountid'] = $accountinfo['id'];

		$this->db->where('speed_num', $local_number->number);
  		$this->db->delete('speed_dial');

		// $this->db->insert('speed_dial',$speed_dial);
		$this->db->insert('speed_dial',$speed_dial);
	}

	function edit_local_number_destination_admin($data, $id){


		$accountinfo['id'] = $data['user_edit_id'];
		unset ( $data ["user_edit_id"] );
		unset ( $data ["action"] );
		unset ( $data ["id"] );

		$data ['last_modified_date'] = gmdate ( 'Y-m-d H:i:s' );
		$this->db->where ( "id", $id );
		$this->db->update ( "local_number_destination", $data );
		$local_number_id = $this->db->query("select local_number_id from local_number_destination where id = '$id'");
		$local_number_id = $local_number_id->first_row();
		$local_number_id = $local_number_id->local_number_id;
		

		$local_number    = $this->db->query("select number from local_number where id = '$local_number_id'");
		$local_number    = $local_number->first_row();

		$speed_dial['speed_num'] = $local_number->number;
		$speed_dial['number']    = $data['destination_number'];
		$speed_dial['accountid'] = $accountinfo['id'];

		$this->db->where('speed_num', $local_number->number);
  		$this->db->delete('speed_dial');

		// $this->db->insert('speed_dial',$speed_dial);
		$this->db->insert('speed_dial',$speed_dial);


	}

	function edit_local_number_destinations($data, $id) {
		unset ( $data ["action"] );
		$data ['last_modified_date'] = gmdate ( 'Y-m-d H:i:s' );
		$this->db->where ( "id", $id );
		$this->db->update ( "local_number", $data );
	}
	function remove_local_number($id) {
		$this->db->where ( "id", $id );
		$this->db->delete ( 'local_number' );
		return true;
	}



	function add_local_number_customer($add_array) {

		unset ( $add_array ["action"] );
		unset ( $add_array ["id"] );
		// print_r($add_array);die();
	//	$add_array ['created_date'] = gmdate ( 'Y-m-d H:i:s' );
		// echo "<pre>";
		// print_r($add_array);die();
		$this->db->insert ( "local_number_destination", $add_array );

		return true;
	}
	function edit_local_number_customer($data, $id) {
		unset ( $data ["action"] );
		$data ['last_modified_date'] = gmdate ( 'Y-m-d H:i:s' );
		$this->db->where ( "id", $id );
		$this->db->update ( "local_number_destination", $data );
	}
	function remove_local_number_customer($id) {
		$this->db->where ( "id", $id );
		$this->db->delete ( 'local_number_destination' );
		return true;
	}
	function bulk_insert_local_number($field_value) {
		$this->db->insert_batch ( 'local_number', $field_value );
		$affected_row = $this->db->affected_rows ();
		return $affected_row;
	}

	private function get_id_list($ids) {
		if (is_array($ids)) {
			$ids = implode(',', $ids);
		}
		$parts = array_filter(array_map('trim', explode(',', $ids)), 'strlen');
		$parts = array_map('intval', $parts);
		return array_values(array_filter($parts, function($value) {
			return $value > 0;
		}));
	}

	function delete_multiple_local_numbers($ids) {
		$id_list = $this->get_id_list($ids);
		if (empty($id_list)) {
			return false;
		}
		$this->db->where_in('id', $id_list);
		return $this->db->delete('local_number');
	}

	function get_local_number_destination_list($account_id, $instant_search = '', $limit = 0, $start = 0, $count_only = false, $fields = '*') {
		if ($count_only) {
			$this->db->select('COUNT(*) AS total', false);
		} else {
			$this->db->select($fields, false);
		}
		$this->db->from('local_number_destination');
		$this->db->where('account_id', $account_id);
		if (!empty($instant_search)) {
			$this->db->group_start();
			$this->db->like('destination_name', $instant_search);
			$this->db->or_like('destination_number', $instant_search);
			$this->db->group_end();
		}
		if (!$count_only) {
			$this->db->order_by('id', 'ASC');
			if ((int) $limit > 0) {
				$this->db->limit((int) $limit, (int) $start);
			}
		}
		$query = $this->db->get();
		if ($count_only) {
			$row = $query->row_array();
			return isset($row['total']) ? (int) $row['total'] : 0;
		}
		return $query;
	}

	function get_available_local_numbers($account_id, $city, $province, $country_id) {
		$this->db->from('local_number');
		$this->db->where('city', $city);
		$this->db->where('province', $province);
		$this->db->where('country_id', $country_id);
		$this->db->where('status', 0);
		$this->db->where("id NOT IN (SELECT local_number_id FROM local_number_destination WHERE account_id=" . (int) $account_id . ")", null, false);
		return $this->db->get();
	}

	function add_destination_with_speed_dial($local_number_id, $account_id, $destination_name, $destination_number, $creation_date) {
		$insert_array = array(
			'local_number_id' => $local_number_id,
			'account_id' => $account_id,
			'destination_name' => $destination_name,
			'destination_number' => $destination_number,
			'creation_date' => $creation_date
		);
		$this->db->insert('local_number_destination', $insert_array);
		$row = $this->db->get_where('local_number', array('id' => $local_number_id))->row();
		if ($row) {
			$this->db->insert('speed_dial', array(
				'accountid' => $account_id,
				'speed_num' => $row->number,
				'number' => $destination_number
			));
		}
		return true;
	}

	function remove_destination_with_speed_dial($id) {
		$query = $this->db->get_where('local_number_destination', array('id' => $id));
		$destination = $query->row();
		if ($destination) {
			$this->db->where('number', $destination->destination_number);
			$this->db->delete('speed_dial');
		}
		$this->db->where('id', $id);
		return $this->db->delete('local_number_destination');
	}

	function delete_multiple_destinations_with_speed_dial($ids) {
		$id_list = $this->get_id_list($ids);
		if (empty($id_list)) {
			return false;
		}
		$this->db->select('local_number_id,destination_number');
		$this->db->from('local_number_destination');
		$this->db->where_in('id', $id_list);
		$query = $this->db->get();
		foreach ($query->result_array() as $value) {
			$local_number = $this->db->get_where('local_number', array('id' => $value['local_number_id']))->row();
			if ($local_number) {
				$this->db->where(array(
					'speed_num' => $local_number->number,
					'number' => $value['destination_number']
				));
				$this->db->delete('speed_dial');
			}
		}
		$this->db->where_in('id', $id_list);
		return $this->db->delete('local_number_destination');
	}

}
