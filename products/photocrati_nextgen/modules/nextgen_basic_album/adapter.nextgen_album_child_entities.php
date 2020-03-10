<?php
/**
 * Because enqueueing an albums child entities (for use in lightboxes) is slow to do inside of cache_action() and
 * we can't guarantee index_action() will run on every hit (thanks to page caching) we inline those entities into
 * our basic albums templates under a window.load listener.
 *
 * @mixin C_MVC_View
 * @adapts I_MVC_View
 */
class A_NextGen_Album_Child_Entities extends Mixin
{
    protected static $_runonce  = FALSE;
    public    static $_entities = array();

    /**
     * The album controller will invoke this filter when its _render_album() method is called
     */
    function __construct()
    {
        if (!self::$_runonce)
            add_filter('ngg_album_prepared_child_entity', array($this, 'register_child_gallery'), 10, 2);
        else
            self::$_runonce = TRUE;
    }

    /**
     * Register each gallery belonging to the album that has just been rendered, so that when the MVC controller
     * system 'catches up' and runs $this->render_object() that method knows what galleries to inline as JS.
     *
     * @param array $galleries
     * @param $displayed_gallery
     * @return array mixed
     */
    function register_child_gallery($galleries, $displayed_gallery)
    {
        if (!$this->is_basic_album($displayed_gallery))
            return $galleries;
        $id = $displayed_gallery->ID();
        foreach ($galleries as $gallery) {
            if ($gallery->is_album)
                continue;
            self::$_entities[$id][] = $gallery;
        }
        return $galleries;
    }

    function is_basic_album($displayed_gallery)
    {
        return in_array($displayed_gallery->display_type, array(NGG_BASIC_COMPACT_ALBUM, NGG_BASIC_EXTENDED_ALBUM));
    }

    /**
     * Determine if we need to append the JS to the current template. This method static for the basic album controller to access.
     *
     * @param $display_settings
     * @return bool
     */
    static function are_child_entities_enabled($display_settings)
    {
        $retval = FALSE;
        if (empty($display_settings['open_gallery_in_lightbox']))
            $display_settings['open_gallery_in_lightbox'] = 0;
        if ($display_settings['open_gallery_in_lightbox'] == 1)
            $retval = TRUE;
        return $retval;
    }

    /**
     * Search inside the template for the inside of the container and append our inline JS
     */
    function render_object()
    {
        $root_element = $this->call_parent('render_object');
        if ($displayed_gallery = $this->object->get_param('displayed_gallery'))
        {
            if (!$this->is_basic_album($displayed_gallery))
                return $root_element;
            $ds = $displayed_gallery->display_settings;
            if (self::are_child_entities_enabled($ds))
            {
                $id = $displayed_gallery->ID();
                foreach ($root_element->find('nextgen_gallery.gallery_container', TRUE) as $container) {
                    $container->append(self::generate_script(self::$_entities[$id]));
                }
            }
        }

        return $root_element;
    }

    /**
     * Generate the JS that will be inserted into the template. This method static for the basic album controller to access.
     *
     * @param array $galleries
     * @return string
     */
    static function generate_script($galleries)
    {
        $retval = '<script type="text/javascript">window.addEventListener("load", function() {';
        foreach ($galleries as $gallery) {
            $dg = $gallery->displayed_gallery;
            $id = $dg->id();
            $retval .= 'galleries.gallery_' . $id . ' = ' . json_encode($dg->get_entity()) . ';';
            $retval .= 'galleries.gallery_' . $id . '.wordpress_page_root = "' . get_permalink() . '";';
        }
        $retval .= '}, false);</script>';

        return $retval;
    }
}
