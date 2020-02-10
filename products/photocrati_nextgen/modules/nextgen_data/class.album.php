<?php

/**
 * Class C_Album
 * @mixin Mixin_NextGen_Album_Instance_Methods
 * @implements I_Album
 */
class C_Album extends C_DataMapper_Model
{
    var $_mapper_interface = 'I_Album_Mapper';


    function define($properties=array(), $mapper=FALSE, $context=FALSE)
    {
        parent::define($mapper, $properties, $context);
        $this->add_mixin('Mixin_NextGen_Album_Instance_Methods');
        $this->implement('I_Album');
    }


    /**
     * Instantiates an Album object
     * @param array $properties
     * @param C_Album_Mapper|bool $mapper (optional)
     * @param string|bool $context (optional)
     */
	function initialize($properties=array(), $mapper=FALSE, $context=FALSE)
	{
		if (!$mapper)
			$mapper = $this->get_registry()->get_utility($this->_mapper_interface);
		parent::initialize($mapper, $properties);
	}
}

/**
 * Provides instance methods for the album
 */
class Mixin_NextGen_Album_Instance_Methods extends Mixin
{
    function validation()
    {
        $this->validates_presence_of('name');
        $this->validates_numericality_of('previewpic');
        return $this->object->is_valid();
    }

    /**
     * Gets all galleries associated with the album
     * @param array|bool $models (optional)
     * @return array
     */
    function get_galleries($models=FALSE)
    {
        $retval = array();
        $mapper = C_Gallery_Mapper::get_instance();
        $gallery_key = $mapper->get_primary_key_column();
        $retval = $mapper->find_all(array("{$gallery_key} IN %s", $this->object->sortorder), $models);
        return $retval;
    }
}