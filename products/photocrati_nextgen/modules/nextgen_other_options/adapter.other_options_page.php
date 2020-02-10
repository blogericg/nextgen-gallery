<?php

/**
 * Class A_Other_Options_Page
 * @mixin C_NextGen_Admin_Page_Manager
 * @adapts I_Page_Manager
 */
class A_Other_Options_Page extends Mixin
{
	function setup()
	{
		$this->object->add(NGG_OTHER_OPTIONS_SLUG, array(
			'adapter'	=>	'A_Other_Options_Controller',
			'parent'	=>	NGGFOLDER,
            'before'    =>  'ngg_pro_upgrade'
		));

        return $this->call_parent('setup');
	}
}