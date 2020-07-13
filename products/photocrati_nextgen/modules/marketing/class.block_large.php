<?php

class C_Marketing_Block_Large
{
    public $title         = '';
    public $description   = '';
    public $links         = [];
    public $footer        = '';
    public $source        = '';
    public $medium        = '';
    public $campaign      = '';
    public $thumbnail_url = '';
    public $demo_url      = '';
    public $demo_text     = '';

    /**
     * @param string $title
     * @param string $description
     * @param string $footer
     * @param string $thumbnail_url
     * @param string $demo_url
     * @param string $medium
     * @param string $campaign
     * @param string $src
     * @return C_Marketing_Block_Large
     */
    public function __construct($title, $description, $footer, $thumbnail_url, $demo_url, $demo_text, $campaign, $src, $medium = 'ngg')
    {
        $this->title         = $title;
        $this->description   = $description;
        $this->footer        = $footer;
        $this->thumbnail_url = $thumbnail_url;
        $this->demo_url      = $demo_url;
        $this->demo_text     = $demo_text;
        $this->medium        = $medium;
        $this->campaign      = $campaign;
        $this->source        = $src;

        return $this;
    }

    public function render($return = TRUE)
    {
        $view = new C_MVC_View(
            "photocrati-marketing#block-large",
            [
                'block'     => $this,
                'link_text' => __('Upgrade to NextGEN Pro', 'nggallery')
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
