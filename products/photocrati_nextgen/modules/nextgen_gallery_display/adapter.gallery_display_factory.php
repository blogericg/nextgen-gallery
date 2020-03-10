<?php

/**
 * Class A_Gallery_Display_Factory
 * @mixin C_Component_Factory
 * @adapts I_Component_Factory
 */
class A_Gallery_Display_Factory extends Mixin
{
	/**
	 * Instantiates a Display Type
     * @param array|stdClass|C_DataMapper_Model $properties (optional)
	 * @param C_Display_Type_Mapper $mapper (optional)
	 * @param string|array|FALSE $context (optional)
	 */
	function display_type($properties=array(), $mapper=FALSE, $context=FALSE)
	{
		return new C_Display_Type($properties, $mapper, $context);
	}

	/**
	 * Instantiates a Displayed Gallery
     * @param array|stdClass|C_DataMapper_Model $properties (optional)
	 * @param C_Displayed_Gallery_Mapper $mapper (optional)
	 * @param string|array|FALSE $context (optional)
	 */
	function displayed_gallery($properties=array(), $mapper=FALSE, $context=FALSE)
	{
		return new C_Displayed_Gallery($properties, $mapper, $context);
	}
}