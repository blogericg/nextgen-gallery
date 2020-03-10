<?php

/**
 * Provides the display settings form for the NextGen Basic Slideshow
 * @mixin C_Form
 * @adapts I_Form for "photocrati-nextgen_basic_slideshow" context
 */
class A_NextGen_Basic_Slideshow_Form extends Mixin_Display_Type_Form
{
	function get_display_type_name()
	{
		return NGG_BASIC_SLIDESHOW;
	}

    function enqueue_static_resources()
    {
        $this->object->enqueue_script(
            'nextgen_basic_slideshow_settings-js',
            $this->get_static_url('photocrati-nextgen_basic_gallery#slideshow/nextgen_basic_slideshow_settings.js'),
            array('jquery.nextgen_radio_toggle')
        );
    }

    /**
     * Returns a list of fields to render on the settings page
     */
    function _get_field_names()
    {
        return array(
            'nextgen_basic_slideshow_gallery_dimensions',
            'nextgen_basic_slideshow_autoplay',
            'nextgen_basic_slideshow_pauseonhover',
            'nextgen_basic_slideshow_arrows',
            'nextgen_basic_slideshow_transition_style',
            'nextgen_basic_slideshow_interval',
            'nextgen_basic_slideshow_transition_speed',
            'nextgen_basic_slideshow_show_thumbnail_link',
            'nextgen_basic_slideshow_thumbnail_link_text',
            'display_view',
        );
    }

    /**
     * Renders the autoplay field for new Slick.js slideshow
     *
     * @param C_Display_Type $display_type
     * @return string
     */
    function _render_nextgen_basic_slideshow_autoplay_field($display_type)
    {
        return $this->_render_radio_field(
            $display_type,
            'autoplay',
            __('Autoplay?', 'nggallery'),
            $display_type->settings['autoplay']
        );
    }

    /**
     * Renders the Pause on Hover field for new Slick.js slideshow
     *
     * @param C_Display_Type $display_type
     * @return string
     */
    function _render_nextgen_basic_slideshow_pauseonhover_field($display_type)
    {
        return $this->_render_radio_field(
            $display_type,
            'pauseonhover',
            __('Pause on Hover?', 'nggallery'),
            $display_type->settings['pauseonhover']
        );
    }

    /**
     * Renders the arrows field for new Slick.js slideshow
     *
     * @param C_Display_Type $display_type
     * @return string
     */
    function _render_nextgen_basic_slideshow_arrows_field($display_type)
    {
        return $this->_render_radio_field(
            $display_type,
            'arrows',
            __('Show Arrows?', 'nggallery'),
            $display_type->settings['arrows']
        );
    }

    /**
     * Renders the effect field for new Slick.js slideshow
     *
     * @param C_Display_Type $display_type
     * @return string
     */
    function _render_nextgen_basic_slideshow_transition_style_field($display_type)
    {
        return $this->_render_select_field(
            $display_type,
            'transition_style',
            __('Transition Style', 'nggallery'),
            array(
            'slide' => 'Slide',
            'fade' => 'Fade'
            ),
            $display_type->settings['transition_style'],
            '',
            FALSE
        );
    }

    /**
     * Renders the interval field for new Slick.js slideshow
     *
     * @param C_Display_Type $display_type
     * @return string
     */
    function _render_nextgen_basic_slideshow_interval_field($display_type)
    {
        return $this->_render_number_field(
            $display_type,
            'interval',
            __('Interval', 'nggallery'),
            $display_type->settings['interval'],
            '',
            FALSE,
            __('Milliseconds', 'nggallery'),
            1
        );
    }

    /**
     * Renders the interval field for new Slick.js slideshow
     *
     * @param C_Display_Type $display_type
     * @return string
     */
    function _render_nextgen_basic_slideshow_transition_speed_field($display_type)
    {
        return $this->_render_number_field(
            $display_type,
            'transition_speed',
            __('Transition Speed', 'nggallery'),
            $display_type->settings['transition_speed'],
            '',
            FALSE,
            __('Milliseconds', 'nggallery'),
            1
        );
    }

    function _render_nextgen_basic_slideshow_gallery_dimensions_field($display_type)
    {
        return $this->render_partial('photocrati-nextgen_basic_gallery#slideshow/nextgen_basic_slideshow_settings_gallery_dimensions', array(
            'display_type_name' => $display_type->name,
            'gallery_dimensions_label' => __('Maximum dimensions', 'nggallery'),
            'gallery_dimensions_tooltip' => __('Certain themes may allow images to flow over their container if this setting is too large', 'nggallery'),
            'gallery_width' => $display_type->settings['gallery_width'],
            'gallery_height' => $display_type->settings['gallery_height'],
        ), True);
    }

    /**
     * Renders the show_thumbnail_link settings field
     *
     * @param C_Display_Type $display_type
     * @return string
     */
    function _render_nextgen_basic_slideshow_show_thumbnail_link_field($display_type)
    {
        return $this->_render_radio_field(
            $display_type,
            'show_thumbnail_link',
            __('Show thumbnail link', 'nggallery'),
            $display_type->settings['show_thumbnail_link']
        );
    }

    /**
     * Renders the thumbnail_link_text settings field
     *
     * @param C_Display_Type $display_type
     * @return string
     */
    function _render_nextgen_basic_slideshow_thumbnail_link_text_field($display_type)
    {
        return $this->_render_text_field(
            $display_type,
            'thumbnail_link_text',
            __('Thumbnail link text', 'nggallery'),
            $display_type->settings['thumbnail_link_text'],
            '',
            !empty($display_type->settings['show_thumbnail_link']) ? FALSE : TRUE
        );
    }
}
