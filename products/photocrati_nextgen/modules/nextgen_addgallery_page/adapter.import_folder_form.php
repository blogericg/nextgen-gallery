<?php

/**
 * Class A_Import_Folder_Form
 * @mixin C_Form
 * @adapts I_Form for import_folder context
 */
class A_Import_Folder_Form extends Mixin
{
    function get_title()
    {
        return __("Import Folder", 'nggallery');
    }

    function enqueue_static_resources()
    {
        wp_enqueue_style('jquery.filetree');
        wp_enqueue_style('ngg_progressbar');
        wp_enqueue_script('jquery.filetree');
        wp_enqueue_script('ngg_progressbar');
    }

    function render()
    {
        return $this->object->render_partial('photocrati-nextgen_addgallery_page#import_folder', array(
	        'browse_nonce' =>  M_Security::create_nonce('nextgen_upload_image'),
	        'import_nonce' =>  M_Security::create_nonce('nextgen_upload_image')
        ), TRUE);
    }
}