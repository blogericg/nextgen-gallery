<?php

/**
 * Class A_NextGen_Basic_Gallery_Urls
 * @mixin C_Routing_App
 * @adapts I_Routing_App
 */
class A_NextGen_Basic_Gallery_Urls extends Mixin
{
    function create_parameter_segment($key, $value, $id=NULL, $use_prefix=FALSE)
	{
		if ($key == 'show') {
            if ($value == NGG_BASIC_SLIDESHOW) $value = 'slideshow';
            elseif ($value == NGG_BASIC_THUMBNAILS) $value = 'thumbnails';
            elseif ($value == NGG_BASIC_IMAGEBROWSER) $value = 'imagebrowser';
            return $value;
        }
        elseif ($key == 'nggpage') {
			return 'page/'.$value;
		}
		else
			return $this->call_parent('create_parameter_segment', $key, $value, $id, $use_prefix);

	}


    function set_parameter_value($key, $value, $id=NULL, $use_prefix=FALSE, $url=FALSE)
	{
		$retval = $this->call_parent('set_parameter_value', $key, $value, $id, $use_prefix, $url);
        return $this->_set_ngglegacy_page_parameter($retval, $key, $value, $id, $use_prefix);
	}


	function remove_parameter($key, $id=NULL, $url=FALSE)
	{
        $retval = $this->call_parent('remove_parameter', $key, $id, $url);
		$retval = $this->_set_ngglegacy_page_parameter($retval, $key);

        // For some reason, we're not removing our parameters the way we should. Our routing system seems to be
        // a bit broken and so I'm adding an exception here.
        // TODO: Our parameter manipulations need to be flawless. Look into route cause
        if ($key == 'show') {
            $uri = explode('?', $retval);
            $uri = $uri[0];
            $settings = C_NextGen_Settings::get_instance();
            $regex = '#/'.$settings->router_param_slug.'.*(/?(slideshow|thumbnails|imagebrowser)/?)#';
            if (preg_match($regex, $retval, $matches)) {
                $retval = str_replace($matches[1], '', $retval);
            }
        }

        return $retval;
	}


	function _set_ngglegacy_page_parameter($retval, $key, $value=NULL, $id=NULL, $use_prefix=NULL)
	{
        // Get the settings manager
        $settings	= C_NextGen_Settings::get_instance();

        // Create regex pattern
        $param_slug = preg_quote($settings->router_param_slug, '#');

        if ($key == 'nggpage') {
            $regex = "#(/{$param_slug}/.*)(/?page/\\d+/?)(.*)#";
            if (preg_match($regex, $retval, $matches)) {
                $new_segment = $value ? "/page/{$value}" : "";
                $retval = rtrim(str_replace(
                    $matches[0],
                    rtrim($matches[1], "/").$new_segment.ltrim($matches[3], "/"),
                    $retval
                ), "/");
            }
        }

        # Convert the nggpage parameter to a slug
        if (preg_match("#(/{$param_slug}/.*)nggpage--(.*)#", $retval, $matches)) {
            $retval = rtrim(str_replace($matches[0], rtrim($matches[1],"/") ."/page/".ltrim($matches[2], "/"), $retval), "/");
        }

        # Convert the show parameter to a slug
        if (preg_match("#(/{$param_slug}/.*)show--(.*)#", $retval, $matches)) {
            $retval = rtrim(str_replace($matches[0], rtrim($matches[1], "/").'/'.$matches[2], $retval), "/");
            $retval = str_replace(NGG_BASIC_SLIDESHOW, 'slideshow', $retval);
            $retval = str_replace(NGG_BASIC_THUMBNAILS, 'thumbnails', $retval);
            $retval = str_replace(NGG_BASIC_IMAGEBROWSER, 'imagebrowser', $retval);
        }

		return $retval;
	}
}