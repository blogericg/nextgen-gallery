<?php

/**
 * Class C_Attach_Controller
 * @mixin Mixin_Attach_To_Post
 * @mixin Mixin_Attach_To_Post_Display_Tab
 * @implements I_Attach_To_Post_Controller
 */
class C_Attach_Controller extends C_NextGen_Admin_Page_Controller
{
	static $_instances = array();
	var	   $_displayed_gallery;
	var    $_marked_scripts;
	var 	 $_is_rendering;

	static function &get_instance($context='all')
	{
		if (!isset(self::$_instances[$context])) {
			$klass = get_class();
			self::$_instances[$context] = new $klass($context);
		}
		return self::$_instances[$context];
	}

	function define($context = FALSE)
	{
		if (!is_array($context)) $context = array($context);
		array_unshift($context, 'ngg_attach_to_post');
		parent::define($context);
		$this->add_mixin('Mixin_Attach_To_Post');
		$this->add_mixin('Mixin_Attach_To_Post_Display_Tab');
		$this->implement('I_Attach_To_Post_Controller');
	}

	function initialize()
	{
		parent::initialize();
		$this->_load_displayed_gallery();
		if (!has_action('wp_print_scripts',  array($this, 'filter_scripts')))
			add_action('wp_print_scripts', array($this, 'filter_scripts'));

		if (!has_action('wp_print_scripts', array($this, 'filter_styles')))
			add_action('wp_print_scripts', array($this, 'filter_styles'));
	}
}

class Mixin_Attach_To_Post extends Mixin
{
	function _load_displayed_gallery()
	{
		$mapper = C_Displayed_Gallery_Mapper::get_instance();

		// Fetch the displayed gallery by ID
		if (($id = $this->object->param('id'))) {
			$this->object->_displayed_gallery = $mapper->find($id, TRUE);
		}
		else if (isset($_REQUEST['shortcode'])) {
            // Fetch the displayed gallery by shortcode
            $shortcode = base64_decode($_REQUEST['shortcode']);

            // $shortcode lacks the opening and closing brackets but still begins with 'ngg ' or 'ngg_images ' which are not parameters
            $params = preg_replace('/^(ngg|ngg_images) /i', '', $shortcode, 1);
            $params = stripslashes($params);
			$params = str_replace(array('[',']'), array('&#91;', '&#93;'), $params);
			$params = shortcode_parse_atts($params);

			$this->object->_displayed_gallery = C_Displayed_Gallery_Renderer::get_instance()->params_to_displayed_gallery($params);
		}

		// If all else fails, then create fresh with a new displayed gallery
		if (empty($this->object->_displayed_gallery)) $this->object->_displayed_gallery = $mapper->create();
	}



	/**
	 * Gets all dependencies for a particular resource that has been registered using wp_register_style/wp_register_script
	 * @param $handle
	 * @param $type
	 *
	 * @return array
	 */
	function get_resource_dependencies($handle, $type)
	{
		$retval = array();

		$wp_resources = $GLOBALS[$type];

		if (($index = array_search($handle, $wp_resources->registered)) !== FALSE) {
			$registered_script = $wp_resources->registered[$index];
			if ($registered_script->deps) {
				foreach ($registered_script->deps as $dep) {
					$retval[] = $dep;
					$retval = array_merge($retval, $this->get_script_dependencies($handle));
				}
			}
		}

		return $retval;
	}

	function get_script_dependencies($handle)
	{
		return $this->get_resource_dependencies($handle, 'wp_scripts');
	}

	function get_style_dependencies($handle)
	{
		return $this->get_resource_dependencies($handle, 'wp_styles');
	}

