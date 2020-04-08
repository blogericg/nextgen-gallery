<?php

class C_CDN_Recover_Image_Job extends \ReactrIO\Background\Job
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

        // recover_image() will generate a new version of every dynamic image that has been generated; when finished
        // it will trigger the action 'ngg_recovered_image' which the cdnjobs module class will listen for and
        // will create a new task to upload each new image file
        C_Gallery_Storage::get_instance()->recover_image($id);
    }
}