<?php

class C_CDN_Delete_Image_Final_Job extends \ReactrIO\Background\Job
{
    function run()
    {
        $cdn      = C_CDN_Providers::get_current();
        $storage  = C_Gallery_Storage::get_instance();
        $settings = C_NextGen_Settings::get_instance();
        $mapper   = C_Image_Mapper::get_instance();

        $data = $this->get_dataset();
        $id   = $data['id'];

        $image = $mapper->find($id);

        if ($image)
        {
            do_action('ngg_delete_picture', $image->pid, $image);

            if ($settings->get('deleteImg', FALSE) && !$cdn->is_offload_enabled())
                $storage->delete_image($image->pid);
            else
                $mapper->destroy($image->pid);
        }
    }
}