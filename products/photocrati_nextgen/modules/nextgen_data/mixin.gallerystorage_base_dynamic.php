<?php

/**
 * Provides methods to C_Gallery_Storage related to dynamic images, thumbnails, clones, etc
 * @property C_Gallery_Storage $object
 */
class Mixin_GalleryStorage_Base_Dynamic extends Mixin
{
    /**
     * Returns an array of dimensional properties (width, height, real_width, real_height) of a resulting clone image if and when generated
     * @param object|int $image Image ID or an image object
     * @param string $size
     * @param array $params
     * @param bool $skip_defaults
     * @return bool|array
     */
    function calculate_image_size_dimensions($image, $size, $params = null, $skip_defaults = false)
    {
        $retval = FALSE;

        // Get the image entity
        if (is_numeric($image)) {
            $image = $this->object->_image_mapper->find($image);
        }

        // Ensure we have a valid image
        if ($image)
        {
            $params = $this->object->get_image_size_params($image, $size, $params, $skip_defaults);

            // Get the image filename
            $image_path = $this->object->get_original_abspath($image, 'original');
            $clone_path = $this->object->get_image_abspath($image, $size);

            $retval = $this->object->calculate_image_clone_dimensions($image_path, $clone_path, $params);
        }

        return $retval;
    }

