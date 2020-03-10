<?php

/**
 * Class A_WordPress_Router
 * @mixin C_Router
 * @adapts I_Router
 */
class A_WordPress_Router extends Mixin
{
    function get_url($uri='/', $with_qs=TRUE, $site_url = FALSE)
    {
	    static $cache = array();

	    $key = implode('|', array($uri, $with_qs, $site_url));

	    if (isset($cache[$key])) return $cache[$key];
	    else {

		    $retval = $this->call_parent('get_url', $uri, $with_qs, $site_url);

		    // Determine whether the url is a directory or file on the filesystem
		    // If so, then we do NOT need /index.php as part of the url
		    $base_url = $this->object->get_base_url();
		    $filename = str_replace(
			    $base_url,
			    C_Fs::get_instance()->get_document_root(),
			    $retval
		    );

		    if ($retval && $retval != $base_url && @file_exists($filename))
		    {
			    // Remove index.php from the url
			    $retval = $this->object->remove_url_segment('/index.php', $retval);

			    // Static urls don't end with a slash
			    $retval = untrailingslashit($retval);
		    }

		    $cache[$key] = $retval;
		    return $retval;
	    }
    }
}
