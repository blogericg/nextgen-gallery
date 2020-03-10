<?php

/**
 * Class C_NextGen_Admin_Page_Manager
 * @mixin Mixin_Page_Manager
 * @implements I_Page_Manager
 */
class C_NextGen_Admin_Page_Manager extends C_Component
{
	static $_instance = NULL;
	var $_pages = array();

	/**
	 * Gets an instance of the Page Manager
	 * @param string|false $context (optional)
	 * @return C_NextGen_Admin_Page_Manager
	 */
	static function &get_instance($context=FALSE)
	{
        if (is_null(self::$_instance)) {
            $klass = get_class();
            self::$_instance = new $klass($context);
        }
        return self::$_instance;
	}

	/**
	 * Defines the instance of the Page Manager
	 * @param string $context
	 */
	function define($context=FALSE)
	{
		parent::define($context);
		$this->add_mixin('Mixin_Page_Manager');
		$this->implement('I_Page_Manager');
	}

	/**
	 * Determines if a NextGEN page or post type is being requested
	 * @return bool|string
	 */
	static function is_requested()
	{
		$retval = FALSE;

		if ( self::is_requested_page() ) {

			$retval = self::is_requested_page();

		} elseif ( self::is_requested_post_type() ) {

			$retval = self::is_requested_post_type();

		}

		return apply_filters('is_ngg_admin_page', $retval);
	}

	/**
	 * Determines if a NextGEN Admin page is being requested
	 * @return bool|string
	 */
	static function is_requested_page()
	{
		$retval = FALSE;
		
		// First, check the screen for the "ngg" property. This is how ngglegacy pages register themselves
		$screen = get_current_screen();
		if (property_exists($screen, 'ngg') && $screen->ngg) {
			$retval = $screen->id;
		}

		// Check if it's a registered page
		else {
			foreach (self::get_instance()->get_all() as $slug => $properties) {

				// Are we rendering a NGG added page?
				if (isset($properties['hook_suffix'])) {
					$hook_suffix = $properties['hook_suffix'];
					if (did_action("load-{$hook_suffix}")) {
						$retval = $slug;
						break;
					}
				}
			}
		}
		return $retval;
	}

	/**
	 * Determines if a NextGEN post type is being requested
	 * @return bool|string
	 */
	static function is_requested_post_type()
	{
		$retval = FALSE;
		$screen = get_current_screen();
		foreach (self::get_instance()->get_all() as $slug => $properties) {
			// Are we rendering a NGG post type?
			if (isset($properties['post_type']) && $screen->post_type == $properties['post_type']) {
				$retval = $slug;
				break;
			}
		}

		return $retval;
	}
}

class Mixin_Page_Manager extends Mixin
{
	function add($slug, $properties=array())
	{
		if (!isset($properties['adapter'])) $properties['adapter']	= NULL;
		if (!isset($properties['parent']))	$properties['parent']	= NULL;
		if (!isset($properties['add_menu']))$properties['add_menu']	= TRUE;
		if (!isset($properties['before']))	$properties['before']	= NULL;
		if (!isset($properties['url']))		$properties['url']		= NULL;

		$this->object->_pages[$slug] = $properties;
	}
	
	function move_page($slug, $other_slug, $after = false)
	{
		$page_list = $this->object->_pages;
		
		if (isset($page_list[$slug]) && isset($page_list[$other_slug]))
		{
			$slug_list = array_keys($page_list);
			$item_list = array_values($page_list);
			
			$slug_idx = array_search($slug, $slug_list);
			$item = $page_list[$slug];
			
			unset($slug_list[$slug_idx]);
			unset($item_list[$slug_idx]);
			
			$slug_list = array_values($slug_list);
			$item_list = array_values($item_list);
			
			$other_idx = array_search($other_slug, $slug_list);
			
			array_splice($slug_list, $other_idx, 0, array($slug));
			array_splice($item_list, $other_idx, 0, array($item));
			
			$this->object->_pages = array_combine($slug_list, $item_list);
		}
	}

	function remove($slug)
	{
		unset($this->object->_pages[$slug]);
	}

	function get_all()
	{
		return $this->object->_pages;
	}


	function setup()
	{
		$registry		= $this->get_registry();
		$controllers	= array();
		foreach ($this->object->_pages as $slug => $properties) {

			$post_type      = NULL;
			$page_title 	= "Unnamed Page";
			$menu_title		= "Unnamed Page";
			$permission		= NULL;
			$callback 		= NULL;

			// There's two type of pages we can have. Some are powered by our controllers, and others
			// are powered by WordPress, such as a custom post type page.

			// Is this powered by a controller? If so, we expect an adapter
			if ($properties['adapter']) {

				$controllers[$slug] = $registry->get_utility(
					'I_NextGen_Admin_Page',
					$slug
				);

				$menu_title = $controllers[$slug]->get_page_heading();
				$page_title = $controllers[$slug]->get_page_title();
				$permission = $controllers[$slug]->get_required_permission();
				$callback 	= array(&$controllers[$slug], 'index_action');
			}

			// Is this page powered by another url, such as one that WordPres provides?
			elseif ($properties['url']) {
				$url = $properties['url'];
				if (preg_match("/post_type=([^&]+)/", $url, $matches)) {
					$this->object->_pages[$slug]['post_type'] = $matches[1];
				}
				$slug = $url;

				if (isset($properties['menu_title'])) {
					$menu_title = $properties['menu_title'];
				}
				if (isset($properties['permission'])) {
					$permission = $properties['permission'];
				}
			}

			// Are we to add a menu?
			if ($properties['add_menu'] && current_user_can($permission)) {

				$this->object->_pages[$slug]['hook_suffix'] = add_submenu_page(
					$properties['parent'],
					$page_title,
					$menu_title,
					$permission,
					$slug,
					$callback
				);
				
				if ($properties['before']) {
					global $submenu;

                    if (empty($submenu[$properties['parent']]))
                        $parent = null;
					else
					    $parent = $submenu[$properties['parent']];
					$item_index = -1;
					$before_index = -1;
					
					if ($parent != null) {
						foreach ($parent as $index => $menu) {
						
							// under add_submenu_page, $menu_slug is index 2
							// $submenu[$parent_slug][] = array ( $menu_title, $capability, $menu_slug, $page_title );
							if ($menu[2] == $slug) {
								$item_index = $index;
							}
							else if ($menu[2] == $properties['before']) {
								$before_index = $index;
							}
						}
					}
				
					if ($item_index > -1 && $before_index > -1) {
				
						$item = $parent[$item_index];
					
						unset($parent[$item_index]);
						$parent = array_values($parent);
					
						if ($item_index < $before_index) 
							$before_index--;
						
						array_splice($parent, $before_index, 0, array($item));
					
						$submenu[$properties['parent']] = $parent;
					}
				}
			}
		}

		do_action('ngg_pages_setup');
	}
}


// For backwards compatibility
// TODO: Remove some time in 2018
class C_Page_Manager
{
    /**
     * @return C_NextGen_Admin_Page_Manager
     */
	static function get_instance()
	{
		return C_NextGen_Admin_Page_Manager::get_instance();
	}

	static function is_requested()
	{
		return C_NextGen_Admin_Page_Manager::is_requested();
	}
}
