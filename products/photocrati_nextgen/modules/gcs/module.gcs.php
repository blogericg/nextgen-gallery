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

        \ReactrIO\Background\Job::register_type('cdn_gcs_delete_version', C_CDN_GCS_Delete_Image_Version_Job::class);
    }

    function get_type_list()
    {
        return [
            'C_GCS_CDN_Provider'                 => 'class.gcs_cdn_provider.php',
            'C_CDN_GCS_Delete_Image_Version_Job' => 'class.delete_image_version_job.php'
        ];
    }
}

new M_GCS();    