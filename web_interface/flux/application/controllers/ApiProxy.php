<?php
defined ( 'BASEPATH' ) or exit ( 'No direct script access allowed' );
// ##############################################################################
// Flux Telecom - Unindo pessoas e negocios
//
// Copyright (C) 2025 Flux Telecom
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

require APPPATH . '/libraries/API_Controller.php';
class ApiProxy extends API_Controller {

//class ApiProxy extends MX_Controller {
    public static $global_config;
    public $Error_flag = "true";
    
    public $CurrentDate = "";
    public $StartDate = "";
    public $EndDate = "";
    public $fp = "";
    
    
    public $api_url = "";
    public $api_endpoint = "";
    public $api_provider = "";
    public $api_token = "";
    protected $postdata = "";

    function __construct() {
		parent::__construct ();
    $this->load->model('common_model');
    $this->load->library ('common');
    
    $this->load->model ( "db_model" );
    $this->load->library ( "flux/common" );
    $this->load->library("flux_log");
    
    $rawinfo = $this->post ();
    $this->postdata = array();
    
    foreach ( $rawinfo as $key => $value ) {
    	$this->postdata [$key] = $this->_xss_clean ( $value, TRUE );
    }    		
    
    $this->CurrentDate = gmdate("Y-m-d H:i:s");
    $this->custom_current_date = gmdate("Y-m-d 23:59:59");
    $this->api_log->write_log ( 'API URL : ',base_url()."".$_SERVER['REQUEST_URI']);
    $this->api_log->write_log ( 'Params : ', json_encode($this->postdata) );
	}
			
    public function forward_get()
    {
        
        $headers = $this->input->request_headers();
        
        $api_url = $_SERVER['HTTP_REDIRECT_URL'];
        $api_endpoint = $_SERVER['HTTP_API_ENDPOINT'];
        $api_provider = $_SERVER['HTTP_API_PROVIDER'];
        
        
        
        // URL do servidor de destino
        if ($api_provider == "ixc") {
        $api_url = "https://".$api_url."/webservice/v1/".$api_endpoint."";
        }
        else {
        $api_url = $_SERVER['HTTP_REDIRECT_URL'];
        
        }

        // Captura os parâmetros GET da requisição original
        $query_string = $_SERVER['QUERY_STRING']; // Exemplo: ?id=123&name=teste

        // Monta a URL final incluindo os parâmetros recebidos
        $final_url = $api_url . '?' . $query_string;

        // Inicializa o cURL
        $ch = curl_init();

        // Configurações do cURL
        curl_setopt($ch, CURLOPT_URL, $final_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPGET, true);

        // Executa a requisição GET para a API de destino
        $response = curl_exec($ch);

        // Captura erros, se houver
        if (curl_errno($ch)) {
            echo json_encode(['error' => curl_error($ch)]);
            curl_close($ch);
            return;
        }

        // Fecha a conexão cURL
        curl_close($ch);

        // Retorna a resposta da API de destino ao cliente original
        header('Content-Type: application/json');
        echo $response;
    }
    
