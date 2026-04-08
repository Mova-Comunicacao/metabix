<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Api
{
    /**
     * Options autoload=1
     * @var array
     */
    private $options = array();
    /**
     * CI Instance
     * @deprecated 1.9.8 Use $this->ci instead
     * @var object
     */
    private $_instance;
    /**
     * CI Instance
     * @var object
     */
    private $ci;
    /**
     * Media folder
     * @var string
     */
    private $media_folder;
    /**
     * Available languages
     * @var array
     */
    private $available_languages = array();
    
    public function __construct()
    {
        $this->ci = & get_instance();
        // @deprecated
        $this->_instance = $this->ci;

        $this->init();
        
        hooks()->do_action('app_base_after_construct_action');
    }  

    /**
     * Check if database upgrade is required
     * @param  string  $v
     * @return boolean
     */
    public function is_db_upgrade_required($v = '')
    {
        if (!is_numeric($v)) {
            $v = $this->get_current_db_version();
        }

        $this->ci->load->config('migration');
        if ((int) $this->ci->config->item('migration_version') !== (int) $v) {
            return true;
        }

        return false;
    }

    /**
     * Return current database version
     * @return string
     */
    public function get_current_db_version()
    {
        $this->ci->db->limit(1);

        return $this->ci->db->get(db_prefix() . 'migrations')->row()->version;
    }

    
    /**
     * Make request to server to get latest version info
     * @return mixed
     */
    public function get_update_info()
    {
        $lastUpdatedDate = get_option('last_updated_date');
        $dateInstall     = get_option('di');

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_USERAGENT      => $this->ci->agent->agent_string(),
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_URL            => UPDATE_INFO_URL,
            CURLOPT_POST           => 1,
            CURLOPT_POSTFIELDS     => [
                'identification_key'      => get_option('identification_key'),
                'base_url'                => site_url(),
                'installed_date'          => $dateInstall ? date('Y-m-d H:i:s', (int) $dateInstall) : null,
                'last_updated_date'       => $lastUpdatedDate ? date('Y-m-d H:i:s', (int) $lastUpdatedDate) : null,
                'current_version'         => $this->get_current_db_version(),
                'php_version'             => PHP_VERSION,
                'purchase_key'            => get_option('purchase_key'),
                'server_ip'               => $_SERVER['SERVER_ADDR'],
                'database_driver'         => $this->ci->db->platform() ?? null,
                'database_driver_version' => $this->ci->db->version() ?? null,
                'update_info'             => 'true',
                // For portal
                'installed_version' => wordwrap($this->get_current_db_version(), 1, '.', true) ?? null,
                'app_url'           => site_url(),
            ],
        ]);

        $result = curl_exec($curl);
        $error  = '';

        if (!$curl || !$result) {
            $error = 'Curl Error - Contact your hosting provider with the following error as reference: Error: "' . curl_error($curl) . '" - Code: ' . curl_errno($curl);
        }

        curl_close($curl);

        if ($error != '') {
            return $error;
        }

        return $result;
    }

    /**
     * Set the application identification key
     *
     * @param string|null $key
     */
    public function set_identification_key($key = null)
    {
        update_option('identification_key', $key ?: uniqid(rand() . time()));
    }

    /**
     * Return all available languages in the application/language folder
     * @return array
     */
    public function get_available_languages()
    {
        return hooks()->apply_filters('before_get_languages', $this->available_languages);
    }    

    /**
     * All available reminders keys for the features
     * @return array
     */
    public function get_available_reminders_keys()
    {
        return $this->available_reminders;
    }    

    /**
     * Get all db options
     * @return array
     */
    public function get_options()
    {
        return $this->options;
    }    

    /**
     * Function that gets option based on passed name
     * @param  string $name
     * @return string
     */
    public function get_option($name)
    {
        $val  = '';
        $name = trim($name);

        if (!isset($this->options[$name])) {
            // is not auto loaded
            $this->ci->db->select('value');
            $this->ci->db->where('name', $name);
            $row = $this->ci->db->get(db_prefix() . 'options')->row();
            if ($row) {
                $val = $row->value;
            }
        } else {
            $val = $this->options[$name];
        }

        return hooks()->apply_filters('get_option', $val, $name);
    }    

    /**
     * Init necessary data
     */
    protected function init()
    {
        // Temporary checking for v1.8.0
        if ($this->ci->db->field_exists('autoload', db_prefix() . 'options')) {
            $options = $this->ci->db->select('name, value')
            ->where('autoload', 1)
            ->get(db_prefix() . 'options')->result_array();
        } else {
            $options = $this->ci->db->select('name, value')
            ->get(db_prefix() . 'options')->result_array();
        }
        
        // Loop the options and store them in a array to prevent fetching again and again from database
        foreach ($options as $option) {
            $this->options[$option['name']] = $option['value'];
        }  
        /*
        
        */

        foreach (list_folders(APPPATH . 'language') as $language) {
            if (is_dir(APPPATH.'language/'.$language)) {
                array_push($this->available_languages, $language);
            }
        }   
              
    }       
}