<?php

class C_Marketing_Block_Card extends C_Marketing_Block_Base
{
    public $title       = '';
    public $thumb_url   = '';
    public $description = '';

    /**
     * @param string $title Card title
     * @param string $thumb Full URL to the thumbnail
     * @param string $desc Card description
     * @return C_Marketing_Block_Card
     */
    public function __construct($title, $thumb, $desc, $medium, $campaign, $src = 'nggallery')
    {
        parent::__construct('card', $medium, $campaign, $src);

        $this->title       = $title;
        $this->thumb_url   = $thumb;
        $this->description = $desc;

        return $this;
    }

}