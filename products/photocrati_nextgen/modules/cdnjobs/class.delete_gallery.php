<?php

class C_CDN_Delete_Gallery_Job extends \ReactrIO\Background\Job
{
    function run()
    {
        $data = $this->get_dataset();
        $id   = $data['id'];

        $final_job_id = \ReactrIO\Background\Job::create(
            sprintf(__("Deleting gallery #%d final stage", 'nggallery'), $id),
            'cdn_delete_gallery_final',
            ['id' => $id]
        )->save('cdn')->get_id();

        array_map(
            function($image) use ($final_job_id) {
                return \ReactrIO\Background\Job::create(
                    sprintf(__("Deleting image #%d", 'nggallery'), $image->pid),
                    'cdn_delete_image',
                    ['id' => $image->pid, 'size' => 'all'],
                    $final_job_id
                )->save('cdn');
            },
            C_Image_Mapper::get_instance()->find_all_for_gallery($id)
        );
    }
}