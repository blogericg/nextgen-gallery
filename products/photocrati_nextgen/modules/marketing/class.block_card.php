<?php

class C_Marketing_Card extends C_Marketing_Block_Base
{
    public $size        = '';
    public $title       = '';
    public $thumb_url   = '';
    public $description = '';

    /**
     * @param string $size Currently only 'large' is accepted
     * @param string $title Card title
     * @param string $thumb Full URL to the thumbnail
     * @param string $desc Card description
     * @return C_Marketing_Card
     */
    public function __construct($size, $title, $thumb, $desc, $medium, $campaign, $src = 'nggallery')
    {
        if ($size !== 'large')
            $size = 'large';

        $this->size         = 'card-' . $size;
        $this->title       = $title;
        $this->thumb_url   = $thumb;
        $this->description = $desc;

        parent::__construct($this->size, $medium, $campaign, $src);

        return $this;
    }

}