<?php

/**
 * Class A_NextGen_Basic_Slideshow_Controller
 * @mixin C_Display_Type_Controller
 * @adapts I_Display_Type_Controller for "photocrati-nextgen_basic_slideshow" context
 */
class A_NextGen_Basic_Slideshow_Controller extends Mixin
{
	/**
	 * @param C_Displayed_Gallery $displayed_gallery
	 * @param bool $return (optional)
	 * @return string
	 */
	function index_action($displayed_gallery, $return=FALSE)
	{   
		// We now hide option for triggers on this display type. 
        // This ensures they do not show based on past settings.
        $displayed_gallery->display_settings['ngg_triggers_display'] = 'never';
        
		// Get the images to be displayed
        $current_page = (int)$this->param('nggpage', 1);

		if (($images = $displayed_gallery->get_included_entities()))
        {
			// Get the gallery storage component
			$storage = C_Gallery_Storage::get_instance();

			// Create parameter list for the view
			$params = $displayed_gallery->display_settings;
			$params['storage']				= $storage;
			$params['images']				= $images;
			$params['displayed_gallery_id'] = $displayed_gallery->id();
			$params['current_page']			= $current_page;
			$params['effect_code']			= $this->object->get_effect_code($displayed_gallery);
			$params['anchor']				= 'ngg-slideshow-' . $displayed_gallery->id() . '-' . rand(1, getrandmax()) . $current_page;
			$gallery_width					= $displayed_gallery->display_settings['gallery_width'];
			$gallery_height					= $displayed_gallery->display_settings['gallery_height'];
			$params['aspect_ratio']			= $gallery_width / $gallery_height;
			$params['placeholder']			= $this->object->get_static_url('photocrati-nextgen_basic_gallery#slideshow/placeholder.gif');

			// This was not set correctly in previous versions
			if (empty($params['cycle_effect']))
			    $params['cycle_effect'] = 'fade';

            // Are we to generate a thumbnail link?
            if ($displayed_gallery->display_settings['show_thumbnail_link']) {
                $params['thumbnail_link'] = $this->object->get_url_for_alternate_display_type(
                    $displayed_gallery, NGG_BASIC_THUMBNAILS
                );
            }
                
	        $params = $this->object->prepare_display_parameters($displayed_gallery, $params);

			$retval = $this->object->render_partial('photocrati-nextgen_basic_gallery#slideshow/index', $params, $return);
		}

		// No images found
		else {
			$retval = $this->object->render_partial('photocrati-nextgen_gallery_display#no_images_found', array(), $return);
		}

		return $retval;
	}

	/**
	 * Enqueues all static resources required by this display type
	 * @param C_Displayed_Gallery $displayed_gallery
	 */
	function enqueue_frontend_resources($displayed_gallery)
	{

		wp_enqueue_style(
			'ngg_basic_slideshow_style',
			$this->get_static_url('photocrati-nextgen_basic_gallery#slideshow/ngg_basic_slideshow.css'),
			array(),
			NGG_SCRIPT_VERSION
		);

		// Add new scripts for slick based slideshow

		wp_enqueue_script(
			'ngg_slick',
			$this->get_static_url("photocrati-nextgen_basic_gallery#slideshow/slick/slick-1.8.0-modded.js"),
			array('jquery'),
			NGG_SCRIPT_VERSION
		);

		wp_enqueue_style(
			'ngg_slick_slideshow_style',
			$this->get_static_url('photocrati-nextgen_basic_gallery#slideshow/slick/slick.css'),
			array(),
			NGG_SCRIPT_VERSION
		);

		wp_enqueue_style(
			'ngg_slick_slideshow_theme',
			$this->get_static_url('photocrati-nextgen_basic_gallery#slideshow/slick/slick-theme.css'),
			array(),
			NGG_SCRIPT_VERSION
		);

		$this->call_parent('enqueue_frontend_resources', $displayed_gallery);
		$this->enqueue_ngg_styles();
	}

	/**
	 * Provides the url of the JavaScript library required for
	 * NextGEN Basic Slideshow to display
	 * @return string
	 */
	function _get_js_lib_url()
	{
		return $this->get_static_url('photocrati-nextgen_basic_gallery#slideshow/ngg_basic_slideshow.js');
	}
}