<?php

class C_Marketing_Block_Two_Columns
{
    public $title       = '';
    public $description = '';
    public $links       = [];
    public $footer      = '';
    public $source      = '';
    public $medium      = '';
    public $campaign    = '';

    /**
     * @param string $title
     * @param string|string[] $description
     * @param array $links
     * @param string $footer
     * @param string $medium
     * @param string $campaign
     * @param string $src
     * @return C_Marketing_Block_Two_Columns
     */
    public function __construct($title, $description, $links, $footer, $campaign, $src, $medium = 'ngg')
    {
        $this->title       = $title;
        $this->description = $description;
        $this->links       = $links;
        $this->footer      = $footer;
        $this->medium      = $medium;
        $this->campaign    = $campaign;
        $this->source      = $src;

        return $this;
    }

    public function render($return = TRUE)
    {
        $view = new C_MVC_View(
            "photocrati-marketing#block-two-columns",
            [
                'block'     => $this,
                'link_text' => __('Click here to Upgrade', 'nggallery')
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
