<?php

abstract class C_Marketing_Block_Base
{
    public $source      = '';
    public $medium      = '';
    public $campaign    = '';
    public $template    = '';
    public $link_text   = '';

    /**
     * @param string $template
     * @param string $medium
     * @param string $campaign
     * @param string $src
     * @return C_Marketing_Block_Base
     */
    public function __construct($template, $medium, $campaign, $src = 'nggallery')
    {
        $this->template  = $template;
        $this->source    = $src;
        $this->medium    = $medium;
        $this->campaign  = $campaign;
        $this->link_text = __('Upgrade Now', 'nggallery');

        return $this;
    }

    public function render($return = TRUE)
    {
        $view = new C_MVC_View(
            'photocrati-marketing#' . $this->template,
            [
                'block'     => $this,
                'link_text' => $this->link_text
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
