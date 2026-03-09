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

defined('BASEPATH') or exit('No direct script access allowed');

class Documents extends MX_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->helper('template_inheritance');
		$this->load->helper('form');
		$this->load->library('session');
		$this->load->library('flux/permission');
		$this->load->library("flux_log");
		$this->load->library('flux/form', 'documents_form');
		$this->load->library('documents_form');
		$this->load->model('documents_model');
		$this->load->model('accounts_model');
		$this->config->load('documents', true);

		if ($this->session->userdata('user_login') == FALSE) {
			redirect(base_url().'/login/login');
		}
	}

	/**
	 * Tela de consulta CPF/CNPJ
	 * URL: /accounts/documents
	 */
	public function index()
	{
		$data = array();
		$data['username'] = $this->session->userdata('user_name');
		$data['page_title'] = gettext('Consulta CPF/CNPJ');
		$data['result'] = null;
		$data['message'] = $this->session->flashdata('documents_message');
		$data['error'] = $this->session->flashdata('documents_error');

		// Pré-preencher via GET (?doc=...&account_id=...)
		$prefill = array(
			'doc' => $this->input->get('doc', true),
			'account_id' => $this->input->get('account_id', true),
			'update_account' => $this->input->get('update_account', true),
		);
        $this->flux_log->write_log('prefill', json_encode($prefill));
		if (!empty($prefill['doc'])) {
			$doc = preg_replace('/\D+/', '', (string) $prefill['doc']);
			try {
				$data['result'] = $this->documents_model->get_latest_consultation($doc);
			} catch (Exception $e) {
				$data['result'] = null;
			}
		}

		$data['form'] = $this->form->build_form($this->documents_form->get_consulta_form(), $prefill);
		$this->load->view('view_documents_details', $data);
	}

	/**
	 * Processa consulta via POST
	 */
	public function consultar()
	{
		$doc = $this->input->post('doc', true);
		$account_id = $this->input->post('account_id', true);
		$update_account = $this->input->post('update_account', true);

		$doc = preg_replace('/\D+/', '', (string) $doc);
		$account_id = ($account_id !== '' && $account_id !== null) ? (int) $account_id : null;
		$update_account = ($update_account == '1' || $update_account === 1 || $update_account === 'on') ? 1 : 0;

		if ($doc === '' || $doc === null) {
			$this->session->set_flashdata('flux_error', gettext('Informe um CPF ou CNPJ.'));
			redirect(base_url().'accounts/documents');
			return;
		}

		$len = strlen($doc);
		if ($len !== 11 && $len !== 14) {
			$this->session->set_flashdata('flux_error', gettext('Documento inválido. Envie CPF (11 dígitos) ou CNPJ (14 dígitos).'));
			redirect(base_url().'accounts/documents?doc='.$doc);
			return;
		}

		$result = $this->documents_model->consultar($doc, $account_id);

		if (!isset($result['ok']) || $result['ok'] !== true) {
			$msg = isset($result['error']) ? $result['error'] : gettext('Falha ao consultar o documento.');
			$this->session->set_flashdata('flux_error', $msg);
			redirect(base_url().'accounts/documents?doc='.$doc.($account_id ? '&account_id='.$account_id : ''));
			return;
		}

		// Atualiza a conta (opcional)
		if ($update_account && $account_id) {
			$mapped = $this->documents_model->map_result_to_account($doc, $result['data']);
			$upd = $this->documents_model->update_account_from_doc($account_id, $mapped);
			if ($upd) {
				$this->session->set_flashdata('flux_notification', gettext('Conta atualizada com dados do documento.'));
			} else {
				$this->session->set_flashdata('flux_error', gettext('Consulta realizada, mas não foi possível atualizar a conta.'));
			}
		} else {
			$this->session->set_flashdata('flux_notification', gettext('Consulta realizada com sucesso.'));
		}

		// Redireciona para manter padrão de tela
		redirect(base_url().'accounts/documents?doc='.$doc.($account_id ? '&account_id='.$account_id : '').($update_account ? '&update_account=1' : ''));
	}

	/**
	 * Endpoint AJAX para preencher formulário de conta (tax_number -> dados)
	 * POST: doc
	 */
	public function ajax_consultar()
	{
		$doc = $this->input->post('doc', true);
		$doc = preg_replace('/\D+/', '', (string) $doc);
		$len = strlen($doc);

		$this->output->set_content_type('application/json', 'utf-8');
		if ($doc === '' || ($len !== 11 && $len !== 14)) {
			$this->output->set_status_header(422);
			echo json_encode(array(
				'ok' => false,
				'error' => 'Documento inválido. Envie CPF (11) ou CNPJ (14).',
			));
			return;
		}

		$result = $this->documents_model->consultar($doc, null);
		if (!isset($result['ok']) || $result['ok'] !== true) {
			$this->output->set_status_header(502);
			$this->flux_log->write_log('result', json_encode($result));
//			$this->session->set_flashdata('flux_notification', $result['message']);
			echo json_encode(array(
				'ok' => false,
				'message' => $result['message'],
				'error' => isset($result['error']) ? $result['error'] : 'Falha ao consultar.',
			));
			$this->session->set_flashdata('flux_notification', gettext($result['message']));
			return;
		}
        $this->flux_log->write_log('result', json_encode($result));
		$mapped = $this->documents_model->map_result_to_account($doc, $result['data']);
		echo json_encode(array(
			'ok' => true,
			'doc' => $doc,
			'mapped' => $mapped,
			'data' => $result['data'],
		));
	}
}
