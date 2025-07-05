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

    function partners_endpoints_list()
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
        $data['page_title'] = gettext('Partners Endpoints');
        $data['search_flag'] = true;
        $this->session->set_userdata('advance_search', 0);
        $data['grid_fields'] = $this->api_endpoints_form->build_partners_endpoints_list_for_admin();
        $data["grid_buttons"] = $this->api_endpoints_form->build_partners_grid_buttons();
        $data['form_search'] = $this->form->build_serach_form($this->api_endpoints_form->get_partners_endpoints_search_form());
        $this->load->view('view_partners_endpoints_list', $data);
    }
    
    function partners_endpoints_list_json()
    {
        $json_data = array();
        $count_all = $this->api_endpoints_model->getpartners_endpoints_list(false);
        $paging_data = $this->form->load_grid_config($count_all, $_GET['rp'], $_GET['page']);
        $json_data = $paging_data["json_paging"];
        $query = $this->api_endpoints_model->getpartners_endpoints_list(true, $paging_data["paging"]["start"], $paging_data["paging"]["page_no"]);
        $grid_fields = json_decode($this->api_endpoints_form->build_partners_endpoints_list_for_admin());
        $json_data['rows'] = $this->form->build_grid($query, $grid_fields);
        echo json_encode($json_data);
    }
    
    
    function partners_list()
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
        $data['page_title'] = gettext('Partners List');
        $data['search_flag'] = true;
        $this->session->set_userdata('advance_search', 0);
        $data['grid_fields'] = $this->api_endpoints_form->build_partners_list_for_admin();
        $data["grid_buttons"] = $this->api_endpoints_form->build_partner_grid_buttons();
        $data['form_search'] = $this->form->build_serach_form($this->api_endpoints_form->get_partners_search_form());
        $this->load->view('view_partners_list', $data);
    }
    
    function partners_list_json()
    {
        $json_data = array();
        $count_all = $this->api_endpoints_model->getpartners_list(false);
        $paging_data = $this->form->load_grid_config($count_all, $_GET['rp'], $_GET['page']);
        $json_data = $paging_data["json_paging"];
        $query = $this->api_endpoints_model->getpartners_list(true, $paging_data["paging"]["start"], $paging_data["paging"]["page_no"]);
        $grid_fields = json_decode($this->api_endpoints_form->build_partners_list_for_admin());
        $json_data['rows'] = $this->form->build_grid($query, $grid_fields);
        echo json_encode($json_data);
    }


    function partners_list_search()
    {
        $ajax_search = $this->input->post('ajax_search', 0);
    
        if ($this->input->post('advance_search', TRUE) == 1) {
            $this->session->set_userdata('advance_search', $this->input->post('advance_search'));
            $action = $this->input->post();
            unset($action['action']);
            unset($action['advance_search']);
            $this->session->set_userdata('partners_list_search', $action);
        }
        if (@$ajax_search != 1) {
            redirect(base_url() . 'api_endpoints/partners_list/');
        }
    }
    
    function partners_list_clearsearchfilter()
    {
        $this->session->set_userdata('advance_search', 0);
        $this->session->set_userdata('account_search', "");
    }

    
    function partners_endpoints_list_search()
    {
        $ajax_search = $this->input->post('ajax_search', 0);
    
        if ($this->input->post('advance_search', TRUE) == 1) {
            $this->session->set_userdata('advance_search', $this->input->post('advance_search'));
            $action = $this->input->post();
            unset($action['action']);
            unset($action['advance_search']);
            $this->session->set_userdata('partners_endpoints_list_search', $action);
        }
        if (@$ajax_search != 1) {
            redirect(base_url() . 'api_endpoints/partners_endpoints_list/');
        }
    }
    
    function partners_endpoints_list_clearsearchfilter()
    {
        $this->session->set_userdata('advance_search', 0);
        $this->session->set_userdata('account_search', "");
    }

	function partners_add($type = "")
	{
		$data['username'] = $this->session->userdata('user_name');
		$data['flag'] = 'create';
		$data['page_title'] = gettext('Create Partner');
		$data['form'] = $this->form->build_form($this->api_endpoints_form->get_partners_form_fields(), '');
		$this->load->view('view_partners_add_edit', $data);
	}

	function partners_edit($edit_id = '')
	{
		$accountinfo = $this->session->userdata('accountinfo');
		if ($accountinfo['type'] == - 1 || $accountinfo['type'] == 2) {
			$data['page_title'] = gettext('Edit Partner');
			$where = array(
				'id' => $edit_id
			);
			$account = $this->db_model->getSelect("*", "api_partners", $where);
			foreach ($account->result_array() as $key => $value) {
				$edit_data = $value;
			}
			$data['form'] = $this->form->build_form($this->api_endpoints_form->get_partners_form_fields($edit_id), $edit_data);
			$this->load->view('view_partners_add_edit', $data);
		} else {
			$this->session->set_flashdata('flux_notification', gettext('Permission Denied!'));
			redirect(base_url() . 'api_endpoints/partners_list/');
			exit();
		}
	}

	function partners_save()
	{
		$add_array = $this->input->post();
		$data['form'] = $this->form->build_form($this->api_endpoints_form->get_partners_form_fields($add_array['id']), $add_array);
		if ($add_array['id'] != '') {
			$data['page_title'] = gettext('Edit Partner');
			if ($this->form_validation->run() == FALSE) {
				$data['validation_errors'] = validation_errors();
				echo $data['validation_errors'];
				exit();
			} 
			else {
//                $data['product_rate_group'] = $this->db_model->build_dropdown("id,name", "pricelists", "", $where_arr);
				$this->api_endpoints_model->edit_partners($add_array, $add_array['id']);
				echo json_encode(array(
					"SUCCESS" => $add_array["partner_name"] .' '. gettext("Partner Updated Successfully!")
				));
				exit();
			}
		} 
		else {
			$data['page_title'] = gettext('Partner Details');
			if ($this->form_validation->run() == FALSE) {
				$data['validation_errors'] = validation_errors();
				echo $data['validation_errors'];
				exit();
			} else {
				$this->api_endpoints_model->add_partners($add_array);
				echo json_encode(array(
					"SUCCESS" => $add_array["partner_name"] .' '. gettext("Partner Added Successfully!")
				));
				exit();
			}
		}
	}

	function partners_delete_multiple()
	{
	    $ids = $this->input->post("selected_ids", true);
	    $where = "id IN ($ids)";
	    $this->db->where($where);
	    echo $this->db->delete("api_partners");
	}

    function partners_endpoints_add($type = "")
    {
        $data['username'] = $this->session->userdata('user_name');
        $data['flag'] = 'create';
        $data['page_title'] = gettext('Create Partner Endpoint');
        $data['form'] = $this->form->build_form($this->api_endpoints_form->get_partners_endpoints_form_fields(), '');
        $this->load->view('view_partners_endpoints_add_edit', $data);
    }

    function partners_endpoints_edit($edit_id = '')
    {
        $accountinfo = $this->session->userdata('accountinfo');
        if ($accountinfo['type'] == - 1 || $accountinfo['type'] == 2) {
            $data['page_title'] = gettext('Edit Partner Endpoint');
            $where = array(
                'id' => $edit_id
            );
            $account = $this->db_model->getSelect("*", "endpoints", $where);
            foreach ($account->result_array() as $key => $value) {
                $edit_data = $value;
            }
            $data['form'] = $this->form->build_form($this->api_endpoints_form->get_partners_endpoints_form_fields($edit_id), $edit_data);
            $this->load->view('view_partners_endpoints_add_edit', $data);
        } else {
            $this->session->set_flashdata('flux_notification', gettext('Permission Denied!'));
            redirect(base_url() . 'api_endpoints/partners_endpoints_list/');
            exit();
        }
    }


	function partners_endpoints_save()
	{
		$add_array = $this->input->post();
		$data['form'] = $this->form->build_form($this->api_endpoints_form->get_partners_endpoints_form_fields($add_array['id']), $add_array);
		if ($add_array['id'] != '') {
			$data['page_title'] = gettext('Edit Partner Endpoint');
			if ($this->form_validation->run() == FALSE) {
				$data['validation_errors'] = validation_errors();
				echo $data['validation_errors'];
				exit();
			} 
			else {
//                $data['product_rate_group'] = $this->db_model->build_dropdown("id,name", "pricelists", "", $where_arr);
				$this->api_endpoints_model->edit_partners_endpoints($add_array, $add_array['id']);
				echo json_encode(array(
					"SUCCESS" => $add_array["nome"] .' '. gettext("Enpoint Updated Successfully!")
				));
				exit();
			}
		} 
		else {
			$data['page_title'] = gettext('Partner Endpoint Details');
			if ($this->form_validation->run() == FALSE) {
				$data['validation_errors'] = validation_errors();
				echo $data['validation_errors'];
				exit();
			} else {
				$this->api_endpoints_model->add_partners_endpoints($add_array);
				echo json_encode(array(
					"SUCCESS" => $add_array["nome"] .' '. gettext("Enpoint Added Successfully!")
				));
				exit();
			}
		}
	}

	function partners_endpoints_delete_multiple()
	{
	    $ids = $this->input->post("selected_ids", true);
	    $where = "id IN ($ids)";
	    $this->db->where($where);
	    echo $this->db->delete("endpoints");
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
            } 
            else {
//                $data['product_rate_group'] = $this->db_model->build_dropdown("id,name", "pricelists", "", $where_arr);
                $this->api_endpoints_model->edit_api_endpoints($add_array, $add_array['id']);
				$this->session->set_flashdata('flux_errormsg', $add_array["endpoint_name"].' '.gettext('Enpoint Updated Successfully!'));
				redirect(base_url().'api_endpoints/api_endpoints_list/');
				exit();
            }
        } 
        else {
            $data['page_title'] = gettext('Endpoint Details');
            if ($this->form_validation->run() == FALSE) {
                $data['validation_errors'] = validation_errors();
                echo $data['validation_errors'];
                exit();
            } else {
                $this->api_endpoints_model->add_api_endpoints($add_array);
				$this->session->set_flashdata('flux_errormsg', $add_array["endpoint_name"].' '.gettext('Enpoint Added Successfully!'));
				redirect(base_url().'api_endpoints/api_endpoints_list/');
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
    
    function set_force_endpoint($endpointid, $partnerid)
    {
        foreach ($partnerid as $id) {
            $endpoint_arr = array(
                "partner_id" => $id,
                "endpoint_id" => $endpointid
            );
            $this->db->insert("endpoints", $endpoint_arr);
        }
    }
    
    function endpoint_set_force_partner($add_array, $endpoints_id)
    {
        $partner_id = explode(",", $add_array['partner_id']);
        $partner_count = count($partner_id);
        foreach ($partner_id as $key => $value) {
            if ($value != 0) {
                $insert_array = array(
                    "endpoint_id" => $endpoints_id,
//                    "pricelist_id" => 0,
                    "partner_id" => $value
//                    "percentage" => $percentage[$key]
                );
                $this->db->insert("endpoints", $insert_array);
            }
        }
    }
    
}
?>
