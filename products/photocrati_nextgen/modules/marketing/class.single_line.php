<?php

class C_Marketing_Single_Line
{
    public $title       = '';
    public $source      = '';
    public $medium      = '';
    public $campaign    = '';

    /**
     * @var string $title
     * @var string $medium
     * @var string $campaign
     * @var string $src (optional) Defaults to 'nggallery'
     * @return string
     */
    public function __construct($title, $medium, $campaign, $src = 'nggallery')
    {
        $this->title       = $title;
        $this->source      = $src;
        $this->medium      = $medium;
        $this->campaign    = $campaign;
    }

    public function render($return = TRUE)
    {
        $view = new C_MVC_View(
            "photocrati-marketing#single-line",
            [
                'line'      => $this,
                'link_text' => __('Upgrade Now', 'nggallery')
            ]
        );

        return $view->render($return);
    }

    public function get_upgrade_link()
    {
        $url = 'https://www.imagely.com/nextgen-gallery/?utm_source=' . $this->source;
        $url .= '&utm_medium=' . $this->medium;
        $url .= '&utm_campaign=' . $this->campaign;

        return $url;
    }
}