    public function forward_post1()
    {
        $headers = $this->input->request_headers();
        
        $api_url = $_SERVER['HTTP_REDIRECT_URL'];
        $api_endpoint = $_SERVER['HTTP_API_ENDPOINT'];
        $api_provider = $_SERVER['HTTP_API_PROVIDER'];
        $api_token = $_SERVER['HTTP_AUTHORIZATION'];
        $api_method = $_SERVER['REQUEST_METHOD'];
//        $this->flux_log->write_log('post_server', json_encode($_SERVER));
                        
        $providerdata = $this->db_model->getSelect("*", "api_partners", array(
        	"partner_name" => $api_provider,
        	"status" => "0",
        ));
        if ($providerdata->num_rows() > 0) {
        	$providerdata = $providerdata->result_array()[0];
        	$api_url = $providerdata['partner_url']."".$api_endpoint;  
        	$partner_get = true;      	
        }
        else {        
          $api_url = "https://".$_SERVER['HTTP_REDIRECT_URL']."";
          $partner_get = false;          
        }

        // Captura os dados enviados no corpo da requisição POST
        
        if ($api_method == 'POST') {
        $post_data = file_get_contents("php://input");
        }
        else {
        
        // Captura os parâmetros GET da requisição original
        $query_string = $_SERVER['QUERY_STRING']; // Exemplo: ?id=123&name=teste
        
        // Monta a URL final incluindo os parâmetros recebidos
        $final_url = $api_url . '?' . $query_string;
        
        
        }
        // Inicializa o cURL
        $ch = curl_init();

        if ($api_method == 'GET') {
        // Configurações do cURL
        curl_setopt($ch, CURLOPT_URL, $final_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        } 
        else {
        
        // Configurações do cURL para enviar a requisição POST
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt($ch, CURLOPT_ENCODING, "");
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 6.0; en-US; rv:1.9.2) Gecko/20100115 Firefox/3.6 (.NET CLR 3.5.30729)");
        
        }
        // Encaminha os headers originais
        $headers = [];
        foreach ($this->input->request_headers() as $key => $value) {
            $headers[] = "$key: $value";
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Executa a requisição para a API de destino
        $response = curl_exec($ch);
        $this->flux_log->write_log('post_server_response', json_encode($response));

        // Captura possíveis erros
        if (curl_errno($ch)) {
            echo json_encode(['error' => curl_error($ch)]);
            curl_close($ch);
            return;
        }

        // Fecha a conexão cURL
        curl_close($ch);

        // Atualiza ultima requisição na tabela
        if ($partner_get = true) {
        $update_login_date = "update api_partners set last_login_date = '" . $this->CurrentDate . "', partner_token = '".$api_token."' where id = " . $providerdata['id'] . "";
        $this->db->query($update_login_date);
        }
        // Retorna a resposta da API de destino ao cliente original
        header('Content-Type: application/json');
        echo $response;
    }
    
    public function forward_post()
    {
      
    $headers = $this->input->request_headers();
    
    $api_url = $_SERVER['HTTP_REDIRECT_URL'];
    $api_endpoint = $_SERVER['HTTP_API_ENDPOINT'];
    $api_provider = $_SERVER['HTTP_API_PROVIDER'];
    $api_token = $_SERVER['HTTP_AUTHORIZATION'];
    $api_method = $_SERVER['REQUEST_METHOD'];
    
     $providerdata = $this->db_model->getSelect("*", "api_partners", array(
        	"partner_name" => $api_provider,
        	"status" => "0",
        ));
     if ($providerdata->num_rows() > 0) {
        	$providerdata = $providerdata->result_array()[0];
        	$api_url = $providerdata['partner_url']."".$api_endpoint;  
        	$partner_get = true;      	
        }
     else {        
          $api_url = "https://".$_SERVER['HTTP_REDIRECT_URL']."";
          $partner_get = false;          
        }
   
   // 1. Dados para envio via POST na API

    $post_data = file_get_contents("php://input");
    // 2. Configuração e execução do cURL (requisição POST)
    $ch = curl_init(''.$api_url.''); // URL da API
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    $headers = [];
    foreach ($this->input->request_headers() as $key => $value) {
            $headers[] = "$key: $value";
        }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    
    // Executa a requisição e captura a resposta
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        die('Erro na requisição: ' . curl_error($ch));
    }
    curl_close($ch);
    
    // Verifica o status HTTP da resposta
    if ($http_code !== 200) {
        die('Erro na API. Código HTTP: ' . $http_code . ' | Resposta: ' . $response);
    }
    
    // 3. Decodificando o JSON de resposta
    $arr = json_decode($response, true);
    $data = $arr['registros'];
    $dataTotal = $arr['total'];  
    if (json_last_error() !== JSON_ERROR_NONE) {
        die('Erro ao decodificar JSON: ' . json_last_error_msg());
    }
        
    // 4. Conexão segura com o banco de dados via PDO
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=flux', 'root', 'SPsR*L4LgIThYU6Jd9Lq', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    } catch (PDOException $e) {
        die('Erro ao conectar no banco de dados: ' . $e->getMessage());
    }
    
    // 5. Inserção no banco (dinâmica, segura e automática)
    // Suporte tanto para um único registro quanto para múltiplos    
    $apiTabela = ''.$api_provider.'_'.$api_endpoint.'';
    $get_total_item = "select count(id) as count from ".$apiTabela."";
    
