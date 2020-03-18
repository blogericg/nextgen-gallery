<?php

class C_CDN_Generate_Thumbnail_Image_Job extends \ReactrIO\Background\Job
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

        $this->generate_thumbnail_image($id);
    }

    /**
     * @param int $id
     * @throws RuntimeException
     */
    function generate_thumbnail_image($id)
    {
        $storage = C_Gallery_Storage::get_instance();

        $params = [
            'watermark'  => FALSE,
            'reflection' => FALSE
        ];

        if ($storage->generate_thumbnail($id, $params) === FALSE)
            throw new RuntimeException(
                sprintf(__("Could not generate thumbnail for image #%d", 'nggallery'), $id)
            );
    }

}