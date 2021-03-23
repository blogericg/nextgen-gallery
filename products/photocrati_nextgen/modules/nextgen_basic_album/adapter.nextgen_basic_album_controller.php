<?php

/**
 * Class A_NextGen_Basic_Album_Controller
 * @mixin C_Display_Type_Controller
 * @adapts I_Display_Type_Controller
 * @property C_Display_Type_Controller|A_NextGen_Basic_Album_Controller $object
 */
class A_NextGen_Basic_Album_Controller extends Mixin_NextGen_Basic_Pagination
{
    var $albums = array();

    protected static $alternate_displayed_galleries = [];

    /**
     * @param C_Displayed_Gallery $displayed_gallery
     * @return C_Displayed_Gallery
     */
    function get_alternate_displayed_gallery($displayed_gallery)
    {
        // Prevent recursive checks for further alternates causing additional modifications to the settings array
        $id = $displayed_gallery->id();
        if (!empty(self::$alternate_displayed_galleries[$id]))
            return self::$alternate_displayed_galleries[$id];

        // Without this line the param() method will always return NULL when in wp_enqueue_scripts
        $renderer = C_Displayed_Gallery_Renderer::get_instance('inner');
        $renderer->do_app_rewrites($displayed_gallery);

        $display_settings = $displayed_gallery->display_settings;
        $gallery          = $gallery_slug = $this->param('gallery');

        if ($gallery && strpos($gallery, 'nggpage--') !== 0)
        {
            $result = C_Gallery_Mapper::get_instance()->get_by_slug($gallery);

            if ($result)
                $gallery = $result->{$result->id_field};

            $parent_albums = $displayed_gallery->get_albums();

            $gallery_params = array(
                'source' => 'galleries',
                'container_ids' => array($gallery),
                'display_type' => $display_settings['gallery_display_type'],
                'original_display_type' => $displayed_gallery->display_type,
                'original_settings' => $display_settings,
                'original_album_entities' => $parent_albums
            );
            if (!empty($display_settings['gallery_display_template']))
                $gallery_params['template'] = $display_settings['gallery_display_template'];

            $displayed_gallery = $renderer->params_to_displayed_gallery($gallery_params);
            if (is_null($displayed_gallery->id()))
                $displayed_gallery->id(md5(json_encode($displayed_gallery->get_entity())));
            self::$alternate_displayed_galleries[$id] = $displayed_gallery;
        }

        return $displayed_gallery;
    }

