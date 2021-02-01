<?php

/**
 * Class A_NextGen_Basic_Thumbnail_Form
 * @mixin C_Form
 * @adapts I_Form for photocrati-nextgen_basic_thumbnails context
 */
class A_NextGen_Basic_Thumbnail_Form extends Mixin_Display_Type_Form
{
	function get_display_type_name()
	{
		return NGG_BASIC_THUMBNAILS;
	}

	/**
	 * Enqueues static resources required by this form
	 */
	function enqueue_static_resources()
	{
		$this->object->enqueue_style(
			'nextgen_basic_thumbnails_settings',
			$this->object->get_static_url('photocrati-nextgen_basic_gallery#thumbnails/nextgen_basic_thumbnails_settings.css')
        );

		$this->object->enqueue_script(
			'nextgen_basic_thumbnails_settings',
			$this->object->get_static_url('photocrati-nextgen_basic_gallery#thumbnails/nextgen_basic_thumbnails_settings.js'),
			array('jquery.nextgen_radio_toggle')
		);
	}

	/**
     * Returns a list of fields to render on the settings page
     */
    function _get_field_names()
    {
        return array(
            'thumbnail_override_settings',
            'nextgen_basic_thumbnails_images_per_page',
            'nextgen_basic_thumbnails_number_of_columns',
            'ajax_pagination',
            'nextgen_basic_thumbnails_hidden',
            'nextgen_basic_thumbnails_imagebrowser_effect',
            'nextgen_basic_thumbnails_show_slideshow_link',
            'nextgen_basic_thumbnails_slideshow_link_text',
            'display_view',
            'nextgen_basic_templates_template'
            
        );
    }

    /**
     * Renders the images_per_page settings field
     *
     * @param C_Display_Type $display_type
     * @return string
     */
    function _render_nextgen_basic_thumbnails_images_per_page_field($display_type)
    {
        return $this->_render_number_field(
            $display_type,
            'images_per_page',
            __('Images per page', 'nggallery'),
            $display_type->settings['images_per_page'],
            __('0 will display all images at once', 'nggallery'),
            FALSE,
            '# of images',
            0
        );
    }

    /**
     * Renders the number_of_columns settings field
     *
     * @param C_Display_Type $display_type
     * @return string
     */
    function _render_nextgen_basic_thumbnails_number_of_columns_field($display_type)
    {
        return $this->_render_number_field(
            $display_type,
            'number_of_columns',
            __('Number of columns to display', 'nggallery'),
            $display_type->settings['number_of_columns'],
            '',
            FALSE,
            __('# of columns', 'nggallery'),
            0
        );
    }

    /**
     * Renders the 'Add hidden images' settings field
     *
     * @param C_Display_Type $display_type
     * @return string
     */
    function _render_nextgen_basic_thumbnails_hidden_field($display_type)
    {
        return $this->_render_radio_field(
            $display_type,
            'show_all_in_lightbox',
            __('Add Hidden Images', 'nggallery'),
            $display_type->settings['show_all_in_lightbox'],
            __('If pagination is used this option will show all images in the modal window (Thickbox, Lightbox etc.) This increases page load.', 'nggallery')
        );
    }

    function _render_nextgen_basic_thumbnails_imagebrowser_effect_field($display_type)
    {
        return $this->_render_radio_field(
            $display_type,
            'use_imagebrowser_effect',
            __('Use imagebrowser effect', 'nggallery'),
            $display_type->settings['use_imagebrowser_effect'],
            __('When active each image in the gallery will link to an imagebrowser display and lightbox effects will not be applied.', 'nggallery')
        );
    }

    /**
     * Renders the show_slideshow_link settings field
     *
     * @param C_Display_Type $display_type
     * @return string
     */
    function _render_nextgen_basic_thumbnails_show_slideshow_link_field($display_type)
    {
        return $this->_render_radio_field(
            $display_type,
            'show_slideshow_link',
            __('Show slideshow link', 'nggallery'),
            $display_type->settings['show_slideshow_link']
        );
    }

    /**
     * Renders the slideshow_link_text settings field
     *
     * @param C_Display_Type $display_type
     * @return string
     */
    function _render_nextgen_basic_thumbnails_slideshow_link_text_field($display_type)
    {
        return $this->_render_text_field(
            $display_type,
            'slideshow_link_text',
            __('Slideshow link text', 'nggallery'),
            $display_type->settings['slideshow_link_text'],
            '',
            !empty($display_type->settings['show_slideshow_link']) ? FALSE : TRUE
        );
    }
}