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
class Products extends MX_Controller {
	var $ProductCategory;	
	function __construct() {
		parent::__construct ();
		$this->load->library ( 'session' );	
		$this->load->library ( 'product_form' );
		$this->load->library ( 'flux/form' );
		$this->load->model ( 'product_model' );
		$this->load->library('form_validation');
		$this->load->library ( 'flux/order' );
		$this->load->library ( 'did_lib' );
		$this->load->library("flux_log");
		$this->load->model ( 'Flux_common' );
		if ($this->session->userdata ( 'user_login' ) == FALSE)
			redirect ( base_url () . '/flux/login' );
		$accountinfo=$this->session->userdata('accountinfo');
		$paypal_permission =(array)$this->db_model->getSelect ( "paypal_permission", "accounts", array ('id' => $accountinfo ['id']) )->first_row();
		
		if($accountinfo['type'] == 0 && $paypal_permission['paypal_permission'] == 1){
				$this->session->set_flashdata('flux_danger_alert',gettext("Your TopUp permission has been disabled please contact to your administrator"));
				redirect ( base_url () . 'dashboard' );
		}
		$this->get_product_category();
	}

	function get_product_category(){
		$this->ProductCategory = $this->product_model->get_product_category_dropdown($this->session->userdata('logintype'));
	}
	function products_list() { 
		$data['accountinfo']  = $this->session->userdata ( "accountinfo" );
		$data ['search_flag'] = true;
		$data ['page_title']  = gettext ('Products');
		$this->session->set_userdata ( 'product_list_search', 0 );
		unset($_POST['productlist']);
		if ($this->session->userdata ( 'logintype' ) == '-1' || $this->session->userdata ( 'logintype' ) == '2' ){
			$data ['page_title']  = gettext ('Products');	
		}
		else{
			$data ['page_title']  = gettext ('My Products');
		}
		$data ['grid_fields']  = $this->product_form->build_product_list_for_admin ();
		$data ["grid_buttons"] = $this->product_form->build_grid_buttons ();
		$data ['form_search']  = $this->form->build_serach_form ( $this->product_form->get_product_search_form() );

		$this->load->view ( 'view_product_list', $data );
	}
	function products_list_json() {
		$json_data = array ();
		$count_all = $this->product_model->getreseller_products_list( false );
		$paging_data = $this->form->load_grid_config ( $count_all, $_GET ['rp'], $_GET ['page'] );

		$json_data = $paging_data ["json_paging"];
		$query = $this->product_model->getreseller_products_list( true, $paging_data ["paging"] ["start"], $paging_data ["paging"] ["page_no"] );
		$grid_fields = json_decode ( $this->product_form->build_product_list_for_admin() );
		$json_data ['rows'] = $this->form->build_grid ( $query, $grid_fields );

		echo json_encode ( $json_data );
	}
	function products_listing() {
		$data['accountinfo']  = $this->session->userdata ( "accountinfo" );   
		$data ['page_title']  = gettext ('Parent Products');
		$data ['search_flag'] = true;
		$this->session->set_userdata ( 'product_list_search', 0 );
		$data ['grid_fields']  = $this->product_form->build_product_list_for_admin_products();
		$data ['form_search']  = $this->form->build_serach_form ( $this->product_form->get_product_listing_search_form() );
		$this->load->view ( 'view_productlisting', $data );
	}
	function products_listing_json() {
		$json_data = array ();
		$count_res = $this->product_model->getproduct_list( false );
		$paging_data = $this->form->load_grid_config ( $count_res, $_GET ['rp'], $_GET ['page'] );
		$json_data = $paging_data ["json_paging"];
		$query = $this->product_model->getproduct_list( true, $paging_data ["paging"] ["start"], $paging_data ["paging"] ["page_no"] );
		$grid_fields = json_decode ( $this->product_form->build_product_list_for_admin_products() );
		$json_data ['rows'] = $this->form->build_grid ( $query, $grid_fields );
		echo json_encode ( $json_data );
	}
	function products_add($category="") {
		$data ['page_title'] = gettext ( 'Create Product' );
		$data['product_category'] = $this->ProductCategory;
		$accountinfo = $this->session->userdata ( "accountinfo" );
		$reseller_id = $accountinfo ['type'] == 1 ? $accountinfo ['id'] : 0;
		$data['currency'] = $this->common->get_field_name("currency","currency",array("id"=>$accountinfo['currency_id'])); 
		$where_arr= array("reseller_id"=>$reseller_id,"status"=>0);
		$data['product_rate_group'] = $this->db_model->build_dropdown("id,name", "pricelists","where_arr", $where_arr);
		$accountinfo = $this->session->userdata ( 'accountinfo' );
		if(isset($_POST['product_category']) && $_POST['product_category'] != ''){
			$data['product_name'] = isset($_POST['product_name'])?$_POST['product_name']:'';
			$category = $data['product_category'][$_POST['product_category']];

			if ($category == "Pacote"){
				$category = "Package";
			}
			
			$data['add_array'] = $_POST;
			$ProductDataLog = array(
				"function" => 'products_add',
				"data" => $data['add_array'],
				"product_name" => $data['product_name'],
				"product_category" => $category,
			);
			$this->load->view ( 'view_product_add_'.strtolower($category), $data);
		}else{
			if ($this->session->userdata ( 'logintype' ) == 1 || $this->session->userdata ( 'logintype' ) == 5){
				$this->load->view ( 'view_product_add_subscription', $data);
			}else{
				$this->load->view ( 'view_product_add_package', $data);
				
			}
		}
	
	}
	function products_edit($edit_id = '') {  
		$data ['page_title'] = gettext ( 'Edit Product' );
		$accountinfo = $this->session->userdata ( "accountinfo" );
		$reseller_id = $accountinfo ['type'] == 1 ? $accountinfo ['id'] : 0;
		$data['product_category'] = $this->ProductCategory;
		$data ['grid_fields'] = $this->product_form->build_block_pattern_list_for_customer($edit_id);
		$where_arr = array("reseller_id"=>$reseller_id,"status"=>0);
		$data['product_rate_group'] = $this->db_model->build_dropdown("id,name", "pricelists", "where_arr", $where_arr);
		$data ['grid_field'] = $this->product_form->build_pattern_list_for_customer( $edit_id );
		$data['destination_rategroups'] = $this->db_model->build_dropdown("id,name", "pricelists", "where_arr",  $where_arr);
		$data['currency'] = $this->common->get_field_name("currency","currency",array("id"=>$accountinfo['currency_id']));
		
		$add_array = $this->db_model->getSelect ( "*", " products", array ('id' => $edit_id));
		if ($add_array->num_rows > 0) {
			$product_info = ( array ) $add_array->first_row ();
			if($product_info['product_category'] == 4){
				$did_info = $this->db_model->getSelect ( "*", " dids", array ('number' => $product_info['name']));
				if($did_info->num_rows > 0){
					$did_info = ( array ) $did_info->first_row ();	
					$product_info = array_merge($product_info,$did_info);
				}
			}
			else{
				$product_info = ( array ) $add_array->first_row ();
			}
			$data['product_info']=$product_info;
			$data['edit_id']=$edit_id;
			$data['country_id']=$product_info['country_id'];
			$category = $this->common->get_field_name("name","category",array("id"=>$product_info['product_category']));

			if($accountinfo ['type'] == 1){
			 	if($accountinfo ['reseller_id'] > 0 ){
					$optin_product = $this->db_model->getSelect ( "*", " reseller_products", array ('product_id'=>$edit_id,'reseller_products.account_id'=>$accountinfo['id'],'reseller_products.reseller_id'=>$accountinfo['reseller_id']));
				}
				else{
					$optin_product = $this->db_model->getSelect ( "*", " reseller_products", array ('product_id' => $edit_id,'reseller_products.account_id'=>$accountinfo['id']));
				}
				if($optin_product->num_rows > 0){
					$data['optin_product'] = ( array ) $optin_product->first_row ();
					if($data['optin_product']['is_optin'] == 0){ 
						$this->products_reseller_edit($data['product_info'],$data['optin_product'],$category);
				}
				else{
					$data['accountinfo']=$accountinfo;
					$this->load->view ( 'view_product_edit_'.strtolower($category), $data);

				}
			 }
			}
			else{

				$data['accountinfo']=$accountinfo;
				$this->load->view ( 'view_product_edit_'.strtolower($category), $data);
			}
			$this->session->unset_userdata ( 'optin_product');	
		} 
		else {
			redirect ( base_url () . 'product/product_list/' );
		}
	}
	function products_reseller_edit($product_info,$optin_product,$category){  
		$accountinfo = $this->session->userdata ( "accountinfo" );
		$data ['page_title'] = gettext ( 'Edit Product' );
		$data['currency'] = $this->common->get_field_name("currency","currency",array("id"=>$accountinfo['currency_id']));
		$data['optin_product'] = $optin_product;
		$data['product_info'] = $product_info;
		$data['accountinfo'] = $accountinfo;
		$this->load->view ( 'view_reseller_optin_edit', $data);
	
	}
	function products_reseller_optin_save(){  
		$add_array = $this->input->post();
		$productid= $this->input->post('product_id');
		$accountinfo = $this->session->userdata ( "accountinfo" );
			if(!empty($add_array ) && $add_array !='' ){
				$product_category = $this->common->get_field_name("product_category","products",array("id"=>$productid));
				$this->product_model->update_reseller_optin_product($add_array,$productid,$accountinfo );
				
				$this->session->set_flashdata ( 'flux_errormsg', gettext('Product updated successfully!') );
				if($product_category == 4){
					redirect ( base_url () . 'did/did_list/' );
				}else{
					redirect ( base_url () . 'products/products_list/' );
				}	
	                }
	}
	function products_save() {
		$add_array = $this->input->post ();
		$data['product_category'] = $this->ProductCategory;
		$accountinfo = $this->session->userdata ( "accountinfo" );
		$reseller_id = $accountinfo ['type'] == 1 ? $accountinfo ['id'] : 0;
		$data['currency'] = $this->common->get_field_name("currency","currency",array("id"=>$accountinfo['currency_id']));
		if(isset($add_array['product_name'])){
			$this->form_validation->set_rules('product_name', 'Name', 'required');
		}

		if(!isset($add_array['id'])){
		  if(isset($add_array['number'])){
				$this->form_validation->set_rules('connectcost', 'Connection Cost', 'greater_than[-1]|xss_clean');
				$this->form_validation->set_rules('includedseconds', 'Grace Time', 'greater_than[-1]|xss_clean');
				$this->form_validation->set_rules('cost', 'Cost/Min', 'greater_than[-1]|xss_clean');
				$this->form_validation->set_rules('init_inc', 'Initial Increment', 'greater_than[-1]|xss_clean');
				$this->form_validation->set_rules('inc', 'Increment', 'greater_than[-1]|xss_clean');
				$this->form_validation->set_rules('leg_timeout', 'Call Timeout (Sec.)', 'greater_than[-1]|xss_clean');
			$did_number = $this->common->get_field_name("number","dids",array("number"=>$add_array['number']));
			if($did_number != ""){
				$is_unique =  '|is_unique[dids.number]';
			}else{
				$is_unique =  '';
			}
			$this->form_validation->set_rules('number', 'DID', 'required|numeric|trim|xss_clean'.$is_unique);
		  }
		}
		else{ 
			if($add_array['product_category'] == "DID" || $add_array['product_category'] == 4 ){
				$did_number_info = $this->db_model->getSelect("*","dids",array("product_id"=>$add_array['id']));
				$this->form_validation->set_rules('connectcost', 'Connection Cost', 'greater_than[-1]|min_length[1]|max_length[15]|xss_clean');
				$this->form_validation->set_rules('includedseconds', 'Grace Time', 'greater_than[-1]|min_length[1]|max_length[15]|xss_clean');
				$this->form_validation->set_rules('cost', 'Cost/Min', 'min_length[1]|max_length[10]|greater_than[-1]|xss_clean');
				$this->form_validation->set_rules('init_inc', 'Initial Increment', 'greater_than[-1]|min_length[1]|max_length[15]|xss_clean');
				$this->form_validation->set_rules('inc', 'Increment', 'greater_than[-1]|xss_clean');
				$this->form_validation->set_rules('leg_timeout', 'Call Timeout (Sec.)', 'greater_than[-1]|min_length[1]|max_length[15]|xss_clean');
				if($did_number_info->num_rows > 0 ){
					$did_number_info = $did_number_info->result_array()[0];
					if($did_number_info['number'] != $add_array['number'] ){
						$is_unique =  '|is_unique[dids.number]';
						
					}else{
						$is_unique =  '';
					}
					$this->form_validation->set_rules('number', 'DID', 'required|numeric|trim|xss_clean'.$is_unique);
				}
				else{
					$this->form_validation->set_rules('number', 'DID', 'required|numeric|trim|xss_clean');
				}
		      }
		      else{
					$this->form_validation->set_rules('product_name', 'Name', 'required|trim|xss_clean');
		      }
		}
		if(isset($add_array['billing_days'])){
			$this->form_validation->set_rules('billing_days', 'Billing Days', 'numeric|required|greater_than[-1]|min_length[0]|max_length[3]|integer');
		}
		if(isset($add_array['setup_fee'])){
			$this->form_validation->set_rules('setup_fee', 'Setup Fee', 'numeric|greater_than[-1]|min_length[1]|max_length[15]|xss_clean');
		}
		if(isset($add_array['buy_cost'])){
			$this->form_validation->set_rules('buy_cost', 'Setup Fee', 'greater_than[-1]|min_length[1]|max_length[15]|xss_clean');
		}
		if(isset($add_array['commission'])){
			$this->form_validation->set_rules('commission', 'Commission', 'numeric|greater_than[-1]|min_length[1]|max_length[15]|xss_clean');
		}
		if(isset($add_array['price'])){
			$this->form_validation->set_rules('price', 'Price', 'numeric|required|greater_than[-1]|min_length[1]|max_length[15]|xss_clean');
		}
		if(isset($add_array['free_minutes'])){
			$this->form_validation->set_rules('free_minutes', 'Free Minutes', 'numeric|required|is_natural|xss_clean');
		}
		$this->form_validation->set_message('max_length', '%s field can not excced  numbers in length %s');
		if(isset($add_array['id']) && $add_array['id'] != ''){ 
			$where_arr = array("reseller_id"=>$reseller_id,"status"=>0);
			$data['destination_rategroups'] = $this->db_model->build_dropdown("id,name", "pricelists", "where_arr", $where_arr);
			$product_info = $this->db_model->getSelect ( "*", "products", array ('id' => $add_array['id']));
			$product_info = ( array ) $product_info->first_row ();
			$did_acc_id = $this->common->get_field_name("accountid","dids",array("product_id"=>$add_array['id']));
			$category = $this->common->get_field_name("name","category",array("id"=>$product_info['product_category']));

			  if ($this->form_validation->run() == FALSE){ 	
				$data ['page_title'] = gettext ( 'Edit Product' );
				$data['product_info'] = $add_array ;
				$data['product_info']['description'] =  isset($add_array['product_description'])?$add_array['product_description']:['description'];
				$data['product_info']['buy_cost'] = isset($add_array['product_buy_cost'])?$add_array['product_buy_cost']:$product_info['buy_cost'];
				
				$data['product_rate_group'] = $this->db_model->build_dropdown("id,name", "pricelists", "where_arr", $where_arr);	

				$data['product_info']['apply_on_rategroups'] = $product_info['apply_on_rategroups'];
				$data['product_info']['name'] = (isset($add_array['product_name']) && $add_array['product_name'] !='' )?$add_array['product_name']: $product_info['name'];
				$data['product_info']['description'] = (isset($add_array['product_description']) && $add_array['product_description'] !='' )?$add_array['product_description']: $product_info['description'];
				$data['product_info']['product_category'] = $product_info['product_category'];
				$data['product_info']['apply_on_existing_account'] = $product_info['apply_on_existing_account'];
				$data['product_info']['id'] = $product_info['id'];
				$data['product_info']['product_id'] = $product_info['id'];
				$data['accountinfo'] = $accountinfo ;

				$data ['validation_errors'] = validation_errors ();

				$this->load->view ( 'view_product_edit_'.strtolower($category), $data);	
	       	         }
	       	         else{  
				if(isset($add_array) && !empty($add_array)){
					$account_data = $this->session->userdata ( "accountinfo" );
					if ($this->session->userdata ( 'logintype' ) == 1 || $this->session->userdata ( 'logintype' ) == 5) {
						$add_array['reseller_id'] = $account_data ['reseller_id'];
						
						
					} 
					
					$add_array['accountid'] = ($add_array['product_category'] =='DID')?$did_acc_id:$accountinfo ['id'];
					$add_array['parent_id'] = $this->common->get_field_name("parent_id","dids",array("product_id"=>$add_array['id']));
					$Method = strtolower($category)."_product";
					$add_array['name']=$product_info['name'];
					
					$this->$Method($add_array);
					if($add_array['product_category'] == "DID"){
						$this->session->set_flashdata ( 'flux_errormsg', gettext('DID updated successfully!'));
						redirect ( base_url () . 'did/did_list/' );

					}
					else{
						$this->session->set_flashdata ( 'flux_errormsg', gettext('Product updated successfully!'));
						redirect ( base_url () . 'products/products_list/' );
					}
		  }
		}
		}
		else{
			$category =$data['product_category'][$add_array['product_category']];
		 	$data['add_array'] = $add_array['product_category'];
			$where_arr = array("reseller_id"=>$reseller_id, "status"=>0);
			$data['product_rate_group'] = $this->db_model->build_dropdown("id,name", "pricelists", "where_arr", $where_arr);
		      if ($this->form_validation->run() == FALSE){  
				$data['add_array'] = $add_array;
				
				$data ['page_title'] = gettext ( 'Create Product' );
				$data ['validation_errors'] = validation_errors ();

				if ($category == "Pacote"){
					$category = "Package";
				}

				$this->load->view ( 'view_product_add_'.strtolower($category), $data);	
	       	     }
	       	     else{  
			if(isset($add_array) && !empty($add_array)){
				$account_data = $this->session->userdata ( "accountinfo" );
				if ($this->session->userdata ( 'logintype' ) == 1 || $this->session->userdata ( 'logintype' ) == 5) {
					$add_array['reseller_id'] = $account_data ['id'];
				} 

				$add_array['accountid'] = $account_data['id'];
				//$Method = strtolower($category)."_product";
				if (strtolower($category) == "package"){
					$Method = "package_product";
				}else{
					$Method = "did_product";
				}
				
				$this->$Method($add_array); 

				if($add_array['product_category'] == 4){
					$this->session->set_flashdata ( 'flux_errormsg', gettext('DID added successfully!'));
					redirect ( base_url () . 'did/did_list/' );
				
				}
				else{
					$this->session->set_flashdata ( 'flux_errormsg', gettext('Product added successfully!'));
					redirect ( base_url () . 'products/products_list/' );

				}
			 }
		}	 
	  }	
	}
	function assign_product_to_exiting_account($productinfo,$product_id){
		$productinfo['product_id'] = $product_id;
		$accountinfo = $this->session->userdata ( "accountinfo" );
		$reseller_id = $accountinfo ['type'] == 1 ? $accountinfo ['id'] : 0;
		$account_info = $this->product_model->get_existing_accounts_for_assignment($reseller_id, isset($productinfo['product_rate_group']) ? $productinfo['product_rate_group'] : array());

		if (empty($account_info) || $productinfo['apply_on_existing_account'] != 0 || !isset($productinfo['product_rate_group']) || empty($productinfo['product_rate_group'])) {
			return true;
		}

		if($productinfo['release_no_balance'] == 1){
			foreach($account_info as $account){
				$customer_data = $this->product_model->get_active_customer_account($account['id']);
				$productinfo['payment_by'] = "Account Balance";
				$productinfo['create_invoice'] = "true";
				$last_id = $this->order->confirm_order($productinfo,$account['id'],$accountinfo);
				if(!empty($customer_data) && isset($productinfo['email_notify']) && $productinfo['email_notify'] == 1 && $last_id > 0){
					$productinfo['product_category'] = ($productinfo['product_category'] == 1) ? "PACKAGE" : (($productinfo['product_category'] == 2)  ? "SUBSCRIPTION" : "DID");
					$productinfo['next_billing_date'] = ($productinfo['billing_days'] == 0)?gmdate('Y-m-d 23:59:59', strtotime('+10 years')):gmdate("Y-m-d 23:59:59",strtotime("+".($productinfo['billing_days']-1)." days"));
					$final_array = array_merge($customer_data,$productinfo);
					$final_array['quantity'] = (isset($productinfo['product_category']) && $productinfo['product_category']==2) ? (isset($productinfo['quantity'])?$productinfo['quantity']:1) : 1;
					$final_array['category_name']=$productinfo['product_category'];
					$final_array['price']=($productinfo['setup_fee']+$productinfo['price']);
					$final_array['total_price']=($productinfo['setup_fee']+$productinfo['price'])*($final_array['quantity']);
					$final_array['total_price_amount']=($productinfo['setup_fee']+$productinfo['price']);
				}
			}
			return true;
		}

		if($productinfo['release_no_balance'] == 0){
			$total_amt = $productinfo['price'] + $productinfo['setup_fee'];
			foreach($account_info as $account){
				$customer_data = $this->product_model->get_active_customer_account($account['id']);
				$account_balance = $account['posttoexternal'] == 1 ? $account ['credit_limit'] - ($account ['balance']) : $account ['balance'];
				if($account_balance >= $total_amt ){
					$productinfo['payment_by'] = "Account Balance";
					$productinfo['create_invoice'] = "true";
					$last_id =$this->order->confirm_order($productinfo,$account['id'],$accountinfo);
					if(!empty($customer_data) && isset($productinfo['email_notify']) && $productinfo['email_notify'] ==1 && $last_id > 0 ){
						$productinfo['product_category'] = ($productinfo['product_category'] == 1) ? "PACKAGE" : (($productinfo['product_category'] == 2)  ? "SUBSCRIPTION" : "DID");
						$productinfo['next_billing_date'] = ($productinfo['billing_days'] == 0)?gmdate('Y-m-d 23:59:59', strtotime('+10 years')):gmdate("Y-m-d 23:59:59",strtotime("+".($productinfo['billing_days']-1)." days"));
						$final_array = array_merge($customer_data,$productinfo);
						$final_array['quantity'] = (isset($productinfo['product_category']) && $productinfo['product_category']==2) ? (isset($productinfo['quantity'])?$productinfo['quantity']:1) : 1;
						$final_array['category_name']=$productinfo['product_category'];
						$final_array['price']=($productinfo['setup_fee']+$productinfo['price']);
						$final_array['total_price']=($productinfo['setup_fee']+$productinfo['price'])*($final_array['quantity']);
						$final_array['total_price_amount']=($productinfo['setup_fee']+$productinfo['price']);
						$this->common->mail_to_users("product_purchase",$final_array);
					}
				}
			}
		}

		return true;
	}
	function did_product($add_array){ 
		if(isset($add_array['id']) && $add_array['id']!= ''){
			$this->product_model->edit_product($add_array,$add_array['id']);
		}else{
			$last_id =$this->product_model->add_product($add_array);
		}
	}
	function refill_product($add_array){
		if(isset($add_array['id']) && $add_array['id']!= ''){
			$this->product_model->edit_product($add_array,$add_array['id']);
		}else{
			$this->product_model->add_product($add_array);
		}
	}
	function products_package_pattern_search() {
		$package_search_data = $this->input->post ();
		$this->session->set_userdata ( 'product_package_pattern_search', $package_search_data );
		exit;
	}
	function products_quick_search(){
		$action = $this->input->post ();
		$this->session->set_userdata ( 'left_panel_search_package_pattern', "" );
		if (! empty ( $action ['left_panel_search'] )) {
			$this->session->set_userdata ( 'left_panel_search_package_pattern', $action ['left_panel_search'] );
		}
	}
	function products_patterns_selected_delete() {
		$ids = $this->input->post ( "selected_ids", true );
		unset ( $_POST );
		echo $this->product_model->delete_selected_package_patterns($ids);
	}
	function products_package_pattern($productid){
		$accountinfo = $this->session->userdata ( "accountinfo" );
		$reseller_id = $accountinfo ['type'] == 1 ? $accountinfo ['id'] : 0;
		$search = $this->session->userdata('product_package_pattern_search');
		echo $this->product_model->count_available_package_pattern_routes($productid, $reseller_id, !empty($search) ? $search : array());
	}
	function products_patterns_delete($productid,$id) {
		$this->product_model->delete_product_pattern($productid, $id);
		redirect ( base_url () . "products/products_edit/" . $productid );
	}
	function products_delete($id) {
		$this->product_model->remove_product ( $id );
		$this->session->set_flashdata ( 'flux_notification', gettext('Product removed successfully!'));
		redirect ( base_url () . 'products/products_list/' );
	}
	function products_delete_multiple() {
		$ids = $this->input->post ( 'selected_ids', true );
		$accountinfo = $this->session->userdata ( "accountinfo" );
		$this->product_model->bulk_delete_products($ids, $accountinfo);
		unset ( $_POST );
	}
	function products_list_search() {
		$ajax_search = $this->input->post ( 'ajax_search', 0 );
		if ($this->input->post ( 'advance_search', TRUE ) == 1) {
			$this->session->set_userdata ( 'advance_search', $this->input->post ( 'advance_search' ) );
			$action = $this->input->post ();
			if (isset ( $action ['buy_cost'] ['buy_cost'] ) && $action ['buy_cost'] ['buy_cost'] != '') {
			$action ['buy_cost'] ['buy_cost'] = $this->common_model->add_calculate_currency ( $action ['buy_cost'] ['buy_cost'], "", '', false, false );
			}
			if (isset ( $action ['price'] ['price'] ) && $action ['price'] ['price'] != '') {
			$action ['price'] ['price'] = $this->common_model->add_calculate_currency ( $action ['price'] ['price'], "", '', false, false );
			}
			if (isset ( $action ['setup_fee'] ['setup_fee'] ) && $action ['setup_fee'] ['setup_fee'] != '') {
			$action ['setup_fee'] ['setup_fee'] = $this->common_model->add_calculate_currency ( $action ['setup_fee'] ['setup_fee'], "", '', false, false );
			}
			unset ( $action ['action'] );
			unset ( $action ['advance_search'] );
			$this->session->set_userdata ( 'product_list_search', $action );
		}
		if (@$ajax_search != 1) {
			redirect ( base_url () . 'products/products_list/' );
		}
	}
	function products_listing_search() {
		$ajax_search = $this->input->post ( 'ajax_search', 0 );
		if ($this->input->post ( 'advance_search', TRUE ) == 1) {
			$this->session->set_userdata ( 'advance_search', $this->input->post ( 'advance_search' ) );
			$action = $this->input->post ();

			$accountinfo = $this->session->userdata ( "accountinfo" );
			
			if (isset ( $action ['buy_cost'] ['buy_cost'] ) && $action ['buy_cost'] ['buy_cost'] != '') {
				$action ['buy_cost'] ['buy_cost'] = $this->common_model->add_calculate_currency ( $action ['buy_cost'] ['buy_cost'], "", '', false, false );
				}
			
			if (isset ( $action ['price'] ['price'] ) && $action ['price'] ['price'] != '') {
			$action ['price'] ['price'] = $this->common_model->add_calculate_currency ( $action ['price'] ['price'], "", '', false, false );
			}
			if (isset ( $action ['setup_fee'] ['setup_fee'] ) && $action ['setup_fee'] ['setup_fee'] != '') {
			$action ['setup_fee'] ['setup_fee'] = $this->common_model->add_calculate_currency ( $action ['setup_fee'] ['setup_fee'], "", '', false, false );
			}
			unset ( $action ['action'] );
			unset ( $action ['advance_search'] );
			$this->session->set_userdata ( 'product_list_search', $action );
		}
		if (@$ajax_search != 1) {
			redirect ( base_url () . 'products/products_list/' );
		}
	}
	function products_list_clearsearchfilter() {
		$this->session->set_userdata ( 'advance_search', 0 );
		$this->session->set_userdata ( 'product_list_search', "" );
	}
	function products_pattern_list_json($productid) {
		$json_data = array ();
		$instant_search = $this->session->userdata ( 'left_panel_search_package_pattern' );
		$count_all = $this->product_model->count_product_patterns($productid, $instant_search);
		$paging_data = $this->form->load_grid_config ( $count_all, $_GET ['rp'], $_GET ['page'] );
		$json_data = $paging_data ["json_paging"];
		$pattern_data = $this->product_model->get_product_patterns($productid, $instant_search, $paging_data ["paging"] ["page_no"], $paging_data ["paging"] ["start"]);
		$grid_fields = json_decode ( $this->product_form->build_pattern_list_for_customer ( $productid ) );
		$json_data ['rows'] = $this->form->build_grid ( $pattern_data, $grid_fields );
		echo json_encode ( $json_data );
	}
	function products_patterns_add_info($productid) {
		$accountinfo = $this->session->userdata ( "accountinfo" );
		$reseller_id = $accountinfo ['type'] == 1 ? $accountinfo ['id'] : 0;
		$rates = $this->product_model->get_available_package_pattern_routes($productid, $reseller_id, $this->session->userdata('product_package_pattern_search'));
		if($rates->num_rows > 0){
			$result = $this->product_model->insert_pacakge_pattern ($productid, $rates);
			if($result == 1){
				echo 1;
				exit ();
			}
		}else{
			echo 0; 
			exit ();
		}
	}
	function customer_products_list($accountid, $accounttype) { 
		$json_data = array ();
		$instant_search = $this->session->userdata ( 'left_panel_search_' . $accounttype . '_products' );
		$count_all = $this->product_model->count_customer_products($accountid, $instant_search);
		$paging_data = $this->form->load_grid_config ( $count_all, $_GET ['rp'], $_GET ['page'] );
		$json_data = $paging_data ["json_paging"];
		$account_product_list = $this->product_model->get_customer_products($accountid, $instant_search, $paging_data ["paging"] ["page_no"], $paging_data ["paging"] ["start"]);
		$grid_fields = json_decode ( $this->product_form->build_products_list_for_customer ($accountid, $accounttype));
		$json_data ['rows'] = $this->form->build_grid ( $account_product_list, $grid_fields );
		echo json_encode ( $json_data );
	}
	function products_reseller_save(){
		if(!empty($this->input->post())){ 
			$add_array = $this->input->post();
			$accountinfo = $this->session->userdata ( "accountinfo" );
			$account_data = $this->db_model->getSelect("*","accounts",array("id"=>$add_array['accountid']));
			if($account_data->num_rows > 0){
				$data['account_data']= $account_data->result_array()[0];			
				$product_data = $this->db_model->getSelect("*","products",array("id"=>$add_array['product_id']));
				if($product_data->num_rows > 0){
					$data['product_data']=$product_data->result_array()[0];
					$where = array("id"=>$data['product_data']['product_category']);
					$data['category_list'] =  $this->common->get_field_name("name", "category",$where);	
					$this->load->view("view_reseller_orders_assign",$data);
				}
			}
		   }	
	}
	function products_reseller_confirm_order(){ 
		if(!empty($this->input->post())){
			$ProductData = $this->input->post(); 
			$account_id = $this->input->post('account_id');
			$accountinfo = $this->session->userdata ( "accountinfo" );
			$ProductData['create_invoice'] = "true";
			$order_id =$this->order->confirm_order($ProductData,$account_id,$accountinfo);
			$this->session->set_flashdata ( 'flux_errormsg', gettext('Product assigned successfully!'));
			redirect ( base_url () . 'products/products_list/' );
		}else{
			$this->session->set_flashdata ( 'flux_errormsg', gettext('Somthing went wronge'));
			redirect ( base_url () . 'products/products_list/' );
		}

	}
	function products_did(){  
		$data ['page_title'] = gettext ( 'Create Product' );
		$accountinfo = $this->session->userdata ( "accountinfo" );
		$data['product_category'] = $this->db_model->build_dropdown("id,name,code", "category", "name", "DID");
		$data['currency'] = $this->common->get_field_name("currency","currency",array("id"=>$accountinfo['currency_id']));
		$reseller_id = $accountinfo ['type'] == 1 ? $accountinfo ['id'] : 0;
		$this->load->view("view_product_add_did",$data);

	}
	function products_edit_reseller_optinproduct($productid=''){ 
		$accountinfo = $this->session->userdata ( "accountinfo" );
		$data ['page_title'] = gettext ( 'Create Product' );
		$data['currency'] = $this->common->get_field_name("currency","currency",array("id"=>$accountinfo['currency_id']));
		$product_info = $this->product_model->get_reseller_optin_product_info($productid, $accountinfo, false);
		$data['product_info']=$product_info;
		$data['accountinfo'] = $accountinfo;
		$this->load->view("view_optin_reseller_product",$data);
	}
	function products_reseller_option_save(){
		$add_array = $this->input->post();
		$productid= $this->input->post('productid');
		$accountinfo = $this->session->userdata ( "accountinfo" );
		if($productid != '' &&  $productid != 0){
			if($this->product_model->save_reseller_option($productid, $add_array, $accountinfo)){
				$this->session->set_flashdata ( 'flux_errormsg', gettext('Product optin successfully!'));
			}
			redirect ( base_url () . 'products/products_list/' );
		}else{
			redirect ( base_url () . 'products/products_list/' );
		}	
	}
	function products_optin(){
	  if($this->input->post()){
		$product_id = $this->input->post('product_id');
		$status = $this->input->post('status');
		$accountinfo = $this->session->userdata ( "accountinfo" );
		$this->product_model->toggle_product_optin($product_id, $status, $accountinfo);
	}
 }
 	function products_topuplist() { 
		$accountinfo = $this->session->userdata ( "accountinfo" );
		$account_arr = $this->product_model->get_active_account($accountinfo['id']);
		if(empty($account_arr)){
			$this->session->sess_destroy ();
			$this->load->helper('cookie');
			set_cookie('post_info',json_encode("text"),'20');
			redirect ( base_url ()."login/");
		}
		if($accountinfo['posttoexternal'] != '1'){ 
			$this->load->module("pages/pages");
			$this->pages->topup_reseller();
		}else{
			if($accountinfo['type'] == '0' || $accountinfo['type'] == '3'){
				$this->session->set_flashdata('flux_danger_alert',gettext('Permission Denied!'));
				redirect(base_url() . 'user/user/');
			}else{
				$this->session->set_flashdata('flux_danger_alert',gettext('Permission Denied!'));
				redirect(base_url() . 'dashboard/');
			}
		}
	}
}
?>
