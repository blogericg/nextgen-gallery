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
        } catch (Exception $ex) {
            $cdn->download($id, 'backup');
        }

        $storage = C_Gallery_Storage::get_instance();

        if ($storage->generate_image_size($id, $size, $params) === FALSE)
            throw new RuntimeException(
                sprintf(__("Could not generate size %s for image #%d", 'nggallery'), $size, $id)
            );
        else
            error_log("Generated new image!");

    }
}