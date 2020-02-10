<?php

class A_NextGen_Block_Ajax extends Mixin
{
    function get_image_action()
    {
        $retval = array('success' => FALSE);

        // TODO: Should this method check for a valid nonce? Should it require authentication?
        if (($image = $this->param('image_id'))) {
            if (($image = C_Image_Mapper::get_instance()->find($image))) {
                $storage = C_Gallery_Storage::get_instance();
                $image->thumbnail_url   = $storage->get_image_url($image, 'thumb');
                $image->image_url       = $storage->get_image_url($image, 'full');
                $retval['image']        = $image;
                $retval['success']      = TRUE;
            }
        }

        return $retval;
    }
}