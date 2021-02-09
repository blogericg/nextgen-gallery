<?php

/**
 * Class C_Displayed_Gallery_Renderer
 * @mixin Mixin_Displayed_Gallery_Renderer
 * @implements I_Displayed_Gallery_Renderer
 */
class C_Displayed_Gallery_Renderer extends C_Component
{
    static $_instances = array();

    /**
     * Returns an instance of the class
     * @param bool|string $context
     * @return C_Displayed_Gallery_Renderer
     */
    static function get_instance($context=FALSE)
    {
		if (!isset(self::$_instances[$context]))
	   	{
            $klass = __CLASS__;
            self::$_instances[$context]= new $klass($context);
        }
        return self::$_instances[$context];
    }


    /**
     * Defines the object
     * @param bool $context
     */
    function define($context=FALSE)
    {
        parent::define($context);
        $this->add_mixin('Mixin_Displayed_Gallery_Renderer');
        $this->implement('I_Displayed_Gallery_Renderer');
    }
}


/**
 * Provides the ability to render a display type
 */
class Mixin_Displayed_Gallery_Renderer extends Mixin
{
    function params_to_displayed_gallery($params)
    {
        $displayed_gallery = NULL;

        // Get the NextGEN settings to provide some defaults
        $settings = C_NextGen_Settings::get_instance();

        // Perform some conversions...
        if (isset($params['galleries'])) {
            $params['gallery_ids'] = $params['galleries'];
            unset($params['galleries']);
        }
        if (isset($params['albums'])) {
            $params['album_ids'] = $params['albums'];
            unset($params['albums']);
        }

        // Configure the arguments
        $defaults = array(
            'id'				=> NULL,
            'ids'               => NULL,
            'source'			=> '',
            'src'               => '',
            'container_ids'		=> array(),
            'gallery_ids'		=> array(),
            'album_ids'			=> array(),
            'tag_ids'			=> array(),
            'display_type'		=> '',
            'display'           => '',
            'exclusions'		=> array(),
            'order_by'			=> $settings->galSort,
            'order_direction'	=> $settings->galSortOrder,
            'image_ids'			=> array(),
            'entity_ids'		=> array(),
            'tagcloud'          => FALSE,
            'returns'           => 'included',
            'slug'              => NULL,
            'sortorder'         => array()
        );
        $args = shortcode_atts($defaults, $params, 'ngg');

        // Are we loading a specific displayed gallery that's persisted?
        $mapper = C_Displayed_Gallery_Mapper::get_instance();
        if (!is_null($args['id'])) {
            $displayed_gallery = $mapper->find($args['id'], TRUE);
            unset($mapper); // no longer needed
        }

        // We're generating a new displayed gallery
        else {

            // Galleries?
            if ($args['gallery_ids'])
            {
                if ($args['source'] != 'albums' AND $args['source'] != 'album')
                {
                    $args['source']        = 'galleries';
                    $args['container_ids'] = $args['gallery_ids'];
                    if ($args['image_ids'])
                        $args['entity_ids'] = $args['image_ids'];
                }
                elseif ($args['source'] == 'albums') {
                    $args['entity_ids']	= $args['gallery_ids'];
                }
                unset($args['gallery_ids']);
            }

            // Albums ?
            elseif ($args['album_ids'] || $args['album_ids'] === '0') {
                $args['source'] = 'albums';
                $args['container_ids'] = $args['album_ids'];
                unset($args['albums_ids']);
            }

            // Tags ?
            elseif ($args['tag_ids']) {
                $args['source'] = 'tags';
                $args['container_ids'] = $args['tag_ids'];
                unset($args['tag_ids']);
            }

            // Specific images selected
            elseif ($args['image_ids']) {
                $args['source'] = 'galleries';
                $args['entity_ids'] = $args['image_ids'];
                unset($args['image_ids']);
            }

            // Tagcloud support
            elseif ($args['tagcloud']) {
                $args['source'] = 'tags';
            }

            // Convert strings to arrays
            if (!empty($args['ids']) && !is_array($args['ids']))
            {
                $args['container_ids'] = preg_split("/,|\|/", $args['ids']);
                unset($args['ids']);
            }

            if (!is_array($args['container_ids']))
                $args['container_ids'] = preg_split("/,|\|/", $args['container_ids']);

            if (!is_array($args['exclusions']))
                $args['exclusions'] = preg_split("/,|\|/", $args['exclusions']);

            if (!is_array($args['entity_ids']))
                $args['entity_ids'] = preg_split("/,|\|/", $args['entity_ids']);

            if (!is_array($args['sortorder']))
                $args['sortorder'] = preg_split("/,|\|/", $args['sortorder']);

            // 'src' is used for legibility
            if (!empty($args['src']) && empty($args['source']))
            {
                $args['source'] = $args['src'];
                unset($args['src']);
            }

            // 'display' is used for legibility
            if (!empty($args['display']) && empty($args['display_type']))
            {
                $args['display_type'] = $args['display'];
                unset($args['display']);
            }

            // Get the display settings
            foreach (array_keys($defaults) as $key) {
                unset($params[$key]);
            }

            $args['display_settings'] = $params;

            // Create the displayed gallery
            $factory = C_Component_Factory::get_instance();
            $displayed_gallery = $factory->create('displayed_gallery', $args, $mapper);

            unset($factory);
        }

        // Validate the displayed gallery
        if ($displayed_gallery) $displayed_gallery->validate();

        return $displayed_gallery;
    }

