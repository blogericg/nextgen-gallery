<?php

/**
 * Class A_WordPress_Base_Url
 * @mixin C_Router
 * @adapts I_Router
 */
class A_WordPress_Base_Url extends Mixin
{
	static $_lookups = array();

    function initialize()
    {
        register_shutdown_function(array(&$this, 'cache_lookups'));
        self::$_lookups = C_Photocrati_Transient_Manager::fetch($this->_get_cache_key(), array());
    }

	function _get_cache_key()
	{
		return C_Photocrati_Transient_Manager::create_key('WordPress-Router', 'get_base_url');
	}

    function cache_lookups()
    {
	    C_Photocrati_Transient_Manager::update($this->_get_cache_key(), self::$_lookups);
	}
	
	function has_cached_base_url($type=FALSE)
	{
		return isset(self::$_lookups[$type]);
	}

	function get_cached_base_url($type=FALSE)
	{
		return self::$_lookups[$type];
	}

	function get_computed_base_url($site_url=FALSE)
	{
		$retval            = NULL;
	    $add_index_dot_php = TRUE;

	    if (in_array($site_url, array(TRUE, 'site'), TRUE))
	    {
		    $retval = site_url();
	    }
	    elseif (in_array($site_url, array(FALSE, 'home'), TRUE)) {
		    $retval = home_url();
	    }
	    elseif (in_array($site_url, array('plugins', 'plugin'), TRUE)) {
		    $retval = plugins_url();
		    $add_index_dot_php = FALSE;
	    }
	    elseif (in_array($site_url, array('plugins_mu', 'plugin_mu'), TRUE)) {
		    $retval = WPMU_PLUGIN_URL;
		    $retval = set_url_scheme($retval);
		    $retval = apply_filters( 'plugins_url', $retval, '', '');
		    $add_index_dot_php = FALSE;
	    }
	    elseif (in_array($site_url, array('templates', 'template', 'themes', 'theme'), TRUE)) {
		    $retval = get_template_directory_uri();
		    $add_index_dot_php = FALSE;
	    }
	    elseif (in_array($site_url, array('styles', 'style', 'stylesheets', 'stylesheet'), TRUE)) {
		    $retval = get_stylesheet_directory_uri();
		    $add_index_dot_php = FALSE;
	    }
	    elseif (in_array($site_url, array('content'), TRUE)) {
		    $retval = content_url();
		    $add_index_dot_php = FALSE;
	    }
	    elseif (in_array($site_url, array('root'), TRUE)) {
		    $retval = get_option('home');
		    if (is_ssl())
			    $scheme = 'https';
		    else
			    $scheme = parse_url($retval, PHP_URL_SCHEME);
		    $retval = set_url_scheme($retval, $scheme);
	    }
	    elseif (in_array($site_url, array('gallery', 'galleries'), TRUE)) {
		    $root_type = NGG_GALLERY_ROOT_TYPE;
		    $add_index_dot_php = FALSE;
		    if ($root_type === 'content')
			    $retval = content_url();
		    else
			    $retval = site_url();
	    }
	    else {
		    $retval = site_url();
	    }

	    if ($add_index_dot_php)
		    $retval = $this->_add_index_dot_php_to_url($retval);

	    if ($this->object->is_https())
		    $retval = preg_replace('/^http:\\/\\//i', 'https://', $retval, 1);

        return $retval;
	}

    function _add_index_dot_php_to_url($url)
    {
        if (strpos($url, '/index.php') === FALSE)
        {
            $pattern = get_option('permalink_structure');
            if (!$pattern OR strpos($pattern, '/index.php') !== FALSE)
                $url = $this->object->join_paths($url, '/index.php');
        }

        return $url;
    }

    function get_base_url($type = FALSE)
    {
		if ($this->has_cached_base_url($type))
			return $this->get_cached_base_url($type);

		return $this->get_computed_base_url($type);
    }
}