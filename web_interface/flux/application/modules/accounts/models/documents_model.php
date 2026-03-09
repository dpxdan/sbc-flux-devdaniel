<?php
// ##############################################################################
// Flux Telecom - Unindo pessoas e negócios
//
// Copyright (C) 2023 Flux Telecom
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
if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

class Documents_model extends CI_Model
{
	private $cfg;

	public function __construct()
	{
		parent::__construct();
		$this->load->library("flux_log");
		$this->config->load('documents', true);
		$cfg = $this->config->item('documents');
		if (empty($cfg) && defined('APPPATH')) {
			$path = APPPATH.'modules/accounts/config/documents.php';
			if (file_exists($path)) {
				@include $path;
				if (isset($config) && isset($config['documents'])) {
					$cfg = $config['documents'];
				}
			}
		}
		$this->cfg = (array) $cfg;
		$this->flux_log->write_log('Documents_model', json_encode($this->cfg));	
	}

	public function consultar($doc_number, $account_id = null)
	{
		$doc_number = preg_replace('/\D+/', '', (string) $doc_number);
		$len = strlen($doc_number);
		$doc_type = ($len === 14) ? 'CNPJ' : (($len === 11) ? 'CPF' : null);
		if ($doc_type === null) {
			return array('ok' => false, 'error' => 'Documento inválido.');
		}

		$cached = $this->get_cached($doc_number);
		if ($cached !== null) {
			return array(
				'ok' => true,
				'source' => 'mysql_cache',
				'data' => $cached,
			);
		}

		$provider = null;
		if ($doc_type === 'CPF') {
			$cpf_provider = isset($this->cfg['cpf_provider']) ? (string) $this->cfg['cpf_provider'] : 'apicpf';
			if ($cpf_provider === '' || $cpf_provider === 'null') {
				$cpf_provider = 'apicpf';
			}
			switch (strtolower($cpf_provider)) {
				case 'apicpf':
					$provider = 'apicpf';
					$api = $this->call_apicpf_cpf($doc_number);
					break;
				default:
					return array(
						'ok' => false,
						'error' => 'Provedor de CPF não configurado ou não suportado: '.$cpf_provider,
					);
			}
		} else {
			$provider = 'cnpja';
			$api = $this->call_cnpja_cnpj($doc_number);
		}

		$this->log_consulta($doc_number, $doc_type, $account_id, $provider, $api);

		if (!$api['ok']) {
			return array('ok' => false, 'error' => $api['error'], 'message' => $api['message']);
		}

		return array(
			'ok' => true,
			'source' => 'provider',
			'data' => $api['data'],
		);
	}

	public function get_cached($doc_number)
	{
		$days = isset($this->cfg['mysql_cache_days']) ? (int) $this->cfg['mysql_cache_days'] : 0;
		if ($days <= 0) {
			return null;
		}

		$doc_number = preg_replace('/\D+/', '', (string) $doc_number);
		$threshold = date('Y-m-d H:i:s', strtotime('-'.$days.' days'));

		$q = $this->db
			->select('response_json')
			->from('account_document_consultations')
			->where('doc_number', $doc_number)
			->where('success', 1)
			->where('created_at >=', $threshold)
			->order_by('id', 'DESC')
			->limit(1)
			->get();

		if (!$q || $q->num_rows() === 0) {
			return null;
		}
		$row = (array) $q->row_array();
		if (!isset($row['response_json']) || $row['response_json'] === '') {
			return null;
		}

		$decoded = json_decode($row['response_json'], true);
		return (json_last_error() === JSON_ERROR_NONE) ? $decoded : null;
	}

	public function get_latest_consultation($doc_number)
	{
		$doc_number = preg_replace('/\D+/', '', (string) $doc_number);
		$q = $this->db
			->select('id, doc_number, doc_type, provider, http_code, success, error_message, response_json, created_at')
			->from('account_document_consultations')
			->where('doc_number', $doc_number)
			->order_by('id', 'DESC')
			->limit(1)
			->get();

		if (!$q || $q->num_rows() === 0) {
			return null;
		}
		$row = (array) $q->row_array();
		$row['response'] = null;
		if (isset($row['response_json']) && $row['response_json'] !== '') {
			$decoded = json_decode($row['response_json'], true);
			if (json_last_error() === JSON_ERROR_NONE) {
				$row['response'] = $decoded;
			}
		}
		return $row;
	}

