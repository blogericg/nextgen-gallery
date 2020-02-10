<?php

/**
 * Class A_NextGen_Basic_Tagcloud
 * @mixin C_Display_Type
 * @adapts I_Display_Type
 */
class A_NextGen_Basic_Tagcloud extends Mixin
{
    function validation()
    {
        if ($this->object->name == NGG_BASIC_TAGCLOUD) {
            $this->object->validates_presence_of('gallery_display_type');
        }

        // If we have a "gallery_display_type", we don't need a "display_type" setting
        if (isset($this->object->settings['display_type']) && isset($this->object->settings['gallery_display_type'])) {
            unset($this->object->settings['display_type']);
        }

        return $this->call_parent('validation');
    }
}
