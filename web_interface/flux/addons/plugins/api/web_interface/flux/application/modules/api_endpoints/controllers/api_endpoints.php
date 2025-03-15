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
class api_endpoints extends CI_Controller
{

    function __construct()
    {
        parent::__construct();
        $this->load->helper('template_inheritance');
        $this->load->library('session');
        $this->load->library("api_endpoints_form");
        $this->load->library('flux/form');
        $this->load->model('api_endpoints_model');
        $this->load->library('csvreader');
        $this->load->library('FLUX_Sms');
        
        if ($this->session->userdata('user_login') == FALSE)
            redirect(base_url() . '/flux/login');
    }

    function api_endpoints_list()
    {
        $accountinfo = $this->session->userdata("accountinfo");
        $account_arr = (array) $this->db->get_where("accounts", array(
            "id" => $accountinfo['id'],
            "deleted" => "0",
            "status" => "0"
        ))->first_row();
        if (empty($account_arr)) {
            $this->session->sess_destroy();
            $this->load->helper('cookie');
            set_cookie('post_info', json_encode("text"), '20');
            redirect(base_url() . "login/");
        }
        $data['username'] = $this->session->userdata('user_name');
        $data['page_title'] = gettext('API Endpoints');
        $data['search_flag'] = true;
        $this->session->set_userdata('advance_search', 0);
        $data['grid_fields'] = $this->api_endpoints_form->build_api_endpoints_list_for_admin();
        $data["grid_buttons"] = $this->api_endpoints_form->build_grid_buttons();
        $data['form_search'] = $this->form->build_serach_form($this->api_endpoints_form->get_api_endpoints_search_form());
        $this->load->view('view_api_endpoints_list', $data);
    }

    function api_endpoints_list_json()
    {
        $json_data = array();
        $count_all = $this->api_endpoints_model->getapi_endpoints_list(false);
        $paging_data = $this->form->load_grid_config($count_all, $_GET['rp'], $_GET['page']);
        $json_data = $paging_data["json_paging"];
        $query = $this->api_endpoints_model->getapi_endpoints_list(true, $paging_data["paging"]["start"], $paging_data["paging"]["page_no"]);
        $grid_fields = json_decode($this->api_endpoints_form->build_api_endpoints_list_for_admin());
        $json_data['rows'] = $this->form->build_grid($query, $grid_fields);
        echo json_encode($json_data);
    }

    function api_endpoints_add($type = "")
    {
        $data['username'] = $this->session->userdata('user_name');
        $data['flag'] = 'create';
        $data['page_title'] = gettext('Create API Endpoint');
        $data['form'] = $this->form->build_form($this->api_endpoints_form->get_api_endpoints_form_fields(), '');
        $this->load->view('view_api_endpoints_add_edit', $data);
    }

    function api_endpoints_edit($edit_id = '')
    {
        $accountinfo = $this->session->userdata('accountinfo');
        if ($accountinfo['type'] == - 1 || $accountinfo['type'] == 2) {
            $data['page_title'] = gettext('Edit API Endpoint');
            $where = array(
                'id' => $edit_id
            );
            $account = $this->db_model->getSelect("*", "api_endpoints", $where);
            foreach ($account->result_array() as $key => $value) {
                $edit_data = $value;
            }
            $data['form'] = $this->form->build_form($this->api_endpoints_form->get_api_endpoints_form_fields($edit_id), $edit_data);
            $this->load->view('view_api_endpoints_add_edit', $data);
        } else {
            $this->session->set_flashdata('flux_notification', gettext('Permission Denied!'));
            redirect(base_url() . 'api_endpoints/api_endpoints_list/');
            exit();
        }
    }

    function api_endpoints_save()
    {
        $add_array = $this->input->post();
        $data['form'] = $this->form->build_form($this->api_endpoints_form->get_api_endpoints_form_fields($add_array['id']), $add_array);
        if ($add_array['id'] != '') {
            $data['page_title'] = gettext('Edit API Endpoint');
            if ($this->form_validation->run() == FALSE) {
                $data['validation_errors'] = validation_errors();
                echo $data['validation_errors'];
                exit();
            } else {
                $this->api_endpoints_model->edit_api_endpoints($add_array, $add_array['id']);
                echo json_encode(array(
                    "SUCCESS" => $add_array["endpoint_name"] .' '. gettext("Enpoint Updated Successfully!")
                ));
                exit();
            }
        } else {
            $data['page_title'] = gettext('Endpoint Details');
            if ($this->form_validation->run() == FALSE) {
                $data['validation_errors'] = validation_errors();
                echo $data['validation_errors'];
                exit();
            } else {
                $this->api_endpoints_model->add_api_endpoints($add_array);
                echo json_encode(array(
                    "SUCCESS" => $add_array["endpoint_name"] .' '. gettext("Enpoint Added Successfully!")
                ));
                exit();
            }
        }
    }