	public function log_consulta($doc_number, $doc_type, $account_id, $provider, array $api_result)
	{
		$provider = ($provider !== null && $provider !== '') ? (string) $provider : 'unknown';
		$insert = array(
			'account_id' => $account_id,
			'doc_number' => $doc_number,
			'doc_type' => $doc_type,
			'provider' => $provider,
			'http_code' => isset($api_result['http_code']) ? (int) $api_result['http_code'] : null,
			'success' => isset($api_result['ok']) && $api_result['ok'] ? 1 : 0,
			'error_message' => isset($api_result['error']) ? substr((string) $api_result['error'], 0, 255) : null,
			'response_json' => isset($api_result['raw']) ? $api_result['raw'] : (isset($api_result['data']) ? json_encode($api_result['data']) : null),
		);

		try {
			$this->db->insert('account_document_consultations', $insert);
		} catch (Exception $e) {
		}
	}

	private function call_apicpf_cpf($cpf)
	{
		$key = isset($this->cfg['apicpf_api_key']) ? (string) $this->cfg['apicpf_api_key'] : '049e8e09a0c266b81c20579d098fc086cc10877fd6bf8824efce8970d12f422a';
		$base = isset($this->cfg['apicpf_base_url']) ? rtrim((string) $this->cfg['apicpf_base_url'], '/') : 'https://apicpf.com';
		$path = isset($this->cfg['apicpf_path']) ? (string) $this->cfg['apicpf_path'] : '/api/consulta';
		$timeout = isset($this->cfg['apicpf_timeout']) ? (int) $this->cfg['apicpf_timeout'] : 30;

		if ($key === '' || $key === 'CHANGE_ME') {
			return array(
				'ok' => false,
				'http_code' => 0,
				'error' => 'API key do provedor de CPF (apicpf.com) não configurada. Ajuste em accounts/config/documents.php',
			);
		}

		$query_params = array('cpf' => $cpf);
		if (isset($this->cfg['apicpf_query_params']) && is_array($this->cfg['apicpf_query_params'])) {
			$query_params = array_merge($query_params, $this->cfg['apicpf_query_params']);
		}

		$url = $base.$path;
		$url .= (strpos($url, '?') === false ? '?' : '&').http_build_query($query_params);

		$ch = curl_init();
		curl_setopt_array($ch, array(
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => $timeout,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'GET',
			CURLOPT_HTTPHEADER => array(
				'Accept: application/json',
				'X-API-KEY: '.$key,
			),
		));

		$body = curl_exec($ch);
		$curl_err = curl_error($ch);
		$http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($body === false) {
			return array(
				'ok' => false,
				'http_code' => $http_code,
				'error' => 'Falha no cURL: '.$curl_err,
			);
		}

		$decoded = json_decode($body, true);
		if (json_last_error() !== JSON_ERROR_NONE) {
			return array(
				'ok' => false,
				'http_code' => $http_code,
				'raw' => $body,
				'error' => 'Resposta não é JSON válido: '.json_last_error_msg(),
			);
		}

		$api_code = isset($decoded['code']) ? (int) $decoded['code'] : null;
		$message = isset($decoded['message']) ? (string) $decoded['message'] : null;

		if ($http_code < 200 || $http_code >= 300 || ($api_code !== null && $api_code !== 200)) {
			$err = 'API retornou HTTP '.$http_code;
			$this->flux_log->write_log('message', json_encode($message));
			if ($api_code !== null && $api_code !== 200) {
				$err = 'API retornou code '.$api_code;
			}
			if ($message) {
				$err .= ' - '.$message;
			}
			return array(
				'ok' => false,
				'http_code' => $http_code,
				'raw' => $body,
				'message' => $message,
				'error' => $err,
				'data' => $decoded,
			);
		}

		return array(
			'ok' => true,
			'http_code' => $http_code,
			'raw' => $body,
			'data' => $decoded,
		);
	}