    /**
     * Renders the front-end for the NextGen Basic Album display type
     *
     * @param C_Displayed_Gallery $displayed_gallery
     * @param bool $return
     * @return string
     */
    function index_action($displayed_gallery, $return = FALSE)
    {
        $display_settings = $displayed_gallery->display_settings;

		// We need to fetch the album containers selected in the Attach
		// to Post interface. We need to do this, because once we fetch the
		// included entities, we need to iterate over each entity and assign it
		// a parent_id, which is the album that it belongs to. We need to do this
		// because the link to the gallery, is not /nggallery/gallery--id, but
		// /nggallery/album--id/gallery--id

        // Are we to display a gallery? Ensure our 'gallery' isn't just a paginated album view
        $gallery = $gallery_slug = $this->param('gallery');
        if ($gallery && strpos($gallery, 'nggpage--') !== 0)
        {
            // basic albums only support one per post
            if (isset($GLOBALS['nggShowGallery']))
                return '';
            $GLOBALS['nggShowGallery'] = TRUE;

            $alternate_displayed_gallery = $this->object->get_alternate_displayed_gallery($displayed_gallery);
            if ($alternate_displayed_gallery !== $displayed_gallery)
            {
                $renderer = C_Displayed_Gallery_Renderer::get_instance('inner');

                add_filter('ngg_displayed_gallery_rendering', array($this, 'add_description_to_legacy_templates'), 8, 2);
                add_filter('ngg_displayed_gallery_rendering', array($this, 'add_breadcrumbs_to_legacy_templates'), 9, 2);
                $output = $renderer->display_images($alternate_displayed_gallery, $return);
                remove_filter('ngg_displayed_gallery_rendering', array($this, 'add_breadcrumbs_to_legacy_templates'));
                remove_filter('ngg_displayed_gallery_rendering', array($this, 'add_description_to_legacy_templates'));

                return $output;
            }
        }

		// If we're viewing a sub-album, then we use that album as a container instead
		else if (($album = $this->param('album'))) {

			// Are we to display a sub-album?
            $result    = C_Album_Mapper::get_instance()->get_by_slug($album);
            $album_sub = $result ? $result->{$result->id_field} : null;
            if ($album_sub != null)
                $album = $album_sub;

            $displayed_gallery->entity_ids = array();
			$displayed_gallery->sortorder = array();
            $displayed_gallery->container_ids = ($album === '0' OR $album === 'all') ? array() : array($album);

            $displayed_gallery->display_settings['original_album_id'] = 'a' . $album_sub;
            $displayed_gallery->display_settings['original_album_entities'] = $displayed_gallery->get_albums();
		}

		// Get the albums
        // TODO: This should probably be moved to the elseif block above
		$this->albums = $displayed_gallery->get_albums();

        // None of the above: Display the main album. Get the settings required for display
        $current_page = (int)$this->param('page', $displayed_gallery->id(), 1);
        $offset = $display_settings['galleries_per_page'] * ($current_page - 1);
        $entities = $displayed_gallery->get_included_entities($display_settings['galleries_per_page'], $offset);

        // If there are entities to be displayed
        if ($entities)
        {
            $pagination_result = $this->object->create_pagination(
                $current_page,
                $displayed_gallery->get_entity_count(),
                $display_settings['galleries_per_page'],
                urldecode($this->object->param('ajax_pagination_referrer'))
            );
            $display_settings['entities']          = $entities;
            $display_settings['pagination']        = $pagination_result['output'];
            $display_settings['displayed_gallery'] = $displayed_gallery;
            $display_settings = $this->prepare_legacy_album_params($displayed_gallery->get_entity(), $display_settings);

            if (!empty($display_settings['template']) && $display_settings['template'] != 'default')
            {
                // Add additional parameters
                $this->object->remove_param('ajax_pagination_referrer');
                $display_settings['current_page'] = $current_page;
                $display_settings['pagination_prev'] = $pagination_result['prev'];
                $display_settings['pagination_next'] = $pagination_result['next'];

                // Legacy templates lack a good way of injecting content at the right time
                $this->object->add_mixin('Mixin_NextGen_Basic_Templates');
                $this->object->add_mixin('A_NextGen_Album_Breadcrumbs');
                $this->object->add_mixin('A_NextGen_Album_Descriptions');

                $breadcrumbs = $this->object->render_legacy_template_breadcrumbs($displayed_gallery, $entities);
                $description = $this->object->render_legacy_template_description($displayed_gallery);

                // If enabled enqueue the child entities as JSON for lightboxes to read
                if (A_NextGen_Album_Child_Entities::are_child_entities_enabled($display_settings))
                    $script = A_NextGen_Album_Child_Entities::generate_script($entities);

                $retval = $this->object->legacy_render($display_settings['template'], $display_settings, $return, 'album');

                if (!empty($description))
                    $retval = $description . $retval;
                if (!empty($breadcrumbs))
                    $retval = $breadcrumbs . $retval;
                if (!empty($script))
                    $retval = $retval . $script;

                return $retval;
            }
            else {
                $params = $display_settings;
                $params = $this->object->prepare_display_parameters($displayed_gallery, $params);

                switch ($displayed_gallery->display_type) {
                    case NGG_BASIC_COMPACT_ALBUM:
                        $template = 'compact';
                        break;
                    case NGG_BASIC_EXTENDED_ALBUM:
                        $template = 'extended';
                        break;
                }

                return $this->object->render_partial("photocrati-nextgen_basic_album#{$template}", $params, $return);
            }
        }
        else {
            return $this->object->render_partial('photocrati-nextgen_gallery_display#no_images_found', array(), $return);
        }
    }

