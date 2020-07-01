<?php

class C_Mailchimp_OptIn_Notice
{
    /** @var C_Mailchimp_OptIn_Notice $_instance */
    static $_instance = NULL;

    /**
     * @return C_Mailchimp_OptIn_Notice
     */
    static function get_instance()
    {
        if (!self::$_instance)
        {
            $klass = get_class();
            self::$_instance = new $klass;
        }
        return self::$_instance;
    }

    /**
     * @return string
     */
    function get_css_class()
    {
        return 'notice notice-success';
    }

    /**
     * @return bool
     */
    public function is_dismissable()
    {
        return TRUE;
    }

    /**
     * @param $code
     * @return array
     */
    public function dismiss($code)
    {
        return array(
            'handled' => TRUE
        );
    }

    /**
     * @return bool
     */
    function is_renderable()
    {
        if (!C_NextGen_Admin_Page_Manager::is_requested())
            return FALSE;

        $settings = C_NextGen_Settings::get_instance();

        try {
            $time = time();

            $install = new DateTime("@" . $settings->get('installDate'));
            $now     = new DateTime("@" . $time);

            $diff = (int)$install->diff($now)->format('%a days');
            if ($diff >= 14)
                return TRUE;
        }
        // we're returning FALSE right away anyway
        catch (Exception $exception) {}

        return FALSE;
    }

    /**
     * @return string
     */
    function render()
    {
        $manager = C_Admin_Notification_Manager::get_instance();

        $view = new C_MVC_View('photocrati-nextgen_admin#mailchimp_optin', [
            'dismiss_url' => $manager->_dismiss_url . '&name=mailchimp_opt_in&code=1',
            'i18n' => [
                'headline'     => __('Thank you for using NextGen Gallery!', 'nggallery'),
                'message'      => __('Get NextGen updates, blog posts, tinned meat products, and free kittens right in your mailbox.', 'nggallery'),
                'submit'       => __('GIMME', 'nggallery'),
                'confirmation' => __('Thank you for subscribing!', 'nggallery'),
                'email_placeholder' => __('Email Address', 'nggallery'),
                'name_placeholder'  => __('First Name', 'nggallery'),
                'connect_error'     => __('Cannot connect to the registration server right now. Please try again later.', 'nggallery')
            ]
        ]);

        return $view->render(TRUE);
    }
}
