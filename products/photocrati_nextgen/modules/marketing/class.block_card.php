<?php

class C_Marketing_Block_Card extends C_Marketing_Block_Base
{
    public $title       = '';
    public $thumb_url   = '';
    public $description = '';
    public $icon        = '';

    /**
     * @param string $title Card title
     * @param string $desc Card description
     * @param string $icon Icon found under static/icons/
     * @param string $medium
     * @param string $campaign
     * @param string $src
     * @return C_Marketing_Block_Card
     */
    public function __construct($title, $desc, $icon, $medium, $campaign, $src = 'ngg')
    {
        parent::__construct('card', $medium, $campaign, $src);

        $this->title       = $title;
        $this->description = $desc;
        $this->icon        = C_Router::get_instance()->get_static_url('photocrati-marketing#icons/' . $icon);

        return $this;
    }

}