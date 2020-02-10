<?php

/**
 * Class A_NextGen_Basic_SinglePic_Mapper
 * @mixin C_Display_Type_Mapper
 * @adapts I_Display_Type_Mapper
 */
class A_NextGen_Basic_SinglePic_Mapper extends Mixin
{
	/**
	 * Sets default values for SinglePic settings
	 * @param stdClass|C_DataMapper_Model $entity
	 */
	function set_defaults($entity)
	{
        $this->call_parent('set_defaults', $entity);

		if (isset($entity->name) && $entity->name == NGG_BASIC_SINGLEPIC) {
			$this->object->_set_default_value($entity, 'settings', 'width', '');
			$this->object->_set_default_value($entity, 'settings', 'height', '');
			$this->object->_set_default_value($entity, 'settings', 'mode', '');
			$this->object->_set_default_value($entity, 'settings', 'display_watermark', 0);
			$this->object->_set_default_value($entity, 'settings', 'display_reflection', 0);
			$this->object->_set_default_value($entity, 'settings', 'float', '');
			$this->object->_set_default_value($entity, 'settings', 'link', '');
            $this->object->_set_default_value($entity, 'settings', 'link_target', '_blank');
			$this->object->_set_default_value($entity, 'settings', 'quality', 100);
			$this->object->_set_default_value($entity, 'settings', 'crop', 0);
            $this->object->_set_default_value($entity, 'settings', 'template', '');

            // Part of the pro-modules
            $this->object->_set_default_value($entity, 'settings', 'ngg_triggers_display', 'never');
		}
	}
}
