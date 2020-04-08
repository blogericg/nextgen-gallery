<?php

class C_CDN_Generate_Image_Size_Job extends \ReactrIO\Background\Job
{

    function run()
    {
        $cdn    = C_CDN_Providers::get_current();
        $data   = $this->get_dataset();
        $id     = $data['id'];
        $size   = $data['size'];
        $params = $data['params'];

        try {
            $cdn->download($id, 'full');
            $original_size = 'full';
        } catch (Exception $ex) {
            $cdn->download($id, 'backup');
            $original_size = 'backup';
        }

        $storage = C_Gallery_Storage::get_instance();

        // generate_image_size() will make a new _backup file if this setting is on
        $settings = C_NextGen_Settings::get_instance();
        $original = $settings->imgBackup;
        $settings->imgBackup = 0;

        $result = $storage->generate_image_size($id, $size, $params);

        // Restore the original setting
        $settings->imgBackup = $original;

        // Cleanup: remove the image used to generate the thumbnail file
        if ($cdn->is_offload_enabled())
            unlink($storage->get_image_abspath($id, $original_size));

        if ($result === FALSE)
            throw new RuntimeException(
                sprintf(__("Could not generate size %s for image #%d", 'nggallery'), $size, $id)
            );
    }
}