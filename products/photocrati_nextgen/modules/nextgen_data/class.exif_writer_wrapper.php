<?php

class C_Exif_Writer_Wrapper
{
    // Because our C_Exif_Writer class relies on PEL (a library which uses namespaces) we wrap
    // its use through these methods which performs a PHP version check before loading the class file

    /**
     * @param $old_file
     * @param $new_file
     * @return bool|int
     */
    static public function copy_metadata($old_file, $new_file)
    {
        if (!M_NextGen_Data::check_pel_min_php_requirement())
            return FALSE;
        self::load_pel();

        return @C_Exif_Writer::copy_metadata($old_file, $new_file);
    }

    /**
     * @param $filename
     * @return array|null
     */
    static public function read_metadata($filename)
    {
        if (!M_NextGen_Data::check_pel_min_php_requirement())
            return array();
        self::load_pel();

        return @C_Exif_Writer::read_metadata($filename);
    }

    /**
     * @param array $exif
     * @return array
     */
    static public function reset_orientation($exif = array())
    {
        if (!M_NextGen_Data::check_pel_min_php_requirement())
            return array();
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
        if (!M_NextGen_Data::check_pel_min_php_requirement())
            return FALSE;
        self::load_pel();

        return @C_Exif_Writer::write_metadata($filename, $metadata);
    }

    static public function load_pel()
    {
        require_once(__DIR__ . DIRECTORY_SEPARATOR . 'pel-0.9.6' . DIRECTORY_SEPARATOR . 'class.exif_writer.php');
    }
}