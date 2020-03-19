<?php

class C_CDN_Flush_Cache_Gallery_Job extends \ReactrIO\Background\Job
{
    function run()
    {
        $cdn       = C_CDN_Providers::get_current();
        $dynthumbs = C_Dynamic_Thumbnails_Manager::get_instance();
        $mapper    = C_Image_Mapper::get_instance();
        $storage   = C_Gallery_Storage::get_instance();

        // Important: array_map() can consume considerably more memory here so foreach() MUST be used
        foreach ($mapper->find_all_for_gallery($this->get_dataset()) as $image) {

            // Determine if there is a dynamic version to purge first
            foreach ($storage->get_image_sizes($image->pid) as $size) {
                if (!$dynthumbs->is_size_dynamic($size))
                    continue;

                \ReactrIO\Background\Job::create(
                    sprintf(__("Flushing dynamic images for image #%d", 'nggallery'), $image->pid),
                    'cdn_flush_cache_image',
                    $image->pid,
                    $this->get_id()
                )->save('cdn');

                // Only create one job per image
                break;
            }
        }
    }
}