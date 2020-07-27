<?php

class C_Marketing_Block_Card extends C_Marketing_Block_Base
{
    public $title       = '';
    public $thumb_url   = '';
    public $description = '';

    /**
     * @param string $title Card title
     * @param string $desc Card description
     * @return C_Marketing_Block_Card
     */
    public function __construct($title, $desc, $medium, $campaign, $src = 'ngg')
    {
        parent::__construct('card', $medium, $campaign, $src);

        $this->title       = $title;
        $this->description = $desc;

        return $this;
    }

}