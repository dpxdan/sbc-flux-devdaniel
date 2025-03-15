<?php
// ##############################################################################
// Flux Telecom - Unindo pessoas e negócios
//
// Copyright (C) 2021 Flux Telecom
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
if (! defined('BASEPATH'))
    exit('No direct script access allowed');

class api_endpoints_form
{

    protected $CI;

    function __construct()
    {
        $this->CI = & get_instance();
    }

    function get_api_endpoints_form_fields($id = false, $partner_id = false)
    {
        if (! $partner_id) {

            $partner = array(
                gettext('Partner'),
                array(
                    'name' => 'partner_id',
                    'class' => 'partner_id'
                ),
                'SELECT',
                '',
                array(
                    "name" => "partner_id",
                    "rules" => "required"
                ),
                'tOOL TIP',
                'Please Enter account number',
                'id',
                'partner_name',
                'api_partners',
                'build_dropdown_country_camel',
                '',
                ''
            );
        } 
        else {
            $partner = array(
                gettext('Partner'),
                array(
                    'name' => 'partner_id',
                    'class' => 'partner_id',
                    'vlaue' => $partner_id
                ),
                'SELECT',
                '',
                array(
                    "name" => "partner_id",
                    "rules" => "required",
                    'selected' => 'selected'
                ),
                'tOOL TIP',
                'Please Enter account number',
                'id',
                'partner_name',
                'api_partners',
                'build_dropdown_country_camel',
                '',
                ''
            );
        }
        $val = $id > 0 ? 'api_endpoints.endpoint_name.' . $id : 'api_endpoints.endpoint_name';
        if ($id > 0) {
            $reseller_drp = array(
                gettext('Reseller'),
                array(
                    'name' => 'reseller_id',
                    'class' => 'reseller_drp',
                    'id' => 'reseller_id',
                    'onchange' => 'account_change_add(this.value)'
                ),
                'SELECT',
                '',
                '',
                'tOOL TIP',
                'Please Enter account number',
                'id',
                'first_name,last_name,number',
                'accounts',
                'build_concat_dropdown_reseller',
                'where_arr',
                ''
            );
        } 
        else {
            $reseller_drp = array(
                gettext('Reseller'),
                array(
                    'name' => 'reseller_id',
                    'class' => 'reseller_drp',
                    'id' => 'reseller_id',
                    'onchange' => 'account_change_add(this.value)'
                ),
                'SELECT',
                '',
                '',
                'tOOL TIP',
                'Please Enter account number',
                'id',
                'first_name,last_name,number',
                'accounts',
                'build_concat_dropdown_reseller',
                'where_arr',
                ''
            );
        }
        $form['forms'] = array(
            base_url() . 'api_endpoints/api_endpoints_save/',
            array(
                'id' => 'api_endpoints_form',
                'method' => 'POST',
                'name' => 'api_endpoints_form'
            )
        );
        $form[gettext('Endpoint Information')] = array(
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
                gettext('Endpoint Name'),
                'INPUT',
                array(
                    'name' => 'endpoint_name',
                    'size' => '20',
                    'class' => "text field medium"
                ),
                'trim|required|xss_clean|is_unique[' . $val . ']',
                'tOOL TIP',
                'Please Enter endpoint Name'
            ),
            array(
                    gettext('Account'),
                    array(
                        'name' => 'accountid',
                        'class' => 'account_drp',
                        'id' => 'account_drp'
                    ),
                    'SELECT',
                    '',
                    '',
                    'tOOL TIP',
                    'Please Enter account number',
                    'id',
                    'first_name,last_name,number',
                    'accounts',
                    'build_concat_dropdown',
                    'where_arr',
                    array(
                        "reseller_id" => "0",
                        "type" => "0,3",
                        "deleted" => "0"
                    )
                ),
            $reseller_drp,
            array(
                gettext('Endpoint URL'),
                'INPUT',
                array(
                    'name' => 'endpoint_url',
                    'size' => '50',
                    'class' => "text field medium"
                ),
                'trim',
                'tOOL TIP',
                'Please Enter Endpoint URL'
            ),            
            $partner,
            array(
                gettext('Status'),
                'status',
                'SELECT',
                '',
                '',
                'tOOL TIP',
                'Please Select Status',
                '',
                '',
                '',
                'set_status'
            )
        );
        $form[gettext('Authentication Information')] = array(
            array(
                gettext('Autentication Type'),
                'endpoint_auth',
                'SELECT',
                '',
                '',
                'tOOL TIP',
                '',
                '',
                '',
                '',
                'set_authtype_drp_option'
            ),            
            array(
                gettext('Authentication User'),
                'INPUT',
                array(
                    'name' => 'endpoint_user',
                    'size' => '20',
                    'class' => "text field medium"
                ),
                '',
                'tOOL TIP',
                ''
            ),
            array(
                gettext('Authentication Password'),
                'INPUT',
                array(
                    'name' => 'endpoint_password',
                    'size' => '50',
                    'class' => "text field medium"
                ),
                '',
                'tOOL TIP',
                ''
            ),
            array(
                gettext('Endpoint Token'),
                'INPUT',
                array(
                    'name' => 'endpoint_token',
                    'size' => '50',
                    'class' => "text field medium"
                ),
                '',
                'tOOL TIP',
                ''
            )
        );
        $form['button_cancel'] = array(
            'name' => 'action',
            'content' => gettext('Close'),
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
            'type' => 'button',
            'class' => 'btn btn-success'
        );
        return $form;
    }

    function get_api_endpoints_search_form()
    {
        $form['forms'] = array(
            "",
            array(
                'id' => "api_endpoints_search"
            )
        );
        $accountinfo = $this->CI->session->userdata('accountinfo');
        if ($accountinfo['type'] == - 1 || $accountinfo['type'] == 2) {
            $search_field_arr = array(
                array(
                    gettext('Endpoint Name'),
                    'INPUT',
                    array(
                        'name' => 'endpoint_name[endpoint_name]',
                        '',
                        'size' => '20',
                        'class' => "text field"
                    ),
                    '',
                    'tOOL TIP',
                    '1',
                    'endpoint_name[endpoint_name-string]',
                    '',
                    '',
                    '',
                    'search_string_type',
                    ''
                ),
                array(
                    gettext('Partner'),
                    'partner_id',
                    'SELECT',
                    '',
                    '',
                    'tOOL TIP',
                    'Please Enter account number',
                    'id',
                    'partner_name',
                    'api_partners',
                    'build_dropdown_country_camel',
                    '',
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
                    'set_search_status',
                    '',
                    ''
                ),
                array(
                    '',
                    'HIDDEN',
                    'ajax_search',
                    '1',
                    '',
                    '',
                    ''
                ),
                array(
                    '',
                    'HIDDEN',
                    'advance_search',
                    '1',
                    '',
                    '',
                    ''
                )
            );
        } 
        else {
            $search_field_arr = array(

                array(
                    gettext('Endpoint Name'),
                    'INPUT',
                    array(
                        'name' => 'api_endpoints[api_endpoints]',
                        '',
                        'size' => '20',
                        'class' => "text field"
                    ),
                    '',
                    'tOOL TIP',
                    '1',
                    'api_endpoints[api_endpoints-string]',
                    '',
                    '',
                    '',
                    'search_string_type',
                    ''
                ),

                array(
                    gettext('Partner'),
                    'partner_id',
                    'SELECT',
                    '',
                    '',
                    'tOOL TIP',
                    'Please Enter account number',
                    'id',
                    'partner_name',
                    'api_partners',
                    'build_dropdown_country_camel',
                    '',
                    ''
                ),
                array(
                    '',
                    'HIDDEN',
                    'ajax_search',
                    '1',
                    '',
                    '',
                    ''
                ),
                array(
                    '',
                    'HIDDEN',
                    'advance_search',
                    '1',
                    '',
                    '',
                    ''
                )
            );
        }
        $form[gettext('Search')] = $search_field_arr;
        $form['button_search'] = array(
            'name' => 'action',
            'id' => "api_endpoints_search_btn",
            'content' => gettext('Search'),
            'value' => 'save',
            'type' => 'button',
            'class' => 'btn btn-success float-right'
        );
        $form['button_reset'] = array(
            'name' => 'action',
            'id' => "id_reset",
            'content' => gettext('Clear'),
            'value' => 'cancel',
            'type' => 'reset',
            'class' => 'btn btn-secondary float-right ml-2'
        );
        return $form;
    }

    function build_api_endpoints_list_for_admin()
    {
        $accountinfo = $this->CI->session->userdata('accountinfo');
        if ($accountinfo['type'] == - 1 || $accountinfo['type'] == 2) {
            $grid_field_arr = array(
                array(
                    "<input type='checkbox' name='chkAll' class='ace checkall'/><label class='lbl'></label>",
                    "30",
                    "",
                    "",
                    "",
                    "",
                    "",
                    "false",
                    "center"
                ),
                array(
                    gettext("Endpoint Name"),
                    "150",
                    "endpoint_name",
                    "",
                    "",
                    "",
                    "EDITABLE",
                    "true",
                    "left"
                ),
                array(
                    gettext("Partner"),
                    "150",
                    "partner_id",
                    "partner_name",
                    "api_partners",
                    "get_field_name",
                    "",
                    "true",
                    "center"
                ),
                array(
                    gettext("Endpoint URL"),
                    "150",
                    "endpoint_url",
                    "",
                    "",
                    "",
                    "",
                    "true",
                    "center"
                ),
                array(
                    gettext("Modified Date"),
                    "150",
                    "last_modified_date",
                    "last_modified_date",
                    "last_modified_date",
                    "convert_GMT_to",
                    "",
                    "true",
                    "center"
                ),
                array(
                    gettext("Status"),
                    "30",
                    "status",
                    "status",
                    "api_endpoints",
                    "get_status",
                    "",
                    "true",
                    "center"
                ),
                array(
                    gettext("Action"),
                    "150",
                    "",
                    "",
                    "",
                    array(
                        "EDIT" => array(
                            "url" => "api_endpoints/api_endpoints_edit/",
                            "mode" => "popup",
                            "layout" => "medium"
                        ),
                        "DELETE" => array(
                            "url" => "api_endpoints/api_endpoints_remove/",
                            "mode" => "single"
                        )
                    ),
                    "false"
                )
            );
        } 
        else {
            $grid_field_arr = array(
                array(
                    gettext("Endpoint"),
                    "150",
                    "endpoint_name",
                    "",
                    "",
                    "",
                    "",
                    "true",
                    "left"
                ),
                array(
                    gettext("Partner"),
                    "150",
                    "partner_id",
                    "partner_name",
                    "api_partners",
                    "get_field_name",
                    "",
                    "true",
                    "center"
                ),
                array(
                    gettext("Created Date"),
                    "150",
                    "creation_date",
                    "creation_date",
                    "creation_date",
                    "convert_GMT_to",
                    "",
                    "true",
                    "center"
                ),
                array(
                    gettext("Modified Date"),
                    "150",
                    "last_modified_date",
                    "last_modified_date",
                    "last_modified_date",
                    "convert_GMT_to",
                    "",
                    "true",
                    "center"
                ),
                array(
                    gettext("Action"),
                    "150",
                    "",
                    "",
                    "",
                    array(
                        "EDIT" => array(
                            "url" => "api_endpoints/api_endpoints_edit/",
                            "mode" => "popup",
                            "layout" => "medium"
                        ),
                        "DELETE" => array(
                            "url" => "api_endpoints/api_endpoints_remove/",
                            "mode" => "single"
                        )
                    ),
                    "false"
                )
            );
        }

        return json_encode($grid_field_arr);
    }

    function build_grid_buttons()
    {
        $accountinfo = $this->CI->session->userdata('accountinfo');
        if (($accountinfo['type'] == - 1) || ($accountinfo['type'] == 2)) {
            $buttons_json = json_encode(array(
                array(
                    gettext("Create"),
                    "btn btn-line-warning btn",
                    "fa fa-plus-circle fa-lg",
                    "button_action",
                    "/api_endpoints/api_endpoints_add/",
                    "popup",
                    "medium",
                    "create"
                ),
                array(
                    gettext("Delete"),
                    "btn btn-line-danger",
                    "fa fa-times-circle fa-lg",
                    "button_action",
                    "/api_endpoints/api_endpoints_delete_multiple/",
                    "",
                    "",
                    "delete"
                ),
                array(
                    gettext("Import"),
                    "btn btn-line-blue",
                    "fa fa-download fa-lg",
                    "button_action",
                    "/api_endpoints/api_endpoints_import/",
                    '',
                    "small",
                    "import"
                ),
                array(
                    gettext("Export"),
                    "btn btn-xing",
                    "fa fa-upload fa-lg",
                    "button_action",
                    "/api_endpoints/api_endpoints_export_data_xls",
                    'single',
                    "",
                    "export"
                )
            ));
        } else {
            $buttons_json = json_encode(array(
                array(
                    gettext("Export"),
                    "btn btn-xing",
                    "fa fa-upload fa-lg",
                    "button_action",
                    "/api_endpoints/api_endpoints_export_data_xls",
                    'single',
                    "",
                    "export"
                )
            ));
        }
        return $buttons_json;
    }
}
?>