	function get_ngg_provided_resources($type)
	{
		$wp_resources = $GLOBALS[$type];

		$retval = array();

		foreach ($wp_resources->queue as $handle) {
			$script = $wp_resources->registered[$handle];

			if (strpos($script->src, plugin_dir_url(NGG_PLUGIN_BASENAME)) !== FALSE) {
				$retval[] = $handle;
			}

			if (defined('NGG_PRO_PLUGIN_BASENAME') && strpos($script->src, plugin_dir_url(NGG_PRO_PLUGIN_BASENAME)) !== FALSE) {
				$retval[] = $handle;
			}

			if (defined('NGG_PLUS_PLUGIN_BASENAME') && strpos($script->src, plugin_dir_url(NGG_PLUS_PLUGIN_BASENAME)) !== FALSE) {
				$retval[] = $handle;
			}
		}

		return array_unique($retval);
	}

	function get_ngg_provided_scripts()
	{
		return $this->get_ngg_provided_resources('wp_scripts');
	}

	function get_ngg_provided_styles()
	{
		return $this->get_ngg_provided_resources('wp_styles');
	}

	function get_igw_allowed_scripts()
	{
		$retval = array();

		foreach ($this->get_ngg_provided_scripts() as $handle) {
			$retval[] = $handle;
			$retval = array_merge($retval, $this->get_script_dependencies($handle));
		}

		foreach ($this->get_display_type_scripts() as $handle) {
			$retval[] = $handle;
			$retval = array_merge($retval, $this->get_script_dependencies($handle));
		}

		foreach ($this->attach_to_post_scripts as $handle) {
			$retval[] = $handle;
			$retval = array_merge($retval, $this->get_script_dependencies($handle));
		}

		return array_unique(apply_filters('ngg_igw_approved_scripts', $retval));
	}

	function get_display_type_scripts()
	{
		global $wp_scripts;

		$wp_scripts->old_queue = $wp_scripts->queue;
		$wp_scripts->queue = array();

		$mapper = C_Display_Type_Mapper::get_instance();
		foreach ($mapper->find_all() as $display_type) {
			$form = C_Form::get_instance($display_type->name);
			$form->enqueue_static_resources();
		}

		$retval = $wp_scripts->queue;
		$wp_scripts->queue = $wp_scripts->old_queue;
		unset($wp_scripts->old_queue);

		return $retval;
	}


	function get_display_type_styles()
	{
		global $wp_styles;

		$wp_styles->old_queue = $wp_styles->queue;
		$wp_styles->queue = array();

		$mapper = C_Display_Type_Mapper::get_instance();
		foreach ($mapper->find_all() as $display_type) {
			$form = C_Form::get_instance($display_type->name);
			$form->enqueue_static_resources();
		}

		$retval = $wp_styles->queue;
		$wp_styles->queue = $wp_styles->old_queue;
		unset($wp_styles->old_queue);

		return $retval;
	}


	function get_igw_allowed_styles()
	{
		$retval = array();

		foreach ($this->get_ngg_provided_styles() as $handle) {
			$retval[] = $handle;
			$retval = array_merge($retval, $this->get_style_dependencies($handle));
		}

		foreach ($this->get_display_type_styles() as $handle) {
			$retval[] = $handle;
			$retval = array_merge($retval, $this->get_style_dependencies($handle));
		}

		foreach ($this->attach_to_post_styles as $handle) {
			$retval[] = $handle;
			$retval = array_merge($retval, $this->get_style_dependencies($handle));
		}

		return array_unique(apply_filters('ngg_igw_approved_styles', $retval));
	}

	function filter_scripts()
	{
		global $wp_scripts;

		$new_queue          = array();
		$current_queue      = $wp_scripts->queue;
		$approved   = $this->get_igw_allowed_scripts();

		foreach ($current_queue as $handle){
			if (in_array($handle, $approved)) $new_queue[] = $handle;
		}

		$wp_scripts->queue = $new_queue;
	}

	function filter_styles()
	{
		global $wp_styles;

		$new_queue          = array();
		$current_queue      = $wp_styles->queue;
		$approved   = $this->get_igw_allowed_styles();

		foreach ($current_queue as $handle){
			if (in_array($handle, $approved)) $new_queue[] = $handle;
		}

		$wp_styles->queue = $new_queue;
	}


