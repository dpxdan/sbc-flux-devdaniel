<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Voip_model extends CI_Model {
	function Voip_model() {
		parent::__construct ();
		$this->load->library ('common');
		$this->load->model ('db_model');
		$this->load->model ('api_model');
		$this->load->library ('flux/order');
		$this->load->library('flux/signup_lib');
		$this->load->library('flux_log');
	}

    public function salvar_voip_sippeers($data) {
    $campos_permitidos = array(
        'id',
        'cliente_id',
        'name',
        'username',
        'host',
        'context',
        'type',
        'nat',
        'qualify',
        'disallow',
        'allow',
        'dtmfmode',
        'secret',
        'callerid',
        'id_contrato',
        'ativo',
        'id_plano_sip'
    );

    
//    $this->flux_log->write_log('salvar_voip_sippeers', json_encode($data['id']));
    $device_id = $data['id'];
    $dados_filtrados = array();
    foreach ($campos_permitidos as $campo) {
        if (isset($data[$campo])) {
            $dados_filtrados[$campo] = $data[$campo];            
        }
    }
    
    $this->db->replace('voip_sippeers', $dados_filtrados);        
    $updateDevice = $this->api_model->insert_or_update_device($device_id);
//    $this->flux_log->write_log('updateDevice', json_encode($data));
//    $createDid = $this->salvar_did_devices($data);
//    $this->flux_log->write_log('updateDevice', json_encode($updateDevice));
}
    public function get_ids($lista_ids_na_api, $column = 'id',$table = 'voip_sippeers') { 
    if (empty($lista_ids_na_api)) return [];
    $this->db->where_not_in($column, $lista_ids_na_api);
    return $this->db->get($table)->result_array();
}
    public function salvar_voip_devices($data) {
    $campos_permitidos = array(
        'id',
        'id_plano_sip'
    );
    
//    $this->flux_log->write_log('salvar_voip_sippeers', json_encode($data['id']));
    $device_id = $data['id'];
    $dados_filtrados = array();
    foreach ($campos_permitidos as $campo) {
        if (isset($data[$campo])) {
            $dados_filtrados[$campo] = $data[$campo];            
        }
    }

    $this->db->replace('voip_sippeers', $dados_filtrados);        
    $updateDevice = $this->api_model->insert_or_update_device($device_id);
}
    public function salvar_did_devices($data) {
	$queryDids = $this->db->get_where('dids', array('number' => $data['name']));

	if($queryDids->num_rows() == 0){
	$insert_product_did_array = array(
		'name' => $data['name'],
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
	}

	$this->db->insert("products", $insert_product_did_array);
	$product_did_id = $this->common->get_field_name('id','products',array('name' => $data['name']));

	$did_add_array = array (
		'number' => $data['name'],
		'accountid' => $data['cliente_id'],
		'status' => '0',
		'extensions' => $data['name'],
		'product_id' => $product_did_id,
	);

	$this->db->insert("dids",$did_add_array);
	$account_id =  $data['cliente_id'];
	$created_by_accountinfo = '1';
	$productdata['product_id'] = $product_did_id;
	$confirm_oder = $this->order->confirm_order_proxy($productdata, $account_id, $created_by_accountinfo);
			
}
    public function salvar_cliente($data) {
    $campos_permitidos = array(
        'id',
        'razao',
        'cnpj_cpf',
        'email',
        'telefone_celular',
        'contato',
        'ativo',
        'tipo_pessoa',
        'endereco',
        'bairro',
        'cidade',
        'cep',
        'id_conta',
        'data_cadastro',
        'ultima_atualizacao',
        'numero'
    );

    $cliente_id = $data['id'];
    $dados_filtrados = array();
    foreach ($campos_permitidos as $campo) {
        if (isset($data[$campo])) {
            $dados_filtrados[$campo] = $data[$campo];
        }
    }

    $this->db->replace('clientes', $dados_filtrados);
    $updateClient = $this->api_model->insert_or_update_cliente($cliente_id);
//    $this->flux_log->write_log('updateClient', json_encode($updateClient));
}
    public function salvar_cliente_contrato($data) {
    $campos_permitidos = array(
        'id',
        'contrato',
        'data',
        'data_ativacao',
        'id_cliente',
        'id_vd_contrato',
        'status',
        'ultima_atualizacao'
    );
    $dados_filtrados = array();
    foreach ($campos_permitidos as $campo) {
        if (isset($data[$campo])) {
            $dados_filtrados[$campo] = $data[$campo];
        }
    }

    $this->db->replace('cliente_contrato', $dados_filtrados);
}
    public function salvar_cdrs($data) {
    $campos_permitidos = array(
        'id',
        'accountcode',
        'calldate',
        'clid',
        'custo',
        'valor_user',
        'dest_pais',
        'destino',
        'dst',
        'did',
        'disposition',
        'duration',
        'id_ligacao',
        'id_sip',
        'id_tarifa',
        'importado',
        'ramal',
        'tarifado',
        'tp_chamada',
        'src',
        'uniqueid'
    );

    $dados_filtrados = array();
    foreach ($campos_permitidos as $campo) {
        if (isset($data[$campo])) {
            $dados_filtrados[$campo] = $data[$campo];
        }
    }

    $this->db->replace('cdr', $dados_filtrados);
}
    public function salvar_cidade($data) {
    $campos_permitidos = array(
        'id',
        'codigo',
        'nome',
        'uf',
        'cod_ibge',
        'regiao'
    );

    $dados_filtrados = array();
    foreach ($campos_permitidos as $campo) {
        if (isset($data[$campo])) {
            $dados_filtrados[$campo] = $data[$campo];
        }
    }

    $this->db->replace('cidade', $dados_filtrados);
}
    public function salvar_uf($data) {
    $campos_permitidos = array(
        'id',
        'cod_uf',
        'nome',
        'sigla',
        'regiao',
        'id_pais',
        'cod_ibge'
    );

    $dados_filtrados = array();
    foreach ($campos_permitidos as $campo) {
        if (isset($data[$campo])) {
            $dados_filtrados[$campo] = $data[$campo];
        }
    }

    $this->db->replace('uf', $dados_filtrados);
}
    public function salvar_account($data) {
    $campos_permitidos = array(
        'id',
        'razao',
        'cnpj_cpf',
        'email',
        'telefone_celular',
        'contato',
        'ativo',
        'tipo_pessoa',
        'endereco',
        'bairro',
        'cidade',
        'cep',
        'id_conta',
        'data_cadastro',
        'ultima_atualizacao'
    );

    $dados_filtrados = array();
    foreach ($campos_permitidos as $campo) {
        if (isset($data[$campo])) {
            $dados_filtrados[$campo] = $data[$campo];
        }
    }

    $this->db->replace('clientes', $dados_filtrados);
}
    public function get_cdrs_nao_enviados() {
        $this->db->where('enviado_ixc', 'nao');
        return $this->db->get('cdr')->result_array();
    }
    public function get_api_data() {
        $this->db->where('status', '0');
        $this->db->where('run_cron', '0');
        return $this->db->get('api_partners')->result_array();
    }
    public function get_api_endpoints() {
        $this->db->where('status', '0');
        $this->db->where('run_cron', '0');
        return $this->db->get('api_endpoints')->result_array();
    }
    public function marcar_como_enviado($uniqueid) {
        $this->db->where('uniqueid', $uniqueid);
        $this->db->update('cdr', array('enviado_ixc' => 'sim'));
    }
    public function marcar_como_enviado_com_id($uniqueid, $ixc_id) {
    $this->db->where('uniqueid', $uniqueid);
    $this->db->update('cdr', array(
        'enviado_ixc' => 'sim',
        'ixc_id' => $ixc_id
    ));
}
	public function marcar_como_enviado_com_id_ligacao($id_ligacao, $ixc_id) {
		$this->db->where('id_ligacao', $id_ligacao);
		$this->db->update('cdr', array(
			'enviado_ixc' => 'sim',
			'ixc_id' => $ixc_id
		));
	}
    public function get_cdrs_nao_enviados_por_uniqueids($lista_uniqueids_na_api) {
    if (empty($lista_uniqueids_na_api)) return [];
    $this->db->where_not_in('uniqueid', $lista_uniqueids_na_api);
    $this->db->where('enviado_ixc', 'nao');
    $this->db->or_where('ixc_id', 'IS NULL');
    return $this->db->get('cdr')->result_array();
}
    public function get_cdrs_nao_enviados_por_idligacao($lista_idligacao_na_api) {
    if (empty($lista_idligacao_na_api)) return [];
    $where_null = "ixc_id IS NULL";
    $this->db->where_not_in('id_ligacao', $lista_idligacao_na_api);
    $this->db->where('enviado_ixc', 'nao');
    $this->db->or_where($where_null);
    return $this->db->get('cdr')->result_array();
}
	public function get_cdrs_idligacao2($ids_api = []) {
		$this->db->where('enviado_ixc', 'nao');
		if (!empty($ids_api)) {
			$this->db->where_not_in('id_ligacao', $ids_api);
		}
		return $this->db->get('cdr')->result_array();
	}
	public function get_cdrs_idligacao($lista_idligacao_na_api) {
    if (empty($lista_idligacao_na_api)) return [];

    $lista_idligacao_na_api = array_unique($lista_idligacao_na_api);

    $this->db->where('enviado_ixc', 'nao');
    $this->db->where_not_in('id_ligacao', $lista_idligacao_na_api);
    $this->db->where('id_ligacao IS NOT NULL');

    return $this->db->get('cdr')->result_array();
}

	public function get_cdrs_idligacao_tmp($lista_idligacao_na_api) {
    if (empty($lista_idligacao_na_api)) return [];

    $this->db->query("CREATE TEMPORARY TABLE tmp_ixc_ids (id_ligacao VARCHAR(255) PRIMARY KEY)");

    $chunks = array_chunk($lista_idligacao_na_api, 500);
    foreach ($chunks as $lote) {
        $values = array_map(function ($id) {
            return "('" . $this->db->escape_str($id) . "')";
        }, $lote);
        $sql = "INSERT INTO tmp_ixc_ids (id_ligacao) VALUES " . implode(',', $values);
        $this->db->query($sql);
    }

    $sql = "
        SELECT cdr.*
        FROM cdr
        LEFT JOIN tmp_ixc_ids ON cdr.id_ligacao = tmp_ixc_ids.id_ligacao
        WHERE tmp_ixc_ids.id_ligacao IS NULL
          AND cdr.enviado_ixc = 'nao'
    ";

    return $this->db->query($sql)->result_array();
}
	public function update_cdr_id_ligacao($id_ligacao, $ixc_id) {
		$this->db->where('id_ligacao', $id_ligacao);
		$this->db->update('cdr', [
			'enviado_ixc' => 'sim',
			'ixc_id' => $ixc_id
		]);
	}
}
