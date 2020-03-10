<?php

/**
 * Class A_NextGen_Basic_Gallery_Validation
 * @mixin C_Display_Type
 * @adapts I_Display_Type
 */
class A_NextGen_Basic_Gallery_Validation extends Mixin
{
    function validation()
    {
        if ($this->object->name == NGG_BASIC_THUMBNAILS) {
            $this->object->validates_presence_of('thumbnail_width');
            $this->object->validates_presence_of('thumbnail_height');
            $this->object->validates_numericality_of('thumbnail_width');
            $this->object->validates_numericality_of('thumbnail_height');
            $this->object->validates_numericality_of('images_per_page');
        }
        else if ($this->object->name == NGG_BASIC_SLIDESHOW) {
            $this->object->validates_presence_of('gallery_width');
            $this->object->validates_presence_of('gallery_height');
            $this->object->validates_numericality_of('gallery_width');
            $this->object->validates_numericality_of('gallery_height');
        }

        return $this->call_parent('validation');
    }
}