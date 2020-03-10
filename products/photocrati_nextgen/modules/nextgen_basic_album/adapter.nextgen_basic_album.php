<?php

/**
 * Provides validation for NextGen Basic Albums
 * @mixin C_Album_Mapper
 * @adapts I_Album_Mapper
 */
class A_NextGen_Basic_Album extends Mixin
{
    function validation()
    {
        $ngglegacy_albums = array(
            NGG_BASIC_COMPACT_ALBUM,
            NGG_BASIC_EXTENDED_ALBUM
        );
        if (in_array($this->object->name, $ngglegacy_albums)) {
            $this->object->validates_presence_of('gallery_display_type');
            $this->object->validates_numericality_of('galleries_per_page');
        }

        return $this->call_parent('validation');
    }

    function get_order()
    {
        return NGG_DISPLAY_PRIORITY_BASE + NGG_DISPLAY_PRIORITY_STEP;
    }
}
