<?php

/**
 * Class A_NextGen_Basic_Album_Urls
 * @mixin C_Routing_App
 * @adapts I_Routing_App
 */
class A_NextGen_Basic_Album_Urls extends Mixin
{
    function create_parameter_segment($key, $value, $id=NULL, $use_prefix=FALSE)
    {
        if ($key == 'nggpage') {
            return 'page/'.$value;
        }
        elseif ($key == 'album') {
            return $value;
        }
        elseif ($key == 'gallery') {
            return $value;
        }
        else
            return $this->call_parent('create_parameter_segment', $key, $value, $id, $use_prefix);
    }

    function remove_parameter($key, $id=NULL, $url=FALSE)
    {
        $retval        = $this->call_parent('remove_parameter', $key, $id, $url);
        $settings	= C_NextGen_Settings::get_instance();
        $param_slug = preg_quote($settings->router_param_slug, '#');

        if (preg_match("#(/{$param_slug}/.*)album--#", $retval, $matches)) {
            $retval = str_replace($matches[0], $matches[1], $retval);
        }

        if (preg_match("#(/{$param_slug}/.*)gallery--#", $retval, $matches)) {
            $retval = str_replace($matches[0], $matches[1], $retval);
        }

        return $retval;
    }
}