    /**
     * Displays a "displayed gallery" instance
     *
     * Alias Properties:
     * gallery_ids/album_ids/tag_ids == container_ids
     * image_ids/gallery_ids		 == entity_ids
     *
     * Default Behavior:
     * - if order_by and order_direction are missing, the default settings
     *   are used from the "Other Options" page. The exception to this is
     *   when entity_ids are selected, in which the order is custom unless
     *   specified.
     *
     * How to use:
     *
     * To retrieve images from gallery 1 & 3, but exclude images 4 & 6:
     * [ngg gallery_ids="1,3" exclusions="4,6" display_type="photocrati-nextgen_basic_thumbnails"]
     *
     * To retrieve images 1 & 2 from gallery 1:
     * [ngg gallery_ids="1" image_ids="1,2" display_type="photocrati-nextgen_basic_thumbnails"]
     *
     * To retrieve images matching tags "landscapes" and "wedding shoots":
     * [ngg tag_ids="landscapes,wedding shoots" display_type="photocrati-nextgen_basic_thumbnails"]
     *
     * To retrieve galleries from albums 1 & #, but exclude sub-album 1:
     * [ngg album_ids="1,2" exclusions="a1" display_type="photocrati-nextgen_basic_compact_album"]
     *
     * To retrieve galleries from albums 1 & 2, but exclude gallery 1:
     * [ngg album_ids="1,2" exclusions="1" display_type="photocrati-nextgen_basic_compact_album"]
     *
     * To retrieve image 2, 3, and 5 - independent of what container is used
     * [ngg image_ids="2,3,5" display_type="photocrati-nextgen_basic_thumbnails"]
     *
     * To retrieve galleries 3 & 5, custom sorted, in album view
     * [ngg source="albums" gallery_ids="3,5" display_type="photocrati-nextgen_basic_compact_album"]
     *
     * To retrieve recent images, sorted by alt/title text
     * [ngg source="recent" order_by="alttext" display_type="photocrati-nextgen_basic_thumbnails"]
     *
     * To retrieve random image
     * [ngg source="random" display_type="photocrati-nextgen_basic_thumbnails"]
     *
     * To retrieve a single image
     * [ngg image_ids='8' display_type='photocrati-nextgen_basic_singlepic']
     *
     * To retrieve a tag cloud
     * [ngg tagcloud=yes display_type='photocrati-nextgen_basic_tagcloud']
	 *
	 * @param array|C_Displayed_Gallery $params_or_dg
	 * @param null|string $inner_content (optional)
	 * @param bool|null $mode (optional)
	 * @return string
     */
    function display_images($params_or_dg, $inner_content=NULL, $mode=NULL)
    {
        $retval = '';

        // Convert the array of parameters into a displayed gallery
        if (is_array($params_or_dg))
        {
            $params = $params_or_dg;
            $displayed_gallery = $this->object->params_to_displayed_gallery($params);
        }
        // We've already been given a displayed gallery
        elseif (is_object($params_or_dg) && get_class($params_or_dg) === 'C_Displayed_Gallery') {
            $displayed_gallery = $params_or_dg;
        }
        // Something has gone wrong; the request cannot be rendered
        else {
            $displayed_gallery = NULL;
        }

        // Validate the displayed gallery
        if ($displayed_gallery && $displayed_gallery->validate())
        {
            // Display!
            $retval = $this->object->render($displayed_gallery, TRUE, $mode);
        }
        else {
            if (C_NextGEN_Bootstrap::$debug)
                $retval = __('We cannot display this gallery', 'nggallery') . $this->debug_msg($displayed_gallery->get_errors()) . $this->debug_msg($displayed_gallery->get_entity());
            else
                $retval = __('We cannot display this gallery', 'nggallery');
        }

        return $retval;
    }

	function debug_msg($msg, $print_r=FALSE)
	{
		$retval = '';

		if (C_NextGEN_Bootstrap::$debug) {
			ob_start();
			if ($print_r) {
				echo '<pre>';
				print_r($msg);
				echo '</pre>';
			}
			else
				var_dump($msg);

			$retval = ob_get_clean();
		}

		return $retval;
	}

	/**
	 * Gets the display type version associated with the displayed gallery
	 * @param $display_type
	 *
	 * @return string
	 */
	function get_display_type_version($display_type)
	{
		$retval = NGG_PLUGIN_VERSION;

		$registry = C_Component_Registry::get_instance();
		$module = $registry->get_module(isset($display_type->module_id) ? $display_type->module_id : $display_type->name);
		if ($module) {
			$retval = $module->module_version;
		}

		return $retval;
	}


