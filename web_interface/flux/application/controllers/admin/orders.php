<?php
defined('BASEPATH') or exit('No direct script access allowed');
require APPPATH . '/controllers/common/account.php';
class Orders extends Account
{
	protected $postdata = "";
	function __construct()
	{
		parent::__construct();
		$this->load->model('common_model');
		$this->load->library('common');
		$this->load->model('db_model');
		$this->load->model('Flux_common');
		$this->load->library('flux_log');
		$this->load->library('Form_validation');
		$this->load->library('flux/payment');
		$rawinfo = $this->post();
		$this->accountinfo = $this->get_account_info(); 
		if($this->accountinfo['type'] != '-1'  && $this->accountinfo ['type'] != '2'  && $this->accountinfo ['type'] != '1' ){
			$this->response ( array (
				'status'  => false,
				'error'   => $this->lang->line ( 'error_invalid_key' )
			), 400 );
		}
		foreach ($rawinfo as $key => $value) {
			$this->postdata[$key] = $this->_xss_clean($value, TRUE);
		}
	}
	public function index()
	{
		$accountid = $this->postdata ['id'];
		if($this->accountinfo['type'] == '1'){
			$where = array('id' => $this->accountinfo['id'] , 'type' => 1);
		}
		else{
			$type = array(-1,2);
			$where = array('id'=>$accountid,'deleted'=>0,'status'=>0);
		}
		$this->db->where($where);
		$this->db->where_in('type',$type);
		$accountinfo = (array)$this->db->get('accounts')->first_row();
		if(empty($accountinfo) || !isset($accountinfo)){
			$this->response ( array (
				'status'  => false,
				'error'   => $this->lang->line ( 'account_not_found' )
			), 400 );
		}
		$accountinfo = $this->_authorize_account ( $accountinfo,true,true);
		$function = isset ( $this->postdata ['action'] ) ? $this->postdata ['action'] : '';
		if ($function != '') {
			$function = '_' . $function;
			if (( int ) method_exists ( $this, $function ) > 0) {
				$this->$function ();
			} 
			else {
				$this->response ( array (
					'status' => false,
					'error' => $this->lang->line ( 'unknown_method' )
				), 400 );
			}
		} else {
			$this->response ( array (
				'status'=> false,
				'error' => $this->lang->line ( 'unknown_method' )
			), 400 );
		}
	}
	
	private function _reseller_orders_list(){
		$this->_orders_list();
	}
	private function _orders_list(){
		if (empty($this->postdata['end_limit']) || empty($this->postdata['start_limit']) ){
			if(!( $this->postdata['start_limit'] == '	0' || $this->postdata['end_limit'] == '0' )){
				$this->response ( array (
					'status' => false,
					'error' => $this->lang->line ( 'error_param_missing' ) . " integer:end_limit,integer:start_limit"
				), 400 );
			}else{
				$this->response ( array (
					'status' => false,
					'error' => $this->lang->line('number_greater_zero')
				), 400 );
			}
		}
		if(!($this->postdata['start_limit'] < $this->postdata['end_limit'])){
			$this->response ( array (
					'status' => false,
					'error' => $this->lang->line('valid_start_limit')
			), 400 );
		}
		$start = $this->postdata['start_limit']-1;
		$limit = $this->postdata['end_limit'];
		$no_of_records = (int)$limit - (int)$start;
		$object_where_params = $this->postdata['object_where_params'];
		if(!empty($object_where_params['from_date']) || !empty($object_where_params['to_date'])  ){
			$from_dates = DateTime::createFromFormat("Y-m-d H:i:s", $object_where_params['from_date']);
	       	$to_dates = DateTime::createFromFormat("Y-m-d H:i:s", $object_where_params['to_date']);
	       	if(empty($from_dates) || empty($to_dates)){
	       		$this->response ( array (
						'status' => false,
						'error' => $this->lang->line('invalid_date_format')
				), 400 );
	       	}
	       	else{
	       		if($this->postdata['action'] != 'reseller_orders_list'){
	       			$object_where_params_date['order_items.billing_date >='] = $this->timezone->convert_to_GMT_new ( $object_where_params['from_date'], '1' , $this->accountinfo['timezone_id']);
					$object_where_params_date['order_items.billing_date <='] = $this->timezone->convert_to_GMT_new ( $object_where_params['to_date'], '1',$this->accountinfo['timezone_id']);
	       		}
	       		else{
	       			$object_where_params_date['order_items.billing_date >='] =  $object_where_params['from_date'];
					$object_where_params_date['order_items.billing_date <='] =  $object_where_params['to_date'];
	       		}
				$this->db->where($object_where_params_date);
	       	}
		}
		unset($object_where_params['to_date'],$object_where_params['from_date']);
		foreach($object_where_params as $object_where_key => $object_where_value) {
			if($object_where_value != '') {	
				if(isset($object_where_key['accountid']) || $object_where_key == 'accountid'){
					$this->db->where('orders.accountid', $object_where_value);
				}else{
					$where[$object_where_key] = $object_where_value;
				}
				if(isset($object_where_key) && $object_where_key == 'subject'){
					$like_array['subject like'] = $object_where_params['subject'].'%';
				}
				if(isset($object_where_key) && $object_where_key == 'body'){
					$like_array['body like'] = $object_where_params['body'].'%';
				}
			}
		}
		if(!empty($where)) {
			unset($where['subject'],$where['body']); 
			$this->db->where($where);
		}
		if(!empty($like_array)) {
			$this->db->where($like_array); 
		}
		$this->db->order_by('id',DESC);
		if($this->accountinfo['type'] == '1' && $this->postdata['action'] != 'reseller_orders_list'){
			$this->db->where('reseller_id', $this->postdata['id']); 
		}
		if($this->postdata['action'] == 'reseller_orders_list'){
			$this->db->where('accountid', $this->postdata['id']); 
		}

					$this->db->select('orders.id,orders.order_id ,orders.order_date,orders.payment_gateway,(CASE WHEN order_items.`is_terminated`=0 THEN CONCAT("Ativo") ELSE CONCAT("Inativo") END) AS order_status,orders.payment_status,orders.reseller_id,orders.accountid,order_items.billing_date,order_items.termination_date,order_items.next_billing_date,order_items.product_id,order_items.setup_fee,order_items.price,products.name as product_name,category.name as category_name');
					$this->db->join('order_items', 'order_items.order_id = orders.id', 'inner');
					$this->db->join('products', 'products.id = order_items.product_id', 'left');
					$this->db->join('category', 'category.id = products.product_category', 'left');
					$this->db->order_by('order_items.billing_date', 'desc');
					$result = $this->db->get('orders');
					$count = $result -> num_rows();
					$orders_info = $result->result_array();

		foreach ($orders_info as $key => $orders_value) {
			$orders_value['billing_date'] = $this->timezone->convert_to_GMT_new($orders_value['billing_date'],'1',$this->accountinfo['timezone_id']);
			unset($orders_value['reseller_id'],$orders_value['accountid'],$orders_value['id']);
			$ordersinfo[] =$orders_value;
		}
    	if (!empty($ordersinfo)) {
			$this->response ( array (
				'status' => true,
				'total_count' => $count,
				'data' => $ordersinfo,
				'success' => $this->lang->line( "orders_list" )
			), 200 );
        }else{
			$this->response ( array (
				'status' => true,
				'data' => array(),
				'success' => $this->lang->line( "no_records_found" )
			), 200 );
		}
	}
}
