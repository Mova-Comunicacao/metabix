<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dashboard_model extends Api_Model
{
    private $is_admin;

    public function __construct()
    {
        parent::__construct();
       // $this->is_admin = is_admin();
    }    

}