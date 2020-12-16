<?php

/**
 * A Controller which displays the settings form for the display type, as
 * well as the front-end display
 */

/**
 * Class C_Display_Type_Controller
 * @mixin Mixin_Display_Type_Controller
 * @implements I_Display_Type_Controller
 */
class C_Display_Type_Controller extends C_MVC_Controller
{
    static $_instances = array();

    function define($context=FALSE)
    {
        parent::define($context);
        $this->add_mixin('Mixin_Display_Type_Controller');
        $this->implement('I_Display_Type_Controller');
    }

    /**
     * Gets a singleton of the mapper
     * @param string|bool $context
     * @return C_Display_Type_Controller
     */
    public static function get_instance($context = FALSE)
    {
        if (!isset(self::$_instances[$context]))
            self::$_instances[$context] = new C_Display_Type_Controller($context);
        return self::$_instances[$context];
    }
}

/**
 * Provides instance methods for the C_Display_Type_Controller class
 */
class Mixin_Display_Type_Controller extends Mixin
{
    var $_render_mode;

    /**
     * Enqueues static resources required for lightbox effects
     * @param object $displayed_gallery
     */
    function enqueue_lightbox_resources($displayed_gallery)
    {
        C_Lightbox_Library_Manager::get_instance()->enqueue();
    }

    function is_cachable()
    {
        return TRUE;
    }