    /**
     * Creates a displayed gallery of a gallery belonging to an album. Shared by index_action() and cache_action() to
     * allow lightboxes to open album children directly.
     *
     * @param $gallery
     * @param $display_settings
     * @return C_Displayed_Gallery
     */
    function make_child_displayed_gallery($gallery, $display_settings)
    {
        $gallery->displayed_gallery                    = new C_Displayed_Gallery();
        $gallery->displayed_gallery->container_ids     = array($gallery->{$gallery->id_field});
        $gallery->displayed_gallery->display_settings  = $display_settings;
        $gallery->displayed_gallery->returns           = 'included';
        $gallery->displayed_gallery->source            = 'galleries';
        $gallery->displayed_gallery->images_list_count = $gallery->displayed_gallery->get_entity_count();
        $gallery->displayed_gallery->is_album_gallery  = TRUE;
        $gallery->displayed_gallery->to_transient();

        return $gallery;
    }

    function add_breadcrumbs_to_legacy_templates($html, $displayed_gallery)
    {
        $this->object->add_mixin('A_NextGen_Album_Breadcrumbs');

	    $original_album_entities = array();
	    if (isset($displayed_gallery->display_settings['original_album_entities']))
		    $original_album_entities = $displayed_gallery->display_settings['original_album_entities'];
	    elseif (isset($displayed_gallery->display_settings['original_settings']) && isset($displayed_gallery->display_settings['original_settings']['original_album_entities']))
		    $original_album_entities = $displayed_gallery->display_settings['original_settings']['original_album_entities'];

        $breadcrumbs = $this->object->render_legacy_template_breadcrumbs(
            $displayed_gallery,
            $original_album_entities,
            $displayed_gallery->container_ids
        );

        if (!empty($breadcrumbs))
            $html = $breadcrumbs . $html;

        return $html;
    }

    function add_description_to_legacy_templates($html, $displayed_gallery)
    {
        $this->object->add_mixin('A_NextGen_Album_Descriptions');

        $description = $this->object->render_legacy_template_description($displayed_gallery);

        if (!empty($description))
            $html = $description . $html;

        return $html;
    }

	/**
	 * Gets the parent album for the entity being displayed
	 * @param int $entity_id
	 * @return null|object Album object
	 */
	function get_parent_album_for($entity_id)
	{
		$retval = NULL;

		foreach ($this->albums as $album) {
			if (in_array($entity_id, $album->sortorder)) {
				$retval = $album;
				break;
			}
		}

		return $retval;
	}

