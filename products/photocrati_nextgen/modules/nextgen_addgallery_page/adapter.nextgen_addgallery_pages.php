<?php

/**
 * Class A_NextGen_AddGallery_Pages
 * @mixin C_NextGen_Admin_Page_Manager
 * @adapts I_Page_Manager
 */
class A_NextGen_AddGallery_Pages extends Mixin
{
    function setup()
    {
        $this->object->add(NGG_ADD_GALLERY_SLUG, [
            'adapter'  => 'A_NextGen_AddGallery_Controller',
            'parent'   => NGGFOLDER,
            'add_menu' => TRUE,
            'before'   => 'nggallery-manage-gallery'
        ]);

        return $this->call_parent('setup');
    }
}