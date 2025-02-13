<?php
class Refactor extends MX_Controller {

public $repoPath = '/opt/flux';

    function __construct() {
        parent::__construct();
        $this->load->model("db_model");
        $this->load->library("flux/common");
        $this->load->library("flux_log");
    }

    public function getRate($pattern, $rates){
        $filtered = array_filter($rates, function($item) use ($pattern){
            return $item['pattern'] == $pattern;
        });

        $result = reset($filtered);
        return $result['cost'];
    }

    function index(){
        $query = $this->db->query("SELECT * FROM refactor where refactor_date < NOW() and status = 0");
        $result = $query->result_array();

        if ($result){
            // $this->flux_log->write_log("REFACTOR_CONTROLLER", json_encode($result));
            foreach ($result as $row){
                $data_status['status'] = 2;

                // update status
                $this->db->where("id", $row['id']);
                $this->db->update("refactor", $data_status);

                // get rates
                $this->db->where("id", $row['pricelist_id']);
                $result_pricelist = $this->db->get("pricelists");
                $pricelist = $result_pricelist->row_array();
                
                $rates = $this->db->get_where('routes', array(
                    'status' => '0',
                    'pricelist_id' => $row['pricelist_id'],
                ))->result_array();

                $patterns = array_column($rates, 'pattern');

                // get CDR
                $this->db->where_not_in('calltype', array('FREE', 'Gratuita', 'DID'));
                $cdrsData = $this->db->get_where('cdrs', array(
                    'accountid' => $row['account_id'],
                    'callstart >=' => $row['from_date'],
                    'callstart <=' => $row['to_date'],
                    'call_direction' => 'outbound',
                    'disposition' => 'NORMAL_CLEARING [16]',
                    'debit >' => 0,
                    'billseconds >' => 0,
                ))->result_array();
                 
                // refactor
                foreach ($cdrsData as $key) {                    
                    if (in_array($key['pattern'], $patterns)){

                        $cost = $this->getRate($key['pattern'], $rates);
                        if ($cost != $key['rate_cost']){
                            if ($key['billseconds'] <= 30){
                                $tariffedTime = 30;
                            } else {
                                $restTime = $key['billseconds'] - 30;
                                $tariffedTime = 30 + ceil($restTime / 6) * 6;
                            }
    
                            $calc_debit = round((($tariffedTime / 60) * $cost), 2);
                        } else {
                            $cost = $key['rate_cost'];
                            $calc_debit = $key['debit'];
                        }

                        $data['debit'] = $calc_debit;
                        $data['rate_cost'] = $cost;
                        $data['pricelist_id'] = $row['pricelist_id'];

                        $this->db->where("uniqueid", $key['uniqueid']);
                        $this->db->update("cdrs", $data);
                    }
                }
            }

            $data_status['status'] = 1;

            // update status
            $this->db->where("id", $row['id']);
            $this->db->update("refactor", $data_status);

        }

        exit;
    }

}