    /**
     * Renders a displayed gallery on the frontend
     * @param C_Displayed_Gallery $displayed_gallery
	 * @param bool $return
	 * @param string|null $mode (optional)
	 * @return string
     */
    function render($displayed_gallery, $return=FALSE, $mode = null)
    {
		$retval = '';
		$lookup = TRUE;

        // Simply throwing our rendered gallery into a feed will most likely not work correctly.
        // The MediaRSS option in NextGEN is available as an alternative.
        if (!C_NextGen_Settings::get_instance()->galleries_in_feeds && is_feed())
        {
            return sprintf(
                __(' [<a href="%s">See image gallery at %s</a>] ', 'nggallery'),
                esc_url(apply_filters('the_permalink_rss', get_permalink())),
                $_SERVER['SERVER_NAME']
            );
        }

		if ($mode == null)
			$mode = 'normal';

        if (is_null($displayed_gallery->id()))
            $displayed_gallery->id(md5(json_encode($displayed_gallery->get_entity())));

        // Get the display type controller
        $controller = $this->get_registry()->get_utility(
            'I_Display_Type_Controller', $displayed_gallery->display_type
        );

		// Get routing info
		$router = C_Router::get_instance();
		$url    = $router->get_url($router->get_request_uri(), TRUE);

		// Should we lookup in cache?
		if (is_array($displayed_gallery->container_ids) && in_array('All', $displayed_gallery->container_ids)) $lookup = FALSE;
		elseif ($displayed_gallery->source == 'albums' && ($controller->param('gallery')) OR $controller->param('album')) $lookup = FALSE;
        elseif (in_array($displayed_gallery->source, array('random', 'random_images'))) $lookup = FALSE;
		elseif ($controller->param('show')) $lookup = FALSE;
		elseif ($controller->is_cachable() === FALSE) $lookup = FALSE;
        elseif (!NGG_RENDERING_CACHE_ENABLED) $lookup = FALSE;

		// Enqueue any necessary static resources
        if ((!defined('NGG_SKIP_LOAD_SCRIPTS') || !constant('NGG_SKIP_LOAD_SCRIPTS')) && !$this->is_rest_request()) {
		    $controller->enqueue_frontend_resources($displayed_gallery);
        }

		// Try cache lookup, if we're to do so
		$key =  NULL;
		$html = FALSE;
		if ($lookup)
        {
			// The display type may need to output some things
			// even when serving from the cache
			if ($controller->has_method('cache_action')) {
				$retval = $controller->cache_action($displayed_gallery);
			}

			// Output debug message
			$retval .= $this->debug_msg("Lookup!");

			// Some settings affect display types
			$settings = C_NextGen_Settings::get_instance();
			$key_params = apply_filters('ngg_displayed_gallery_cache_params', array(
				$displayed_gallery->get_entity(),
				$url,
				$mode,
				$settings->activateTags,
				$settings->appendType,
				$settings->maxImages,
				$settings->thumbEffect,
				$settings->thumbCode,
				$settings->galSort,
				$settings->galSortDir,
				$this->get_display_type_version($displayed_gallery->get_display_type())
			));

            // Any displayed gallery links on the home page will need to be regenerated if the permalink structure
            // changes
            if (is_home() OR is_front_page()) $key_params[] = get_option('permalink_structure');

			// Try getting the rendered HTML from the cache
			$key = C_Photocrati_Transient_Manager::create_key('displayed_gallery_rendering', $key_params);
            $html = C_Photocrati_Transient_Manager::fetch($key, FALSE);
		}
		else {
		    $retval .= $this->debug_msg("Not looking up in cache as per rules");
        }

        // TODO: This is hack. We need to figure out a more uniform way of detecting dynamic image urls
        if (strpos($html, C_Photocrati_Settings_Manager::get_instance()->dynamic_thumbnail_slug.'/') !== FALSE) {
            $html = FALSE; // forces the cache to be re-generated
        }

        // Output debug messages
        if ($html) $retval .= $this->debug_msg("HIT!");
        else $retval .= $this->debug_msg("MISS!");

		// If a cached version doesn't exist, then create the cache
		if (!$html)
		{
			$retval .= $this->debug_msg("Rendering displayed gallery");

			$current_mode = $controller->get_render_mode();
			$controller->set_render_mode($mode);
            $html = apply_filters(
                'ngg_displayed_gallery_rendering',
                $controller->index_action($displayed_gallery, TRUE),
                $displayed_gallery
            );

			if ($key != null) C_Photocrati_Transient_Manager::update($key, $html, NGG_RENDERING_CACHE_TTL);
		}

		$retval .= $html;

		if (!$return) echo $retval;

		return $retval;
    }

	/**
	 * @return bool
	 */
    function is_rest_request()
    {
    	return defined('REST_REQUEST') || strpos($_SERVER['REQUEST_URI'], 'wp-json') !== FALSE;
    }
}