    /**
     * Generates a "clone" for an existing image, the clone can be altered using the $params array
     * @param string $image_path
     * @param string $clone_path
     * @param array $params
     * @return null|object
     */
    function generate_image_clone($image_path, $clone_path, $params)
    {
        $crop       = isset($params['crop'])       ? $params['crop']       : NULL;
        $watermark  = isset($params['watermark'])  ? $params['watermark']  : NULL;
        $reflection = isset($params['reflection']) ? $params['reflection'] : NULL;
        $rotation   = isset($params['rotation'])   ? $params['rotation']   : NULL;
        $flip       = isset($params['flip'])       ? $params['flip']       : NULL;
        $destpath   = NULL;
        $thumbnail  = NULL;

        $result = $this->object->calculate_image_clone_result($image_path, $clone_path, $params);

        // XXX this should maybe be removed and extra settings go into $params?
        $settings = apply_filters('ngg_settings_during_image_generation', C_NextGen_Settings::get_instance()->to_array());

        // Ensure we have a valid image
        if ($image_path && @file_exists($image_path) && $result != null && !isset($result['error']))
        {
            $image_dir    = dirname($image_path);
            $clone_path   = $result['clone_path'];
            $clone_dir    = $result['clone_directory'];
            $clone_format = $result['clone_format'];
            $format_list  = $this->object->get_image_format_list();

            // Ensure target directory exists, but only create 1 subdirectory
            if (!@file_exists($clone_dir))
            {
                if (strtolower(realpath($image_dir)) != strtolower(realpath($clone_dir)))
                {
                    if (strtolower(realpath($image_dir)) == strtolower(realpath(dirname($clone_dir))))
                    {
                        wp_mkdir_p($clone_dir);
                    }
                }
            }

            $method = $result['method'];
            $width = $result['width'];
            $height = $result['height'];
            $quality = $result['quality'];

            if ($quality == null)
            {
                $quality = 100;
            }

            if ($method == 'wordpress')
            {
                $original = wp_get_image_editor($image_path);
                $destpath = $clone_path;
                if (!is_wp_error($original))
                {
                    $original->resize($width, $height, $crop);
                    $original->set_quality($quality);
                    $original->save($clone_path);
                }
            }
            else if ($method == 'nextgen')
            {
                $destpath = $clone_path;
                $thumbnail = new C_NggLegacy_Thumbnail($image_path, true);
                if (!$thumbnail->error) {
                    if ($crop) {
                        $crop_area = $result['crop_area'];
                        $crop_x = $crop_area['x'];
                        $crop_y = $crop_area['y'];
                        $crop_width = $crop_area['width'];
                        $crop_height = $crop_area['height'];

                        $thumbnail->crop($crop_x, $crop_y, $crop_width, $crop_height);
                    }

                    $thumbnail->resize($width, $height);
                }
                else {
                    $thumbnail = NULL;
                }
            }

            // We successfully generated the thumbnail
            if (is_string($destpath) && (@file_exists($destpath) || $thumbnail != null))
            {
                if ($clone_format != null)
                {
                    if (isset($format_list[$clone_format]))
                    {
                        $clone_format_extension = $format_list[$clone_format];
                        $clone_format_extension_str = null;

                        if ($clone_format_extension != null)
                        {
                            $clone_format_extension_str = '.' . $clone_format_extension;
                        }

                        $destpath_info = M_I18n::mb_pathinfo($destpath);
                        $destpath_extension = $destpath_info['extension'];

                        if (strtolower($destpath_extension) != strtolower($clone_format_extension))
                        {
                            $destpath_dir = $destpath_info['dirname'];
                            $destpath_basename = $destpath_info['filename'];
                            $destpath_new = $destpath_dir . DIRECTORY_SEPARATOR . $destpath_basename . $clone_format_extension_str;

                            if ((@file_exists($destpath) && rename($destpath, $destpath_new)) || $thumbnail != null)
                            {
                                $destpath = $destpath_new;
                            }
                        }
                    }
                }

                if (is_null($thumbnail))
                {
                    $thumbnail = new C_NggLegacy_Thumbnail($destpath, true);

                    if ($thumbnail->error) {
                        $thumbnail = null;

                        return null;
                    }
                }
                else
                {
                    $thumbnail->fileName = $destpath;
                }

                // This is quite odd, when watermark equals int(0) it seems all statements below ($watermark == 'image') and ($watermark == 'text') both evaluate as true
                // so we set it at null if it evaluates to any null-like value
                if ($watermark == null)
                {
                    $watermark = null;
                }

                if ($watermark == 1 || $watermark === true)
                {
                    $watermark_setting_keys = array(
                        'wmFont',
                        'wmType',
                        'wmPos',
                        'wmXpos',
                        'wmYpos',
                        'wmPath',
                        'wmText',
                        'wmOpaque',
                        'wmFont',
                        'wmSize', 
                        'wmColor' 
                    );
                    foreach ($watermark_setting_keys as $watermark_key) {
                        if (!isset($params[$watermark_key])) $params[$watermark_key] = $settings[$watermark_key];
                    }

                    if (in_array(strval($params['wmType']), array('image', 'text')))
                    {
                        $watermark = $params['wmType'];
                    }
                    else
                    {
                        $watermark = 'text';
                    }
                }

                $watermark = strval($watermark);

                if ($watermark == 'image')
                {
                    $thumbnail->watermarkImgPath = $params['wmPath'];
                    $thumbnail->watermarkImage($params['wmPos'], $params['wmXpos'], $params['wmYpos']);
                }
                else if ($watermark == 'text')
                {
                    $thumbnail->watermarkText = $params['wmText'];
                    $thumbnail->watermarkCreateText($params['wmColor'], $params['wmFont'], $params['wmSize'], $params['wmOpaque']);
                    $thumbnail->watermarkImage($params['wmPos'], $params['wmXpos'], $params['wmYpos']);
                }

                if ($rotation && in_array(abs($rotation), array(90, 180, 270)))
                {
                    $thumbnail->rotateImageAngle($rotation);
                    $remove_orientation_exif = TRUE;
                }
                else {
                    $remove_orientation_exif = FALSE;
                }

                $flip = strtolower($flip);

                if ($flip && in_array($flip, array('h', 'v', 'hv')))
                {
                    $flip_h = in_array($flip, array('h', 'hv'));
                    $flip_v = in_array($flip, array('v', 'hv'));

                    $thumbnail->flipImage($flip_h, $flip_v);
                }

                if ($reflection)
                {
                    $thumbnail->createReflection(40, 40, 50, FALSE, '#a4a4a4');
                }

                if ($clone_format != null && isset($format_list[$clone_format]))
                {
                    // Force format
                    $thumbnail->format = strtoupper($format_list[$clone_format]);
                }

                $thumbnail = apply_filters('ngg_before_save_thumbnail', $thumbnail);

                $backup_path = $image_path . '_backup';
                try {
                    $exif_abspath = @file_exists($backup_path) ? $backup_path : $image_path;
                    $exif_iptc = @C_Exif_Writer::read_metadata($exif_abspath);
                }
                catch (PelException $ex) {
                    error_log("Could not read image metadata {$exif_abspath}");
                    error_log(print_r($ex, TRUE));
                }

                $thumbnail->save($destpath, $quality);

                // We've just rotated the image however the EXIF metadata contains an Orientation tag. To prevent
                // certain browsers from rotating our already-rotated image we reset the Orientation tag to the default.
                if ($remove_orientation_exif && !empty($exif_iptc['exif']))
                    $exif_iptc['exif'] = @C_Exif_Writer::reset_orientation($exif_iptc['exif']);

                try {
                    @C_Exif_Writer::write_metadata($destpath, $exif_iptc);
                }
                catch (PelException $ex) {
                    error_log("Could not write data to {$destpath}");
                    error_log(print_r($ex, TRUE));
                }
                    
            }
        }

        return $thumbnail;
    }

