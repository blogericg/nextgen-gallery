<?php

class C_Marketing_Block_Single_Line extends C_Marketing_Block_Base
{
    public $title       = '';
    public $source      = '';
    public $medium      = '';
    public $campaign    = '';

    /**
     * @return C_Marketing_Block_Single_Line
     *@var string $medium
     * @var string $campaign
     * @var string $src (optional) Defaults to 'nggallery'
     * @var string $title
     */
    public function __construct($title, $medium, $campaign, $src = 'ngg')
    {
        parent::__construct('single-line', $medium, $campaign, $src);

        $this->title       = $title;
        $this->source      = $src;
        $this->medium      = $medium;
        $this->campaign    = $campaign;

        return $this;
    }
}