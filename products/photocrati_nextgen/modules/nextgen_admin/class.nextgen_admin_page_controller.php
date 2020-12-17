<?php

/**
 * @mixin Mixin_NextGen_Admin_Page_Instance_Methods
 * @implements I_NextGen_Admin_Page
 */
class C_NextGen_Admin_Page_Controller extends C_MVC_Controller
{
	static $_instances = array();

    /**
     * @param bool|string $context
     * @return C_NextGen_Admin_Page_Controller
     */
	static function get_instance($context=FALSE)
	{
		if (!isset(self::$_instances[$context])) {
			$klass = get_class();
			self::$_instances[$context] = new $klass($context);
		}
		return self::$_instances[$context];
	}

	function define($context=FALSE)
	{
		if (is_array($context)) $this->name = $context[0];
		else $this->name = $context;

		parent::define($context);
		$this->add_mixin('Mixin_NextGen_Admin_Page_Instance_Methods');
		$this->implement('I_NextGen_Admin_Page');
	}
}

/**
 * @property Mixin_NextGen_Admin_Page_Instance_Methods|C_MVC_Controller|A_MVC_Validation $object
 */
class Mixin_NextGen_Admin_Page_Instance_Methods extends Mixin
{
	/**
     * @param string $privilege
     * @return bool
     *
	 * Authorizes the request
	 */
	function is_authorized_request($privilege=NULL)
	{
        if (!$privilege)
            $privilege = $this->object->get_required_permission();

        if ($this->object->is_post_request() && (!isset($_REQUEST['nonce']) || !M_Security::verify_nonce($_REQUEST['nonce'], $privilege)))
            return FALSE;

        // Ensure that the user has permission to access this page
        return M_Security::is_allowed($privilege);
	}

	/**
	 * Returns the permission required to access this page
	 * @return string
	 */
	function get_required_permission()
	{
		return str_replace(array(' ', "\n", "\t"), '_', $this->object->name);
	}

	// Sets an appropriate screen for NextGEN Admin Pages
	function set_screen()
	{
		$screen = get_current_screen();
		if ($screen) $screen->ngg = TRUE;
		else if (is_null($screen)) {
			$screen = WP_Screen::get($this->object->name);
			$screen->ngg = TRUE;
			set_current_screen($this->object->name);
		}
	}


	/**
	 * Enqueues resources required by a NextGEN Admin page
	 */
	function enqueue_backend_resources()
	{
		$this->set_screen();

		if (C_NextGen_Admin_Page_Manager::is_requested()) {
			M_NextGen_Admin::enqueue_common_admin_static_resources();
		}

		wp_enqueue_script('jquery');
		$this->object->enqueue_jquery_ui_theme();
		wp_enqueue_script('photocrati_ajax');
		wp_enqueue_script('jquery-ui-accordion');

		wp_enqueue_style( 
			'imagely-admin-font', 
			'https://fonts.googleapis.com/css?family=Lato:300,400,700,900', 
			array(),
			NGG_SCRIPT_VERSION );
		if (method_exists('M_Gallery_Display', 'enqueue_fontawesome'))
            M_Gallery_Display::enqueue_fontawesome();

		// Ensure select2
		wp_enqueue_style('ngg_select2');
		wp_enqueue_script('ngg_select2');
	}

	function enqueue_jquery_ui_theme()
	{
		$settings = C_NextGen_Settings::get_instance();
		wp_enqueue_style(
			$settings->jquery_ui_theme,
			is_ssl() ?
				 str_replace('http:', 'https:', $settings->jquery_ui_theme_url) :
				 $settings->jquery_ui_theme_url,
			array(),
			$settings->jquery_ui_theme_version
		);
	}

	/**
	 * Returns the page title
	 * @return string
	 */
	function get_page_title()
	{
		return $this->object->name;
	}

	/**
	 * Returns the page heading
	 * @return string
	 */
	function get_page_heading()
	{
		return $this->object->get_page_title();
	}

	/**
	 * Returns a header message
	 * @return string
	 */
	function get_header_message()
	{
	    $message = '';

		if (defined('NGG_PRO_PLUGIN_VERSION') || defined('NGG_PLUS_PLUGIN_VERSION'))
			$message = '<p>' . __("Good work. Keep making the web beautiful.", 'nggallery') . '</p>';

		return $message;
	}

	/**
	 * Returns the type of forms to render on this page
	 * @return string
	 */
	function get_form_type()
	{

		return is_array($this->object->context) ?
			$this->object->context[0] : $this->object->context;
	}

	function get_success_message()
	{
		return __("Saved successfully", 'nggallery');
	}

