<?php

/**
 * Provides the "Display Tab" for the Attach To Post interface/controller
 * @see C_Attach_Controller adds this mixin
 */
class Mixin_Attach_To_Post_Display_Tab extends Mixin
{
	function _display_type_list_sort($type_1, $type_2)
	{
		$order_1 = $type_1->view_order;
		$order_2 = $type_2->view_order;
		
		if ($order_1 == null) {
			$order_1 = NGG_DISPLAY_PRIORITY_BASE;
		}
		
		if ($order_2 == null) {
			$order_2 = NGG_DISPLAY_PRIORITY_BASE;
		}
		
		if ($order_1 > $order_2) {
			return 1;
		}
		
		if ($order_1 < $order_2) {
			return -1;
		}
		
		return 0;
	}


	/**
	 * Gets a list of tabs to render for the "Display" tab
	 */
	function _get_display_tabs()
	{
		// The ATP requires more memmory than some applications, somewhere around 60MB.
		// Because it's such an important feature of NextGEN Gallery, we temporarily disable
		// any memory limits
        if (!extension_loaded('suhosin')) @ini_set('memory_limit', -1);

		return array(
			'choose_display_tab' 	=> $this->object->_render_choose_display_tab(),
			'display_settings_tab' 	=> $this->object->_render_display_settings_tab(),
			'preview_tab'			=> $this->object->_render_preview_tab()
		);
	}


	/**
	 * Renders the accordion tab, "What would you like to display?"
	 */
	function _render_choose_display_tab()
	{
		return array(
			'id'			=> 'choose_display',
			'title'			=>	__('Choose Display', 'nggallery'),
			'content'		=>	$this->object->_render_display_source_tab_contents() . $this->object->_render_display_type_tab_contents()
		);
	}
	

	/**
	 * Renders the contents of the source tab
	 * @return string
	 */
	function _render_display_source_tab_contents()
	{
		return $this->object->render_partial('photocrati-attach_to_post#display_tab_source', array(
			'i18n'	=>	array(),
		),TRUE);
	}


	/**
	 * Renders the contents of the display type tab
	 */
	function _render_display_type_tab_contents()
	{
		return $this->object->render_partial('photocrati-attach_to_post#display_tab_type', array(), TRUE);
	}


	/**
	 * Renders the display settings tab for the Attach to Post interface
	 * @return array
	 */
	function _render_display_settings_tab()
	{
		return array(
			'id'      => 'display_settings_tab',
			'title'   => __('Customize Display Settings', 'nggallery'),
			'content' => $this->object->_render_display_settings_contents()
		);
	}

	/**
	 * If editing an existing displayed gallery, retrieves the name
	 * of the display type
	 * @return string
	 */
	function _get_selected_display_type_name()
	{
		$retval = '';

		if ($this->object->_displayed_gallery)
			$retval = $this->object->_displayed_gallery->display_type;

		return $retval;
	}


	/**
	 * Is the displayed gallery that's being edited using the specified display
	 * type?
	 * @param string $name	name of the display type
	 * @return bool
	 */
	function is_displayed_gallery_using_display_type($name)
	{
		$retval = FALSE;

		if ($this->object->_displayed_gallery) {
			$retval = $this->object->_displayed_gallery->display_type == $name;
		}

		return $retval;
	}


	/**
	 * Renders the contents of the display settings tab
	 * @return string
	 */
	function _render_display_settings_contents()
	{
		$retval = array();

		// Get all display setting forms
        $form_manager = C_Form_Manager::get_instance();
		$forms		  = $form_manager->get_forms(
			NGG_DISPLAY_SETTINGS_SLUG, TRUE
		);

		// Display each form
		foreach ($forms as $form) {

			// Enqueue the form's static resources
			$form->enqueue_static_resources();

			// Determine which classes to use for the form's "class" attribute
			$model = $form->get_model();
			$current = $this->object->is_displayed_gallery_using_display_type($model->name);
			$css_class =  $current ? 'display_settings_form' : 'display_settings_form hidden';
			$defaults = $model->settings;

			// If this form is used to provide the display settings for the current
			// displayed gallery, then we need to override the forms settings
			// with the displayed gallery settings
			if ($current) {
				$settings = $this->array_merge_assoc(
					$model->settings,
					$this->object->_displayed_gallery->display_settings,
					TRUE
				);
				
				$model->settings = $settings;
			}
			
			// Output the display settings form
			$retval[] = $this->object->render_partial('photocrati-attach_to_post#display_settings_form', array(
				'settings'          => $form->render(),
				'display_type_name' => $model->name,
				'css_class'         => $css_class,
                'defaults'          => $defaults
			), TRUE);
		}

		// In addition, we'll render a form that will be displayed when no
		// display type has been selected in the Attach to Post interface
		// Render the default "no display type selected" view
		$css_class = $this->object->_get_selected_display_type_name() ?
			'display_settings_form hidden' : 'display_settings_form';
		$retval[] = $this->object->render_partial('photocrati-attach_to_post#no_display_type_selected', array(
			'no_display_type_selected'	=>	__('No display type selected', 'nggallery'),
			'css_class'					=>	$css_class

		), TRUE);

		// Return all display setting forms
		return implode("\n", $retval);
	}

	/**
	 * Renders the tab used to preview included images
	 * @return array
	 */
	function _render_preview_tab()
	{
		return array(
			'id'			=> 'preview_tab',
			'title'			=>	__('Sort or Exclude Images', 'nggallery'),
			'content'		=>	$this->object->_render_preview_tab_contents()
		);
	}

	/**
	 * Renders the contents of the "Preview" tab.
	 * @return string
	 */
	function _render_preview_tab_contents()
	{
		return $this->object->render_partial('photocrati-attach_to_post#preview_tab', array(), TRUE);
	}
}
