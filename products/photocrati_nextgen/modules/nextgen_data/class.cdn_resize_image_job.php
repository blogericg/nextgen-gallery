<?php

/**
 * Expects an associative array for its dataset, with the following properties:
 * int id - the id of the image
 * string size - the named size of the image to generate
 * array params - a list of params passed to C_Gallery_Storage::generate_image_size()
 */
class C_CDN_Resize_Image_Job extends C_CDN_Publish_Image_Job
{
    function run()
    {
        $cdn = C_CDN_Providers::get_current();
        $data = $this->get_dataset();

        try {
            $cdn->download($data['id'], 'backup');
        }
        catch (Exception $ex) {
            $cdn->download($data['id'], 'full');
        }
        
        $this->resize_local_image($data['id'], $data['size'], $data['params']);

        $this->set_dataset($data['id']);
        parent::run();
    }

    /**
     * Performs the resize operation
     * @param int $image
     * @param string $size
     * @param [] $params
     * @throws RuntimeException
     * @return null
     */
    function resize_local_image($image, $size='full', $params=[])
    {
        $storage = C_Gallery_Storage::get_instance();
        if ($storage->generate_image_size($image, $size, $params) === FALSE) {
            throw new RuntimeException(__("Could not resize the {$size} named size for image #{$image}", 'nextgen-gallery'));
        }
    }
}