<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Wallet_model extends MY_Model{

    public function __construct()
    {
        parent::__construct();
        $this->table   = 'coin_wallet';
        $this->tableID = 'walletID';
    }
}