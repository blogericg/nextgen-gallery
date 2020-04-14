<?php

class C_CDN_Copy_Image_Job extends \ReactrIO\Background\Job
{
    function run()
    {
        $cdn  = C_CDN_Providers::get_current();
        $data = $this->get_dataset();

        $id             = $data['id'];
        $destination_id = $data['destination'];

        // If no size has been passed to download then generate jobs to download each image size. The actual
        // copying of the image will be performed by the cdn_copy_image_final task.
        if (empty($data['size']))
        {
            $storage = C_Gallery_Storage::get_instance();
            $sizes = $storage->get_image_sizes($id);

            $parent_job_id = \ReactrIO\Background\Job::create(
                sprintf(__("Copying image #%d to gallery #%d final stage", 'nggallery'), $id, $destination_id),
                'cdn_copy_image_final',
                ['id' => $id, 'destination' => $destination_id]
            )->save('cdn')->get_id();

            foreach ($sizes as $size) {
                \ReactrIO\Background\Job::create(
                    sprintf(__("Downloading image #%d sized %s to copy to gallery #%d", 'nggallery'), $id, $size, $destination_id),
                    'cdn_copy_image',
                    ['id' => $id, 'destination' => $destination_id, 'size' => $size],
                    $parent_job_id
                )->save('cdn');
            }
        }
        else if (is_string($data['size'])) {
            // Just fetch the image, nothing more
            try {
                $cdn->download($id, $data['size']);
            } catch (Exception $ex) {
                // Well this isn't going well
            }
        }
    }
}