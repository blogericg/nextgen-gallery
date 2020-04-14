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

    // Used by tasks to disable the action listening in this module
    public static $_run_ngg_generated_image_action = TRUE;

    function initialize()
    {
        \ReactrIO\Background\Job::register_type('cdn_copy_image',                 C_CDN_Copy_Image_Job::class);
        \ReactrIO\Background\Job::register_type('cdn_copy_image_final',           C_CDN_Copy_Image_Final_Job::class);
        \ReactrIO\Background\Job::register_type('cdn_delete_gallery',             C_CDN_Delete_Gallery_Job::class);
        \ReactrIO\Background\Job::register_type('cdn_delete_gallery_final',       C_CDN_Delete_Gallery_Final_Job::class);
        \ReactrIO\Background\Job::register_type('cdn_delete_image',               C_CDN_Delete_Image_Job::class);
        \ReactrIO\Background\Job::register_type('cdn_delete_image_final',         C_CDN_Delete_Image_Final_Job::class);
        \ReactrIO\Background\Job::register_type('cdn_flush_cache_gallery',        C_CDN_Flush_Cache_Gallery_Job::class);
        \ReactrIO\Background\Job::register_type('cdn_flush_cache_image',          C_CDN_Flush_Cache_Image_Job::class);
        \ReactrIO\Background\Job::register_type('cdn_generate_image_size',        C_CDN_Generate_Image_Size_Job::class);
        \ReactrIO\Background\Job::register_type('cdn_generate_thumbnail_gallery', C_CDN_Generate_Thumbnail_Gallery_Job::class);
        \ReactrIO\Background\Job::register_type('cdn_generate_thumbnail_image',   C_CDN_Generate_Thumbnail_Image_Job::class);
        \ReactrIO\Background\Job::register_type('cdn_import_metadata_gallery',    C_CDN_Import_MetaData_Gallery_Job::class);
        \ReactrIO\Background\Job::register_type('cdn_import_metadata_image',      C_CDN_Import_MetaData_Image_Job::class);
        \ReactrIO\Background\Job::register_type('cdn_move_image',                 C_CDN_Move_Image_Job::class);
        \ReactrIO\Background\Job::register_type('cdn_move_image_final',           C_CDN_Move_Image_Final_Job::class);
        \ReactrIO\Background\Job::register_type('cdn_publish_gallery',            C_CDN_Publish_Gallery_Job::class);
        \ReactrIO\Background\Job::register_type('cdn_publish_image',              C_CDN_Publish_Image_Job::class);
        \ReactrIO\Background\Job::register_type('cdn_resize_gallery',             C_CDN_Resize_Gallery_Job::class);
        \ReactrIO\Background\Job::register_type('cdn_recover_image',              C_CDN_Recover_Image_Job::class);
        \ReactrIO\Background\Job::register_type('cdn_recover_gallery',            C_CDN_Recover_Gallery_Job::class);
        \ReactrIO\Background\Job::register_type('cdn_resize_image',               C_CDN_Resize_Image_Job::class);
        \ReactrIO\Background\Job::register_type('cdn_rotate_image',               C_CDN_Rotate_Image_Job::class);
        \ReactrIO\Background\Job::register_type('cdn_watermark_gallery',          C_CDN_Watermark_Gallery_Job::class);
        \ReactrIO\Background\Job::register_type('cdn_watermark_image',            C_CDN_Watermark_Image_Job::class);

        $notices_manager = C_Admin_Notification_Manager::get_instance();
        $notices_manager->add('cdnjobs_in_progress', 'C_CDN_Jobs_In_Progress_Notice');
    }

    function _register_adapters()
    {
        $this->get_registry()->add_adapter('I_Ajax_Controller', 'A_CDN_Jobs_In_Progress_Notice_Ajax');
    }

    function _register_hooks()
    {
        if (C_CDN_Providers::is_cdn_configured())
        {
            add_filter('ngg_displayed_gallery_rendering', function($html, $displayed_gallery) {

                // TODO: move this out of being an enclosure
                if (!C_CDN_Providers::get_current()->is_offload_enabled())
                    return $html;

                if (C_Displayed_Gallery_Renderer::get_instance()->rendering_has_dynimages($html))
                {
                    $settings = C_NextGen_Settings::get_instance();
                    $slug = $settings->get('dynamic_thumbnail_slug');

                    $pattern = "#/{$slug}/(.*)/(.*)/(.*)[,\"\'\\s]#Um";

                    $matches = NULL;

                    preg_match_all($pattern, $html, $matches);

                    foreach ($matches[0] as $ndx => $match) {

                        $dynthumbs = C_Dynamic_Thumbnails_Manager::get_instance();

                        $id     = $matches[1][$ndx];
                        $params = $dynthumbs->get_params_from_uri($match);
                        $size   = $dynthumbs->get_size_name($params);

                        \ReactrIO\Background\Job::create(
                            sprintf(__("Generating dynamic image size %s for image #%d", 'nextgen-gallery'), $size, $id),
                            'cdn_generate_image_size',
                            ['id' => $id, 'size' => $size, 'params' => $params]
                        )->save('cdn');
                    }

                    // TODO: make this into a template
                    return '<p>This gallery is still generating</p>';
                }

                return $html;
            }, 10, 2);

            add_action('ngg_admin_enqueue_scripts', function() {
                $manager = C_Admin_Notification_Manager::get_instance();
                $notice  = $manager->get_handler_instance('cdnjobs_in_progress');

                /** @var C_CDN_Jobs_In_Progress_Notice $notice */
                if ($notice->is_renderable())
                    $notice->enqueue_static_resources();
            });

            add_action('ngg_added_new_image', function($image) {
                // Invoked by import_image_file() and should be restricted to only running in response to user events
                if (defined('IN_REACTR_WORKER'))
                    return;

                foreach (['full', 'backup'] as $size) {
                    \ReactrIO\Background\Job::create(
                        sprintf(__("Publishing new image %d sized %s to CDN", 'nggallery'), $image->pid, $size),
                        'cdn_publish_image',
                        ['id' => $image->pid, 'size' => $size]
                    )->save('cdn');
                }
            });

            add_action(
                'ngg_recovered_image',
                function($image) {
                    \ReactrIO\Background\Job::create(
                        sprintf(__("Publishing recovered image #%d", 'nextgen-gallery'), $image->pid),
                        'cdn_publish_image',
                        ['id' => $image->pid, 'size' => 'all']
                    )->save('cdn');
                }
            );

            add_action(
                'ngg_generated_image',
                function($image, $size, $params) {
                    if (!self::$_run_ngg_generated_image_action)
                        return;
                    \ReactrIO\Background\Job::create(
                        sprintf(__("Publishing generated image size %s for image #%d", 'nextgen-gallery'), $size, $image->pid),
                        'cdn_publish_image',
                        ['id' => $image->pid, 'size' => $size]
                    )->save('cdn');
                },
                10,
                3
            );

            add_action('ngg_flush_galleries_cache', function($galleries) {
                foreach ($galleries as $gallery) {
                    \ReactrIO\Background\Job::create(
                        sprintf(__("Flushing dynamic images for gallery #%d", 'nextgen-gallery'), $gallery->gid),
                        'cdn_flush_cache_gallery',
                        $gallery->gid
                    )->save('cdn');
                }
            });
        }
    }

    function get_type_list()
    {
        return [
            'C_CDN_Jobs_In_Progress_Notice'        => 'class.cdn_jobs_in_progress_notice.php',
            'A_CDN_Jobs_In_Progress_Notice_Ajax'   => 'class.cdn_jobs_in_progress_notice_ajax.php',
            'C_CDN_Copy_Image_Job'                 => 'class.copy_image.php',
            'C_CDN_Copy_Image_Final_Job'           => 'class.copy_image_final.php',
            'C_CDN_Delete_Gallery_Job'             => 'class.delete_gallery.php',
            'C_CDN_Delete_Gallery_Final_Job'       => 'class.delete_gallery_final.php',
            'C_CDN_Delete_Image_Job'               => 'class.delete_image.php',
            'C_CDN_Delete_Image_Final_Job'         => 'class.delete_image_final.php',
            'C_CDN_Flush_Cache_Gallery_Job'        => 'class.flush_cache_gallery.php',
            'C_CDN_Flush_Cache_Image_Job'          => 'class.flush_cache_image.php',
            'C_CDN_Generate_Image_Size_Job'        => 'class.generate_image_size.php',
            'C_CDN_Generate_Thumbnail_Gallery_Job' => 'class.generate_thumbnail_gallery.php',
            'C_CDN_Generate_Thumbnail_Image_Job'   => 'class.generate_thumbnail_image.php',
            'C_CDN_Import_MetaData_Gallery_Job'    => 'class.import_metadata_gallery.php',
            'C_CDN_Import_MetaData_Image_Job'      => 'class.import_metadata_image.php',
            'C_CDN_Move_Image_Job'                 => 'class.move_image.php',
            'C_CDN_Move_Image_Final_Job'           => 'class.move_image_final.php',
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