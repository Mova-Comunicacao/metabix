<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Misc extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('misc_model');
    }

    public function get_countries()
    {
        $this->safe(function () {
            $countries = get_all_countries();
            $data = [];
            if(!empty($countries)){
                foreach ($countries as $c) {
                    $data[] = [
                        'short_name'    => $c['short_name'],
                        'country_id'    => (int) $c['country_id'],
                    ];
                }   
            }    
            return $this->respond($data, 200);
		});                      
    }
}