<?php

class E_NggCdnUnconfigured extends RuntimeException {};

abstract class C_CDN_Provider
{
    abstract function get_key();
    abstract function is_configured();

    abstract function upload($image,   $size = 'full');
    abstract function download($image, $size = 'full');
    abstract function delete($image,   $size = 'full');

    abstract function copy($image, $gallery_id);
    abstract function move($image, $gallery_id);
    
    protected static $_instance = NULL;

    /**
     * Gets an instance of the CDN provider
     * @return self
     */
    static function get_instance()
    {
        if (!isset(self::$_instance))
        {
            $klass = get_called_class();
            self::$_instance = new $klass;
        }
        return self::$_instance;
    }

    /**
     * Gets the current configuration for the provider
     *
     * @return mixed []
     */
    function get_config()
    {
        return C_NextGen_Settings::get_instance()->get($this->get_key(), []);
    }

    /**
     * @return bool
     */
    function is_offload_enabled()
    {
        $config = $this->get_config();
        return isset($config['offload']) ? $config['offload'] : FALSE;
    }

    /**
     * Updates the configuration for the provider
     *
     * @param array $config
     * @return bool
     */
    function update_config($config = [])
    {
        $settings = C_NextGen_Settings::get_instance();
        $settings->set($this->get_key(), $config);
        return $settings->save();
    }
    
}

class C_CDN_Providers
{
    protected static $_mapping = [];

    protected function __construct() {}

    /**
     * Registers a CDN provider
     *
     * @param string $klass
     * @return string
     */
    static function register($klass)
    {
        /** @var C_CDN_Provider $provider */
        $provider = $klass::get_instance();
        $key = $provider->get_key();
        self::$_mapping[$key] = $klass;
        return $key;
    }

    /**
     * Deregisters a CDN provider
     *
     * @return NULL
     */
    static function deregister($klass)
    {
        /** @var C_CDN_Provider $provider */
        $provider = $klass::get_instance();
        $key = $provider->get_key();
        unset(self::$_mapping[$key]);
        return NULL;
    }

    /**
     * Gets the CDN Provider for the given key
     *
     * @param string $key
     * @return C_CDN_Provider
     */
    static function get($key)
    {
        if (!isset(self::$_mapping[$key]))
            throw new E_NggCdnUnconfigured(
                sprintf(__("No CDN provider registered for %s", "nextgen-gallery"), $key)
            );
        $klass = self::$_mapping[$key];
        return $klass::get_instance();
    }

    /**
     * Gets the currently configured CDN provider
     *
     * @return C_CDN_Provider
     */
    static function get_current()
    {
        $key = self::is_cdn_configured();
        if (!$key)
            throw new E_NggCdnUnconfigured(__("No CDN provider has been configured yet", "nextgen-gallery"));
        return self::get($key);
    }

    /**
     * Determines whether a CDN has been configured yet
     *
     * @return bool
     */
    static function is_cdn_configured()
    {
        return C_NextGen_Settings::get_instance()->get('cdn', FALSE);
    }
}