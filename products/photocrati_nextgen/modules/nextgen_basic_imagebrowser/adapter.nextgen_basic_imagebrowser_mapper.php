<?php

/**
 * Class A_NextGen_Basic_ImageBrowser_Mapper
 * @mixin C_Display_Type_Mapper
 * @adapts I_Display_Type_Mapper
 */
class A_NextGen_Basic_ImageBrowser_Mapper extends Mixin
{
    function set_defaults($entity)
    {
        $this->call_parent('set_defaults', $entity);

        if (isset($entity->name) && $entity->name == NGG_BASIC_IMAGEBROWSER)
        {
  
            $default_template = isset($entity->settings["template"]) ? 'default' : 'default-view.php';
            $this->object->_set_default_value($entity, 'settings', 'display_view', $default_template);

            $this->object->_set_default_value($entity, 'settings', 'template', '');
            $this->object->_set_default_value($entity, 'settings', 'ajax_pagination', '1');

            // Part of the pro-modules
            $this->object->_set_default_value($entity, 'settings', 'ngg_triggers_display', 'never');
            
        }
    }
}