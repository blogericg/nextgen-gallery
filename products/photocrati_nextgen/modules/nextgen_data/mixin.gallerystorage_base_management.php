<?php

/**
 * Provides the basic methods of gallery management to C_Gallery_Storage
 * @property C_Gallery_Storage $object
 */
class Mixin_GalleryStorage_Base_Management extends Mixin
{
    /**
     * Set correct file permissions (taken from wp core). Should be called after writing any file
     *
     * @param string $filename
     * @return bool $result
     */
    function _chmod($filename = '')
    {
        $stat = @stat(dirname($filename));
        $perms = $stat['mode'] & 0000666; // Remove execute bits for files
        if (@chmod($filename, $perms))
            return TRUE;

        return FALSE;
    }

    function _delete_gallery_directory($abspath)
    {
        // Remove all image files and purge all empty directories left over
        $iterator = new DirectoryIterator($abspath);

        // Only delete image files! Other files may be stored incorrectly but it's not our place to delete them
        $removable_extensions = apply_filters('ngg_allowed_file_types', array('jpeg', 'jpg', 'png', 'gif'));
        foreach ($removable_extensions as $extension) {
            $removable_extensions[] = $extension . '_backup';
        }

        foreach ($iterator as $file) {
            if (in_array($file->getBasename(), array('.', '..'))) {
                continue;

            } elseif ($file->isFile() || $file->isLink()) {
                $extension = strtolower(pathinfo($file->getPathname(), PATHINFO_EXTENSION));
                if (in_array($extension, $removable_extensions, TRUE)) {
                    @unlink($file->getPathname());
                }

            } elseif ($file->isDir()) {
                $this->object->_delete_gallery_directory($file->getPathname());
            }
        }

        // DO NOT remove directories that still have files in them. Note: '.' and '..' are included with getSize()
        $empty = TRUE;
        foreach ($iterator as $file) {
            if (in_array($file->getBasename(), array('.', '..')))
                continue;
            $empty = FALSE;
        }
        if ($empty) {
            @rmdir($iterator->getPath());
        }
    }

    /**
     * Backs up an image file
     *
     * @param int|object $image
     * @param bool $save
     * @return bool
     */
    function backup_image($image, $save = TRUE)
    {
        $retval = FALSE;
        $image_path = $this->object->get_image_abspath($image);

        if ($image_path && @file_exists($image_path))
        {
            $retval = copy($image_path, $this->object->get_backup_abspath($image));

            // Store the dimensions of the image
            if (function_exists('getimagesize'))
            {
                $mapper = C_Image_Mapper::get_instance();
                if (!is_object($image))
                    $image = $mapper->find($image);
                if ($image)
                {
                    if (!property_exists($image, 'meta_data'))
                        $image->meta_data = array();
                    $dimensions = getimagesize($image_path);
                    $image->meta_data['backup'] = array(
                        'filename'  => basename($image_path),
                        'width'     => $dimensions[0],
                        'height'    => $dimensions[1],
                        'generated' => microtime()
                    );
                    if ($save)
                        $mapper->save($image);
                }
            }
        }

        return $retval;
    }

    /**
     * @param int[]|C_Image[]|stdClass[] $images
     * @param int|stdClass|C_Gallery $dst_gallery
     * @return int[]
     */
    function copy_images($images, $dst_gallery)
    {
        $retval = array();

        // Ensure that the image ids we have are valid
        $image_mapper = C_Image_Mapper::get_instance();
        foreach ($images as $image) {
            if (is_numeric($image))
                $image = $image_mapper->find($image);

            if (($image_abspath = $this->object->get_image_abspath($image)))
            {
                // Import the image; this will copy the main file
                $new_image_id = $this->object->import_image_file($dst_gallery, $image_abspath, $image->filename);       

                if ($new_image_id)
                {
                    // Copy the properties of the old image
                    $new_image = $image_mapper->find($new_image_id);
                    foreach (get_object_vars($image) as $key => $value) {
                        if (in_array($key, array('pid', 'galleryid', 'meta_data', 'filename', 'sortorder', 'extras_post_id')))
                            continue;
                        $new_image->$key = $value;
                    }
                    $image_mapper->save($new_image);

                    // Copy tags
                    $tags = wp_get_object_terms($image->pid, 'ngg_tag', 'fields=ids');
                    $tags = array_map('intval', $tags);
                    wp_set_object_terms($new_image_id, $tags, 'ngg_tag', true);

                    // Copy all of the generated versions (resized versions, watermarks, etc)
                    foreach ($this->get_image_sizes($image) as $named_size) {
                        if (in_array($named_size, array('full', 'thumbnail')))
                            continue;
                        $old_abspath = $this->object->get_image_abspath($image, $named_size);
                        $new_abspath = $this->object->get_image_abspath($new_image, $named_size);
                        if (is_array(@stat($old_abspath)))
                        {
                            $new_dir = dirname($new_abspath);
                            // Ensure the target directory exists
                            if (@stat($new_dir) === FALSE)
                                wp_mkdir_p($new_dir);
                            @copy($old_abspath, $new_abspath);
                        }
                    }
                    
                    // Mark as done
                    $retval[] = $new_image_id;
                }
            }
        }

        return $retval;
    }

