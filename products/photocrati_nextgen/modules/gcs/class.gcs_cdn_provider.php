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
                'bucket'  => constant('NGG_GCS_BUCKET'),
                'offload' => TRUE
            ];

        return [];
    }

    public function copy($image_id, $gallery_id)
    {
        $storage = C_Gallery_Storage::get_instance();
        $new_image_id = $storage->copy_image($image_id, $gallery_id);

        \ReactrIO\Background\Job::create(
            sprintf(__("Publishing newly copied image %d to gallery %d", 'nextgen-gallery'), $new_image_id, $gallery_id),
            'cdn_publish_image',
            ['id' => $new_image_id, 'size' => 'all']
        )->save('cdn');

        // Cleanup any files brought to the server for copy_image() to function. The method move() creates a new task
        // to delete the original image and does not require this same treatment.
        if ($this->is_offload_enabled() && $storage->is_on_cdn($image_id))
        {
            $sizes = array_merge(['backup'], $storage->get_image_sizes());
            foreach ($sizes as $size) {
                unlink($storage->get_image_abspath($image_id, $size));
            }
        }
    }

    public function move($image_id, $gallery_id)
    {
        // The gallery storage's move_image() method will delete the image which we don't want to perform just yet
        $new_image_id = C_Gallery_Storage::get_instance()->copy_image($image_id, $gallery_id);

        // Now we create a job to remove the original file
        \ReactrIO\Background\Job::create(
            sprintf(__("Removing original files of moved image #%d", 'nggallery'), $image_id),
            'cdn_delete_image',
            ['id' => $image_id, 'size' => 'all']
        )->save('cdn');

        // And last we publish the new image
        \ReactrIO\Background\Job::create(
            sprintf(__("Publishing newly moved image #%d", 'nggallery'), $new_image_id),
            'cdn_publish_image',
            ['id' => $new_image_id, 'size' => 'all']
        )->save('cdn');
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
    public function get_new_image_name($image, $size, $time = NULL)
    {
        if (is_numeric($image))
            $image = C_Image_Mapper::get_instance()->find($image);

        if (!$time)
            $time = time();

        $filename = basename(C_Gallery_Storage::get_instance()->get_image_abspath($image, $size));

        $dir = $image->galleryid . '/' . $image->pid . '/';

        if (C_Dynamic_Thumbnails_Manager::get_instance()->is_size_dynamic($size))
            $dir .= 'dynamic/';

        return $dir . $time . '--' . $filename;
    }

    public function get_current_image_name($image, $size)
    {
        if (is_numeric($image))
        {
            $mapper = C_Image_Mapper::get_instance();
            $image  = $mapper->find($image);
            if (!$image)
                return;
        }

        $old_name = NULL;
        if (isset($image->meta_data[$size]['gcs']['name']))
            $old_name = $image->meta_data[$size]['gcs']['name'];
        if ($size === 'full' && isset($image->meta_data['gcs']['name']))
            $old_name = $image->meta_data['gcs']['name'];

        return $old_name;
    }

    /**
     * Flushes all dynamically generated images
     *
     * @param C_Image|stdClass|int $image
     * @throws E_NggCdnUnconfigured
     */
    function flush($image)
    {
        if (!$this->is_configured())
            throw new E_NggCdnUnconfigured(__("GCS has not been configured yet", 'nggallery'));

        $gcs    = new StorageClient($this->get_config());
        $bucket = $gcs->bucket($this->get_bucket_name());

        if (is_numeric($image))
            $image = C_Image_Mapper::get_instance()->find($image);

        $dir = $image->galleryid . '/' . $image->pid . '/dynamic/';

        foreach ($bucket->objects(['prefix' => $dir]) as $object) {
            \ReactrIO\Background\Job::create(
                sprintf(__("Flushing dynamic image %s for image %d", 'nextgen-gallery'), $object->name(), $image->pid),
                'cdn_delete_image',
                ['id' => $image->pid, 'size' => $object->name()]
            )->save('cdn');
        }
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

        $name = $this->get_new_image_name($image, $size);

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

        $old_name = $this->get_current_image_name($image, $size);
        if ($old_name)
        {
            \ReactrIO\Background\Job::create(
                sprintf(__("Removing old version of image %d with name %s", 'nextgen-gallery'), $image->pid, $old_name),
                'cdn_gcs_delete_version',
                $old_name
            )->save('cdn');
        }

        $data = $obj->info();

        $retval = $storage->update_cdn_data($image, $version, $data['mediaLink'], $data, $size, $this->get_key());

        $mapper->_use_cache = $original_cache;

        return $retval;
    }

    /**
     * @param string $name
     */
    function delete_version($name)
    {
        if (!$this->is_configured())
            throw new E_NggCdnUnconfigured(__("GCS has not been configured yet", 'nggallery'));

        $gcs    = new StorageClient($this->get_config());
        $bucket = $gcs->bucket($this->get_bucket_name());

        $object = $bucket->object($name);
        $object->delete();
    }

    /**
     * Deletes an image from the CDN
     *
     * @param int|C_Image|stdClass $image
     * @param string $size
     */
    function delete($image, $size = 'full')
    {
        if (!$this->is_configured())
            throw new E_NggCdnUnconfigured(__("GCS has not been configured yet", 'nggallery'));

        $gcs    = new StorageClient($this->get_config());
        $bucket = $gcs->bucket($this->get_bucket_name());

        $name = $this->get_current_image_name($image, $size);

        if ($name)
        {
            try {
                $object = $bucket->object($name);
                $object->delete();
            } catch (\Google\Cloud\Core\Exception\NotFoundException $exception) {
                // The object doesn't exist, so there's no need to worry here
            }
        }
    }

    /**
     * Downloads the image
     *
     * @param string $image
     * @param string $size
     * @param bool $overwrite Force downloading an image that may already exist locally
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
            copy($filename, $image_abspath);
            unlink($filename);
            return $image_abspath;
        }

        return $storage->get_image_abspath($image, $size);
    }
}