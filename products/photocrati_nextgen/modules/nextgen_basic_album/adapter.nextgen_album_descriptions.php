<?php

/**
 * Class A_NextGen_Album_Descriptions
 * @mixin C_MVC_View
 * @adapts I_MVC_View
 */
class A_NextGen_Album_Descriptions extends Mixin
{
    // When viewing a child gallery the album controller's add_description_to_legacy_templates() method will be
    // called for the gallery and then again for the root album; we only want to run once
    public static $_description_added_once = FALSE;

    function are_descriptions_enabled($display_settings)
    {
        $retval = FALSE;

        if (isset($display_settings['enable_descriptions']) && $display_settings['enable_descriptions'])
            $retval = TRUE;
        elseif (isset($display_settings['original_settings']) && $this->are_descriptions_enabled($display_settings['original_settings']))
            $retval = TRUE;

        return $retval;
    }

    function render_object()
    {
        $root_element = $this->call_parent('render_object');

        if ($displayed_gallery = $this->object->get_param('displayed_gallery'))
        {
            $ds = $displayed_gallery->display_settings;
            if ($this->are_descriptions_enabled($ds))
            {
                $description = $this->object->generate_description($displayed_gallery);

                foreach ($root_element->find('nextgen_gallery.gallery_container', TRUE) as $container) {
                    // Determine where (to be compatible with breadcrumbs) in the container to insert
                    $pos = 0;
                    foreach ($container->_list as $ndx => $item) {
                        if (is_string($item))
                            $pos = $ndx;
                        else
                            break;
                    }

                    $container->insert($description, $pos);
                }
            }
        }

        return $root_element;
    }

    function render_legacy_template_description($displayed_gallery)
    {
        if (!empty($displayed_gallery->display_settings['template'])
        &&  $this->are_descriptions_enabled($displayed_gallery->display_settings))
            return $this->object->generate_description($displayed_gallery);
        else
            return '';
    }

    function generate_description($displayed_gallery)
    {
        if (self::$_description_added_once)
            return '';

        self::$_description_added_once = TRUE;
        $description = $this->get_description($displayed_gallery);
        $view = new C_MVC_View('photocrati-nextgen_basic_album#descriptions', array(
            'description' => $description
        ));

        return $view->render(TRUE);
    }

    function get_description($displayed_gallery)
    {
        $description = '';
        
        // Important: do not array_shift() $displayed_gallery->container_ids as it will affect breadcrumbs
        $container_ids = $displayed_gallery->container_ids;
        
        if ($displayed_gallery->source == 'galleries')
        {
            $gallery_id = array_shift($container_ids);
            $gallery = C_Gallery_Mapper::get_instance()->find($gallery_id);
            if ($gallery && !empty($gallery->galdesc))
                $description = $gallery->galdesc;
        }
        else if ($displayed_gallery->source == 'albums') {
            $album_id = array_shift($container_ids);
            $album = C_Album_Mapper::get_instance()->find($album_id);
            if ($album && !empty($album->albumdesc))
                $description = $album->albumdesc;
        }

        return $description;
    }
}