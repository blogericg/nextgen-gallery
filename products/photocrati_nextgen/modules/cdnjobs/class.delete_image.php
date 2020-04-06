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

        if (is_string($sizes) && $sizes === 'all')
        {
            // We must wait to remove the image from the DB until after all the child jobs have finished
            // TODO: there must be a better way to handle this in the Job class itself by requiring
            // TODO: child tasks be processed before parents
            $sizes = ['cdn_final_cleanup'];
            $sizes = array_merge($sizes, $storage->get_image_sizes($id));
            if (!in_array('backup', $sizes))
                $sizes[] = 'backup';
        }

        if (is_array($sizes))
        {
            foreach ($sizes as $size) {
                \ReactrIO\Background\Job::create(
                    sprintf(__("Deleting size %s image %d from CDN", 'nggallery'), $size, $id),
                    'cdn_delete_image',
                    ['id' => $id, 'size' => $size],
                    $this->get_id()
                )->save('cdn');
            }
        }
        else {
            $settings = C_NextGen_Settings::get_instance();
            $mapper   = C_Image_Mapper::get_instance();

            $cdn->delete($id, $sizes);

            if ($sizes === 'cdn_final_cleanup')
            {
                $image = $mapper->find($id);
                if ($image)
                {
                    do_action('ngg_delete_picture', $image->pid, $image);

                    if ($settings->get('deleteImg', FALSE) && !$cdn->is_offload_enabled())
                        $storage->delete_image($image->pid);

                    $mapper->destroy($image->pid);
                }
            }
        }

        return $this;
    }
}