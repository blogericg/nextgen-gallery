<?php

/**
 * Class A_NextGen_Data_Factory
 * @mixin C_Component_Factory
 * @adapts I_Component_Factory
 */
class A_NextGen_Data_Factory extends Mixin
{
	function gallery($properties=array(), $mapper=FALSE, $context=FALSE)
    {
        return new C_Gallery($properties, $mapper, $context);
    }


    function gallery_image($properties=array(), $mapper=FALSE, $context=FALSE)
    {
        return new C_Image($properties, $mapper, $context);
    }


    function image($properties=array(), $mapper=FALSE, $context=FALSE)
    {
        return new C_Image($properties, $mapper, $context);
    }


    function album($properties=array(), $mapper=FALSE, $context=FALSE)
    {
        return new C_Album($properties, $mapper, $context);
    }


	function gallery_storage($context=FALSE)
	{
		return new C_Gallery_Storage($context);
	}

	function extra_fields($properties=array(), $mapper=FALSE, $context=FALSE)
	{
		return new C_Datamapper_Model($mapper, $properties, $context);
	}


	function gallerystorage($context=FALSE)
	{
		return $this->object->gallery_storage($context);
	}
}