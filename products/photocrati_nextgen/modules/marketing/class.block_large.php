<?php

class C_Marketing_Block_Large extends C_Marketing_Block_Base
{
    public $title         = '';
    public $description   = '';
    public $links         = [];
    public $footer        = '';
    public $thumbnail_url = '';
    public $demo_url      = '';
    public $demo_text     = '';

    /**
     * @param string $title
     * @param string $description
     * @param string $footer
     * @param string $thumbnail_url Either a full HTTPS path or a FontAwesome icon (must begin with fa-)
     * @param string $demo_url
     * @param string $demo_text
     * @param string $campaign
     * @param string $medium
     * @param string $src
     * @return C_Marketing_Block_Large
     */
    public function __construct($title, $description, $footer, $thumbnail_url, $demo_url, $demo_text, $medium, $campaign, $src = 'ngg')
    {
        parent::__construct('large', $medium, $campaign, $src);

        $this->title         = $title;
        $this->description   = $description;
        $this->footer        = $footer;
        $this->thumbnail_url = $thumbnail_url;
        $this->demo_url      = $demo_url;
        $this->demo_text     = $demo_text;

        $this->link_text = __('Upgrade to NextGEN Pro', 'nggallery');

        return $this;
    }

}
