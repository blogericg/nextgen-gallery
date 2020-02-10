<?php

class C_NextGen_First_Run_Notification_Wizard
{
    protected static $wizard = NULL;
    protected static $_instance = NULL;

    /**
     * @return bool
     */
    public function is_renderable()
    {
        return is_null(self::$wizard) ? FALSE : TRUE;
    }

    /**
     * @return string
     */
    public function render()
    {
        if (!self::$wizard)
            return '';

        $wizard = self::$wizard;
        return __('Thanks for installing NextGEN Gallery! Want help creating your first gallery?', 'nggallery')
            . ' <a data-ngg-wizard="' . $wizard->get_id() . '" class="ngg-wizard-invoker" href="' . esc_url(add_query_arg('ngg_wizard', $wizard->get_id())) . '">' . __('Launch the Gallery Wizard', 'nggallery') . '</a>. '
            . __('If you close this message, you can also launch the Gallery Wizard at any time from the', 'nggallery')
            . ' <a href="' . esc_url(admin_url('admin.php?page=nextgen-gallery')) . '">' . __('NextGEN Overview page', 'nggallery') . '</a>.';
    }

    public function get_css_class()
    {
        return 'updated';
    }

    public function is_dismissable()
    {
        return TRUE;
    }

    public function dismiss($code)
    {
        return array(
            'handled' => TRUE
        );
    }

    /**
     * @return C_NextGen_First_Run_Notification_Wizard
     */
    public static function get_instance()
    {
        if (!isset(self::$_instance)) {
            $klass = get_class();
            self::$_instance = new $klass;
        }
        return self::$_instance;
    }

    static public function set_wizard($wizard)
    {
        self::$wizard = $wizard;
    }
}