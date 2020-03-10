<?php

class C_Admin_Requirements_Manager
{
    protected $_requirements = array();
    protected $_groups       = array();

    public function __construct()
    {
        $this->set_initial_groups();
    }

    protected function set_initial_groups()
    {
        // Requirements can be added with any group key desired but only registered groups will be displayed
        $this->_groups = apply_filters('ngg_admin_requirements_manager_groups', array(
            'phpext'   => __('NextGen Gallery requires the following PHP extensions to function correctly. Please contact your hosting provider or systems admin and ask them for assistance:', 'nggallery'),
            'phpver'   => __('NextGen Gallery has degraded functionality because of your PHP version. Please contact your hosting provider or systems admin and ask them for assistance:', 'nggallery'),
            'dirperms' => __('NextGen Gallery has found an issue trying to access the following files or directories. Please ensure the following locations have the correct permissions:', 'nggallery')
        ));
    }

    /**
     * @return C_Admin_Requirements_Manager
     */
    private static $_instance = NULL;
    public static function get_instance()
    {
        if (!isset(self::$_instance))
        {
            self::$_instance = new C_Admin_Requirements_Manager();
        }
        return self::$_instance;
    }

    /**
     * @param string $name Unique notification ID
     * @param string $group Choose one of phpext | phpver | dirperms
     * @param callable $callback Method that determines whether the notification should display
     * @param array $data Possible keys: className, message, dismissable
     */
    public function add($name, $group, $callback, $data)
    {
        $this->_requirements[$group][$name] = new C_Admin_Requirements_Notice($name, $callback, $data);
    }

    /**
     * @param string $name
     */
    public function remove($name)
    {
        unset($this->_notifications[$name]);
    }

    public function create_notification()
    {
        foreach ($this->_groups as $groupID => $groupLabel) {

            if (empty($this->_requirements[$groupID])) {
                continue;
            }

            $dismissable = TRUE;
            $notices     = array();

            foreach ($this->_requirements[$groupID] as $key => $requirement) {
                $passOrFail = $requirement->run_callback();

                if (!$passOrFail)
                {
                    // If any of the notices can't be dismissed then all notices in that group can't be dismissed
                    if (!$requirement->is_dismissable())
                    {
                        // Add important notices to the beginning of the list
                        $dismissable = FALSE;
                        array_unshift($notices, $requirement);
                    }
                    else {
                        // Less important notices go to the end of the list
                        $notices[] = $requirement;
                    }
                }
            }

            // Don't display empty group notices
            if (empty($notices))
                continue;

            // Generate the combined message for this group
            $message = '<p>' . $this->_groups[$groupID] . '</p><ul>';
            foreach ($notices as $requirement) {
                // Make non-dismissable notifications bold
                $string = $requirement->is_dismissable() ? $requirement->get_message() : '<strong>' . $requirement->get_message() . '</strong>';
                $message .= '<li>' . $string . '</li>';
            }
            $message .= '</ul>';

            // Generate the notice object
            $name = 'ngg_requirement_notice_' . $groupID . '_' . md5($message);
            $notice = new C_Admin_Requirements_Notice(
                $name,
                '__return_true',
                array(
                    'dismissable' => $dismissable,
                    'message' => $message
                )
            );

            C_Admin_Notification_Manager::get_instance()->add($name, $notice);
        }
    }
}

class C_Admin_Requirements_Notice
{
    protected $_name;
    protected $_data;
    protected $_callback;

    /**
     * C_Admin_Requirements_Notice constructor
     * @param string $name
     * @param callable $callback
     * @param array $data
     */
    public function __construct($name, $callback, $data)
    {
        $this->_name     = $name;
        $this->_data     = $data;
        $this->_callback = $callback;
    }

    /**
     * @return bool
     */
    public function is_renderable()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function is_dismissable()
    {
        return isset($this->_data['dismissable']) ? $this->_data['dismissable'] : TRUE;
    }

    /**
     * @return string
     */
    public function render()
    {
        return $this->_data["message"];
    }

    /**
     * @return string
     */
    public function get_mvc_template()
    {
        return 'photocrati-nextgen_admin#requirement_notice';
    }

    /**
     * @return string
     */
    public function get_name()
    {
        return $this->_name;
    }

    /**
     * @return bool
     */
    public function run_callback()
    {
        if (is_callable($this->_callback))
        {
            return call_user_func($this->_callback);
        }
        else {
            return false;
        }
    }

    /**
     * @return string
     */
    public function get_css_class()
    {
        $prefix = 'notice notice-';
        if ($this->is_dismissable())
            return $prefix . 'warning';
        else
            return $prefix . 'error';
    }

    public function get_message()
    {
        return empty($this->_data['message']) ? "" : $this->_data['message'];
    }
}