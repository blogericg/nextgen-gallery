<?php

class C_CDN_Delete_Image_Job extends \ReactrIO\Background\Job
{
    function run()
    {
        $storage  = C_Gallery_Storage::get_instance();
        $cdn  = C_CDN_Providers::get_current();
        $data  = $this->get_dataset();
        $id    = $data['id'];
        $sizes = $data['size'];

        $parent_job_id = 0;

        if (is_string($sizes) && $sizes === 'all')
        {
            $sizes = $storage->get_image_sizes($id);
            if (!in_array('backup', $sizes))
                $sizes[] = 'backup';

            $parent_job_id = \ReactrIO\Background\Job::create(
                sprintf(__("Deleting image #%d final stage", 'nggallery'), $id),
                'cdn_delete_image_final',
                ['id' => $id],
                self::get_parent_id()
            )->save('cdn')->get_id();
        }

        if (is_array($sizes))
        {
            foreach ($sizes as $size) {
                \ReactrIO\Background\Job::create(
                    sprintf(__("Deleting size %s image %d from CDN", 'nggallery'), $size, $id),
                    'cdn_delete_image',
                    ['id' => $id, 'size' => $size],
                    $parent_job_id
                )->save('cdn');
            }
        }
        else {
            $cdn->delete($id, $sizes);
        }
    }
}