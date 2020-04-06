<?php

class C_CDN_Jobs_In_Progress_Notice
{
    /** @var C_CDN_Jobs_In_Progress_Notice $_instance */
    static $_instance = NULL;

    static $has_checked   = FALSE;
    static $is_renderable = FALSE;
    static $jobs_count    = NULL;

    /**
     * @return string
     */
    function get_css_class()
    {
        return 'notice notice-warning';
    }

    /**
     * @return bool
     */
    function is_renderable()
    {
        if (!C_NextGen_Admin_Page_Manager::is_requested())
            return FALSE;

        if (!self::$has_checked)
        {
            self::$has_checked   = TRUE;
            self::$jobs_count    = \ReactrIO\Background\Job::get_count_from_queue('cdn', [\ReactrIO\Background\Job::STATUS_QUEUED]);
            self::$is_renderable = (int)self::$jobs_count !== 0 ? TRUE : FALSE;
        }

        return self::$is_renderable;
    }

    /**
     * @return array
     */
    public function get_i18n()
    {
        return [
            'refreshing_label'  => __('Refreshing &hellip;', 'nggallery'),
            'pending_label'     => __('There are %d CDN job(s) pending.', 'nggallery'),
            'expand_link'       => sprintf(__('<a href="#" id="%s">Expand details &darr;</a>', 'nggallery'), 'ngg_cdn_jobs_in_progress_notice_expand_link'),
            'finished_label'    => sprintf(__('All CDN jobs are finished. You may now <a href="#" id="%s">refresh the page</a>.'),   'ngg_cdn_jobs_in_progress_notice_finished_link'),
            'minimize_label'    => __('Minimize details &uarr;', 'nggallery'),
            'refresh_countdown' => __('Refreshing in %d seconds.', 'nggallery')
        ];
    }

    function enqueue_static_resources()
    {
        $router   = C_Router::get_instance();
        $settings = C_NextGen_Settings::get_instance();

        wp_enqueue_script(
            'ngg_cdn_jobs_in_progress_notice_js',
            $router->get_static_url('imagely-cdn-jobs#notice.js'),
            [],
            NGG_SCRIPT_VERSION
        );

        wp_enqueue_style(
            'ngg_cdn_jobs_in_progress_notice_js',
            $router->get_static_url('imagely-cdn-jobs#notice.css'),
            [],
            NGG_SCRIPT_VERSION
        );

        wp_localize_script(
            'ngg_cdn_jobs_in_progress_notice_js',
            'ngg_cdn_jobs_in_progress_notice',
            [
                'action' => $settings->ajax_url . '&action=cdnjobs_in_progress_check_status',
                'i18n'   => $this->get_i18n()
            ]
        );
    }

    /**
     * @return string
     */
    function render()
    {
        $view = new C_MVC_View('imagely-cdn-jobs#notice', [
            'count' => self::$jobs_count,
            'i18n'  => $this->get_i18n()
        ]);

        return $view->render(TRUE);
    }

    /**
     * @return C_CDN_Jobs_In_Progress_Notice
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
}