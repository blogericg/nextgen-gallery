<?php

/**
 * Class A_NextGen_Basic_Singlepic_Controller
 * @mixin C_Display_Type_Controller
 * @adapts I_Display_Type_Controller for "photocrati-nextgen_basic_singlepic" context
 */
class A_NextGen_Basic_Singlepic_Controller extends Mixin
{
    /**
     * Displays the 'singlepic' display type
     *
     * @param C_Displayed_Gallery
     * @param bool $return (optional)
     * @return string
     */
    function index_action($displayed_gallery, $return = FALSE)
    {
        $storage   = C_Gallery_Storage::get_instance();
        $dynthumbs = C_Dynamic_Thumbnails_Manager::get_instance();
        $display_settings = $displayed_gallery->display_settings;

        // use this over get_included_entities() so we can display images marked 'excluded'
        $displayed_gallery->skip_excluding_globally_excluded_images = TRUE;
        $entities = $displayed_gallery->get_entities(1, FALSE, FALSE, 'included');
        $image = array_shift($entities);

        if (!$image)
            return $this->object->render_partial("photocrati-nextgen_gallery_display#no_images_found", array(), $return);

        switch ($display_settings['float']) {
            case 'left':
                $display_settings['float'] = 'ngg-left';
                break;
            case 'right':
                $display_settings['float'] = 'ngg-right';
                break;
            case 'center':
                $display_settings['float'] = 'ngg-center';
                break;
            default:
                $display_settings['float'] = '';
                break;
        }

        $params = array();

        if (!empty($display_settings['link']))
        {
            $target = $display_settings['link_target'];
            $effect_code = '';
        }
        else {
            $display_settings['link'] = $storage->get_image_url($image, 'full', TRUE);
            $target = '_self';
            $effect_code = $this->object->get_effect_code($displayed_gallery);
        }
        $params['target'] = $target;

        // mode is a legacy parameter
        if (!is_array($display_settings['mode']))
            $display_settings['mode'] = explode(',', $display_settings['mode']);
        if (in_array('web20', $display_settings['mode']))
            $display_settings['display_reflection'] = TRUE;
        if (in_array('watermark', $display_settings['mode']))
            $display_settings['display_watermark'] = TRUE;
        
	      if (isset($display_settings['w']))
	          $display_settings['width'] = $display_settings['w'];
	      elseif (isset($display_settings['h']))
	      		unset($display_settings['width']);
	          
	      if (isset($display_settings['h']))
	          $display_settings['height'] = $display_settings['h'];
	      elseif (isset($display_settings['w']))
	      		unset($display_settings['height']);
        
        // legacy assumed no width/height meant full size unlike generate_thumbnail: force a full resolution
        if (!isset($display_settings['width']) && !isset($display_settings['height']))
            $display_settings['width'] = $image->meta_data['width'];
        
        if (isset($display_settings['width']))
        		$params['width'] = $display_settings['width'];
        
        if (isset($display_settings['height']))
            $params['height'] = $display_settings['height'];
            
        $params['quality'] = $display_settings['quality'];
        $params['crop'] = $display_settings['crop'];
        $params['watermark'] = $display_settings['display_watermark'];
        $params['reflection'] = $display_settings['display_reflection'];

        // Fall back to full in case dynamic images aren't available
        $size = 'full';

        if ($dynthumbs != null)
            $size = $dynthumbs->get_size_name($params);

        $thumbnail_url = $storage->get_image_url($image, $size);

        if (!empty($display_settings['template']) && $display_settings['template'] != 'default')
        {
            $this->object->add_mixin('A_NextGen_Basic_Template_Form');
            $this->object->add_mixin('Mixin_NextGen_Basic_Templates');
            $params = $this->object->prepare_legacy_parameters(array($image), $displayed_gallery, array('single_image' => TRUE));

            // the wrapper is a lazy-loader that calculates variables when requested. We here override those to always
            // return the same precalculated settings provided
            $params['image']->container[0]->_cache_overrides['caption']      = $displayed_gallery->inner_content;
            $params['image']->container[0]->_cache_overrides['classname']    = 'ngg-singlepic ' . $display_settings['float'];
            $params['image']->container[0]->_cache_overrides['imageURL']     = $display_settings['link'];
            $params['image']->container[0]->_cache_overrides['thumbnailURL'] = $thumbnail_url;
            $params['target'] = $target;

            // if a link is present we temporarily must filter out the effect code
            if (empty($effect_code))
                add_filter('ngg_get_thumbcode', array(&$this, 'strip_thumbcode'), 10);

            $retval = $this->object->legacy_render($display_settings['template'], $params, $return, 'singlepic');

            if (empty($effect_code))
                remove_filter('ngg_get_thumbcode', array(&$this, 'strip_thumbcode'), 10);

            return $retval;
        }
        else {
            $params = $display_settings;
            $params['storage']       = &$storage;
            $params['image']         = &$image;
            $params['effect_code']   = $effect_code;
            $params['inner_content'] = $displayed_gallery->inner_content;
            $params['settings']      = $display_settings;
            $params['thumbnail_url'] = $thumbnail_url;
            $params['target']        = $target;
                
            $params = $this->object->prepare_display_parameters($displayed_gallery, $params);

            return $this->object->render_partial('photocrati-nextgen_basic_singlepic#nextgen_basic_singlepic', $params, $return);
        }
    }

    /**
     * Intentionally disable the application of the effect code
     * @param string $thumbcode Unused
     * @return string
     */
    function strip_thumbcode($thumbcode)
    {
        return '';
    }

    /**
     * Enqueues all static resources required by this display type
     *
     * @param C_Displayed_Gallery $displayed_gallery
     */
    function enqueue_frontend_resources($displayed_gallery)
    {
		$this->call_parent('enqueue_frontend_resources', $displayed_gallery);

        wp_enqueue_style(
            'nextgen_basic_singlepic_style',
            $this->get_static_url('photocrati-nextgen_basic_singlepic#nextgen_basic_singlepic.css'),
            array(),
            NGG_SCRIPT_VERSION
        );

		$this->enqueue_ngg_styles();
    }

}
