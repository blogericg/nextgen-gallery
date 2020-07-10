<?php

class C_Marketing_Card
{
    public $size        = 'large';
    public $title       = '';
    public $thumb_url   = '';
    public $description = '';
    public $source      = '';
    public $medium      = '';
    public $campaign    = '';

    /**
     * @param string $size Currently only 'large' is accepted
     * @param string $title Card title
     * @param string $thumb Full URL to the thumbnail
     * @param string $desc Card description
     * @return string
     */
    public function __construct($size, $title, $thumb, $desc, $medium, $campaign, $src = 'nggallery')
    {
        if (!in_array($size, ['large']))
            $size = 'large';

        $this->size        = $size;
        $this->title       = $title;
        $this->thumb_url   = $thumb;
        $this->description = $desc;
        $this->source      = $src;
        $this->medium      = $medium;
        $this->campaign    = $campaign;
    }

    public function render($return = TRUE)
    {
        $view = new C_MVC_View(
            "photocrati-marketing#card-{$this->size}",
            [
                'card'      => $this,
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