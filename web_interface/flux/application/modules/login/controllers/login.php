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
class Login extends MX_Controller
{

    function __construct()
    {
        parent::__construct();
        $this->load->helper('form');
        $this->load->library('flux/permission');
        $this->load->library('encrypt');
        $this->load->model('Auth_model');
        $this->load->model('db_model');
        $this->load->model('login_model');
        $this->load->library('form_validation');
        $this->load->library('FLUX_Sms');
        $this->load->library ( 'flux_log' );
        $this->load->library('user_agent');
        $this->load->helper('cookie');
    }

    function test()
    {
        $popup_flag = $_POST['flag'];

        $flag = '1';

        if ($flag == 'termination_rates') {
            $this->session->set_userdata($popup_flag . 'popup_flag', $flag);
        }

        return true;
    }

    function set_lang_global($post = false)
    {
        $language = $this->uri->segment(3);

        $this->session->set_userdata('user_language', $language);

        $this->locale->set_lang();
        return true;
    }

    function index()
    {
        $this->load->helper('cookie');
        $cookie_user = get_cookie('post_info');
        if (! empty($cookie_user)) {
            $data['flux_notification'] = gettext("Please Check Your account is deleted or inactive from admin side, please contact to your administrator");
        }
        if (isset($this->session->userdata['key'])) {
            $key = $this->session->userdata('key');
            $reseller_id = $this->common->decode($this->common->decode_params(trim($key)));
        }
        if ($this->session->userdata('user_login') == FALSE) {
            if (! empty ( $_POST ) && isset($_POST ['username']) && isset($_POST ['password']) && trim ( $_POST ['username'] ) != '' && trim ( $_POST ['password'] ) != '') {
                $actual_password = $_POST['password'];
                $_POST['password'] = $this->common->encode($_POST['password']);
                $user_valid = $this->Auth_model->verify_login($_POST['username'], $_POST['password']);

                if ($user_valid == 1) {
                    $this->session->set_userdata('user_login', TRUE);
                    $result = $this->login_model->get_account_by_username_or_email($_POST['username']);

                    $user_multi_level = 0;
                    $addon_status = $this->db_model->countQuery("*", "addons", array(
                        'package_name' => 'pbx'
                    ));
                    $this->flux_log->write_log('login', json_encode($_SERVER));
                    if ($addon_status != '99' && $result['type'] != '1000' && $result['id'] != '100') {
                        $multidomain = $this->db_model->getSelect("*", "domain,domains_to_accounts", array(
                            'domain' => $_SERVER["HTTP_HOST"],
                            'domains_to_accounts.accountid' => $result['id'],
                            'domain.status' => 0
                        ));
                        $multidomain_result = $multidomain->result_array();
                        if (! empty($multidomain_result)) {
                            $user_multi_level = 0;
                        } 
                        else {
                            $user_multi_level = 1;
                        }
                    }
                    if ($user_multi_level == 0) {

                        $logintype = $result['type'] == - 1 ? 2 : $result['type'];
                        $this->session->set_userdata('logintype', $logintype);
                        $this->session->set_userdata('user_api', 'true');
                        $this->session->set_userdata('userlevel_logintype', $result['type']);
                        $this->session->set_userdata('username', $_POST['username']);
                        $this->session->set_userdata('accountinfo', $result);
                        $permission_result = $this->login_model->get_permission($result['permission_id']);
                        $permission_decode = json_decode($permission_result['permissions'], true);
                        $permission_decode['login_type'] = $result['type'];
                        $this->session->set_userdata('permissioninfo', $permission_decode);
                        $logintype = $result['type'] == - 1 ? 2 : $result['type'];
                        $this->session->set_userdata('logintype', $logintype);
                        $this->session->set_userdata('userlevel_logintype', $result['type']);
                        $this->session->set_userdata('username', $_POST['username']);
                        $this->session->set_userdata('accountinfo', $result);
                        $token = $this->token($result['id'], 'e', $result);
                        $this->session->set_userdata('token', $token);
                        $login_activity_array=array(
                            "account_id"=>$result['id'],
                            "country_name"=>ucwords(strtolower(geoip_country_name_by_name($this->input->server('REMOTE_ADDR')))),
                            "timestamp"=>gmdate("Y-m-d H:i:s"),
                            "user_agent"=> $this->agent->agent_string(),
                            "ip"=>$this->input->server('REMOTE_ADDR')
                            );
                        $this->login_model->insert_login_activity($login_activity_array);
                        
                        $accessid = $this->encrypt($this->config->item('private_key'), $result['id'] . $result['type']);
                        $this->session->set_userdata('ipsettings_token', $accessid);
                        if ($result['status'] == 1) {
                            $this->session->set_flashdata('flux_danger_alert', gettext("Your account has been deactive please contact to your administrator"));
                        } else {
                            if (($actual_password == 'admin') or (!$this->form_validation->chk_password_expression($actual_password,false))) {
                                if ($logintype == - 1 || $logintype == 2 || $logintype == 4)
                                    $url = "/accounts/admin_edit/" . $result['id'];
                                elseif ($logintype == 0 || $logintype == 3 || $logintype == 1)
                                    $url = "/user/user_change_password";
                                else
                                    $url = "#";
                                $this->session->set_flashdata('flux_danger_alert', gettext("Please do not use default or less secure password for your account!! You must change password from")." <a href='" . $url . "'><b>".gettext('HERE')."</b></a> .");

                                {}
                            }
                        }

                        $invoice_conf = $this->login_model->get_invoice_conf_for_account($result);
                        $data['user_logo'] = (! empty($invoice_conf['logo'])) ? $invoice_conf['accountid'] . "_" . $invoice_conf['logo'] : "logo.png";
                        $data['user_header'] = (! empty($invoice_conf['website_title'])) ? $invoice_conf['website_title'] : "Flux Telecom - Unindo pessoas e negócios Solution";
                        $data['user_footer'] = (! empty($invoice_conf['website_footer'])) ? $invoice_conf['website_footer'] : "Flux Telecom All Rights Reserved.";
                        $data['user_favicon'] = (! empty($invoice_conf['favicon'])) ? $invoice_conf['accountid'] . "_" . $invoice_conf['favicon'] : "favicon.ico";
                        $this->session->set_userdata('user_logo', $data['user_logo']);
                        $this->session->set_userdata('user_header', $data['user_header']);
                        $this->session->set_userdata('user_footer', $data['user_footer']);
                        $this->session->set_userdata('user_favicon', $data['user_favicon']);
                        if ($result['type'] == 0 || $result['type'] == 1 || $result['type'] == 3) {
                            $menu_list = $this->permission->get_module_access($result['type']);
                            $this->session->set_userdata('mode_cur', 'user');
                            if ($result['type'] == 1) {
                                $new_url = get_cookie('flux_last_visit_url' . $result["id"]);
                                if (empty($new_url) || ! isset($new_url) || $new_url == '') {
                                    redirect(base_url() . 'dashboard/');
                                }
                                redirect($new_url);
                            } 
                            else {
                                $new_url = get_cookie('flux_last_visit_url' . $result["id"]);
                                if (empty($new_url) || ! isset($new_url) || $new_url == '') {
                                    redirect(base_url() . 'user/user/');
                                }
                                if (strpos($new_url, 'addons') !== false) {
                                    redirect(base_url() . 'dashboard/');
                                }
                                redirect($new_url);
                            }
                        } else {
                            $menu_list = $this->permission->get_module_access($result['type']);
                            $this->session->set_userdata('mode_cur', 'admin');

                            $new_url = get_cookie('flux_last_visit_url' . $result["id"]);
                            if (empty($new_url) || ! isset($new_url) || $new_url == '') {
                                redirect(base_url() . 'dashboard/');
                            }
                            if (strpos($new_url, 'addons') !== false) {
                                redirect(base_url() . 'dashboard/');
                            }
                            redirect($new_url);
                        }
                    } 
                    else {
                        $data['flux_notification'] = gettext("Login unsuccessful. Please make sure you entered the correct username and password, and that your account is active");
                    }
                } else {
                    $data['flux_notification'] = gettext("Login unsuccessful. Please make sure you entered the correct username and password, and that your account is active");
                }
            } else {
                if (! empty ( $_POST ) && ((isset($_POST ['password']) && ($_POST ['password'] == '')) || (isset($_POST ['password']) && ($_POST ['username'] == '')))) {
					$data ['flux_notification'] = gettext("Please enter Username/email and Password.");
				}
            }

            if (isset($_SERVER['HTTP_HOST'])) {
		if($_SERVER['HTTP_HOST'] == $_SERVER['SERVER_NAME']){
			$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'];
		}else{
			$_SERVER['HTTP_HOST'] = $_SERVER['SERVER_NAME'];		
		}
                if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") {
                    $domain = "https://" . $_SERVER["HTTP_HOST"] . "/";
                } else {
                    $domain = "http://" . $_SERVER["HTTP_HOST"] . "/";
                }

                $http_host = $_SERVER["HTTP_HOST"];
                $invoice_conf = $this->login_model->get_invoice_conf_by_domain($domain, $http_host);
                $data['user_logo'] = (! empty($invoice_conf['logo'])) ? $invoice_conf['logo'] : "logo.png";
                $data['website_header'] = (! empty($invoice_conf['website_title'])) ? $invoice_conf['website_title'] : "Flux Telecom - Unindo pessoas e negócios";
                $data['website_footer'] = (! empty($invoice_conf['website_footer'])) ? $invoice_conf['website_footer'] : "Flux Telecom All Rights Reserved.";
                $data['user_favicon'] = (! empty($invoice_conf['favicon'])) ? $invoice_conf['accountid'] . "_" . $invoice_conf['favicon'] : "favicon.ico";
                $this->session->set_userdata('user_logo', $data['user_logo']);
                $this->session->set_userdata('user_header', $data['website_header']);
                $this->session->set_userdata('user_footer', $data['website_footer']);
                $this->session->set_userdata('user_favicon', $data['user_favicon']);
            }

            $this->session->set_userdata('user_login', FALSE);
            $data['app_name'] = 'Flux Telecom - Unindo pessoas e negócios';
            $this->load->view('view_login', $data);
        } 
        else {
	    if($_SERVER['HTTP_HOST'] == $_SERVER['SERVER_NAME']){
			$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'];
	    }else{
			$_SERVER['HTTP_HOST'] = $_SERVER['SERVER_NAME'];		
	    }
            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") {
                $custom_domain = "https://" . $_SERVER["HTTP_HOST"];
            } else {
                $custom_domain = "http://" . $_SERVER["HTTP_HOST"];
            }
            $invoice_conf = $this->login_model->get_invoice_conf_by_custom_domain_or_reseller($custom_domain, $_SERVER["HTTP_HOST"], $reseller_id);

