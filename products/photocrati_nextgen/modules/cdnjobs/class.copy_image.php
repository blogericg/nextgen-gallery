<?php

class C_CDN_Copy_Image_Job extends C_CDN_Publish_Image_Job
{
    function run()
    {
        $cdn  = C_CDN_Providers::get_current();
        $data = $this->get_dataset();

        $id             = $data['id'];
        $destination_id = $data['destination'];

        try {
            $cdn->download($id, 'backup');
        } catch (Exception $ex) {
            $cdn->download($id, 'full');
        }

        $new_id = reset(C_Gallery_Storage::get_instance()->copy_images([$id], $destination_id));

        // Call C_CDN_Publish_Image_Job->run()
        $this->set_dataset(['id' => $new_id, 'size' => 'all']);
        parent::run();
    }
}