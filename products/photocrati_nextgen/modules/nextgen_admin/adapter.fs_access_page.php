<?php

/**
 * Class A_Fs_Access_Page
 * @todo Finish the implementation
 * @mixin C_NextGen_Admin_Page_Controller
 * @adapts I_NextGen_Admin_Page for the "ngg_fs_access" context
 */
class A_Fs_Access_Page extends Mixin
{
	function index_action()
	{
		$router			= C_Router::get_instance();
		$url			= $this->param('uri') ? $router->get_url($this->param('uri')) :
							admin_url('/admin.php?'.$router->get_querystring());

		// Request filesystem credentials from user
		$creds = request_filesystem_credentials(
			$url,
			'',
			FALSE,
			ABSPATH,
			array()
		);

		if (WP_Filesystem($creds)) {
			global $wp_filesystem;
		}
	}

	/**
	 * Determines whether the given paths are writable
	 * @return boolean
	 */
	function are_paths_writable()
	{
		$retval = TRUE;
		$path = $this->object->param('path');
		if (!is_array($path)) $path = array($path);
		foreach ($path as $p) {
			if (!is_writable($p)) {
				$retval = FALSE;
				break;
			}
		}
		return $retval;
	}
}