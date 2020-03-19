<?php

class C_CDN_Move_Image_Job extends \ReactrIO\Background\Job
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

        $cdn->move($id, $destination_id);

        return $this;
    }
}