<?php

class C_CDN_Flush_Cache_Image_Job extends \ReactrIO\Background\Job
{
    function run()
    {
        $cdn  = C_CDN_Providers::get_current();
        $key  = $cdn->get_key();
        $id   = $this->get_dataset();

        $storage   = C_Gallery_Storage::get_instance();
        $mapper    = C_Image_Mapper::get_instance();
        $dynthumbs = C_Dynamic_Thumbnails_Manager::get_instance();

        $image = $mapper->find($id);

        foreach ($storage->get_image_sizes($image->pid) as $size) {

            if (!$dynthumbs->is_size_dynamic($size))
                continue;

            if (empty($image->meta_data[$size][$key]) || empty($image->meta_data[$size][$key]['name']))
                continue;

            $cdn->delete($image->pid, $size);

            unset($image->meta_data[$size]);
            $mapper->save($image);
        }
    }
}