	private function call_cnpja_cnpj($cnpj)
	{
		$token = isset($this->cfg['cnpja_token']) ? (string) $this->cfg['cnpja_token'] : '4a58f61e-0473-47f4-bebe-a48488e9d5f6-e04a1827-a50f-427b-b8a9-0e66a2580814';
		$base = isset($this->cfg['cnpja_base_url']) ? rtrim((string) $this->cfg['cnpja_base_url'], '/') : 'https://api.cnpja.com';
		$path = isset($this->cfg['cnpja_cnpj_path']) ? (string) $this->cfg['cnpja_cnpj_path'] : '/office/{doc}?simples=true&registrations=BR';
		$path = str_replace('{doc}', $cnpj, $path);

		if ($token === '' || $token === 'CHANGE_ME') {
			return array(
				'ok' => false,
				'http_code' => 0,
				'error' => 'Token da API CNPJá não configurado. Ajuste em accounts/config/documents.php',
			);
		}

		$query_params = isset($this->cfg['cnpja_query_params']) && is_array($this->cfg['cnpja_query_params']) ? $this->cfg['cnpja_query_params'] : array();
		$url = $base.$path;
		if (!empty($query_params)) {
			$url .= (strpos($url, '?') === false ? '?' : '&').http_build_query($query_params);
		}
		$this->flux_log->write_log('url', json_encode($url));	

		$ch = curl_init();
		curl_setopt_array($ch, array(
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'GET',
			CURLOPT_HTTPHEADER => array(
				'Accept: application/json',
				'Authorization: '.$token,
			),
		));

		$body = curl_exec($ch);
		$curl_err = curl_error($ch);
		$http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($body === false) {
			return array(
				'ok' => false,
				'http_code' => $http_code,
				'error' => 'Falha no cURL: '.$curl_err,
			);
		}

		$decoded = json_decode($body, true);
		if (json_last_error() !== JSON_ERROR_NONE) {
			return array(
				'ok' => false,
				'http_code' => $http_code,
				'raw' => $body,
				'error' => 'Resposta não é JSON válido: '.json_last_error_msg(),
			);
		}

		if ($http_code < 200 || $http_code >= 300) {
			// API costuma retornar JSON com detalhes
			$err = 'API retornou HTTP '.$http_code;
			if (isset($decoded['message'])) {
				$err .= ' - '.$decoded['message'];
			}
			return array(
				'ok' => false,
				'http_code' => $http_code,
				'message' => $decoded['message'],
				'raw' => $body,
				'error' => $err,
				'data' => $decoded,
			);
		}

		return array(
			'ok' => true,
			'http_code' => $http_code,
			'raw' => $body,
			'data' => $decoded,
		);
	}

