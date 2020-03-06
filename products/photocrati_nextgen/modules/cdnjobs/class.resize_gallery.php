<?php

/**
 * Expects an associative array for its dataset, with the following properties:
 * int id - the id of the gallery
 * string size - the named size of the image to regenerate
 * array params - a list of params passed to C_Gallery_Storage::generate_image_size()
 */
class C_CDN_Resize_Gallery_Job extends \ReactrIO\Background\Job
{
    function run()
    {
        $data = $this->get_dataset();
        
        return array_map(
            function($image) {
                $dataset = array_merge($this->get_dataset(), ['id' => $image->pid]);

                return \ReactrIO\Background\Job::create(
                    sprintf(__("Resizing image #%d", 'nggallery'), $image->pid),
                    'cdn_resize_image',
                    $dataset,
                    $this->get_id()
                )->save('cdn');
            },
            \C_Image_Mapper::get_instance()->find_all_for_gallery($data['id'])
        );
    }
}