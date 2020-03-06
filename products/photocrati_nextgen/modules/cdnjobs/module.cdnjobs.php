<?php

class M_CDN_Jobs extends C_Base_Module
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
            'imagely-cdn-jobs',
            'Provides generic jobs for interaction with CDN',
            '0.0.1',
            'https://www.imagely.com/wordpress-gallery-plugin/nextgen-gallery/',
            'Imagely',
            'https://www.imagely.com',
            $context
        );
    }

    function initialize()
    {
        \ReactrIO\Background\Job::register_type('cdn_publish_image',   C_CDN_Publish_Image_Job::class);
        \ReactrIO\Background\Job::register_type('cdn_publish_gallery', C_CDN_Publish_Gallery_Job::class);
        \ReactrIO\Background\Job::register_type('cdn_resize_image',    C_CDN_Resize_Image_Job::class);
        \ReactrIO\Background\Job::register_type('cdn_resize_gallery',  C_CDN_Resize_Gallery_Job::class);
    }

    function _register_hooks()
    {
        if (C_CDN_Providers::is_cdn_configured())
        {
            add_action('ngg_added_new_image', function($image) {
                \ReactrIO\Background\Job::create(
                    sprintf(__("Upload image %d to CDN", 'nggallery'), $image->pid),
                    'cdn_publish_image', $image->pid
                )->save('cdn');
            });
        }
    }

    function get_type_list()
    {
        return [
            'C_CDN_Publish_Gallery_Job' => 'class.publish_gallery.php',
            'C_CDN_Publish_Image_Job'   => 'class.publish_image.php',
            'C_CDN_Resize_Gallery_Job'  => 'class.resize_gallery.php',
            'C_CDN_Resize_Image_Job'    => 'class.resize_image.php',
        ];
    }
}

new M_CDN_Jobs();