<?php

/**
 * Class A_Display_Settings_Page
 * @mixin C_NextGen_Admin_Page_Manager
 * @adapts I_Page_Manager
 */
class A_Display_Settings_Page extends Mixin
{
	function setup()
	{
		$this->object->add(NGG_DISPLAY_SETTINGS_SLUG, array(
			'adapter'	=>	'A_Display_Settings_Controller',
			'parent'	=>	NGGFOLDER,
			'before'    =>  'ngg_other_options'
		));

        return $this->call_parent('setup');
	}
}