    $get_total_item = $this->db->query($get_total_item);
    if ($get_total_item->num_rows() > 0) {
    	$total_item = $get_total_item->result_array()[0];
    	$count_total_item = $total_item['count'];
    	if ($count_total_item > $dataTotal) {
    	$updateData = false;
    	$is_process = false;
    	} 
    	elseif ($count_total_item < $dataTotal) {
      $updateData = true;
      $is_process = true;
    	}
    	else {    		    		
    		$updateData = true;
    		$is_process = true;
    	}
    
    }
    if ($is_process = true) {
    if (isset($data[0]) && is_array($data[0])) {
        // Se for múltiplos registros (array de objetos)
        foreach ($data as $registro) {
            $this->insertOrUpdateData($pdo, $apiTabela, $registro);
        }
    } 
    else {
        // Registro único
        $this->insertOrUpdateData($pdo, $apiTabela, $data);
        
    }
    }
    if ($partner_get = true) {
    $update_login_date = "update api_partners set last_login_date = '" . $this->CurrentDate . "', partner_token = '".$api_token."' where id = " . $providerdata['id'] . "";
    $this->db->query($update_login_date);
    }
    header('Content-Type: application/json');
    echo $response;
    
    
//    echo "Dados inseridos/atualizados com sucesso!";
    }
    
    public function insertDB(PDO $pdo, string $tabela, array $dados)
    {
        $colunas = implode(", ", array_keys($dados)); // colunas da tabela
        $placeholders = ":" . implode(", :", array_keys($dados)); // placeholders PDO
    
        $sql = "INSERT INTO $tabela ($colunas) VALUES ($placeholders)";
        $stmt = $pdo->prepare($sql);
    
        // Executar a query
        $stmt->execute($dados);
    }

    public function insertOrUpdateData(PDO $pdo, string $tabela, array $dados)
    {
        // Colunas e placeholders
        $colunas = implode(", ", array_keys($dados));
        $placeholders = ":" . implode(", :", array_keys($dados));
    
        // Construindo o ON DUPLICATE KEY UPDATE dinamicamente
        $updates = [];
        foreach ($dados as $coluna => $valor) {
            $updates[] = "$coluna = VALUES($coluna)";
        }
        $update_clause = implode(", ", $updates);
    
        // SQL final
        $sql = "INSERT INTO $tabela ($colunas) VALUES ($placeholders) ON DUPLICATE KEY UPDATE $update_clause";
    
        // Executando a query preparada
        $stmt = $pdo->prepare($sql);
        $stmt->execute($dados);
    }
    
    public function update_data()
    {
   /*   
    $headers = $this->input->request_headers();
    
    $api_url = $_SERVER['HTTP_REDIRECT_URL'];
    $api_endpoint = $_SERVER['HTTP_API_ENDPOINT'];
    $api_provider = $_SERVER['HTTP_API_PROVIDER'];
    $api_token = $_SERVER['HTTP_AUTHORIZATION'];
    $api_method = $_SERVER['REQUEST_METHOD'];
    
     $providerdata = $this->db_model->getSelect("*", "api_partners", array(
          "partner_name" => $api_provider,
          "status" => "0",
        ));
     if ($providerdata->num_rows() > 0) {
          $providerdata = $providerdata->result_array()[0];
          $api_url = $providerdata['partner_url']."".$api_endpoint;  
          $partner_get = true;      	
        }
     else {        
          $api_url = "https://".$_SERVER['HTTP_REDIRECT_URL']."";
          $partner_get = false;          
        }
   
   // 1. Dados para envio via POST na API

    $post_data = file_get_contents("php://input");
    // 2. Configuração e execução do cURL (requisição POST)
    $ch = curl_init(''.$api_url.''); // URL da API
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    $headers = [];
    foreach ($this->input->request_headers() as $key => $value) {
            $headers[] = "$key: $value";
        }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    
    // Executa a requisição e captura a resposta
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        die('Erro na requisição: ' . curl_error($ch));
    }
    curl_close($ch);
    
    // Verifica o status HTTP da resposta
    if ($http_code !== 200) {
        die('Erro na API. Código HTTP: ' . $http_code . ' | Resposta: ' . $response);
    }
    
    // 3. Decodificando o JSON de resposta
    $arr = json_decode($response, true);
    $data = $arr['registros'];
    $dataTotal = $arr['total'];  
    if (json_last_error() !== JSON_ERROR_NONE) {
        die('Erro ao decodificar JSON: ' . json_last_error_msg());
    }
        
    // 4. Conexão segura com o banco de dados via PDO
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=flux', 'root', 'SPsR*L4LgIThYU6Jd9Lq', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    } catch (PDOException $e) {
        die('Erro ao conectar no banco de dados: ' . $e->getMessage());
    }
    
    // 5. Inserção no banco (dinâmica, segura e automática)
    // Suporte tanto para um único registro quanto para múltiplos    
    $apiTabela = ''.$api_provider.'_'.$api_endpoint.'';
    $get_total_item = "select count(id) as count from ".$apiTabela."";
    
    $get_total_item = $this->db->query($get_total_item);
    if ($get_total_item->num_rows() > 0) {
      $total_item = $get_total_item->result_array()[0];
      $count_total_item = $total_item['count'];
      if ($count_total_item > $dataTotal) {
      $updateData = false;
      $is_process = false;
      } 
      elseif ($count_total_item < $dataTotal) {
      $updateData = true;
      $is_process = true;
      }
      else {    		    		
        $updateData = true;
        $is_process = true;
      }
    
    }
    if ($is_process = true) {
    if (isset($data[0]) && is_array($data[0])) {
        // Se for múltiplos registros (array de objetos)
        foreach ($data as $registro) {
            $this->insertOrUpdateData($pdo, $apiTabela, $registro);
        }
    } 
    else {
        // Registro único
        $this->insertOrUpdateData($pdo, $apiTabela, $data);
        
    }
    }
    if ($partner_get = true) {
    $update_login_date = "update api_partners set last_login_date = '" . $this->CurrentDate . "', partner_token = '".$api_token."' where id = " . $providerdata['id'] . "";
    $this->db->query($update_login_date);
    }
    header('Content-Type: application/json');
    echo $response;
    
    
//    echo "Dados inseridos/atualizados com sucesso!";
*/
    }
    
    public function update_login_date($providerdata,$token)
    {
    
        $this->db->where("id",$providerdata['id']);
        $this->db->update("api_partners",array(
        "last_login_date" => $this->CurrentDate,
        "partner_token" => $token));
        $this->flux_log->write_log('update_login_date', json_encode($providerdata));
        }
        
    public function get_system_config()
    {
    		$query = $this->db->get("system");
    		$config = array();
    		$result = $query->result_array();
    		foreach ($result as $row) {
    			$config[$row['name']] = $row['value'];
    		}
    		self::$global_config['system_config'] = $config;
    	}


}
