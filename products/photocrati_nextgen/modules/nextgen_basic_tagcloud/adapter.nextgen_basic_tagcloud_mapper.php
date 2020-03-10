<?php

/**
 * Class A_NextGen_Basic_TagCloud_Mapper
 *
 * @mixin C_Display_Type_Mapper
 * @adapts I_Display_Type_Mapper
 */
class A_NextGen_Basic_TagCloud_Mapper extends Mixin
{
    function set_defaults($entity)
    {
        $this->call_parent('set_defaults', $entity);

        if (isset($entity->name) && $entity->name == NGG_BASIC_TAGCLOUD)
        {
            if (isset($entity->display_settings) && is_array($entity->display_settings) && isset($entity->display_settings['display_type'])) {
                if (!isset($entity->display_settings['gallery_display_type'])) $entity->display_settings['gallery_display_type'] = $entity->display_settings['display_type'];
                unset($entity->display_settings['display_type']);
            }
            $this->object->_set_default_value($entity, 'settings', 'gallery_display_type', NGG_BASIC_THUMBNAILS);
            $this->object->_set_default_value($entity, 'settings', 'number', 45);
            $this->object->_set_default_value($entity, 'settings', 'ngg_triggers_display', 'never');
        }
    }
}