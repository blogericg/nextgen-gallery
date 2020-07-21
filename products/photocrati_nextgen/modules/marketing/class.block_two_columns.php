<?php

class C_Marketing_Block_Two_Columns extends C_Marketing_Block_Base
{
    public $title       = '';
    public $description = '';
    public $links       = [];
    public $footer      = '';

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
    public function __construct($title, $description, $links, $footer, $medium, $campaign, $src = 'ngg')
    {
        parent::__construct('two-columns', $medium, $campaign, $src);

        $this->title       = $title;
        $this->description = $description;
        $this->links       = $links;
        $this->footer      = $footer;

        return $this;
    }

}