<?php

/**
 * Class C_DataMapper_Model
 * @mixin Mixin_Validation
 * @mixin Mixin_DataMapper_Model_Instance_Methods
 * @mixin Mixin_DataMapper_Model_Validation
 * @implements I_DataMapper_Model
 */
class C_DataMapper_Model extends C_Component /*implements JsonSerializable - PHP 5.4 only */
{
	var $_mapper;
	var $_stdObject;

	/**
	 * Define the model
	 */
	function define($mapper=NULL, $properties=array(), $context=FALSE)
	{
		parent::define($context);
		$this->add_mixin('Mixin_Validation');
        $this->add_mixin('Mixin_DataMapper_Model_Instance_Methods');
		$this->add_mixin('Mixin_DataMapper_Model_Validation');
		$this->implement('I_DataMapper_Model');
	}

	/**
	 * Creates a new entity for the specified mapper
	 * @param C_DataMapper_Driver_Base $mapper (optional)
	 * @param array|object|bool $properties (optional)
	 */
	function initialize($mapper=NULL, $properties=FALSE)
	{
		$this->_mapper = $mapper;
		$this->_stdObject = $properties ? (object)$properties  : new stdClass();
		parent::initialize();
		if (!$this->has_default_values()) {
            $this->set_defaults();
            $this->_stdObject->__defaults_set = TRUE;
        }
	}

	function jsonSerialize()
	{
		return $this->get_entity();
	}

    function has_default_values()
    {
        return isset($this->_stdObject->__defaults_set) && $this->_stdObject->__defaults_set == TRUE;
    }

	/**
	 * Gets the data mapper for the entity
	 * @return C_DataMapper_Driver_Base
	 */
	function get_mapper()
	{
		return $this->_mapper;
	}

	/**
	 * Gets a property of the model
     * @param string $property
     * @return mixed
	 */
    function &__get($property)
	{
		if (isset($this->_stdObject->$property)) {
			$retval = &$this->_stdObject->$property;
			return $retval;
		}
		else {
			// We need to assign NULL to a variable first, since only
			// variables can be returned by reference
			$retval = NULL;
			return $retval;
		}
	}

	/**
	 * Sets a property for the model
     * @param mixed $property
     * @param mixed $value
     * @return mixed $value
	 */
    function &__set($property, $value)
	{
        $retval = $this->_stdObject->$property= $value;
		return $retval;
	}

	function __isset($property_name)
	{
		return isset($this->_stdObject->$property_name);
	}

	/**
	 * Saves the entity
	 * @param array $updated_attributes
     * @return int|bool Object ID or false upon failure
	 */
	function save($updated_attributes=array())
	{
		$this->update_attributes($updated_attributes);
		return $this->get_mapper()->save($this->get_entity());
	}

	/**
	 * Updates the attributes for an object
     * @param array $array (optional)
	 */
	function update_attributes($array=array())
	{
		foreach ($array as $key => $value) $this->_stdObject->$key = $value;
	}

	/**
	 * Sets the default values for this model
	 */
	function set_defaults()
	{
		$mapper = $this->get_mapper();
		if ($mapper->has_method('set_defaults'))
			$mapper->set_defaults($this);
	}

	/**
	 * Destroys or deletes the entity
	 */
	function destroy()
	{
		return $this->get_mapper()->destroy($this->_stdObject);
	}


	/**
	 * Determines whether the object is new or existing
	 * @return bool
	 */
	function is_new()
	{
		return $this->id() ? FALSE: TRUE;
	}

	/**
	 * Gets/sets the primary key
     * @param null|int|string $value (optional)
     * @return mixed
	 */
	function id($value=NULL)
	{
		$key = $this->get_mapper()->get_primary_key_column();
		if ($value) {
			$this->__set($key, $value);

		}
		return $this->__get($key);
	}
}

/**
 * This mixin should be overwritten by other modules
 */
class Mixin_DataMapper_Model_Validation extends Mixin
{
	function validation()
	{
		return $this->object->is_valid();
	}
}

class Mixin_DataMapper_Model_Instance_Methods extends Mixin
{
    /**
     * Returns the associated entity
     */
    function &get_entity()
    {
        return $this->object->_stdObject;
    }
}
