<?php

class C_CDN_Delete_Image_Job extends \ReactrIO\Background\Job
{
    function run()
    {
        $cdn  = C_CDN_Providers::get_current();
        $id   = $this->get_dataset();

        $settings = C_NextGen_Settings::get_instance();
        $mapper   = C_Image_Mapper::get_instance();
        $storage  = C_Gallery_Storage::get_instance();

        // Remove each image from the CDN
        foreach ($storage->get_image_sizes($id) as $size) {
            $cdn->delete($id, $size);
        }

        $image = $mapper->find($id);
        if ($image)
        {
            do_action('ngg_delete_picture', $image->pid, $image);

            if ($settings->get('deleteImg', FALSE) && !$cdn->is_offload_enabled())
                $storage->delete_image($image->pid);

            $mapper->destroy($image->pid);
        }

        return $this;
    }
}