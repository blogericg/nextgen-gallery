<?php

class C_CDN_Generate_Thumbnail_Gallery_Job extends \ReactrIO\Background\Job
{
    function run()
    {
        return array_map(
            function ($image) {
                return \ReactrIO\Background\Job::create(
                    sprintf(__("Generating new thumbnail for image #%d", 'nextgen-gallery'), $image->pid),
                    'cdn_generate_thumbnail_image',
                    ['id' => $image->pid],
                    $this->get_id()
                )->save('cdn');
            },
            C_Image_Mapper::get_instance()->find_all_for_gallery($this->get_dataset())
        );
    }
}