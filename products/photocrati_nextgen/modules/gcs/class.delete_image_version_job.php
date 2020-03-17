<?php

class C_CDN_GCS_Delete_Image_Version_Job extends \ReactrIO\Background\Job
{
    public function run()
    {
        $cdn  = C_CDN_Providers::get_current();
        $name = $this->get_dataset();

        $this->logOutput(
            sprintf(__("Removing old version of image: %s", 'nggallery'), $name)
        );

        $cdn->delete_version($name);

        return $this;
    }
}