<?php

class C_CDN_Recover_Image_Job extends C_CDN_Publish_Image_Job
{
    function run()
    {
        $cdn  = C_CDN_Providers::get_current();
        $data = $this->get_dataset();
        $id   = $data['id'];

        try {
            $cdn->download($id, 'backup');
        } catch (Exception $ex) {
            $cdn->download($id, 'full');
        }

        C_Gallery_Storage::get_instance()->recover_image($id);

        // Call C_CDN_Publish_Image_Job->run()
        $this->set_dataset($id);
        parent::run();
    }
}