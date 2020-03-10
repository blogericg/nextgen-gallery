<?php

class Mixin_Router extends Mixin
{
    function set_routed_app($app)
    {
        $this->object->_routed_app = $app;
    }

    function &get_routed_app()
    {
        $retval = $this->object->_routed_app ? $this->object->_routed_app : $this->object->get_default_app();
        return $retval;
    }

    function &get_default_app()
    {
        if (is_null($this->object->_default_app))
            $this->object->_default_app = $this->object->create_app();
        $retval = $this->object->_default_app;

        return $retval;
    }

    function route($patterns, $handler=FALSE)
    {
        $this->object->get_default_app()->route($patterns, $handler);
    }

    function rewrite($src, $dst, $redirect=FALSE)
    {
        $this->object->get_default_app()->rewrite($src, $dst, $redirect);
    }

    function get_parameter($key, $prefix=NULL, $default=NULL)
    {
        return $this->object->get_routed_app()->get_parameter($key, $prefix, $default);
    }

    function param($key, $prefix=NULL, $default=NULL)
    {
        return $this->object->get_parameter($key, $prefix, $default);
    }

    function has_parameter_segments()
    {
        return $this->object->get_routed_app()->has_parameter_segments();
    }

    function passthru()
    {
        return $this->object->get_default_app()->passthru();
    }

    /**
     * Gets url for the router
     * @param string $uri (optional) Default = /
     * @param bool $with_qs (optional) Default = true
     * @param bool $site_url
     * @return string
     */
    function get_url($uri='/', $with_qs=TRUE, $site_url = FALSE)
    {
        $retval = $this->object->join_paths(
            $this->object->get_base_url($site_url),
            $uri
        );
        if ($with_qs) {
            $parts = parse_url($retval);
            if (!isset($parts['query']))
                $parts['query'] = $this->object->get_querystring();
            else
                $parts['query'] = $this->object->join_querystrings($parts['query'], $this->object->get_querystring());

            $retval = $this->object->construct_url_from_parts($parts);

        }
        return str_replace("\\", "/", $retval);
    }

    /**
     * Currents the relative url
     * @param string $uri
     * @param boolean $with_qs
     * @return string
     */
    function get_relative_url($uri='/', $with_qs=TRUE)
    {
        $url = $this->object->get_url($uri, $with_qs=TRUE);
        if ($url !== '/')
            $retval = str_replace($this->object->get_base_url(), '', $url);
        return '/'.lrtim($retval, '/');
    }


    /**
     * Returns a static url
     * @param string $path
     * @param string|false $module (optional)
     * @return string
     */
    function get_static_url($path, $module=FALSE)
    {
        return M_Static_Assets::get_static_url($path, $module);
    }


    /**
     * Gets the routed url
     * @return string
     */
    function get_routed_url()
    {
        $retval = $this->object->get_url($this->object->get_request_uri());

        if (($app = $this->object->get_routed_app())) {
            $retval = $this->object->get_url($app->get_app_uri());
        }

        return $retval;
    }

    /**
     * Gets the base url for the router
     *
     * @param bool $site_url Unused
     * @return string
     */
    function get_base_url($site_url = FALSE)
    {
        $protocol = $this->object->is_https()? 'https://' : 'http://';
        $retval = "{$protocol}{$_SERVER['SERVER_NAME']}{$this->object->context}";
        return str_replace("\\", "/", rtrim($retval, "/\\"));
    }

    /**
     * Determines if the current request is over HTTPs or not
     */
    function is_https()
    {
        return (
            (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') ||
            (!empty($_SERVER['HTTP_USESSL']) && strtolower($_SERVER['HTTP_USESSL']) !== 'off') ||
            (!empty($_SERVER['REDIRECT_HTTPS']) && strtolower($_SERVER['REDIRECT_HTTPS']) !== 'off') ||
            (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443));
    }

    /**
     * Serve request using defined Routing Apps
     */
    function serve_request()
    {
        $served = FALSE;

        // iterate over all apps, and serve the route
        foreach ($this->object->get_apps() as $app) {
            if (($served = $app->serve_request($this->object->context)))
                break;
        }

        return $served;
    }

    /**
     * Gets the querystring of the current request
     * @return null|bool
     */
    function get_querystring()
    {
        return isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : null ;
    }


    function set_querystring($value)
    {
        $_SERVER['QUERY_STRING'] = $value;
    }

    /**
     * Gets the request for the router
     * @param bool $with_params (optional) Default = true
     * @return string
     */
    function get_request_uri($with_params=TRUE)
    {
        if (!empty($_SERVER['NGG_ORIG_REQUEST_URI']))
            $retval = $_SERVER['NGG_ORIG_REQUEST_URI'];
        elseif (!empty($_SERVER['PATH_INFO']))
            $retval = $_SERVER['PATH_INFO'];
        else
            $retval = $_SERVER['REQUEST_URI'];

        // Remove the querystring
        if (($index = strpos($retval, '?')) !== FALSE) {
            $retval = substr($retval, 0, $index);
        }

        // Remove the router's context
        $retval = preg_replace('#^'.preg_quote($this->object->context, '#').'#', '', $retval);

        // Remove the params
        if (!$with_params)
            $retval = $this->object->strip_param_segments($retval);

        // Ensure that request uri starts with a slash
        if (strpos($retval, '/') !== 0) $retval = "/{$retval}";

        return $retval;
    }

    /**
     * Gets the method of the HTTP request
     * @return string
     */
    function get_request_method()
    {
        return $this->object->_request_method;
    }


    function &create_app($name = '/')
    {
        $factory = C_Component_Factory::get_instance();
        $app = $factory->create('routing_app', $name);
        $this->object->_apps[] = $app;
        return $app;
    }

    /**
     * Gets a list of apps registered for the router
     *
     * @return array
     */
    function get_apps()
    {
        usort($this->object->_apps, array(&$this, '_sort_apps'));
        return array_reverse($this->object->_apps);
    }

    /**
     * Sorts apps.This is needed because we want the most specific app to be
     * executed first
     * @param C_Routing_App $a
     * @param C_Routing_App $b
     * @return int
     */
    function _sort_apps($a, $b)
    {
        return strnatcmp($a->context, $b->context);
    }
}

/**
 * A router is configured to match patterns against a url and route the request to a particular controller and action
 * @mixin Mixin_Url_Manipulation
 * @mixin Mixin_Router
 * @implements I_Router
 */
class C_Router extends C_Component
{
    static $_instances	= array();
    var $_apps			= array();
    var $_default_app	= NULL;

    function define($context = FALSE)
    {
        if (!$context OR $context == 'all') $context = '/';
        parent::define($context);
        $this->add_mixin('Mixin_Url_Manipulation');
        $this->add_mixin('Mixin_Router');
        $this->implement('I_Router');
    }

    function initialize()
    {
        parent::initialize();
        $this->_request_method = !empty($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : null;
    }

    /**
     * @param string|false $context (optional)
     * @return self
     */
    static function &get_instance($context = False)
    {
        if (!isset(self::$_instances[$context])) {
            $klass = get_class();
            self::$_instances[$context] = new $klass($context);
        }
        return self::$_instances[$context];
    }
}