    /**
     * Moves images from to another gallery
     *
     * @param int[]|C_Image[]|stdClass[] $images
     * @param int|stdClass|C_Gallery $gallery
     * @return int[]
     */
    function move_images($images, $gallery)
    {
        $retval = $this->object->copy_images($images, $gallery);

        if ($images) {
            foreach ($images as $image_id) {
                $this->object->delete_image($image_id);
            }
        }

        return $retval;
    }    

    function delete_directory($abspath)
    {
        $retval = FALSE;

        if (@file_exists($abspath))
        {
            $files = scandir($abspath);
            array_shift($files);
            array_shift($files);
            foreach ($files as $file) {
                $file_abspath = implode(DIRECTORY_SEPARATOR, array(rtrim($abspath, "/\\"), $file));
                if (is_dir($file_abspath))
                    $this->object->delete_directory($file_abspath);
                else
                    unlink($file_abspath);
            }
            rmdir($abspath);
            $retval = @file_exists($abspath);
        }

        return $retval;
    }

    function delete_gallery($gallery)
    {
        $fs = C_Fs::get_instance();
        $safe_dirs = array(
            DIRECTORY_SEPARATOR,
            $fs->get_document_root('plugins'),
            $fs->get_document_root('plugins_mu'),
            $fs->get_document_root('templates'),
            $fs->get_document_root('stylesheets'),
            $fs->get_document_root('content'),
            $fs->get_document_root('galleries'),
            $fs->get_document_root()
        );

        $abspath = $this->object->get_gallery_abspath($gallery);

        if ($abspath && file_exists($abspath) && !in_array(stripslashes($abspath), $safe_dirs))
            $this->object->_delete_gallery_directory($abspath);
    }

    /**
     * @param int|C_Image|stdClass $image
     * @param bool string $size
     * @return bool
     */
    function delete_image($image, $size = FALSE)
    {
        $retval = FALSE;

        // Ensure that we have the image entity
        if (is_numeric($image))
            $image = $this->object->_image_mapper->find($image);

        if ($image)
        {
            $image_id = $image->{$image->id_field};
            do_action('ngg_delete_image', $image_id, $size);

            // Delete only a particular image size
            if ($size)
            {
                $abspath = $this->object->get_image_abspath($image, $size);
                if ($abspath && @file_exists($abspath))
                    @unlink($abspath);
                if (isset($image->meta_data) && isset($image->meta_data[$size]))
                {
                    unset($image->meta_data[$size]);
                    $this->object->_image_mapper->save($image);
                }
            }
            // Delete all sizes of the image
            else {
                foreach ($this->get_image_sizes($image) as $named_size) {
                    
                    $image_abspath = $this->object->get_image_abspath($image, $named_size);        
                    @unlink($image_abspath);
                }

                // Delete the entity
                $this->object->_image_mapper->destroy($image);
            }
            $retval = TRUE;
        }

        return $retval;
    }

    /**
     * Recover image from backup copy and reprocess it
     *
     * @param int|stdClass|C_Image $image
     * @return bool|string result code
     */
    function recover_image($image)
    {
        $retval = FALSE;

        if (is_numeric($image))
            $image = $this->object->_image_mapper->find($image);

        if ($image)
        {
            $full_abspath   = $this->object->get_image_abspath($image);
            $backup_abspath = $this->object->get_image_abspath($image, 'backup');

            if ($backup_abspath != $full_abspath && @file_exists($backup_abspath))
            {
                if (is_writable($full_abspath) && is_writable(dirname($full_abspath)))
                {
                    // Copy the backup
                    if (@copy($backup_abspath, $full_abspath))
                    {
                        // Backup images are not altered at all; we must re-correct the EXIF/Orientation tag
                        $this->object->correct_exif_rotation($image, TRUE);

                        // Re-create non-fullsize image sizes
                        foreach ($this->object->get_image_sizes($image) as $named_size) {
                            if (in_array($named_size, array('full', 'backup')))
                                continue;

                            // Reset thumbnail cropping set by 'Edit thumb' dialog
                            if ($named_size === 'thumbnail')
                                unset($image->meta_data[$named_size]['crop_frame']);

                            $thumbnail = $this->object->generate_image_clone(
                                $full_abspath,
                                $this->object->get_image_abspath($image, $named_size),
                                $this->object->get_image_size_params($image, $named_size)
                            );
                            if ($thumbnail)
                                $thumbnail->destruct();
                        }

                        do_action('ngg_recovered_image', $image);

                        // Reimport all metadata
                        $retval = $this->object->_image_mapper->reimport_metadata($image);

                        if (C_CDN_Providers::is_cdn_configured())
                        {
                            \ReactrIO\Background\Job::create(
                                sprintf(__("Publishing recovered image #%d", 'nextgen-gallery'), $image->pid),
                                'cdn_publish_image',
                                ['id' => $image->pid, 'size' => 'all']
                            )->save('cdn');
                        }
                    }
                }
            }
        }

        return $retval;
    }
}