    function prepare_legacy_album_params($displayed_gallery, $params)
    {
        $image_mapper = C_Image_Mapper::get_instance();
        $storage      = C_Gallery_Storage::get_instance();
        $image_gen    = C_Dynamic_Thumbnails_Manager::get_instance();

        if (empty($displayed_gallery->display_settings['override_thumbnail_settings']))
        {
            // legacy templates expect these dimensions
            $image_gen_params = array(
                'width'  => 91,
                'height' => 68,
                'crop'   => TRUE
            );
        }
        else {
            // use settings requested by user
            $image_gen_params = array(
                'width'     => $displayed_gallery->display_settings['thumbnail_width'],
                'height'    => $displayed_gallery->display_settings['thumbnail_height'],
                'quality'   => isset($displayed_gallery->display_settings['thumbnail_quality']) ? $displayed_gallery->display_settings['thumbnail_quality'] : 100,
                'crop'      => isset($displayed_gallery->display_settings['thumbnail_crop']) ? $displayed_gallery->display_settings['thumbnail_crop'] : NULL,
                'watermark' => isset($displayed_gallery->display_settings['thumbnail_watermark']) ? $displayed_gallery->display_settings['thumbnail_watermark'] : NULL
            );
        }

        // so user templates can know how big the images are expected to be
        $params['image_gen_params'] = $image_gen_params;

        // Transform entities
        $params['galleries'] = $params['entities'];
        unset($params['entities']);

        foreach ($params['galleries'] as &$gallery) {

            // Get the preview image url
            $gallery->previewurl = '';
            if ($gallery->previewpic && $gallery->previewpic > 0)
            {
                if (($image = $image_mapper->find(intval($gallery->previewpic))))
                {
                    $gallery->previewpic_image = $image;
                    $gallery->previewpic_fullsized_url = $storage->get_image_url($image, 'full');
                    $gallery->previewurl = $storage->get_image_url($image, $image_gen->get_size_name($image_gen_params), TRUE);
                    $gallery->previewname = $gallery->name;
                }
            }

            // Get the page link. If the entity is an album, then the url will
			// look like /nggallery/album--slug.
            $id_field = $gallery->id_field;
			if ($gallery->is_album)
            {
                if ($gallery->pageid > 0)
                    $gallery->pagelink = @get_page_link($gallery->pageid);
                else {
                    $pagelink = $this->object->get_routed_url(TRUE);
                    $pagelink = $this->object->remove_param_for($pagelink, 'album');
                    $pagelink = $this->object->remove_param_for($pagelink, 'gallery');
                    $pagelink = $this->object->remove_param_for($pagelink, 'nggpage');
                    $pagelink = $this->object->set_param_for($pagelink, 'album', $gallery->slug);
                    $gallery->pagelink = $pagelink;
                }
			}

			// Otherwise, if it's a gallery then it will look like
			// /nggallery/album--slug/gallery--slug
			else {
                if ($gallery->pageid > 0) {
					$gallery->pagelink = @get_page_link($gallery->pageid);
				}
                if (empty($gallery->pagelink)) {
                    $pagelink = $this->object->get_routed_url(TRUE);
                    $parent_album = $this->object->get_parent_album_for($gallery->$id_field);
                    if ($parent_album) {
                        $pagelink = $this->object->remove_param_for($pagelink, 'album');
                        $pagelink = $this->object->remove_param_for($pagelink, 'gallery');
                        $pagelink = $this->object->remove_param_for($pagelink, 'nggpage');
                        $pagelink = $this->object->set_param_for(
                            $pagelink,
                            'album',
                            $parent_album->slug
                        );
                    }
                    // Legacy compat: use an album slug of 'all' if we're missing a container_id
                    else if($displayed_gallery->container_ids === array('0')
                         || $displayed_gallery->container_ids === array('')) {
                        $pagelink = $this->object->set_param_for($pagelink, 'album', 'all');
                    }
                    else {
                        $pagelink = $this->object->remove_param_for($pagelink, 'nggpage');
                        $pagelink = $this->object->set_param_for($pagelink, 'album', 'album');
                    }
                    $gallery->pagelink = $this->object->set_param_for(
                        $pagelink,
                        'gallery',
                        $gallery->slug
                    );
                }
			}

			// Mark the child type
            $gallery->entity_type = isset($gallery->is_gallery) && intval($gallery->is_gallery) ? 'gallery' : 'album';

            // If this setting is on we need to inject an effect code
            if (!empty($displayed_gallery->display_settings['open_gallery_in_lightbox']) && $gallery->entity_type == 'gallery')
            {
                $gallery = $this->object->make_child_displayed_gallery($gallery, $displayed_gallery->display_settings);
                if ($this->does_lightbox_support_displayed_gallery($displayed_gallery))
                    $gallery->displayed_gallery->effect_code = $this->object->get_effect_code($gallery->displayed_gallery);
            }

            // Let plugins modify the gallery
            $gallery = apply_filters('ngg_album_galleryobject', $gallery);
        }

        $params['galleries'] = apply_filters('ngg_album_prepared_child_entity', $params['galleries'], $params['displayed_gallery']);

        $params['album'] = reset($this->albums);
        $params['albums'] = $this->albums;

        // Clean up
        unset($storage);
        unset($image_mapper);
        unset($image_gen);
        unset($image_gen_params);

        return $params;
    }

    function _get_js_lib_url()
    {
        return $this->object->get_static_url('photocrati-nextgen_basic_album#init.js');
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
            'nextgen_basic_album_style',
            $this->object->get_static_url('photocrati-nextgen_basic_album#nextgen_basic_album.css'),
            [],
            NGG_SCRIPT_VERSION
        );

        wp_enqueue_style(
            'nextgen_pagination_style',
            $this->get_static_url('photocrati-nextgen_pagination#style.css'),
            [],
            NGG_SCRIPT_VERSION
        );

        wp_enqueue_script('shave.js');

        $ds = $displayed_gallery->display_settings;
        if ((!empty($ds['enable_breadcrumbs']) && $ds['enable_breadcrumbs'])
        ||  (!empty($ds['original_settings']['enable_breadcrumbs']) && $ds['original_settings']['enable_breadcrumbs']))
            wp_enqueue_style(
                'nextgen_basic_album_breadcrumbs_style',
                $this->object->get_static_url('photocrati-nextgen_basic_album#breadcrumbs.css'),
                array(),
                NGG_SCRIPT_VERSION
            );

		$this->enqueue_ngg_styles();
    }

}
