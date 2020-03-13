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
        \ReactrIO\Background\Job::register_type('cdn_copy_image',                 C_CDN_Copy_Image_Job::class);
        \ReactrIO\Background\Job::register_type('cdn_delete_gallery',             C_CDN_Delete_Gallery_Job::class);
        \ReactrIO\Background\Job::register_type('cdn_delete_image',               C_CDN_Delete_Image_Job::class);
        \ReactrIO\Background\Job::register_type('cdn_generate_thumbnail_gallery', C_CDN_Generate_Thumbnail_Gallery_Job::class);
        \ReactrIO\Background\Job::register_type('cdn_generate_thumbnail_image',   C_CDN_Generate_Thumbnail_Image_Job::class);
        \ReactrIO\Background\Job::register_type('cdn_import_metadata_gallery',    C_CDN_Import_MetaData_Gallery_Job::class);
        \ReactrIO\Background\Job::register_type('cdn_import_metadata_image',      C_CDN_Import_MetaData_Image_Job::class);
        \ReactrIO\Background\Job::register_type('cdn_move_image',                 C_CDN_Move_Image_Job::class);
        \ReactrIO\Background\Job::register_type('cdn_publish_gallery',            C_CDN_Publish_Gallery_Job::class);
        \ReactrIO\Background\Job::register_type('cdn_publish_image',              C_CDN_Publish_Image_Job::class);
        \ReactrIO\Background\Job::register_type('cdn_resize_gallery',             C_CDN_Resize_Gallery_Job::class);
        \ReactrIO\Background\Job::register_type('cdn_recover_image',              C_CDN_Recover_Image_Job::class);
        \ReactrIO\Background\Job::register_type('cdn_recover_gallery',            C_CDN_Recover_Gallery_Job::class);
        \ReactrIO\Background\Job::register_type('cdn_resize_image',               C_CDN_Resize_Image_Job::class);
        \ReactrIO\Background\Job::register_type('cdn_rotate_image',               C_CDN_Rotate_Image_Job::class);
        \ReactrIO\Background\Job::register_type('cdn_watermark_gallery',          C_CDN_Watermark_Gallery_Job::class);
        \ReactrIO\Background\Job::register_type('cdn_watermark_image',            C_CDN_Watermark_Image_Job::class);
    }

    function _register_hooks()
    {
        if (C_CDN_Providers::is_cdn_configured())
        {
            add_action('ngg_added_new_image', function($image) {
                \ReactrIO\Background\Job::create(
                    sprintf(__("Upload image %d to CDN", 'nggallery'), $image->pid),
                    'cdn_publish_image',
                    $image->pid
                )->save('cdn');
            });
        }
    }

    function get_type_list()
    {
        return [
            'C_CDN_Copy_Image_Job'                 => 'class.copy_image.php',
            'C_CDN_Delete_Gallery_Job'             => 'class.delete_gallery.php',
            'C_CDN_Delete_Image_Job'               => 'class.delete_image.php',
            'C_CDN_Generate_Thumbnail_Gallery_Job' => 'class.generate_thumbnail_gallery.php',
            'C_CDN_Generate_Thumbnail_Image_Job'   => 'class.generate_thumbnail_image.php',
            'C_CDN_Import_MetaData_Gallery_Job'    => 'class.import_metadata_gallery.php',
            'C_CDN_Import_MetaData_Image_Job'      => 'class.import_metadata_image.php',
            'C_CDN_Move_Image_Job'                 => 'class.move_image.php',
            'C_CDN_Publish_Gallery_Job'            => 'class.publish_gallery.php',
            'C_CDN_Publish_Image_Job'              => 'class.publish_image.php',
            'C_CDN_Recover_Gallery_Job'            => 'class.recover_image.php',
            'C_CDN_Recover_Image_Job'              => 'class.recover_image.php',
            'C_CDN_Resize_Gallery_Job'             => 'class.resize_gallery.php',
            'C_CDN_Resize_Image_Job'               => 'class.resize_image.php',
            'C_CDN_Rotate_Gallery_Job'             => 'class.rotate_gallery.php',
            'C_CDN_Rotate_Image_Job'               => 'class.rotate_image.php',
            'C_CDN_Watermark_Gallery_Job'          => 'class.watermark_gallery.php',
            'C_CDN_Watermark_Image_Job'            => 'class.watermark_image.php'
        ];
    }
}

new M_CDN_Jobs();