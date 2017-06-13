<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Model extends CI_Model{

    protected $table;
    protected $tableID;

    public function __construct()
    {
        parent::__construct();
        $this->db->reconnect();
    }

    public function set_table($table, $tableID)
    {
        $this->table   = $table;
        $this->tableID = $tableID;
    }

    public function get_by_id($id)
    {
        #Method Chaining APENAS PHP >= 5
        $this->db->select('*')
                    ->from($this->table)
                    ->where($this->tableID, $id);

        $query = $this->db->get();

        if($query->num_rows() > 0):
            return $query->row();
        endif;

        return false;
    }

    public function get_by_field($field, $value, $limit = NULL)
    {
        #Method Chaining APENAS PHP >= 5
        $this->db->select('*')
                    ->from($this->table)
                    ->where($field, $value);

        if(!$limit == NULL){
            $this->db->limit($limit);
        }

        $query = $this->db->get();

        if($limit == 1):
            return $query->row();
        endif;
        if ($query->num_rows() >= 1):
            return $query->result();
        endif;

        return FALSE;
    }

    public function get_by_fields(array $fields, $limit = null)
    {
        $this->db->reconnect();
        #Method Chaining APENAS PHP >= 5
        $this->db->select('*')
                    ->from($this->table);

        foreach ($fields as $key => $value) {
            $this->db->where($key, $value);
        }

        if(!$limit == null){
            $this->db->limit($limit);
        }
        $query = $this->db->get();

        if ($query->num_rows() >= 1):
            return $query->result();
        endif;

        return false;
    }

    public function get_all()
    {
        #Method Chaining APENAS PHP >= 5
        $this->db->select('*')
                    ->from($this->table);

        $query = $this->db->get();
        return $query->result();
    }

    public function insert($attributes)
    {
        if($this->db->insert($this->table, $attributes)):
            return $this->db->insert_id();
        endif;
        return false;
    }

    public function insert_batch($attributes)
    {
        if($this->db->insert_batch($this->table, $attributes)):
            return true;
        endif;
        return false;
    }

    public function update($id, $attributes)
    {
        $this->db->where($this->tableID, $id)->limit(1);

        if($this->db->update($this->table, $attributes)):
            return $this->db->affected_rows();
        endif;
        return false;
    }

    public function delete($id, $limit = null)
    {
        $this->db->where($this->tableID, $id);

        if(!$limit == null){
            $this->db->limit($limit);
        }

        if($this->db->delete($this->table)):
            return true;
        endif;
    }

    public function insertIfNotExists($attributes)
    {
        $checkProfile = $this->get_by_fields($attributes);
        if ($checkProfile == FALSE) {
            if($this->insert($attributes)) {
                return $this->db->insert_id();
            }
        }
        return FALSE;
    }

    public function createOrUpdate($attributes)
    {
        $checkProfile = $this->get_by_field('senderID', $attributes['senderID'], 1);
        if ($checkProfile == FALSE) {
            $attributes['created_at'] = date('Y-m-d H:i:s');
            if($this->insert($attributes)) {
                return $this->db->insert_id();
            }
            return FALSE;
        }
        $attributes['updated_at'] = date('Y-m-d H:i:s');
        $update = $this->update($checkProfile->profileID, $attributes);
        if (count($update) > 0) {
            return $checkProfile->profileID;
        }
        return FALSE;
    }
}