<?php

/**
 * Class A_MVC_Fs
 * @mixin C_Fs
 * @adapts I_Fs
 */
class A_MVC_Fs extends Mixin
{
    static $_lookups = array();
    static $_non_minified_modules = array();

	function _get_cache_key()
	{
		return C_Photocrati_Transient_Manager::create_key('MVC', 'find_static_abspath');
	}

    function initialize()
    {
        register_shutdown_function(array(&$this, 'cache_lookups'));
	    //self::$_lookups = C_Photocrati_Transient_Manager::fetch($this->_get_cache_key(), array());
        self::$_non_minified_modules = apply_filters('ngg_non_minified_modules', array());
    }

    function cache_lookups()
    {
	    C_Photocrati_Transient_Manager::update($this->_get_cache_key(), self::$_lookups);
    }

    /**
     * Gets the absolute path to a static resource. If it doesn't exist, then NULL is returned
     *
     * @param string $path
     * @param string|false $module (optional)
     * @param bool $relative (optional)
     * @param bool $found_root (optional)
     * @return string|NULL
     * @deprecated Use M_Static_Assets instead
     */
    function find_static_abspath($path, $module = FALSE, $relative = FALSE, &$found_root=FALSE)
    {
        $retval = NULL;
        $key = $this->_get_static_abspath_key($path, $module, $relative);

        // Have we looked up this resource before?
        if (isset(self::$_lookups[$key])) {
            $retval = self::$_lookups[$key];
        }
        else 
        {
            // Get the module if we haven't got one yet
            if (!$module) list($path, $module) = $this->object->parse_formatted_path($path);

            // Lookup the module directory
            $mod_dir = $this->object->get_registry()->get_module_dir($module);

            $filter = has_filter('ngg_non_minified_files') ? apply_filters('ngg_non_minified_files', $path, $module) : FALSE;

	        if (!defined('SCRIPT_DEBUG')) define('SCRIPT_DEBUG', FALSE);

	        if (!SCRIPT_DEBUG
            &&  !in_array($module, self::$_non_minified_modules)
            &&  strpos($path, 'min.') === FALSE && strpos($path, 'pack.') === FALSE
            &&  strpos($path, 'packed.') === FALSE && preg_match('/\.(js|css)$/', $path)
            &&  !$filter)
            {
		        $path = preg_replace("#\\.[^\\.]+$#", ".min\\0", $path);
	        }

            // In case NextGen is in a symlink we make $mod_dir relative to the NGG root and then rebuild it
            // using WP_PLUGIN_DIR; without this NGG-in-symlink creates URL that reference the file abspath
            if (is_link($this->object->join_paths(WP_PLUGIN_DIR, basename(NGG_PLUGIN_DIR)))) {
                $mod_dir = str_replace(dirname(NGG_PLUGIN_DIR), '', $mod_dir);
                $mod_dir = $this->object->join_paths(WP_PLUGIN_DIR, $mod_dir);
            }

            // Create the absolute path to the file
            $path = $this->object->join_paths(
                $mod_dir,
                C_NextGen_Settings::get_instance()->get('mvc_static_dirname'),
                $path
            );

            $path = wp_normalize_path($path);

            if ($relative) {
                $original_length = strlen($path);
                $roots = array('plugins', 'plugins_mu', 'templates', 'stylesheets');
                $found_root = FALSE;
                foreach ($roots as $root) {
                    $path = str_replace($this->object->get_document_root($root), '', $path);
                    if (strlen($path) != $original_length) {
                        $found_root = $root;
                        break;
                    }
                }
            }

            // Cache result
            $retval = self::$_lookups[$key] = $path;
        }

        return $retval;
    }

    function _get_static_abspath_key($path, $module=FALSE, $relative=FALSE)
    {
        $key = $path;
        if ($module)    $key .= '|'.$module;
        if ($relative)  $key .= 'r';
        global $wpdb;
        if ($wpdb) $key .= '|' . $wpdb->blogid;

        return $key;
    }
}
