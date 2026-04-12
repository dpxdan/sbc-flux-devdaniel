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
class Product_model extends CI_Model {
	function Plans_model() {
		parent::__construct ();
	}
	function getproduct_list($flag, $start = 0, $limit = 0) {
		$categoryinfo = $this->db_model->getSelect("GROUP_CONCAT('''',id,'''') as id","category","code NOT IN ('REFILL','DID')");
			if($categoryinfo->num_rows > 0 ){ 
				$categoryinfo = $categoryinfo->result_array()[0]['id']; 

			}   
		$accountinfo=$this->session->userdata('accountinfo');
		
		if ($this->session->userdata ( 'logintype' ) == 1 || $this->session->userdata ( 'logintype' ) == 5) {				
			if (isset ( $_GET ['sortname'] ) && $_GET ['sortname'] != 'undefined') {
						$this->db->order_by ( $_GET ['sortname'], ($_GET ['sortorder'] == 'undefined') ? 'desc' : $_GET ['sortorder'] );
			} else {
						$this->db->order_by("products.id","DESC");
			}
			if($accountinfo["reseller_id"] > 0){
				$this->db_model->build_search ( 'product_list_search','reseller_products.' );
				$this->db->where ("product_id NOT IN (select CONCAT(product_id) from reseller_products where is_owner = 1 and is_optin = 0 and account_id = ".$accountinfo['id']." )");
				$this->db->where("product_category IN (".$categoryinfo.")",NULL, false);
			
				if($flag){
					 
					$query = $this->db_model->getJionQuery('products', 'products.id,products.name,products.product_category,products.country_id,reseller_products.status as reseller_status,reseller_products.buy_cost,reseller_products.reseller_id,products.commission,reseller_products.setup_fee,reseller_products.price as
buycost,reseller_products.price,reseller_products.billing_type,reseller_products.billing_days,reseller_products.free_minutes,products.status,products.last_modified_date,reseller_products.product_id', array('products.status'=>0,'products.is_deleted'=>0,'reseller_products.status'=>0,'products.can_resell'=>0,'products.can_purchase'=>0,'reseller_products.account_id'=>$accountinfo['reseller_id']), 'reseller_products','products.id=reseller_products.product_id', 'inner', $limit , $start,'','');
				}else{
					
					$query = $this->db_model->getJionQueryCount('products', 'products.id,products.name,products.product_category,products.country_id,reseller_products.status as reseller_status,reseller_products.buy_cost,reseller_products.reseller_id,products.commission,reseller_products.setup_fee,reseller_products.price as buycost,reseller_products.price,reseller_products.billing_type,reseller_products.billing_days,reseller_products.free_minutes,products.status,products.last_modified_date,reseller_products.product_id', array('products.status'=>0,'products.is_deleted'=>0,'reseller_products.status'=>0,'products.can_resell'=>0,'products.can_purchase'=>0,'reseller_products.account_id'=>$accountinfo['reseller_id']), 'reseller_products','products.id=reseller_products.product_id', 'inner', '' , '','','');
				}

			}else{
			$this->db_model->build_search ( 'product_list_search' );
			if($flag){
				  $this->db->where("product_category IN (".$categoryinfo.")",NULL, false);
				  $this->db->where ("id NOT IN (select product_id from reseller_products where is_optin = 0 and account_id = ".$accountinfo["id"]." )");
				  $query = $this->db_model->select("*,price as buycst","products",array("status"=>0,"reseller_id"=>0,"can_resell"=>0,"can_purchase"=>0,'products.is_deleted'=>0),"id","ASC",$limit,$start,""); 
			}else{
				$this->db->where("product_category IN (".$categoryinfo.")",NULL, false);
				 $this->db->where ("id NOT IN (select product_id from reseller_products where is_optin = 0 and account_id = ".$accountinfo["id"]." )");
				 $query = $this->db_model->countQuery ( "*", "products", array("status"=>0,"reseller_id"=>0,"can_resell"=>0,"can_purchase"=>0,'products.is_deleted'=>0) );
			}
			
		}
		return $query;

	}
}
	function getreseller_products_list($flag, $start = 0, $limit = 0) {  
			if ($this->session->userdata ( 'logintype' ) == 1 || $this->session->userdata ( 'logintype' ) == 5) {
			
			$categoryinfo = $this->db_model->getSelect("GROUP_CONCAT('''',id,'''') as id","category","code NOT IN ('REFILL','DID')");
			if($categoryinfo->num_rows > 0 ){ 
				$categoryinfo = $categoryinfo->result_array()[0]['id']; 
				$this->db->where("product_category IN (".$categoryinfo.")",NULL, false);
			}
			$this->db_model->build_search ( 'product_list_search','reseller_products.' );			
			$accountinfo=$this->session->userdata('accountinfo');
			$temp_where = "(reseller_products.is_optin = 0 OR reseller_products.is_owner=0)";
			$this->db->where($temp_where);
			$tmp_where = "(reseller_products.status = 0 OR reseller_products.status =1)";
			$this->db->where($tmp_where);
			$rst_where = "(products.status = 0 OR products.status = 1)";
			$this->db->where($rst_where);			
			$str_where = "(reseller_products.is_owner=1 OR reseller_products.is_owner=0)";
			$this->db->where($str_where);
			$this->db->where('reseller_products.account_id',$accountinfo['id']);
			
			if($accountinfo['reseller_id'] > 0){
				if ($flag) {
					if (isset ( $_GET ['sortname'] ) && $_GET ['sortname'] != 'undefined') {
						$this->db->order_by ( $_GET ['sortname'], ($_GET ['sortorder'] == 'undefined') ? 'desc' : $_GET ['sortorder'] );
					} 
					else {
						$this->db->order_by("products.id","DESC");
					}
					$query = $this->db_model->getJionQuery('products', 'products.id,products.name,products.product_category,products.country_id,reseller_products.status as reseller_status,reseller_products.buy_cost,reseller_products.reseller_id,products.commission,reseller_products.setup_fee,reseller_products.price,reseller_products.billing_type,reseller_products.billing_days,reseller_products.free_minutes,products.status,products.last_modified_date,reseller_products.product_id', array('products.is_deleted'=>0), 'reseller_products','products.id=reseller_products.product_id', 'inner', $limit , $start,'','');
				} else {
					$query = $this->db_model->getJionQueryCount('products', 'products.id,products.name,products.product_category,products.country_id,reseller_products.status as reseller_status,reseller_products.buy_cost,reseller_products.reseller_id,products.commission,reseller_products.setup_fee,reseller_products.price,reseller_products.billing_type,reseller_products.billing_days,reseller_products.free_minutes,products.status,products.last_modified_date,reseller_products.product_id', array('products.is_deleted'=>0), 'reseller_products','products.id=reseller_products.product_id', 'inner','', '','','');

				}
			}else{
				if ($flag) {
					if (isset ( $_GET ['sortname'] ) && $_GET ['sortname'] != 'undefined') {
						$this->db->order_by ( $_GET ['sortname'], ($_GET ['sortorder'] == 'undefined') ? 'desc' : $_GET ['sortorder'] );
					} else {
						$this->db->order_by("products.id","DESC");
					}
					$query = $this->db_model->getJionQuery('products', 'products.id,products.name,products.product_category,products.country_id,reseller_products.status as reseller_status,reseller_products.buy_cost,reseller_products.reseller_id,products.commission,reseller_products.setup_fee,reseller_products.price,reseller_products.billing_type,reseller_products.billing_days,reseller_products.free_minutes,products.status,products.last_modified_date,reseller_products.product_id', array('products.is_deleted'=>0), 'reseller_products','products.id=reseller_products.product_id', 'inner', $limit , $start,'','');
				} else {
					$query = $this->db_model->getJionQueryCount('products', 'products.id,products.name,products.product_category,products.country_id,reseller_products.status as reseller_status,reseller_products.buy_cost,reseller_products.reseller_id,products.commission,reseller_products.setup_fee,reseller_products.price,reseller_products.billing_type,reseller_products.billing_days,reseller_products.free_minutes,products.status,products.last_modified_date,reseller_products.product_id', array('products.is_deleted'=>0), 'reseller_products','products.id=reseller_products.product_id', 'inner','', '','','');
				}
		       }
	      return $query;
	      }else{
			$this->db_model->build_search ( 'product_list_search');
			$this->db->order_by("id","DESC");
			$where = array ("is_deleted" => "0","product_category <>"=>4);
			if ($flag) {
				$query = $this->db_model->select ( "*", "products", $where, "", "", $limit, $start );
			} else {
				$query = $this->db_model->countQuery ( "*", "products", $where );
			}

		return $query;
	  }

	}
	function add_product($add_array,$patternSearchArr='') {
		$accountinfo = $this->session->userdata ( "accountinfo" );
		unset ( $add_array ["action"] );
		unset ( $add_array ["email_notify"] );
		$insert_array = array(
				"name"=>($add_array['product_category'] == 4 || $add_array['product_category'] == "DID" )?$add_array['number']:$add_array['product_name'],
				"country_id"=>(isset($add_array['country_id']) && $add_array['country_id'] > 0) ? $add_array['country_id'] : "",
				"description"=>isset($add_array['product_description'])?$add_array['product_description']:"",
				"product_category"=>$add_array['product_category'],
				"buy_cost"=>isset($add_array['product_buy_cost'])?$this->common_model->add_calculate_currency ($add_array['product_buy_cost'], "", '', false, false ):"0.00",
				"price"=>$this->common_model->add_calculate_currency ($add_array['price'], "", '', false, false ),
				"setup_fee"=>isset($add_array['setup_fee'])?$this->common_model->add_calculate_currency ($add_array['setup_fee'], "", '', false, false ):"0.00",
				"can_purchase"=>isset($add_array['can_purchase'])?$add_array['can_purchase']:"",
				"status"=>$add_array['status'],	
				"can_resell"=>isset($add_array['can_resell'])?$add_array['can_resell']:"0",
				"commission"=>isset($add_array['commission'])?$add_array['commission']:"0",
				"billing_type"=>isset($add_array['billing_type'])?$add_array['billing_type']:"",
				"billing_days"=>isset($add_array['billing_days'])?$add_array['billing_days']:"",
				"free_minutes"=>isset($add_array['free_minutes'])?$add_array['free_minutes']:"",
				"apply_on_rategroups"=>isset($add_array['product_rate_group'])?implode(",",$add_array['product_rate_group']):"",
				"destination_rategroups"=>isset($patternSearchArr['destination_rategroups'])?implode(",",$patternSearchArr['destination_rategroups']):"",
				"destination_countries"=>isset($patternSearchArr['destination_countries'])?implode(",",$patternSearchArr['destination_countries']):"",
				"destination_calltypes"=>isset($patternSearchArr['destination_calltypes'])?implode(",",$patternSearchArr['destination_calltypes']):"",
				"apply_on_existing_account"=>isset($add_array['apply_on_existing_account'])?$add_array['apply_on_existing_account']:0,
				"applicable_for"=>isset($add_array['applicable_for'])?$add_array['applicable_for']:0,
				"release_no_balance"=>isset($add_array['release_no_balance'])?$add_array['release_no_balance']:"1",
				"created_by"=>$add_array['accountid'],
				"reseller_id"=>isset($add_array['reseller_id'])?$add_array['reseller_id']:0,
				"creation_date"=>gmdate("Y-m-d H:i:s"),
				"last_modified_date"=>gmdate("Y-m-d H:i:s")
		);
		$this->db->insert ( "products", $insert_array );
		$last_id = $this->db->insert_id();
		if($add_array['product_category']==4 || $add_array['product_category']=="DID" ){
				$did_insert_array = array(
					"number"=>$add_array['number'],
					"accountid"=>0,
					"parent_id"=>isset($add_array['parent_id'])?$add_array['parent_id']:"0",
					"connectcost"=>isset($add_array['connectcost'])?$this->common_model->add_calculate_currency ($add_array['connectcost'], "", '', false, false ):"0",
					"includedseconds"=>isset($add_array['includedseconds'])?$add_array['includedseconds']:"0",
					"monthlycost"=>isset($add_array['price'])?$this->common_model->add_calculate_currency ($add_array['price'], "", '', false, false ):"0",
					"cost"=>isset($add_array['cost'])?$this->common_model->add_calculate_currency ($add_array['cost'], "", '', false, false ):"0.00",
					"init_inc"=>isset($add_array['init_inc'])?$add_array['init_inc']:"0",
					"inc"=>isset($add_array['inc'])?$add_array['inc']:"0",
					"extensions"=>isset($add_array['extensions'])?$add_array['extensions']:"",
					"status"=>isset($add_array['status'])?$add_array['status']:"0",
					"provider_id"=>isset($add_array['provider_id'])?$add_array['provider_id']:"0",
					"country_id"=>isset($add_array['country_id'])?$add_array['country_id']:"0",
					"province"=>isset($add_array['province'])?$add_array['province']:"",
					"city"=>isset($add_array['city'])?$add_array['city']:"",
					"area_code"=>isset($add_array['area_code'])?$add_array['area_code']:"",
					"setup"=>isset($add_array['setup_fee'])?$this->common_model->add_calculate_currency ($add_array['setup_fee'], "", '', false, false ):"0",
					"maxchannels"=>isset($add_array['maxchannels'])?$add_array['maxchannels']:"0",
					"reverse_rate"=>isset($add_array['reverse_rate'])?$add_array['reverse_rate']:"1",
					"call_type"=>isset($add_array['call_type'])?$add_array['call_type']:"",
					"leg_timeout"=>isset($add_array['leg_timeout'])?$add_array['leg_timeout']:"30",
					"last_modified_date"=>gmdate("Y-m-d H:i:s"),
					"product_id"=>$last_id
					
				   );
			$this->db->insert ( "dids", $did_insert_array );
			$did_last_id = $this->db->insert_id();
		}
		if($accountinfo['type'] == 1){
			$reseller_products_array = array(
					"product_id"=>$last_id,
					"account_id"=>isset($add_array['reseller_id'])?$add_array['reseller_id']:0,							"buy_cost"=>isset($add_array['product_buy_cost'])?$add_array['product_buy_cost']:"",
					"country_id"=>($add_array['country_id'] > 0) ? $add_array['country_id'] : "",
					"price"=>$this->common_model->add_calculate_currency ($add_array['price'], "", '', false, false ),
					"buy_cost"=>isset($add_array['product_buy_cost'])?$this->common_model->add_calculate_currency ($add_array['product_buy_cost'], "", '', false, false ):"0.00",
					"setup_fee"=>isset($add_array['setup_fee'])?$this->common_model->add_calculate_currency ($add_array['setup_fee'], "", '', false, false ):"0.00",
					"reseller_id"=>isset($accountinfo ['reseller_id'])?$accountinfo ['reseller_id']:0,
					"commission"=>isset($add_array['commission'])?$this->common_model->add_calculate_currency ($add_array['commission'], "", '', false, false ):"0",
					"billing_type"=>isset($add_array['billing_type'])?$add_array['billing_type']:"",
					"billing_days"=>isset($add_array['billing_days'])?$add_array['billing_days']:"",
					"free_minutes"=>isset($add_array['free_minutes'])?$add_array['free_minutes']:"",
					"status"=>$add_array['status'],
					"is_owner"=>0,
					"is_optin"=>1
					);

			$this->db->insert ( "reseller_products", $reseller_products_array );
			$reseller_products_last_id = $this->db->insert_id();

		}	
		$this->session->unset_userdata('product_package_pattern_search');
		return $last_id;
	}
	function get_pacakge_pattern($patternSearchArr){
		$this->db->where_in('pricelist_id',$patternSearchArr['destination_rategroups']);
		$this->db->where_in('country_id',$patternSearchArr['destination_countries']);
		$this->db->where_in('call_type',$patternSearchArr['destination_calltypes']);
		$this->db->select("*");
		$this->db->from("routes");
		$pattern_array = $this->db->get();
		return $pattern_array;
	}
	function insert_pacakge_pattern($product_last_id,$pattern_array){ 

		$update_fields [] = "patterns=VALUES(patterns),destination=VALUES(destination),country_id=VALUES(country_id)";
		$insert_string = "INSERT INTO package_patterns (product_id,country_id,patterns,destination) VALUES ";
		$update_string = " ON DUPLICATE KEY UPDATE " . implode ( ', ', $update_fields );

		$pattern_str = "";
		if($pattern_array->num_rows != 0){
		$pattern_array = $pattern_array->result_array();	

			foreach($pattern_array as $key => $package_pattern){
			$package_pattern_update = array();
			$package_pattern_update = array($product_last_id,$package_pattern['country_id'],$package_pattern['pattern'],$package_pattern['comment']);
				$pattern_str.= "('" . implode ( "','", $package_pattern_update) . "'),";

			}
			$pattern_str= rtrim ( $pattern_str, "," );
			$this->db->query ( $insert_string . $pattern_str . $update_string, false );
		}
		return true;

	}
	function edit_product($add_array, $id,$editpatternSearchArr='') {
		$accountinfo = $this->session->userdata ( "accountinfo" );
		//		unset($add_array['apply_on_existing_account']);
		//		unset($add_array['product_rate_group']);
		//		unset ( $add_array ["email_notify"] );
		$add_array['product_category'] = isset($add_array['product_category'])?$add_array['product_category']:'';
		if($add_array['product_category']=='DID' || $add_array['product_category']== 4){
				$destination_info = $this->db_model->getSelect("call_type,extensions","dids",array("number"=>$add_array['number']));
				$destination_info = $destination_info->result_array()[0];
				$did_update_array = array(
					"number"=>$add_array['number'],
					"accountid"=>$add_array['accountid'],
					"parent_id"=>isset($add_array['parent_id'])?$add_array['parent_id']:"0",
					"connectcost"=>isset($add_array['connectcost'])?$this->common_model->add_calculate_currency ($add_array['connectcost'], "", '', false, false ):"0",
					"includedseconds"=>isset($add_array['includedseconds'])?$add_array['includedseconds']:"0",
					"monthlycost"=>isset($add_array['price'])?$this->common_model->add_calculate_currency ($add_array['price'], "", '', false, false ):"0",
					"cost"=>isset($add_array['cost'])?$this->common_model->add_calculate_currency ($add_array['cost'], "", '', false, false ):"0.00",
					"init_inc"=>isset($add_array['init_inc'])?$add_array['init_inc']:"0",
					"inc"=>isset($add_array['inc'])?$add_array['inc']:"0",
					"extensions"=>isset($destination_info['extensions'])?$destination_info['extensions']:"",
					"status"=>isset($add_array['status'])?$add_array['status']:"0",
					"provider_id"=>isset($add_array['provider_id'])?$add_array['provider_id']:"0",
					"country_id"=>isset($add_array['country_id'])?$add_array['country_id']:"0",
					"province"=>isset($add_array['province'])?$add_array['province']:"",
					"area_code"=>isset($add_array['area_code'])?$add_array['area_code']:"",
					"city"=>isset($add_array['city'])?$add_array['city']:"",
					"setup"=>isset($add_array['setup_fee'])?$this->common_model->add_calculate_currency ($add_array['setup_fee'], "", '', false, false ):"0",
					"maxchannels"=>isset($add_array['maxchannels'])?$add_array['maxchannels']:"0",
					"reverse_rate"=>isset($add_array['reverse_rate'])?$add_array['reverse_rate']:"1",
					"call_type"=>isset($destination_info['call_type'])?$destination_info['call_type']:"",
					"rate_group"=>isset($add_array['rate_group'])?$add_array['rate_group']:"",
					"leg_timeout"=>isset($add_array['leg_timeout'])?$add_array['leg_timeout']:"30"
				   );
			$this->db->where ( "number", $add_array['name'] );
			$this->db->update ( "dids", $did_update_array );
		}

		$update_array = array(
				"name"=>($add_array['product_category'] == "DID" || $add_array['product_category'] == 4)?$add_array['number']:$add_array['product_name'],
				"country_id"=>($add_array['country_id'] > 0) ? $add_array['country_id'] : "",
				"description"=>isset($add_array['product_description'])?$add_array['product_description']:"",
				"buy_cost"=>isset($add_array['product_buy_cost'])?$this->common_model->add_calculate_currency ($add_array['product_buy_cost'], "", '', false, false ):"0.00",
				"price"=>$this->common_model->add_calculate_currency ($add_array['price'], "", '', false, false ),
				"can_purchase"=>isset($add_array['can_purchase'])?$add_array['can_purchase']:"",
				"status"=>$add_array['status'],	
				"setup_fee"=>isset($add_array['setup_fee'])?$this->common_model->add_calculate_currency ($add_array['setup_fee'], "", '', false, false ):"0",
				"can_resell"=>isset($add_array['can_resell'])?$add_array['can_resell']:"0",
				//				"area_code"=>isset($add_array['area_code'])?$add_array['area_code']:"",
				"commission"=>isset($add_array['commission'])?$add_array['commission']:"0",
				"billing_type"=>isset($add_array['billing_type'])?$add_array['billing_type']:"",
				"billing_days"=>isset($add_array['billing_days'])?$add_array['billing_days']:"",
				"free_minutes"=>isset($add_array['free_minutes'])?$add_array['free_minutes']:"",
				"applicable_for"=>isset($add_array['applicable_for'])?$add_array['applicable_for']:0,
				"apply_on_rategroups"=>isset($add_array['rate_group'])?$add_array['rate_group']:0,
				"destination_rategroups"=>isset($editpatternSearchArr['destination_rategroups'])?implode(",",$editpatternSearchArr['destination_rategroups']):"",
				"destination_countries"=>isset($editpatternSearchArr['destination_countries'])?implode(",",$editpatternSearchArr['destination_countries']):"",
				"destination_calltypes"=>isset($editpatternSearchArr['destination_calltypes'])?implode(",",$editpatternSearchArr['destination_calltypes']):"",
				"release_no_balance"=>isset($add_array['release_no_balance'])?$add_array['release_no_balance']:"1",
				"created_by"=>$add_array['accountid'],
				"creation_date"=>gmdate("Y-m-d H:i:s"),
				"last_modified_date"=>gmdate("Y-m-d H:i:s")
		);

		$this->db->where ( "id", $id );
		$this->db->update ( "products", $update_array );
		if($accountinfo['type'] == 1){

			$reseller_products_array = array(
						"product_id"=>$add_array['id'],
						"account_id"=>isset($add_array['accountid'])?$add_array['accountid']:0,						"buy_cost"=>isset($add_array['product_buy_cost'])?$this->common_model->add_calculate_currency ($add_array['product_buy_cost'], "", '', false, false ):"0.00",
						"country_id"=>($add_array['country_id'] > 0) ? $add_array['country_id'] : "",
						"price"=>$this->common_model->add_calculate_currency ($add_array['price'], "", '', false, false ),
						"setup_fee"=>isset($add_array['setup_fee'])?$this->common_model->add_calculate_currency ($add_array['setup_fee'], "", '', false, false ):"0.00",
						"reseller_id"=>isset($accountinfo ['reseller_id'])?$accountinfo ['reseller_id']:0,
						"commission"=>isset($add_array['commission'])?$this->common_model->add_calculate_currency ($add_array['commission'], "", '', false, false ):"0",
						"billing_type"=>isset($add_array['billing_type'])?$add_array['billing_type']:"",
						"billing_days"=>isset($add_array['billing_days'])?$add_array['billing_days']:"",
						"free_minutes"=>isset($add_array['free_minutes'])?$add_array['free_minutes']:"",
						"status"=>$add_array['status'],
						"is_owner"=>0,
						"is_optin"=>1
					);
			$this->db->where ( "product_id", $add_array['id'] );
			$this->db->where ( "account_id", $add_array['accountid'] );
			$this->db->update ( "reseller_products", $reseller_products_array );
			$reseller_products_last_id = $this->db->insert_id();
		}
		$edit_pattern_array = array();
		if(isset($editpatternSearchArr) && $editpatternSearchArr != '' ){
			$edit_pattern_array = $this->get_pacakge_pattern($editpatternSearchArr);
		}
			if(isset($edit_pattern_array) && $edit_pattern_array !=''){
				$this->insert_pacakge_pattern($add_array['id'],$edit_pattern_array);
			}
		return true;
	}
	function remove_product($id) {
		$this->db->where ( "id", $id );
		$this->db->delete ( "products" );
		return true;
	}
	function products_release($product_info,$accountinfo){
		if($this->session->userdata ['userlevel_logintype'] == '-1'){
			$product_update_array  = array("is_deleted"=>1);
			$order_where = array("is_terminated"=>0,"product_id"=>$product_info['id']);
		}
		$this->db->where(array("id"=>$product_info['id']));
		$this->db->update("products",$product_update_array);
		$order_update_array = array("is_terminated"=>1,"termination_date"=>gmdate('Y-m-d H:i:s'),"termination_note"=> "Product (".$product_info['name'].") has been released by ".$accountinfo['number']."( ".$accountinfo['first_name']." ".$accountinfo['last_name'].") ");
		$this->db->where($order_where);
		$this->db->update("order_items",$order_update_array);
		return true;
	}
	function update_reseller_optin_product($add_array,$productid,$accountinfo){
		if($this->session->userdata ( 'logintype' ) == 1){
		 	if($accountinfo ['reseller_id'] > 0 ){
				$product_info = $this->db_model->getSelect ( "*", " reseller_products", array ('product_id' =>$productid,'reseller_products.account_id'=>$accountinfo['id'],'reseller_products.reseller_id'=>$accountinfo['reseller_id']));
			}else{
				$product_info = $this->db_model->getSelect ( "*", " reseller_products", array ('product_id' => $productid,'reseller_products.account_id'=>$accountinfo['id']));
				}
		}else{
			$product_info = $this->db_model->getSelect ( "*", " products", array ('id' => $productid,'status'=>0));
		}
		if($product_info->num_rows > 0)
		 {
		 $product_info = $product_info->result_array()[0];
		 $optin_product_update = array(
					"buy_cost"=>$this->common_model->add_calculate_currency ($product_info['price'], "", '', false, false ),
					"commission"=>$product_info['commission'],
					"setup_fee"=>$this->common_model->add_calculate_currency ($add_array['setup_fee'], "", '', false, false ),
					"price"=>$this->common_model->add_calculate_currency ($add_array['price'], "", '', false, false ),
					"free_minutes"=>$product_info['free_minutes'],
					"billing_type"=>$product_info['billing_type'],
					"billing_days"=>$product_info['billing_days'],
					"status"=>$add_array['status'],
					"is_optin"=>0,
					"is_owner"=>1,
					"modified_date"=>gmdate("Y-m-d H:i:s")
					);
		
			$this->db->where('product_id',$productid);
		        $this->db->where('account_id',$accountinfo['id']);
			$this->db->update("reseller_products",$optin_product_update);
			return true;
	  }

	}
	function get_product_category_dropdown($logintype){
		if ($logintype == 1 || $logintype == 5){
			$categoryinfo = $this->db_model->getSelect("GROUP_CONCAT(id) as id","category","code NOT IN ('REFILL','DID','PACKAGE')");
			$categoryinfo_arr = $categoryinfo->result_array();
			if($categoryinfo->num_rows > 0 && !empty($categoryinfo_arr[0]['id'])){
				$where_arr['where'] = $this->db->where("id IN (".$categoryinfo_arr[0]['id'].")", NULL, false);
				return $this->db_model->build_dropdown_products("id,name,code", "category", "", $where_arr);
			}
			return array();
		}

		return $this->db_model->build_dropdown_products("id,name,code,description", "category", "", "");
	}

	function get_existing_accounts_for_assignment($reseller_id, $product_rate_group){
		if (empty($product_rate_group)) {
			return array();
		}
		$this->db->select("*");
		$this->db->from("accounts");
		$this->db->where(array("status"=>0,"deleted"=>0,"type"=>0,"reseller_id"=>$reseller_id));
		$this->db->where_in("pricelist_id", $product_rate_group);
		$query = $this->db->get();
		return $query->num_rows() > 0 ? $query->result_array() : array();
	}

	function get_active_customer_account($account_id){
		$query = $this->db_model->getSelect("*", "accounts", array("id"=>$account_id,"status"=>0,"deleted"=>0,"type"=>0));
		return $query->num_rows() > 0 ? $query->result_array()[0] : array();
	}

	function delete_selected_package_patterns($ids){
		$ids = preg_replace('/[^0-9,]/', '', (string)$ids);
		if ($ids === '') {
			return false;
		}
		$where = "id IN ($ids)";
		return $this->db->delete("package_patterns", $where);
	}

	private function apply_available_package_pattern_filters($productid, $reseller_id, $search = array()){
		$where = '(pattern NOT IN (select DISTINCT patterns from package_patterns where product_id = "' . (int)$productid . '" and reseller_id = "'.(int)$reseller_id.'"))';
		$this->db->where($where);
		if (!empty($search['destination_rategroups'])) {
			$this->db->where_in('pricelist_id', $search['destination_rategroups']);
		}
		if (!empty($search['destination_countries'])) {
			$this->db->where_in('country_id', $search['destination_countries']);
		}
		if (!empty($search['destination_calltypes'])) {
			$this->db->where_in('call_type', $search['destination_calltypes']);
		}
		if (isset($search['code']) && $search['code'] !== '') {
			$this->db->where('pattern', '^'.$search['code'].'.*');
		}
		if (isset($search['destination']) && $search['destination'] !== '') {
			$this->db->where('comment', $search['destination']);
		}
	}

	function count_available_package_pattern_routes($productid, $reseller_id, $search = array()){
		$this->apply_available_package_pattern_filters($productid, $reseller_id, is_array($search) ? $search : array());
		return $this->db->count_all_results('routes');
	}

	function get_available_package_pattern_routes($productid, $reseller_id, $search = array(), $limit = null, $start = null){
		$this->apply_available_package_pattern_filters($productid, $reseller_id, is_array($search) ? $search : array());
		$this->db->select('*');
		$this->db->from('routes');
		if ($limit !== null && $start !== null) {
			$this->db->limit($limit, $start);
		}
		return $this->db->get();
	}

	function delete_product_pattern($productid, $id){
		return $this->db->delete("package_patterns", array("id" => $id, "product_id" => $productid));
	}

	function bulk_delete_products($ids, $accountinfo){
		$ids = preg_replace('/[^0-9,]/', '', (string)$ids);
		if ($ids === '') {
			return;
		}
		$where = "id IN ($ids)";
		if($this->session->userdata('logintype') == 1){
			$this->db->where("product_id IN (".$ids.")", NULL, false);
			$product_info = (array) $this->db->get("reseller_products")->result_array();
			if(!empty($product_info)){
				foreach($product_info as $value){
					if($value['account_id'] == $accountinfo['id']){
						$this->db->where("id",$value['product_id']);
						$this->db->update("products",array("is_deleted"=>1));
					}
					$this->db->where("id",$value['id']);
					$this->db->update("reseller_products",array("is_optin"=>1));
				}
			}
		}else{
			$product_info = (array) $this->db->get_where("products", $where)->result_array();
			if(!empty($product_info)){
				foreach($product_info as $value){
					$where_arr['where'] = "product_id=".$value['id'];
					$order_item = $this->db_model->getSelect("*", "order_items", $where_arr);
					if($order_item->num_rows == 0){
						$this->db->where("id", $value['id']);
						if($this->session->userdata ['logintype'] == '1'){
							$this->db->where("created_by", $accountinfo['id']);
						}
						$this->db->update("products",array("is_deleted"=>1));
						$this->db->where("product_id", $value['id']);
						$this->db->update("reseller_products",array("is_optin"=>1,"modified_date"=>gmdate("Y-m-d H:i:s")));
					}
				}
			}
		}
	}

	private function apply_product_pattern_search($productid, $instant_search = ''){
		$where = array("product_id" => $productid);
		if (!empty($instant_search)) {
			$like_str = "(pattern like '%".$this->db->escape_like_str($instant_search)."%' OR patterns like '%".$this->db->escape_like_str($instant_search)."%' OR increment like '%".$this->db->escape_like_str($instant_search)."%')";
			$this->db->where($like_str, NULL, false);
		}
		return $where;
	}

	function count_product_patterns($productid, $instant_search = ''){
		$where = $this->apply_product_pattern_search($productid, $instant_search);
		return $this->db_model->countQuery("*", "package_patterns", $where);
	}

	function get_product_patterns($productid, $instant_search = '', $limit = 0, $start = 0){
		$where = $this->apply_product_pattern_search($productid, $instant_search);
		return $this->db_model->select("*", "package_patterns", $where, "id", "ASC", $limit, $start);
	}

	private function get_product_search_categoryinfo(){
		$categoryinfo = $this->db_model->getSelect("GROUP_CONCAT('''',id,'''') as id","category","code NOT IN ('REFILL','DID')");
		if($categoryinfo->num_rows > 0 ){
			return $categoryinfo->result_array()[0]['id'];
		}
		return "''";
	}

	function count_customer_products($accountid, $instant_search = ''){
		$select = "products.id as id,order_items.id as id1,products.name,order_items.price,order_items.free_minutes,order_items.setup_fee,order_items.billing_type,order_items.billing_days";
		$table = "products";
		$jionTable = array('order_items','accounts');
		$jionCondition = array('products.id = order_items.product_id','accounts.id = order_items.accountid');
		$type = array('left','inner');
		$categoryinfo = $this->get_product_search_categoryinfo();
		if (!empty($instant_search)) {
			$like_str = "(products.name like '%".$this->db->escape_like_str($instant_search)."%' OR  products.price like '%".$this->db->escape_like_str($instant_search)."%' OR  IF(order_items.billing_type=0, 'One Time', 'Recurring') like '%".$this->db->escape_like_str($instant_search)."%' OR  order_items.billing_days like '%".$this->db->escape_like_str($instant_search)."%' OR  products.free_minutes like '%".$this->db->escape_like_str($instant_search)."%')";
			$this->db->where($like_str, NULL, false);
		}
		$this->db->where("order_items.accountid",$accountid);
		$this->db->where("order_items.is_terminated",0);
		$this->db->where("products.product_category IN (".$categoryinfo.")",NULL, false);
		return $this->db_model->getCountWithJion($table, $select, '', $jionTable, $jionCondition, $type);
	}

	function get_customer_products($accountid, $instant_search = '', $limit = 0, $start = 0){
		$select = "products.id as id,order_items.id as id1,products.name,order_items.price,order_items.free_minutes,order_items.setup_fee,order_items.billing_type,order_items.billing_days";
		$table = "products";
		$jionTable = array('order_items','accounts');
		$jionCondition = array('products.id = order_items.product_id','accounts.id = order_items.accountid');
		$type = array('left','inner');
		$categoryinfo = $this->get_product_search_categoryinfo();
		if (!empty($instant_search)) {
			$like_str = "(products.name like '%".$this->db->escape_like_str($instant_search)."%' OR  products.price like '%".$this->db->escape_like_str($instant_search)."%' OR  IF(order_items.billing_type=0, 'One Time', 'Recurring') like '%".$this->db->escape_like_str($instant_search)."%' OR  order_items.billing_days like '%".$this->db->escape_like_str($instant_search)."%' OR  products.free_minutes like '%".$this->db->escape_like_str($instant_search)."%')";
			$this->db->where($like_str, NULL, false);
		}
		$this->db->where("order_items.accountid",$accountid);
		$this->db->where("order_items.is_terminated",0);
		$this->db->where("products.product_category IN (".$categoryinfo.")",NULL, false);
		return $this->db_model->getAllJionQuery($table, $select, '', $jionTable, $jionCondition, $type, $limit, $start, "ASC", 'id', "");
	}

	function get_reseller_optin_product_info($productid, $accountinfo, $for_save = false){
		if($accountinfo['reseller_id'] > 0){
			$temp_where = '(`reseller_products`.`account_id` = '.$accountinfo['reseller_id'].' AND `reseller_products`.`is_optin` = 0 OR `reseller_products`.`account_id` = '.$accountinfo['reseller_id'].' AND `reseller_products`.`is_owner` = 0)';
			$this->db->where($temp_where);
			$select = $for_save ? ' products.id,products.name,products.product_category,reseller_products.buy_cost,products.commission,reseller_products.price,reseller_products.billing_type,reseller_products.billing_days,reseller_products.setup_fee,reseller_products.free_minutes,products.status,products.last_modified_date,reseller_products.product_id' : ' products.id,products.name,products.product_category,products.buy_cost,products.country_id,products.commission,reseller_products.price,reseller_products.billing_type,reseller_products.billing_days,reseller_products.setup_fee,reseller_products.free_minutes,products.status,products.last_modified_date,reseller_products.product_id';
			$product_info = $this->db_model->getJionQuery('products', $select, array('reseller_products.product_id'=>$productid), 'reseller_products','products.id=reseller_products.product_id', 'inner', '' ,'','DESC','products.id');
		}else{
			$product_info = $this->db_model->getSelect ( "*", " products", array ('id' => $productid,'status'=>0));
		}
		if ($product_info->num_rows > 0) {
			return (array) ($for_save ? $product_info->result_array()[0] : $product_info->first_row());
		}
		return array();
	}

	function save_reseller_option($productid, $add_array, $accountinfo){
		$product_info = $this->get_reseller_optin_product_info($productid, $accountinfo, true);
		if(empty($product_info)){
			return false;
		}
		$add_array['billing_type'] = $product_info['billing_type'];
		$add_array['billing_days'] = $product_info['billing_days'];
		$add_array['commission'] = $product_info['commission'];
		$add_array['free_minutes'] = $product_info['free_minutes'];
		if(($accountinfo['reseller_id'] > 0 || $accountinfo['type'] == 1) && $accountinfo['is_distributor'] == 0 ){
			$add_array['buy_cost'] = $this->common_model->add_calculate_currency ($add_array['product_buy_cost'], "", '', false, false );
		}else{
			$add_array['buy_cost'] = $product_info['buy_cost'];
		}
		if($accountinfo['is_distributor'] == 0){
			$add_array['price']  = isset($add_array['price']) ? $this->common_model->add_calculate_currency ($add_array['price'], "", '', false, false ) : $this->common_model->add_calculate_currency ($product_info['price'], "", '', false, false );
			$add_array['setup_fee']  = isset($add_array['setup_fee']) ? $this->common_model->add_calculate_currency ($add_array['setup_fee'], "", '', false, false ) : $this->common_model->add_calculate_currency ($product_info['setup_fee'], "", '', false, false );
		}else{
			$add_array['price']  = $product_info['price'];
			$add_array['setup_fee']  = $product_info['setup_fee'];
		}
		$now = gmdate("Y-m-d H:i:s");
		$query = "INSERT INTO reseller_products (product_id, account_id, reseller_id,country_id,commission, setup_fee, price, free_minutes,buy_cost,billing_type, billing_days, status, is_optin,is_owner,optin_date,modified_date)
	VALUES(".(int)$productid.",".(int)$accountinfo['id'].", ".(int)$accountinfo['reseller_id'].", ".(int)$add_array['country_id'].",".$add_array['commission'].",'".$add_array['setup_fee']."','".$add_array['price']."',".$add_array['free_minutes'].",".$add_array['buy_cost'].",".$add_array['billing_type'].",".$add_array['billing_days'].", 0, 0, 1, '".$now."','".$now."') ON DUPLICATE KEY UPDATE product_id = VALUES(product_id), account_id = VALUES(account_id), reseller_id = VALUES(reseller_id), commission = VALUES(commission), setup_fee = VALUES(setup_fee), price = VALUES(price), free_minutes = VALUES(free_minutes), buy_cost = VALUES(buy_cost),billing_type = VALUES(billing_type), billing_days = VALUES(billing_days), status = VALUES(status), is_optin = VALUES(is_optin), is_owner = VALUES(is_owner), optin_date = VALUES(optin_date),modified_date = VALUES(modified_date)";
		return $this->db->query($query);
	}

	function toggle_product_optin($product_id, $status, $accountinfo){
		if($status == 'true'){
			$reseller_products_array = array(
				"product_id"=>$product_id,
				"account_id"=>$accountinfo['id'],
				"reseller_id"=>isset($accountinfo ['reseller_id'])?$accountinfo ['reseller_id']:0,
				"status"=>0,
				"creation_date"=>gmdate("Y-m-d H:i:s")
			);
			$this->db->insert("reseller_products", $reseller_products_array);
			return true;
		}
		if($status == 'false'){
			$orders = $this->db_model->getSelect("*","order_items",array("product_id"=>$product_id));
			if($orders->num_rows == 0){
				$this->db->where("product_id",$product_id);
				$this->db->delete("reseller_products");
			}else{
				$this->db->where("product_id",$product_id);
				$this->db->update("reseller_products",array("status"=>1));
			}
		}
		return true;
	}

	function get_active_account($account_id){
		return (array)$this->db->get_where("accounts",array("id"=>$account_id,"deleted"=>"0","status"=>"0"))->first_row();
	}

}
?>
