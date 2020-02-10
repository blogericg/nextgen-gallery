<?php

/**
 * Class A_NextGen_Basic_Thumbnails_Controller
 * @mixin Mixin_NextGen_Basic_Pagination
 */
class A_NextGen_Basic_Thumbnails_Controller extends Mixin
{
	/**
	 * Adds framework support for thumbnails
	 */
	function initialize()
	{
        $this->add_mixin('Mixin_NextGen_Basic_Pagination');
	}

	/**
	 * @param C_Displayed_Gallery $displayed_gallery
     * @param bool $return (optional)
     * @return string
	 */
	function index_action($displayed_gallery, $return=FALSE)
    {
        $display_settings = $displayed_gallery->display_settings;
        $gallery_id = $displayed_gallery->id();

        if (!$display_settings['disable_pagination'])
            $current_page = (int)$this->param('nggpage', $gallery_id, 1);
        else
            $current_page = 1;

        $offset = $display_settings['images_per_page'] * ($current_page - 1);
        $storage = C_Gallery_Storage::get_instance();
        $total = $displayed_gallery->get_entity_count();

        // Get the images to be displayed
        if ($display_settings['images_per_page'] > 0 && $display_settings['show_all_in_lightbox'])
        {
            // the "Add Hidden Images" feature works by loading ALL images and then marking the ones not on this page
            // as hidden (style="display: none")
            $images = $displayed_gallery->get_included_entities();
            $i = 0;
            foreach ($images as &$image) {
                if ($i < $display_settings['images_per_page'] * ($current_page - 1))
                {
                    $image->hidden = TRUE;
                }
                elseif ($i >= $display_settings['images_per_page'] * ($current_page))
                {
                    $image->hidden = TRUE;
                }
                $i++;
            }
        }
        else {
            // just display the images for this page, as normal
            $images = $displayed_gallery->get_included_entities($display_settings['images_per_page'], $offset);
        }

		// Are there images to display?
		if ($images) {

			// Create pagination
			if ($display_settings['images_per_page'] && !$display_settings['disable_pagination']) {
                $pagination_result = $this->object->create_pagination(
                    $current_page,
                    $total,
                    $display_settings['images_per_page'],
                    urldecode($this->object->param('ajax_pagination_referrer'))
                );
                $this->object->remove_param('ajax_pagination_referrer');
                $pagination_prev = $pagination_result['prev'];
                $pagination_next = $pagination_result['next'];
                $pagination      = $pagination_result['output'];
			} else {
                list($pagination_prev, $pagination_next, $pagination) = array(NULL, NULL, NULL);
            }

			$thumbnail_size_name = 'thumbnail';

			if ($display_settings['override_thumbnail_settings'])
            {
                $dynthumbs = C_Dynamic_Thumbnails_Manager::get_instance();

                if ($dynthumbs != null)
                {
                    $dyn_params = array(
                        'width' => $display_settings['thumbnail_width'],
                        'height' => $display_settings['thumbnail_height'],
                    );

                    if ($display_settings['thumbnail_quality'])
                        $dyn_params['quality'] = $display_settings['thumbnail_quality'];

                    if ($display_settings['thumbnail_crop'])
                        $dyn_params['crop'] = true;

                    if ($display_settings['thumbnail_watermark'])
                        $dyn_params['watermark'] = true;

                    $thumbnail_size_name = $dynthumbs->get_size_name($dyn_params);
                }
            }

            // Generate a slideshow link
            $slideshow_link = '';
            if ($display_settings['show_slideshow_link'])
            {
                // origin_url is necessary for ajax operations. slideshow_link_origin will NOT always exist.
                $origin_url = $this->object->param('ajax_pagination_referrer');
                $slideshow_link = $this->object->get_url_for_alternate_display_type(
                    $displayed_gallery, NGG_BASIC_SLIDESHOW, $origin_url
                );
            }

            // This setting 1) points all images to an imagebrowser display & 2) disables the lightbox effect
            if ($display_settings['use_imagebrowser_effect'])
            {
                if (!empty($displayed_gallery->display_settings['original_display_type'])
                    &&  !empty($_SERVER['NGG_ORIG_REQUEST_URI']))
                    $origin_url = $_SERVER['NGG_ORIG_REQUEST_URI'];

                $url = (!empty($origin_url) ? $origin_url : $this->object->get_routed_url(TRUE));
                $url = $this->object->remove_param_for($url, 'image');
                $url = $this->object->set_param_for($url, 'image', '%STUB%', NULL, FALSE);

                $effect_code = "class='use_imagebrowser_effect' data-imagebrowser-url='{$url}'";
            }
            else {
                $effect_code = $this->object->get_effect_code($displayed_gallery);
            }

            // The render functions require different processing
            if (!empty($display_settings['template']) && $display_settings['template'] != 'default')
            {
                $this->object->add_mixin('A_NextGen_Basic_Template_Form');
                $this->object->add_mixin('Mixin_NextGen_Basic_Templates');
                $params = $this->object->prepare_legacy_parameters(
                    $images,
                    $displayed_gallery,
                    array(
                        'next' => (empty($pagination_next)) ? FALSE : $pagination_next,
                        'prev' => (empty($pagination_prev)) ? FALSE : $pagination_prev,
                        'pagination' => $pagination,
                        'slideshow_link' => $slideshow_link,
	                    'effect_code'    => $effect_code
                    )
                );
                $output = $this->object->legacy_render($display_settings['template'], $params, $return, 'gallery');
            }
            else {
                $params = $display_settings;
                $params['storage']				= &$storage;
                $params['images']				= &$images;
                $params['displayed_gallery_id'] = $gallery_id;
                $params['current_page']			= $current_page;
                $params['effect_code']			= $effect_code;
                $params['pagination']			= $pagination;
                $params['thumbnail_size_name']	= $thumbnail_size_name;
                $params['slideshow_link']       = $slideshow_link;
                
                $params = $this->object->prepare_display_parameters($displayed_gallery, $params);
                
                $output = $this->object->render_partial('photocrati-nextgen_basic_gallery#thumbnails/index', $params, $return);
            }

            return $output;

		}
		else if ($display_settings['display_no_images_error']) {
			return $this->object->render_partial("photocrati-nextgen_gallery_display#no_images_found", array(), $return);
		}
		return '';
	}

