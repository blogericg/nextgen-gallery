<?php

class C_Exif_Writer_Wrapper
{
    // TODO: Remove this file & class. It was initially created as a wrapper for hosts using PHP 5.2
    /**
     * @param $old_file
     * @param $new_file
     * @return bool|int
     */
    static public function copy_metadata($old_file, $new_file)
    {
        self::load_pel();
        return @C_Exif_Writer::copy_metadata($old_file, $new_file);
    }

    /**
     * @param $filename
     * @return array|null
     */
    static public function read_metadata($filename)
    {
        self::load_pel();
        return @C_Exif_Writer::read_metadata($filename);
    }

    /**
     * @param array $exif
     * @return array
     */
    static public function reset_orientation($exif = array())
    {
        self::load_pel();
        return @C_Exif_Writer::reset_orientation($exif);
    }

    /**
     * @param $filename
     * @param $metadata
     * @return bool|int
     */
    static public function write_metadata($filename, $metadata)
    {
        self::load_pel();
        return @C_Exif_Writer::write_metadata($filename, $metadata);
    }

    static public function load_pel()
    {
        require_once(__DIR__ . DIRECTORY_SEPARATOR . 'pel-0.9.6' . DIRECTORY_SEPARATOR . 'class.exif_writer.php');
    }
}