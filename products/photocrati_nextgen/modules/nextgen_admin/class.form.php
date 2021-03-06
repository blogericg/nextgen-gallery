<?php

/**
 * Class C_Form
 * @mixin Mixin_Form_Instance_Methods
 * @mixin Mixin_Form_Field_Generators
 * @implements I_Form
 */
class C_Form extends C_MVC_Controller
{
	static $_instances = array();
	var $page = NULL;

	/**
	 * Gets an instance of a form
	 * @param string $context
	 * @return C_Form
	 */
	static function &get_instance($context)
	{
		if (!isset(self::$_instances[$context])) {
			$klass = get_class();
			self::$_instances[$context] = new $klass($context);
		}
		return self::$_instances[$context];
	}

	/**
	 * Defines the form
	 * @param string|bool $context (optional)
	 */
	function define($context = FALSE)
	{
		parent::define($context);
		$this->add_mixin('Mixin_Form_Instance_Methods');
		$this->add_mixin('Mixin_Form_Field_Generators');
		$this->implement('I_Form');
	}
}

class Mixin_Form_Instance_Methods extends Mixin
{
	/**
	 * Enqueues any static resources required by the form
	 */
	function enqueue_static_resources()
	{
	}

	/**
	 * Gets a list of fields to render
	 * @return array
	 */
	function _get_field_names()
	{
		return array();
	}

	function get_id()
	{
		return $this->object->context;
	}

	function get_title()
	{
		return $this->object->context;
	}

	/**
	 * Saves the form/model
	 * @param array $attributes
	 * @return bool
	 */
	function save_action($attributes=array())
	{
		if (!$attributes) $attributes = array();
		if ($this->object->has_method('get_model') && $this->object->get_model()) {
			return $this->object->get_model()->save($attributes);
		}
		else return TRUE;
	}

	/**
	 * Returns the rendered form
     * @param bool $wrap (optional) Default = true
     * @return string
	 */
	function render($wrap = TRUE)
	{
		$fields = array();
		foreach ($this->object->_get_field_names() as $field) {
			$method = "_render_{$field}_field";
			if ($this->object->has_method($method)) {
				$fields[] = $this->object->$method($this->object->get_model());
			}
		}

		return $this->object->render_partial(
            'photocrati-nextgen_admin#form',
            array(
                'fields' => $fields,
                'wrap'   => $wrap
            ),
            TRUE
        );
	}

	function get_model()
	{
		return $this->object->page->has_method('get_model') ? $this->object->page->get_model() : NULL;
	}
}

/**
 * Provides some default field generators for forms to use
 */
class Mixin_Form_Field_Generators extends Mixin
{
	function _render_select_field($display_type, $name, $label, $options=array(), $value, $text = '', $hidden = FALSE)
    {
        return $this->object->render_partial(
            'photocrati-nextgen_admin#field_generator/nextgen_settings_field_select',
            array(
                'display_type_name' => $display_type->name,
                'name'    => $name,
                'label'   => $label,
                'options' => $options,
                'value'   => $value,
                'text'    => $text,
                'hidden'  => $hidden
            ),
            True
        );
    }

    function _render_radio_field($display_type, $name, $label, $value, $text = '', $hidden = FALSE)
    {
        return $this->object->render_partial(
            'photocrati-nextgen_admin#field_generator/nextgen_settings_field_radio',
            array(
                'display_type_name' => $display_type->name,
                'name'   => $name,
                'label'  => $label,
                'value'  => $value,
                'text'   => $text,
                'hidden' => $hidden
            ),
            True
        );
    }

    function _render_number_field($display_type,
                                  $name,
                                  $label,
                                  $value,
                                  $text = '',
                                  $hidden = FALSE,
                                  $placeholder = '',
                                  $min = NULL,
                                  $max = NULL)
    {
        return $this->object->render_partial(
            'photocrati-nextgen_admin#field_generator/nextgen_settings_field_number',
            array(
                'display_type_name' => $display_type->name,
                'name'  => $name,
                'label' => $label,
                'value' => $value,
                'text' => $text,
                'hidden' => $hidden,
                'placeholder' => $placeholder,
                'min' => $min,
                'max' => $max
            ),
            True
        );
    }

    function _render_text_field($display_type, $name, $label, $value, $text = '', $hidden = FALSE, $placeholder = '')
    {
        return $this->object->render_partial(
            'photocrati-nextgen_admin#field_generator/nextgen_settings_field_text',
            array(
                'display_type_name' => $display_type->name,
                'name'  => $name,
                'label' => $label,
                'value' => $value,
                'text' => $text,
                'hidden' => $hidden,
                'placeholder' => $placeholder
            ),
            True
        );
    }

    function _render_textarea_field($display_type, $name, $label, $value, $text = '', $hidden = FALSE, $placeholder = '')
    {
        return $this->object->render_partial(
            'photocrati-nextgen_admin#field_generator/nextgen_settings_field_textarea',
            array(
                'display_type_name' => $display_type->name,
                'name'  => $name,
                'label' => $label,
                'value' => $value,
                'text' => $text,
                'hidden' => $hidden,
                'placeholder' => $placeholder
            ),
            True
        );
    }

    function _render_color_field($display_type, $name, $label, $value, $text = '', $hidden = FALSE)
    {
        return $this->object->render_partial(
            'photocrati-nextgen_admin#field_generator/nextgen_settings_field_color',
            array(
                'display_type_name' => $display_type->name,
                'name'  => $name,
                'label' => $label,
                'value' => $value,
                'text' => $text,
                'hidden' => $hidden
            ),
            True
        );
    }

    /**
     * Renders a pair of fields for width and width-units (px, em, etc)
     *
     * @param C_Display_Type $display_type
     * @return string
     */
    function _render_width_and_unit_field($display_type)
    {
        return $this->object->render_partial(
            'photocrati-nextgen_admin#field_generator/nextgen_settings_field_width_and_unit',
            array(
                'display_type_name' => $display_type->name,
                'name' => 'width',
                'label' => __('Gallery width', 'nggallery'),
                'value' => $display_type->settings['width'],
                'text' => __('An empty or 0 setting will make the gallery full width', 'nggallery'),
                'placeholder' => __('(optional)', 'nggallery'),
                'unit_name' => 'width_unit',
                'unit_value' => $display_type->settings['width_unit'],
                'options' => array('px' => __('Pixels', 'nggallery'), '%' => __('Percent', 'nggallery'))
            ),
            TRUE
        );
    }

    function _get_aspect_ratio_options()
    {
        return array(
            'first_image' => __('First Image', 'nggallery'),
            'image_average' => __('Average', 'nggallery'),
            '1.5'   => '3:2 [1.5]',
            '1.333' => '4:3 [1.333]',
            '1.777' => '16:9 [1.777]',
            '1.6'   => '16:10 [1.6]',
            '1.85'  => '1.85:1 [1.85]',
            '2.39'  => '2.39:1 [2.39]',
            '1.81'  => '1.81:1 [1.81]',
            '1'     => '1:1 (Square) [1]'
        );
    }
}
