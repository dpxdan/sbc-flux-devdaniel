<?php
// ##############################################################################
// Flux Telecom - Unindo pessoas e negócios
//
// Copyright (C) 2026 Flux Telecom
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
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Detraf_form extends common
{

    function __construct()
    {
        $this->CI = & get_instance();
    }

    function get_search_detraf_form()
    {
        $session_info = $this->CI->session->userdata('detraf_search');

        $form['forms'] = array(
            '',
            array('id' => 'detraf_search')
        );

        $form[gettext('Pesquisa')] = array(
            array(
            	gettext('Data Início'),
            	'INPUT',
            	array(
            		'name' => 'data_inicio',
            		'',
            		'size'  => '20',
            		'class' => "text field",
            		'id'    => 'detraf_from_date',
            	),
            	'',
            	'tOOL TIP',
            	'',
            	'data_inicio[data_inicio-date]',
            ),
            array(
            	gettext('Data Fim'),
            	'INPUT',
            	array(
            		'name' => 'data_fim',
            		'',
            		'size'  => '20',
            		'class' => "text field",
            		'id'    => 'detraf_to_date',
            	),
            	'',
            	'tOOL TIP',
            	'',
            	'data_fim[data_fim-date]',
            ),
            array(
                gettext('Operadora (EOT Devedora)'),
                'eot_devedora',
                'SELECT',
                '',
                '',
                'tOOL TIP',
                'Please Enter account number',
                'id',
                'eot,nome_fantasia,holding',
                'eot_anexo_5',
                'build_concat_eot_select_dropdown',
                'where_arr',
                array(
                    "nome_fantasia <>" => ""
                )
            ),
            array(
                gettext('Operadora (EOT Credora)'),
                'eot_credora',
                'SELECT',
                '',
                '',
                'tOOL TIP',
                'Please Enter account number',
                'id',
                'eot,nome_fantasia,holding',
                'eot_anexo_5',
                'build_concat_eot_select_dropdown',
                'where_arr',
                array(
                    "nome_fantasia <>" => ""
                )
            ),
           /* array(
                gettext('EOT Credora (sua operadora)'),
                'eot_credora',
                'INPUT',
                array(
                    'name'  => 'eot_credora',
                    'size'  => '10',
                    'class' => 'text field',
                    'value' => isset($session_info['eot_credora'])
                               ? $session_info['eot_credora']
                               : $this->CI->detraf_model->get_eot_credora(),
                ),
                '',
                'tOOL TIP',
                ''
            ),*/
            array('', 'HIDDEN', 'ajax_search',    '1', '', '', ''),
            array('', 'HIDDEN', 'advance_search', '1', '', '', ''),
        );

		$form['button_search'] = array(
			'name'    => 'action',
			'id'      => "detraf_search_btn",
			'content' => gettext('Search'),
			'value'   => 'save',
			'type'    => 'button',
			'class'   => 'btn btn-success float-right',
		);

		$form['button_reset'] = array(
			'name'    => 'action',
			'id'      => "id_reset",
			'content' => gettext('Clear'),
			'value'   => 'cancel',
			'type'    => 'reset',
			'class'   => 'btn btn-secondary float-right mx-2',
		);

        return $form;
    }

    function get_carrier_list_dropdown()
    {
        return $this->CI->detraf_model->get_carrier_list();
    }

    function get_detraf_operadoras()
    {
        $result = $this->CI->db->query(
            "SELECT DISTINCT co.eot, co.nomePrestadora
             FROM cadup_operadoras co
             INNER JOIN carrier_routing cr
                ON cr.carrier_id = co.carrier_id
               AND cr.carrier_rn1 = co.rn1
             WHERE co.eot IS NOT NULL AND co.eot <> ''
             ORDER BY co.nomePrestadora ASC, co.eot ASC"
        );

        $list = array('' => gettext('-- Selecione --'));
        if ($result && $result->num_rows() > 0) {
            foreach ($result->result_array() as $row) {
                $list[$row['eot']] = $row['eot'] . ' - ' . $row['nomePrestadora'];
            }
        }
        return $list;
    }

    function build_detraf()
    {
        $column_arr = array(
            array(gettext('EOT Credora'),        '65',  '', '', '', ''),
            array(gettext('Operadora Credora'),   '150', '', '', '', ''),
            array(gettext('EOT Devedora'),        '65',  '', '', '', ''),
            array(gettext('Operadora Devedora'),  '150', '', '', '', ''),
            array(gettext('Referência'),          '60',  '', '', '', ''),
            array(gettext('Período Tráfego'),     '60',  '', '', '', ''),
            array(gettext('POI'),                 '60',  '', '', '', ''),
            array(gettext('Tipo Rel.'),           '55',  '', '', '', ''),
            array(gettext('Descritor'),           '55',  '', '', '', ''),
            array(gettext('Grupo Horário'),       '60',  '', '', '', ''),
            array(gettext('Chamadas'),            '65',  '', '', '', ''),
            array(gettext('Minutos'),             '65',  '', '', '', ''),
        );

        return json_encode($column_arr);
    }
    
    function build_list_for_email()
    {
        $grid_field_arr = json_encode(array(
            array(
                gettext("EOT Credora"),
                "100",
                "",
                "",
                "",
                "",
                "",
                "true",
                "center"
            ),
            array(
                gettext("Operadora Credora"),
                "100",
                "",
                "",
                "",
                "",
                "",
                "true",
                "center"
            ),
            array(
                gettext("EOT Devedora"),
                "120",
                "",
                "",
                "",
                "",
                "",
                "true",
                "center"
            ),
            array(
                gettext("Operadora Devedora"),
                "175",
                "",
                "",
                "",
                "",
                "",
                "true",
                "center"
            ),
            array(
                gettext("Referência"),
                "175",
                "",
                "",
                "",
                "",
                "",
                "true",
                "center"
            ),
            array(
                gettext("Período Tráfego"),
                "175",
                "",
                "",
                "",
                "",
                "",
                "true",
                "center"
            ),           
            array(
                gettext("POI"),
                "175",
                "",
                "",
                "",
                "",
                "",
                "true",
                "center"
            ),
            array(
                gettext("Tipo Rel."),
                "175",
                "",
                "",
                "",
                "",
                "",
                "true",
                "center"
            ),
            array(
                gettext("Descritor"),
                "175",
                "",
                "",
                "",
                "",
                "",
                "true",
                "center"
            ),
            array(
                gettext("Grupo Horário"),
                "175",
                "",
                "",
                "",
                "",
                "",
                "true",
                "center"
            ),
            array(
                gettext("Chamadas"),
                "175",
                "",
                "",
                "",
                "",
                "",
                "true",
                "center"
            ),
            array(
                gettext("Minutos"),
                "175",
                "",
                "",
                "",
                "",
                "",
                "true",
                "center"
            ),
            array(
                gettext("Action"),
                "120",
                "",
                "",
                "",
                array(
                    "RESEND" => array(
                        "url" => "detraf/detraf_send_email/",
                        "mode" => "popup"
                    ),
                    "VIEW" => array(
                        "url" => "email/email_view/",
                        "mode" => "popup",
                        "layout" => "medium"
                    ),
                    "DELETE" => array(
                        "url" => "email/email_delete/",
                        "mode" => "single"
                    )
                ),
                "true"
            )
        ));
        return $grid_field_arr;
    }

    function build_grid_buttons_detraf()
    {
		$logintype      = $this->CI->session->userdata('userlevel_logintype');
        $detraf_contestacao = array(
            gettext("Detraf Contestação"),
            "btn btn-line-success",
            "fa fa-exclamation fa-lg",
            "button_action",
            "/detraf/detraf_contestacao/",
            'single',
            "small",
            "import",
        );
		$buttons_json = json_encode(array(
				$detraf_contestacao,
				array(
					gettext("Export")." ".gettext("CSV"),
					"btn btn-xing",
					"fa fa-file-excel-o fa-lg",
					"button_action",
					"/detraf/detraf_export_cdr_xls/",
					'single',
					"",
					"export",
				),
				array(
					gettext("Export")." ".gettext("PDF"),
					"btn btn-line-danger",
					"fa fa-file-pdf-o fa-lg",
					"button_action",
					"/detraf/detraf_export_cdr_pdf/",
					"single",
					"",
					"export",
				)
			));
		return $buttons_json;
	}
    
    function get_form_fields_email()
    {
    $form['forms'] = array(
        base_url() . 'email/email_re_send/',
        array(
            'id' => 'commission_form',
            'method' => 'POST',
            'name' => 'commission_form'
        )
    );
    $form[gettext('Resend Email')] = array(
        array(
            '',
            'HIDDEN',
            array(
                'name' => 'id'
            ),
            '',
            '',
            '',
            ''
        ),
        array(
            gettext('To'),
            'INPUT',
            array(
                'name' => 'to',
                'size' => '20',
                'class' => "text field medium"
            ),
            'trim|required|xss_clean',
            'tOOL TIP',
            ''
        ),
        array(
            gettext('From'),
            'INPUT',
            array(
                'name' => 'from',
                'size' => '20',
                'class' => "text field medium"
            ),
            'trim|required|xss_clean',
            'tOOL TIP',
            ''
        ),
        array(
            gettext('Subject'),
            'INPUT',
            array(
                'name' => 'subject',
                'size' => '20',
                'class' => "text field medium"
            ),
            'trim|required|xss_clean',
            'tOOL TIP',
            ''
        ),
        array(
            gettext('Body'),
            'TEXTAREA',
            array(
                'name' => 'body',
                'size' => '20',
                'class' => "text field medium"
            ),
            'trim|required|xss_clean',
            'tOOL TIP',
            ''
        ),

        array(
            gettext('Status'),
            'status',
            'SELECT',
            '',
            '',
            'tOOL TIP',
            'Please Enter account number',
            '',
            '',
            '',
            'email_search_status',
            '',
            ''
        )
    );
    $form['button_cancel'] = array(
        'name' => 'action',
        'content' => gettext('Cancel'),
        'value' => 'cancel',
        'type' => 'button',
        'class' => 'btn btn-secondary ml-2',
        'onclick' => 'return redirect_page(\'NULL\')'
    );
    $form['button_save'] = array(
        'name' => 'action',
        'content' => gettext('Save'),
        'value' => 'save',
        'id' => 'submit',
        'type' => 'submit',
        'class' => 'btn btn-success'
    );

    return $form;
}

    function build_detraf_contestacao()
    {
        $column_arr = array(
            array(gettext('EOT Credora'),       '60',  '', '', '', ''),
            array(gettext('Operadora Credora'), '130', '', '', '', ''),
            array(gettext('EOT Devedora'),      '60',  '', '', '', ''),
            array(gettext('Operadora Devedora'),'130', '', '', '', ''),
            array(gettext('Referência'),        '55',  '', '', '', ''),
            array(gettext('POI'),               '55',  '', '', '', ''),
            array(gettext('Grupo H.'),          '45',  '', '', '', ''),
            array(gettext('Cham. Deles'),       '65',  '', '', '', ''),
            array(gettext('Min. Deles'),        '65',  '', '', '', ''),
            array(gettext('Cham. Nossos'),      '65',  '', '', '', ''),
            array(gettext('Min. Nossos'),       '65',  '', '', '', ''),
            array(gettext('Diff Cham.'),        '60',  '', '', '', ''),
            array(gettext('Diff Min.'),         '60',  '', '', '', ''),
            array(gettext('Status'),            '75',  '', '', '', ''),
        );
        return json_encode($column_arr);
    }
    
    function build_grid_buttons_contestacao($batch_id)
    {
        $buttons = array(
            array(
                gettext('Export CSV'),
                'btn btn-xing',
                'fa fa-file-text-o fa-lg',
                'button_action',
                '/detraf/contestacao_export_csv/' . $batch_id . '/',
                'single', '', 'export'
            ),
            array(
                gettext('Só Divergências'),
                'btn btn-warning',
                'fa fa-exclamation-triangle fa-lg',
                'button_action',
                '/detraf/contestacao_export_csv/' . $batch_id . '/1/',
                'single', '', 'export'
            ),
        );
        return json_encode($buttons);
    }
}
?>
