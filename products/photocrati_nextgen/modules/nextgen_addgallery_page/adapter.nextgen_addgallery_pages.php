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
        // TODO: remove this if() when plupload is upgraded
        // Because iOS cannot work with our current version of plupload we hide this page from iOS users
        if (!preg_match('/crios|iP(hone|od|ad)/i', $_SERVER['HTTP_USER_AGENT']))
        {
            $this->object->add(NGG_ADD_GALLERY_SLUG,
                array(
                    'adapter'  => 'A_NextGen_AddGallery_Controller',
                    'parent'   => NGGFOLDER,
                    'add_menu' => TRUE,
                    'before'   => 'nggallery-manage-gallery'
                ));
        };

        return $this->call_parent('setup');
    }
}
