<?php

use \Google\Cloud\Storage\StorageClient;

class C_GCS_CDN_Provider extends C_CDN_Provider
{
    /**
     * Determines whether the provider has been configured or not
     * @return bool
     */
    function is_configured()
    {
        $config = $this->get_config();
        return isset($config['keyFile']) && isset($config['bucket']);
    }

    /**
     * Gets the configuration for the provider
     * @return []
     */
    function get_config()
    {
        // Once we have a UI for providing the service account, we'll fetch from C_NextGen_Settings.
        // For now we will just get the service account from a constant
        return defined('NGG_GCS_SERVICE_ACCOUNT') && defined('NGG_GCS_BUCKET')
            ? ['keyFile' => json_decode(constant('NGG_GCS_SERVICE_ACCOUNT'), true), 'bucket' => constant('NGG_GCS_BUCKET')]
            : [];
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
     * @return string
     */
    function get_key()
    {
        return 'gcs';
    }
    
    /**
     * Uploads an image to the CDN
     * @param string $image
     * @param string $size
     * @return bool
     */
    function upload($image, $size='full')
    {
        if (!$this->is_configured()) throw new E_NggCdnUnconfigured(__("GCS has not been configured yet", 'nextgen-gallery'));

        $storage = C_Gallery_Storage::get_instance();
        $gcs = new StorageClient($this->get_config());
        $bucket = $gcs->bucket($this->get_bucket_name());
        $version = $storage->get_latest_image_timestamp($image, $size);
        $obj = $bucket->upload(
            fopen($storage->get_image_abspath($image, $size), 'r'),
            ['predefinedAcl' => 'publicRead', 'metadata' => ['version' => $version]]
        );
        $data = $obj->info();
        return $storage->update_cdn_data($image, $version, $data['mediaLink'], $data, $size, $this->get_key());
    }

    function delete($image, $size='full')
    {
        // TODO: Implement
    }

    function download($image, $size='full')
    {
        // TODO: Implement
    }
}