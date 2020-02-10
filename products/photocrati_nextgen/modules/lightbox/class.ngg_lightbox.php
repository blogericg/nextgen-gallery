<?php

/**
 * Represents a lightbox available in NextGEN Gallery
 * Class C_NGG_Lightbox
 * @mixin Mixin_NGG_Lightbox_Instance_Methods
 * @implements I_Lightbox
 */
class C_NGG_Lightbox extends C_Component
{
    function define($context=FALSE, $properties=array())
    {
        parent::define($context);
        $this->add_mixin('Mixin_NGG_Lightbox_Instance_Methods');
        $this->implement('I_Lightbox');
    }

    function initialize($name='', $properties=array())
    {
        parent::initialize();
        $properties['name'] = $name;
        foreach ($properties as $k=>$v) $this->$k = $v;
    }
}

class Mixin_NGG_Lightbox_Instance_Methods extends Mixin
{
    /**
     * Returns true/false whether or not the lightbox supports displaying entities from the displayed gallery object
     * @param $displayed_gallery. By default, lightboxes don't support albums
     * @return bool
     */
    function is_supported($displayed_gallery)
    {
        $retval = TRUE;

        if (in_array($displayed_gallery->source, array('album', 'albums')) && !isset($displayed_gallery->display_settings['open_gallery_in_lightbox'])) {
            $retval = FALSE;
        }

        return $retval;
    }
}