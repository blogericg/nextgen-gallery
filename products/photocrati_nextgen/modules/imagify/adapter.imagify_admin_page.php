<?php

/**
 * Class A_Imagify_Admin_Page
 * @mixin C_NextGen_Admin_Page_Manager
 * @adapts I_Page_Manager
 */

class A_Imagify_Admin_Page extends Mixin
{
    function setup()
    {
        // This hides the imagify page from the menu while still allowing it to display
        if (defined('IMAGIFY_VERSION'))
            $parent = NULL;
        elseif (is_multisite()) 
            $parent = NULL;
        else
            $parent = NGGFOLDER;

        $this->object->add('ngg_imagify', array(
            'adapter' => 'A_Imagify_Admin_Page_Controller',
            'parent'  => $parent
        ));

        return $this->call_parent('setup');
    }
}


