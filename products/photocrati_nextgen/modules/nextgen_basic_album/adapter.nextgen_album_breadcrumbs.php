<?php

/**
 * Class A_NextGen_Album_Breadcrumbs
 * @mixin C_MVC_View
 * @adapts I_MVC_View
 */
class A_NextGen_Album_Breadcrumbs extends Mixin
{
    public $breadcrumb_cache = array();


	function are_breadcrumbs_enabled($display_settings)
	{
		$retval = FALSE;

		if (isset($display_settings['enable_breadcrumbs']) && $display_settings['enable_breadcrumbs']) $retval = TRUE;
		elseif (isset($display_settings['original_settings']) && $this->are_breadcrumbs_enabled($display_settings['original_settings'])) $retval = TRUE;

		return $retval;
	}

	function get_original_album_entities($display_settings)
	{
		$retval = array();

		if (isset($display_settings['original_album_entities'])) $retval = $display_settings['original_album_entities'];
		elseif (isset($display_settings['original_settings']) && $this->get_original_album_entities($display_settings['original_settings'])) $retval = $this->get_original_album_entities($display_settings['original_settings']);

		return $retval;
	}

    function render_object()
    {
        $root_element = $this->call_parent('render_object');

        if ($displayed_gallery = $this->object->get_param('displayed_gallery'))
        {
            $ds = $displayed_gallery->display_settings;
	        if ($this->are_breadcrumbs_enabled($ds) && ($original_entities = $this->get_original_album_entities($ds)))
	        {
		        $original_entities = $this->get_original_album_entities($ds);
                if (!empty($ds['original_album_id']))
                    $ids = $ds['original_album_id'];
                else
                    $ids = $displayed_gallery->container_ids;

                $breadcrumbs = $this->object->generate_breadcrumb($ids, $original_entities);
                foreach ($root_element->find('nextgen_gallery.gallery_container', TRUE) as $container) {
                    $container->insert($breadcrumbs);
                }
            }
        }

        return $root_element;
    }

    function render_legacy_template_breadcrumbs($displayed_gallery, $entities, $gallery_id = FALSE)
    {
        $ds = $displayed_gallery->display_settings;

	    if (!empty($entities) && !empty($ds['template']) && $this->are_breadcrumbs_enabled($ds))
        {
            if ($gallery_id)
            {
            	if (is_array($gallery_id))
            		$ids = $gallery_id;
            	else
                $ids = array($gallery_id);
            }
            elseif (!empty($ds['original_album_id']))
                $ids = $ds['original_album_id'];
            else
                $ids = $displayed_gallery->container_ids;

            // Prevent galleries with the same ID as the parent album being displayed as the root
            // breadcrumb when viewing the album page
            if (is_array($ids) && count($ids) == 1 && strpos($ids[0], 'a') !== 0)
                $ids = array();

            if (!empty($ds['original_album_entities']))
                $breadcrumb_entities = $ds['original_album_entities'];
            else
                $breadcrumb_entities = $entities;

            return $this->object->generate_breadcrumb(
                $ids,
                $breadcrumb_entities
            );
        }
        else
            return '';
    }

    function find_gallery_parent($gallery_id, $sortorder)
    {
        $map    = C_Album_Mapper::get_instance();
        $found  = array();

        foreach ($sortorder as $order) {
            if (strpos($order, 'a') === 0)
            {
                $album_id = ltrim($order, 'a');
                if (empty($this->breadcrumb_cache[$order]))
                {
                    $album = $map->find($album_id);
                    $this->breadcrumb_cache[$order] = $album;
                    if (in_array($gallery_id, $album->sortorder))
                    {
                        $found[] = $album;
                        break;
                    }
                    else {
                        $found = $this->find_gallery_parent($gallery_id, $album->sortorder);
                        if ($found)
                        {
                            $found[] = $album;
                            break;
                        }
                    }
                }
            }
        }

        return $found;
    }

    function generate_breadcrumb($gallery_id = NULL, $entities)
    {
        $found = array();
        $router = C_Router::get_instance();
        $app = $router->get_routed_app();

        if (is_array($gallery_id))
            $gallery_id = array_shift($gallery_id);
        if (is_array($gallery_id))
            $gallery_id = $gallery_id[0];

        foreach ($entities as $ndx => $entity) {
            $tmpid = (isset($entity->albumdesc) ? 'a' : '') . $entity->{$entity->id_field};
            $this->breadcrumb_cache[$tmpid] = $entity;
            if (isset($entity->albumdesc) && in_array($gallery_id, $entity->sortorder))
            {
                $found[] = $entity;
                break;
            }
        }

        if (empty($found))
        {
            foreach ($entities as $entity) {
                if (!empty($entity->sortorder))
                    $found = $this->object->find_gallery_parent($gallery_id, $entity->sortorder);
                if (!empty($found))
                {
                    $found[] = $entity;
                    break;
                }
            }
        }

        $found = array_reverse($found);

        if (strpos($gallery_id, 'a') === 0)
        {
            $album_found = FALSE;
            foreach ($found as $found_item) {
                if ($found_item->{$found_item->id_field} == $gallery_id)
                    $album_found = TRUE;
            }
            if (!$album_found)
            {
                $album_id = ltrim($gallery_id, 'a');
                $album = C_Album_Mapper::get_instance()->find($album_id);
                $found[] = $album;
                $this->breadcrumb_cache[$gallery_id] = $album;
            }
        } else {
            $gallery_found = FALSE;
            foreach ($entities as $entity) {
                if (isset($entity->is_gallery) && $entity->is_gallery && $gallery_id == $entity->{$entity->id_field})
                {
                    $gallery_found = TRUE;
                    $found[] = $entity;
                    break;
                }
            }
            if (!$gallery_found)
            {
                $gallery = C_Gallery_Mapper::get_instance()->find($gallery_id);
                if ($gallery != null) {
		              $found[] = $gallery;
		              $this->breadcrumb_cache[$gallery->{$gallery->id_field}] = $gallery;
                }
            }
        }

        $crumbs = array();
        if (!empty($found))
        {
            $end = end($found);
            reset($found);
            foreach ($found as $found_item) {
                $type   = isset($found_item->albumdesc) ? 'album' : 'gallery';
                $id     = ($type == 'album' ? 'a' : '') . $found_item->{$found_item->id_field};
                $entity = $this->breadcrumb_cache[$id];
                $link   = NULL;

                if ($type == 'album')
                {
                    $name = $entity->name;
                    if ($entity->pageid > 0)
                        $link = @get_page_link($entity->pageid);
                    if (empty($link) && $found_item !== $end)
                    {
                        $link = $app->get_routed_url();
                        $link = $app->strip_param_segments($link);
                        $link = $app->set_parameter_value('album', $entity->slug, NULL, FALSE, $link);
                    }
                }
                else {
                    $name = $entity->title;
                }

                $crumbs[] = array(
                    'type' => $type,
                    'name' => $name,
                    'url'  => $link
                );
            }
        }

        // free this memory immediately
        $this->breadcrumb_cache = array();

        $view = new C_MVC_View('photocrati-nextgen_basic_album#breadcrumbs', array(
            'breadcrumbs' => $crumbs,
            'divisor'     => apply_filters('ngg_breadcrumb_separator', ' &raquo; ')
        ));

        return $view->render(TRUE);
    }
}
