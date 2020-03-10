<?php

/**
 * Class A_NextGen_Admin_Default_Pages
 * @mixin C_NextGen_Admin_Page_Manager
 * @adapts I_Page_Manager
 */
class A_NextGen_Admin_Default_Pages extends Mixin
{
	function setup()
	{
		$this->object->add(NGG_FS_ACCESS_SLUG, array(
			'adapter'	=>	'A_Fs_Access_Page',
			'parent'	=>	NGGFOLDER,
			'add_menu'	=>	FALSE
		));

        return $this->call_parent('setup');
	}
}