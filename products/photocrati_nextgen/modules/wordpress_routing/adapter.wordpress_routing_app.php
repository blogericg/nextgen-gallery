<?php

/**
 * Class A_WordPress_Routing_App
 * @mixin C_Routing_App
 * @adapts I_Routing_App
 */
class A_WordPress_Routing_App extends Mixin
{
    function remove_parameter($key, $id=NULL, $url=FALSE)
    {
        $generated_url = $this->call_parent('remove_parameter', $key, $id, $url);
        $generated_url = $this->object->add_post_permalink_to_url($generated_url);

        return $generated_url;
    }

    function parse_url($url)
    {
        $parts = parse_url($url);
        if (!isset($parts['path']))  $parts['path']  = '/';
        if (!isset($parts['query'])) $parts['query'] = '';

        return $parts;
    }

    /**
     * Adds the post permalink to the url, if it isn't already present.
     *
     * The generated_url could look like:
     * http://localhost/dir/nggallery/show/slideshow
     * @param $generated_url
     * @return mixed
     */
    function add_post_permalink_to_url($generated_url)
    {
        if (!apply_filters('ngg_wprouting_add_post_permalink', TRUE))
            return $generated_url;

        $base_url = $this->object->get_router()->get_base_url('home');
        $settings = C_NextGen_Settings::get_instance();
        if (strlen($generated_url) < 2)
            $generated_url = $base_url;

        $original_url    = $generated_url;
        $generated_parts = explode($settings->router_param_slug, $generated_url);
        $generated_url   = $generated_parts[0];
        $ngg_parameters  = '/';
        if (isset($generated_parts[1])) {
            $parts = explode('?', $generated_parts[1]);
            $ngg_parameters = array_shift($parts);
        }
        $post_permalink  = get_permalink(isset($_REQUEST['p']) ? $_REQUEST['p'] : 0);
        if ($post_permalink == '/')
            $post_permalink = $base_url;

        // Trailing slash all of the urls
        $original_url   = trailingslashit($original_url);
        $post_permalink = trailingslashit($post_permalink);
        $generated_url  = trailingslashit($generated_url);

        // We need to determine if the generated url and the post permalink TRULY differ. If they
        // differ, then we'll return post_permalink + nggallery parameters appended. Otherwise, we'll
        // just return the generated url
        $generated_url   = str_replace($base_url, home_url(), $generated_url);
        $generated_parts = $this->parse_url($generated_url);
        $post_parts      = $this->parse_url($post_permalink);
        $generated_parts['path'] = trailingslashit($generated_parts['path']);
        if (isset($generated_parts['query']))
            $generated_parts['query'] = untrailingslashit($generated_parts['query']);
        $post_parts['path'] = trailingslashit($post_parts['path']);
        if (isset($post_parts['query']))
            $post_parts['query'] = untrailingslashit($post_parts['query']);

        $generated_url  = $this->object->construct_url_from_parts($generated_parts);
        $post_permalink = $this->object->construct_url_from_parts($post_parts);

        // No change required...
        if ($generated_url == $post_permalink)
        {
            $generated_url = $original_url;

            // Ensure that the generated url has the real base url for default permalinks
            if (strpos($generated_url, home_url()) !== FALSE && strpos($generated_url, $base_url) === FALSE)
                $generated_url = str_replace(home_url(), $base_url, $generated_url);
        }
        else {
            // The post permalink differs from the generated url
            $post_permalink = str_replace(home_url(), $base_url, $post_permalink);
            $post_parts = $this->parse_url($post_permalink);
            $post_parts['path'] = $this->object->join_paths($post_parts['path'], $settings->router_param_slug, $ngg_parameters);
	        $post_parts['path'] = str_replace('index.php/index.php', 'index.php', $post_parts['path']); // incase permalink_structure contains index.php
            if (!empty($generated_parts['query']) && empty($post_parts['query']))
                $post_parts['query'] = $generated_parts['query'];
            $generated_url = $this->object->construct_url_from_parts($post_parts);
        }

        return $generated_url;
    }

    function join_paths()
    {
        $args = func_get_args();
        return $this->get_router()->join_paths($args);
    }

    function passthru()
    {
        $router = C_Router::get_instance();

        $_SERVER['NGG_ORIG_REQUEST_URI'] = $_SERVER['REQUEST_URI'];
        $base_parts = parse_url($router->get_base_url('root'));
	    $new_request_uri = $router->join_paths(
		    (!empty($base_parts['path']) ? $base_parts['path'] : ''),
		    $this->object->strip_param_segments($router->get_request_uri())
	    );

	    $new_request_uri = str_replace('index.php/index.php', 'index.php', $new_request_uri);

        // Handle possible incompatibility with 3rd party plugins manipulating the query as well: WPML in particular
        // can lead to our $new_request_uri here becoming index.php/en/index.php: remove this double index.php
        $uri_array = explode('/', $new_request_uri);
	    if (!empty($uri_array) && count($uri_array) >= 2 && reset($uri_array) == 'index.php' && end($uri_array) == 'index.php') {
	        array_shift($uri_array);
	        $new_request_uri = implode('/', $uri_array);
        }

        $_SERVER['UNENCODED_URL'] = $_SERVER['HTTP_X_ORIGINAL_URL'] = $_SERVER['REQUEST_URI'] = '/' . trailingslashit($new_request_uri);
        if (isset($_SERVER['PATH_INFO'])) {
            $_SERVER['ORIG_PATH_INFO'] = $_SERVER['PATH_INFO'];
            unset($_SERVER['PATH_INFO']);
        }
    }

}