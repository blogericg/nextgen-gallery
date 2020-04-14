<?php

class C_CDN_Move_Image_Final_Job extends \ReactrIO\Background\Job
{
    function run()
    {
        $cdn  = C_CDN_Providers::get_current();
        $data = $this->get_dataset();

        $id             = $data['id'];
        $destination_id = $data['destination'];

        // Because C_Gallery_Storage->copy_image()
        //                          -> import_image_file()
        //                          -> generate_thumbnail()
        //                          -> generate_image_size()
        //                          -> action ngg_generated_image is triggered
        // that action has a listener in the CDN jobs module to handle most operations, but in this case it conflicts
        // with the task created by the CDN copy() method that creates new publish tasks for each image size.
        M_CDN_Jobs::$_run_ngg_generated_image_action = FALSE;

        $cdn->move($id, $destination_id);
    }
}