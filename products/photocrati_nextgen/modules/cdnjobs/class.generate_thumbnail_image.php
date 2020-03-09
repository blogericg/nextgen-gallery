<?php

class C_CDN_Generate_Thumbnail_Image_Job extends C_CDN_Publish_Image_Job
{

    function run()
    {
        $cdn = C_CDN_Providers::get_current();
        $data = $this->get_dataset();

        try {
            $cdn->download($data['image_id'], 'backup');
        } catch (Exception $ex) {
            $cdn->download($data['image_id'], 'full');
        }

        $this->generate_thumbnail_image($data['image_id']);

        // Call C_CDN_Publish_Image_Job->run()
        $this->set_dataset($data['image_id']);
        parent::run();
    }

    /**
     * @param int $image_id
     * @throws RuntimeException
     */
    function generate_thumbnail_image($image_id)
    {
        $storage = C_Gallery_Storage::get_instance();

        $params = [
            'watermark'  => FALSE,
            'reflection' => FALSE
        ];

        if ($storage->generate_thumbnail($image_id, $params) === FALSE)
            throw new RuntimeException(
                sprintf(__("Could not generate thumbnail for image #%d", 'nggallery'), $image_id)
            );
    }

}