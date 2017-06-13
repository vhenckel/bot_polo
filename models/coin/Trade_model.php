<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Trade_model extends MY_Model{

    public function __construct()
    {
        parent::__construct();
        $this->table   = 'coin_trade';
        $this->tableID = 'tradeID';
    }
}