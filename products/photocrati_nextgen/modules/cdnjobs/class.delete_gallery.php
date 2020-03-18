<?php

class C_CDN_Delete_Gallery_Job extends \ReactrIO\Background\Job
{
    function run()
    {
        return array_map(
            function($image) {
                return \ReactrIO\Background\Job::create(
                    sprintf(__("Deleting image #%d", 'nggallery'), $image->pid),
                    'cdn_delete_image',
                    $image->pid,
                    $this->get_id()
                )->save('cdn');
            },
            C_Image_Mapper::get_instance()->find_all_for_gallery($this->get_dataset())
        );
    }
}