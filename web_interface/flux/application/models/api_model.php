<?php
// ##############################################################################
// Flux SBC - Unindo pessoas e negócios
//
// Copyright (C) 2025 Flux Telecom
// Daniel Paixao <daniel@flux.net.br>
// Flux SBC Version 4.2 and above
// License https://www.gnu.org/licenses/agpl-3.0.html
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as
// published by the Free Software Foundation, either version 3 of the
// License, or (at your option ) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU Affero General Public License for more details.
//
// You should have received a copy of the GNU Affero General Public License
// along with this program. If not, see <http://www.gnu.org/licenses/>.
// ##############################################################################
class Api_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->library('common');
        $this->load->model('common_model');
        $this->load->library('flux/order');
        $this->load->library('flux/signup_lib');
        $this->load->library('flux_log');
        $this->load->database();
    }

    /**
     * Inserts or updates an account in the 'accounts' table based on customer data.
     */
    public function upsert_account($customer_data) {
        $this->db->where('id_external', $customer_data['id']);
        $this->db->where('deleted', '0');
        $existing_account = $this->db->get('accounts')->row();

        $password = !empty($customer_data['senha']) ? $customer_data['senha'] : $this->common->generate_password();
        $encoded_password = $this->common->encode($password);
        $telefone = $this->sanitize_string($customer_data['fone']);
        $telefone_celular = $this->sanitize_string($customer_data['telefone_celular']);
        $razaoConvert = $this->sanitize_string($customer_data['razao']);
        
        $pin_generate = common_model::$global_config['system_config']['generate_pin'];
		if ($pin_generate == 0 ) {
			$pin = (common_model::$global_config['system_config']['pinlength'] < 6) ? 6 : common_model::$global_config['system_config']['pinlength'];
			$pin_number = $this->common->find_uniq_rendno_customer($pin, 'number', 'accounts');
		}
        
        $account_data = [
            'id_external'       => $customer_data['id'],
            'number'            => preg_replace('/[^0-9]/', '', $customer_data['cnpj_cpf']),
            'company_name' => (!empty($customer_data['fantasia'])) ? $customer_data['fantasia'] : $customer_data['razao'],
            'first_name'        => $customer_data['razao'],
            'last_name'         => $customer_data['razao'],
			'email' => (!empty($customer_data['email'])) ? $customer_data['email'] : $customer_data['id'] . ''.$razaoConvert.'@flux.net.br',            
			'notification_email' => (!empty($customer_data['email'])) ? $customer_data['email'] : $customer_data['id'] . ''.$razaoConvert.'@flux.net.br',
            'telephone_1' => (!empty($telefone)) ? $telefone : '5155555555',
			'telephone_2' => (!empty($telefone_celular)) ? $telefone_celular : '5155555555',
            'address_1' => $customer_data['endereco'] . ', ' . $customer_data['numero'] . ' - ' . $customer_data['bairro'],
            'city' => $this->get_city_name($customer_data['cidade']),
            'province' => $this->get_uf_name($customer_data['cidade']),
            'postal_code' => $customer_data['cep'],
            'creation'          => $customer_data['data_cadastro'],
            'status'            => ($customer_data['ativo'] == 'S') ? 0 : 1,
            'deleted'           => 0,
            'deleted_date'      => '1000-01-01 00:00:00',
        ];

        if ($existing_account) {
            $this->db->where('id_external', $customer_data['id']);
            $this->db->update('accounts', $account_data);
            $this->flux_log->write_log('info', 'Account updated for external ID: ' . $customer_data['id']);
        } 
        else {
            $default_data = [
                'reseller_id'       => 0,
                'pricelist_id'      => common_model::$global_config['system_config']['default_signup_rategroup'] ?: 1,
                'country_id'        => 28,
                'currency_id'       => 16,
                'timezone_id'       => 78,
                'credit_limit'      => '100.000',
                'balance'           => '100.000',
                'maxchannels'       => 3,
                'charge_per_min'    => 0,
                'invoice_day'       => 1,
                'posttoexternal'    => 1,
                'sweep_id'          => 2,
                'type'              => 0,
                'notifications'     => 0,
                'password'          => $encoded_password,
                'pin'               => $pin_number,
                'sip_device_flag'   => 1,
                'deleted'           => 0,
                'deleted_date'      => '1000-01-01 00:00:00',
            ];
            $this->signup_lib->proxy_create_account(array_merge($account_data, $default_data));
            $this->flux_log->write_log('info', 'New account created for external ID: ' . $customer_data['id']);
        }
    }

    /**
     * Inserts or updates a SIP device and its associated DID product.
     */
    public function upsert_device($device_id) {
        $device = $this->db->get_where('voip_sippeers', ['id' => $device_id])->row();
        if (!$device) return false;

        $account = $this->db->get_where('accounts', ['id_external' => $device->cliente_id])->row();
        if (!$account) {
            $this->flux_log->write_log('warning', "Cannot upsert device. Account not found for customer ID: {$device->cliente_id}");
            return false;
        }

        $existing_device = $this->db->get_where('sip_devices', ['id_sip_external' => $device->id])->row();
        $password = !empty($device->secret) ? $device->secret : $this->common->generate_password();
        $encoded_password = $this->common->encode($password);

		$pin_generate = common_model::$global_config['system_config']['generate_pin'];
		if ($pin_generate == 0 ) {
			$pin = (common_model::$global_config['system_config']['pinlength'] < 6) ? 6 : common_model::$global_config['system_config']['pinlength'];
			$pin_number = $this->common->find_uniq_rendno_customer($pin, 'number', 'accounts');
		}
		
		$digits=5;
		$random_password = rand(pow(10, $digits-1), pow(10, $digits)-1);
		$current_date = gmdate("Y-m-d H:i:s");
		
		$this->db->select('id');
		$this->db->from('accounts');
		$this->db->where('id_external', $device->cliente_id);
		$account_device = $this->db->get()->row();
		if ($account_device) {
		$account_id = $account_device->id;
		} else {
		$account_id = $device->cliente_id;      
		}

        if ($existing_device) {
            $this->flux_log->write_log('existing_device', json_encode($existing_device));
            $sip_profile_info = $this->signup_lib->_proxy_get_sip_profile();
            $device_data = [
                'accountid' => $account_id,
                'status' => ($device->ativo == 'S') ? 0 : 1,
                'username' => preg_replace('/[^0-9]/', '', $device->name),
				'sip_profile_id' => $sip_profile_info ['id'],
				'reseller_id'=>'0',
				'id_sip_external' => isset($device->id) ? $device->id : '0',				
				'dir_params' => json_encode(array(
					'password'=> $device->secret,
					'vm-enabled' => 'false',
					'vm-password'=> $random_password,
					'vm-mailto'=> '',
					'vm-attach-file'=>'false',
					'vm-keep-local-after-email'=>'false',
					'vm-email-all-messages'=>'false'
				)),
				'dir_vars'=>json_encode(array(
					'effective_caller_id_name' => $device->name,
					'effective_caller_id_number' => $device->callerid,
					'user_context'=>'default'
				)),
				'codec' => 'G729,PCMA,PCMU',
				'creation_date'=>$current_date,
				'last_modified_date'=>$current_date
            ];
            
            
            $this->db->where('id_sip_external', $existing_device->id_sip_external)->update('sip_devices', $device_data);
            $this->flux_log->write_log('info', "SIP device updated for external ID: {$device->id}");
            $this->flux_log->write_log('info', "SIP device name: {$device->name}");
            $this->_create_did_product_and_order($device, $account);            
        } 
        else {        
            $device_data = [
                'id_sip_external' => $device->id,
				'number' => preg_replace('/[^0-9]/', '', $device->name),
				'reseller_id' => '0',
				'pricelist_id'      => common_model::$global_config['system_config']['default_signup_rategroup'] ?: 1,
				'accountid' => $account_id,
				'country_id' => '28',
				'currency_id' => '16',
				'timezone_id' => '78',
				'credit_limit' => '100.00',
				'sweep_id' => '2',
				'posttoexternal' => '1',
				'type' => '0',
				'notifications' => '1',
				'sip_device_flag' => '1',
				'password' => $encoded_password,
				'status' => ($device->ativo == 'S') ? 0 : 1
            ];
            $this->flux_log->write_log('no_existing_device', json_encode($device_data));
            $sip_profile = $this->signup_lib->_proxy_get_sip_profile();
            if ($sip_profile) {
                $this->signup_lib->_proxy_create_sip_device($device_data, $sip_profile);
                $this->flux_log->write_log('info', "New SIP device created for external ID: {$device->id}");
                $this->_create_did_product_and_order($device, $account);
            }
        }
    }        

    private function _create_did_product_and_order($device, $account) {
        if ($this->db->get_where('dids', ['number' => $device->name])->num_rows() > 0) return;

        $product_data = [
        'name' => $device->name,
        'country_id' => 28,
        'product_category' => 4,
        'status' => 0,
        'can_purchase' => 0,
        'billing_type' => 1,
        'creation_date' => gmdate("Y-m-d H:i:s"),
        'last_modified_date' => gmdate("Y-m-d H:i:s")
        ];
        $this->db->insert("products", $product_data);
        $product_id = $this->db->insert_id();


        $account_did_id = $this->common->get_field_name('id','accounts',array('id_external' => $device->cliente_id));
        $account_city = $this->common->get_field_name('city','accounts',array('id_external' => $device->cliente_id));
        $account_province = $this->common->get_field_name('province','accounts',array('id_external' => $device->cliente_id));

        $did_data = [
        'number' => $device->name,
        'accountid' => $account_did_id,
        'country_id' => 28,
        'city' => $account_city,
        'province' => $account_province,
        'provider_id' => 3,
        'status' => 0,        
        'extensions' => $device->name,
        'product_id' => $product_id
        ];

        $this->db->insert("dids", $did_data);

        $this->order->confirm_order_proxy(['product_id' => $product_id], $account_did_id, 1);
        $this->flux_log->write_log('info', "Created DID, Product, and Order for number: {$device->name}");
    }

    public function get_city_name($city_id) {
        $city = $this->db->select('cidade')->get_where('view_cidade', ['cidade_id' => $city_id])->row();
        return $city ? $city->cidade : 'Cidade Desconhecida';
    }

    public function get_uf_name($city_id) {
        $province = $this->db->select('estado')->get_where('view_cidade', ['cidade_id' => $city_id])->row();
        return $province ? $province->estado : 'Estado Desconhecido';
    }
    
    public function save_api_log($url, $payload, $response, $type, $http_code = null ) {
        $data = [
            'url'       => $url,
            'payload'   => is_string($payload) ? $payload : json_encode($payload),
            'response'  => $response,
            'http_code' => $http_code,
            'type'      => $type,
            'status'    => ($http_code >= 200 && $http_code < 300 ) ? 0 : 1,
        ];
        $this->db->insert('api_logs', $data);
    }
    
    public function sanitize_string($string) {
    
    $string = json_decode('"' . $string . '"');

    $string = trim($string);

    $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8');

    $string = strtolower($string);

    $string = preg_replace('/[^a-z0-9]/', '', $string);

    return $string;
}
}
