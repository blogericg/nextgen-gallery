<?php

/**
 * Sets default values for the NextGen Basic Slideshow display type
 * @mixin C_Display_Type_Mapper
 * @adapts I_Display_Type_Mapper
 */
class A_NextGen_Basic_Gallery_Mapper extends Mixin
{
	function set_defaults($entity)
	{
        $this->call_parent('set_defaults', $entity);

		if (isset($entity->name)) {
			if ($entity->name == NGG_BASIC_SLIDESHOW)
				$this->set_slideshow_defaults($entity);

			else if ($entity->name == NGG_BASIC_THUMBNAILS)
				$this->set_thumbnail_defaults($entity);
		}
	}
    
    function set_slideshow_defaults($entity)
    {
        $settings = C_NextGen_Settings::get_instance();
        $this->object->_set_default_value($entity, 'settings', 'gallery_width', $settings->irWidth);
        $this->object->_set_default_value($entity, 'settings', 'gallery_height', $settings->irHeight);
        $this->object->_set_default_value($entity, 'settings', 'show_thumbnail_link', $settings->galShowSlide ? 1 : 0);
        $this->object->_set_default_value($entity, 'settings', 'thumbnail_link_text', $settings->galTextGallery);
        $this->object->_set_default_value($entity, 'settings', 'template', '');
        $this->object->_set_default_value($entity, 'settings', 'display_view', 'default');
        $this->object->_set_default_value($entity, 'settings', 'autoplay', 1);
        $this->object->_set_default_value($entity, 'settings', 'pauseonhover', 1);
        $this->object->_set_default_value($entity, 'settings', 'arrows', 0);
        $this->object->_set_default_value($entity, 'settings', 'interval', 3000);
        $this->object->_set_default_value($entity, 'settings', 'transition_speed', 300);
        $this->object->_set_default_value($entity, 'settings', 'transition_style', 'fade');

        // Part of the pro-modules
        $this->object->_set_default_value($entity, 'settings', 'ngg_triggers_display', 'never');
    } 
    
    function set_thumbnail_defaults($entity)
    {
        $settings = C_NextGen_Settings::get_instance();

        $default_template = isset($entity->settings["template"]) ? 'default' : 'default-view.php';
        $this->object->_set_default_value($entity, 'settings', 'display_view', $default_template);

        $this->object->_set_default_value($entity, 'settings', 'images_per_page', $settings->galImages);
        $this->object->_set_default_value($entity, 'settings', 'number_of_columns', $settings->galColumns);
        $this->object->_set_default_value($entity, 'settings', 'thumbnail_width', $settings->thumbwidth);
        $this->object->_set_default_value($entity, 'settings', 'thumbnail_height', $settings->thumbheight);
        $this->object->_set_default_value($entity, 'settings', 'show_all_in_lightbox', $settings->galHiddenImg);
        $this->object->_set_default_value($entity, 'settings', 'ajax_pagination', $settings->galAjaxNav);
        $this->object->_set_default_value($entity, 'settings', 'use_imagebrowser_effect', $settings->galImgBrowser);
        $this->object->_set_default_value($entity, 'settings', 'template', '');
        $this->object->_set_default_value($entity, 'settings', 'display_no_images_error', 1);

        // TODO: Should this be called enable pagination?
        $this->object->_set_default_value($entity, 'settings', 'disable_pagination', 0);

        // Alternative view support
        $this->object->_set_default_value($entity, 'settings', 'show_slideshow_link', $settings->galShowSlide ? 1 : 0);
        $this->object->_set_default_value($entity, 'settings', 'slideshow_link_text', $settings->galTextSlide);

        // override thumbnail settings
        $this->object->_set_default_value($entity, 'settings', 'override_thumbnail_settings', 0);
        $this->object->_set_default_value($entity, 'settings', 'thumbnail_quality', '100');
        $this->object->_set_default_value($entity, 'settings', 'thumbnail_crop', 1);
        $this->object->_set_default_value($entity, 'settings', 'thumbnail_watermark', 0);

        // Part of the pro-modules
        $this->object->_set_default_value($entity, 'settings', 'ngg_triggers_display', 'never');

    }
}
