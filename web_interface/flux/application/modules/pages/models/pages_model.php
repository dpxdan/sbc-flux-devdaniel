<?php
class Pages_model extends CI_Model {
	function __construct() {
		parent::__construct();
	}

	function get_service_categories() {
		$this->db->from('category');
		$this->db->where("code <>", 'DID');
		$this->db->where("code <>", 'REFILL');
		$this->db->order_by("FIELD(id, '3', '1', '2')", '', false);
		return $this->db->get();
	}

	function get_reseller_service_products($reseller_id, $category, $country_id = null) {
		$this->db->select('products.id,products.name,products.product_category,products.country_id,products.buy_cost,products.commission,reseller_products.setup_fee,reseller_products.price,reseller_products.billing_type,reseller_products.billing_days,reseller_products.free_minutes,products.status,products.last_modified_date,reseller_products.product_id');
		$this->db->from('products');
		$this->db->join('reseller_products', 'products.id=reseller_products.product_id', 'inner');
		$this->db->where('reseller_products.status', 0);
		$this->db->where('products.can_purchase', 0);
		$this->db->where('products.is_deleted', 0);
		$this->db->where('products.product_category', $category);
		$this->db->where('reseller_products.account_id', $reseller_id);
		if ($country_id !== null && $country_id !== '') {
			$this->db->where('reseller_products.country_id', $country_id);
		}
		$this->db->group_start();
		$this->db->where('reseller_products.is_optin', 0);
		$this->db->or_where('reseller_products.is_owner', 0);
		$this->db->group_end();
		$this->db->order_by('products.id', 'desc');
		return $this->db->get();
	}

	function get_public_service_products($category, $country_id = null) {
		$this->db->from('products');
		$this->db->where('product_category', $category);
		$this->db->where('status', 0);
		$this->db->where('can_purchase', 0);
		$this->db->where('is_deleted', 0);
		$this->db->where('reseller_id', 0);
		if ($country_id !== null && $country_id !== '') {
			$this->db->where('country_id', $country_id);
		}
		$this->db->order_by('id', 'desc');
		return $this->db->get();
	}

	function get_topup_products() {
		return $this->db->get_where('products', array(
			'product_category' => 3,
			'is_deleted' => 0,
			'status' => 0,
			'can_purchase' => 0
		));
	}

	function get_topup_product_by_id($id) {
		return (array) $this->db->get_where('products', array(
			'product_category' => 3,
			'id' => $id
		))->first_row();
	}

	function get_account_by_id($id) {
		return (array) $this->db->get_where('accounts', array('id' => $id))->first_row();
	}

	function get_currency_by_id($id) {
		return (array) $this->db->get_where('currency', array('id' => $id))->first_row();
	}

	function get_recent_order_with_items($orderid, $from_datetime) {
		$this->db->select('orders.id as id, orders.order_id as orderid, orders.order_date, orders.payment_gateway, orders.payment_status, orders.reseller_id, orders.accountid, order_items.*');
		$this->db->from('orders');
		$this->db->join('order_items', 'orders.id=order_items.order_id', 'inner');
		$this->db->group_start();
		$this->db->where('orders.id', $orderid);
		$this->db->or_where('orders.order_id', $orderid);
		$this->db->group_end();
		$this->db->where('orders.order_date >=', $from_datetime);
		$query = $this->db->get();
		return $query->num_rows() > 0 ? $query->row_array() : array();
	}

	function create_payment_transaction($data) {
		$this->db->insert('payment_transaction', $data);
		return $this->db->insert_id();
	}

	function get_available_products_for_account($account_id, $reseller_id = 0) {
		$this->db->from('products');
		$this->db->where('reseller_id', $reseller_id);
		$this->db->where("id NOT IN (SELECT product_id FROM order_items WHERE accountid=" . (int) $account_id . ")", null, false);
		return $this->db->get();
	}

	function get_refill_coupon($number, $reseller_id = 0) {
		$where = array('number' => $number);
		if ((int) $reseller_id > 0) {
			$where['reseller_id'] = $reseller_id;
		}
		$query = $this->db->get_where('refill_coupon', $where);
		return $query->num_rows() > 0 ? $query->row_array() : array();
	}

	function mark_refill_coupon_used($number, $customer_id, $date) {
		$this->db->where('number', $number);
		return $this->db->update('refill_coupon', array(
			'status' => 2,
			'account_id' => $customer_id,
			'firstused' => $date
		));
	}
}
?>
