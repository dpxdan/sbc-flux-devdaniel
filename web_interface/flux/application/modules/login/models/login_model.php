<?php

class Login_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function get_account_by_username_or_email($username, $include_deleted = false)
    {
        $username = $this->db->escape_str($username);
        $where = "(number = '" . $username . "' OR email = '" . $username . "')";
        if (! $include_deleted) {
            $where .= " and deleted = 0";
        }
        $this->db->where($where, null, false);
        return (array) $this->db->get('accounts')->first_row();
    }

    public function get_permission($permission_id)
    {
        return (array) $this->db->get_where('permissions', array('id' => $permission_id))->first_row();
    }

    public function insert_login_activity($data)
    {
        return $this->db->insert('login_activity_report', $data);
    }

    public function get_invoice_conf_for_account($account)
    {
        $account_id = $this->resolve_invoice_conf_accountid($account);
        return (array) $this->db->get_where('invoice_conf', array('accountid' => $account_id))->first_row();
    }

    protected function resolve_invoice_conf_accountid($account)
    {
        if ($account['type'] == '2' || $account['type'] == '-1') {
            return 1;
        }

        if ($account['type'] == '0') {
            return ($account['reseller_id'] == 0) ? 1 : $account['reseller_id'];
        }

        if ($account['type'] == '1') {
            if ($account['reseller_id'] == 0) {
                return $this->has_invoice_conf($account['id']) ? $account['id'] : 1;
            }
            return $this->has_invoice_conf($account['reseller_id']) ? $account['reseller_id'] : 1;
        }

        return 1;
    }

    protected function has_invoice_conf($account_id)
    {
        $this->db->select('id');
        $this->db->where('accountid', $account_id);
        $this->db->limit(1);
        return $this->db->get('invoice_conf')->num_rows() > 0;
    }

    public function get_invoice_conf_by_domain($domain, $http_host)
    {
        $domain = $this->db->escape_like_str($domain);
        $http_host = $this->db->escape_like_str($http_host);
        $this->db->select('*');
        $this->db->where("domain LIKE '%{$domain}%'", null, false);
        $this->db->or_where("domain LIKE '%{$http_host}%'", null, false);
        $this->db->order_by('id', 'desc');
        $this->db->limit(1);
        return (array) $this->db->get('invoice_conf')->first_row();
    }

    public function get_invoice_conf_by_custom_domain_or_reseller($custom_domain, $http_host, $reseller_id)
    {
        $custom_domain = $this->db->escape_str($custom_domain);
        $http_host = $this->db->escape_str($http_host);
        $where = "domain in ('{$custom_domain}','{$http_host}')";
        $this->db->select('*');
        $this->db->where($where, null, false);
        $this->db->or_where('reseller_id', $reseller_id);
        $this->db->order_by('id', 'desc');
        $this->db->limit(1);
        return (array) $this->db->get('invoice_conf')->first_row();
    }

    public function get_system_value($name, $group_title)
    {
        $row = (array) $this->db->get_where('system', array(
            'name' => $name,
            'group_title' => $group_title
        ))->first_row();
        return isset($row['value']) ? $row['value'] : '';
    }

    public function get_pending_payment_transaction($item_number)
    {
        return (array) $this->db->get_where('payment_transaction', array(
            'transaction_details' => $item_number,
            'amount' => '0',
            'actual_amount' => '0',
            'user_currency' => ''
        ))->first_row();
    }

    public function delete_pending_payment_transaction($item_number)
    {
        $this->db->where(array('transaction_details' => $item_number));
        return $this->db->delete('payment_transaction');
    }

    public function get_account_by_id($account_id)
    {
        return (array) $this->db->get_where('accounts', array('id' => $account_id))->first_row();
    }

    public function get_currency_by_id($currency_id)
    {
        return (array) $this->db->get_where('currency', array('id' => $currency_id))->first_row();
    }

    public function insert_payment_transaction($data)
    {
        $this->db->insert('payment_transaction', $data);
        return $this->db->insert_id();
    }

    public function insert_payment($data)
    {
        $this->db->insert('payments', $data);
        return $this->db->insert_id();
    }

    public function get_last_invoice_number()
    {
        $this->db->select('invoiceid');
        $this->db->order_by('id', 'desc');
        $this->db->limit(1);
        $row = (array) $this->db->get('invoices')->first_row();
        return (isset($row['invoiceid']) && $row['invoiceid'] > 0) ? $row['invoiceid'] : 1;
    }

    public function get_receipt_invoice_conf($reseller_id)
    {
        $this->db->where("accountid IN ('" . (int) $reseller_id . "','1')", null, false);
        $this->db->select('*');
        $this->db->order_by('accountid', 'desc');
        $this->db->limit(1);
        return (array) $this->db->get('invoice_conf')->first_row();
    }

    public function insert_invoice_detail($data)
    {
        $this->db->insert('invoice_details', $data);
        return $this->db->insert_id();
    }

    public function generate_receipt($accountid, $amount, $accountinfo, $last_invoice_ID, $invoice_prefix, $due_date)
    {
        $invoice_data = array(
            'accountid' => $accountid,
            'invoice_prefix' => $invoice_prefix,
            'invoiceid' => '0000' . $last_invoice_ID,
            'reseller_id' => $accountinfo['reseller_id'],
            'invoice_date' => gmdate('Y-m-d H:i:s'),
            'from_date' => gmdate('Y-m-d H:i:s'),
            'to_date' => gmdate('Y-m-d H:i:s'),
            'due_date' => $due_date,
            'status' => 1,
            'balance' => $accountinfo['balance'],
            'amount' => $amount,
            'type' => 'R',
            'confirm' => '1'
        );
        $this->db->insert('invoices', $invoice_data);
        return $this->db->insert_id();
    }
}
