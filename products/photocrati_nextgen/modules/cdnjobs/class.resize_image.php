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
        $cdn  = C_CDN_Providers::get_current();
        $data = $this->get_dataset();
        $id   = $data['id'];

        try {
            $cdn->download($id, 'backup');
        }
        catch (Exception $ex) {
            $cdn->download($id, 'full');
        }
        
        $this->resize_local_image($id, $data['size'], $data['params']);

        $this->set_dataset($id);
        parent::run();
    }

    /**
     * Performs the resize operation
     * @param int $id
     * @param string $size
     * @param array $params
     * @return null
     * @throws RuntimeException
     */
    function resize_local_image($id, $size = 'full', $params = [])
    {
        $storage = C_Gallery_Storage::get_instance();
        if ($storage->generate_image_size($id, $size, $params) === FALSE)
        {
            throw new RuntimeException(
                sprintf(__("Could not resize the '%s' named size for image #%d", 'nggallery'), $size, $id)
            );
        }
    }
}