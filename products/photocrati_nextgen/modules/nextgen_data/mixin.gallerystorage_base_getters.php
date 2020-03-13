<?php

class E_NggImageNotFound extends RuntimeException {};
class E_NggImageSizeNotFound extends RuntimeException {};
class E_NggCdnOutOfDate extends RuntimeException {};

/**
 * Provides getter methods to C_Gallery_Storage for determining absolute paths, URL, etc
 * @property C_Gallery_Storage $object
 */
class Mixin_GalleryStorage_Base_Getters extends Mixin
{
    static $gallery_abspath_cache   = array();
    static $image_abspath_cache     = array();
    static $image_url_cache         = array();

    /**
     * Flushes the cache we use for path/url calculation for images
     */
    function flush_image_path_cache($image, $size)
    {
        $image      = is_numeric($image) ? $image : $image->pid;
        $key        = strval($image).$size;

        unset(self::$image_abspath_cache[$key]);
        unset(self::$image_url_cache[$key]);
    }

    /**
     * Flushes the cache we use for path/url calculation for galleries
     */
    function flush_gallery_path_cache($gallery)
    {
        $gallery    = is_numeric($gallery) ? $gallery : $gallery->gid;
        unset(self::$gallery_abspath_cache[$gallery]);
    }

    /**
     * Gets the id of an image, regardless of whether an integer
     * or object was passed as an argument
     * @param object|int $image_obj_or_id
     * @return null|int
     */
    function _get_image_id($image_obj_or_id)
    {
        $retval = NULL;

        $image_key = $this->object->_image_mapper->get_primary_key_column();
        if (is_object($image_obj_or_id)) {
            if (isset($image_obj_or_id->$image_key)) {
                $retval = $image_obj_or_id->$image_key;
            }
        }
        elseif (is_numeric($image_obj_or_id)) {
            $retval = $image_obj_or_id;
        }

        return $retval;
    }

    /**
     * Gets the absolute path of the backup of an original image
     * @param string $image
     * @return null|string
     */
    function get_backup_abspath($image) {
        $retval = null;

        if ( ( $image_path = $this->object->get_image_abspath( $image ) ) ) {
            $retval = $image_path . '_backup';
        }

        return $retval;
    }

    function get_backup_dimensions($image)
    {
        return $this->object->get_image_dimensions($image, 'backup');
    }

    function get_backup_url($image)
    {
        return $this->object->get_image_url($image, 'backup');
    }

    /**
     * Returns the absolute path to the cache directory of a gallery.
     *
     * Without the gallery parameter the legacy (pre 2.0) shared directory is returned.
     *
     * @param int|object|false|C_Gallery $gallery (optional)
     * @return string Absolute path to cache directory
     */
    function get_cache_abspath($gallery = FALSE)
    {
        return path_join($this->object->get_gallery_abspath($gallery), 'cache');
    }

    /**
     * Gets the absolute path where the full-sized image is stored
     * @param int|object $image
     * @return null|string
     */
    function get_full_abspath($image)
    {
        return $this->object->get_image_abspath($image, 'full');
    }

    /**
     * Alias to get_image_dimensions()
     * @param int|object $image
     * @return array
     */
    function get_full_dimensions($image)
    {
        return $this->object->get_image_dimensions($image, 'full');
    }

    /**
     * Alias to get_image_html()
     * @param int|object $image
     * @return string
     */
    function get_full_html($image)
    {
        return $this->object->get_image_html($image, 'full');
    }

    /**
     * Alias for get_original_url()
     *
     * @param int|stdClass|C_Image $image
     * @return string
     */
    function get_full_url($image)
    {
        return $this->object->get_image_url($image, 'full');
    }

    function get_gallery_root()
    {
        return wp_normalize_path(NGG_GALLERY_ROOT_TYPE == 'content' ? WP_CONTENT_DIR : ABSPATH);
    }