	/**
	 * Enqueues all static resources required by this display type
	 * @param C_Displayed_Gallery $displayed_gallery
	 */
	function enqueue_frontend_resources($displayed_gallery)
	{
		$this->call_parent('enqueue_frontend_resources', $displayed_gallery);

        wp_enqueue_style(
            'nextgen_basic_thumbnails_style',
            $this->get_static_url('photocrati-nextgen_basic_gallery#thumbnails/nextgen_basic_thumbnails.css'),
            array(),
            NGG_SCRIPT_VERSION
        );

        if ($displayed_gallery->display_settings['ajax_pagination'])
            wp_enqueue_script(
                'nextgen-basic-thumbnails-ajax-pagination',
                $this->object->get_static_url('photocrati-nextgen_basic_gallery#thumbnails/ajax_pagination.js'),
                array(),
                NGG_SCRIPT_VERSION
            );

		wp_enqueue_style(
            'nextgen_pagination_style',
            $this->get_static_url('photocrati-nextgen_pagination#style.css'),
            array(),
            NGG_SCRIPT_VERSION
        );

		$this->enqueue_ngg_styles();
	}

	/**
	 * Provides the url of the JavaScript library required for
	 * NextGEN Basic Thumbnails to display
	 * @return string
	 */
	function _get_js_lib_url()
	{
        return $this->object->get_static_url('photocrati-nextgen_basic_gallery#thumbnails/nextgen_basic_thumbnails.js');
	}

    /**
     * Override to the MVC method, allows the above imagebrowser-url to return as image/23 instead of image--23
     *
     * @param $url
     * @param $key
     * @param $value
     * @param null $id
     * @param bool $use_prefix
     * @return string
     */
    function set_param_for($url, $key, $value, $id=NULL, $use_prefix=FALSE)
    {
        $retval = $this->call_parent('set_param_for', $url, $key, $value, $id, $use_prefix);
        while (preg_match("#(image)--([^/]+)#", $retval, $matches)) {
            $retval = str_replace($matches[0], $matches[1] . '/' . $matches[2], $retval);
        }

        return $retval;
    }
}
