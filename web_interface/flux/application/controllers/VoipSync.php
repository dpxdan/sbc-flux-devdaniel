<?php

// ##############################################################################
// Flux Telecom - Unindo pessoas e negócios
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

class VoipSync extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Voip_model');
        $this->load->model('api_model');
        $this->load->library('curl');
        $this->load->library ('common');
        $this->load->library('flux_log');
    }

    public function sync() {
        $this->flux_log->write_log('success', 'Start Sync.');
        $voip_accounts = $this->get_api_endpoints();        
        if ($voip_accounts || isset($voip_accounts['endpoint_url'])){
        foreach ($voip_accounts as $voip_account) {
        $account_name = $voip_account['endpoint_name'];
        $account_url = $voip_account['endpoint_url'];
        $account_user = $voip_account['endpoint_user'];
        $account_password = $voip_account['endpoint_password'];
        $account_auth = $voip_account['endpoint_auth'];
        $accountAuth = $account_user.':'.$account_password;
        $this->flux_log->write_log($account_name, json_encode($accountAuth));
        
        
        $voip_response = $this->request_voip_sippeers($account_url,$accountAuth);
        
        if (!$voip_response || !isset($voip_response['registros'])) {
            $this->flux_log->write_log('error', 'Erro na resposta da API view_voip_sippeers_cliente');
            return;
        }
		$ids_na_api = [];
		if (isset($voip_response['registros']) && is_array($voip_response['registros'])) {
			foreach ($voip_response['registros'] as $registro) {
				if (isset($registro['id'])) {
					$ids_na_api[] = $registro['id'];
				}
			}
		}
		$ids_nao_enviados = $this->Voip_model->get_ids($ids_na_api,'id','voip_sippeers');
		foreach ($ids_nao_enviados as $ids) {
		$this->common->delete_data ( 'sip_devices', array (
					'id_sip_external' => $ids['id']
			) );
		$this->common->delete_data ( 'voip_sippeers', array (
					'id' => $ids['id']
			) );
		
		}
        $cliente_ids = array();
        foreach ($voip_response['registros'] as $registro) {
            $this->Voip_model->salvar_voip_sippeers($registro);

            if (isset($registro['cliente_id'])) {
                $cliente_ids[] = $registro['cliente_id'];
            }            
            
        }
        
        $cliente_ids = array_unique($cliente_ids);

        foreach ($cliente_ids as $cliente_id) {
            $cliente_response = $this->request_cliente($account_url,$accountAuth,$cliente_id);
            if ($cliente_response && isset($cliente_response['registros'])) {
                foreach ($cliente_response['registros'] as $cliente) {
                    $this->Voip_model->salvar_cliente($cliente);
                }
            $cliente_contrato_response = $this->request_cliente_contrato($account_url,$accountAuth,$cliente_id);
            if ($cliente_contrato_response && isset($cliente_contrato_response['registros'])) {
                foreach ($cliente_contrato_response['registros'] as $cliente_contrato) {
                    $this->Voip_model->salvar_cliente_contrato($cliente_contrato);
                }
            }
            }
        }
        
		$plano_response = $this->request_voip_devices($account_url,$accountAuth);
	
		if ($plano_response && isset($plano_response['registros'])) {
			foreach ($plano_response['registros'] as $registro) {
				if (isset($registro['id']) && isset($registro['id_plano_sip'])) {
					$this->db->where('id', (int) $registro['id']);
					$this->db->update('voip_sippeers', [
						'id_plano_sip' => (int) $registro['id_plano_sip']
					]);
				}
			}
		} 
		else {
			$this->flux_log->write_log('error', 'Erro ao sincronizar id_plano_sip da tabela voip_sippeers.');
		}
		}
		} 
		else {
		$this->flux_log->write_log('sync', 'Sem dados get_api_endpoints');
		return;
		}
    }

    //Sync Cidade
    public function sincronizar_cidade() {
        $voip_accounts = $this->get_api_endpoints();        
        if ($voip_accounts || isset($voip_accounts['endpoint_url'])){
        foreach ($voip_accounts as $voip_account) {
        $account_name = $voip_account['endpoint_name'];
        $account_url = $voip_account['endpoint_url'];
        $account_user = $voip_account['endpoint_user'];
        $account_password = $voip_account['endpoint_password'];
        $account_auth = $voip_account['endpoint_auth'];
        $accountAuth = $account_user.':'.$account_password;
        
        
        $cidade_response = $this->request_cidade($account_url,$accountAuth);
        if (!$cidade_response || !isset($cidade_response['registros'])) {
            $this->flux_log->write_log('error', 'Erro na resposta da API cidade');
            return;
        }
        foreach ($cidade_response['registros'] as $cidade) {
            $this->Voip_model->salvar_cidade($cidade);
        }

        $uf_response = $this->request_uf($account_url,$accountAuth);
        if (!$uf_response || !isset($uf_response['registros'])) {
            $this->flux_log->write_log('error', 'Erro na resposta da API UF');
            return;
        }
        foreach ($uf_response['registros'] as $uf) {
            $this->Voip_model->salvar_uf($uf);
        }  
        }
        }
    }

    //Sync Planos    
    public function enviar_planos_voip() {
    $this->load->database();

    $produtos = $this->db
        ->select('id, name')
        ->from('products')
        ->where('product_category', 1)
        ->where('status', 0)
        ->get()
        ->result_array();

    foreach ($produtos as $produto) {
        $body = array(
            'id_plataforma' => $produto['id'],
            'descricao'     => $produto['name']
        );
                
        $voip_accounts = $this->get_api_endpoints();
        if ($voip_accounts || isset($voip_accounts['endpoint_url'])){
        foreach ($voip_accounts as $voip_account) {
		$account_name = $voip_account['endpoint_name'];
		$account_url = $voip_account['endpoint_url'];
		$account_user = $voip_account['endpoint_user'];
		$account_password = $voip_account['endpoint_password'];
		$account_auth = $voip_account['endpoint_auth'];
		$accountAuth = $account_user.':'.$account_password;
		$url = $account_url.'planos_voip';
		$response = $this->send_post_request($url,$accountAuth, $body, '');
        $this->flux_log->write_log('info', 'Plano VOIP enviado: ' . json_encode($body) . ' | Resposta: ' . json_encode($response));
		}        
    }
}
}

    public function sincronizar_planos_voip() {
    $this->load->database();
    $produtos = $this->db
        ->select('id, name')
        ->from('products')
        ->where('product_category', 1)
        ->where('status', 0)
        ->get()
        ->result_array();

    $postData = array(
        'qtype' => 'planos_voip.id_plataforma',
        'query' => '0',
        'oper'  => '>',
        'page'  => '1',
        'rp'    => '1000',
        'sortname' => 'planos_voip.id',
        'sortorder' => 'asc'
    );
    $voip_accounts = $this->get_api_endpoints(); 
	if ($voip_accounts || isset($voip_accounts['endpoint_url'])){
	foreach ($voip_accounts as $voip_account) {
	$account_name = $voip_account['endpoint_name'];
	$account_url = $voip_account['endpoint_url'];
	$account_user = $voip_account['endpoint_user'];
	$account_password = $voip_account['endpoint_password'];
	$account_auth = $voip_account['endpoint_auth'];
	$accountAuth = $account_user.':'.$account_password;
	$url = $account_url.'planos_voip';

    $resposta_api = $this->send_post_request($url,$accountAuth, $postData, 'listar');
    $planos_api = isset($resposta_api['registros']) ? $resposta_api['registros'] : array();

    $ids_existentes = array();
    foreach ($planos_api as $registro) {
        if (isset($registro['id_plataforma'])) {
            $ids_existentes[] = $registro['id_plataforma'];
        }
    }

    foreach ($produtos as $produto) {
        if (!in_array($produto['id'], $ids_existentes)) {
            $body = array(
                'id_plataforma' => $produto['id'],
                'descricao'     => $produto['name']
            );

            $res = $this->send_post_request($url,$accountAuth, $body, '');

            $this->flux_log->write_log('info', 'Plano VOIP inserido via sincronização: ' . json_encode($body) . ' | Resposta: ' . json_encode($res));
        }
    }
    }
}
}

    //Sync CDRs    
    public function sincronizar_cdr() {
        $voip_accounts = $this->get_api_endpoints();        
        if ($voip_accounts || isset($voip_accounts['endpoint_url'])){
        foreach ($voip_accounts as $voip_account) {
        $account_name = $voip_account['endpoint_name'];
        $account_url = $voip_account['endpoint_url'];
        $account_user = $voip_account['endpoint_user'];
        $account_password = $voip_account['endpoint_password'];
        $account_auth = $voip_account['endpoint_auth'];
        $accountAuth = $account_user.':'.$account_password;        
        }
        
        $cdrs_response = $this->request_cdr($account_url,$accountAuth);
        $cdrs_total = $cdrs_response['total'];
        
        if ($cdrs_total == '0') {
        $cdr_insert = $this->sincronizar_ausentes();
        $this->flux_log->write_log('cdrs_total0', json_encode($cdrs_total)); 
        return;       
        }
        
        if (!$cdrs_response || !isset($cdrs_response['registros'])) {
            $this->flux_log->write_log('error', json_encode($cdrs_response));
            return;
        }
        foreach ($cdrs_response['registros'] as $cdrs) {
            $this->Voip_model->salvar_cdrs($cdrs);
        }    
    }
    }
   
    public function enviar_cdr() {
         $voip_accounts = $this->get_api_endpoints();        
        if ($voip_accounts || isset($voip_accounts['endpoint_url'])){
        foreach ($voip_accounts as $voip_account) {
        $account_name = $voip_account['endpoint_name'];
        $account_url = $voip_account['endpoint_url'];
        $account_user = $voip_account['endpoint_user'];
        $account_password = $voip_account['endpoint_password'];
        $account_auth = $voip_account['endpoint_auth'];
        $accountAuth = $account_user.':'.$account_password;
        $url = $account_url.'cdr';       
        }        
        $cdrs = $this->Voip_model->get_cdrs_nao_enviados();

        foreach ($cdrs as $cdr) {
            $payload = array(
                'accountcode'   => $cdr['accountcode'],
                'billsec'       => $cdr['billsec'],
                'calldate'      => $cdr['calldate'],
                'clid'          => $cdr['clid'],
                'custo'         => $cdr['custo'],
                'destino'       => $cdr['destino'],
                'did'           => $cdr['did'],
                'disposition'   => $cdr['disposition'],
                'duration'      => $cdr['duration'],
                'id_ligacao'    => $cdr['id_ligacao'],
                'id_sip'        => $cdr['id_sip'],
                'id_tarifa'     => $cdr['id_tarifa'],
                'importado'     => $cdr['importado'],
                'ramal'         => $cdr['ramal'],
                'tarifado'      => $cdr['tarifado'],
                'tp_chamada'    => $cdr['tp_chamada'],
                'uniqueid'      => $cdr['uniqueid'],
                'dst'           => $cdr['dst'],
                'src'           => $cdr['src'],
                'dest_pais'     => $cdr['dest_pais']
            );

            $json_payload = json_encode($payload);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode($accountAuth)
            ));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json_payload);

			$response = curl_exec($ch);
			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);
			
			if ($httpCode == 200 || $httpCode == 201) {
				$responseData = json_decode($response, true);
			
				if (isset($responseData['id'])) {
					$ixc_id = $responseData['id'];
					$this->Voip_model->marcar_como_enviado_com_id($cdr['uniqueid'], $ixc_id);
				} 
				else {
					//echo "Resposta sem ID para UniqueID {$cdr['uniqueid']}<br>";
				}
			} 
			else {
				echo "Erro no envio: " . $cdr['uniqueid'] . "<br>";
				echo "Resposta da API: " . $response . "<br>";
			}
        }
        }
    }
    
    public function sincronizar_ausentes() {
    $this->flux_log->write_log('success', 'sincronizar_ausentes.');
    
    $voip_accounts = $this->get_api_endpoints();        
        if ($voip_accounts || isset($voip_accounts['endpoint_url'])){
        foreach ($voip_accounts as $voip_account) {
        $account_name = $voip_account['endpoint_name'];
        $account_url = $voip_account['endpoint_url'];
        $account_user = $voip_account['endpoint_user'];
        $account_password = $voip_account['endpoint_password'];
        $account_auth = $voip_account['endpoint_auth'];
        $accountAuth = $account_user.':'.$account_password;
        $url = $account_url.'cdr';    
        }
        
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'ixcsoft: listar',
        'Authorization: Basic ' . base64_encode($accountAuth)
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'qtype' => 'cdr.id_ligacao',
        'query' => '0',
        'oper' => '>',
        'page' => '1',
        'rp' => '10000',
        'sortname' => 'cdr.id_ligacao',
        'sortorder' => 'desc'
    ]));

    $response = curl_exec($ch);
    curl_close($ch);
    $dados_api = json_decode($response, true);
    
    $ids_ligacao_na_api = [];
    $uniqueids_na_api = [];
    
    if (isset($dados_api['total']) && $dados_api['total'] == '0') {
    $cdr_insert = $this->enviar_cdr();
    
    }
    if (isset($dados_api['registros']) && is_array($dados_api['registros'])) {
        foreach ($dados_api['registros'] as $registro) {
            if (isset($registro['id_ligacao'])) {
                $ids_ligacao_na_api[] = $registro['id_ligacao'];
            }
            if (isset($registro['uniqueid'])) {
                $uniqueids_na_api[] = $registro['uniqueid'];
        }
    }
    }

    $cdrs_nao_enviados = $this->Voip_model->get_cdrs_nao_enviados_por_idligacao($ids_ligacao_na_api);

    foreach ($cdrs_nao_enviados as $cdr) {
        $payload = array(
            'accountcode'   => $cdr['accountcode'],
            'billsec'       => $cdr['billsec'],
            'calldate'      => $this->converter_para_brasilia($cdr['calldate']),
            'clid'          => $cdr['clid'],
            'custo'         => $cdr['custo'],
            'valor_user'    => $cdr['valor_user'],
            'destino'       => $cdr['destino'],
            'did'           => $cdr['did'],
            'disposition'   => $cdr['disposition'],
            'duration'      => $cdr['duration'],
            'id_ligacao'    => $cdr['id_ligacao'],
            'id_sip'        => $cdr['id_sip'],
            'id_tarifa'     => $cdr['id_tarifa'],
            'importado'     => $cdr['importado'],
            'ramal'         => $cdr['ramal'],
            'tarifado'      => $cdr['tarifado'],
            'tp_chamada'    => $cdr['tp_chamada'],
            'uniqueid'      => $cdr['uniqueid'],
            'dst'           => $cdr['dst'],
            'src'           => $cdr['src'],
            'dest_pais'     => $cdr['dest_pais']
        );

        $json_payload = json_encode($payload);
        $this->flux_log->write_log('success', json_encode($payload));

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'ixcsoft: ',
            'Authorization: Basic ' . base64_encode($accountAuth)
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_payload);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode == 200 || $httpCode == 201) {
            $resposta = json_decode($response, true);
            $ixc_id = isset($resposta['id']) ? $resposta['id'] : null;
            $this->Voip_model->marcar_como_enviado_com_id($cdr['uniqueid'], $ixc_id);
    if ($ixc_id) {
        $put_payload = $payload;
        $put_payload['id'] = $ixc_id;
        
        $urlCurl = $url.'/'.$ixc_id.'';

        $ch_put = curl_init($urlCurl);
        curl_setopt($ch_put, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch_put, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'ixcsoft: ',
            'Authorization: Basic ' . base64_encode($accountAuth)
        ));        
        curl_setopt($ch_put, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch_put, CURLOPT_POST, true);
        curl_setopt($ch_put, CURLOPT_POSTFIELDS, json_encode($put_payload));

        $response_put = curl_exec($ch_put);
        $httpCodePut = curl_getinfo($ch_put, CURLINFO_HTTP_CODE);
        curl_close($ch_put);

        if ($httpCodePut == 200 || $httpCodePut == 201) {
            $this->flux_log->write_log('success', "PUT realizado com sucesso no IXC ID {$ixc_id}: " . json_encode($put_payload));
            $this->flux_log->write_log('success', "PUT Response {$ixc_id}: " . json_encode($response_put));
        } else {
            $this->api_model->salvar_log_api(
                $url,
                json_encode($put_payload),
                $response_put,
                'update_cdr',
                $httpCodePut
            );
        } 
    }
        } 
        else {
            $this->api_model->salvar_log_api($url, $json_payload, $response, 'insert_cdr', $httpCode);
            // echo "✗ Falha: {$cdr['uniqueid']}<br>Resposta: {$response}<br>";
        }
    }
    }
}

	public function sincronizar_cdrs() {
			$this->flux_log->write_log('success', 'Iniciando sincronizar_ausentes');
	        $voip_accounts = $this->get_api_endpoints();        
			if ($voip_accounts || isset($voip_accounts['endpoint_url'])){
			foreach ($voip_accounts as $voip_account) {
			$account_name = $voip_account['endpoint_name'];
			$account_url = $voip_account['endpoint_url'];
			$account_user = $voip_account['endpoint_user'];
			$account_password = $voip_account['endpoint_password'];
			$account_auth = $voip_account['endpoint_auth'];
			$accountAuth = $account_user.':'.$account_password;
			$url = $account_url.'cdr';
			$this->flux_log->write_log('success', 'URL sincronizar_ausentes:'.$url);   
			}		
			$ids_ligacao_na_api = [];
			$page = 1;
	
			do {
				$postfields = [
					'qtype' => 'cdr.id',
					'query' => '0',
					'oper' => '>',
					'page' => $page,
					'rp' => '10000',
					'sortname' => 'cdr.calldate',
					'sortorder' => 'desc'
				];
	
				$ch = curl_init($url);
				curl_setopt_array($ch, [
					CURLOPT_HTTPHEADER => [
						'Content-Type: application/json',
						'ixcsoft: listar',
						'Authorization: Basic ' . base64_encode($accountAuth)
					],
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_POST => true,
					CURLOPT_POSTFIELDS => json_encode($postfields)
				]);
	
				$response = curl_exec($ch);
//				$this->flux_log->write_log('response_getcdrs', $response);
				curl_close($ch);
	
				$dados_api = json_decode($response, true);
				if (!empty($dados_api['registros'])) {
					foreach ($dados_api['registros'] as $registro) {
						if (!empty($registro['id_ligacao'])) {
							$ids_ligacao_na_api[] = $registro['id_ligacao'];
						}
					}
				}
				$page++;
			} while (!empty($dados_api['registros']) && count($dados_api['registros']) == 10000);
	
			$cdrs_nao_enviados = $this->Voip_model->get_cdrs_idligacao($ids_ligacao_na_api);
	
			foreach ($cdrs_nao_enviados as $cdr) {
				if (empty($cdr['id_ligacao'])) {
					$this->flux_log->write_log('error', 'CDR sem id_ligacao: ' . json_encode($cdr));
					continue;
				}
	
				$payload = [
					'accountcode'   => $cdr['accountcode'],
					'billsec'       => $cdr['billsec'],
					'calldate'      => $this->converter_para_brasilia($cdr['calldate']),
					'clid'          => $cdr['clid'],
					'custo'         => $cdr['custo'],
					'valor_user'    => $cdr['valor_user'],
					'destino'       => $cdr['destino'],
					'did'           => $cdr['did'],
					'disposition'   => $cdr['disposition'],
					'duration'      => $cdr['duration'],
					'id_ligacao'    => $cdr['id_ligacao'],
					'id_sip'        => $cdr['id_sip'],
					'id_tarifa'     => $cdr['id_tarifa'],
					'importado'     => $cdr['importado'],
					'ramal'         => $cdr['ramal'],
					'tarifado'      => $cdr['tarifado'],
					'tp_chamada'    => $cdr['tp_chamada'],
					'dst'           => $cdr['dst'],
					'src'           => $cdr['src'],
					'dest_pais'     => $cdr['dest_pais']
				];
	
				$json_payload = json_encode($payload);
//				$this->flux_log->write_log('success', $json_payload);
	
				$ch = curl_init($url);
				curl_setopt_array($ch, [
					CURLOPT_HTTPHEADER => [
						'Content-Type: application/json',
						'ixcsoft: ',
						'Authorization: Basic ' . base64_encode($accountAuth)
					],
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_POST => true,
					CURLOPT_POSTFIELDS => $json_payload
				]);
	
				$response = curl_exec($ch);
				$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				//$this->flux_log->write_log('response_cdr_insert', $response);
				$this->api_model->salvar_log_api($url, $payload, $response, 'insert', $httpCode);
				curl_close($ch);
	
				if (in_array($httpCode, [200, 201])) {
					$resposta = json_decode($response, true);
					$ixc_id = $resposta['id'] ?? null;
					if ($ixc_id) {
						$this->Voip_model->marcar_como_enviado_com_id_ligacao($cdr['id_ligacao'], $ixc_id);
	
						$put_payload = $payload;
						$put_payload['id'] = $ixc_id;
						$url_put = $url . '/' . $ixc_id;
						
						$ch = curl_init($url_put);
						curl_setopt_array($ch, [
							CURLOPT_CUSTOMREQUEST => 'PUT',
							CURLOPT_HTTPHEADER => [
								'Content-Type: application/json',
								'ixcsoft: ',
								'Authorization: Basic ' . base64_encode($accountAuth)
							],
							CURLOPT_RETURNTRANSFER => true,
							CURLOPT_POSTFIELDS => json_encode($put_payload)
						]);
	
						$response_put = curl_exec($ch);
						$httpCodePut = curl_getinfo($ch, CURLINFO_HTTP_CODE);
						$this->api_model->salvar_log_api($url_put, $put_payload, $response_put, 'update', $httpCodePut);
						curl_close($ch);
	
						if (in_array($httpCodePut, [200, 201])) {
							$this->flux_log->write_log('success', "PUT realizado com sucesso no IXC ID {$ixc_id}: " . json_encode($put_payload));
						} 
						else {
							$this->api_model->salvar_log_api($url_put, json_encode($put_payload), $response_put, 'update_cdr', $httpCodePut);
						}
					}
				} 
				else {
					$this->api_model->salvar_log_api($url, $json_payload, $response, 'insert_cdr', $httpCode);
				}
			}
			}
			$this->flux_log->write_log('success', 'Fim sincronizar_ausentes');
		}

    // Requests Func
    
    private function request_voip_sippeers($accountUrl,$accountAuth) {
        $this->flux_log->write_log('success', 'request_voip_sippeers');
        $postData = array(
            'qtype' => 'view_voip_sippeers_cliente.name',
            'query' => '0',
            'oper' => '>',
            'page' => '1',
            'rp' => '2000',
            'sortname' => 'view_voip_sippeers_cliente.id',
            'sortorder' => 'desc'
        );

        $url = $accountUrl.'view_voip_sippeers_cliente';
        return $this->send_post_request($url,$accountAuth, $postData, 'listar');
    }

    private function request_voip_devices($accountUrl,$accountAuth) {
        $this->flux_log->write_log('success', 'request_voip_devices');
        $postData = array(
            'qtype' => 'voip_sippeers.id',
            'query' => '0',
            'oper' => '>',
            'page' => '1',
            'rp' => '20000',
            'sortname' => 'voip_sippeers.id',
            'sortorder' => 'asc'
        );

        $url = $accountUrl.'voip_sippeers';
        return $this->send_post_request($url,$accountAuth, $postData, 'listar');        
    }
    
    private function request_cliente($account_url,$accountAuth,$id) {
        $postData = array(
            'qtype' => 'cliente.id',
            'query' => $id,
            'oper' => '=',
            'page' => '1',
            'rp' => '1',
            'sortname' => 'cliente.id',
            'sortorder' => 'asc'
        );

        $url = $account_url.'cliente';
        return $this->send_post_request($url,$accountAuth, $postData, 'listar');
    }
    
    private function request_cliente_contrato($account_url,$accountAuth,$id_cliente) {
        $postData = array(
            'qtype' => 'cliente_contrato.id_cliente',
            'query' => $id_cliente,
            'oper' => '=',
            'page' => '1',
            'rp' => '1000',
            'sortname' => 'cliente_contrato.id',
            'sortorder' => 'asc'
        );

        $url = $account_url.'cliente_contrato';
        return $this->send_post_request($url,$accountAuth, $postData, 'listar');        
    }

	private function request_cidade($accountUrl,$accountAuth) {
	$postData = array(
		'qtype' => 'cidade.id',
		'query' => '0',
		'oper' => '>',
		'page' => '1',
		'rp' => '10000',
		'sortname' => 'cidade.id',
		'sortorder' => 'asc'
	);

	$url = $accountUrl.'cidade';
	return $this->send_post_request($url,$accountAuth, $postData, 'listar');
}

    private function request_uf($accountUrl,$accountAuth) {
        $postData = array(
            'qtype' => 'uf.id',
            'query' => '0',
            'oper' => '>',
            'page' => '1',
            'rp' => '10000',
            'sortname' => 'uf.id',
            'sortorder' => 'asc'
        );

        $url = $accountUrl.'uf';
        return $this->send_post_request($url,$accountAuth, $postData, 'listar');
    }

    private function request_cdr($accountUrl,$accountAuth) {
        $postData = array(
            'qtype' => 'cdr.id',
            'query' => '0',
            'oper' => '>',
            'page' => '1',
            'rp' => '10000',
            'sortname' => 'cdr.id',
            'sortorder' => 'asc'
        );

        $url = $accountUrl.'cdr';
        return $this->send_post_request($url,$accountAuth, $postData, 'listar');
    }
    
    // Misc
    private function send_post_request($url, $auth, $postData, $acao = 'listar') {

    $accountAuth = $url.' - '.$auth;
    $this->curl->create($url);

    $headers = array(
        'Content-Type: application/json',
        'ixcsoft: ' . $acao,
        'Authorization: Basic ' . base64_encode(''.$auth.'')
    );

    $this->curl->option(CURLOPT_HTTPHEADER, $headers);
    $this->curl->post(json_encode($postData));
    $response = $this->curl->execute();
    $http_code = $this->curl->info['http_code'];
     
    if ($http_code >= 400 || $http_code == 401) {
        $this->load->model('api_model');
        $this->api_model->salvar_log_api($url, $postData, $response, $acao, $http_code);
    }
//    $this->api_model->salvar_log_api($url, $postData, $response, $acao, $http_code);
//    $this->flux_log->write_log($url, json_encode($response));
    return json_decode($response, true);
}

    public function get_api_accounts() {
        $accounts = $this->Voip_model->get_api_data();
        return $accounts;
    }
    public function get_api_endpoints() {
        $accounts = $this->Voip_model->get_api_endpoints();
        return $accounts;
    }
    private function converter_para_brasilia($data_utc) {
    $date = new DateTime($data_utc, new DateTimeZone('UTC'));
    $date->setTimezone(new DateTimeZone('America/Sao_Paulo'));
    return $date->format('Y-m-d H:i:s');
}


}
