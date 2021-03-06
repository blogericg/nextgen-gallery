<?php

/**
 * Class A_NextGen_AddGallery_Controller
 * @mixin C_NextGen_Admin_Page_Controller
 * @adapts I_NextGen_Admin_Page
 */
class A_NextGen_AddGallery_Controller extends Mixin
{
    function get_page_title()
    {
        return __('Add Gallery / Images', 'nggallery');
    }

    function get_required_permission()
    {
        return 'NextGEN Upload images';
    }

    function enqueue_backend_resources()
    {
        $this->call_parent('enqueue_backend_resources');
        wp_enqueue_style('nextgen_addgallery_page');
        wp_enqueue_script('nextgen_addgallery_page');
        wp_enqueue_script('frame_event_publisher');
    }

    function show_save_button()
    {
        return FALSE;
    }
}