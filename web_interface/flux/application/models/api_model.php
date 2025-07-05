<?php
// ##############################################################################
// Flux SBC - Unindo pessoas e negócios
//
// Copyright (C) 2025 Flux Telecom
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
class api_model extends CI_Model {
	
	function api_model() {
		parent::__construct ();
		$this->load->library ('common');
		$this->load->model ('db_model');
		$this->load->library ('flux/order');
		$this->load->library('flux/signup_lib');
		$this->load->database(); // Carrega o banco de dados
	}

    public function getApiBaseUrl($api_nome) {
            $this->db->select('base_url');
            $this->db->from('endpoints');
            $this->db->where('nome', $api_nome);
            $query = $this->db->get();
    
            if ($query->num_rows() > 0) {
                return $query->row()->base_url;
            }
            return null;
        }
        
    public function insert_or_update_cliente($cliente_id) {
        // Buscando os dados do cliente
        $this->db->select('*');
        $this->db->from('clientes');
        $this->db->where('id', $cliente_id);
        $cliente = $this->db->get()->row();

        if (!$cliente) {
            return false;  // Cliente não encontrado
        }

        // Verificando se o cliente já existe na tabela 'accounts_copy1'
        $this->db->select('*');
        $this->db->from('accounts');
        $this->db->where('id_external', $cliente->id);
        $existing_account = $this->db->get()->row();
//        $this->flux_log->write_log('insert_or_update_cliente', json_encode($existing_account));
	    if (empty($cliente->senha)) {
	    $password = $this->common->generate_password();
	    } else {
	    $password = $cliente->senha;	    
	    }
	    $encoded_password = $this->common->encode($password);
	    
	    $pin_generate = common_model::$global_config['system_config']['generate_pin'];
		if ($pin_generate == 0 ) {
			$pin = (common_model::$global_config['system_config']['pinlength'] < 6) ? 6 : common_model::$global_config['system_config']['pinlength'];
			$pin_number = $this->common->find_uniq_rendno_customer($pin, 'number', 'accounts');
		}
		
		$uname = $this->common->find_uniq_rendno_customer(10, 'number ', 'accounts');
	    $razaoConvert = $this->sanitize_string($cliente->razao);
	    $phoneConvert = $this->sanitize_string($cliente->telefone_celular);
        // Dados formatados
        $data = array(
//            'id' => $cliente->id,
            'id_external' => $cliente->id,
            'number' => preg_replace('/[^0-9]/', '', $cliente->cnpj_cpf),  // Remove '.', '-', '/'
            'reseller_id' => '0',
            'pricelist_id' => '1',
            'country_id' => '28',
            'currency_id' => '16',
            'timezone_id' => '78',
            'credit_limit' => '100.000',
            'balance' => '100.000',
            'maxchannels' => '3',
            'charge_per_min' => '0',
            'sweep_id' => '2',
            'posttoexternal' => '1',
            'type' => '0',
            'notifications' => '0', //flag envio email create 0=true 1=false
//            'sip_device_flag' => '1', //flag criacao device 0=true 1=false
            'invoice_day' => '1',
            'first_name' => $cliente->razao,
            'last_name' => $cliente->razao,
            'company_name' => (!empty($cliente->fantasia)) ? $cliente->fantasia : $cliente->razao,
            'password' => $encoded_password,
            'pin' => $pin_number,
            'telephone_1' => (!empty($phoneConvert)) ? $phoneConvert : '5155555555',
            'telephone_2' => (!empty($cliente->telefone_comercial)) ? $cliente->telefone_comercial : '5155555555',
            'email' => (!empty($cliente->email)) ? $cliente->email : $cliente->id . ''.$razaoConvert.'@flux.net.br',            
            'notification_email' => (!empty($cliente->email)) ? $cliente->email : $cliente->id . ''.$razaoConvert.'@flux.net.br',
            'address_1' => $cliente->endereco . ', ' . $cliente->numero . ' - ' . $cliente->bairro,
            'postal_code' => $cliente->cep,
            'city' => $this->get_city_name($cliente->cidade),
            'creation' => $cliente->data_cadastro,
            'status' => ($cliente->ativo == 'S') ? 0 : 1
        );
        
        $dataUpdate = array(
//            'id' => $cliente->id,
            'id_external' => $cliente->id,
            'number' => preg_replace('/[^0-9]/', '', $cliente->cnpj_cpf),  // Remove '.', '-', '/'
            'reseller_id' => '0',
//            'pricelist_id' => '1',
            'country_id' => '28',
            'currency_id' => '16',
            'timezone_id' => '78',
//            'credit_limit' => '100.000',
//            'balance' => '100.000',
            'maxchannels' => '3',
            'charge_per_min' => '0',
            'sweep_id' => '2',
            'posttoexternal' => '1',
            'type' => '0',
            'notifications' => '0', //flag envio email create 0=true 1=false
//            'sip_device_flag' => '1', //flag criacao device 0=true 1=false
//            'invoice_day' => '1',
            'first_name' => $cliente->razao,
            'last_name' => $cliente->razao,
            'company_name' => (!empty($cliente->fantasia)) ? $cliente->fantasia : $cliente->razao,
//            'password' => $encoded_password,
            'pin' => $pin_number,
            'telephone_1' => (!empty($phoneConvert)) ? $phoneConvert : '5155555555',
            'telephone_2' => (!empty($cliente->telefone_comercial)) ? $cliente->telefone_comercial : '5155555555',
            'email' => (!empty($cliente->email)) ? $cliente->email : $cliente->id . ''.$razaoConvert.'@flux.net.br',            
            'notification_email' => (!empty($cliente->email)) ? $cliente->email : $cliente->id . ''.$razaoConvert.'@flux.net.br',
            'address_1' => $cliente->endereco . ', ' . $cliente->numero . ' - ' . $cliente->bairro,
            'postal_code' => $cliente->cep,
            'city' => $this->get_city_name($cliente->cidade),
            'creation' => $cliente->data_cadastro,
            'status' => ($cliente->ativo == 'S') ? 0 : 1
        );

        // Se o cliente já existir na tabela accounts, faz um UPDATE
        if ($existing_account) {
            $this->db->where('id_external', $cliente->id);
            $this->db->update('accounts', $dataUpdate);
            $accountinfo = $this->db_model->getSelect('*', 'accounts', array('id_external' => $cliente->id))->row_array();                        
            return $accountinfo; 
        } 
        else {
//            $this->db->insert('accounts', $data);            
//            $this->load->library('flux/signup_lib');
	        $last_id = $this->signup_lib->proxy_create_account($data);
            $accountinfo = $this->db_model->getSelect('*', 'accounts', array('id' => $last_id))->row_array();                        
            return $accountinfo;
//              return false;
        }       
    }

