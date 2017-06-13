<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Cotacao_model extends MY_Model{

    public function __construct()
    {
        parent::__construct();
        $this->table   = 'coin_cotacao';
        $this->tableID = 'cotacaoID';
    }

    public function get_last($limit=NULL){
        $this->db->select('*')
                    ->from($this->table)
                    ->order_by($this->tableID, 'DESC');
        if ($limit) {
            $this->db->limit($limit);
        }

        $query = $this->db->get();

        if($query->num_rows() > 0):
            return $query->result();
        endif;

        return false;
    }
}