	public function map_result_to_account($doc_number, array $data)
	{
		$doc_number = preg_replace('/\D+/', '', (string) $doc_number);
		$out = array(
			'tax_number' => $doc_number,
		);

		$get = function ($arr, $path, $default = null) {
			$parts = explode('.', $path);
			$cur = $arr;
			foreach ($parts as $p) {
				if (!is_array($cur) || !array_key_exists($p, $cur)) {
					return $default;
				}
				$cur = $cur[$p];
			}
			return $cur;
		};

		$first_non_empty = function ($candidates) use ($get, $data) {
			foreach ($candidates as $path) {
				$v = $get($data, $path, null);
				if (is_string($v) && trim($v) !== '') {
					return trim($v);
				}
			}
			return null;
		};

		$company = $first_non_empty(array('company.name', 'name', 'razao_social', 'company.razao_social', 'data.nome', 'data.name', 'nome'));
		if ($company) {
			$out['company_name'] = $company;
			$out['rms_fantasia'] = $company;
		}

		$alias = $first_non_empty(array('alias', 'company.alias', 'nome_fantasia', 'company.nome_fantasia', 'trade_name'));
		if ($alias) {
			$out['reference'] = $alias;
		}

		$email = $first_non_empty(array('email', 'company.email', 'contacts.email'));
		if (!$email) {
			$emails = $get($data, 'emails', null);
			if (is_array($emails) && isset($emails[0])) {
				$email = is_array($emails[0]) ? ($emails[0]['address'] ?? null) : $emails[0];
			}
		}
		if ($email) {
			$out['email'] = $email;
			$out['notification_email'] = $email;
		}
		
		$registrations = $first_non_empty(array('registrations', 'company.registrations', 'contacts.registrations'));
		if (!$registrations) {
			$registrations = $get($data, 'registrations', null);
			if (is_array($registrations) && isset($registrations[0])) {
				$registrations = is_array($registrations[0]) ? ($registrations[0]['number'] ?? null) : $registrations[0];
			}
		}
		if ($registrations) {
			$out['registrations'] = $registrations;
		}

		$simei = $first_non_empty(array('company.simples', 'simples', 'contacts.simples'));
		if (!$simei) {
			$simei = $get($data, 'company.simples', null);
			$this->flux_log->write_log('simei', json_encode($simei));
			if (is_array($simei) && isset($simei[0])) {
				$simei = is_array($simei) ? ($simei['optant'] ?? null) : $simei;
				$this->flux_log->write_log('simei_optant', json_encode($simei['optant']));
			}
		}
		if ($simei['optant']) {
		    $this->flux_log->write_log('simei_optant', json_encode($simei['optant']));
			$out['simei'] = $simei['optant'];
		}

		$phone = $first_non_empty(array('phone', 'company.phone', 'contacts.phone', 'telephone'));
		if (!$phone) {
			$phones = $get($data, 'phones', null);
			if (is_array($phones) && isset($phones[0])) {
				$phone = is_array($phones[0]) ? ($phones[0]['number'] ?? null) : $phones[0];
				$areaphone = is_array($phones[0]) ? ($phones[0]['area'] ?? null) : $phones[0];
			}
		}
		if ($phone) {
			$out['telephone_1'] = $areaphone.$phone;
		}

		$addr = $get($data, 'address', null);
		if (!is_array($addr)) {
			$addr = $get($data, 'company.address', null);
		}
		if (is_array($addr)) {
			$street = $addr['street'] ?? ($addr['logradouro'] ?? ($addr['street_name'] ?? ''));
			$number = $addr['number'] ?? ($addr['numero'] ?? '');
			$details = $addr['details'] ?? ($addr['complement'] ?? ($addr['complemento'] ?? ''));
			$district = $addr['district'] ?? ($addr['bairro'] ?? '');
			$city = $addr['city'] ?? ($addr['municipality'] ?? ($addr['cidade'] ?? ''));
			$state = $addr['state'] ?? ($addr['uf'] ?? ($addr['estado'] ?? ''));
			$zip = $addr['zip'] ?? ($addr['zip_code'] ?? ($addr['cep'] ?? ''));
			
			$rms_bairro = $addr['district'] ?? ($addr['bairro'] ?? '');			
			$rms_endereco_numero = $addr['number'] ?? ($addr['numero'] ?? '');

			$line1 = trim(trim($street.' '.$number).($details ? ' - '.$details : ''));
			$line2 = trim($district);

			if ($line1 !== '') {
				$out['address_1'] = $line1;
			}
			if ($line2 !== '') {
				$out['address_2'] = $line2;
			}
			if ($zip !== '') {
				$out['postal_code'] = $zip;
			}
			if ($city !== '') {
				$out['city'] = $city;
			}
			if ($state !== '') {
				$out['province'] = $state;
			}
			if ($rms_bairro !== '') {
				$out['rms_bairro'] = $rms_bairro;
			}
			if ($rms_endereco_numero !== '') {
				$out['rms_endereco_numero'] = $rms_endereco_numero;
			}
		}
		
		return $out;
	}

	public function update_account_from_doc($account_id, array $mapped)
	{
		if (!$account_id) {
			return false;
		}
		$allowed = array(
			'tax_number',
			'company_name',
			'address_1',
			'address_2',
			'postal_code',
			'province',
			'city',
			'telephone_1',
			'email',
			'notification_email',
			'reference',
		);
		$update = array();
		foreach ($allowed as $k) {
			if (isset($mapped[$k]) && $mapped[$k] !== null && $mapped[$k] !== '') {
				$update[$k] = $mapped[$k];
			}
		}
		if (empty($update)) {
			return false;
		}

		$this->db->where('id', (int) $account_id);
		return (bool) $this->db->update('accounts', $update);
	}
}