    public function insert_or_update_device($device_id) {
        // Buscando os dados do device
        $this->db->select('*');
        $this->db->from('voip_sippeers');
        $this->db->where('id', $device_id);
        $device = $this->db->get()->row();

        if (!$device) {
            return false;  // Device não encontrado
        }

        // Verificando se o device já existe na tabela 'sip_devices'
        $this->db->select('id');
        $this->db->from('sip_devices');
        $this->db->where('id_sip_external', $device->id);
        $existing_device = $this->db->get()->row();
	    if (empty($device->secret)) {
	    $password = $this->common->generate_password();
	    } 
	    else {
	    $password = $device->secret;	    
	    }
	    $encoded_password = $this->common->encode($password);
	    
	    $pin_generate = common_model::$global_config['system_config']['generate_pin'];
		if ($pin_generate == 0 ) {
			$pin = (common_model::$global_config['system_config']['pinlength'] < 6) ? 6 : common_model::$global_config['system_config']['pinlength'];
			$pin_number = $this->common->find_uniq_rendno_customer($pin, 'number', 'accounts');
		}
		
		$uname = $this->common->find_uniq_rendno_customer(10, 'number ', 'accounts');
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
        // Dados formatados
        $data = array(
            'id_sip_external' => $device->id,
            'number' => preg_replace('/[^0-9]/', '', $device->name),  // Remove '.', '-', '/'
            'reseller_id' => '0',
            'pricelist_id' => '1',
            'accountid' => $account_id,
            'country_id' => '28',
            'currency_id' => '16',
            'timezone_id' => '78',
            'credit_limit' => '',
            'sweep_id' => '2',
            'posttoexternal' => '1',
            'type' => '0',
            'notifications' => '1', //flag envio email create 0=true 1=false
            'sip_device_flag' => '1', //flag criacao device 0=true 1=false
            'password' => $encoded_password,
            'status' => ($device->ativo == 'S') ? 0 : 1
        );
        $this->flux_log->write_log('insert_or_update_device', json_encode($data));
//        $this->load->library('flux/signup_lib');


        // Se o device já existir na tabela sip_devices, faz um UPDATE
        if ($existing_device) {            
            $this->flux_log->write_log('existing_device_true', json_encode($device));
            $queryAccounts = $this->db->get_where('accounts', array('id_external' => $device->cliente_id));
            if($queryAccounts->num_rows() > 0) {
            $this->flux_log->write_log('queryAccounts_1', json_encode($queryAccounts));
            $queryDids = $this->db->get_where('dids', array('number' => $device->name));
            if($queryDids->num_rows() == 0){
			$insert_product_did_array = array(
				'name' => $device->name,
				'country_id' => 28,
				'product_category' => 4,
				'buy_cost' => 0,
				'price' => 0,
				'setup_fee' => 0,
				'can_resell' => 0,
				'commission' => 0,
				'billing_type' => 1,
				'billing_days' => 28,
				'free_minutes' => 0,
				'applicable_for' => 0,
				'apply_on_existing_account' => 0,
				'apply_on_rategroups' => '',
				'destination_rategroups' => '',
				'destination_countries' => '',
				'destination_calltypes' => '',
				'release_no_balance' => 0,
				'can_purchase' => 0,
				'status' => 0,
				'is_deleted' => 0,
				'created_by' => 1,
				'reseller_id' => 0,
				'creation_date' => gmdate("Y-m-d H:i:s"),
				'last_modified_date' => gmdate("Y-m-d H:i:s")
			);
			$this->db->insert("products", $insert_product_did_array);
			$product_did_id = $this->common->get_field_name('id','products',array('name' => $device->name));
			$account_did_id = $this->common->get_field_name('id','accounts',array('id_external' => $device->cliente_id));
			$account_city = $this->common->get_field_name('city','accounts',array('id_external' => $device->cliente_id));
			
			$did_add_array = array (
				'number' => $device->name,
				'accountid' => $account_did_id,
				'country_id' => '28',
				'city' => $account_city,
				'status' => '0',
				'extensions' => $device->name,
				'product_id' => $product_did_id,
			);
			$this->flux_log->write_log('queryDids_0', json_encode($did_add_array));
			$this->db->insert("dids",$did_add_array);
			$account_id =  $account_did_id;
			$created_by_accountinfo = '1';
			$productdata['product_id'] = $product_did_id;
			$confirm_oder = $this->order->confirm_order($productdata, $account_id, $created_by_accountinfo);
			
			}
            }
            $sip_profile_info = $this->signup_lib->_proxy_get_sip_profile();
            $sipdevice_array = array (
				'username' => preg_replace('/[^0-9]/', '', $device->name),
				'sip_profile_id' => $sip_profile_info ['id'],
				'reseller_id'=>'0',
				'accountid' => $account_id,
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
					'effective_caller_id_number' => $device->name,
					'user_context'=>'default'
				)),
				'codec' => 'PCMA,PCMU',
				'status' => ($device->ativo == 'S') ? 0 : 1,
				'creation_date'=>$current_date,
				'last_modified_date'=>$current_date
		);
            
