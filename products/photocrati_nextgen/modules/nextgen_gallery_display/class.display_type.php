<?php

/**
 * A Display Type is a component which renders a collection of images
 * in a "gallery".
 *
 * Properties:
 * - entity_types (gallery, album)
 * - name		 (nextgen_basic-thumbnails)
 * - title		 (NextGEN Basic Thumbnails)
 * - aliases	[basic_thumbnail, basic_thumbnails]
 *
 * @mixin Mixin_Display_Type_Validation
 * @mixin Mixin_Display_Type_Instance_Methods
 * @implements I_Display_Type
 */
class C_Display_Type extends C_DataMapper_Model
{
	var $_mapper_interface = 'I_Display_Type_Mapper';
	var $__settings = array();

	function define($properties=array(), $mapper=FALSE, $context=FALSE)
	{
		parent::define($mapper, $properties, $context);
		$this->add_mixin('Mixin_Display_Type_Validation');
		$this->add_mixin('Mixin_Display_Type_Instance_Methods');
		$this->implement('I_Display_Type');
	}

	/**
	 * Initializes a display type with properties
     * @param array|stdClass|C_Display_Type $properties
	 * @param FALSE|C_Display_Type_Mapper $mapper
	 * @param FALSE|string|array $context
	 */
	function initialize($properties=array(), $mapper=FALSE, $context=FALSE)
	{
		// If no mapper was specified, then get the mapper
		if (!$mapper) $mapper = $this->get_registry()->get_utility($this->_mapper_interface);

		// Construct the model
		parent::initialize($mapper, $properties);
	}


	/**
	 * Allows a setting to be retrieved directly, rather than through the
	 * settings property
	 * @param string $property
	 * @return mixed
	 */
	function &__get($property)
	{
		if ($property == 'settings')
        {
        	if (isset($this->_stdObject->settings))
        	{
            //$this->__settings = array_merge($this->_stdObject->settings, $this->__settings);
        	}
        	
        	return $this->_stdObject->settings;
        }
			
		if (isset($this->_stdObject->settings[$property]) && $this->_stdObject->settings[$property] != NULL)
		{
			return $this->_stdObject->settings[$property];
		}
		else {
    	return parent::__get($property);
    }
	}

  function &__set($property, $value)
	{
		if ($property == 'settings')
            $retval = $this->_stdObject->settings = $value;
        else {
            $retval = $this->_stdObject->settings[$property] = $value;
        }
        return $retval;
	}
	
	function __isset($property_name)
	{
		if ($property_name == 'settings')
			return isset($this->_stdObject->settings);
			
		return isset($this->_stdObject->settings[$property_name]) || parent::__isset($property_name);
	}
}

class Mixin_Display_Type_Validation extends Mixin
{
	function validation()
	{
		$this->object->validates_presence_of('entity_types');
		$this->object->validates_presence_of('name');
		$this->object->validates_presence_of('title');

		return $this->object->is_valid();
	}
}

/**
 * Provides methods available for class instances
 */
class Mixin_Display_Type_Instance_Methods extends Mixin
{
	/**
	 * Determines if this display type is compatible with a displayed gallery source
	 * @param stdClass $source
	 * @return bool
	 */
	function is_compatible_with_source($source)
	{
		return C_Displayed_Gallery_Source_Manager::get_instance()->is_compatible($source, $this);
	}
	
	function get_order()
	{
		return NGG_DISPLAY_PRIORITY_BASE;
	}
}