    /**
     * Returns an array of dimensional properties (width, height, real_width, real_height) of a resulting clone image if and when generated
     * @param string $image_path
     * @param string $clone_path
     * @param array $params
     * @return null|array
     */
    function calculate_image_clone_dimensions($image_path, $clone_path, $params)
    {
        $retval = null;
        $result = $this->object->calculate_image_clone_result($image_path, $clone_path, $params);

        if ($result != null) {
            $retval = array(
                'width' => $result['width'],
                'height' => $result['height'],
                'real_width' => $result['real_width'],
                'real_height' => $result['real_height']
            );
        }

        return $retval;
    }

    /**
     * Returns an array of properties of a resulting clone image if and when generated
     * @param string $image_path
     * @param string $clone_path
     * @param array $params
     * @return null|array
     */
    function calculate_image_clone_result($image_path, $clone_path, $params)
    {
        $width      = isset($params['width'])      ? $params['width']      : NULL;
        $height     = isset($params['height'])     ? $params['height']     : NULL;
        $quality    = isset($params['quality'])    ? $params['quality']    : NULL;
        $type       = isset($params['type'])       ? $params['type']       : NULL;
        $crop       = isset($params['crop'])       ? $params['crop']       : NULL;
        $watermark  = isset($params['watermark'])  ? $params['watermark']  : NULL;
        $rotation   = isset($params['rotation'])   ? $params['rotation']   : NULL;
        $reflection = isset($params['reflection']) ? $params['reflection'] : NULL;
        $crop_frame = isset($params['crop_frame']) ? $params['crop_frame'] : NULL;
        $result  = NULL;

        // Ensure we have a valid image
        if ($image_path && @file_exists($image_path))
        {
            // Ensure target directory exists, but only create 1 subdirectory
            $image_dir = dirname($image_path);
            $clone_dir = dirname($clone_path);
            $image_extension = M_I18n::mb_pathinfo($image_path, PATHINFO_EXTENSION);
            $image_extension_str = null;
            $clone_extension = M_I18n::mb_pathinfo($clone_path, PATHINFO_EXTENSION);
            $clone_extension_str = null;

            if ($image_extension != null)
            {
                $image_extension_str = '.' . $image_extension;
            }

            if ($clone_extension != null)
            {
                $clone_extension_str = '.' . $clone_extension;
            }

            $image_basename = M_I18n::mb_basename($image_path);
            $clone_basename = M_I18n::mb_basename($clone_path);
            // We use a default suffix as passing in null as the suffix will make WordPress use a default
            $clone_suffix = null;
            $format_list = $this->object->get_image_format_list();
            $clone_format = null; // format is determined below and based on $type otherwise left to null

            // suffix is only used to reconstruct paths for image_resize function
            if (strpos($clone_basename, $image_basename) === 0)
            {
                $clone_suffix = substr($clone_basename, strlen($image_basename));
            }

            if ($clone_suffix != null && $clone_suffix[0] == '-')
            {
                // WordPress adds '-' on its own
                $clone_suffix = substr($clone_suffix, 1);
            }

            // Get original image dimensions
            $dimensions = getimagesize($image_path);

            if ($width == null && $height == null) {
                if ($dimensions != null) {

                    if ($width == null) {
                        $width = $dimensions[0];
                    }

                    if ($height == null) {
                        $height = $dimensions[1];
                    }
                }
                else {
                    // XXX Don't think there's any other option here but to fail miserably...use some hard-coded defaults maybe?
                    return null;
                }
            }

            if ($dimensions != null) {
                $dimensions_ratio = $dimensions[0] / $dimensions[1];

                if ($width == null) {
                    $width = (int) round($height * $dimensions_ratio);

                    if ($width == ($dimensions[0] - 1))
                    {
                        $width = $dimensions[0];
                    }
                }
                else if ($height == null) {
                    $height = (int) round($width / $dimensions_ratio);

                    if ($height == ($dimensions[1] - 1))
                    {
                        $height = $dimensions[1];
                    }
                }

                if ($width > $dimensions[0]) {
                    $width = $dimensions[0];
                }

                if ($height > $dimensions[1]) {
                    $height = $dimensions[1];
                }

                $image_format = $dimensions[2];

                if ($type != null)
                {
                    if (is_string($type))
                    {
                        $type = strtolower($type);

                        // Indexes in the $format_list array correspond to IMAGETYPE_XXX values appropriately
                        if (($index = array_search($type, $format_list)) !== false)
                        {
                            $type = $index;

                            if ($type != $image_format)
                            {
                                // Note: this only changes the FORMAT of the image but not the extension
                                $clone_format = $type;
                            }
                        }
                    }
                }
            }

            if ($width == null || $height == null) {
                // Something went wrong...
                return null;
            }

            // We now need to estimate the 'quality' or level of compression applied to the original JPEG: *IF* the
            // original image has a quality lower than the $quality parameter we will end up generating a new image
            // that is MUCH larger than the original. 'Quality' as an EXIF or IPTC property is quite unreliable
            // and not all software honors or treats it the same way. This calculation is simple: just compare the size
            // that our image could become to what it currently is. '3' is important here as JPEG uses 3 bytes per pixel.
            //
            // First we attempt to use ImageMagick if we can; it has a more robust method of calculation.
            if (!empty($dimensions['mime']) && $dimensions['mime'] == 'image/jpeg')
            {
                $possible_quality = NULL;
                $try_image_magick = TRUE;

                if (function_exists('is_wpe') && ($dimensions[0] >= 8000 || $dimensions[1] >= 8000))
                    $try_image_magick = FALSE;

                if ($try_image_magick && extension_loaded('imagick') && class_exists('Imagick'))
                {
                    $img = new Imagick($image_path);
                    if (method_exists($img, 'getImageCompressionQuality'))
                        $possible_quality = $img->getImageCompressionQuality();
                }

                // ImageMagick wasn't available so we guess it from the dimensions and filesize
                if ($possible_quality === NULL) {
                    $filesize = filesize($image_path);
                    $possible_quality = (101 - (($width * $height) * 3) / $filesize);
                }

                if ($possible_quality !== NULL && $possible_quality < $quality)
                    $quality = $possible_quality;
            }

            $result['clone_path']      = $clone_path;
            $result['clone_directory'] = $clone_dir;
            $result['clone_suffix']    = $clone_suffix;
            $result['clone_format']    = $clone_format;
            $result['base_width']      = $dimensions[0];
            $result['base_height']     = $dimensions[1];

            // image_resize() has limitations:
            // - no easy crop frame support
            // - fails if the dimensions are unchanged
            // - doesn't support filename prefix, only suffix so names like thumbs_original_name.jpg for $clone_path are not supported
            //   also suffix cannot be null as that will make WordPress use a default suffix...we could use an object that returns empty string from __toString() but for now just fallback to ngg generator
            if (FALSE) { // disabling the WordPress method for Iteration #6
//			if (($crop_frame == null || !$crop) && ($dimensions[0] != $width && $dimensions[1] != $height) && $clone_suffix != null)
                $result['method'] = 'wordpress';

                $new_dims = image_resize_dimensions($dimensions[0], $dimensions[1], $width, $height, $crop);

                if ($new_dims) {
                    list($dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h) = $new_dims;

                    $width = $dst_w;
                    $height = $dst_h;
                }
                else {
                    $result['error'] = new WP_Error( 'error_getting_dimensions', __('Could not calculate resized image dimensions') );
                }
            }
            else
            {
                $result['method'] = 'nextgen';
                $original_width = $dimensions[0];
                $original_height = $dimensions[1];
                $aspect_ratio = $width / $height;

                $orig_ratio_x = $original_width / $width;
                $orig_ratio_y = $original_height / $height;

                if ($crop)
                {
                    $algo = 'shrink'; // either 'adapt' or 'shrink'

                    if ($crop_frame != null)
                    {
                        $crop_x = (int) round($crop_frame['x']);
                        $crop_y = (int) round($crop_frame['y']);
                        $crop_width = (int) round($crop_frame['width']);
                        $crop_height = (int) round($crop_frame['height']);
                        $crop_final_width = (int) round($crop_frame['final_width']);
                        $crop_final_height = (int) round($crop_frame['final_height']);

                        $crop_width_orig = $crop_width;
                        $crop_height_orig = $crop_height;

                        $crop_factor_x = $crop_width / $crop_final_width;
                        $crop_factor_y = $crop_height / $crop_final_height;

                        $crop_ratio_x = $crop_width / $width;
                        $crop_ratio_y = $crop_height / $height;

                        if ($algo == 'adapt')
                        {
                            // XXX not sure about this...don't use for now
#							$crop_width = (int) round($width * $crop_factor_x);
#							$crop_height = (int) round($height * $crop_factor_y);
                        }
                        else if ($algo == 'shrink')
                        {
                            if ($crop_ratio_x < $crop_ratio_y)
                            {
                                $crop_width = max($crop_width, $width);
                                $crop_height = (int) round($crop_width / $aspect_ratio);
                            }
                            else
                            {
                                $crop_height = max($crop_height, $height);
                                $crop_width = (int) round($crop_height * $aspect_ratio);
                            }

                            if ($crop_width == ($crop_width_orig - 1))
                            {
                                $crop_width = $crop_width_orig;
                            }

                            if ($crop_height == ($crop_height_orig - 1))
                            {
                                $crop_height = $crop_height_orig;
                            }
                        }

                        $crop_diff_x = (int) round(($crop_width_orig - $crop_width) / 2);
                        $crop_diff_y = (int) round(($crop_height_orig - $crop_height) / 2);

                        $crop_x += $crop_diff_x;
                        $crop_y += $crop_diff_y;

                        $crop_max_x = ($crop_x + $crop_width);
                        $crop_max_y = ($crop_y + $crop_height);

                        // Check if we're overflowing borders
                        //
                        if ($crop_x < 0)
                        {
                            $crop_x = 0;
                        }
                        else if ($crop_max_x > $original_width)
                        {
                            $crop_x -= ($crop_max_x - $original_width);
                        }

                        if ($crop_y < 0)
                        {
                            $crop_y = 0;
                        }
                        else if ($crop_max_y > $original_height)
                        {
                            $crop_y -= ($crop_max_y - $original_height);
                        }
                    }
                    else
                    {
                        if ($orig_ratio_x < $orig_ratio_y)
                        {
                            $crop_width = $original_width;
                            $crop_height = (int) round($height * $orig_ratio_x);

                        }
                        else
                        {
                            $crop_height = $original_height;
                            $crop_width = (int) round($width * $orig_ratio_y);
                        }

                        if ($crop_width == ($width - 1))
                        {
                            $crop_width = $width;
                        }

                        if ($crop_height == ($height - 1))
                        {
                            $crop_height = $height;
                        }

                        $crop_x = (int) round(($original_width - $crop_width) / 2);
                        $crop_y = (int) round(($original_height - $crop_height) / 2);
                    }

                    $result['crop_area'] = array('x' => $crop_x, 'y' => $crop_y, 'width' => $crop_width, 'height' => $crop_height);
                }
                else {
                    // Just constraint dimensions to ensure there's no stretching or deformations
                    list($width, $height) = wp_constrain_dimensions($original_width, $original_height, $width, $height);
                }
            }

            $result['width'] = $width;
            $result['height'] = $height;
            $result['quality'] = $quality;

            $real_width = $width;
            $real_height = $height;

            if ($rotation && in_array(abs($rotation), array(90, 270)))
            {
                $real_width = $height;
                $real_height = $width;
            }

            if ($reflection)
            {
                // default for nextgen was 40%, this is used in generate_image_clone as well
                $reflection_amount = 40;
                // Note, round() would probably be best here but using the same code that C_NggLegacy_Thumbnail uses for compatibility
                $reflection_height = intval($real_height * ($reflection_amount / 100));
                $real_height = $real_height + $reflection_height;
            }

            $result['real_width'] = $real_width;
            $result['real_height'] = $real_height;
        }

        return $result;
    }

