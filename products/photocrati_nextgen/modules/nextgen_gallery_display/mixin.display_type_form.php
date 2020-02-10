<?php

/**
 * Class Mixin_Display_Type_Form
 * @mixin C_Form
 */
class Mixin_Display_Type_Form extends Mixin
{
	var $_model = null;
	
	function initialize()
	{
		$this->object->implement('I_Display_Type_Form');
	}

    /**
     * A wrapper to wp_enqueue_script() and ATP's mark_script()
     *
     * Unlike wp_enqueue_script() the version parameter is last as NGG should always use NGG_SCRIPT_VERSION
     * @param string $handle
     * @param string $source
     * @param array $dependencies
     * @param bool $in_footer
     * @param string $version
     */
	public function enqueue_script($handle, $source = '', $dependencies = array(), $in_footer = FALSE, $version = NGG_SCRIPT_VERSION)
    {
        wp_enqueue_script($handle, $source, $dependencies, $version, $in_footer);
        $atp = C_Attach_Controller::get_instance();
        if ($atp !== NULL)
            $atp->mark_script($handle);
    }

    /**
     * A wrapper to wp_enqueue_style()
     *
     * Unlike wp_enqueue_style() the version parameter is last as NGG should always use NGG_SCRIPT_VERSION
     * @param string $handle
     * @param string $source
     * @param array $dependencies
     * @param string $media
     * @param string $version
     */
    public function enqueue_style($handle, $source = '', $dependencies = array(), $media = 'all', $version = NGG_SCRIPT_VERSION)
    {
        wp_enqueue_style($handle, $source, $dependencies, $version, $media);
    }
  
	/**
	 * Returns the name of the display type. Sub-class should override
	 * @throws Exception
	 * @return string
	 */
	function get_display_type_name()
	{
		throw new Exception(__METHOD__." not implemented");
	}

	/**
	 * Returns the model (display type) used in the form
	 * @return stdClass
	 */
	function get_model()
	{
		if ($this->_model == null)
		{
			$mapper = C_Display_Type_Mapper::get_instance();
			$this->_model = $mapper->find_by_name($this->object->get_display_type_name(), TRUE);
		}
		
		return $this->_model;
	}

	/**
	 * Returns the title of the form, which is the title of the display type
	 * @return string
	 */
	function get_title()
	{
		return __($this->object->get_model()->title, 'nggallery');
	}
        
    /**
     * Saves the settings for the display type
     * @param array $attributes
     * @return boolean
     */
    function save_action($attributes=array())
    {
        return $this->object->get_model()->save(array('settings'=>$attributes));
    }

    /**
     * Renders the AJAX pagination settings field
     *
     * @param C_Display_Type $display_type
     * @return string
     */
    function _render_ajax_pagination_field($display_type)
	{
		return $this->object->_render_radio_field(
			$display_type,
			'ajax_pagination',
			__('Enable AJAX pagination', 'nggallery'),
			isset($display_type->settings['ajax_pagination']) ? $display_type->settings['ajax_pagination'] : FALSE,
            __('Browse images without reloading the page.', 'nggallery')
		);
	}
    
    function _render_thumbnail_override_settings_field($display_type)
    {
        $enabled = isset($display_type->settings['override_thumbnail_settings']) ? $display_type->settings['override_thumbnail_settings'] : FALSE;
        $hidden  = !$enabled;
        $width   = $enabled && isset($display_type->settings['thumbnail_width']) ? intval($display_type->settings['thumbnail_width']) : 0;
        $height  = $enabled && isset($display_type->settings['thumbnail_height']) ? intval($display_type->settings['thumbnail_height']) : 0;
        $crop    = $enabled && isset($display_type->settings['thumbnail_crop']) ? $display_type->settings['thumbnail_crop'] : FALSE;

        $override_field = $this->_render_radio_field(
            $display_type,
            'override_thumbnail_settings',
            __('Override thumbnail settings', 'nggallery'),
            $enabled,
			__("This does not affect existing thumbnails; overriding the thumbnail settings will create an additional set of thumbnails. To change the size of existing thumbnails please visit 'Manage Galleries' and choose 'Create new thumbnails' for all images in the gallery.", 'nggallery')
        );

        $dimensions_field = $this->object->render_partial(
            'photocrati-nextgen_gallery_display#thumbnail_settings',
            array(
                'display_type_name' => $display_type->name,
                'name' => 'thumbnail_dimensions',
                'label'=> __('Thumbnail dimensions', 'nggallery'),
                'thumbnail_width' => $width,
                'thumbnail_height'=> $height,
                'hidden' => $hidden ? 'hidden' : '',
                'text' => ''
            ),
            TRUE
        );

        $crop_field = $this->_render_radio_field(
            $display_type,
            'thumbnail_crop',
            __('Thumbnail crop', 'nggallery'),
            $crop,
            '',
            $hidden
        );

        $everything = $override_field . $dimensions_field . $crop_field;

        return $everything;
    }