    function _get_computed_gallery_abspath($gallery)
    {
        $retval         = NULL;
        $gallery_root   = $this->get_gallery_root();
        
        // Get the gallery entity from the database
        if ($gallery) {
            if (is_numeric($gallery)) {
                $gallery = $this->object->_gallery_mapper->find($gallery);
            }
        }

        // It just doesn't exist
        if (!$gallery) return $retval;

        // We we have a gallery, determine it's path
        if ($gallery) {
            if (isset($gallery->path)) {
                $retval = $gallery->path;
            }
            elseif (isset($gallery->slug)) {
                $basepath = wp_normalize_path(C_NextGen_Settings::get_instance()->gallerypath);
                $retval = path_join($basepath, $this->object->sanitize_directory_name(sanitize_title($gallery->slug)));
            }

            // Normalize the gallery path. If the gallery path starts with /wp-content, and 
            // NGG_GALLERY_ROOT_TYPE is set to 'content', then we need to strip out the /wp-content
            // from the start of the gallery path
            if (NGG_GALLERY_ROOT_TYPE === 'content') {
                $retval = preg_replace("#^/?wp-content#", "", $retval);
            }

            // Ensure that the path is absolute
            if (strpos($retval, $gallery_root) !== 0) {

                // path_join() behaves funny - if the second argument starts with a slash,
                // it won't join the two paths together
                $retval = preg_replace("#^/#", "", $retval);
                $retval = path_join($gallery_root, $retval);
            }

            $retval = wp_normalize_path($retval);
        }

        return $retval;
    }

    /**
     * Get the abspath to the gallery folder for the given gallery
     * The gallery may or may not already be persisted
     * @param int|object|C_Gallery $gallery
     * @return string
     */
    function get_gallery_abspath($gallery)
    {
        $gallery_id = is_numeric($gallery) ? $gallery : (is_object($gallery) && isset($gallery->gid) ? $gallery->gid : NULL);

        if (!$gallery_id || !isset(self::$gallery_abspath_cache[$gallery_id])) {
            self::$gallery_abspath_cache[$gallery_id] = $this->object->_get_computed_gallery_abspath($gallery);
        }
        
        return self::$gallery_abspath_cache[$gallery_id];
    }


    function get_gallery_relpath($gallery)
    {
        return str_replace($this->object->get_gallery_root(), '', $this->get_gallery_abspath($gallery));
    }


     /**
     * Gets the absolute path where the image is stored. Can optionally return the path for a particular sized image.
     * @param int|object $image
     * @param string $size (optional) Default = full
     * @return string
     */
    function _get_computed_image_abspath($image, $size='full', $check_existance=FALSE)
    {
        $retval = NULL;
        $fs     = C_Fs::get_instance();

        // If we have the id, get the actual image entity
        if (is_numeric($image)) {
            $image = $this->object->_image_mapper->find($image);
        }

        // Ensure we have the image entity - user could have passed in an
        // incorrect id
        if (is_object($image)) {
            if (($gallery_path = $this->object->get_gallery_abspath($image->galleryid))) {
                $folder = $prefix = $size;
                switch ($size) {

                # Images are stored in the associated gallery folder
                case 'full':
                    $retval = path_join($gallery_path, $image->filename);
                    break;

                case 'backup':
                    $retval = path_join($gallery_path, $image->filename.'_backup');
                    if (!@file_exists($retval)) {
                        $retval = path_join($gallery_path, $image->filename);
                    }
                    break;

                case 'thumbnail':
                    $size = 'thumbnail';
                    $folder = 'thumbs';
                    $prefix = 'thumbs';
                    // deliberately no break here

                default:
                    // NGG 2.0 stores relative filenames in the meta data of
                    // an image. It does this because it uses filenames
                    // that follow conventional WordPress naming scheme.
                    $image_path = NULL;
                    $dynthumbs  = C_Dynamic_Thumbnails_Manager::get_instance();
                    if (isset($image->meta_data) && isset($image->meta_data[$size]) && isset($image->meta_data[$size]['filename'])) {
                        if ($dynthumbs && $dynthumbs->is_size_dynamic($size)) {
                            $image_path = path_join($this->object->get_cache_abspath($image->galleryid), $image->meta_data[$size]['filename']);
                        } else {
                            $image_path = path_join($gallery_path, $folder);
                            $image_path = path_join($image_path, $image->meta_data[$size]['filename']);
                        }
                    }

                    // Filename not found in meta, but is dynamic
                    else if ($dynthumbs && $dynthumbs->is_size_dynamic($size)) {
                            $params = $dynthumbs->get_params_from_name($size, true);
                            $image_path = path_join($this->object->get_cache_abspath($image->galleryid), $dynthumbs->get_image_name($image, $params));
                    
                    // Filename is not found in meta, nor dynamic        
                    } else {
                        $image_path = path_join($gallery_path, $folder);
                        $image_path = path_join($image_path, "{$prefix}_{$image->filename}");
                    }

                    $retval = $image_path;
                    break;
                }
            }
        }
        if ($retval && $check_existance && !@file_exists($retval)) $retval = NULL;
        return $retval;
    }

