<?php

class C_CDN_Import_MetaData_Image_Job extends C_CDN_Publish_Image_Job
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

        $this->import_metadata($id);

        // Call C_CDN_Publish_Image_Job->run()
        $this->set_dataset($id);
        parent::run();
    }

    /**
     * @param int $id
     */
    function import_metadata($id)
    {
        C_Image_Mapper::get_instance()->reimport_metadata($id);
    }
}