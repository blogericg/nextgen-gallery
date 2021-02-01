<?php

/*** { Module: photocrati-nextgen_rest } ***/

class M_NextGen_REST extends C_Base_Module
{
    function define($id = 'pope-module',
                    $name = 'Pope Module',
                    $description = '',
                    $version = '',
                    $uri = '',
                    $author = '',
                    $author_uri = '',
                    $context = FALSE)
    {
        parent::define(
            'photocrati-nextgen_rest',
            'NextGEN Gallery REST API methods',
            'Provides REST API methods for NextGEN Gallery',
            '0.1',
            'https://www.imagely.com/wordpress-gallery-plugin/nextgen-gallery/',
            'Imagely',
            'https://www.imagely.com'
        );
    }

    function _register_adapters()
    {
    }

    function _register_hooks()
    {
        add_action('rest_api_init', array($this, 'rest_api_init'));
    }

    function _register_utilities()
    {
    }

    public function rest_api_init()
    {
        new C_NextGen_Rest_V1();
    }

    function get_type_list()
    {
        return array(
            'C_NextGen_Rest_V1'               => 'class.nextgen_rest_v1.php',
            'C_NextGen_Rest_V1_Albums'        => 'class.nextgen_rest_v1_albums.php',
            'C_NextGen_Rest_V1_Galleries'     => 'class.nextgen_rest_v1_galleries.php',
            'C_NextGen_Rest_V1_Images'        => 'class.nextgen_rest_v1_images.php',
            'C_NextGen_Rest_V1_Settings'      => 'class.nextgen_rest_v1_settings.php',
            'C_NextGen_Rest_V1_Display_Types' => 'class.nextgen_rest_v1_display_types.php'
        );
    }
}

new M_NextGen_REST;