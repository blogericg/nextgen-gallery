<?php

/**
 * Basic gallery storage methods; please consult the other available mixin.gallerystorage_base_(.*).php files before
 * adding new methods to this class: new methods may be more appropriately defined in other mixins.
 * @property C_Gallery_Storage $object
 */
class Mixin_GalleryStorage_Base extends Mixin
{
    /**
     * Gets the id of a gallery, regardless of whether an integer
     * or object was passed as an argument
     * @param mixed $gallery_obj_or_id
     * @return null|int
     */
    function _get_gallery_id($gallery_obj_or_id)
    {
        $retval = NULL;
        $gallery_key = $this->object->_gallery_mapper->get_primary_key_column();
        if (is_object($gallery_obj_or_id)) {
            if (isset($gallery_obj_or_id->$gallery_key)) {
                $retval = $gallery_obj_or_id->$gallery_key;
            }
        }
        elseif(is_numeric($gallery_obj_or_id)) {
            $retval = $gallery_obj_or_id;
        }

        return $retval;
    }

    /**
     * Outputs/renders an image
     * @param int|stdClass|C_Image $image
     * @return bool
     */
    function render_image($image, $size=FALSE)
    {
        $format_list = $this->object->get_image_format_list();
        $abspath = $this->object->get_image_abspath($image, $size, true);

        if ($abspath == null)
        {
            $thumbnail = $this->object->generate_image_size($image, $size);

            if ($thumbnail != null)
            {
                $abspath = $thumbnail->fileName;

                $thumbnail->destruct();
            }
        }

        if ($abspath != null)
        {
            $data = @getimagesize($abspath);
            $format = 'jpg';

            if ($data != null && is_array($data) && isset($format_list[$data[2]]))
            {
                $format = $format_list[$data[2]];
            }

            // Clear output
            while (ob_get_level() > 0)
            {
                ob_end_clean();
            }

            $format = strtolower($format);

            // output image and headers
            header('Content-type: image/' . $format);
            readfile($abspath);

            return true;
        }

        return false;
    }

    /**
     * Sets a NGG image as a post thumbnail for the given post
     */
    function set_post_thumbnail($postId, $image, $only_create_attachment=FALSE)
    {
        $retval = FALSE; // attachment_id or FALSE

        // Get the post ID
        if (is_object($postId)) {
            $post = $postId;
            $postId = isset($post->ID) ? $post->ID : $post->post_id;
        }

        // Get the image
        if (is_int($image)) {
            $imageId = $image;
            $mapper = C_Image_Mapper::get_instance();
            $image = $mapper->find($imageId);
        }

        if ($image && $postId) {
            $attachment_id = $this->object->is_in_media_library($image->pid);
            if ($attachment_id === FALSE) $attachment_id = $this->object->copy_to_media_library($image);
            if ($attachment_id) {
                if (!$only_create_attachment) set_post_thumbnail($postId, $attachment_id);
                $retval = $attachment_id;
            }
        }

        return $retval;
    }

    function convert_slashes($path)
    {
        $search = array('/', "\\");
        $replace = array(DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR);

        return str_replace($search, $replace, $path);
    }

    /**
     * Empties the gallery cache directory of content
     * @param object $gallery
     */
    function flush_cache($gallery)
    {
        $cache = C_Cache::get_instance();
        $cache->flush_directory($this->object->get_cache_abspath($gallery));

        if (C_CDN_Providers::is_cdn_configured())
        {
            \ReactrIO\Background\Job::create(
                sprintf(__("Flushing dynamic images for gallery #%d", 'nextgen-gallery'), $gallery->gid),
                'cdn_flush_cache_gallery',
                $gallery->gid
            )->save('cdn');
        }
    }

    /**
     * Sanitizes a directory path, replacing whitespace with dashes.
     *
     * Taken from WP' sanitize_file_name() and modified to not act on file extensions.
     *
     * Removes special characters that are illegal in filenames on certain
     * operating systems and special characters requiring special escaping
     * to manipulate at the command line. Replaces spaces and consecutive
     * dashes with a single dash. Trims period, dash and underscore from beginning
     * and end of filename. It is not guaranteed that this function will return a
     * filename that is allowed to be uploaded.
     * @param string $dirname The directory name to be sanitized
     * @return string The sanitized directory name
     */
    public function sanitize_directory_name($dirname)
    {
        $dirname_raw   = $dirname;
        $special_chars = array("?", "[", "]", "/", "\\", "=", "<", ">", ":", ";", ",", "'", "\"", "&", "$", "#", "*", "(", ")", "|", "~", "`", "!", "{", "}", "%", "+", chr(0));
        $special_chars = apply_filters('sanitize_file_name_chars', $special_chars, $dirname_raw);
        $dirname = preg_replace("#\x{00a0}#siu", ' ', $dirname);
        $dirname = str_replace($special_chars, '', $dirname);
        $dirname = str_replace(array( '%20', '+' ), '-', $dirname);
        $dirname = preg_replace('/[\r\n\t -]+/', '-', $dirname);
        $dirname = trim($dirname, '.-_');
        return $dirname;
    }
}