    /**
     * This method should be overwritten by other adapters/mixins, and call
     * wp_enqueue_script() / wp_enqueue_style()
     * @param C_Displayed_Gallery $displayed_gallery
     */
    function enqueue_frontend_resources($displayed_gallery)
    {
        // This script provides common JavaScript among all display types
        wp_enqueue_script('ngg_common');
        wp_add_inline_script('ngg_common', '
            var nggLastTimeoutVal = 1000;

			var nggRetryFailedImage = function(img) {
				setTimeout(function(){
					img.src = img.src;
				}, nggLastTimeoutVal);
			
				nggLastTimeoutVal += 500;
			}
        ');

        // Enqueue the display type library
        wp_enqueue_script(
            $displayed_gallery->display_type,
            $this->object->_get_js_lib_url($displayed_gallery),
            array(),
            NGG_SCRIPT_VERSION
        );

        // Add "galleries = {};"
        $this->object->_add_script_data(
            'ngg_common',
            'galleries',
            new stdClass,
            TRUE,
            FALSE
        );

        // Add "galleries.gallery_1 = {};"
        $this->object->_add_script_data(
            'ngg_common',
            'galleries.gallery_' . $displayed_gallery->id(),
            (array)$displayed_gallery->get_entity(),
            FALSE
        );

        $this->object->_add_script_data(
            'ngg_common',
            'galleries.gallery_' . $displayed_gallery->id() . '.wordpress_page_root',
            get_permalink(),
            FALSE
        );

        // Enqueue trigger button resources
        C_Displayed_Gallery_Trigger_Manager::get_instance()->enqueue_resources($displayed_gallery);

        // Enqueue lightbox library
        $this->object->enqueue_lightbox_resources($displayed_gallery);
    }

    function enqueue_ngg_styles()
    {
        $settings = C_NextGen_Settings::get_instance();
        if ((!is_multisite() || (is_multisite() && $settings->wpmuStyle)) && $settings->activateCSS)
        {
            wp_enqueue_style(
                'nggallery',
                C_NextGen_Style_Manager::get_instance()->get_selected_stylesheet_url(),
                array(),
                NGG_SCRIPT_VERSION
            );
        }
    }

    function get_render_mode()
    {
        return $this->object->_render_mode;
    }

    function set_render_mode($mode)
    {
        $this->object->_render_mode = $mode;
    }

    /**
     * Ensures that the minimum configuration of parameters are sent to a view
     * @param $displayed_gallery
     * @param null $params
     * @return array|null
     */
    function prepare_display_parameters($displayed_gallery, $params = null)
    {
        if ($params == null)
        {
            $params = array();
        }

        $params['display_type_rendering'] = true;
        $params['displayed_gallery'] = $displayed_gallery;
        $params['render_mode'] = $this->object->get_render_mode();

        return $params;
    }

    /**
     * Renders the frontend display of the display type
     * @param C_Displayed_Gallery $displayed_gallery
     * @param bool $return (optional)
     * @return string
     */
    function index_action($displayed_gallery, $return=FALSE)
    {
        return $this->object->render_partial('photocrati-nextgen_gallery_display#index', array(), $return);
    }

    /**
     * Returns the url for the JavaScript library required
     * @return null|string
     */
    function _get_js_lib_url()
    {
        return NULL;
    }

    function does_lightbox_support_displayed_gallery($displayed_gallery, $lightbox=NULL)
    {
        if (!$lightbox) $lightbox = C_Lightbox_Library_Manager::get_instance()->get_selected();

        $retval = FALSE;

        if ($lightbox) {
            // HANDLE COMPATIBILITY BREAK
            // In NGG 2.1.48 and earlier, lightboxes were stdClass objects, and it was assumed
            // that they only supported galleries that contained images, not albums that contained galleries.

            // After NGG 2.1.48, lightboxes are now C_NGG_Lightbox instances which have a 'is_supported()' method
            // to test if the lightbox can work with the displayed gallery settings
            if (get_class($lightbox) == 'stdClass') {
                $retval = !in_array($displayed_gallery->source, array('album', 'albums'));
            }
            else $retval = $lightbox->is_supported($displayed_gallery);
        }

        return $retval;
    }

    /**
     * Returns the effect HTML code for the displayed gallery
     * @param object $displayed_gallery
     * @return string
     */
    function get_effect_code($displayed_gallery)
    {
        $retval = '';

        if (($lightbox = C_Lightbox_Library_Manager::get_instance()->get_selected())) {

            if ($this->does_lightbox_support_displayed_gallery($displayed_gallery, $lightbox)) {
                $retval = $lightbox->code;

                $retval = str_replace('%GALLERY_ID%', $displayed_gallery->id(), $retval);
                $retval = str_replace('%GALLERY_NAME%', $displayed_gallery->id(), $retval);

                global $post;
                if ($post && isset($post->ID) && $post->ID)
                    $retval = str_replace('%PAGE_ID%', $post->ID, $retval);
            }
        }

        // allow for customization
        $retval = apply_filters('ngg_effect_code', $retval, $displayed_gallery);

        return $retval;
    }

    /**
     * Adds data to the DOM which is then accessible by a script
     * @param string $handle
     * @param string $object_name
     * @param mixed $object_value
     * @param bool $define
     * @param bool $override
     * @return bool
     */
    function _add_script_data($handle, $object_name, $object_value, $define=TRUE, $override=FALSE)
    {
        $retval = FALSE;

        // wp_localize_script allows you to add data to the DOM, associated
        // with a particular script. You can even call wp_localize_script
        // multiple times to add multiple objects to the DOM. However, there
        // are a few problems with wp_localize_script:
        //
        // - If you call it with the same object_name more than once, you're
        //   overwritting the first call.
        // - You cannot namespace your objects due to the "var" keyword always
        // - being used.
        //
        // To circumvent the above issues, we're going to use the WP_Scripts
        // object to workaround the above issues
        global $wp_scripts;

        // Has the script been registered or enqueued yet?
        if (isset($wp_scripts->registered[$handle])) {

            // Get the associated data with this script
            $script = &$wp_scripts->registered[$handle];
            $data = isset($script->extra['data']) ? $script->extra['data'] : '';

            // Construct the addition
            $addition = $define ? "\nvar {$object_name} = " . json_encode($object_value) . ';' :
                "\n{$object_name} = " . json_encode($object_value) . ';';

            // Add the addition
            if ($override) {
                $data .= $addition;
                $retval = TRUE;
            }
            else if (strpos($data, $object_name) === FALSE) {
                $data .= $addition;
                $retval = TRUE;
            }

            $script->extra['data'] = $data;

            unset($script);
        }

        return $retval;
    }

    // Returns the longest and widest dimensions from a list of entities
    function get_entity_statistics($entities, $named_size, $style_images=FALSE)
    {
        $longest        = $widest = 0;
        $storage        = C_Gallery_Storage::get_instance();
        $image_mapper   = FALSE; // we'll fetch this if needed

        // Calculate longest and
        foreach ($entities as $entity) {

            // Get the image
            $image = FALSE;
            if (isset($entity->pid)) {
                $image = $entity;
            }
            elseif (isset($entity->previewpic)) {
                if (!$image_mapper) $image_mapper = C_Image_Mapper::get_instance();
                $image = $image_mapper->find($entity->previewpic);
            }

            // Once we have the image, get it's dimensions
            if ($image) {
                $dimensions = $storage->get_image_dimensions($image, $named_size);
                if ($dimensions['width']  > $widest)    $widest     = $dimensions['width'];
                if ($dimensions['height'] > $longest)   $longest    = $dimensions['height'];
            }
        }

        // Second loop to style images
        if ($style_images) foreach ($entities as &$entity) {

            // Get the image
            $image = FALSE;
            if (isset($entity->pid)) {
                $image = $entity;
            }
            elseif (isset($entity->previewpic)) {
                if (!$image_mapper) $image_mapper = C_Image_Mapper::get_instance();
                $image = $image_mapper->find($entity->previewpic);
            }

            // Once we have the image, get it's dimension and calculate margins
            if ($image) {
                $dimensions = $storage->get_image_dimensions($image, $named_size);
            }
        }

        return array(
            'entities'  =>  $entities,
            'longest'   =>  $longest,
            'widest'    =>  $widest
        );
    }

    /**
     * Renders a view after checking for templates
     */
    function create_view($template, $params=array(), $context=NULL)
    {
        if (isset($params['displayed_gallery'])) {
            if (isset($params['displayed_gallery']->display_settings)) {
	            $template = $this->get_display_type_view_abspath($template, $params);
            }
        }
        return $this->call_parent('create_view', $template, $params, $context);
    }

    /**
     * Finds the abs path of template given file name and list of posssible directories
     * @param string $template
     * @param array $params
     * @return string $template 
     */
    function get_display_type_view_abspath($template, $params) {

        /* Identify display type and display_type_view */
	    $displayed_gallery = $params['displayed_gallery'];
        $display_type_name = $params['displayed_gallery']->display_type;
        $display_settings  = $displayed_gallery->display_settings;
		$display_type_view = NULL;
		if (isset($display_settings['display_type_view'])) $display_type_view = $display_settings['display_type_view'];
	    if (isset($display_settings['display_view'])) $display_type_view = $display_settings['display_view'];

        if ($display_type_view && $display_type_view != 'default') {

        	/*
        	 * A display type view or display template value looks like this:
        	 *
        	 * "default"
        	 * "imagebrowser-dark-template.php" ("default" category is implicit)
        	 * "custom/customized-template.php" ("custom" category is explicit)
        	 *
        	 * Templates can be found in multiple directories, and each directory is given
        	 * a key, which is used to distinguish it's "category".
        	 */

        	$fs = C_Fs::get_instance();

	        /* Fetch array of template directories */
	        $dirs = M_Gallery_Display::get_display_type_view_dirs($display_type_name);

	        // Add the missing "default" category name prefix to the template to make it
            // more consistent to evaluate
            if (strpos($display_type_view, DIRECTORY_SEPARATOR) === FALSE) {
                $display_type_view = join(DIRECTORY_SEPARATOR, array('default', $display_type_view));
            }

            foreach ($dirs as $category => $dir) {
                $category = preg_quote($category . DIRECTORY_SEPARATOR);
                if (preg_match("#^{$category}(.*)$#", $display_type_view, $match)) {
                    $display_type_view = $match[1];
                    $template_abspath = $fs->join_paths($dir, $display_type_view);
                    if (@file_exists($template_abspath)) {
                        $template = $template_abspath;
                        break;
                    }
                }
            }
        }

        /* Return template. If no match is found, returns the original template */
        return $template;

    }
    
}


