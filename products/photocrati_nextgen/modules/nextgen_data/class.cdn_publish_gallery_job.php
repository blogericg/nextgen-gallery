<?php

/**
 * Expects an integer for its dataset, the gallery ID
 */
class C_CDN_Publish_Gallery_Job extends \ReactrIO\Background\Job
{
    function run()
    {
        return array_map(
            function($image) {
                return \ReactrIO\Background\Job::create(
                    "Publish image #{$image->pid} to CDN",
                    'cdn_publish_image',
                    $image->pid,
                    $this->get_id()
                )->save('cdn');
            },
            \C_Image_Mapper::get_instance()->find_all_for_gallery($this->get_dataset())
        );
    }
}