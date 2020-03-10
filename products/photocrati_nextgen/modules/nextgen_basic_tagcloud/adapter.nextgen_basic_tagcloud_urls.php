<?php

/**
 * Class A_NextGen_Basic_TagCloud_Urls
 * @mixin C_Routing_App
 * @adapts I_Routing_App
 */
class A_NextGen_Basic_TagCloud_Urls extends Mixin
{
	function create_parameter_segment($key, $value, $id, $use_prefix)
	{
		if ($key == 'gallerytag') {
			return 'tags/'.$value;
		}
		else return $this->call_parent('create_parameter_segment', $key, $value, $id, $use_prefix);
	}


    function set_parameter_value($key, $value, $id=NULL, $use_prefix=FALSE, $url=FALSE)
	{
        $retval = $this->call_parent('set_parameter_value', $key, $value, $id, $use_prefix, $url);
		return $this->_set_tag_cloud_parameters($retval, $key, $id);
	}


    function remove_parameter($key, $id=NULL, $url=FALSE)
	{
        $retval = $this->call_parent('remove_parameter', $key, $id, $url);
		$retval = $this->_set_tag_cloud_parameters($retval, $key, $id);

        return $retval;
	}


	function _set_tag_cloud_parameters($retval, $key, $id=NULL)
	{
		// Get the settings manager
		$settings	= C_NextGen_Settings::get_instance();

		// Create the regex pattern
		$sep		= preg_quote($settings->router_param_separator, '#');
		if ($id)$id = preg_quote($id, '#').$sep;
		$prefix		= preg_quote($settings->router_param_prefix, '#');
		$regex		= implode('', array(
			'#//?',
			$id ? "({$id})?" : "(\w+{$sep})?",
			"($prefix)?gallerytag{$sep}([\w\-_]+)/?#"
		));

		// Replace any page parameters with the ngglegacy equivalent
		if (preg_match($regex, $retval, $matches)) {
			$retval = rtrim(str_replace($matches[0], "/tags/{$matches[3]}/", $retval), "/");
		}

		return $retval;
	}
}