            $this->db->where('id_sip_external', $device->id);            
            $this->db->update('sip_devices', $sipdevice_array);
            //return $this->db->update('sip_devices', $sipdevice_array);
            $deviceinfo = $this->db_model->getSelect('*', 'sip_devices', array('id_sip_external' => $device->id))->row_array();                        
            return $deviceinfo;
            
        } 
        else {
//            $this->db->insert('accounts', $data);            
//            $this->load->library('flux/signup_lib');
            $sip_profile_info = $this->signup_lib->_proxy_get_sip_profile();
	        if(!empty($sip_profile_info)){
	        $last_id = $this->signup_lib->_proxy_create_sip_device($data,$sip_profile_info);
	        $queryAccounts = $this->db->get_where('accounts', array('id_external' => $device->cliente_id));
            if($queryAccounts->num_rows() > 0) {
            $queryDids = $this->db->get_where('dids', array('number' => $device->name));
            if($queryDids->num_rows() == 0){
			$insert_product_did_array = array(
				'name' => $device->name,
				'country_id' => 28,
				'product_category' => 4,
				'buy_cost' => 0,
				'price' => 0,
				'setup_fee' => 0,
				'can_resell' => 0,
				'commission' => 0,
				'billing_type' => 1,
				'billing_days' => 28,
				'free_minutes' => 0,
				'applicable_for' => 0,
				'apply_on_existing_account' => 0,
				'apply_on_rategroups' => '',
				'destination_rategroups' => '',
				'destination_countries' => '',
				'destination_calltypes' => '',
				'release_no_balance' => 0,
				'can_purchase' => 0,
				'status' => 0,
				'is_deleted' => 0,
				'created_by' => 1,
				'reseller_id' => 0,
				'creation_date' => gmdate("Y-m-d H:i:s"),
				'last_modified_date' => gmdate("Y-m-d H:i:s")
			);
			$this->db->insert("products", $insert_product_did_array);
			$product_did_id = $this->common->get_field_name('id','products',array('name' => $device->name));
			$account_did_id = $this->common->get_field_name('id','accounts',array('id_external' => $device->cliente_id));
			$account_city = $this->common->get_field_name('city','accounts',array('id_external' => $device->cliente_id));
			
			$did_add_array = array (
				'number' => $device->name,
				'accountid' => $account_did_id,
				'country_id' => '28',
				'city' => $account_city,
				'status' => '0',
				'extensions' => $device->name,
				'product_id' => $product_did_id,
			);
			
			$this->db->insert("dids",$did_add_array);
			$account_id =  $account_did_id;
			$created_by_accountinfo = '1';
			$productdata['product_id'] = $product_did_id;
			$confirm_oder = $this->order->confirm_order($productdata, $account_id, $created_by_accountinfo);
			
			}
            }
	        }
            $deviceinfo = $this->db_model->getSelect('*', 'sip_devices', array('id' => $last_id))->row_array();                      
            return $deviceinfo;
        }       
    }
    
    public function get_dados_voip($cliente_id) {
    $sql = "
        SELECT
            sip_devices.username,
            JSON_UNQUOTE(JSON_EXTRACT(sip_devices.dir_params, '$.password')) AS secret,
            JSON_UNQUOTE(JSON_EXTRACT(sip_devices.dir_vars, '$.effective_caller_id_name')) AS name,
            JSON_UNQUOTE(JSON_EXTRACT(sip_devices.dir_vars, '$.effective_caller_id_number')) AS numero,
            sip_devices.accountid,
            sip_devices.id_sip_external,
            sip_devices.id AS id_sip,
            ixc_cliente_contrato.id AS id_contrato,
            ixc_cliente.id AS id_cliente
        FROM sip_devices
        JOIN ixc_cliente_contrato ON sip_devices.accountid = ixc_cliente_contrato.id_cliente
        JOIN ixc_cliente ON ixc_cliente.id = ixc_cliente_contrato.id_cliente
        WHERE ixc_cliente.id = ?
        GROUP BY sip_devices.id
    ";

    $query = $this->db->query($sql, [$cliente_id]);
    return $query->row_array();
}


    /* Funcoes Products - Planos Voip */
    /* Start */
    public function get_planos_voip() {
    $sql = "
        SELECT *
        FROM products
        WHERE product_category = 1 AND status = 0
    ";

    $query = $this->db->query($sql);
    return $query->result_array();



    
//    return $this->db->get('products')->result_array();
    }
    
    public function salvar_planos_voip_externos($planos) {
    foreach ($planos as $registro) {
        $data = [
            'id_plataforma' => $registro['id_plataforma'],
            'descricao'     => $registro['descricao']
        ];

        // Verifica se já existe e atualiza ou insere
        $this->db->where('id_plataforma', $registro['id_plataforma']);
        $query = $this->db->get('planos_voip_externos');

        if ($query->num_rows() > 0) {
            $this->db->where('id_plataforma', $registro['id_plataforma']);
            $this->db->update('planos_voip_externos', $data);
        } else {
            $this->db->insert('planos_voip_externos', $data);
        }
    }
}
    

    public function salvarPlanosVoipRemoto($dados) {
        $this->db->truncate('planos_voip_externos');
        foreach ($dados as $registro) {
            $this->db->insert('planos_voip_externos', [
                'id'            => $registro['id'], // ID da API
                'id_plataforma' => $registro['id_plataforma'],
                'descricao'     => $registro['descricao']
            ]);
        }
    }

    public function getAllProducts() {
        return $this->db->get('products')->result_array();
    }

    public function getPlanoVoipRemotoPorId($id_plataforma) {
        return $this->db->get_where('planos_voip_externos', [
            'id_plataforma' => $id_plataforma
        ])->row_array();
    }
    
    public function get_dados_product($product_id) {
    $sql = "
        SELECT
					products.id, 
					rbtelecom_planos_voip.id_plataforma,
					products.`name`, 
					rbtelecom_planos_voip.descricao,
					rbtelecom_planos_voip.id AS planos_voip_id,
					products.description, 
					products.product_category, 
					products.`status`
				FROM
					products
					INNER JOIN
					rbtelecom_planos_voip
					ON 
						products.id = rbtelecom_planos_voip.id_plataforma
        WHERE products.id = ?
        GROUP BY products.id
    ";

    $query = $this->db->query($sql, [$product_id]);
    return $query->row_array();
}

    public function getProdutosParaInsert() {
    $query = $this->db->query("
        SELECT p.id AS id_plataforma, p.name AS descricao
        FROM products p
        LEFT JOIN planos_voip_externos e ON p.id = e.id_plataforma
        WHERE e.id_plataforma IS NULL
    ");
    return $query->result_array();
}

    public function getProdutosParaUpdate() {
    $query = $this->db->query("
        SELECT p.id AS id_plataforma, p.name AS descricao
        FROM products p
        JOIN planos_voip_externos e ON p.id = e.id_plataforma
        WHERE p.name != e.descricao
    ");
    return $query->result_array();
}

    /* End */

    public function insert_or_update($endpoint,$table,$id) {
        // Buscando os dados do cliente
        $this->db->select('*');
        $this->db->from(''.$table.'');
        $this->db->where('id', $id);
        $dbdata = $this->db->get()->row();

        if (!$dbdata) {
            return false;  // Cliente não encontrado
        }
        $this->db->select('id');
        $this->db->from(''.$table.'');
        $this->db->where('id', $dbdata->id);
        $existing_data = $this->db->get()->row();
        if ($existing_data) {
            $datainfo = $this->db_model->getSelect('*', ''.$table.'', array('id' => $dbdata->id))->row_array();                        
            return $datainfo; 
        }    
    }
    
    public function buscar_dinamico($tabela, $filtros = array()) {
        // Segurança básica: valida nome da tabela
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $tabela)) {
            return ['erro' => 'Tabela inválida'];
        }

        $this->db->from($tabela);

        // Adiciona os filtros
        foreach ($filtros as $campo => $valor) {
            $this->db->where($campo, $valor);
        }

        $query = $this->db->get();

        return $query->result_array();
    }
    
    public function get_provider_data($api_provider) {
        $providerdata = $this->db_model->getSelect("*", "api_partners", [
            "partner_name" => $api_provider,
            "status" => "0",
        ]);
        return $providerdata->num_rows() > 0 ? $providerdata->result_array()[0] : null;
    }
    
    public function get_cidade_data($id) {
            $cidadedata = $this->db_model->getSelect("nome", "ixc_cidade", [
                "id" => $id,                
            ]);
            return $cidadedata->num_rows() > 0 ? $cidadedata->result_array()[0] : null;
        }

    public function get_account_data($api_account, $api_provider) {
        $accountdata = $this->db_model->getSelect("*", "view_api_partners", [
            "name" => $api_account,
            "partner_id" => $api_provider,
            "status" => "0",
        ]);
        return $accountdata->num_rows() > 0 ? $accountdata->result_array()[0] : null;
    }
    
    public function get_device_data($id_sip) {
                $devicedata = $this->db_model->getSelect("*", "sip_devices", [
                    "id_sip_external" => $id_sip,
                    "status" => "0",
                ]);
                return $devicedata->num_rows() > 0 ? $devicedata->result_array()[0] : null;
            }
            
    public function update_partner($partner_id, $api_token) {
        $update_login_date = "UPDATE api_partners SET last_login_date = '{$this->CurrentDate}', partner_token = '{$api_token}' WHERE id = {$partner_id}";
        $this->db->query($update_login_date);
    }

    public function get_city_name($city_id) {
        $this->db->select('nome');
        $this->db->from('cidade');
        $this->db->where('id', $city_id);
        $city = $this->db->get()->row();

        return $city ? $city->nome : 'Cidade Desconhecida';
    }
    
    public function sanitize_string_old($string) {
    $this->flux_log->write_log('sanitize_string', json_encode($string));
    
    // Remove espaços do início e fim
    $string = trim($string);

    // Converte para minúsculas
    $string = strtolower($string);

    // Remove acentos (opcional, exige normalização)
    $string = iconv('UTF-8', 'ASCII//TRANSLIT', $string);

    // Remove caracteres especiais (mantém letras e números)
    $string = preg_replace('/[^a-z0-9]/', '', $string);

    return $string;
}

    public function sanitize_string($string) {
    
//    $this->flux_log->write_log('sanitize_string', json_encode($string));
    
    // Converte unicode JSON para UTF-8 real (ex: \u00e7 → ç)
    $string = json_decode('"' . $string . '"');

    // Remove espaços do início e fim
    $string = trim($string);

    // Converte para UTF-8 válido
    $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8');

    // Converte para minúsculas
    $string = strtolower($string);

    // Remove acentos
    //$string = @iconv('UTF-8', 'ASCII//TRANSLIT', $string);

    // Remove caracteres especiais (mantém letras e números)
    $string = preg_replace('/[^a-z0-9]/', '', $string);

    return $string;
}

    
    public function salvar_log_api($url, $payload, $response, $type, $http_code = null) {
    $data = [
        'url' => $url,
        'payload' => json_encode($payload),
        'response' => $response,
        'http_code' => $http_code,
        'type' => $type,
    ];
    $this->db->insert('api_logs', $data);
}
}
