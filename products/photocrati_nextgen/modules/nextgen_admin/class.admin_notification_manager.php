<?php

class C_Review_Notice
{
	function __construct($params = array())
	{
        $this->_data['name']    = $params['name'];
        $this->_data['range']   = $params['range'];
        $this->_data['follows'] = $params['follows'];
	}

	function get_name()
    {
        return $this->_data['name'];
    }

    function get_gallery_count()
    {
        // Get the total # of galleries if we don't have them
        $settings = C_NextGen_Settings::get_instance();
        $count = $settings->get('gallery_count', FALSE);
        if (!$count) $count = M_NextGen_Admin::update_gallery_count_setting();
        return $count;
    }

    function get_range()
    {
        return $this->_data['range'];
    }

	function is_renderable()
	{
		return ($this->is_on_dashboard() || $this->is_on_ngg_admin_page())
                && $this->is_at_gallery_count()
                && $this->is_previous_notice_dismissed()
                && $this->gallery_created_flag_check();
	}

	function gallery_created_flag_check()
    {
        $settings = C_NextGen_Settings::get_instance();
        return $settings->get('gallery_created_after_reviews_introduced');
    }

	function is_at_gallery_count()
	{
		$retval  = FALSE;
		$range   = $this->_data['range'];
		$count   = $this->get_gallery_count();
		$manager = C_Admin_Notification_Manager::get_instance();

		// Determine if we match the current range
        if ($count >= $range['min'] && $count <= $range['max'])
        {
            $retval = TRUE;
        }

        // If the current number of galleries exceeds the parent notice's maximum we should dismiss the parent
        if (!empty($this->_data['follows']))
        {
            $follows = $this->_data['follows'];
            $parent_range = $follows->get_range();
            if ($count > $parent_range['max'] && !$manager->is_dismissed($follows->get_name()))
            {
                $manager->dismiss($follows->get_name(), 2);
            }
        }

		return $retval;
	}

	function is_previous_notice_dismissed($level = FALSE)
	{
        $retval = FALSE;
        $manager = C_Admin_Notification_Manager::get_instance();

        if (empty($level))
            $level = $this;

        if (!empty($level->_data['follows']))
        {
            $parent = $level->_data['follows'];
            $retval = $manager->is_dismissed($parent->get_name());
            if (!$retval && !empty($parent->_data['follows']))
                $retval = $this->is_previous_notice_dismissed($parent);
        }
        else $retval = TRUE;

        return $retval;
	}

	function is_on_dashboard()
	{
		return preg_match('#/wp-admin/?(index\.php)?$#', $_SERVER['REQUEST_URI']) == TRUE;
	}

	function is_on_ngg_admin_page()
	{
	    // Do not show this notification inside of the ATP popup
		return (preg_match("/wp-admin.*(ngg|nextgen).*/", $_SERVER['REQUEST_URI'])
                || (isset($_REQUEST['page']) && preg_match("/ngg|nextgen/", $_REQUEST['page'])))
               && (strpos(strtolower($_SERVER['REQUEST_URI']), '&attach_to_post') == false);
	}

	function render()
	{
		$view = new C_MVC_View('photocrati-nextgen_admin#review_notice', array('number' => $this->get_gallery_count()));
		return $view->render(TRUE);
	}

	function dismiss($code)
	{
		$retval  = array(
			'dismiss'      => TRUE,
			'persist'      => TRUE,
			'success'      => TRUE,
			'code'         => $code,
			'dismiss_code' => $code
		);

		$manager = C_Admin_Notification_Manager::get_instance();

		if ($code == 1 || $code == 3)
		{
			$retval['review_level_1'] = $manager->dismiss('review_level_1', 2);
			$retval['review_level_2'] = $manager->dismiss('review_level_2', 2);
            $retval['review_level_3'] = $manager->dismiss('review_level_3', 2);
		}

		return $retval;
	}
}


class C_Admin_Notification_Wrapper
{
	public $_name;
	public $_data;
	
	function __construct($name, $data)
	{
		$this->_name = $name;
		$this->_data = $data;
	}
	
	function is_renderable()
	{
		return true;
	}
	
	function is_dismissable()
	{
		return true;
	}
	
	function render()
	{
		return $this->_data["message"];
	}
}

class C_Admin_Notification_Manager
{
	public $_notifications = array();
	public $_displayed_notice = FALSE;
	public $_dismiss_url = NULL;

	/**
	 * @var C_Admin_Notification_Manager
	 */
	static $_instance = NULL;

    /**
     * @return C_Admin_Notification_Manager
     */
	static function get_instance()
	{
		if (!isset(self::$_instance))
		{
			$klass = get_class();
			self::$_instance = new $klass;
		}
		return self::$_instance;
	}

	function __construct()
	{
		$this->_dismiss_url = site_url('/?ngg_dismiss_notice=1');
	}

	function has_displayed_notice()
	{
		return $this->_displayed_notice;
	}

	function add($name, $handler)
	{
		$this->_notifications[$name] = $handler;
	}

	function remove($name)
	{
		unset($this->_notifications[$name]);
	}

	function render()
	{
		$output= array();

		foreach (array_keys($this->_notifications) as $notice) {
			if (($html = $this->render_notice($notice)))
				$output[] = $html;
		}

		echo implode("\n", $output);
	}

