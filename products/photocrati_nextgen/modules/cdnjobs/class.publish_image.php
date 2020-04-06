<?php

class C_CDN_Publish_Image_Job extends \ReactrIO\Background\Job
{
    function run()
    {
        $data  = $this->get_dataset();
        $id    = $data['id'];
        $sizes = $data['size'];

        if (is_string($sizes) && $sizes === 'all')
        {
            $sizes = C_Gallery_Storage::get_instance()->get_image_sizes($id);
            if (!in_array('backup', $sizes))
                $sizes[] = 'backup';
        }

        if (is_array($sizes))
        {
            foreach ($sizes as $size) {
                \ReactrIO\Background\Job::create(
                    sprintf(__("Publishing size %s image %d to CDN", 'nggallery'), $size, $id),
                    'cdn_publish_image',
                    ['id' => $id, 'size' => $size]
                )->save('cdn');
            }
        }
        else {
            $storage = C_Gallery_Storage::get_instance();
            $cdn     = C_CDN_Providers::get_current();

            if ($cdn->upload($id, $sizes))
            {
                $this->logOutput(sprintf(__("Uploaded '%s' size for %s", 'nggallery'), $sizes, $id));

                if ($cdn->is_offload_enabled())
                    unlink($storage->get_image_abspath($id, $sizes));
            }
        }

        return $this;
    }
}