    /**
     * Generates a specific size for an image
     * @param int|object|C_Image $image
     * @param string $size
     * @param array|null $params (optional)
     * @param bool $skip_defaults (optional)
     * @return bool|object
     */
    function generate_image_size($image, $size, $params = null, $skip_defaults = false)
    {
        $retval = FALSE;

        // Get the image entity
        if (is_numeric($image)) {
            $image = $this->object->_image_mapper->find($image);
        }

        // Ensure we have a valid image
        if ($image)
        {
            $params = $this->object->get_image_size_params($image, $size, $params, $skip_defaults);
            $settings = C_NextGen_Settings::get_instance();

            // Get the image filename
            $filename = $this->object->get_image_abspath($image, 'original');
            $thumbnail = null;

            if ($size == 'full' && $settings->imgBackup == 1) {
                // XXX change this? 'full' should be the resized path and 'original' the _backup path
                $backup_path = $this->object->get_backup_abspath($image);

                if (!@file_exists($backup_path))
                {
                    @copy($filename, $backup_path);
                }
            }

            // Generate the thumbnail using WordPress
            $existing_image_abpath = $this->object->get_image_abspath($image, $size);
            $existing_image_dir = dirname($existing_image_abpath);

            wp_mkdir_p($existing_image_dir);

            $clone_path = $existing_image_abpath;
            $thumbnail = $this->object->generate_image_clone($filename, $clone_path, $params);

            // We successfully generated the thumbnail
            if ($thumbnail != null)
            {
                $clone_path = $thumbnail->fileName;

                if (function_exists('getimagesize'))
                {
                    $dimensions = getimagesize($clone_path);
                }
                else
                {
                    $dimensions = array($params['width'], $params['height']);
                }

                if (!isset($image->meta_data))
                {
                    $image->meta_data = array();
                }

                $size_meta = array(
                    'width'		=> $dimensions[0],
                    'height'	=> $dimensions[1],
                    'filename'	=> M_I18n::mb_basename($clone_path),
                    'generated'	=> microtime()
                );

                if (isset($params['crop_frame'])) {
                    $size_meta['crop_frame'] = $params['crop_frame'];
                }

                $image->meta_data[$size] = $size_meta;

                if ($size == 'full')
                {
                    $image->meta_data['width'] = $size_meta['width'];
                    $image->meta_data['height'] = $size_meta['height'];
                }

                $retval = $this->object->_image_mapper->save($image);

                do_action('ngg_generated_image', $image, $size, $params);

                if ($retval == 0) {
                    $retval = false;
                }

                if ($retval) {
                    $retval = $thumbnail;
                }
            }
            else {
                // Something went wrong. Thumbnail generation failed!
            }
        }

        return $retval;
    }