	function mark_script($handle)
	{
		return FALSE;
	}

	function enqueue_display_tab_js()
	{
		// Enqueue backbone.js library, required by the Attach to Post display tab
		wp_enqueue_script('backbone'); // provided by WP
		$this->object->mark_script('backbone');

		// Enqueue the backbone app for the display tab
		// Get all entities used by the display tab
		$context = 'attach_to_post';
		$gallery_mapper		= $this->get_registry()->get_utility('I_Gallery_Mapper',		$context);
		$album_mapper		= $this->get_registry()->get_utility('I_Album_Mapper',			$context);
		$image_mapper		= $this->get_registry()->get_utility('I_Image_Mapper',          $context);
		$display_type_mapper= $this->get_registry()->get_utility('I_Display_Type_Mapper',	$context);
		$sources            = C_Displayed_Gallery_Source_Manager::get_instance();
		$settings			= C_NextGen_Settings::get_instance();

		// Get the nextgen tags
		global $wpdb;
		$tags = $wpdb->get_results(
			"SELECT DISTINCT name AS 'id', name FROM {$wpdb->terms}
                        WHERE term_id IN (
                                SELECT term_id FROM {$wpdb->term_taxonomy}
                                WHERE taxonomy = 'ngg_tag'
                        )");
		$all_tags = new stdClass;
		$all_tags->name = "All";
		$all_tags->id   = "All";
		array_unshift($tags, $all_tags);

		$display_types = array();
		$registry = C_Component_Registry::get_instance();

		$display_type_mapper->flush_query_cache();
		foreach ($display_type_mapper->find_all() as $display_type) {

			if ((isset($display_type->hidden_from_igw) && $display_type->hidden_from_igw) || (isset($display_type->hidden_from_ui) && $display_type->hidden_from_ui))
				continue;

			$available = $registry->is_module_loaded($display_type->name);
			if (!apply_filters('ngg_atp_show_display_type', $available, $display_type))
				continue;

			// Some display types were saved with values like "nextgen-gallery-pro/modules/nextgen_pro_imagebrowser/static/preview.jpg"
			// as the preview_image_relpath property
			if (strpos($display_type->preview_image_relpath, '#') === FALSE) {
				$static_path = preg_replace("#^.*static/#", "", $display_type->preview_image_relpath);
				$module_id = isset($display_type->module_id) ? $display_type->module_id : $display_type->name;
				if ($module_id == 'photocrati-nextgen_basic_slideshow') {
					$display_type->module_id = $module_id = 'photocrati-nextgen_basic_gallery';
				}

				$display_type->preview_image_relpath = "{$module_id}#{$static_path}";
				$display_type_mapper->save($display_type);
				$display_type_mapper->flush_query_cache();
			}	

			$display_type->preview_image_url = M_Static_Assets::get_static_url($display_type->preview_image_relpath);
			$display_types[] = $display_type;
		}

		usort($display_types, array($this->object, '_display_type_list_sort'));


		wp_enqueue_script(
			'ngg_display_tab',
			$this->get_static_url('photocrati-attach_to_post#display_tab.js'),
			array('jquery', 'backbone', 'photocrati_ajax'),
			NGG_SCRIPT_VERSION
		);
		$this->object->mark_script('ngg_display_tab');
		wp_localize_script('ngg_display_tab', 'igw_data', array(
			'displayed_gallery_preview_url'	=>	$settings->gallery_preview_url,
			'displayed_gallery'				=>	$this->object->_displayed_gallery->get_entity(),
			'sources'						=>	$sources->get_all(),
			'gallery_primary_key'			=>	$gallery_mapper->get_primary_key_column(),
			'galleries'						=>	$gallery_mapper->find_all(),
			'albums'						=>	$album_mapper->find_all(),
			'tags'							=>	$tags,
			'display_types'					=>	$display_types,
			'nonce'							=>	M_Security::create_nonce('nextgen_edit_displayed_gallery'),
			'image_primary_key'				=>	$image_mapper->get_primary_key_column(),
			'display_type_priority_base'	=>	NGG_DISPLAY_PRIORITY_BASE,
			'display_type_priority_step'	=>	NGG_DISPLAY_PRIORITY_STEP,
			'shortcode_ref'					=>	isset($_REQUEST['ref']) ? floatval($_REQUEST['ref']) : null,
			'shortcode_defaults'            =>  array(
			    'order_by'                  =>  $settings->galSort,
                'order_direction'           =>  $settings->galSortDir,
                'returns'                   =>  'included',
                'maximum_entity_count'      =>  $settings->maximum_entity_count
            ),
			'shortcode_attr_replacements'   =>  array(
			    'source'                    =>  'src',
                'container_ids'             =>  'ids',
                'display_type'              =>  'display'
            ),
			'i18n'							=>	array(
				'sources'					=>	__('Are you inserting a Gallery (default), an Album, or images based on Tags?',							     'nggallery'),
				'optional'					=>	__('(optional)',							 'nggallery'),
				'slug_tooltip'				=>	__('Sets an SEO-friendly name to this gallery for URLs. Currently only in use by the Pro Lightbox', 'nggallery'),
				'slug_label'				=>	__('Slug',									 'nggallery'),
				'no_entities'				=>	__('No entities to display for this source', 'nggallery'),
				'exclude_question'			=>	__('Exclude?', 								 'nggallery'),
				'select_gallery'			=>	__('Select a Gallery',						 'nggallery'),
				'galleries'					=>	__('Select one or more galleries (click in box to see available galleries).', 							 'nggallery'),
				'albums'					=>	__('Select one album (click in box to see available albums).',								 'nggallery'),
				
			)
		));
	}

	function start_resource_monitoring()
	{
		global $wp_scripts, $wp_styles;

		$this->attach_to_post_scripts = array();
		$this->attach_to_post_styles = array();
		$wp_styles->before_monitoring = $wp_styles->queue;
		$wp_scripts->before_monitoring = $wp_styles->queue;
	}

	function stop_resource_monitoring()
	{
		global $wp_scripts, $wp_styles;
		$this->attach_to_post_scripts = array_diff($wp_scripts->queue, $wp_scripts->before_monitoring);
		$this->attach_to_post_styles = array_diff($wp_styles->queue, $wp_styles->before_monitoring);
	}

	function enqueue_backend_resources()
	{
		$this->start_resource_monitoring();
		$this->call_parent('enqueue_backend_resources');

		// Enqueue frame event publishing
		wp_enqueue_script('frame_event_publisher');

		// Enqueue JQuery UI libraries
		wp_enqueue_script('jquery-ui-tabs');
		wp_enqueue_script('jquery-ui-sortable');
		wp_enqueue_script('jquery-ui-tooltip');
		wp_enqueue_script(
			'ngg_tabs',
			$this->get_static_url('photocrati-attach_to_post#ngg_tabs.js'),
		   	FALSE,
			NGG_SCRIPT_VERSION
		);

		wp_enqueue_style( 'buttons' );
		
		// Ensure select2
		wp_enqueue_style('ngg_select2');
		wp_enqueue_script('ngg_select2');

		// Ensure that the Photocrati AJAX library is loaded
		wp_enqueue_script('photocrati_ajax');

		// Enqueue logic for the Attach to Post interface as a whole
		wp_enqueue_script(
			'ngg_attach_to_post_js',
			$this->get_static_url('photocrati-attach_to_post#attach_to_post.js'),
			array(),
			NGG_SCRIPT_VERSION
		);
		
		wp_enqueue_style(
			'ngg_attach_to_post',
			$this->get_static_url('photocrati-attach_to_post#attach_to_post.css'),
			array(),
			NGG_SCRIPT_VERSION
		);

		wp_dequeue_script('debug-bar-js');
		wp_dequeue_style('debug-bar-css');
		
		$this->enqueue_display_tab_js();

		do_action('ngg_igw_enqueue_scripts');
		do_action('ngg_igw_enqueue_styles');

		$this->stop_resource_monitoring();
	}

	/**
	 * Renders the interface
	 * @param bool $return
	 * @return string
	 */
	function index_action($return=FALSE)
	{
		$this->object->enqueue_backend_resources();

		$this->object->do_not_cache();

		// Enqueue resources
		return $this->object->render_view('photocrati-attach_to_post#attach_to_post', array(
			'page_title'	=>	$this->object->_get_page_title(),
			'tabs'			=>	$this->object->_get_main_tabs(),
			'logo'			=>  $this->get_static_url('photocrati-nextgen_admin#imagely_icon.png')
		), $return);
	}


	/**
	 * Displays a preview image for the displayed gallery
	 */
	function preview_action()
	{

		$found_preview_pic = FALSE;

		$dyn_thumbs		= C_Dynamic_Thumbnails_Manager::get_instance();
		$storage		= C_Gallery_Storage::get_instance();
		$image_mapper	= C_Image_Mapper::get_instance();

		// Get the first entity from the displayed gallery. We will use this
		// for a preview pic
		$results = $this->object->_displayed_gallery->get_included_entities(1);
		$entity = array_pop($results);

		$image = FALSE;
		if ($entity) {
			// This is an album or gallery
			if (isset($entity->previewpic)) {
				$image = (int)$entity->previewpic;
				if (($image = $image_mapper->find($image))) {
					$found_preview_pic = TRUE;
				}
			}

			// Is this an image
			else if (isset($entity->galleryid)) {
				$image = $entity;
				$found_preview_pic = TRUE;
			}
		}

		// Were we able to find a preview pic? If so, then render it
		$image_size = $dyn_thumbs->get_size_name(array(
			'width'     =>  300,
			'height'    =>  200,
			'quality'   =>  90,
			'type'		=>	'jpg',
			'watermark'	=>	FALSE,
			'crop'		=>	TRUE
		));;

		add_filter('ngg_before_save_thumbnail', array(&$this, 'set_igw_placeholder_text'));

		$found_preview_pic = $storage->render_image($image, $image_size, TRUE);

		remove_filter('ngg_before_save_thumbnail', array(&$this, 'set_igw_placeholder_text'));

		// Render invalid image if no preview pic is found
		if (!$found_preview_pic) {
			$filename = $this->object->get_static_abspath('photocrati-attach_to_post#invalid_image.png');
			$this->set_content_type('image/png');
			readfile($filename);
			$this->render();
		}
	}

	/**
	 * Filter for ngg_before_save_thumbnail
	 * @param stdClass $thumbnail
	 * @return stdClass
	 */
	function set_igw_placeholder_text($thumbnail)
	{
		$settings = C_NextGen_Settings::get_instance();
		$thumbnail->applyFilter(IMG_FILTER_BRIGHTNESS, -25	);

		$watermark_settings = apply_filters('ngg_igw_placeholder_line_1_settings', array(
			'text'			=>	__("NextGEN Gallery", 'nggallery'),
			'font_color'	=>	'ffffff',
			'font'			=>	'YanoneKaffeesatz-Bold.ttf',
			'font_size'		=>	32
		));

		if ($watermark_settings) {
			$thumbnail->watermarkText = $watermark_settings['text'];
			$thumbnail->watermarkCreateText(
				$watermark_settings['font_color'],
				$watermark_settings['font'],
				$watermark_settings['font_size'],
				100
			);
			$thumbnail->watermarkImage('topCenter', 0, 72);
		}

		$watermark_settings = apply_filters('ngg_igw_placeholder_line_2_settings', array(
			'text'			=>	__("Click to edit", 'nggallery'),
			'font_color'	=>	'ffffff',
			'font'			=>	'YanoneKaffeesatz-Bold.ttf',
			'font_size'		=>	15
		));

		if ($watermark_settings) {
			$thumbnail->watermarkText = $watermark_settings['text'];
			$thumbnail->watermarkCreateText(
				$watermark_settings['font_color'],
				$watermark_settings['font'],
				$watermark_settings['font_size'],
				100
			);
			$thumbnail->watermarkImage('topCenter', 0, 108);
		}

		return $thumbnail;
	}

	/**
	 * Returns the page title of the Attach to Post interface
	 * @return string
	 */
	function _get_page_title()
	{
		return __('NextGEN Gallery - Attach To Post', 'nggallery');
	}


	/**
	 * Returns the main tabs displayed on the Attach to Post interface
	 * @return array
	 */
	function _get_main_tabs()
	{
		$retval = array();

		if (M_Security::is_allowed('NextGEN Manage gallery')) {
			$retval['displayed_tab']    = array(
				'content'   => $this->object->_render_display_tab(),
				'title'     => __('Insert Into Page', 'nggallery')
			);
		}

		if (M_Security::is_allowed('NextGEN Upload images')) {
			$retval['create_tab']       = array(
				'content'   =>  $this->object->_render_create_tab(),
				'title'     =>  __('Upload Images', 'nggallery')
			);
		}

		if (M_Security::is_allowed('NextGEN Manage others gallery') && M_Security::is_allowed('NextGEN Manage gallery')) {
			$retval['galleries_tab']    = array(
				'content'   =>  $this->object->_render_galleries_tab(),
				'title'     =>  __('Manage Galleries', 'nggallery')
			);
		}

		if (M_Security::is_allowed('NextGEN Edit album')) {
			$retval['albums_tab']       = array(
				'content'   =>  $this->object->_render_albums_tab(),
				'title'     =>  __('Manage Albums', 'nggallery')
			);
		}

		// if ($sec_actor->is_allowed('NextGEN Manage tags')) {
		// 	$retval['tags_tab']         = array(
		// 		'content'   =>  $this->object->_render_tags_tab(),
		// 		'title'     =>  __('Manage Tags', 'nggallery')
		// 	);
		// }

		return $retval;
	}

	/**
	 * Renders a NextGen Gallery page in an iframe, suited for the attach to post
	 * interface
	 * @param string $page
	 * @param null|int $tab_id (optional)
	 * @return string
	 */
	function _render_ngg_page_in_frame($page, $tab_id = null)
	{
		$frame_url = admin_url("/admin.php?page={$page}&attach_to_post");
		$frame_url = nextgen_esc_url($frame_url);

		if ($tab_id) {
			$tab_id = " id='ngg-iframe-{$tab_id}'";
		}

		return "<iframe name='{$page}' frameBorder='0'{$tab_id} class='ngg-attach-to-post ngg-iframe-page-{$page}' scrolling='yes' src='{$frame_url}'></iframe>";
	}

	/**
	 * Renders the display tab for adjusting how images/galleries will be displayed
	 * @return string
	 */
	function _render_display_tab()
	{
		return $this->object->render_partial('photocrati-attach_to_post#display_tab', array(
			'messages'	=>	array(),
			'displayed_gallery' => $this->object->_displayed_gallery,
			'tabs'		=>	$this->object->_get_display_tabs()
		), TRUE);
	}


	/**
	 * Renders the tab used primarily for Gallery and Image creation
	 * @return string
	 */
	function _render_create_tab()
	{
		return $this->object->_render_ngg_page_in_frame('ngg_addgallery', 'create_tab');
	}


	/**
	 * Renders the tab used for Managing Galleries
	 * @return string
	 */
	function _render_galleries_tab()
	{
		return $this->object->_render_ngg_page_in_frame('nggallery-manage-gallery', 'galleries_tab');
	}


	/**
	 * Renders the tab used for Managing Albums
	 */
	function _render_albums_tab()
	{
		return $this->object->_render_ngg_page_in_frame('nggallery-manage-album', 'albums_tab');
	}


	/**
	 * Renders the tab used for Managing Albums
	 * @return string
	 */
	function _render_tags_tab()
	{
		return $this->object->_render_ngg_page_in_frame('nggallery-tags', 'tags_tab');
	}
}