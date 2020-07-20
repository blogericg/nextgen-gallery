<?php

class C_Marketing_Block_Popup extends C_Marketing_Block_Base
{
    public $title         = '';
    public $description   = '';
    public $links         = [];
    public $footer        = '';
    public $thumbnail_url = '';

    /**
     * @param string $title
     * @param string $description
     * @param string $footer
     * @param string $thumbnail_url Either a full HTTPS path or a FontAwesome icon (must begin with fa-)
     * @param string $demo_url
     * @param string $medium
     * @param string $campaign
     * @param string $src
     * @return C_Marketing_Block_Popup
     */
    public function __construct($title, $description, $footer, $thumbnail_url, $medium, $campaign, $src = 'ngg')
    {
        parent::__construct('popup', $medium, $campaign, $src);

        $this->title         = $title;
        $this->description   = $description;
        $this->footer        = $footer;
        $this->thumbnail_url = $thumbnail_url;

        $this->link_text = __('Upgrade to NextGEN Pro', 'nggallery');

        return $this;
    }

}
