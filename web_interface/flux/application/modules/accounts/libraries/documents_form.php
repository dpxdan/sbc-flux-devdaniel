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

class Documents_form extends common {

	function __construct($library_name = '') {
		$this->CI = &get_instance();
	}

	function get_consulta_form()
	{
		$form['forms'] = array(
			base_url().'accounts/documents/consultar/',
			array(
				'id' => 'documents_consulta_form',
				'name' => 'documents_consulta_form',
			),
		);

		$form[gettext('Consulta CPF/CNPJ')] = array(
			array(
				gettext('Conta (ID) - opcional'),
				'INPUT',
				array(
					'name' => 'account_id',
					'id' => 'account_id',
					'size' => '10',
					'class' => 'text field medium',
					'placeholder' => 'Ex.: 123',
				),
				'',
				'tOOL TIP',
				'',
			),
			array(
				gettext('CPF/CNPJ'),
				'INPUT',
				array(
					'name' => 'doc',
					'id' => 'doc',
					'size' => '20',
					'class' => 'text field medium',
					'placeholder' => 'Somente números ou com pontuação',
				),
				'required',
				'tOOL TIP',
				'',
				' <i style="cursor:pointer; font-size: 17px; position:absolute; right:20px; bottom: 7px;" title="Consultar" class="documents_consultar align-self-end text-success fa fa-search"></i>',
			),
			array(
				gettext('Atualizar conta com os dados retornados'),
				'INPUT',
				array(
					'name' => 'update_account',
					'id' => 'update_account',
					'type' => 'checkbox',
					'value' => '1',
					'class' => 'mt-2',
				),
				'',
				'tOOL TIP',
				'',
			),
		);

		$form['button_save'] = array(
			'name' => 'action',
			'content' => gettext('Consultar'),
			'value' => 'save',
			'type' => 'submit',
			'class' => 'btn btn-success',
		);
		$form['button_cancel'] = array(
			'name' => 'action',
			'content' => gettext('Voltar'),
			'value' => 'cancel',
			'type' => 'button',
			'class' => 'btn btn-secondary mx-2',
			'onclick' => 'return redirect_page(\'/accounts/customer_list/\')',
		);

		return $form;
	}
}