    /**
     * Gets the absolute path where the image is stored. Can optionally return the path for a particular sized image.
     * @param int|object $image
     * @param string $size (optional) Default = full
     * @param bool $check_existance (optional) Default = false
     * @return string
     */
    function get_image_abspath($image, $size='full', $check_existance=FALSE)
    {
        $image_id       = is_numeric($image) ? $image : $image->pid;
        $size           = $this->object->normalize_image_size_name($size);
        $key            = strval($image_id).$size;
        
        if ($check_existance || !isset(self::$image_abspath_cache[$key])) {
            $retval         = $this->object->_get_computed_image_abspath($image, $size, $check_existance);
            self::$image_abspath_cache[$key]    = $retval;
        }
        $retval         = self::$image_abspath_cache[$key];

        return $retval;
    }

    function get_image_checksum($image, $size='full')
    {
        $retval = NULL;
        if (($image_abspath = $this->get_image_abspath($image, $size, TRUE))) {
            $retval = md5_file($image_abspath);
        }
        return $retval;
    }

    /**
     * Gets the dimensions for a particular-sized image
     *
     * @param int|object $image
     * @param string $size
     * @return null|array
     */
    function get_image_dimensions($image, $size='full')
    {
        $retval = NULL;

        // If an image id was provided, get the entity
        if (is_numeric($image)) $image = $this->object->_image_mapper->find($image);

        // Ensure we have a valid image
        if ($image) {

            $size = $this->normalize_image_size_name($size);
            if (!$size) $size = 'full';

            // Image dimensions are stored in the $image->meta_data
            // property for all implementations
            if (isset($image->meta_data) && isset($image->meta_data[$size])) {
                $retval = $image->meta_data[$size];
            }

            // Didn't exist for meta data. We'll have to compute
            // dimensions in the meta_data after computing? This is most likely
            // due to a dynamic image size being calculated for the first time
            else {
                $dynthumbs = C_Dynamic_Thumbnails_Manager::get_instance();
                $abspath = $this->object->get_image_abspath($image, $size, TRUE);
                if ($abspath)
                {
                    $dims = @getimagesize($abspath);
                    if ($dims) {
                        $retval['width']	= $dims[0];
                        $retval['height']	= $dims[1];
                    }
                }
                elseif ($size == 'backup') {
                    $retval = $this->object->get_image_dimensions($image, 'full');
                }
                
                if (!$retval && $dynthumbs && $dynthumbs->is_size_dynamic($size))
                {
                    $new_dims = $this->object->calculate_image_size_dimensions($image, $size);
                    $retval = array('width' => $new_dims['real_width'], 'height' => $new_dims['real_height']);
                }
            }
        }

        return $retval;
    }

