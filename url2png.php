<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CodeIgniter URL2PNG 
 * 
 * NOTICE OF LICENSE
 *
 * Licensed under the Academic Free License version 3.0
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * http://opensource.org/licenses/AFL-3.0
 *
 * @package     CodeIgniter
 * @author      Gavin Kimpson
 * @copyright   Copyright (c) Gavin Kimpson (http://www.gavk.co.uk)
 * @license     http://opensource.org/licenses/AFL-3.0 Academic Free License (AFL 3.0)
 * @version  	0.1
 */

// ------------------------------------------------------------------------

/**
 * URL2PNG library
 *
 * Library to be used in conjunction with a valid URL2PNG account. I DO NOT have any affiliation with URL2PNG so please do not contact 
 * me if there is a problem with their service :-)
 * This currently works with v6 of the URL2PNG API (not tested for earlier versions)
 *
 */
class URL2PNG
{
	/**
	 * The name of the table (for logging if enabled)	
	 * @var string
	 */
	public $table = 'url2png_log';

	/**
	 * The URL for URL2PNG API
	 * @var string
	 */
	public $url2png_url = 'http://api.url2png.com/v3/';

	/**
	 * API Key
	 * @var string
	 */
	public $api_key = '';

	/**
	 * Secret 'key'
	 * @var string
	 */
	public $secret = '';

	/**
	 * Enable logging to database
	 * @var bool	 
	 */
	public $logging = FALSE;

	/**
	 * Set viewpoint - Max 5000x5000
	 * @var string
	 */
	public $viewport = '640x480';	

	/**
	 * Set thumbnail max width
	 * @param 	string
	 */
	public $thumbnail_max_width = '200';

	/**
	 * Set thumbnail max height
	 * @param 	string
	 */
	public $thumbnail_max_height = '100';	

	/**
	 * Will attempt to capture entire document canvas.
	 * @param 	string
	 */
	public $fullpage = 'true';		

	/**
	 * Forces a fresh screenshot with each request, overwriting the previous copy
	 * @param 	string
	 */
	public $force = 'true';

	// ------------------------------------------------------------------------

	/**
	 * Setup all vars
	 *
	 * @param array $config
	 * @return void
	 */
	public function __construct($config = array())
	{
		// Get CI Instance
		$this->CI = &get_instance();

		$this->set_config($config);
		log_message('debug', 'URL2PNG Class Initialized');
	}

	// ------------------------------------------------------------------------

	/**
	 * Manually Set Config
	 *
	 * Pass an array of config vars to override previous setup
	 *
	 * @param   array
	 * @return  void
	 */
	public function set_config($config = array())
	{
		if ( ! empty($config))
		{
			foreach ($config as $key => $value)
			{
				$this->{$key} = $value;
			}
		}
	}

	/**
	 * Get URL2PNG via version 6 (beta)
	 * @param 	string 		$url
	 * @param 	array 		$args
	 * @return 	string
	 */
	public function url2png_v6($url = '', $args = array()) 
	{
		if (trim($url) == '') return FALSE;

		# urlencode request target
		$options['url'] = trim(urlencode($url));
	  	$options += $args;

	  	# create the query string based on the options
		foreach($options as $key => $value) 
		{
			$_parts[] = "$key=$value";
	  	}

		# create a token from the ENTIRE query string
		$query_string = implode("&", $_parts);
		$token = md5($query_string . $this->secret);

		return 'http://beta.url2png.com/v6/'. $this->api_key .'/'. $token .'/png/?'.$query_string;
	}

	/**
	 * Display image with viewport settings (beta)
	 * @param 	string 		$url
	 * @param 	array 		$options 		array options['force'], options['fullpage'], options['thumbnail_max_width'], options['viewport']
	 */
	public function display_image_with_viewport($url = '', $options = array())
	{
		$options['force'] = (array_key_exists('force', $options)) ? $options['force'] : $this->force;
		$options['fullpage'] = (array_key_exists('fullpage', $options)) ? $options['fullpage'] : $this->fullpage;
		$options['thumbnail_max_width'] = (array_key_exists('thumbnail_max_width', $options)) ? $options['thumbnail_max_width'] : $this->thumbnail_max_width;
		$options['thumbnail_max_height'] = (array_key_exists('thumbnail_max_height', $options)) ? $options['thumbnail_max_height'] : $this->thumbnail_max_height;
		$options['viewport'] = (array_key_exists('viewport', $options)) ? $options['viewport'] : $this->viewport;
		
		$source = $this->url2png_v6($url, $options);
		$img = ($source) ? sprintf('<img src="%s" title="%s">', $source, $url) : FALSE;
		return $img;
	}

	// ------------------------------------------------------------------------

	/**
	 * Get image from URL via URL2PNG API
	 *
	 * @param   string 		$url 	e.g 'google.co.uk'
	 * @param 	string 		$size 	e.g '800x600'
	 * @return  string
	 */
	private function get_image_from_url($url = '', $size = '800x600')
	{
		if (trim($url) == '') return FALSE;

        $url = urlencode(trim($url));
        $token = md5($this->secret .'+'. $url);
        return $this->url2png_url . $this->api_key .'/'. $token .'/'. $size .'/'. $url;     
	}

	/**
	 * Display the image
	 * @param   string 		$url 	e.g 'google.co.uk'
	 * @param 	string 		$size 	e.g '800x600'
	 * @return  string		
	 */
	public function display_image($url = '', $size = '800x600')
	{
		if (trim($url) == '') return FALSE;

		$source = $this->get_image_from_url($url, $size);
		$img = ($source) ? sprintf('<img src="%s" title="%s">', $source, $url) : false;
		return $img;
	}

	/**
	 * Save image locally to your own server - this will NOT overwrite existing filename
	 *
	 * @param 	string 		$url 		e.g 'google.co.uk'
	 * @param 	string 		$size 		e.g '800x600'
	 * @param 	string 		$path 		e.g '/path/to/your/images/' including BOTH the initial & last backslash
	 * @param 	string 		$file 		e.g 'your-screenshot.jpg'
	 * @return 	void
	 */
	public function save_image($url = '', $size = '800x600', $path = '/images/screens/', $file = 'your-screenshot.jpg')
	{
		if (trim($url) == '') return FALSE;

        $file_loc = $_SERVER['DOCUMENT_ROOT'].$path.$file;

        if (!file_exists($file_loc))
        {   
            $img_file = file_get_contents($this->get_image_from_url($url, $size));
            $file_handler = fopen($file_loc, 'w');

            if(fwrite($file_handler, $img_file) == FALSE)
            {
                echo 'an error occured';
            }

            fclose($file_handler);

            if ($this->logging == TRUE)
            {
				$this->_log_to_db($file);
            }            
        }
        else
        {
        	return FALSE;
        }
	}

	/**
	 * Log info to database
	 * @param 	string 		$file 		e.g 'your-screenshot.jpg'
	 * @return 	void
	 */
	private function _log_to_db($file = '')
	{
		$data = array('file' => $file, 'date_created' => date('Y-m-d H:i:s'));
		$this->CI->db->insert($this->table, $data);
	}

} // end class