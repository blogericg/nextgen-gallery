<?php

class C_CDN_Rotate_Image_Job extends C_CDN_Publish_Image_Job
{

    function run()
    {
        $cdn  = C_CDN_Providers::get_current();
        $data = $this->get_dataset();

        try {
            $cdn->download($data['image_id'], 'backup');
        } catch (Exception $ex) {
            $cdn->download($data['image_id'], 'full');
        }

        $this->rotate_local_image($data['image_id'], $data['direction']);

        // Call C_CDN_Publish_Image_Job->run()
        $this->set_dataset($data['image_id']);
        parent::run();
    }

    /**
     * @param int $image_id
     * @param string $direction 'clockwise' or 'counter-clockwise'
     * @throws RuntimeException
     */
    function rotate_local_image($image_id, $direction)
    {
        if (!in_array($direction, ['clockwise', 'counter-clockwise']))
            throw new RuntimeException(
                sprintf(__("Could not rotate image #%d with direction %s: direction must be clockwise or counter-clockwise", 'nggallery'),
                    $image_id,
                    $direction
                )
            );

        $storage = C_Gallery_Storage::get_instance();

        $params = [
            'watermark'  => FALSE,
            'reflection' => FALSE
        ];

        if ($direction === 'clockwise')
            $params['rotation'] = 90;
        if ($direction === 'counter-clockwise')
            $params['rotation'] = -90;

        if ($storage->generate_image_size($image_id, 'full', $params) === FALSE)
            throw new RuntimeException(
                sprintf(__("Could not rotate image #%d", 'nggallery'), $image_id)
            );

        // Avoid rotating the thumbnail a second time
        unset($params['rotation']);

        if ($result = $storage->generate_thumbnail($image_id, $params) === FALSE)
            throw new RuntimeException(
                sprintf(__("Could not generate thumbnail for image #%d", 'nggallery'), $image_id)
            );
    }

}