    function api_endpoints_list_search()
    {
        $ajax_search = $this->input->post('ajax_search', 0);

        if ($this->input->post('advance_search', TRUE) == 1) {
            $this->session->set_userdata('advance_search', $this->input->post('advance_search'));
            $action = $this->input->post();
            unset($action['action']);
            unset($action['advance_search']);
            $this->session->set_userdata('api_endpoints_list_search', $action);
        }
        if (@$ajax_search != 1) {
            redirect(base_url() . 'api_endpoints/api_endpoints_list/');
        }
    }

    function api_endpoints_list_clearsearchfilter()
    {
        $this->session->set_userdata('advance_search', 0);
        $this->session->set_userdata('account_search', "");
    }

    function api_endpoints_remove($id)
    {
        $this->api_endpoints_model->remove_accessnumber($id);
        $this->db->delete("api_endpoints", array(
            "access_number" => $id
        ));
        $this->session->set_flashdata('flux_notification', gettext('Accessnumber Removed Successfully!'));
        redirect(base_url() . 'api_endpoints/api_endpoints_list/');
    }

    function api_endpoints_delete_multiple()
    {
        $ids = $this->input->post("selected_ids", true);
        $where = "id IN ($ids)";
        $this->db->where($where);
        echo $this->db->delete("api_endpoints");
    }

    function api_endpoints_export_data_xls()
    {
        $account_info = $accountinfo = $this->session->userdata('accountinfo');
        $query = $this->api_endpoints_model->getapi_endpoints_list(true, '', false);
        ob_clean();
        if (($account_info['type'] == - 1) || ($account_info['type'] == 2)) {
            $outbound_array[] = array(
                gettext("API Endpoints"),
                gettext("Country"),
                gettext("Created Date"),
                gettext("Modified Date"),
                gettext("Status")
            );
            if ($query->num_rows() > 0) {
                foreach ($query->result_array() as $row) {
                    $outbound_array[] = array(
                        $row['access_number'],
                        $this->common->get_field_name("country", "countrycode", $row['country_id']),
                        $this->common->convert_GMT_to('', '', $row['creation_date']),
                        $this->common->convert_GMT_to('', '', $row['last_modified_date']),
                        $this->common->get_status('export', '', $row['status'])
                    );
                }
            }
        } else {
            $outbound_array[] = array(
                gettext("API Endpoints"),
                gettext("Country"),
                gettext("Created Date"),
                gettext("Modified Date")
            );
            if ($query->num_rows() > 0) {
                foreach ($query->result_array() as $row) {
                    $outbound_array[] = array(
                        $row['access_number'],
                        $this->common->get_field_name("country", "countrycode", $row['country_id']),
                        $this->common->convert_GMT_to('', '', $row['creation_date']),
                        $this->common->convert_GMT_to('', '', $row['last_modified_date'])
                    );
                }
            }
        }
        $this->load->helper('csv');
        array_to_csv($outbound_array, 'Access_number_' . date("Y-m-d") . '.csv');
    }

