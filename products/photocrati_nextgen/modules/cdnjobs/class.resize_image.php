<?php

/**
 * Expects an associative array for its dataset, with the following properties:
 * int id - the id of the image
 * string size - the named size of the image to generate
 * array params - a list of params passed to C_Gallery_Storage::generate_image_size()
 */
class C_CDN_Resize_Image_Job extends \ReactrIO\Background\Job
{
    function run()
    {
        $cdn  = C_CDN_Providers::get_current();
        $data = $this->get_dataset();
        $id   = $data['id'];

        try {
            $cdn->download($id, 'full');
        }
        catch (Exception $ex) {
            $cdn->download($id, 'backup');
        }

        // generate_image_size() will make a new _backup file if this setting is on
        $settings = C_NextGen_Settings::get_instance();
        $original = $settings->imgBackup;
        $settings->imgBackup = 0;

        $result = C_Gallery_Storage::get_instance()->generate_image_size($id, $data['size'], $data['params']);

        // Restore the original setting
        $settings->imgBackup = $original;

        if ($result === FALSE)
            throw new RuntimeException(
                sprintf(__("Could not resize the '%s' named size for image #%d", 'nggallery'), $data['size'], $id)
            );
    }
}