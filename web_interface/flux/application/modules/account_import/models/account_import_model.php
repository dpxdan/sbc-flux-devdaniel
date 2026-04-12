<?php

class Account_import_model extends CI_Model
{
    function __construct()
    {
        parent::__construct();
    }

    function get_active_pricelists($reseller_id)
    {
        $this->db->select('id,name');
        return $this->db->get_where('pricelists', array(
            'reseller_id' => $reseller_id,
            'status' => 0
        ))->result_array();
    }

    function get_sweeplist()
    {
        return $this->db->get_where('sweeplist')->result_array();
    }

    function get_first_active_sip_profile()
    {
        return (array) $this->db->get_where('sip_profiles', array(
            'status' => '0'
        ))->first_row();
    }

    function get_existing_account_by_number($number)
    {
        $this->db->select('id');
        return (array) $this->db->get_where('accounts', array(
            'number' => $number,
            'deleted' => 0
        ))->first_row();
    }

    function get_existing_account_by_email($email)
    {
        $this->db->select('id');
        return (array) $this->db->get_where('accounts', array(
            'email' => $email,
            'deleted' => 0
        ))->first_row();
    }

    function insert_account($data)
    {
        $this->db->insert('accounts', $data);
        return $this->db->insert_id();
    }

    function get_existing_sip_device($username)
    {
        return $this->db->select('id')->get_where('sip_devices', array(
            'username' => $username
        ))->first_row();
    }

    function insert_sip_devices_batch($data)
    {
        return $this->db->insert_batch('sip_devices', $data);
    }

    function get_active_localizations()
    {
        return $this->db->get_where('localization', array(
            'status' => 0
        ))->result_array();
    }

    function get_timezones()
    {
        return $this->db->get_where('timezone')->result_array();
    }

    function get_countries()
    {
        return $this->db->get_where('countrycode')->result_array();
    }

    function get_currencies()
    {
        return $this->db->get_where('currency')->result_array();
    }

    function get_taxes_by_reseller($reseller_id)
    {
        return $this->db->get_where('taxes', array(
            'reseller_id' => $reseller_id
        ))->result_array();
    }
    
    function insert_domains_batch($data)
    {
        return $this->db->insert_batch('domains_to_accounts', $data);
    }
    
    function get_active_domains()
    {
        return $this->db->get_where('domain', array(
            'status' => 0
        ))->result_array();
    }
    
    function get_all_active_domains()
    {
        $this->db->select('id, domain');
        return $this->db->get_where('domain', array('status' => 0))->result_array();
    }
    
    function get_domains_by_names(array $names)
    {
        if (empty($names)) {
            return array();
        }
    
        $names = array_map('trim', $names);
    
        $this->db->select('id, domain');
        $this->db->where_in('domain', $names);
        $this->db->where('status', 0);
        return $this->db->get('domain')->result_array();
    }
    
    function insert_account_domains(int $account_id, array $domain_ids)
    {
        if (empty($domain_ids)) {
            return false;
        }
    
        $batch = array();
        foreach ($domain_ids as $domain_id) {
            $batch[] = array(
                'accountid' => $account_id,
                'domain_id'  => (int) $domain_id,
            );
        }
    
        return $this->db->insert_batch('domains_to_accounts', $batch);
    }
}