	function is_dismissed($name)
	{
		$retval = FALSE;

		$settings  = C_NextGen_Settings::get_instance();
		$dismissed = $settings->get('dismissed_notifications', array());

		if (isset($dismissed[$name]))
		{
			if (($id = get_current_user_id()))
			{
				if (in_array($id, $dismissed[$name]))
				    $retval = TRUE;
				else if (in_array('unknown', $dismissed[$name]))
				    $retval = TRUE;
			}
		}

		return $retval;
	}

	function dismiss($name, $dismiss_code = 1)
	{
		$response = array();

		if (($handler = $this->get_handler_instance($name)))
		{
			$has_method = method_exists($handler, 'is_dismissable');
			if (($has_method && $handler->is_dismissable()) || !$has_method)
			{
				if (method_exists($handler, 'dismiss'))
				{
					$response = $handler->dismiss($dismiss_code);
					$response['handled'] = TRUE;
				}

				if (is_bool($response))
				    $response = array('dismiss' => $response);

				// Set default key/values
				if (!isset($response['handled'])) $response['handled'] = FALSE;
				if (!isset($response['dismiss'])) $response['dismiss'] = TRUE;
				if (!isset($response['persist'])) $response['persist'] = $response['dismiss'];
				if (!isset($response['success'])) $response['success'] = $response['dismiss'];
				if (!isset($response['code']))	  $response['code'] = $dismiss_code;

				if ($response['dismiss'])
				{
					$settings = C_NextGen_Settings::get_instance();
					$dismissed= $settings->get('dismissed_notifications', array());
					if (!isset($dismissed[$name]))
					    $dismissed[$name] = array();
					$user_id = get_current_user_id();
					$dismissed[$name][] = ($user_id ? $user_id : 'unknown');
					$settings->set('dismissed_notifications', $dismissed);

					if ($response['persist'])
					    $settings->save();
				}
			}
			else $response['error'] = __("Notice is not dismissible", 'nggallery');
		}
		else $response['error'] = __("No handler defined for this notice", 'nggallery');

		return $response;
	}

	function get_handler_instance($name)
	{
		$retval = NULL;

		if (isset($this->_notifications[$name]))
		{
			$handler = $this->_notifications[$name];
			
			if (is_object($handler))
				$retval = $handler;
			elseif (is_array($handler))
				$retval = new C_Admin_Notification_Wrapper($name, $handler);
			elseif (class_exists($handler))
				$retval = call_user_func(array($handler, 'get_instance'), $name);
		}

		return $retval;
	}

	function enqueue_scripts()
	{
		if ($this->has_displayed_notice())
		{
			$router = C_Router::get_instance();
			wp_enqueue_script(
				'ngg_admin_notices',
				$router->get_static_url('photocrati-nextgen_admin#admin_notices.js'),
				array(),
				NGG_SCRIPT_VERSION,
				TRUE
			);
			wp_localize_script('ngg_admin_notices', 'ngg_dismiss_url', $this->_dismiss_url);
		}
	}

	function serve_ajax_request()
	{
		$retval = array('failure' => TRUE);

		if (isset($_REQUEST['ngg_dismiss_notice']))
		{
			if (!headers_sent())
			    header('Content-Type: application/json');

			ob_start();

			if (!isset($_REQUEST['code']))
			    $_REQUEST['code'] = 1;

			if (isset($_REQUEST['name']))
				$retval = $this->dismiss($_REQUEST['name'], intval($_REQUEST['code']));
			else
			    $retval['msg'] = __('Not a valid notice name', 'nggallery');

			ob_end_clean();

			echo json_encode($retval);

			// E_Clean_Exit causes warnings to be appended to XHR responses, potentially breaking the client JS
            if (!defined('NGG_DISABLE_SHUTDOWN_EXCEPTION_HANDLER') || !NGG_DISABLE_SHUTDOWN_EXCEPTION_HANDLER)
            {
                throw new E_Clean_Exit;
            }
            else {
                exit();
            }
		}
	}

	function render_notice($name)
	{
		$retval = '';

		if (($handler = $this->get_handler_instance($name)) && !$this->is_dismissed($name))
		{
			// Does the handler want to render?
			$has_method = method_exists($handler, 'is_renderable');
			if (($has_method && $handler->is_renderable()) || !$has_method)
			{
				$show_dismiss_button = false;
				if (method_exists($handler, 'show_dismiss_button'))
                    $show_dismiss_button = $handler->show_dismiss_button();
				else if (method_exists($handler, 'is_dismissable'))
				    $show_dismiss_button = $handler->is_dismissable();

				$template = method_exists($handler, 'get_mvc_template')
                    ? $handler->get_mvc_template()
                    : 'photocrati-nextgen_admin#admin_notice';

                // The 'inline' class is necessary to prevent our notices from being moved in the DOM
                // see https://core.trac.wordpress.org/ticket/34570 for reference
                $css_class = 'inline ';
                $css_class .= (method_exists($handler, 'get_css_class')  ? $handler->get_css_class()  : 'updated');

                $view = new C_MVC_View($template, array(
					'css_class'           => $css_class,
					'is_dismissable'      => (method_exists($handler, 'is_dismissable') ? $handler->is_dismissable() : FALSE),
					'html'                => (method_exists($handler, 'render')         ? $handler->render()         : ''),
					'show_dismiss_button' => $show_dismiss_button,
					'notice_name'         => $name
				));

				$retval = $view->render(TRUE);

				if (method_exists($handler, 'enqueue_backend_resources'))
				    $handler->enqueue_backend_resources();

                $this->_displayed_notice = TRUE;
			}
		}

		return $retval;
	}
}