    function api_endpoints_download_sample_file($file_name)
    {
        $this->load->helper('download');
        $full_path = base_url() . "assets/Rates_File/" . $file_name . ".csv";
        ob_clean();
        $arrContextOptions = array(
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false
            )
        );
        $file = file_get_contents($full_path, false, stream_context_create($arrContextOptions));
        force_download(gettext("samplefile.csv"), $file);
    }

    function _push_file($path, $name)
    {
        // make sure it's a file before doing anything!
        if (is_file($path)) {
            // required for IE
            if (ini_get('zlib.output_compression')) {
                ini_set('zlib.output_compression', 'Off');
            }

            // get the file mime type using the file extension
            $this->load->helper('file');

            $mime = get_mime_by_extension($path);

            // Build the headers to push out the file properly.
            header('Pragma: public'); // required
            header('Expires: 0'); // no cache
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($path)) . ' GMT');
            header('Cache-Control: private', false);
            header('Content-Type: ' . $mime); // Add the mime type from Code igniter.
            header('Content-Disposition: attachment; filename="' . basename($name) . '"'); // Add the file name
            header('Content-Transfer-Encoding: binary');
            header('Content-Length: ' . filesize($path)); // provide file size
            header('Connection: close');
            readfile($path); // push it out
            exit();
        }
    }

    function api_endpoints_import()
    {
        $data['page_title'] = gettext('Import API Endpoints');
        $this->session->set_userdata('import_api_endpoints_rate_csv', "");
        $error_data = $this->session->userdata('import_api_endpoints_csv_error');
        $full_path = $this->config->item('rates-file-path');
        if (file_exists($full_path . $error_data) && $error_data != "") {
            unlink($full_path . $error_data);
            $this->session->set_userdata('import_api_endpoints_csv_error', "");
        }
        $accountinfo = $this->session->userdata('accountinfo');
        $data['fields'] = gettext("API Endpoints,Country");
        $this->load->view('view_import_accessnumber', $data);
    }

    function api_endpoints_preview_file()
    {
        $data['page_title'] = gettext('Import Accessnumber');
        $config_api_endpoints_array = $this->config->item('Accessnumber-rates-field');
        $accountinfo = $this->session->userdata('accountinfo');
        foreach ($config_api_endpoints_array as $key => $value) {
            $api_endpoints_fields_array[$key] = $value;
        }
        $check_header = $this->input->post('check_header', true);
        $invalid_flag = false;
        if (isset($_FILES['accessnumberimport']['name']) && $_FILES['accessnumberimport']['name'] != "") {
            list ($txt, $ext) = explode(".", $_FILES['accessnumberimport']['name']);
            if ($ext == "csv" && $_FILES["accessnumberimport"]['size'] > 0) {
                $error = $_FILES['accessnumberimport']['error'];
                if ($error == 0) {
                    $uploadedFile = $_FILES["accessnumberimport"]["tmp_name"];
                    $full_path = $this->config->item('rates-file-path');
                    $actual_file_name = "FluxSBC-Accessnumber" . date("Y-m-d H:i:s") . "." . $ext;
                    if (move_uploaded_file($uploadedFile, $full_path . $actual_file_name)) {
                        $data['page_title'] = gettext('Import Accessnumber Preview');
                        $data['csv_tmp_data'] = $this->csvreader->parse_file($full_path . $actual_file_name, $api_endpoints_fields_array, $check_header);
                        $data['check_header'] = $check_header;
                        $this->session->set_userdata('import_api_endpoints_rate_csv', $actual_file_name);
                    } else {
                        $data['error'] = gettext("File Uploading Fail Please Try Again");
                    }
                }
            } else {
                $data['fields'] = gettext("API Endpoints,Country");
                $data['error'] = gettext("Invalid file format : Only CSV file allows to import records(Can't import empty file)");
            }
        } else {
            $invalid_flag = true;
        }
        if ($invalid_flag) {
            $data['fields'] = gettext("API Endpoints,Country");
            $str = '';
            if (empty($_FILES['accessnumberimport']['name'])) {
                $str .= '<br/>'.gettext('Please Select File.');
            }
            $data['error'] = $str;
        }
        $this->load->view('view_import_accessnumber', $data);
    }

    function data_validate($csvdata)
    {
        $str = null;
        $alpha_regex = "/^[a-z ,.'-]+$/i";
        $alpha_numeric_regex = "/^[a-z0-9 ,.'-]+$/i";
        $email_regex = "/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/";
        $str .= preg_match("/^([0-9])+$/i", $csvdata['access_number']) ? null : 'API Endpoints,';
        $str = rtrim($str, ',');
        if (! $str) {
            $str .= ! empty($csvdata['access_number']) && is_numeric($csvdata['access_number']) && ($csvdata['access_number'] > 0) ? null : (empty($csvdata['access_number']) ? null : 'API Endpoints,');
            $str .= $csvdata['country_id'] != '' ? null : 'Country,';
            if ($str) {
                $str = rtrim($str, ',');
                $error_field = explode(',', $str);
                $count = count($error_field);
                $str .= $count > 1 ? ' are not valid' : ' is not Valid';
                return $str;
            } else {
                return false;
            }
        } else {
            $str = rtrim($str, ',');
            $error_field = explode(',', $str);
            $count = count($error_field);
            $str .= $count > 1 ? ' are required' : ' is Required';
            return $str;
        }
    }

    function api_endpoints_import_file($check_header = false)
    {
        $new_final_arr = array();
        $invalid_array = array();
        $new_final_arr_key = array(
            'API Endpoints' => 'access_number',
            'Country' => 'country_id'
        );
        $accountinfo = $this->session->userdata('accountinfo');
        $reseller_id = $accountinfo['type'] == 1 ? $accountinfo['id'] : 0;
        $full_path = $this->config->item('rates-file-path');
        $api_endpoints_file_name = $this->session->userdata('import_api_endpoints_rate_csv');
        $csv_tmp_data = $this->csvreader->parse_file($full_path . $api_endpoints_file_name, $new_final_arr_key, $check_header);
        $flag = false;
        $i = 0;
        $number_arr = array();
        foreach ($csv_tmp_data as $key => $csv_data) {
            if (isset($csv_data['access_number']) && $csv_data['access_number'] != '' && $i != 0) {
                $str = null;
                $str = $this->data_validate($csv_data);
                if ($str != "") {
                    $invalid_array[$i] = $csv_data;
                    $invalid_array[$i]['error'] = $str;
                } else {

                    if (! in_array($csv_data['access_number'], $number_arr)) {
                        $number_count = $this->db_model->countQuery('id', 'api_endpoints', array(
                            'access_number' => $csv_data['access_number']
                        ));
                        if ($number_count > 0) {
                            $invalid_array[$i] = $csv_data;
                            $invalid_array[$i]['error'] = gettext('Duplicate api_endpoints found from database');
                        } else {

                            $csv_data['creation_date'] = gmdate('Y-m-d H:i:s');
                            $csv_data['last_modified_date'] = gmdate('Y-m-d H:i:s');
                            $csv_data['status'] = 0;
                            $csv_data['country_id'] = $this->common->get_field_name('id', 'countrycode', array(
                                "country" => strtoupper($csv_data['country_id'])
                            ));
                            $new_final_arr[$i] = $csv_data;
                        }
                    } else {
                        $invalid_array[$i] = $csv_data;
                        $invalid_array[$i]['error'] = gettext('Duplicate api_endpoints found from import file.');
                    }
                }
                $number_arr[] = $csv_data['access_number'];
            }
            $i ++;
        }
        if (! empty($new_final_arr)) {
            $result = $this->api_endpoints_model->bulk_insert_accessnumber($new_final_arr);
        }
        unlink($full_path . $api_endpoints_file_name);
        $count = count($invalid_array);
        if ($count > 0) {
            $session_id = "-1";
            $fp = fopen($full_path . $session_id . '.csv', 'w');
            foreach ($new_final_arr_key as $key => $value) {
                $custom_array[0][$key] = ucfirst($key);
            }
            $custom_array[0]['error'] = gettext("Error");
            $invalid_array = array_merge($custom_array, $invalid_array);
            foreach ($invalid_array as $err_data) {
                fputcsv($fp, $err_data);
            }
            fclose($fp);
            $this->session->set_userdata('import_api_endpoints_csv_error', $session_id . ".csv");
            $data["error"] = $invalid_array;
            $data['import_record_count'] = count($new_final_arr);
            $data['failure_count'] = count($invalid_array) - 1;
            $data['page_title'] = gettext('Accessnumber Import Error');
            $this->load->view('view_import_error', $data);
        } else {

            $this->session->set_flashdata('flux_errormsg', gettext('Total').' ' . count($new_final_arr) .' '.gettext('AccessNumber Imported Successfully!'));
            redirect(base_url() . "api_endpoints/api_endpoints_list/");
        }
    }

    function api_endpoints_error_download()
    {
        $this->load->helper('download');
        $error_data = $this->session->userdata('import_api_endpoints_csv_error');
        $full_path = $this->config->item('rates-file-path');
        $data = file_get_contents($full_path . $error_data);
        force_download("error_api_endpoints_rates.csv", $data);
    }
}
?>
