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
class Signup extends MX_Controller
{

    function __construct()
    {
        parent::__construct();
        $this->load->model('signup_model');
        $this->load->helper('captcha');
        $this->load->library('flux/common');
        $this->load->library('flux/notification');
        $this->load->library('encrypt');
        $this->load->model('Flux_common');
        $this->signup_model->get_rate();
    }

    private function get_domain_context()
    {
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") {
            $domain = "https://" . $_SERVER["HTTP_HOST"] . "/";
        } else {
            $domain = "http://" . $_SERVER["HTTP_HOST"] . "/";
        }
        return array(
            'domain' => $domain,
            'http_host' => $_SERVER['HTTP_HOST']
        );
    }

    private function generate_captcha_view_data()
    {
        $random_number = substr(number_format(time() * rand(), 0, '', ''), 0, 6);
        $vals = array(
            'word' => $random_number,
            'img_path' => getcwd() . '/assets/captcha/',
            'img_url' => base_url() . 'assets/captcha/',
            'img_width' => '243',
            'img_height' => '50',
            'expiration' => '3600'
        );
        $captcha = create_captcha($vals);
        $this->session->set_userdata('captchaWord', $captcha['word']);
        return array('captcha' => $captcha);
    }

    function index($key = "")
    {
        if (Common_model::$global_config['system_config']['enable_signup'] == 1) {
            redirect(base_url());
        }

        if ($key != '') {
            $this->session->set_userdata("signup_key", $key);
        }

        $data = array();
        $add_array = $this->input->post();
        if ((!empty($add_array)) && ((empty($add_array['telephone'])) || (empty($add_array['email'])) || (empty($add_array['userCaptcha'])) || (empty($add_array['first_name'])))) {
            redirect(base_url() . "login/");
        }

        $currency_info = $this->signup_model->get_currency_by_code(Common_model::$global_config['system_config']['base_currency']);
        $data['country_id'] = Common_model::$global_config['system_config']['country'];
        $data['currency_id'] = isset($currency_info['id']) ? $currency_info['id'] : '';
        $data['timezone_id'] = Common_model::$global_config['system_config']['default_timezone'];

        $domain_context = $this->get_domain_context();
        $domain = $domain_context['domain'];
        $http_host = $domain_context['http_host'];

        if (!empty($add_array)) {
            $current_date = gmdate('Y-m-d H:i:s');
            $account_generate = common_model::$global_config['system_config']['telephone_as_account'];
            $country_code = $this->common->get_field_name("countrycode", "countrycode", array(
                "id" => $add_array['country_id']
            ));
            $userCaptcha = $this->input->post('userCaptcha');

            $attempt = 1;
            $last_id = 0;
            $cnt_result = array('count' => 0);
            $number = '';
            $insert_array = array();
            $account_arr = array();

            if (!is_numeric($add_array['telephone'])) {
                $data['error']['telephone'] = "<span class='text-danger'>" . gettext('Telephone number is only numeric') . "</span";
            }

            $reseller_id = '0';
            $account_id = $this->signup_model->get_invoice_conf_account_by_domain($domain, $http_host);
            if ((!empty($account_id)) && ($account_id['accountid'] > 0)) {
                $reseller_id = $account_id['accountid'];
                $type = $this->signup_model->get_account_type($account_id['accountid']);
                if (isset($type['type']) && (($type['type'] == -1) || ($type['type'] == 2))) {
                    $reseller_id = 0;
                }
                $account_arr = $this->signup_model->get_active_account_by_id($account_id['accountid']);
            }

            if ($userCaptcha != $this->session->userdata('captchaWord')) {
                $data['error']['captcha_err'] = "<span class='text-danger'>" . gettext("Please enter valid Captcha code") . "</span>";
            } else {
                if (isset($account_id['accountid']) && $account_id['accountid'] > 0 && empty($account_arr)) {
                    $data['error']['account_deleted'] = "<span class='text-danger'>" . gettext("Please contact to administrator") . "</span>";
                } else {
                    if ($account_generate == 0) {
                        $number = $country_code . $add_array['telephone'];
                        $where = array('number' => $number);
                        $cnt_result = $this->signup_model->count_active_accounts_by_number($number);
                    } else {
                        if (isset(common_model::$global_config['system_config']['minimum_accountlength'])) {
                            $number = $country_code . $this->common->find_uniq_rendno_customer_length(common_model::$global_config['system_config']['minimum_accountlength'], common_model::$global_config['system_config']['maximum_accountlength'], 'number', 'accounts');
                        } else {
                            $number = $country_code . $this->common->find_uniq_rendno_customer(common_model::$global_config['system_config']['cardlength'], 'number', 'accounts');
                        }

                        $where = array('email' => $add_array['email']);
                        $cnt_result = $this->signup_model->count_active_accounts_by_email($add_array['email']);
                    }

                    if (isset($cnt_result['count']) && $cnt_result['count'] > 0) {
                        if ($account_generate == 0) {
                            $data['error']['account_number'] = "<span class='text-danger'>" . gettext("Requested number is already exist") . "</span>";
                        } else {
                            $data['error']['account_email'] = "<span class='text-danger'>" . gettext("Requested email is already exist") . "</span>";
                        }
                    } else {
                        $new_account_details = $this->signup_model->get_account_unverified($where);

                        if (!empty($new_account_details)) {
                            $last_id = $new_account_details['id'];
                            if ($new_account_details['retries'] == common_model::$global_config['system_config']['allow_retires']) {
                                $data['error']['account_deleted'] = "<span class='text-danger'>Please contact to administrator</span>";
                            } else {
                                $attemp = ($new_account_details['retries'] + 1);
                                $this->signup_model->update_account_unverified($where, array(
                                    'creation_date' => $current_date,
                                    'retries' => $attemp
                                ));
                            }
                            $insert_array = $new_account_details;
                        } else {
                            $password = $this->common->encode($this->common->generate_password());
                            $insert_array = array(
                                'number' => $number,
                                'reseller_id' => $reseller_id,
                                'telephone' => $add_array['telephone'],
                                'password' => $password,
                                'email' => $add_array['email'],
                                'first_name' => $add_array['first_name'],
                                'last_name' => $add_array['last_name'],
                                'company_name' => $add_array['company_name'],
                                'country_id' => $add_array['country_id'],
                                'currency_id' => $add_array['currency_id'],
                                'timezone_id' => $add_array['timezone_id'],
                                'retries' => $attempt,
                                'otp' => '',
                                'client_ip' => $_SERVER['REMOTE_ADDR'],
                                'creation_date' => $current_date
                            );
                            $last_id = $this->signup_model->insert_account_unverified($insert_array);
                        }
                    }
                }
            }

            if (empty($data['error'])) {
                $verification_by = common_model::$global_config['system_config']['verification_by'];
                $numberlength = common_model::$global_config['system_config']['pinlength'];
                $numberlength = ($numberlength < 6) ? 6 : common_model::$global_config['system_config']['pinlength'];
                $insert_array['otp'] = rand(pow(10, $numberlength - 1), pow(10, $numberlength) - 1);
                $this->signup_model->update_account_unverified_by_id($last_id, array(
                    "otp" => $insert_array['otp']
                ));

                if ($verification_by == '1' || $verification_by == '2') {
                    $this->send_sms($number, 'signup_confirmation', $insert_array);
                }
                if ($verification_by == '0' || $verification_by == '2') {
                    $this->send_mail('0', 'signup_confirmation', $insert_array, $number);
                }

                $signup_key = $this->session->userdata('key');
                $account_number = $this->signup_model->get_account_unverified_brief_by_id($last_id);
                if ($account_generate == 1 && !empty($account_number['number'])) {
                    $number = $account_number['number'];
                }
                $email = isset($account_number['email']) ? $account_number['email'] : '';
                $this->load->helper('cookie');
                set_cookie('post_info', json_encode(array(
                    'number' => $number,
                    'email' => $email,
                    'creation_date' => isset($account_number['creation_date']) ? $account_number['creation_date'] : '',
                    'account_id' => $last_id,
                    'key' => $signup_key
                )), '20');

                redirect(base_url() . "otp_verification/");
                exit();
            } else {
                $data = array_merge($data, $this->generate_captcha_view_data());
                $data['country_id'] = $add_array['country_id'];
                $data['company_name'] = $add_array['company_name'];
                $data['telephone'] = $add_array['telephone'];
                $data['email'] = $add_array['email'];
                $data['first_name'] = $add_array['first_name'];
                $data['last_name'] = $add_array['last_name'];
                $data['currency_id'] = $add_array['currency_id'];
                $data['timezone_id'] = $add_array['timezone_id'];
            }
        } else {
            $data = array_merge($data, $this->generate_captcha_view_data());
            $account = array();
            if ($key != '') {
                $reseller_id = $this->common->decode($this->common->decode_params(trim($key)));
                $data['key'] = $key;
                $account_result = $this->signup_model->get_signup_inactive_account($reseller_id);
                $account = $this->signup_model->get_account_by_id($reseller_id);
                $email = isset($account['email']) ? $account['email'] : '';
                if (!empty($account_result)) {
                    $this->signup_inactive($email);
                }
            }

            $invoice_conf = $this->signup_model->get_invoice_conf_by_domain($domain, $http_host);
            $data['user_logo'] = (isset($invoice_conf['logo']) && $invoice_conf['logo'] != "") ? $invoice_conf['logo'] : "logo.png";
            $data['website_header'] = (isset($invoice_conf['website_title']) && $invoice_conf['website_title'] != "") ? $invoice_conf['website_title'] : "Flux Telecom - Unindo pessoas e negócios Solution";
            $data['website_footer'] = (isset($invoice_conf['website_footer']) && $invoice_conf['website_footer'] != "") ? $invoice_conf['website_footer'] : "Flux Telecom All Rights Reserved.";
            $data['userlevel_logintype'] = (isset($account['type'])) ? $account['type'] : "0";
            $this->session->set_userdata('userlevel_logintype', $data['userlevel_logintype']);
            $this->session->set_userdata('user_logo', $data['user_logo']);
            $this->session->set_userdata('user_header', $data['website_header']);
            $this->session->set_userdata('user_footer', $data['website_footer']);
        }

        if (isset($key) && $key != '') {
            $data['key_unique'] = $key;
        }

        $data['countrycode_array'] = $this->signup_model->get_countrycode_array();
        $terms_and_conditions = $this->signup_model->get_terms_and_conditions();
        foreach ($terms_and_conditions as $term_data) {
            if ($term_data['name'] == 'url') {
                $data['term_and_condition_url'] = $term_data['value'];
            }
            if ($term_data['field_type'] == 'textarea') {
                $data['term_and_condition_text'] = $term_data['value'];
            }
        }

        $this->load->view('view_signup', $data);
    }

    public function otp_verification()
    {
        $this->load->helper('cookie');

        $data = get_cookie('post_info');

        if (!empty($data)) {
            $data = json_decode($data);
            delete_cookie('post_info');
            $this->load->view('view_otp_signup', $data);
        } else {
            redirect(base_url() . "signup/");
        }
    }

    public function check_captcha($str)
    {
        $word = $this->session->userdata('captchaWord');
        if (strcmp(strtoupper($str), strtoupper($word)) == 0) {
            return true;
        } else {
            $this->form_validation->set_message('check_captcha', gettext('Please enter correct words!'));
            return false;
        }
    }

    function terms_check()
    {
        if (isset($_POST['agreeCheck'])) {
            return true;
        }
        $this->form_validation->set_message('terms_check', gettext('THIS IS REQUIRED!'));
        return false;
    }

    function send_sms($number, $template_name, $user_data)
    {
        $domain_context = $this->get_domain_context();
        $invoice_arr = $this->signup_model->get_invoice_conf_with_default($domain_context['domain'], $domain_context['http_host']);
        $template = $this->signup_model->get_default_template($template_name);

        if (empty($template) || $template['is_sms_enable'] != '0') {
            return true;
        }

        $sms_message = $template['sms_template'];
        $sms_api_key = Common_model::$global_config['system_config']['sms_api_key'];
        $sms_secret_key = Common_model::$global_config['system_config']['sms_secret_key'];
        $otp_expire_time = common_model::$global_config['system_config']['otp_expire'];
        $otp_expire_min = ($otp_expire_time > 0) ? $otp_expire_time : 30;

        if ($template_name == 'signup_confirmation' || $template_name == 'forgot_password_confirmation') {
            $sms_message = str_replace('#FIRST_NAME#', $user_data['first_name'], $sms_message);
            $sms_message = str_replace('#OTP#', $user_data['otp'], $sms_message);
            $sms_message = str_replace('#TIME#', $otp_expire_min, $sms_message);
            $sms_message = str_replace('#COMPANY_NAME#', isset($invoice_arr['company_name']) ? $invoice_arr['company_name'] : '', $sms_message);
        }

        $url = 'https://rest.nexmo.com/sms/json?' . http_build_query(array(
            'api_key' => $sms_api_key,
            'api_secret' => $sms_secret_key,
            'to' => $number,
            'from' => 'ABC',
            'text' => "" . $sms_message . "" . $user_data['otp'] . ""
        ));
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);

        $verification_by = common_model::$global_config['system_config']['verification_by'];
        if ($verification_by != "2") {
            $email_array = array(
                'accountid' => '',
                'date' => gmdate('Y-m-d H:i:s'),
                'subject' => $template['subject'],
                'body' => '',
                'from' => isset($invoice_arr['emailaddress']) ? $invoice_arr['emailaddress'] : '',
                'to' => $user_data['email'],
                'status' => "1",
                'attachment' => '',
                'template' => '',
                'reseller_id' => '0',
                'to_number' => $number,
                'sms_body' => $sms_message
            );
            $this->signup_model->insert_mail_detail($email_array);
        }
        return true;
    }

    function send_mail($account_id, $temp_name, $user_data, $number = "")
    {
        $template = $this->signup_model->get_default_template($temp_name);
        if (empty($template) || $template['is_email_enable'] != '0') {
            return true;
        }

        $domain_context = $this->get_domain_context();
        $invoice_arr = $this->signup_model->get_invoice_conf_with_default($domain_context['domain'], $domain_context['http_host']);
        $company_email = isset($invoice_arr['emailaddress']) ? $invoice_arr['emailaddress'] : '';
        $company_website = isset($invoice_arr['website']) ? $invoice_arr['website'] : '';
        $company_name = isset($invoice_arr['company_name']) ? $invoice_arr['company_name'] : '';
        $otp_expire_time = common_model::$global_config['system_config']['otp_expire'];
        $otp_expire_min = ($otp_expire_time > 0) ? $otp_expire_time : 30;
        $TemplateData = array();
        $sms_message = $template['sms_template'];
        $email_template = $template['template'];

        if ($template['name'] == 'signup_confirmation' || $template['name'] == 'forgot_password_confirmation') {
            $sms_message = str_replace('#FIRST_NAME#', $user_data['first_name'], $sms_message);
            $sms_message = str_replace('#OTP#', $user_data['otp'], $sms_message);
            $sms_message = str_replace('#TIME#', $otp_expire_min, $sms_message);
            $sms_message = str_replace('#COMPANY_NAME#', $company_name, $sms_message);
            $TemplateData['subject'] = $template['subject'];
            $email_template = str_replace('#NAME#', $user_data['first_name'] . " " . $user_data['last_name'], $email_template);
            $email_template = str_replace('#OTP#', $user_data['otp'], $email_template);
            $email_template = str_replace('#TIME#', $otp_expire_min, $email_template);
            $email_template = str_replace('#COMPANY_WEBSITE#', $company_website, $email_template);
            $email_template = str_replace('#COMPANY_EMAIL#', $company_email, $email_template);
            $email_template = str_replace('#COMPANY_NAME#', $company_name, $email_template);
        }

        $TemplateData['subject'] = strip_tags($TemplateData['subject']);
        $email_template = strip_tags($email_template);
        $verification_by = common_model::$global_config['system_config']['verification_by'];
        if ($verification_by == "0") {
            $sms_message = "";
        }

        $email_array = array(
            'accountid' => $account_id,
            'date' => gmdate('Y-m-d H:i:s'),
            'subject' => $TemplateData['subject'],
            'body' => $email_template,
            'from' => isset($invoice_arr['emailaddress']) ? $invoice_arr['emailaddress'] : '',
            'to' => $user_data['email'],
            'status' => "1",
            'attachment' => '',
            'template' => '',
            'reseller_id' => '0',
            'to_number' => isset($number) ? $number : '',
            'sms_body' => $sms_message
        );
        $this->signup_model->insert_mail_detail($email_array);
        return true;
    }

    function successpassword()
    {
        $this->load->view('view_successpassword');
    }

    function send_otp($username, $country_id, $account_id, $mg_type)
    {
        $numberlength = common_model::$global_config['system_config']['pinlength'];
        $numberlength = ($numberlength < 6) ? 6 : common_model::$global_config['system_config']['pinlength'];
        $uniq_rendno = rand(pow(10, $numberlength - 1), pow(10, $numberlength) - 1);
        $otp_array = array(
            'otp_number' => $uniq_rendno,
            'user_number' => $username,
            'account_id' => $account_id,
            'status' => 0,
            'country_id' => $country_id,
            'type' => $mg_type,
            'creation_date' => gmdate('Y-m-d H:i:s')
        );
        $this->signup_model->insert_otp_number($otp_array);
    }

    function signup_otp($number, $account_id)
    {
        $data['username'] = $number;
        $data['account_id'] = $account_id;
        $this->load->view('view_otp_signup', $data);
    }

    function signup_inactive($email)
    {
        $data['email'] = $email;
        $this->load->view('view_signup_inactive', $data);
        exit();
    }

    function check_otp()
    {
        $post = $this->input->post();
        $current_date = gmdate('Y-m-d H:i:s');
        $account_array = $this->signup_model->get_account_by_number_and_email($post['number'], $post['email']);
        $account_details = $this->signup_model->get_account_unverified_by_number_and_email($post['number'], $post['email']);
        if (empty($account_details)) {
            echo 'false';
            return;
        }

        $otp_date = strtotime($account_details['creation_date']);
        $insert_date = strtotime($current_date);
        $remain_time = $insert_date - $otp_date;
        $message = 'false';
        $otp_expire_time = common_model::$global_config['system_config']['otp_expire'];
        $otp_expire_min = ($otp_expire_time > 0) ? $otp_expire_time : 30;
        $otp_expire_sec = ($otp_expire_min * 60);
        if ($remain_time <= $otp_expire_sec) {
            $account_details['pin'] = '';
            $pin = Common_model::$global_config['system_config']['generate_pin'];
            if ($pin == 0) {
                $numberlength = common_model::$global_config['system_config']['pinlength'];
                $numberlength = ($numberlength < 6) ? 6 : common_model::$global_config['system_config']['pinlength'];
                $account_details['pin'] = rand(pow(10, $numberlength - 1), pow(10, $numberlength) - 1);
            }
            if (empty($account_array)) {
                $account_details['pricelist_id'] = Common_model::$global_config['system_config']['default_signup_rategroup'];
                if ($account_details['reseller_id'] > 0) {
                    $pricelist_id = $this->signup_model->get_pricelist_by_reseller_id($account_details['reseller_id']);
                    $account_details['pricelist_id'] = (!empty($pricelist_id)) ? $pricelist_id['id'] : $account_details['pricelist_id'];
                }
                $localization_info = $this->signup_model->get_localization_by_country_id($account_details['country_id']);
                $account_details['localization_id'] = !empty($localization_info) ? $localization_info['id'] : Common_model::$global_config['system_config']['localization_id'];
                if ($account_details['otp'] == $post['otp_number']) {
                    $account_insert_array = array(
                        "number" => $account_details['number'],
                        "reseller_id" => $account_details['reseller_id'],
                        "password" => $account_details['password'],
                        "first_name" => $account_details['first_name'],
                        "last_name" => $account_details['last_name'],
                        "company_name" => $account_details['company_name'],
                        "telephone_1" => $account_details['telephone'],
                        "email" => $account_details['email'],
                        "notification_email" => $account_details['email'],
                        "country_id" => $account_details['country_id'],
                        "currency_id" => $account_details['currency_id'],
                        "timezone_id" => $account_details['timezone_id'],
                        "localization_id" => $account_details['localization_id'],
                        "pricelist_id" => $account_details['pricelist_id'],
                        "pin" => $account_details['pin'],
                        "posttoexternal" => "0",
                        "local_call" => Common_model::$global_config['system_config']['local_call'],
                        "maxchannels" => Common_model::$global_config['system_config']['maxchannels'],
                        "cps" => Common_model::$global_config['system_config']['cps'],
                        "type" => "0"
                    );
                    $this->load->library('flux/signup_lib');
                    $this->signup_lib->create_account($account_insert_array);
                    $created_account = $this->signup_model->get_account_by_number_and_email($account_details['number'], $account_details['email']);
                    $last_id = isset($created_account['id']) ? $created_account['id'] : 0;
                    if (!empty($last_id)) {
                        $message = 'success';
                    }
                }
                echo $message;
            } else {
                $account_arr = $this->signup_model->get_account_by_id($post['account_id']);
                $otp_verify = !empty($account_arr) ? $this->signup_model->get_account_unverified_by_number($account_arr["number"]) : array();
                $password = $this->common->encode($this->common->generate_password());
                if (!empty($otp_verify) && $otp_verify['otp'] == $post['otp_number']) {
                    $this->signup_model->update_account_password($post['account_id'], $password);
                    if (!empty($account_arr)) {
                        $sipdevice_array = array(
                            'dir_params' => json_encode(array(
                                "password" => $this->common->decode($password),
                                'vm-enabled' => "true",
                                "vm-password" => $this->common->decode($password),
                                "vm-mailto" => $account_arr['email'],
                                "vm-attach-file" => "true",
                                "vm-keep-local-after-email" => "true",
                                "vm-email-all-messages" => "true"
                            ))
                        );
                        $this->signup_model->update_sip_device_password($post['account_id'], $account_arr['number'], $sipdevice_array);
                    }
                    $account_arr = $this->signup_model->get_account_by_id($post['account_id']);
                    if (!empty($account_arr)) {
                        $account_arr['password'] = $this->common->decode($account_arr['password']);
                        $last_id = $this->common->mail_to_users("reset_password", $account_arr, "", "");
                        if (!empty($last_id)) {
                            $message = 'forgot';
                        }
                    }
                }
                echo $message;
            }
        } else {
            echo $message;
        }
    }

    function resend_otp()
    {
        $post = $this->input->post();
        if (!empty($post)) {
            $acc_id = $post['account_id'];
            $number = $post['number'];
            $email = $post['email'];
            $numberlength = common_model::$global_config['system_config']['pinlength'];
            $numberlength = ($numberlength < 6) ? 6 : common_model::$global_config['system_config']['pinlength'];
            $uniq_rendno = rand(pow(10, $numberlength - 1), pow(10, $numberlength) - 1);
            $otp_array = array(
                'otp' => $uniq_rendno,
                'creation_date' => date('Y-m-d H:i:s')
            );
            $this->signup_model->update_account_unverified_by_number_and_email($number, $email, $otp_array);
            $user_data = $this->signup_model->get_account_unverified_by_number_and_email($number, $email);
            $verification_by = common_model::$global_config['system_config']['verification_by'];
            $account_arr = $this->signup_model->get_account_by_number_and_email($number, $email);
            if ($verification_by == '1' || $verification_by == '2') {
                if (empty($account_arr)) {
                    $this->send_sms($number, 'signup_confirmation', $user_data);
                } else {
                    $this->send_sms($number, 'forgot_password_confirmation', $user_data);
                }
            }
            if ($verification_by == '0' || $verification_by == '2') {
                if (empty($account_arr)) {
                    $this->send_mail($acc_id, 'signup_confirmation', $user_data);
                } else {
                    $this->send_mail($acc_id, 'forgot_password_confirmation', $user_data);
                }
            }
        } else {
            redirect(base_url() . "signup/");
        }
    }

    function forgotpassword()
    {
        $this->load->view('view_forgotpassword');
    }

    function confirmpassword()
    {
        $current_date = gmdate('Y-m-d H:i:s');
        $email = $_POST['email'];
        $number = $_POST['number'];
        $post_data['email'] = $email;
        $post_data['number'] = $number;
        $data['value']['email'] = $email;
        $data['value']['number'] = $number;
        unset($_POST['action']);

        $cnt_result = $this->signup_model->count_accounts_by_email_or_number($email);

        if (!empty($email)) {
            $acountdata = $this->signup_model->get_forgot_password_accounts($email, $number);
            if ($acountdata['count'] > 0) {
                $user_data = $acountdata['row'];
                if ($user_data['deleted'] == 1) {
                    $data['error']['number'] = "<label class='error_label'><span id='error_mail' class='text-danger'>" . gettext("Your account has been deleted. Please contact administrator for more information.") . "</span></label>";
                    $this->load->view('view_forgotpassword', $data);
                    exit();
                }
                if ($user_data['status'] > 0) {
                    $data['error']['number'] = "<label class='error_label'><span id='error_mail' class='text-danger'>" . gettext("Your account is inactive. Please contact administrator for more information.") . "</span></label>";
                    $this->load->view('view_forgotpassword', $data);
                    exit();
                }
            }
            if ($acountdata['count'] == 0 && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                if ((!filter_var($email, FILTER_VALIDATE_EMAIL)) && (!filter_var($number, FILTER_SANITIZE_NUMBER_INT))) {
                    $data['error']['email'] = "<label class='error_label'><span id='error_mail' class='text-danger'>" . gettext("Please enter proper Email.") . "</span></label>";
                    $data['error']['number'] = "<label class='error_label'><span id='error_mail' class='text-danger'>" . gettext("Please enter proper Username") . "</span></label>";
                    $this->load->view('view_forgotpassword', $data);
                } else {
                    $data['error']['email'] = "<label class='error_label'><span id='error_mail' class='text-danger'>" . gettext("This Username or Email is not valid.") . "</span></label>";
                    $this->load->view('view_forgotpassword', $data);
                }
            } else if ($acountdata['count'] == 0) {
                $data['error']['number'] = "<label class='error_label'><span id='error_mail' class='text-danger'>" . gettext("Please enter proper Username .") . "</span></label>";
                $this->load->view('view_forgotpassword', $data);
            } else {
                $user_data = $acountdata['row'];
                $numberlength = common_model::$global_config['system_config']['pinlength'];
                $numberlength = ($numberlength < 6) ? 6 : common_model::$global_config['system_config']['pinlength'];
                $user_data['otp'] = rand(pow(10, $numberlength - 1), pow(10, $numberlength) - 1);
                $account_unverified_array = $this->signup_model->get_account_unverified_by_number($user_data['number']);
                if (empty($account_unverified_array)) {
                    $insert_array = array(
                        'number' => $user_data['number'],
                        'reseller_id' => $user_data['reseller_id'],
                        'telephone' => $user_data['telephone_1'],
                        'password' => $user_data['password'],
                        'email' => $user_data['email'],
                        'first_name' => $user_data['first_name'],
                        'last_name' => $user_data['last_name'],
                        'company_name' => $user_data['company_name'],
                        'country_id' => $user_data['country_id'],
                        'currency_id' => $user_data['currency_id'],
                        'timezone_id' => $user_data['timezone_id'],
                        'retries' => '1',
                        'otp' => '',
                        'client_ip' => $_SERVER['REMOTE_ADDR'],
                        'creation_date' => $current_date
                    );
                    $this->signup_model->insert_account_unverified($insert_array);
                }

                $this->signup_model->update_account_pass_link_status($user_data['email'], 1);
                $this->signup_model->update_account_unverified_by_number_or_email($email, array(
                    "otp" => $user_data['otp'],
                    "creation_date" => $current_date
                ));
                $this->send_sms($user_data['number'], 'forgot_password_confirmation', $user_data);
                $this->send_mail($user_data['id'], 'forgot_password_confirmation', $user_data, $number);
                $post_data['account_id'] = $user_data['id'];
                $post_data['creation_date'] = $current_date;
                $this->load->view('view_otp_signup', $post_data);
            }
        } else {
            redirect(base_url());
        }
    }
}
?>