    function generate_resized_image($image, $save=TRUE)
    {
        $image_abspath = $this->object->get_image_abspath($image, 'full');

        $generated = $this->object->generate_image_clone(
            $image_abspath,
            $image_abspath,
            $this->object->get_image_size_params($image, 'full')
        );

        if ($generated && $save)
            $this->object->update_image_dimension_metadata($image, $image_abspath);

        if ($generated) $generated->destruct();
    }

    public function update_image_dimension_metadata($image, $image_abspath)
    {
        // Ensure that fullsize dimensions are added to metadata array
        $dimensions = getimagesize($image_abspath);
        $full_meta = array(
            'width'  => $dimensions[0],
            'height' => $dimensions[1],
            'md5'    => $this->object->get_image_checksum($image, 'full')
        );

        if (!isset($image->meta_data) OR (is_string($image->meta_data) && strlen($image->meta_data) == 0) OR is_bool($image->meta_data))
            $image->meta_data = array();

        $image->meta_data = array_merge($image->meta_data, $full_meta);
        $image->meta_data['full'] = $full_meta;

        // Don't forget to append the 'full' entry in meta_data in the db
        $this->object->_image_mapper->save($image);
    }

    /**
     * Most major browsers do not honor the Orientation meta found in EXIF. To prevent display issues we inspect
     * the EXIF data and rotate the image so that the EXIF field is not necessary to display the image correctly.
     * Note: generate_image_clone() will handle the removal of the Orientation tag inside the image EXIF.
     * Note: This only handles single-dimension rotation; at the time this method was written there are no known
     * camera manufacturers that both rotate and flip images.
     * @param $image
     * @param bool $save
     */
    public function correct_exif_rotation($image, $save = TRUE)
    {
        $image_abspath = $this->object->get_image_abspath($image, 'full');

        // This method is necessary
        if (!function_exists('exif_read_data'))
            return;

        // We only need to continue if the Orientation tag is set
        $exif = @exif_read_data($image_abspath, 'exif');
        if (empty($exif['Orientation']) || $exif['Orientation'] == 1)
            return;

        $degree = 0;
        if ($exif['Orientation'] == 3) $degree = 180;
        if ($exif['Orientation'] == 6) $degree = 90;
        if ($exif['Orientation'] == 8) $degree = 270;

        $parameters = array('rotation' => $degree);

        $generated = $this->object->generate_image_clone(
            $image_abspath,
            $image_abspath,
            $this->object->get_image_size_params($image, 'full', $parameters),
            $parameters
        );

        if ($generated && $save)
            $this->object->update_image_dimension_metadata($image, $image_abspath);

        if ($generated) $generated->destruct();
    }

    /**
     * Generates a thumbnail for an image
     * @param int|stdClass|C_Image $image
     * @return bool
     */
    function generate_thumbnail($image, $params = null, $skip_defaults = false)
    {
        $sized_image = $this->object->generate_image_size($image, 'thumbnail', $params, $skip_defaults);
        $retval = false;

        if ($sized_image != null)
        {
            $retval = true;

            $sized_image->destruct();
        }

        return $retval;
    }
}