<?php

/**
 * Class A_Other_Options_Controller
 * @mixin C_NextGen_Admin_Page_Controller
 * @adapts I_NextGen_Admin_Page using "ngg_other_options" context
 */
class A_Other_Options_Controller extends Mixin
{
	function enqueue_backend_resources()
	{
		$this->call_parent('enqueue_backend_resources');
		wp_enqueue_script(
			'nextgen_settings_page',
			$this->get_static_url('photocrati-nextgen_other_options#nextgen_settings_page.js'),
			array('jquery-ui-accordion', 'jquery-ui-tooltip', 'wp-color-picker', 'jquery.nextgen_radio_toggle'),
			NGG_SCRIPT_VERSION
		);

		wp_enqueue_style(
			'nextgen_settings_page',
			$this->get_static_url('photocrati-nextgen_other_options#nextgen_settings_page.css'),
			array(),
			NGG_SCRIPT_VERSION
		);
	}

	function get_page_title()
	{
		return __('Other Options', 'nggallery');
	}
	
	function get_required_permission()
	{
		return 'NextGEN Change options';
	}
}
