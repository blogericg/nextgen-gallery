<?php

class M_GCS extends C_Base_Module
{
    function define($id = 'pope-module',
                    $name = 'Pope Module',
                    $description = '',
                    $version = '',
                    $uri = '',
                    $author = '',
                    $author_uri = '',
                    $context = FALSE) {
        parent::define(
            'imagely-gcs',
            'Google Cloud Storage CDN Provider',
            '0.0.1',
            'https://www.imagely.com/wordpress-gallery-plugin/nextgen-gallery/',
            'Imagely',
            'https://www.imagely.com',
            $context
        );
    }

    function initialize()
    {
        C_CDN_Providers::register(C_GCS_CDN_Provider::class);

        // TODO, once we have a UI to select the CDN, we won't hard-code this
        $settings = C_NextGen_Settings::get_instance();
        $settings->set('cdn', C_GCS_CDN_Provider::get_instance()->get_key());
        $settings->save();
    }
}

new M_GCS();    