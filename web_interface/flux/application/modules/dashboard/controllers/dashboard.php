<?php

// ##############################################################################
// Flux SBC - Unindo pessoas e negócios
//
// Copyright (C) 2022 Flux Telecom
// Daniel Paixao <daniel@flux.net.br>
// Flux SBC Version 4.0 and above
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

class Dashboard extends MX_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->helper('form');
		$this->load->model('Auth_model');
		$this->load->library('flux/form');
		$this->load->model('Flux_common');
		$this->load->model('dashboard_model');
		$this->load->library('freeswitch_lib');
		$this->load->library('flux_log');
		$this->load->library('FLUX_Sms');

		$accountinfo = $this->session->userdata('accountinfo');
		if ($accountinfo['type'] == '0' || $accountinfo['type'] == '3') {
			redirect(base_url() . 'user/user/');
		}
	}

	private function _get_reseller_id()
	{
		$accountinfo = $this->session->userdata('accountinfo');
		return ($accountinfo['type'] == '1') ? $accountinfo['id'] : '0';
	}

	private function _get_scope_field()
	{
		$userlevel = $this->session->userdata('userlevel_logintype');
		return ($userlevel != 0 && $userlevel != 3) ? 'reseller_id' : 'accountid';
	}

	private function _get_parent_id()
	{
		$accountinfo = $this->session->userdata('accountinfo');
		return ($accountinfo['type'] == 1) ? $accountinfo['id'] : 0;
	}

	private function _get_date_range($post)
	{
		$year  = isset($post['year'])  && $post['year']  > 0 ? (int)$post['year']  : (int)date("Y");
		$month = isset($post['month']) && $post['month'] > 0 ? (int)$post['month'] : (int)date("m");

		if (isset($post['drop_val']) && $post['drop_val'] == "t_week") {
			$start_date = (date('D') != 'Mon') ? date('Y-m-d', strtotime('last Monday')) : date('Y-m-d');
			$end_date   = date('Y-m-d');
		} else {
			$start_date = date($year . '-' . sprintf('%02d', $month) . '-01');
			$end_day    = ($year == date("Y") && $month == date("m"))
			            ? date("d")
			            : cal_days_in_month(CAL_GREGORIAN, $month, $year);
			$gmtoffset  = $this->common->get_timezone_offset();
			$end_date_str = date($year . "-" . sprintf('%02d', $month) . "-" . $end_day . ' H:i:s');
			$end_date   = date('Y-m-d', strtotime($end_date_str) + $gmtoffset);
		}

		return array($start_date, $end_date);
	}

	function index()
	{
		if ($this->session->userdata('user_login') == FALSE) {
			redirect(base_url() . 'login/login');
		}

		$data['page_title'] = gettext('Dashboard');

		if ($this->session->userdata('logintype') == 0) {
			$this->load->view('view_user_dashboard', $data);
		} else {
			$data['dashboard_flag'] = true;
			$accountinfo = $this->session->userdata('accountinfo');
			$reseller_id = ($accountinfo['type'] == '1') ? $accountinfo['id'] : 0;
			$data['low_balance_accounts'] = $this->dashboard_model->get_low_balance_accounts($reseller_id);
			$data['currency'] = $this->common->get_field_name("currency", "currency", array("id" => $accountinfo['currency_id']));
			$this->load->view('view_dashboard', $data);
		}
	}

	function user_recent_payments()
	{
		$this->customerReport_recent_payments();
	}

	function customerReport_recent_payments()
	{
		$accountinfo = $this->session->userdata('accountinfo');
		$currency = $this->common->get_field_name('currency', 'currency', array("id" => $accountinfo['currency_id']));

		$json_data = array();
		$result    = $this->dashboard_model->get_recent_recharge();
		$gmtoffset = $this->common->get_timezone_offset();

		if ($result->num_rows() > 0) {
			$account_arr = $this->common->get_array('id,number,first_name,last_name', 'accounts', '');

			$json_data[0]['accountid']    = 'Accounts';
			$json_data[0]['credit']       = 'Amount(' . $currency . ")";
			$json_data[0]['payment_date'] = 'Date';

			$i = 1;
			foreach ($result->result_array() as $data) {
				$data['accountid'] = ($data['accountid'] != '' && isset($account_arr[$data['accountid']]))
					? $account_arr[$data['accountid']]
					: "Anonymous";

				$json_data[$i]['accountid']    = $data['accountid'];
				$json_data[$i]['credit']       = $this->common_model->calculate_currency($data['credit'], '', '', true, false);
				$json_data[$i]['payment_date'] = date('Y-m-d H:i:s', strtotime($data['payment_date']) + $gmtoffset);
				$i++;
			}
		}

		echo json_encode($json_data);
	}

	function user_call_statistics_with_profit()
	{
		$this->customerReport_call_statistics_with_profit();
	}

	function customerReport_call_statistics_with_profit()
	{
		$post = $this->input->post();
		list($start_date, $end_date) = $this->_get_date_range($post);

		$json_data      = array();
		$parent_id      = $this->_get_parent_id();
		$customerresult = $this->dashboard_model->get_call_statistics('cdrs_day_by_summary', $parent_id, $start_date, $end_date);

		$acc_arr = array();
		$customer_total_result = array(
			'sum' => 0, 'answered' => 0, 'mcd' => 0, 'duration' => 0,
			'failed' => 0, 'profit' => 0, 'debit' => 0, 'cost' => 0,
			'completed' => 0, 'billable' => 0
		);
		$mcd = 0;

		if ($customerresult->num_rows > 0) {
			foreach ($customerresult->result_array() as $data) {
				$acc_arr[$data['day']] = $data;
				$customer_total_result['sum']       += $data['sum'];
				$customer_total_result['answered']   += $data['answered'];
				if ($data['mcd'] > $mcd) {
					$mcd = $data['mcd'];
				}
				$customer_total_result['mcd']       = $mcd;
				$customer_total_result['duration']   += $data['duration'];
				$customer_total_result['billable']   += $data['billable'];
				$customer_total_result['failed']     += $data['failed'];
				$customer_total_result['profit']     += $data['profit'];
				$customer_total_result['debit']      += $data['debit'];
				$customer_total_result['cost']       += $data['cost'];
				$customer_total_result['completed']  += $data['completed'];
			}
		}

		$begin     = new DateTime($start_date);
		$end       = (new DateTime($end_date))->modify('+1 day');
		$daterange = new DatePeriod($begin, new DateInterval('P1D'), $end);

		foreach ($daterange as $date) {
			$json_data['date'][] = $date->format("d");
			$day = (int)$date->format("d");

			if (isset($acc_arr[$day])) {
				$asr = ($acc_arr[$day]['sum'] > 0)
					? round(($acc_arr[$day]['completed'] / $acc_arr[$day]['sum']) * 100, 2) : 0;
				$acd = ($acc_arr[$day]['completed'] > 0)
					? round($acc_arr[$day]['billable'] / $acc_arr[$day]['completed'], 2) : 0;

				$json_data['total'][]    = array((string)$acc_arr[$day]['day'], (int)$acc_arr[$day]['sum']);
				$json_data['answered'][] = array((string)$acc_arr[$day]['day'], (int)$acc_arr[$day]['answered']);
				$json_data['failed'][]   = array((string)$acc_arr[$day]['day'], (int)$acc_arr[$day]['failed']);
				$json_data['profit'][]   = array((string)$acc_arr[$day]['day'], (float)str_replace(",", "", $this->common_model->calculate_currency($acc_arr[$day]['profit'])));
				$json_data['acd'][]      = array((string)$acc_arr[$day]['day'], (float)$acd);
				$json_data['mcd'][]      = array((string)$acc_arr[$day]['day'], (float)$acc_arr[$day]['mcd']);
				$json_data['asr'][]      = array((string)$acc_arr[$day]['day'], (float)$asr);
			} else {
				$d = $date->format("d");
				$json_data['total'][]    = array($d, 0);
				$json_data['answered'][] = array($d, 0);
				$json_data['failed'][]   = array($d, 0);
				$json_data['profit'][]   = array($d, 0);
				$json_data['acd'][]      = array($d, 0);
				$json_data['mcd'][]      = array($d, 0);
				$json_data['asr'][]      = array($d, 0);
			}
		}

		$json_data['total_count']['sum']       = $customer_total_result['sum'];
		$json_data['total_count']['debit']     = $this->common_model->to_calculate_currency($customer_total_result['debit'], '', '', true, true);
		$json_data['total_count']['cost']      = $this->common_model->to_calculate_currency($customer_total_result['cost'], '', '', true, true);
		$json_data['total_count']['profit']    = $this->common_model->to_calculate_currency($customer_total_result['profit'], '', '', true, true);
		$json_data['total_count']['completed'] = $customer_total_result['completed'];
		$json_data['total_count']['duration']  = $customer_total_result['duration'];
		$json_data['total_count']['billable']  = $customer_total_result['billable'];

		$completed = $json_data['total_count']['completed'];
		$billable  = $json_data['total_count']['billable'];
		$sum       = $json_data['total_count']['sum'];

		$json_data['total_count']['acd'] = ($completed > 0) ? round($billable / $completed, 2) : 0;
		$json_data['total_count']['mcd'] = ($customer_total_result['mcd'] > 0) ? $customer_total_result['mcd'] : 0;
		$json_data['total_count']['asr'] = ($sum > 0)
			? $this->common_model->format_currency(round(($completed / $sum) * 100, 2))
			: 0;

		echo json_encode($json_data);
	}

	function user_maximum_callminutes()
	{
		$this->customerReport_maximum_callminutes();
	}

	function customerReport_maximum_callminutes()
	{
		$post = $this->input->post();
		list($start_date, $end_date) = $this->_get_date_range($post);

		$parent_id   = $this->_get_parent_id();
		$scope_field = $this->_get_scope_field();
		$result      = $this->dashboard_model->get_customer_maximum_callminutes($start_date, $end_date, $parent_id, $scope_field);

		$accountinfo = $this->session->userdata('accountinfo');
		$reseller_id = ($accountinfo['type'] == -1 || $accountinfo['type'] == 2) ? 0 : $accountinfo['id'];

		if ($scope_field == 'reseller_id') {
			$account_arr = $this->common->get_array('id,number,company_name', 'accounts', array('reseller_id' => $reseller_id));
		} else {
			$account_arr = $this->common->get_array('id,number,company_name', 'accounts', array('id' => $reseller_id));
		}

		$json_data = array();
		$i = 0;
		if ($result->num_rows() > 0) {
			foreach ($result->result_array() as $data) {
				$data['accountid'] = ($data['account_id'] != '' && isset($account_arr[$data['account_id']]))
					? $account_arr[$data['account_id']]
					: "Anonymous";
				$json_data[$i][] = $data['accountid'];
				$json_data[$i][] = round($data['billseconds'] / 60, 0);
				$i++;
			}
		} else {
			$json_data[] = array();
		}

		echo json_encode($json_data);
	}

	function user_maximum_callcount()
	{
		$this->customerReport_maximum_callcount();
	}

	function customerReport_maximum_callcount()
	{
		$post = $this->input->post();
		list($start_date, $end_date) = $this->_get_date_range($post);

		$parent_id   = $this->_get_parent_id();
		$scope_field = $this->_get_scope_field();
		$result      = $this->dashboard_model->get_customer_maximum_callcount($start_date, $end_date, $parent_id, $scope_field);

		$accountinfo = $this->session->userdata('accountinfo');
		$reseller_id = ($accountinfo['type'] == -1 || $accountinfo['type'] == 2) ? 0 : $accountinfo['id'];

		if ($scope_field == 'reseller_id') {
			$account_arr = $this->common->get_array('id,number,first_name,last_name', 'accounts', array('reseller_id' => $reseller_id));
		} else {
			$account_arr = $this->common->get_array('id,number,first_name,last_name', 'accounts', array('id' => $reseller_id));
		}

		$json_data = array();
		$i = 0;
		if ($result->num_rows() > 0) {
			foreach ($result->result_array() as $data) {
				$data['accountid'] = ($data['account_id'] != '' && isset($account_arr[$data['account_id']]))
					? $account_arr[$data['account_id']]
					: "Anonymous";
				$json_data[$i][] = $data['accountid'];
				$json_data[$i][] = (int)$data['call_count'];
				$i++;
			}
		} else {
			$json_data[] = array();
		}

		echo json_encode($json_data);
	}

	function customerReport_maximum_countrycount()
	{
		$post = $this->input->post();
		list($start_date, $end_date) = $this->_get_date_range($post);

		$parent_id   = $this->_get_parent_id();
		$scope_field = $this->_get_scope_field();
		$result      = $this->dashboard_model->get_customer_maximum_countrycount($start_date, $end_date, $parent_id, $scope_field);

		$country_arr = $this->common->get_array('id,call_type', 'calltype', "");

		$json_data = array();
		$i = 0;
		if ($result->num_rows() > 0) {
			foreach ($result->result_array() as $data) {
				$data['call_type_id'] = ($data['call_type_id'] != '' && isset($country_arr[$data['call_type_id']]))
					? $country_arr[$data['call_type_id']]
					: "Anonymous";
				$json_data[$i][] = $data['call_type_id'];
				$json_data[$i][] = (int)$data['call_count'];
				$i++;
			}
		} else {
			$json_data[] = array();
		}

		echo json_encode($json_data);
	}

	function customerReport_maximum_countryminutes()
	{
		$post = $this->input->post();
		list($start_date, $end_date) = $this->_get_date_range($post);

		$parent_id   = $this->_get_parent_id();
		$scope_field = $this->_get_scope_field();
		$result      = $this->dashboard_model->get_customer_maximum_countryminutes($start_date, $end_date, $parent_id, $scope_field);

		$country_arr = $this->common->get_array('id,call_type', 'calltype', "");

		$json_data = array();
		$i = 0;
		if ($result->num_rows() > 0) {
			foreach ($result->result_array() as $data) {
				$data['call_type_id'] = ($data['call_type_id'] != '' && isset($country_arr[$data['call_type_id']]))
					? $country_arr[$data['call_type_id']]
					: "Anonymous";
				$json_data[$i][] = $data['call_type_id'];
				$json_data[$i][] = round($data['billseconds'] / 60, 0);
				$i++;
			}
		} else {
			$json_data[] = array();
		}

		echo json_encode($json_data);
	}

	function customerReport_calculation()
	{
		$reseller_id = $this->_get_reseller_id();

		$today_result = $this->dashboard_model->get_summary_stats(
			$reseller_id,
			date("Y-m-d 00:00:00"),
			date("Y-m-d 23:59:59")
		);
		$today = (array)$today_result->first_row();

		$today['mcd']         = ($today['mcd'] != '')         ? $today['mcd']                     : '0';
		$today['total_calls'] = ($today['total_calls'] != '') ? $today['total_calls']             : '0';
		$today['ACD']         = ($today['ACD'] != '')         ? round($today['ACD'])              : '0';
		$today['total_debit'] = $this->common_model->calculate_currency(($today['total_debit'] != '') ? $today['total_debit'] : 0);
		$today['total_cost']  = $this->common_model->calculate_currency(($today['total_cost'] != '')  ? $today['total_cost']  : 0);
		$today['profit']      = $this->common_model->calculate_currency(($today['profit'] != '')      ? $today['profit']      : 0);

		$month_result = $this->dashboard_model->get_summary_stats(
			$reseller_id,
			date('Y-m-01 00:00:00'),
			date('Y-m-d H:i:s')
		);
		$month = (array)$month_result->first_row();

		$result_array = array_merge($today, array(
			'total_calls_month' => ($month['total_calls'] != '') ? $month['total_calls'] : '0',
			'total_debit_month' => $this->common_model->calculate_currency(($month['total_debit'] != '') ? $month['total_debit'] : 0),
			'total_cost_month'  => $this->common_model->calculate_currency(($month['total_cost'] != '')  ? $month['total_cost']  : 0),
			'profit_month'      => $this->common_model->calculate_currency(($month['profit'] != '')      ? $month['profit']      : 0),
			'mcd_month'         => ($month['mcd'] != '')         ? $month['mcd']        : '0',
			'ACD_month'         => ($month['ACD'] != '')         ? round($month['ACD']) : '0',
			'ASR_month'         => ($month['ASR'] != '')         ? $month['ASR']        : '0',
		));

		echo json_encode($result_array);
	}

	function account_count()
	{
		$post = $this->input->post();
		list($start_date, $end_date) = $this->_get_date_range($post);

		$reseller_id = $this->_get_reseller_id();

		$result = $this->dashboard_model->get_count(
			'accounts', 'creation',
			$start_date . ' 00:00:00', $end_date . ' 23:59:59',
			$reseller_id
		);
		$count = (array)$result->first_row();
		$count['count'] = (!empty($count['count'])) ? $count['count'] : 0;

		echo json_encode($count);
	}

	function call_count()
	{
		$post = $this->input->post();
		list($start_date, $end_date) = $this->_get_date_range($post);

		$reseller_id = $this->_get_reseller_id();

		$result = $this->dashboard_model->get_sum(
			'cdrs_day_by_summary', 'total_calls', 'calldate',
			$start_date . ' 00:00:00', $end_date . ' 23:59:59',
			$reseller_id
		);
		$row = (array)$result->first_row();
		$count = array('total_calls' => (!empty($row['total'])) ? $row['total'] : 0);

		echo json_encode($count);
	}

	function orders_count()
	{
		$post = $this->input->post();
		list($start_date, $end_date) = $this->_get_date_range($post);

		$reseller_id = $this->_get_reseller_id();

		$result = $this->dashboard_model->get_count(
			'orders', 'order_date',
			$start_date . ' 00:00:00', $end_date . ' 23:59:59',
			$reseller_id
		);
		$count = (array)$result->first_row();
		$count['count'] = (!empty($count['count'])) ? $count['count'] : 0;

		echo json_encode($count);
	}

	function orders_fail_count()
	{
		$post = $this->input->post();
		list($start_date, $end_date) = $this->_get_date_range($post);

		$reseller_id = $this->_get_reseller_id();

		$result = $this->dashboard_model->get_count(
			'view_status_pedidos', 'order_date',
			$start_date . ' 00:00:00', $end_date . ' 23:59:59',
			$reseller_id,
			'order_status <> "Pedido Ativo"'
		);
		$count = (array)$result->first_row();
		$count['count'] = (!empty($count['count'])) ? $count['count'] : 0;

		echo json_encode($count);
	}

	function getrefill_value()
	{
		$post = $this->input->post();
		list($start_date, $end_date) = $this->_get_date_range($post);

		$reseller_id = $this->_get_reseller_id();

		$result = $this->dashboard_model->get_sum(
			'payment_transaction', 'amount', 'date',
			$start_date . ' 00:00:00', $end_date . ' 23:59:59',
			$reseller_id
		);
		$row = (array)$result->first_row();

		$result_refill = array();
		$result_refill['total_refill_amount'] = $this->common_model->calculate_currency_customer(
			(!empty($row['total'])) ? $row['total'] : 0
		);

		echo json_encode($result_refill);
	}

	function get_today_result()
	{
		$reseller_id = $this->_get_reseller_id();
		$today_start = date("Y-m-d 00:00:00");
		$today_end   = date("Y-m-d 23:59:59");

		$refill = $this->dashboard_model->get_sum(
			'payment_transaction', 'amount', 'date',
			$today_start, $today_end, $reseller_id
		);
		$refill_row = (array)$refill->first_row();
		$result_array['today_refill_amount'] = $this->common_model->calculate_currency(
			(!empty($refill_row['total'])) ? $refill_row['total'] : 0
		);

		$orders = $this->dashboard_model->get_count('orders', 'order_date', $today_start, $today_end, $reseller_id);
		$orders_row = (array)$orders->first_row();
		$result_array['today_order_count'] = (!empty($orders_row['count'])) ? $orders_row['count'] : 0;

		$fail_orders = $this->dashboard_model->get_count(
			'view_status_pedidos', 'order_date',
			$today_start, $today_end, $reseller_id,
			'order_status <> "Pedido Ativo"'
		);
		$fail_row = (array)$fail_orders->first_row();
		$result_array['today_order_fail_count'] = (!empty($fail_row['count'])) ? $fail_row['count'] : 0;

		$accounts = $this->dashboard_model->get_count(
			'accounts', 'creation',
			$today_start, $today_end, $reseller_id,
			'status = 0 AND deleted = 0'
		);
		$acc_row = (array)$accounts->first_row();
		$result_array['today_account_count'] = (!empty($acc_row['count'])) ? $acc_row['count'] : 0;

		$calls = $this->dashboard_model->get_sum(
			'cdrs_day_by_summary', 'total_calls', 'calldate',
			$today_start, $today_end, $reseller_id
		);
		$calls_row = (array)$calls->first_row();
		$result_array['today_total_calls'] = (!empty($calls_row['total'])) ? $calls_row['total'] : 0;

		echo json_encode($result_array);
	}

	function get_trunk_stats()
	{
		$post = $this->input->post();
		list($start_date, $end_date) = $this->_get_date_range($post);

		$accountinfo = $this->session->userdata('accountinfo');

		$reseller_id = null;
		if ($accountinfo['type'] == '1') {
			$reseller_id = (int)$accountinfo['id'];
		}

		$query = $this->dashboard_model->get_trunk_stats(
			$start_date . ' 00:00:00',
			$end_date . ' 23:59:59',
			$reseller_id
		);

		$result = array();
		foreach ($query->result_array() as $row) {
			$attempts  = (int)$row['attempts'];
			$completed = (int)$row['completed'];
			$asr       = ($attempts > 0) ? round(($completed / $attempts) * 100, 2) : 0;

			$result[] = array(
				'trunk'     => $this->common->get_field_name('name', 'trunks', $row['trunk_id']),
				'attempts'  => $attempts,
				'completed' => $completed,
				'asr'       => $asr,
			);
		}

		echo json_encode($result);
	}

	function customerReport_error_codes()
	{
		$post       = $this->input->post();
		$account_id = (isset($post['account_id']) && (int)$post['account_id'] > 0) ? (int)$post['account_id'] : null;

		list($start_date, $end_date) = $this->_get_date_range($post);

		$parent_id   = $this->_get_parent_id();
		$scope_field = $this->_get_scope_field();

		$result = $this->dashboard_model->get_error_code_stats(
			$start_date, $end_date, $parent_id, $scope_field, $account_id
		);

		$json_data = array();
		$i = 0;
		if ($result->num_rows() > 0) {
			foreach ($result->result_array() as $data) {
				$json_data[$i][] = $data['disposition'];
				$json_data[$i][] = (int)$data['call_count'];
				$i++;
			}
		} else {
			$json_data[] = array();
		}

		echo json_encode($json_data);
	}

	function customerReport_ddd_stats()
	{
		$post       = $this->input->post();
		$account_id = (isset($post['account_id']) && (int)$post['account_id'] > 0) ? (int)$post['account_id'] : null;

		list($start_date, $end_date) = $this->_get_date_range($post);

		$parent_id   = $this->_get_parent_id();
		$scope_field = $this->_get_scope_field();

		$result = $this->dashboard_model->get_ddd_stats(
			$start_date, $end_date, $parent_id, $scope_field, $account_id
		);

		$json_data = array();
		$i = 0;
		if ($result->num_rows() > 0) {
			foreach ($result->result_array() as $data) {
				$json_data[$i][] = $data['ddd'];
				$json_data[$i][] = (int)$data['call_count'];
				$i++;
			}
		} else {
			$json_data[] = array();
		}

		echo json_encode($json_data);
	}

	function get_accounts_list()
	{
		$accountinfo  = $this->session->userdata('accountinfo');
		$account_type = $accountinfo['type'];

		if ($account_type == -1 || $account_type == 2) {
			$query = $this->dashboard_model->get_accounts_list(null);
		} elseif ($account_type == 1) {
			$query = $this->dashboard_model->get_accounts_list((int)$accountinfo['id']);
		} else {
			echo json_encode(array());
			return;
		}

		$json_data = array();
		foreach ($query->result_array() as $row) {
			$label = !empty($row['company_name'])
				? $row['company_name']
				: trim($row['first_name'] . ' ' . $row['last_name']);
			$label .= ' (' . $row['number'] . ')';

			$json_data[] = array('id' => $row['id'], 'label' => $label);
		}

		echo json_encode($json_data);
	}

	function send_notification()
	{
		$accountid = $this->input->post('accountid', true);
		if (!empty($accountid)) {
			$accountinfo = $this->db_model->getSelect("*", 'accounts', array("id" => $accountid, "status" => 0, "deleted" => 0))->result_array();
			if (!empty($accountinfo)) {
				$this->common->mail_to_users('low_balance', $accountinfo[0]);
			}
		}
	}
}
