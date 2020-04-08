<?php

class C_CDN_Watermark_Image_Job extends \ReactrIO\Background\Job
{
    function run()
    {
        $cdn  = C_CDN_Providers::get_current();
        $data = $this->get_dataset();
        $id   = $data['id'];

        try {
            $cdn->download($id, 'full');
        } catch (Exception $ex) {
            $cdn->download($id, 'backup');
        }

        $storage = C_Gallery_Storage::get_instance();

        $params = [
            'watermark'  => TRUE,
            'reflection' => FALSE,
            'crop'       => FALSE
        ];

        // generate_image_size() will make a new _backup file if this setting is on
        $settings = C_NextGen_Settings::get_instance();
        $original = $settings->imgBackup;
        $settings->imgBackup = 0;

        $result = $storage->generate_image_size($id, 'full', $params);

        // Restore the original setting
        $settings->imgBackup = $original;

        if ($result === FALSE)
            throw new RuntimeException(
                sprintf(__("Could not watermark image #%d", 'nggallery'), $id)
            );
    }
}