<?php

/**
 * Class A_NextGen_Basic_Gallery_Controller
 * @mixin C_Display_Type_Controller
 * @adapts I_Display_Type_Controller for both "photocrati-nextgen_basic_slideshow" and "photocrati-nextgen_basic_thumbnails" contexts
 * @property C_Display_Type_Controller|A_NextGen_Basic_Gallery_Controller $object
 */
class A_NextGen_Basic_Gallery_Controller extends Mixin
{
    /**
     * @param C_Displayed_Gallery $displayed_gallery
     * @return C_Displayed_Gallery
     */
    function get_alternate_displayed_gallery($displayed_gallery)
    {
        $show = $this->object->param('show');
        $pid  = $this->object->param('pid');

        if (!empty($pid) && isset($displayed_gallery->display_settings['use_imagebrowser_effect']) && intval($displayed_gallery->display_settings['use_imagebrowser_effect']))
            $show = NGG_BASIC_IMAGEBROWSER;

        // Are we to display a different display type?
        if (!empty($show))
        {
            $params = (array)$displayed_gallery->get_entity();
            $ds = $params['display_settings'];

            if ((!empty($ds['show_slideshow_link']) || !empty($ds['show_thumbnail_link']) || !empty($ds['use_imagebrowser_effect']))
            &&   $show != $this->object->context)
            {
                // Render the new display type
                $renderer = C_Displayed_Gallery_Renderer::get_instance();
                $displayed_gallery->original_display_type = $displayed_gallery->display_type;
                $displayed_gallery->original_settings = $displayed_gallery->display_settings;
                $displayed_gallery->display_type = $show;
                $params = (array)$displayed_gallery->get_entity();
                $params['display_settings'] = array();

                $displayed_gallery = $renderer->params_to_displayed_gallery($params);
            }
        }

        return $displayed_gallery;
    }

    function index_action($displayed_gallery, $return = FALSE)
    {
        $alternate_displayed_gallery = $this->object->get_alternate_displayed_gallery($displayed_gallery);

        if ($displayed_gallery !== $alternate_displayed_gallery)
        {
            $renderer = C_Displayed_Gallery_Renderer::get_instance();
            return $renderer->display_images($alternate_displayed_gallery, $return);
        }

        return $this->call_parent('index_action', $displayed_gallery, $return);
    }

    /**
     * Returns a url to view the displayed gallery using an alternate display
     * type
     * @param C_Displayed_Gallery $displayed_gallery
     * @param string $display_type
     * @return string
     */
    function get_url_for_alternate_display_type($displayed_gallery, $display_type, $origin_url = FALSE)
    {
        if (!$origin_url
        &&  !empty($displayed_gallery->display_settings['original_display_type'])
        &&  !empty($_SERVER['NGG_ORIG_REQUEST_URI']))
            $origin_url = $_SERVER['NGG_ORIG_REQUEST_URI'];
        $url = ($origin_url ? $origin_url : $this->object->get_routed_url(TRUE));
        $url = $this->object->remove_param_for($url, 'show', $displayed_gallery->id());
        $url = $this->object->set_param_for($url, 'show', $display_type, $displayed_gallery->id());

        return $url;
    }
}
