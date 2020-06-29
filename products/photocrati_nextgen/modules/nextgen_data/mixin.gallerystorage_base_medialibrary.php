<?php

/**
 * This class contains methods C_Gallery_Storage needs to interact with (like say, importing from) the WP Media Library
 *
 * * @property C_Gallery_Storage $object
 */
class Mixin_GalleryStorage_Base_MediaLibrary extends Mixin
{
    /**
     * Copies a NGG image to the media library and returns the attachment_id
     *
     * @param C_Image|int|stdClass $image
     * @return FALSE|int attachment_id
     */
    function copy_to_media_library($image)
    {
        $retval = FALSE;

        // Get the image
        if (is_int($image))
        {
            $imageId = $image;
            $mapper  = C_Image_Mapper::get_instance();
            $image   = $mapper->find($imageId);
        }

        if ($image)
        {
            $subdir = apply_filters('ngg_import_to_media_library_subdir', 'nggallery_import');

            $wordpress_upload_dir = wp_upload_dir();
            $path = $wordpress_upload_dir['path'] . DIRECTORY_SEPARATOR . $subdir;

            if (!file_exists($path))
                wp_mkdir_p($path);

            $image_abspath = C_Gallery_Storage::get_instance()->get_image_abspath($image, "full");
            $new_file_path = $path . DIRECTORY_SEPARATOR . $image->filename;

            $image_data    = getimagesize($image_abspath);
            $new_file_mime = $image_data['mime'];

            $i = 1;
            while(file_exists($new_file_path)) {
                $i++;
                $new_file_path = $path . DIRECTORY_SEPARATOR . $i . '_' . $image->filename;
            }

            if (@copy($image_abspath, $new_file_path))
            {
                $upload_id = wp_insert_attachment([
                    'guid'           => $new_file_path,
                    'post_mime_type' => $new_file_mime,
                    'post_title'     => preg_replace('/\.[^.]+$/', '', $image->alttext),
                    'post_content'   => '',
                    'post_status'    => 'inherit'
                ], $new_file_path);

                update_post_meta($upload_id, '_ngg_image_id', intval($image->pid));

                // wp_generate_attachment_metadata() comes from this file
                require_once(ABSPATH . 'wp-admin/includes/image.php');

                $image_meta = wp_generate_attachment_metadata($upload_id, $new_file_path);

                // Generate and save the attachment metas into the database
                wp_update_attachment_metadata($upload_id,  $image_meta);

                $retval = $upload_id;
            }
        }

        return $retval;
    }

    /**
     * Delete the given NGG image from the media library
     *
     * @var int|stdClass $imageId
     */
    function delete_from_media_library($imageId)
    {
        // Get the image
        if (!is_int($imageId)) {
            $image = $imageId;
            $imageId = $image->pid;
        }

        if (($postId = $this->object->is_in_media_library($imageId))) {
            wp_delete_post($postId);
        }
    }

    /**
     * Determines if the given NGG image id has been uploaded to the media library
     *
     * @param integer $imageId
     * @return FALSE|int attachment_id
     */
    function is_in_media_library($imageId)
    {
        $retval = FALSE;

        // Get the image
        if (is_object($imageId)) {
            $image = $imageId;
            $imageId = $image->pid;
        }

        // Try to find an attachment for the given image_id
        if ($imageId) {
            $query = new WP_Query(array(
                'post_type' 	 => 'attachment',
                'meta_key'		 => '_ngg_image_id',
                'meta_value_num' => $imageId
            ));

            foreach ($query->get_posts() as $post) {
                $retval = $post->ID;
            }
        }

        return $retval;
    }
}