<?php

class C_CDN_Move_Image_Job extends \ReactrIO\Background\Job
{
    function run()
    {
        $cdn  = C_CDN_Providers::get_current();
        $data = $this->get_dataset();

        $id             = $data['id'];
        $destination_id = $data['destination'];

        try {
            $cdn->download($id, 'backup');
        } catch (Exception $ex) {
            $cdn->download($id, 'full');
        }

        // move_images() is just a wrapper to copy_images() that removes the original once copy_images() has finished
        // so here we use copy_images() and then call for the originals to be removed
        C_Gallery_Storage::get_instance()->copy_images([$id], $destination_id);

        \ReactrIO\Background\Job::create(
            sprintf(__("Deleting image #%d", 'nggallery'), $id),
            'cdn_delete_image',
            $id,
            $this->get_id()
        )->save('cdn');
    }
}