    /**
     * Renders the thumbnail override settings field(s)
     *
     * @param C_Display_Type $display_type
     * @return string
     */
    function _render_image_override_settings_field($display_type)
    {
        $hidden = !(isset($display_type->settings['override_image_settings']) ? $display_type->settings['override_image_settings'] : FALSE);

        $override_field = $this->_render_radio_field(
            $display_type,
            'override_image_settings',
            __('Override image settings', 'nggallery'),
            isset($display_type->settings['override_image_settings']) ? $display_type->settings['override_image_settings'] : 0,
            __('Overriding the image settings will create an additional set of images', 'nggallery')
        );

        $qualities = array();
        for ($i = 100; $i > 40; $i -= 5) { $qualities[$i] = "{$i}%"; }
        $quality_field = $this->_render_select_field(
            $display_type,
            'image_quality',
            __('Image quality', 'nggallery'),
            $qualities,    
            $display_type->settings['image_quality'],
            '',
            $hidden
        );

        $crop_field = $this->_render_radio_field(
            $display_type,
            'image_crop',
            __('Image crop', 'nggallery'),
            $display_type->settings['image_crop'],
            '',
            $hidden
        );

        $watermark_field = $this->_render_radio_field(
            $display_type,
            'image_watermark',
            __('Image watermark', 'nggallery'),
            $display_type->settings['image_watermark'],
            '',
            $hidden
        );

        $everything = $override_field . $quality_field . $crop_field . $watermark_field;

        return $everything;
    }


    function _render_display_view_field($display_type)
    {
	    $display_type_views = $this->get_available_display_type_views($display_type);
	    $current_value = isset($display_type->settings['display_type_view']) ? $display_type->settings['display_type_view'] : '';
	    if (isset($display_type->settings['display_view'])) $current_value = $display_type->settings['display_view'];

	    return $this->object->_render_select_field(
		    $display_type,
		    'display_view',
		    __('Select View', 'nggallery'),
		    $display_type_views,
		    $current_value,
		    '',
		    FALSE
	    );
    }


    /**
     * Renders a field for selecting a template
     *
     * @param C_Display_Type $display_type
     * @return string
     */
    function _render_display_type_view_field($display_type)
    {
        $display_type_views = $this->get_available_display_type_views($display_type);

        return $this->object->_render_select_field(
            $display_type,
            'display_type_view',
            __('Select View', 'nggallery'),
            $display_type_views,    
            $display_type->settings['display_type_view'],
            '',
            FALSE
        );
    }

    /**
     * Gets available templates
     *
     * @param C_Display_Type $display_type
     * @return array
     */
    function get_available_display_type_views($display_type) {
        
        /* Set up templates array */
        if ( strpos($display_type->name, 'basic') !== false ) {
            $views = array( 'default' => __('Legacy', 'nggallery') );
        } else {
            $views = array( 'default' => __('Default', 'nggallery') );
        }
        
        /* Fetch array of directories to scan */
        $dirs = M_Gallery_Display::get_display_type_view_dirs($display_type);
        
        /* Populate the views array by scanning each directory for relevant templates */
        foreach ($dirs as $dir_name => $dir) {

            /* Confirm directory exists */
            if ( !file_exists($dir) || !is_dir($dir) ) {
                continue;
            }
            
            /* Scan for template files and create array */
            $files = scandir($dir);
            $template_files = preg_grep('/^.+\-(template|view).php$/i' , $files);
            $template_files = $template_files ? array_combine($template_files, $template_files) : array();
            
            /* For custom templates only, append directory name placeholder */
            foreach ($template_files as $key => $value)
                {
                    if ( $dir_name !== 'default' ) {                    
                        $template_files[ $dir_name . DIRECTORY_SEPARATOR . $key] = $dir_name . DIRECTORY_SEPARATOR . $value;
                        unset($template_files[$key]);
                    }
                }

            $views = array_merge($views, $template_files);

        }

        return $views;

    }

}