            $data['user_logo'] = (! empty($invoice_conf['logo'])) ? $invoice_conf['accountid'] . "_" . $invoice_conf['logo'] : "logo.png";
            $data['user_header'] = (! empty($invoice_conf['website_title'])) ? $invoice_conf['website_title'] : "Flux Telecom - Unindo pessoas e negócios Solution";
            $data['user_footer'] = (! empty($invoice_conf['website_footer'])) ? $invoice_conf['website_footer'] : "Flux Telecom All Rights Reserved.";
            $data['user_favicon'] = (! empty($invoice_conf['favicon'])) ? $invoice_conf['accountid'] . "_" . $invoice_conf['favicon'] : "favicon.ico";

            $this->session->set_userdata('user_logo', $data['user_logo']);
            $this->session->set_userdata('user_header', $data['user_header']);
            $this->session->set_userdata('user_footer', $data['user_footer']);
            $this->session->set_userdata('user_favicon', $data['user_favicon']);
            if ($this->session->userdata('logintype') == '2') {
                redirect(base_url() . 'dashboard/');
            } else {
                redirect(base_url() . 'user/user/');
            }
        }
    }

    protected function token($string, $action, $account_info = '')
    {
        $key = hash('sha256', config_item('token_key'));

        $secret_iv = config_item('iv_key');

        $iv = substr(hash('sha256', $secret_iv), 0, 16);
        if ($action == "e") {
            $token = base64_encode(openssl_encrypt($string, 'AES-256-CBC', $key, 0, $iv));

            if (is_array($account_info)) {
                $account_info['token'] = $token;
                return $account_info;
            }

            return $token;
        }

        if ($action == "d") {
            $token = openssl_decrypt(base64_decode($string), 'AES-256-CBC', $key, 0, $iv);
            return $token;
        }
    }

    function logout()
    {
        $userdata = $this->session->userdata('accountinfo');
        $this->session->set_userdata('key', "");
        $this->session->sess_destroy();
        $last_url_data = $_SERVER['HTTP_REFERER'];
        set_cookie('flux_last_visit_url' . $userdata['id'], $last_url_data, '3600');
        redirect(base_url());
    }

    function paypal_response()
    {
        if (count($_POST) > 0) {
            $response_arr = $_POST;

            $logger_path = $this->login_model->get_system_value('log_path', 'global');
            $fp = fopen($logger_path . "flux_payment.log", "a+");
            $date = date("Y-m-d H:i:s");
            fwrite($fp, "====================" . $date . "===============================\n");
            foreach ($response_arr as $key => $value) {
                fwrite($fp, $key . ":::>" . $value . "\n");
            }

            $payment_transaction = $this->login_model->get_pending_payment_transaction($response_arr['item_number']);
            $accountid = isset($payment_transaction['accountid']) ? $payment_transaction['accountid'] : '';

            $this->login_model->delete_pending_payment_transaction($response_arr['item_number']);

            $balance_amt = $actual_amount = $response_arr["custom"];

            $paypal_fee = $this->login_model->get_system_value('paypal_fee', 'paypal');
            $paypalfee = ($paypal_fee == 0) ? '0' : $response_arr["mc_gross"];

            if (($response_arr["payment_status"] == "Pending" || $response_arr["payment_status"] == "Complete" || $response_arr["payment_status"] == "Completed") && $accountid != '') {

                $paypal_tax = $this->login_model->get_system_value('paypal_tax', 'paypal');

                $account_data = $this->login_model->get_account_by_id($accountid);

                $currency = $this->login_model->get_currency_by_id($account_data["currency_id"]);
                $date = date('Y-m-d H:i:s');

                $payment_trans_array = array(
                    "accountid" => $accountid,
                    "amount" => $response_arr["payment_gross"],
                    "tax" => "1",
                    "payment_method" => "Paypal",
                    "actual_amount" => $actual_amount,
                    "paypal_fee" => $paypalfee,
                    "user_currency" => $currency["currency"],
                    "currency_rate" => $currency["currencyrate"],
                    "transaction_details" => json_encode($response_arr),
                    "date" => $date
                );
                $paymentid = $this->login_model->insert_payment_transaction($payment_trans_array);
                $parent_id = $account_data['reseller_id'] > 0 ? $account_data['reseller_id'] : '-1';
                $payment_arr = array(
                    "accountid" => $accountid,
                    "payment_mode" => "1",
                    "credit" => $balance_amt,
                    "type" => "PAYPAL",
                    "payment_by" => $parent_id,
                    "notes" => "Payment Made by Paypal on date:-" . $date,
                    "paypalid" => $paymentid,
                    "txn_id" => $response_arr["txn_id"],
                    'payment_date' => gmdate('Y-m-d H:i:s', strtotime($response_arr['payment_date']))
                );
                $this->login_model->insert_payment($payment_arr);
                $last_invoice_ID = $this->login_model->get_last_invoice_number();
                $reseller_id = $account_data['reseller_id'] > 0 ? $account_data['reseller_id'] : 0;
                $invoiceconf = $this->login_model->get_receipt_invoice_conf($reseller_id);
                $invoice_prefix = $invoiceconf['invoice_prefix'];

                $due_date = gmdate("Y-m-d H:i:s", strtotime(gmdate("Y-m-d H:i:s") . " +" . $invoiceconf['interval'] . " days"));
                $invoice_id = $this->generate_receipt($account_data['id'], $balance_amt, $account_data, $last_invoice_ID + 1, $invoice_prefix, $due_date);
                $details_insert = array(
                    'created_date' => $date,
                    'credit' => $balance_amt,
                    'debit' => '-',
                    'accountid' => $account_data["id"],
                    'reseller_id' => $account_data['reseller_id'],
                    'invoiceid' => $invoice_id,
                    'description' => "Payment Made by Paypal on date:-" . $date,
                    'item_type' => 'PAYMENT',
                    'before_balance' => $account_data['balance'],
                    'after_balance' => $account_data['balance'] + $balance_amt
                );
                $this->login_model->insert_invoice_detail($details_insert);
                $this->db_model->update_balance($balance_amt, $account_data["id"], "credit");
                $this->session->set_flashdata('flux_errormsg', 'Payment done successfully!');
                redirect(base_url() . 'user/user/');
            } else {
                $response_arr['flux_status'] = "Invalid request. No transaction id found in database.";

                $payment_trans_array = array(
                    "accountid" => ($accountid) ? $accountid : 0,
                    "amount" => $response_arr["payment_gross"],
                    "tax" => "1",
                    "payment_method" => "Paypal",
                    "actual_amount" => $actual_amount,
                    "paypal_fee" => $paypalfee,
                    "user_currency" => ($currency["currency"]) ? $currency["currency"] : '',
                    "currency_rate" => ($currency["currencyrate"]) ? $currency["currencyrate"] : '',
                    "transaction_details" => json_encode($response_arr),
                    "date" => $date
                );
                $paymentid = $this->login_model->insert_payment_transaction($payment_trans_array);
                $this->session->set_flashdata('flux_notification', gettext('Payment transaction invalid. Please contact Administrator.'));
            }
        }
        redirect(base_url() . 'user/user/');
    }

    function generate_receipt($accountid, $amount, $accountinfo, $last_invoice_ID, $invoice_prefix, $due_date)
    {
        return $this->login_model->generate_receipt($accountid, $amount, $accountinfo, $last_invoice_ID, $invoice_prefix, $due_date);
    }

    function get_language_text()
    {
        echo gettext($_POST['display']);
    }

    function encode_params($string)
    {
        $data = base64_encode($string);
        $data = str_replace(array(
            '+',
            '/',
            '='
        ), array(
            '-',
            '$',
            ''
        ), $data);
        return $data;
    }

    function encode($value)
    {
        $ivSize = openssl_cipher_iv_length('BF-ECB');
        $iv = openssl_random_pseudo_bytes($ivSize);
        $encrypted = openssl_encrypt($value, 'BF-ECB', $this->config->item('private_key'), OPENSSL_RAW_DATA, $iv);
        $encrypted = $this->encode_params($encrypted);
        return $encrypted;
    }

    function encrypt($api_key, $id = false)
    {
        $str = $this->encode($api_key . "#" . $id . "_" . $api_key);
        return $this->encode_params($str);
    }

    function login_as_reseller($select_id)
    {
        $accountinfo = $this->session->userdata('accountinfo');
        $where = array(
            'id' => $select_id
        );
        $account_res = $this->login_model->get_account_by_id($select_id);
        $this->session->sess_destroy();
        redirect(base_url() . "relogin/" . $account_res['id'] . "/" . $accountinfo['id'] . "/");
    }

    function login_as_customer($select_id)
    {
        $accountinfo = $this->session->userdata('accountinfo');
        $where = array(
            'id' => $select_id
        );
        $account_res = $this->login_model->get_account_by_id($select_id);
        $this->session->sess_destroy();
        redirect(base_url() . "relogin/" . $account_res['id'] . "/" . $accountinfo['id'] . "/");
    }

    function login_as_admin($select_id)
    {
        $this->session->sess_destroy();
        redirect(base_url() . "relogin/" . $select_id . "/0/");
    }

    function relogin($new_login_id, $master_id = '0')
    {
        $where = array(
            'id' => $new_login_id
        );
        $account_res = $this->login_model->get_account_by_id($new_login_id);
        $master_login_details = array();
        if ($master_id != '0') {
            $where = array(
                'id' => $master_id
            );
            $admin_res = $this->login_model->get_account_by_id($master_id);
            $master_login_details = array(
                'master_login_id' => $admin_res['id'],
                'master_number' => $admin_res['number'],
                'master_password' => $this->common->decode($admin_res['password'])
            );
        }
        $this->session->set_userdata('user_login', TRUE);
        $result = $this->login_model->get_account_by_username_or_email($account_res['number'], true);
	$password=$this->common->decode($result['password']);
        $permission_result = $this->login_model->get_permission($result['permission_id']);
        $permission_decode = json_decode($permission_result['permissions'], true);
        $permission_decode['login_type'] = $result['type'];
        $logintype = $result['type'] == - 1 ? 2 : $result['type'];
        if (! empty($master_login_details)) {
            $this->session->set_userdata('master_login_details', $master_login_details);
        }
        $this->session->set_userdata('logintype', $logintype);
        $this->session->set_userdata('userlevel_logintype', $result['type']);
        $this->session->set_userdata('username', $account_res['number']);
        $this->session->set_userdata('accountinfo', $result);
        $token = $this->token($result['id'], 'e', $result);
        $accessid = $this->encrypt($this->config->item('private_key'), $result['id'] . $result['type']);
        $this->session->set_userdata('ipsettings_token', $accessid);
        $this->session->set_userdata('permissioninfo', $permission_decode);
        if (($password == 'admin') or (!$this->form_validation->chk_password_expression($password,false))) {
            if ($logintype == - 1 || $logintype == 2 || $logintype == 4)
                $url = "/accounts/admin_edit/" . $result['id'];
            elseif ($logintype == 0 || $logintype == 3 || $logintype == 1)
                $url = "/user/user_change_password";
            else
                $url = "#";
            $this->session->set_flashdata('flux_danger_alert', gettext("Please do not use default or less secure password for your account!! You must change password from")." "." <a href='" . $url . "'><b>".gettext("HERE"). "</b></a> .");

            {}
        }
        $invoice_conf = $this->login_model->get_invoice_conf_for_account($result);
        $data['user_logo'] = (! empty($invoice_conf['logo'])) ? $invoice_conf['accountid'] . "_" . $invoice_conf['logo'] : "logo.png";
        $data['user_header'] = (! empty($invoice_conf['website_title'])) ? $invoice_conf['website_title'] : "Flux Telecom - Unindo pessoas e negócios";
        $data['user_footer'] = (! empty($invoice_conf['website_footer'])) ? $invoice_conf['website_footer'] : "Flux Telecom All Rights Reserved.";
        $data['user_favicon'] = (! empty($invoice_conf['favicon'])) ? $invoice_conf['accountid'] . "_" . $invoice_conf['favicon'] : "favicon.ico";
        $this->session->set_userdata('user_logo', $data['user_logo']);
        $this->session->set_userdata('user_header', $data['user_header']);
        $this->session->set_userdata('user_footer', $data['user_footer']);
        $this->session->set_userdata('user_favicon', $data['user_favicon']);
        if ($result['type'] == 0 || $result['type'] == 1 || $result['type'] == 3) {
            $menu_list = $this->permission->get_module_access($result['type']);

            $this->session->set_userdata('mode_cur', 'user');
            if ($result['type'] == 1) {
                redirect(base_url() . 'dashboard/');
            } else {
                redirect(base_url() . 'user/user/');
            }
        } else {
            $menu_list = $this->permission->get_module_access($result['type']);
            $this->session->set_userdata('mode_cur', 'admin');
            redirect(base_url() . 'dashboard/');
        }
    }

    function customer_permission_list()
    {
        $button_array = $this->input->post();
        $permissioninfo = $this->session->userdata('permissioninfo');
        $currnet_url = $button_array['current_url'];
        $url_explode = explode('/', $currnet_url);
        $module_name = $url_explode[3];
        $sub_module_name = $url_explode[4];
        if ((isset($permissioninfo[$module_name][$sub_module_name][$button_array['button_name']]) && $permissioninfo[$module_name][$sub_module_name][$button_array['button_name']] == 0) or $permissioninfo['login_type'] == '-1' or $permissioninfo['login_type'] == '0' or $permissioninfo['login_type'] == '3') {
            echo 0;
        } else {
            echo 1;
        }
    }
}

?>
