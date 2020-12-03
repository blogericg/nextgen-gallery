<?php

class C_NextGen_First_Run_Notification_Wizard
{
    protected static $_instance = NULL;

    /**
     * @return bool
     */
    public function is_renderable()
    {
        return TRUE;
    }

    /**
     * @return string
     */
    public function render()
    {
        $block = <<<EOT
        <style>
            div#ngg-wizard-video {
                width: 710px;
                max-width: 710px;
            }
        </style>
        <div class="hidden" id="ngg-wizard-video" style="border: none">
            <iframe width="640"
                    height="480"
                    src="https://www.youtube.com/embed/ZAYj6D5XXNk"
                    frameborder="0"
                    allow="accelerometer; autoplay; encrypted-media;"
                    allowfullscreen></iframe>
        </div>
EOT;

        return __('Thanks for installing NextGEN Gallery! Want help creating your first gallery?', 'nggallery')
            . ' <a id="ngg-video-wizard-invoker" href="">' . __('Launch the Gallery Wizard', 'nggallery') . '</a>. '
            . __('If you close this message, you can also launch the Gallery Wizard at any time from the', 'nggallery')
            . ' <a href="' . esc_url(admin_url('admin.php?page=nextgen-gallery')) . '">' . __('NextGEN Overview page', 'nggallery') . '</a>.' . $block;
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
        return ['handled' => TRUE];
    }

    public function enqueue_backend_resources()
    {
        wp_enqueue_script(
            'nextgen_first_run_wizard',
            C_Router::get_instance()->get_static_url('photocrati-nextgen_admin#first_run_wizard.js'),
            ['jquery', 'jquery-modal'],
            NGG_SCRIPT_VERSION,
            TRUE
        );
        wp_enqueue_style('jquery-modal');
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
}