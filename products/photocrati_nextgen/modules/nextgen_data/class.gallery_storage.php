<?php

class E_UploadException extends E_NggErrorException
{
    function __construct($message='', $code=NULL, $previous=NULL)
    {
        if (!$message)
            $message = "There was a problem uploading the file.";

        parent::__construct($message, $code, $previous);
    }
}

class E_InsufficientWriteAccessException extends E_NggErrorException
{
    function __construct($message=FALSE, $filename=NULL, $code=NULL, $previous=NULL)
    {
        if (!$message)
            $message = "Could not write to file. Please check filesystem permissions.";
        if ($filename)
            $message .= " Filename: {$filename}";

        parent::__construct($message, $code, $previous);
    }
}

class E_NoSpaceAvailableException extends E_NggErrorException
{
    function __construct($message='', $code=NULL, $previous=NULL)
    {
        if (!$message)
            $message = "You have exceeded your storage capacity. Please remove some files and try again.";

        parent::__construct($message, $code, $previous);
    }
}

class E_No_Image_Library_Exception extends E_NggErrorException
{
    function __construct($message='', $code=NULL, $previous=NULL)
    {
        if (!$message)
            $message = "The site does not support the GD Image library. Please ask your hosting provider to enable it.";

        parent::__construct($message, $code, $previous);
    }
}

/**
 * Class C_Gallery_Storage
 * @implements I_Gallery_Storage
 * @mixin Mixin_GalleryStorage_Base
 * @mixin Mixin_GalleryStorage_Base_Dynamic
 * @mixin Mixin_GalleryStorage_Base_Getters
 * @mixin Mixin_GalleryStorage_Base_Management
 * @mixin Mixin_GalleryStorage_Base_MediaLibrary
 * @mixin Mixin_GalleryStorage_Base_Upload
 */
class C_Gallery_Storage extends C_Component
{
    public static $_instances = array();

    function define($context = FALSE)
    {
        parent::define($context);
        $this->add_mixin('Mixin_GalleryStorage_Base');
        $this->add_mixin('Mixin_GalleryStorage_Base_Dynamic');
        $this->add_mixin('Mixin_GalleryStorage_Base_Getters');
        $this->add_mixin('Mixin_GalleryStorage_Base_Management');
        $this->add_mixin('Mixin_GalleryStorage_Base_MediaLibrary');
        $this->add_mixin('Mixin_GalleryStorage_Base_Upload');
        $this->implement('I_Gallery_Storage');
        $this->implement('I_GalleryStorage_Driver'); // backwards compatibility
    }

    /**
     * Provides some aliases to defined methods; thanks to this a call to C_Gallery_Storage->get_thumb_url() is
     * translated to C_Gallery_Storage->get_image_url('thumb').
     * TODO: Remove this 'magic' method so that our code is always understandable without needing deep context
     * @param string $method
     * @param array $args
     * @return mixed
     * @throws Exception
     */
    function __call($method, $args)
    {
        if (preg_match("/^get_(\w+)_(abspath|url|dimensions|html|size_params)$/", $method, $match))
        {
            if (isset($match[1]) && isset($match[2]) && !$this->has_method($method))
            {
                $method = 'get_image_' . $match[2];
                $args[] = $match[1];
                return parent::__call($method, $args);
            }
        }

        return parent::__call($method, $args);
    }

    /**
     * For compatibility reasons, we include this method. This used to be used to get the underlying storage driver.
     * Necessary for Imagify integration
     */
    function &get_wrapped_instance()
    {
        return $this;
    }

    function initialize()
    {
        parent::initialize();
        $this->_gallery_mapper = C_Gallery_Mapper::get_instance();
        $this->_image_mapper   = C_Image_Mapper::get_instance();
    }

    /**
     * @param bool|string $context
     * @return C_Gallery_Storage
     */
    static function get_instance($context = False)
    {
        if (!isset(self::$_instances[$context]))
            self::$_instances[$context] = new C_Gallery_Storage($context);
        return self::$_instances[$context];
    }
}