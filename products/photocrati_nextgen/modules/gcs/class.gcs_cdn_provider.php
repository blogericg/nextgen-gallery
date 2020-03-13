<?php

use \Google\Cloud\Storage\StorageClient;

class C_GCS_CDN_Provider extends C_CDN_Provider
{
    /**
     * Determines whether the provider has been configured or not
     *
     * @return bool
     */
    function is_configured()
    {
        $config = $this->get_config();
        return isset($config['keyFile']) && isset($config['bucket']);
    }

    /**
     * Gets the configuration for the provider
     *
     * @return array
     */
    function get_config()
    {
        // Once we have a UI for providing the service account, we'll fetch from C_NextGen_Settings.
        // For now we will just get the service account from a constant
        if (defined('NGG_GCS_SERVICE_ACCOUNT') && defined('NGG_GCS_BUCKET'))
            return [
                'keyFile' => json_decode(constant('NGG_GCS_SERVICE_ACCOUNT'), true),
                'bucket'  => constant('NGG_GCS_BUCKET')
            ];

        return [];
    }

    public function copy($image_id, $gallery_id)
    {
        // TODO: Implement this feature
    }

    public function move($image_id, $gallery_id)
    {
        // TODO: Implement this feature
    }

    function get_bucket_name()
    {
        $config = $this->get_config();
        return isset($config['bucket']) ? $config['bucket'] : '';
    }

    function get_service_account()
    {
        $config = $this->get_config();
        return isset($config['keyFile']) ? $config['keyFile'] : '';
    }

    /**
     * Gets the key used to identify the provider
     *
     * @return string
     */
    function get_key()
    {
        return 'gcs';
    }

    /**
     * @param int|C_Image|stdClass $image
     * @param string $size
     * @param int $time
     * @return string
     */
    public function get_image_name($image, $size, $time = NULL)
    {
        if (is_numeric($image))
            $image = C_Image_Mapper::get_instance()->find($image);

        if (!$time)
            $time = time();

        $filename = basename(C_Gallery_Storage::get_instance()->get_image_abspath($image, $size));

        return $image->galleryid . '/' . $image->pid . '/' . $time . '--' . $filename;
    }
    
    /**
     * Uploads an image to the CDN
     *
     * @param int|C_Image|stdClass $image
     * @param string $size
     * @return bool
     */
    function upload($image, $size = 'full')
    {
        if (!$this->is_configured())
            throw new E_NggCdnUnconfigured(__("GCS has not been configured yet", 'nggallery'));

        $mapper  = C_Image_Mapper::get_instance();
        $storage = C_Gallery_Storage::get_instance();
        $gcs     = new StorageClient($this->get_config());
        $bucket  = $gcs->bucket($this->get_bucket_name());
        $version = $storage->get_latest_image_timestamp($image, $size);

        // Prevent cache issues from getting in the way of the changes we are about to save
        $original_cache = $mapper->_use_cache;
        $mapper->_use_cache = FALSE;

        if (is_numeric($image))
            $image  = $mapper->find($image);

        $name = $this->get_image_name($image, $size);

        $obj = $bucket->upload(
            fopen($storage->get_image_abspath($image, $size), 'r'),
            [
                'name'          => $name,
                'predefinedAcl' => 'publicRead',
                'metadata'      => [
                    'version' => $version
                ]
            ]
        );

        $old_name = NULL;
        if (isset($image->meta_data[$size]['gcs']['name']))
            $old_name = $image->meta_data[$size]['gcs']['name'];
        if ($size === 'full' && isset($image->meta_data['gcs']['name']))
            $old_name = $image->meta_data['gcs']['name'];
        if ($old_name)
        {
            \ReactrIO\Background\Job::create(
                sprintf(__("Removing old version of image %d with size %s and name %s", 'nextgen-gallery'), $image->pid, $size, $old_name),
                'cdn_gcs_delete_version',
                $old_name
            )->save('cdn');
        }

        $data = $obj->info();

        $retval = $storage->update_cdn_data($image, $version, $data['mediaLink'], $data, $size, $this->get_key());

        $mapper->_use_cache = $original_cache;

        return $retval;
    }

    function delete_version($name)
    {
        if (!$this->is_configured())
            throw new E_NggCdnUnconfigured(__("GCS has not been configured yet", 'nggallery'));

        try {
            $gcs    = new StorageClient($this->get_config());
            $bucket = $gcs->bucket($this->get_bucket_name());

            $object = $bucket->object($name);
            $object->delete();

            return TRUE;
        }
        catch (\Google\Cloud\Core\Exception\NotFoundException $exception) {
            return FALSE;
        }
    }

    /**
     * Deletes an image from the CDN
     *
     * @param int|C_Image|stdClass $image
     * @param string $size
     * @return bool
     */
    function delete($image, $size = 'full')
    {
        if (!$this->is_configured())
            throw new E_NggCdnUnconfigured(__("GCS has not been configured yet", 'nggallery'));

        try {
            $gcs    = new StorageClient($this->get_config());
            $bucket = $gcs->bucket($this->get_bucket_name());

            $name = $this->get_image_name($image, $size);

            $object = $bucket->object($name);
            $object->delete();

            return TRUE;
        }
        catch (\Google\Cloud\Core\Exception\NotFoundException $exception) {
            return FALSE;
        }
    }

    /**
     * Downloads the image
     *
     * @param string $image
     * @param string $size
     * @return string
     */
    function download($image, $size = 'full')
    {   
        $storage = C_Gallery_Storage::get_instance();

        try {
            $is_on_cdn = $storage->is_on_cdn($image, $size);
        }
        catch (E_NggCdnOutOfDate $ex) {
            $is_on_cdn = FALSE;
        }

        if ($is_on_cdn && $this->is_offload_enabled())
        {
            $image_url = $storage->get_image_url($image, $size);

            if (!function_exists('download_url'))
                require_once(ABSPATH.'/wp-admin/includes/file.php');

            $filename = download_url($image_url);

            // TODO: verify $image type
            if (is_wp_error($filename))
            {
                throw new RuntimeException(
                    sprintf(
                        __("Could not download image #%d '%s' sized image: %s", 'nggallery'),
                        $image,
                        $size,
                        $image_url
                    )
                );
            }

            $image_abspath = $storage->get_image_abspath($image, $size);
            move_uploaded_file($filename, $image_abspath);
            return $image_abspath;
        }

        return $storage->get_image_abspath($image, $size);
    }
}