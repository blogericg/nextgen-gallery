<?php

/**
 * Provides upload-related methods used by C_Gallery_Storage
 * @property C_Gallery_Storage $object
 */
class Mixin_GalleryStorage_Base_Upload extends Mixin
{
    /**
     * @param string $abspath
     * @param int $gallery_id
     * @param bool $create_new_gallerypath
     * @param null|string $gallery_title
     * @param array[string] $filenames
     * @return array|bool FALSE on failure
     */
    function import_gallery_from_fs($abspath, $gallery_id = NULL, $create_new_gallerypath = TRUE, $gallery_title = NULL, $filenames = array())
    {
        if (@!file_exists($abspath))
            return FALSE;

        $fs = C_Fs::get_instance();

        $retval = array('image_ids' => array());

        // Ensure that this folder has images
        $files       = array();
        $directories = array();
        foreach (scandir($abspath) as $file) {
            if ($file == '.' || $file == '..' || strtoupper($file) == '__MACOSX')
                continue;

            $file_abspath = $fs->join_paths($abspath, $file);

            // Omit 'hidden' directories prefixed with a period
            if (is_dir($file_abspath) && strpos($file, '.') !== 0)
            {
                $directories[] = $file_abspath;
            }
            elseif ($this->is_image_file($file_abspath)) {
                if ($filenames && array_search($file_abspath, $filenames) !== FALSE)
                    $files[] = $file_abspath;
                else if (!$filenames)
                    $files[] = $file_abspath;
            }
        }

        if (empty($files) && empty($directories))
            return FALSE;

        // Get needed utilities
        $gallery_mapper = C_Gallery_Mapper::get_instance();

        // Recurse through the directory and pull in all of the valid images we find
        if (!empty($directories))
        {
            foreach ($directories as $dir) {
                $subImport = $this->object->import_gallery_from_fs($dir, $gallery_id, $create_new_gallerypath, $gallery_title, $filenames);
                if ($subImport)
                    $retval['image_ids'] = array_merge($retval['image_ids'], $subImport['image_ids']);
            }
        }

        // If no gallery has been specified, then use the directory name as the gallery name
        if (!$gallery_id)
        {
            // Create the gallery
            $gallery = $gallery_mapper->create(array(
                'title' => $gallery_title ? $gallery_title : M_I18n::mb_basename($abspath),
            ));

            if (!$create_new_gallerypath)
            {
                $gallery_root = $fs->get_document_root('gallery');
                $gallery->path = str_ireplace($gallery_root, '', $abspath);
            }

            // Save the gallery
            if ($gallery->save())
                $gallery_id = $gallery->id();
        }

        // Ensure that we have a gallery id
        if (!$gallery_id)
            return FALSE;
        else
            $retval['gallery_id'] = $gallery_id;

        foreach ($files as $file_abspath) {
            $basename = pathinfo($file_abspath, PATHINFO_BASENAME);
            if (($image_id = $this->import_image_file($gallery_id, $file_abspath, $basename, FALSE, FALSE, FALSE)))
                $retval['image_ids'][] = $image_id;
        }

        // Add the gallery name to the result
        if (!isset($gallery))
            $gallery = $gallery_mapper->find($gallery_id);

        $retval['gallery_name'] = $gallery->title;
        return $retval;
    }

    /**
     * @param string $filename
     * @return bool
     */
    public function is_allowed_image_extension($filename)
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $allowed_extensions = apply_filters(
            'ngg_allowed_file_types',
            array('jpeg', 'jpg', 'png', 'gif')
        );

