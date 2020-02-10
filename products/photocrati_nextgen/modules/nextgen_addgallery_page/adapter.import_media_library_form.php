<?php

/**
 * Class A_Import_Media_Library_Form
 * @mixin C_Form
 * @adapts I_Form for import_media_library context
 */
class A_Import_Media_Library_Form extends Mixin
{
    function get_title()
    {
        return __('Import from Media Library', 'nggallery');
    }

    function enqueue_static_resources()
    {
        wp_enqueue_media();
        wp_enqueue_script('nextgen_media_library_import-js');
        wp_enqueue_style('nextgen_media_library_import-css');

        $url = admin_url() . 'admin.php?page=nggallery-manage-gallery&mode=edit&gid={gid}';

        $i18n_array = array(
            'admin_url'         => admin_url(),
            'title'             => __('Import Images into NextGen Gallery',   'nggallery'),
            'import_multiple'   => __('Import %s images',                     'nggallery'),
            'import_singular'   => __('Import 1 image',                       'nggallery'),
            'imported_multiple' => sprintf(__('{count} images were uploaded successfully. <a href="%s" target="_blank">Manage gallery</a>', 'nggallery'), $url),
            'imported_singular' => sprintf(__('1 image was uploaded successfully. <a href="%s" target="_blank">Manage gallery</a>', 'nggallery'), $url),
            'imported_none'     => __('0 images were uploaded',               'nggallery'),
            'progress_title'    => __('Importing gallery',                    'nggallery'),
            'in_progress'       => __('In Progress...',                       'nggallery'),
            'gritter_title'     => __('Upload complete. Great job!',          'nggallery'),
            'gritter_error'     => __('Oops! Sorry, but an error occured. This may be due to a server misconfiguration. Check your PHP error log or ask your hosting provider for assistance.', 'nggallery'),
            'nonce'             => M_Security::create_nonce('nextgen_upload_image')
        );

        wp_localize_script('nextgen_media_library_import-js', 'ngg_importml_i18n',$i18n_array);
    }

    function render()
    {
        $i18n = array(
            'select-images-to-continue' => __('Please make a selection to continue', 'nggallery'),
            'select-opener'             => __('Select images',                       'nggallery'),
            'selected-image-import'     => __('Import %d image(s)',                  'nggallery')
        );

        return $this->object->render_partial('photocrati-nextgen_addgallery_page#import_media_library', array(
            'i18n'      => $i18n,
            'galleries' => $this->object->get_galleries()
        ), TRUE);
    }

    function get_galleries()
    {
        $galleries = array();

        if (M_Security::is_allowed('nextgen_edit_gallery'))
        {
            $galleries = C_Gallery_Mapper::get_instance()->find_all();
            if (!M_Security::is_allowed('nextgen_edit_gallery_unowned'))
            {
                $galleries_all = $galleries;
                $galleries = array();
                foreach ($galleries_all as $gallery) {
                    if (wp_get_current_user()->ID == (int) $gallery->author) $galleries[] = $gallery;
                }
            }
        }

        return $galleries;
    }
}