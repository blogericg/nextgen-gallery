<?php

class C_CDN_Publish_Image_Job extends \ReactrIO\Background\Job
{
    function run()
    {
        $data  = $this->get_dataset();
        $id    = $data['id'];
        $sizes = $data['size'];

        if (is_string($sizes))
        {
            if ($sizes === 'all')
                $sizes = C_Gallery_Storage::get_instance()->get_image_sizes($id);
            else
                $sizes = [$sizes];
        }

        $storage = C_Gallery_Storage::get_instance();
        $cdn     = C_CDN_Providers::get_current();

        array_map(
            function($size) use ($cdn, $id, $storage) {

                if ($cdn->upload($id, $size))
                {
                    $this->logOutput(
                        sprintf(__("Uploaded '%s' size for %s", 'nggallery'), $size, $id)
                    );

                    if ($cdn->is_offload_enabled())
                        unlink($storage->get_image_abspath($id, $size));
                }
            },
            $sizes
        );

        // Because 'backup' is not included in get_image_sizes()
        unlink($storage->get_image_abspath($id, 'backup'));

        return $this;
    }
}