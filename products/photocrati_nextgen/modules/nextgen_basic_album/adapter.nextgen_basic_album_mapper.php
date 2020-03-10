<?php

/**
 * Class A_NextGen_Basic_Album_Mapper
 * @mixin C_Display_Type_Mapper
 * @adapts I_Display_Type_Mapper
 */
class A_NextGen_Basic_Album_Mapper extends Mixin
{
    function set_defaults($entity)
    {
        $this->call_parent('set_defaults', $entity);

		if (isset($entity->name) && in_array($entity->name, array(
		  NGG_BASIC_COMPACT_ALBUM,
		  NGG_BASIC_EXTENDED_ALBUM))) {

			// Set defaults for both display (album) types
            $settings = C_NextGen_Settings::get_instance();

            $default_template = isset($entity->settings["template"]) ? 'default' : 'default-view.php';
            $this->object->_set_default_value($entity, 'settings', 'display_view', $default_template);

            $this->object->_set_default_value($entity, 'settings', 'galleries_per_page',  $settings->galPagedGalleries);
            $this->object->_set_default_value($entity, 'settings', 'enable_breadcrumbs',  1);
            $this->object->_set_default_value($entity, 'settings', 'disable_pagination',  0);
            $this->object->_set_default_value($entity, 'settings', 'enable_descriptions', 0);
            $this->object->_set_default_value($entity, 'settings', 'template',            '');
            $this->object->_set_default_value($entity, 'settings', 'open_gallery_in_lightbox', 0);
            $this->_set_default_value($entity, 'settings', 'override_thumbnail_settings', 1);
            $this->_set_default_value($entity, 'settings', 'thumbnail_quality', $settings->thumbquality);
            $this->_set_default_value($entity, 'settings', 'thumbnail_crop',    1);
            $this->_set_default_value($entity, 'settings', 'thumbnail_watermark', 0);

            // Thumbnail dimensions -- only used by extended albums
            if ($entity->name == NGG_BASIC_COMPACT_ALBUM)
            {
                $this->_set_default_value($entity, 'settings', 'thumbnail_width',   240);
                $this->_set_default_value($entity, 'settings', 'thumbnail_height',  160);
            }

            // Thumbnail dimensions -- only used by extended albums
            if ($entity->name == NGG_BASIC_EXTENDED_ALBUM)
            {
                $this->_set_default_value($entity, 'settings', 'thumbnail_width',   300);
                $this->_set_default_value($entity, 'settings', 'thumbnail_height',  200);
            }

            if (defined('NGG_BASIC_THUMBNAILS'))
                $this->object->_set_default_value($entity, 'settings', 'gallery_display_type', NGG_BASIC_THUMBNAILS);

            $this->object->_set_default_value($entity, 'settings', 'gallery_display_template', '');
            $this->object->_set_default_value($entity, 'settings', 'ngg_triggers_display', 'never');

        }
    }
}