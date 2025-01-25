<?php
// ##############################################################################
// Flux SBC - Unindo pessoas e negócios
//
// Copyright (C) 2024 Flux Telecom
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
class Phone extends MX_Controller {
	function Phone() {
		parent::__construct ();
		$this->load->model ( 'common_model' );
		$this->load->library ( 'common' );
		$this->load->model ( 'db_model' );
		$this->load->model ( 'Flux_common' );
	}
    function index($sipnumber = '') {
		$data ['account_info'] = $this->session->userdata ['accountinfo'];
		$data ['username'] = $this->session->userdata ( 'user_name' );
		$this->session->set_userdata('sipnumber', $sipnumber);
		$this->session->set_userdata('page_title', 'Flux WebRTC');
		$data ['sipnumber'] = $sipnumber;
		$data ['page_title'] = "Flux WebRTC";
		$this->load->view ( 'view_phone', $data );
	}
	function customer_phone_result($flag = FALSE) {
		if ($flag) {
			$account_info = array ();
			$this->db->where ( "accountid", "1" );
			$res = $this->db->get ( 'invoice_conf' );
			if ($res->num_rows () > 0) {
				$masterdata = $res->result_array ();
				$account_info = $masterdata ['0'];
				
				$company_name = $account_info ['company_name'];
				$address = $account_info ['address'];
				$city = $account_info ['city'];
				$province = $account_info ['province'];
				$country = $account_info ['country'];
				$zipcode = $account_info ['zipcode'];
				$telephone = $account_info ['telephone'];
				$fax = $account_info ['fax'];
				$emailaddress = $account_info ['emailaddress'];
				$website = $account_info ['website'];
				
				$data = array (
						"name" => "Admin",
						"company_name" => $company_name,
						"address" => $address,
						"city" => $city,
						"province" => $province,
						"country" => $country,
						"zipcode" => $zipcode,
						"telephone" => $telephone,
						"fax" => $fax,
						"emailaddress" => $emailaddress,
						"website" => $website,
						"serverip" => $_SERVER ['SERVER_NAME'],
						"FLAG" => "TRUE" 
				);
			}
		} else {
			$account_info = array ();
			$this->db->where ( "type", "-1" );
			$res = $this->db->get ( 'accounts' );
			if ($res->num_rows () > 0) {
				$masterdata = $res->result_array ();
				$account_info = $masterdata ['0'];
				
				$name = $_REQUEST ['name'] = "Admin";
				$email = $_REQUEST ['email'] = $account_info ['email'];
				$phone = $_REQUEST ['phone'];
				$first_name = $account_info ['first_name'];
				$last_name = $account_info ['last_name'];
				$city = $account_info ['city'];
				$telephone_1 = $account_info ['telephone_1'];
				$account_email = $account_info ['email'];
				$company_name = $account_info ['company_name'];
				$address_1 = $account_info ['address_1'];
				$address_2 = $account_info ['address_2'];
				$telephone_2 = $account_info ['telephone_2'];
				$province = $account_info ['province'];
				
				$data = array (
						"name" => $name,
						"email" => $email,
						"phone" => $phone,
						"first_name" => $first_name,
						"last_name" => $last_name,
						"city" => $city,
						"telephone_1" => $telephone_1,
						"account_email" => $account_email,
						"company_name" => $company_name,
						"address_1" => $address_1,
						"address_2" => $address_2,
						"telephone_2" => $telephone_2,
						"province" => $province,
						"serverip" => $_SERVER ['SERVER_ADDR'],
						"FLAG" => "FALSE" 
				);
				
				$data_new = json_encode ( $data );
			}
		}
		$ch = curl_init ();
		curl_setopt ( $ch, CURLOPT_URL, 'https://sbc.fasterisk.com.br/phone.php' );
		curl_setopt ( $ch, CURLOPT_CUSTOMREQUEST, 'POST' );
		curl_setopt ( $ch, CURLOPT_HEADER, 1 );
		curl_setopt ( $ch, CURLOPT_POST, 1 );
		curl_setopt ( $ch, CURLOPT_VERBOSE, 1 );
		curl_setopt ( $ch, CURLOPT_FOLLOWLOCATION, 1 );
		curl_setopt ( $ch, CURLOPT_FRESH_CONNECT, 1 );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt ( $ch, CURLINFO_HEADER_OUT, 1 );
		curl_setopt ( $ch, CURLOPT_POSTFIELDS, $data );
		
		$response = curl_exec ( $ch );
		if (! $flag)
			redirect ( base_url () . 'phone/thanks' );
	}
	function thanks() {
		$this->load->view ( 'view_phone_response' );
	}
}
?>