    function get_image_format_list()
    {
        $format_list = array(IMAGETYPE_GIF => 'gif', IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png');

        return $format_list;
    }

    /**
     * Gets the HTML for an image
     * @param int|object $image
     * @param string $size
     * @param array $attributes (optional)
     * @return string
     */
    function get_image_html($image, $size='full', $attributes=array())
    {
        $retval = "";

        if (is_numeric($image)) $image = $this->object->_image_mapper->find($image);

        if ($image) {

            // Set alt text if not already specified
            if (!isset($attributes['alttext'])) {
                $attributes['alt'] = esc_attr($image->alttext);
            }

            // Set the title if not already set
            if (!isset($attributes['title'])) {
                $attributes['title'] = esc_attr($image->alttext);
            }

            // Set the dimensions if not set already
            if (!isset($attributes['width']) OR !isset($attributes['height'])) {
                $dimensions = $this->object->get_image_dimensions($image, $size);
                if (!isset($attributes['width'])) {
                    $attributes['width'] = $dimensions['width'];
                }
                if (!isset($attributes['height'])) {
                    $attributes['height'] = $dimensions['height'];
                }
            }

            // Set the url if not already specified
            if (!isset($attributes['src'])) {
                $attributes['src'] = $this->object->get_image_url($image, $size);
            }

            // Format attributes
            $attribs = array();
            foreach ($attributes as $attrib => $value) $attribs[] = "{$attrib}=\"{$value}\"";
            $attribs = implode(" ", $attribs);

            // Return HTML string
            $retval = "<img {$attribs} />";
        }

        return $retval;
    }

    /**
     * Gets data stored about CDN storage for the image
     *
     * @param stdClass|number $image
     * @param string $size
     * @return null|array
     */
    function get_cdn_data($image, $size='full', $cdn_key=NULL)
    {
        try {
            if (!$cdn_key)
                $cdn_key = C_CDN_Providers::is_cdn_configured();
            if (!$cdn_key)
                return NULL;
            $meta_data = $this->get_stored_image_size_metadata($image, $size);
            return isset($meta_data[$cdn_key]) ? $meta_data[$cdn_key] : NULL;
        }
        catch (E_NggImageNotFound $ex) {
            return NULL;
        }
        catch (E_NggImageSizeNotFound $ex) {
            return NULL;
        }
    }

    /**
     * Returns stored image metadata for a particular image size
     *
     * @param int|Object|C_Image $image
     * @param string $size
     * @return array
     * @throws E_NggImageNotFound
     * @throws E_NggImageSizeNotFound
     */
    function get_stored_image_size_metadata($image, $size='full')
    {
        $size     = $this->normalize_image_size_name($size);
        $image    = $this->_get_image_entity_object($image);
        $image_id = $image->pid;

        if ($size === 'full')
            return $image->meta_data;

        if (!isset($image->meta_data[$size]))
            throw new E_NggImageSizeNotFound(
                sprintf(__("Could not find image #%d's metadata for the \"%s\" named size", 'nggallery'), $image_id, $size)
            );

        return $image->meta_data[$size];
    }

    /**
     * Gets the image entity for an image parameter supplied. The image parameter could be a stdClass, C_Image, or int
     * @param C_Image|Object|int $image
     * @return Object
     * 
     * @throws E_NggImageNotFound
     */
    function _get_image_entity_object($image)
    {
        $image_obj  = is_object($image) ? $image : NULL;
        $image_id   = is_object($image) && isset($image->pid) ? $image->pid : $image;

        if (!$image_obj && is_numeric($image_id)) $image_obj = $this->_image_mapper->find($image_id);
        if (!$image_obj) throw new E_NggImageNotFound(($image_id ? "Could not find image {$image_id}" : "Could not find specified image"));

        return $image_obj;
    }

    /**
     * Updates the meta data stored for a particular image size
     *
     * @param C_Image|Object|int $image
     * @param array $updates
     * @param string $size
     * @return bool
     * @throws E_NggImageNotFound
     * @throws E_NggImageSizeNotFound
     */
    function update_stored_image_meta_data($image, $updates = [], $size = 'full')
    {
        $image = $this->_get_image_entity_object($image);
        $size  = $this->normalize_image_size_name($size);

        $meta_data = array_merge(
            $this->get_stored_image_size_metadata($image, $size),
            $updates
        );

        if ($size == 'full')
            $image->meta_data = $meta_data;
        else
            $image->meta_data[$size]= $meta_data;

        return $this->_image_mapper->save($image);
    }

    /**
     * Gets the latest timestamp when an image size was generated
     *
     * @param C_Image|Object|int $image
     * @param string $size
     * @return number
     */
    function get_latest_image_timestamp($image, $size = 'full')
    {
        try {
            $meta_data = $this->get_stored_image_size_metadata($image, $size);
            return isset($meta_data['generated']) ? $meta_data['generated'] : time();
        }
        catch (E_NggImageSizeNotFound $ex) {
            return FALSE;
        }
        catch (E_NggImageSizeNotFound $ex) {
            return FALSE;
        }
    }

    /**
     * Deteremines if a given image size has been published to the CDN
     * @param C_Image|Object|int $image
     * @param string $size
     * @return bool
     * 
     * @throws E_NggCdnOutOfDate
     */
    function is_on_cdn($image, $size='full')
    {
        $timestamp = $this->get_latest_image_timestamp($image, $size);
        $cdn_data = $this->get_cdn_data($image, $size);
        if ($cdn_data) {
            $image_id = $this->_get_image_id($image);
            if ($timestamp > $cdn_data['version']) {
                throw new E_NggCdnOutOfDate("A CDN version of the \"{$size}\" named size exists on the CDN for image {$image_id} but its out-of-date");
            }
            return TRUE;
        }

        return FALSE;
    }

    /**
     * Gets the CDN url for a particular image size
     *
     * @param C_Image|Object|int $image
     * @param string $size
     * @return string|NULL
     */
    function get_cdn_url_for($image, $size='full', $cdn_key = NULL)
    {
        if (!$cdn_key)
            $cdn_key = C_CDN_Providers::is_cdn_configured();
        if (!$cdn_key)
            return NULL;

        if (($data = $this->get_cdn_data($image, $size, $cdn_key)))
            return $data['public_url'];

        return NULL;
    }

    /**
     * Stores data about the CDN version of the image
     *
     * @param C_Image|Object|int $image
     * @param number $timestamp
     * @param string $public_url
     * @param array $data
     * @param string $size optional. Defaults to "full"
     * @param string $cdn_key optional. Defaults to current configured CDN
     * @return bool
     */
    function update_cdn_data($image, $timestamp, $public_url, $data, $size = 'full', $cdn_key = NULL)
    {
        if ($cdn_key == NULL)
            $cdn_key = C_CDN_Providers::is_cdn_configured();
        if (!$cdn_key)
            return FALSE;

        $data['version']    = $timestamp;
        $data['public_url'] = $public_url;

        $image = $this->_get_image_entity_object($image);
        $size  = $this->normalize_image_size_name($size);

        $cdn_data = $this->get_cdn_data($image, $size, $cdn_key);
        $cdn_data = $cdn_data ? array_merge($cdn_data, $data) : $data;

        $meta_data = $this->get_stored_image_size_metadata($image, $size);
        $meta_data[$cdn_key] = $cdn_data;

        return $this->update_stored_image_meta_data($image, $meta_data, $size);
    }

    /**
     * Gets the computed image url for an image. Not cached.
     * @param C_Image|Object|int $image
     * @param string $size
     * @return string|NULL
     */
    function _get_computed_image_url($image, $size='full')
    {
        $retval     = NULL;
        $dynthumbs  = C_Dynamic_Thumbnails_Manager::get_instance();

        $cdn_url = $this->get_cdn_url_for($image, $size);
        if($cdn_url) return $cdn_url;
        
        // Get the image abspath
        $image_abspath = $this->object->get_image_abspath($image, $size);
        if ($dynthumbs->is_size_dynamic($size) && !file_exists($image_abspath)){
            if (defined('NGG_DISABLE_DYNAMIC_IMG_URLS') && constant('NGG_DISABLE_DYNAMIC_IMG_URLS')) {
                $params = array('watermark' => false, 'reflection' => false, 'crop' => true);
                $result = $this->generate_image_size($image, $size, $params);
                if ($result) $image_abspath = $this->object->get_image_abspath($image, $size);
            }
            else return NULL;
        }

        // Assuming we have an abspath, we can translate that to a url
        if ($image_abspath) {

            // Replace the gallery root with the proper url segment
            $gallery_root = preg_quote($this->get_gallery_root(), '#');
            $image_uri = preg_replace(
                "#^{$gallery_root}#",
                "",
                $image_abspath 
            );

            // Url encode each uri segment
            $segments = explode("/", $image_uri);
            $segments = array_map('rawurlencode', $segments);
            $image_uri = preg_replace("#^/#", "", implode("/", $segments));
            
            // Join gallery root and image uri
            $gallery_root = trailingslashit(NGG_GALLERY_ROOT_TYPE == 'site' ? site_url() : WP_CONTENT_URL);
            $gallery_root = is_ssl() ? str_replace('http:', 'https:', $gallery_root) : $gallery_root;
            $retval = $gallery_root . $image_uri;
        }

        return $retval;
    }

    function normalize_image_size_name($size = 'full')
    {
        switch($size) {
            case 'full':
            case 'original':
            case 'image':
            case 'orig':
            case 'resized':
                $size = 'full';           
                break;
            case 'thumbnails':
            case 'thumbnail':
            case 'thumb':
            case 'thumbs':
                $size = 'thumbnail';
                break;    
        }
        return $size;
    }

    /**
     * Gets the url of a particular-sized image
     * @param int|object $image
     * @param string $size
     * @return string
     */
    function get_image_url($image, $size='full')
    {
        $retval         = NULL;
        $image_id       = is_numeric($image) ? $image : $image->pid;
        $key            = strval($image_id).$size;
        $success        = TRUE;

        if (!isset(self::$image_url_cache[$key])) {
            $url = $this->object->_get_computed_image_url($image, $size);
            if ($url) {
                self::$image_url_cache[$key] = $url;
                $success = TRUE;
            }
            else $success = FALSE;
        }
        if ($success)
            $retval = self::$image_url_cache[$key];
        else {
            $dynthumbs = C_Dynamic_Thumbnails_Manager::get_instance();
            if ($dynthumbs->is_size_dynamic($size)) {
                $params = $dynthumbs->get_params_from_name($size);
                $retval = $dynthumbs->get_image_url($image, $params);
            }
        }
        
        return apply_filters('ngg_get_image_url', $retval, $image, $size);
    }

    /**
     * Returns the named sizes available for images
     * @return array
     */
    function get_image_sizes($image=FALSE)
    {
        $retval = array('full', 'thumbnail');

        if (is_numeric($image)) $image = C_Image_Mapper::get_instance()->find($image);

        if ($image)
        {
            if ($image->meta_data)
            {
                $meta_data = is_object($image->meta_data) ? get_object_vars($image->meta_data) : $image->meta_data;
                foreach ($meta_data as $key => $value) {
                    if (is_array($value) && isset($value['width']) && !in_array($key, $retval))
                    {
                        $retval[] = $key;
                    }
                }
            }
        }

        return $retval;
    }

    function get_image_size_params($image, $size, $params = array(), $skip_defaults = false)
    {
        // Get the image entity
        if (is_numeric($image)) {
            $image = $this->object->_image_mapper->find($image);
        }

        $dynthumbs = C_Dynamic_Thumbnails_Manager::get_instance();
        if ($dynthumbs && $dynthumbs->is_size_dynamic($size)) {
            $named_params = $dynthumbs->get_params_from_name($size, true);
            if (!$params) $params = array();
            $params = array_merge($params, $named_params);
        }

        $params = apply_filters('ngg_get_image_size_params', $params, $size, $image);

        // Ensure we have a valid image
        if ($image)
        {
            $settings = C_NextGen_Settings::get_instance();

            if (!$skip_defaults)
            {
                // Get default settings
                if ($size == 'full') {
                    if (!isset($params['quality'])) {
                        $params['quality'] = $settings->imgQuality;
                    }
                }
                else {
                    if (!isset($params['crop'])) {
                        $params['crop'] = $settings->thumbfix;
                    }

                    if (!isset($params['quality'])) {
                        $params['quality'] = $settings->thumbquality;
                    }
                }
            }

            // width and height when omitted make generate_image_clone create a clone with original size, so try find defaults regardless of $skip_defaults
            if (!isset($params['width']) || !isset($params['height'])) {

                // First test if this is a "known" image size, i.e. if we store these sizes somewhere when users re-generate these sizes from the UI...this is required to be compatible with legacy
                // try the 2 default built-in sizes, first thumbnail...
                if ($size == 'thumbnail') {
                    if (!isset($params['width'])) {
                        $params['width'] = $settings->thumbwidth;
                    }

                    if (!isset($params['height'])) {
                        $params['height'] = $settings->thumbheight;
                    }
                }
                // ...and then full, which is the size specified in the global resize options
                else if ($size == 'full') {
                    if (!isset($params['width'])) {
                        if ($settings->imgAutoResize) {
                            $params['width'] = $settings->imgWidth;
                        }
                    }

                    if (!isset($params['height'])) {
                        if ($settings->imgAutoResize) {
                            $params['height'] = $settings->imgHeight;
                        }
                    }
                }
                // Only re-use old sizes as last resort
                else if (isset($image->meta_data) && isset($image->meta_data[$size])) {
                    $dimensions = $image->meta_data[$size];

                    if (!isset($params['width'])) {
                        $params['width'] = $dimensions['width'];
                    }

                    if (!isset($params['height'])) {
                        $params['height'] = $dimensions['height'];
                    }
                }
            }

            if (!isset($params['crop_frame'])) {
                $crop_frame_size_name = 'thumbnail';

                if (isset($image->meta_data[$size]['crop_frame'])) {
                    $crop_frame_size_name = $size;
                }

                if (isset($image->meta_data[$crop_frame_size_name]['crop_frame'])) {
                    $params['crop_frame'] = $image->meta_data[$crop_frame_size_name]['crop_frame'];

                    if (!isset($params['crop_frame']['final_width'])) {
                        $params['crop_frame']['final_width'] = $image->meta_data[$crop_frame_size_name]['width'];
                    }

                    if (!isset($params['crop_frame']['final_height'])) {
                        $params['crop_frame']['final_height'] = $image->meta_data[$crop_frame_size_name]['height'];
                    }
                }
            }
            else {
                if (!isset($params['crop_frame']['final_width'])) {
                    $params['crop_frame']['final_width'] = $params['width'];
                }

                if (!isset($params['crop_frame']['final_height'])) {
                    $params['crop_frame']['final_height'] = $params['height'];
                }
            }
        }

        return $params;
    }

    /**
     * An alias for get_full_abspath()
     * @param int|object $image
     * @param bool $check_existance
     * @return null|string
     */
    function get_original_abspath($image, $check_existance=FALSE)
    {
        return $this->object->get_image_abspath($image, 'full', $check_existance);
    }

    /**
     * Alias to get_image_dimensions()
     * @param int|object $image
     * @return array
     */
    function get_original_dimensions($image)
    {
        return $this->object->get_image_dimensions($image, 'full');
    }

    /**
     * Alias to get_image_html()
     * @param int|object $image
     * @return string
     */
    function get_original_html($image)
    {
        return $this->object->get_image_html($image, 'full');
    }

    /**
     * Gets the url to the original-sized image
     * @param int|stdClass|C_Image $image
     * @param bool $check_existance (optional)
     * @return string
     */
    function get_original_url($image, $check_existance=FALSE)
    {
        return $this->object->get_image_url($image, 'full', $check_existance);
    }

    /**
     * @param object|bool $gallery (optional)
     * @return string
     */
    function get_upload_abspath($gallery=FALSE)
    {
        // Base upload path
        $retval = C_NextGen_Settings::get_instance()->gallerypath;
        $fs     = C_Fs::get_instance();

        // If a gallery has been specified, then we'll
        // append the slug
        if ($gallery) $retval = $this->get_gallery_abspath($gallery);

        // We need to make this an absolute path
        if (strpos($retval, $fs->get_document_root('gallery')) !== 0)
            $retval = rtrim($fs->join_paths($fs->get_document_root('gallery'), $retval), "/\\");

        // Convert slashes
        return wp_normalize_path($retval);
    }

    /**
     * Gets the upload path, optionally for a particular gallery
     * @param int|C_Gallery|object|false $gallery (optional)
     * @return string
     */
    function get_upload_relpath($gallery=FALSE)
    {
        $fs = C_Fs::get_instance();

        $retval = str_replace(
            $fs->get_document_root('gallery'),
            '',
            $this->object->get_upload_abspath($gallery)
        );

        return '/'.wp_normalize_path(ltrim($retval, "/"));
    }
}