	/**
	 * Returns an accordion tab, encapsulating the form
	 * @param C_Form $form
     * @return string
	 */
	function to_accordion_tab($form)
	{
		return $this->object->render_partial('photocrati-nextgen_admin#accordion_tab', array(
			'id'		=>	$form->get_id(),
			'title'		=>	$form->get_title(),
			'content'	=>	$form->render(TRUE)
		), TRUE);
	}

	/**
	 * Returns the forms registered for the current get_form_type()
	 * @return array
	 */
	function get_forms()
	{
		$forms = array();
        $form_manager = C_Form_Manager::get_instance();
		foreach ($form_manager->get_forms($this->object->get_form_type()) as $form) {
			$forms[] = $this->object->get_registry()->get_utility('I_Form', $form);
		}
		return $forms;
	}

	/**
	 * Gets the action to be executed
	 * @return string
	 */
	function _get_action()
	{
		$retval = preg_quote($this->object->param('action'), '/');
		$retval = strtolower(preg_replace(
			"/[^\w]/",
			'_',
			$retval
		));
		return preg_replace("/_{2,}/", "_", $retval).'_action';
	}

	/**
	 * Returns the template to be rendered for the index action
	 * @return string
	 */
	function index_template()
	{
		return 'photocrati-nextgen_admin#nextgen_admin_page';
	}

	/**
	 * Returns a list of parameters to include when rendering the view
	 * @return array
	 */
	function get_index_params()
	{
		return array();
	}

    function show_save_button()
    {
        return TRUE;
    }

	/**
	 * Renders a NextGEN Admin Page using jQuery Accordions
	 */
	function index_action()
	{
		$this->object->enqueue_backend_resources();

		if (($token = $this->object->is_authorized_request()))
		{
			// Get each form. Validate it and save any changes if this is a post
			// request
			$tabs			= array();
			$errors			= array();
			$action 		= $this->object->_get_action();
			$success		= $this->object->param('message');
			if ($success)	$success = $this->object->get_success_message();
			else 			$success = $this->object->is_post_request() ?
										$this->object->get_success_message() : '';

			// First, process the Post request
			if ($this->object->is_post_request() && $this->object->has_method($action)) {
				$this->object->$action($this->object->param($this->context));
			}

            $index_template = $this->object->index_template();

			foreach ($this->object->get_forms() as $form) {
				$form->page = $this->object;
				$form->enqueue_static_resources();
				if ($this->object->is_post_request()) {
					if ($form->has_method($action)) {
                        $form->$action($this->object->param($form->context));
					}
				}

				// This is a strange but necessary hack: this seemingly extraneous use of to_accordion_tab() normally
                // just means that we're rendering the admin content twice but NextGen Pro's pricelist and coupons pages
                // actually depend on echo'ing the $tabs variable here, unlike the 'nextgen_admin_page' template which
                // doesn't make use of the $tabs parameter at all.
                // TLDR: The next two lines are necessary for the pricelist and coupons pages.
				if ($index_template !== 'photocrati-nextgen_admin#nextgen_admin_page')
				    $tabs[] = $this->object->to_accordion_tab($form);
                $forms[] = $form;
      
                if ($form->has_method('get_model') && $form->get_model()) {
                    if ($form->get_model()->is_invalid()) {
                        if (($form_errors = $this->object->show_errors_for($form->get_model(), TRUE))) {
                            $errors[] = $form_errors;
                        }
                        $form->get_model()->clear_errors();
                    }
                }
			}

			// Render the view
			$index_params = array(
				'page_heading'		=>	$this->object->get_page_heading(),
				'tabs'				=>	$tabs,
				'forms'				=>	$forms,
				'errors'			=>	$errors,
				'success'			=>	$success,
				'form_header'		=> FALSE,
				'header_message'	=>	$this->object->get_header_message(),
				'nonce'				=>  M_Security::create_nonce($this->object->get_required_permission()),
				'show_save_button'  =>  $this->object->show_save_button(),
				'model'				=>	$this->object->has_method('get_model') ? $this->object->get_model() : NULL,
				'logo'				=>	$this->object->get_router()->get_static_url('photocrati-nextgen_admin#imagely_icon.png')
			);

			$index_params= array_merge($index_params, $this->object->get_index_params());
			$this->object->render_partial($index_template, $index_params);
		}

		// The user is not authorized to view this page
		else {
			$this->object->render_view('photocrati-nextgen_admin#not_authorized', array(
				'name'	=>	$this->object->name,
				'title'	=>	$this->object->get_page_title()
			));
		}
	}
}
