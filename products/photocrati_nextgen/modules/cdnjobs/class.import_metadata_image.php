<?php

class C_CDN_Import_MetaData_Image_Job extends \ReactrIO\Background\Job
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

        if (is_string($id))
            $id = intval($id);

        C_Image_Mapper::get_instance()->reimport_metadata($id);

        if ($cdn->is_offload_enabled())
            unlink(C_Gallery_Storage::get_instance()->get_image_abspath($id, 'backup'));
    }
}