<?php

class C_CDN_Watermark_Image_Job extends \ReactrIO\Background\Job
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

        $this->watermark_local_image($id);
    }

    /**
     * @param int $id
     * @throws RuntimeException
     */
    function watermark_local_image($id)
    {
        $storage = C_Gallery_Storage::get_instance();

        $params = [
            'watermark'  => TRUE,
            'reflection' => FALSE,
            'crop'       => FALSE
        ];

        if ($storage->generate_image_size($id, 'full', $params) === FALSE)
            throw new RuntimeException(
                sprintf(__("Could not watermark image #%d", 'nggallery'), $id)
            );
    }
}