<?php

class C_CDN_Publish_Image_Job extends \ReactrIO\Background\Job
{
    function run()
    {
        array_map(
            function($size) {
                $cdn = C_CDN_Providers::get_current();
                if ($cdn->upload($this->get_dataset(), $size))
                {
                    $this->logOutput(
                        sprintf(__("Uploaded '%s' size for %s", 'nggallery'), $size, $this->get_dataset())
                    );
                    if ($cdn->is_offload_enabled())
                    {
                        $storage = C_Gallery_Storage::get_instance();
                        unlink($storage->get_image_abspath($this->get_dataset()), $size);
                    }
                }
            },
            C_Gallery_Storage::get_instance()->get_image_sizes($this->get_dataset())
        );
        return $this;
    }
}