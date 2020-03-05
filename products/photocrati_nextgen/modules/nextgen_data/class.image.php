<?php


class Mixin_NextGen_Gallery_Image_Validation extends Mixin
{
	function validation()
	{
		// Additional checks...
		if (isset($this->object->description)) {
			$this->object->description = M_NextGen_Data::strip_html($this->object->description, TRUE);
		}

		if (isset($this->object->alttext)) {
			$this->object->alttext = M_NextGen_Data::strip_html($this->object->alttext, TRUE);
		}

		$this->validates_presence_of('galleryid', 'filename', 'alttext', 'exclude', 'sortorder', 'imagedate');
        $this->validates_numericality_of('galleryid');
        $this->validates_numericality_of($this->id());
		$this->validates_numericality_of('sortorder');

		$this->validates_length_of(
		    'filename',
            185,
            '<=',
            __('Image filenames may not be longer than 185 characters in length', 'nggallery')
        );

		return $this->object->is_valid();
	}
}

/**
 * Model for NextGen Gallery Images
 * @mixin Mixin_NextGen_Gallery_Image_Validation
 * @implements I_Image
 */
class C_Image extends C_DataMapper_Model
{
	var $_mapper_interface = 'I_Image_Mapper';

    function define($properties = array(), $mapper = FALSE, $context = FALSE)
    {
        parent::define($mapper, $properties, $context);
		$this->add_mixin('Mixin_NextGen_Gallery_Image_Validation');
        $this->implement('I_Image');
    }

	/**
	 * Instantiates a new model
	 * @param array|stdClass $properties (optional)
	 * @param C_Image_Mapper|false $mapper (optional)
	 * @param string|false $context (optional)
	 */
	function initialize($properties = array(), $mapper = FALSE, $context = FALSE)
	{
		if (!$mapper)
			$mapper = $this->get_registry()->get_utility($this->_mapper_interface);
		parent::initialize($mapper, $properties);
	}

	/**
	 * Returns the model representing the gallery associated with this image
     * @param object|false $model (optional)
	 * @return C_Gallery|object
	 */
    function get_gallery($model = FALSE)
    {
        return C_Gallery_Mapper::get_instance()->find($this->galleryid, $model);
    }
}