        return in_array($extension, $allowed_extensions);
    }

    function is_current_user_over_quota()
    {
        $retval = FALSE;
        $settings = C_NextGen_Settings::get_instance();

        if ((is_multisite()) && $settings->get('wpmuQuotaCheck')) {
            require_once(ABSPATH . 'wp-admin/includes/ms.php');
            $retval = upload_is_user_over_quota(FALSE);
        }

        return $retval;
    }

    /**
     * @param string? $filename
     * @return bool
     */
    function is_image_file($filename = NULL)
    {
        $retval = FALSE;

        if (!$filename
            &&  isset($_FILES['file'])
            &&  $_FILES['file']['error'] == 0)
        {
            $filename = $_FILES['file']['tmp_name'];
        }

        $valid_types = array(
            'image/gif',
            'image/jpg',
            'image/jpeg',
            'image/pjpeg',
            'image/png',
        );

        // If we can, we'll verify the mime type
        if (function_exists('exif_imagetype'))
        {
            if (($image_type = @exif_imagetype($filename)) !== FALSE)
                $retval = in_array(image_type_to_mime_type($image_type), $valid_types);
        }
        else {
            $file_info = @getimagesize($filename);
            if (isset($file_info[2]))
                $retval = in_array(image_type_to_mime_type($file_info[2]), $valid_types);
        }

        return $retval;
    }

    function is_zip()
    {
        $retval = FALSE;

        if ((isset($_FILES['file']) && $_FILES['file']['error'] == 0)) {
            $file_info = $_FILES['file'];

            if (isset($file_info['type'])) {
                $type = $file_info['type'];
                $type_parts = explode('/', $type);

                if (strtolower($type_parts[0]) == 'application') {
                    $spec = $type_parts[1];
                    $spec_parts = explode('-', $spec);
                    $spec_parts = array_map('strtolower', $spec_parts);

                    if (in_array($spec, array('zip', 'octet-stream')) || in_array('zip', $spec_parts)) {
                        $retval = true;
                    }
                }
            }
        }

        return $retval;
    }

    function maybe_base64_decode($data)
    {
        $decoded = base64_decode($data);
        if ($decoded === FALSE) return $data;
        else if (base64_encode($decoded) == $data) return base64_decode($data);
        return $data;
    }

    function get_unique_abspath($file_abspath)
    {
        $filename       = basename($file_abspath);
        $dir_abspath    = dirname($file_abspath);
        $num            = 1;

        $pattern = path_join($dir_abspath, "*_{$filename}");
        if (($found = glob($pattern))) {
            natsort($found);
            $last = array_pop($found);
            $last = basename($last);
            if (preg_match("/^(\d+)_/", $last, $match)) {
                $num = intval($match[1])+1;
            }
        } 

        return path_join($dir_abspath, "{$num}_{$filename}");
    }

    function sanitize_filename_for_db($filename=NULL) {
        $filename = $filename ? $filename : uniqid('nextgen-gallery');
        $filename = preg_replace("#^/#", "", $filename);
        $filename = sanitize_file_name($filename);
        if (preg_match("/\-(png|jpg|gif|jpeg)$/i", $filename, $match)) {
            $filename = str_replace($match[0], '.'.$match[1], $filename);
        }
        return $filename;
    }

    function import_image_file($dst_gallery, $image_abspath, $filename=NULL, $image=FALSE, $override=FALSE, $move=FALSE)
    {
        $image_abspath = wp_normalize_path($image_abspath);

        if ($this->object->is_current_user_over_quota())
        {
            $message = sprintf(__('Sorry, you have used your space allocation. Please delete some files to upload more files.', 'nggallery'));
            throw new E_NoSpaceAvailableException($message);
        }

        // Do we have a gallery to import to?
        if ($dst_gallery)
        {
            // Get the gallery abspath. This is where we will put the image files
            $gallery_abspath = $this->object->get_gallery_abspath($dst_gallery);

            // If we can't write to the directory, then there's no point in continuing
            if (!@file_exists($gallery_abspath))
                @wp_mkdir_p($gallery_abspath);
            if (!is_writable($gallery_abspath))
                throw new E_InsufficientWriteAccessException(FALSE, $gallery_abspath, FALSE);

            // Sanitize the filename for storing in the DB
            $filename = $this->sanitize_filename_for_db($filename);

            // Ensure that the filename is valid
            if (!preg_match("/(png|jpeg|jpg|gif)\$/i", $filename))
                throw new E_UploadException(__('Invalid image file. Acceptable formats: JPG, GIF, and PNG.', 'nggallery'));

            // Compute the destination folder
            $new_image_abspath = path_join($gallery_abspath, $filename);

            // Are the src and dst the same? If so, we don't have to copy or move files
            if ($image_abspath != $new_image_abspath)
            {
                // If we're not to override, ensure that the filename is unique
                if (!$override && @file_exists($new_image_abspath))
                {
                    $new_image_abspath  = $this->object->get_unique_abspath($new_image_abspath);
                    $filename           = $this->sanitize_filename_for_db(basename($new_image_abspath));
                }

                // Try storing the file
                $copied = copy($image_abspath, $new_image_abspath);
                if ($copied && $move) {
                    unlink($image_abspath);
                }

                // Ensure that we're not vulnerable to CVE-2017-2416 exploit
                if (($dimensions = getimagesize($new_image_abspath)) !== FALSE) {
                    if ((isset($dimensions[0]) && intval($dimensions[0]) > 30000)
                        ||  (isset($dimensions[1]) && intval($dimensions[1]) > 30000)) {
                        unlink($new_image_abspath);
                        throw new E_UploadException(__('Image file too large. Maximum image dimensions supported are 30k x 30k.'));
                    }
                }
            }

            // Save the image in the DB
            $image_mapper = C_Image_Mapper::get_instance();
            $image_mapper->_use_cache = FALSE;
            if ($image)
            {
                if (is_numeric($image))
                    $image = $image_mapper->find($image);
            }
            if (!$image)
                $image = $image_mapper->create();
            $image->alttext		= preg_replace("#\.\w{2,4}$#", "", $filename);
            $image->galleryid	= is_numeric($dst_gallery) ? $dst_gallery : $dst_gallery->gid;
            $image->filename	= $filename;
            $image->image_slug  = nggdb::get_unique_slug(sanitize_title_with_dashes($image->alttext), 'image');
            $image_id           = $image_mapper->save($image);

            if (!$image_id)
            {
                $exception = '';
                foreach ($image->get_errors() as $field => $errors) {
                    foreach ($errors as $error) {
                        if (!empty($exception))
                            $exception .= "<br/>";
                        $exception .= __(sprintf("Error while uploading %s: %s", $filename, $error), 'nggallery');
                    }

                }
                throw new E_UploadException($exception);
            }

            // Important: do not remove this line. The image mapper's save() routine imports metadata
            // meaning we must re-acquire a new $image object after saving it above; if we do not our
            // existing $image object will lose any metadata retrieved during said save() method.
            $image = $image_mapper->find($image_id);

            $image_mapper->_use_cache = TRUE;
            $settings = C_NextGen_Settings::get_instance();

            // Backup the image
            if ($settings->get('imgBackup', FALSE))
                $this->object->backup_image($image, TRUE);

            // Most browsers do not honor EXIF's Orientation header: rotate the image to prevent display issues
            $this->object->correct_exif_rotation($image, TRUE);

            // Create resized version of image
            if ($settings->get('imgAutoResize', FALSE))
                $this->object->generate_resized_image($image, TRUE);

            // Generate a thumbnail for the image
            $this->object->generate_thumbnail($image);

            // Set gallery preview image if missing
            C_Gallery_Mapper::get_instance()->set_preview_image($dst_gallery, $image_id, TRUE);

            // Notify other plugins that an image has been added
            do_action('ngg_added_new_image', $image);

            // delete dirsize after adding new images
            delete_transient( 'dirsize_cache' );

            // Seems redundant to above hook. Maintaining for legacy purposes
            do_action( 
                'ngg_after_new_images_added',
                is_numeric($dst_gallery) ? $dst_gallery : $dst_gallery->gid,
                array($image_id)
            );
            
            return $image_id;

        }
        else {
            throw new E_EntityNotFoundException();
        }

        return NULL;
    }

    /**
     * Uploads base64 file to a gallery
     * @param int|stdClass|C_Gallery $gallery
     * @param $data base64-encoded string of data representing the image
     * @param string|false (optional) $filename specifies the name of the file
     * @param int|false $image_id (optional)
     * @param bool $override (optional)
     * @return C_Image
     */
    function upload_base64_image($gallery, $data, $filename=FALSE, $image_id=FALSE, $override=FALSE, $move=FALSE)
    {
        try {
            $temp_abspath = tempnam(sys_get_temp_dir(), '');

            // Try writing the image
            $fp = fopen($temp_abspath, 'wb');
            fwrite($fp, $this->maybe_base64_decode($data));
            fclose($fp);
        }
        catch (E_UploadException $ex) {
            throw $ex;
        }

        return $this->object->import_image_file($gallery, $temp_abspath, $filename, $image_id, $override, $move);
    }

    /**
     * Uploads an image for a particular gallery
     * @param int|object|C_Gallery $gallery
     * @param string|bool $filename (optional) Specifies the name of the file
     * @param string|bool $data (optional) If specified, expects base64 encoded string of data
     * @return C_Image
     */
    function upload_image($gallery, $filename=FALSE, $data=FALSE)
    {
        $retval = NULL;

        // Ensure that we have the data present that we require
        if ((isset($_FILES['file']) && $_FILES['file']['error'] == 0)) {

            //		$_FILES = Array(
            //		 [file]	=>	Array (
            //            [name] => Canada_landscape4.jpg
            //            [type] => image/jpeg
            //            [tmp_name] => /private/var/tmp/php6KO7Dc
            //            [error] => 0
            //            [size] => 64975
            //         )
            //
            $file = $_FILES['file'];

            if ($this->object->is_zip()) {
                $retval = $this->object->upload_zip($gallery);
            }
            else if ($this->is_image_file()) {
                $retval = $this->object->import_image_file(
                    $gallery,
                    $file['tmp_name'],
                    $filename ? $filename : (isset($file['name']) ? $file['name'] : FALSE),
                    FALSE,
                    FALSE,
                    TRUE
                );
            }
            else {
                // Remove the non-valid (and potentially insecure) file from the PHP upload directory
                if (isset($_FILES['file']['tmp_name'])) {
                    $filename = $_FILES['file']['tmp_name'];
                    @unlink($filename);
                }
                throw new E_UploadException(__('Invalid image file. Acceptable formats: JPG, GIF, and PNG.', 'nggallery'));
            }
        }
        elseif ($data) {
            $retval = $this->object->upload_base64_image(
                $gallery,
                $data,
                $filename
            );
        }
        else throw new E_UploadException();

        return $retval;
    }

    /**
     * @param int $gallery_id
     * @return array|bool
     */
    function upload_zip($gallery_id)
    {
        if (!$this->object->is_zip())
            return FALSE;

        $retval = FALSE;

        $memory_limit = intval(ini_get('memory_limit'));
        if (!extension_loaded('suhosin') && $memory_limit < 256)
            @ini_set('memory_limit', '256M');

        $fs = C_Fs::get_instance();

        // Uses the WordPress ZIP abstraction API
        include_once($fs->join_paths(ABSPATH, 'wp-admin', 'includes', 'file.php'));
        WP_Filesystem(FALSE, get_temp_dir(), TRUE);

        // Ensure that we truly have the gallery id
        $gallery_id = $this->object->_get_gallery_id($gallery_id);
        $zipfile    = $_FILES['file']['tmp_name'];
        $dest_path  = implode(DIRECTORY_SEPARATOR, array(
            rtrim(get_temp_dir(), "/\\"),
            'unpacked-' . M_I18n::mb_basename($zipfile)
        ));

        // Attempt to extract the zip file into the normal system directory
        $extracted = $this->object->extract_zip($zipfile, $dest_path);

        // Now verify it worked. get_temp_dir() will check each of the following directories to ensure they are
        // a directory and against wp_is_writable(). Should ALL of those options fail we will fallback to wp_upload_dir().
        //
        // WP_TEMP_DIR
        // sys_get_temp_dir()
        // ini/upload_tmp_dir
        // WP_CONTENT_DIR
        // /tmp
        $size = 0;
        $files = glob($dest_path . DIRECTORY_SEPARATOR . '*');
        foreach ($files as $file) {
            if (is_array(stat($file)))
                $size += filesize($file);
        }

        // Extraction failed; attempt again with wp_upload_dir()
        if ($size == 0)
        {
            // Remove the empty directory we may have possibly created but could not write to
            $this->object->delete_directory($dest_path);

            $destination = wp_upload_dir();
            $destination_path = $destination['basedir'];
            $dest_path = implode(DIRECTORY_SEPARATOR, array(
                rtrim($destination_path, "/\\"),
                rand(),
                'unpacked-' . M_I18n::mb_basename($zipfile)
            ));

            $extracted = $this->object->extract_zip($zipfile, $dest_path);
        }

        if ($extracted)
            $retval = $this->object->import_gallery_from_fs($dest_path, $gallery_id);

        $this->object->delete_directory($dest_path);

        if (!extension_loaded('suhosin'))
            @ini_set('memory_limit', $memory_limit . 'M');

        return $retval;
    }

    /**
     * @param string $zipfile
     * @param string $dest_path
     * @return bool FALSE on failure
     */
    public function extract_zip($zipfile, $dest_path)
    {
        wp_mkdir_p($dest_path);

        if (class_exists('ZipArchive', FALSE) && apply_filters('unzip_file_use_ziparchive', TRUE))
        {
            $zipObj = new ZipArchive;
            if ($zipObj->open($zipfile) === FALSE)
                return FALSE;

            for ($i = 0; $i < $zipObj->numFiles; $i++) {
                $filename = $zipObj->getNameIndex($i);
                if (!$this->object->is_allowed_image_extension($filename))
                    continue;
                $zipObj->extractTo($dest_path, array($zipObj->getNameIndex($i)));
            }
        }
        else {
            require_once(ABSPATH . 'wp-admin/includes/class-pclzip.php');
            $zipObj = new PclZip($zipfile);
            $zipContent = $zipObj->listContent();
            $indexesToExtract = array();

            foreach ($zipContent as $zipItem) {
                if ($zipItem['folder'])
                    continue;
                if (!$this->object->is_allowed_image_extension($zipItem['stored_filename']))
                    continue;
                $indexesToExtract[] = $zipItem['index'];
            }

            if (!$zipObj->extractByIndex(implode(',', $indexesToExtract), $dest_path))
                return FALSE;
        }

        return TRUE;
    }
}