<?php

/**
 * Class A_Miscellaneous_Form
 * @mixin C_Form
 * @adapts I_Form using "miscellaneous" context
 */
class A_Miscellaneous_Form extends Mixin
{
	function get_model()
	{
		return C_Settings_Model::get_instance('global');
	}

	function get_title()
	{
		return __('Miscellaneous', 'nggallery');
	}

	function render()
	{
		return $this->object->render_partial(
            'photocrati-nextgen_other_options#misc_tab',
            array(
                'mediarss_activated'       => C_NextGen_Settings::get_instance()->useMediaRSS,
                'mediarss_activated_label' => __('Add MediaRSS link?', 'nggallery'),
                'mediarss_activated_help'  => __('When enabled, adds a MediaRSS link to your header. Third-party web services can use this to publish your galleries', 'nggallery'),
                'mediarss_activated_no'    => __('No'),
                'mediarss_activated_yes'   => __('Yes'),
                'galleries_in_feeds'       => C_NextGen_Settings::get_instance()->galleries_in_feeds,
                'galleries_in_feeds_label' => __('Display galleries in feeds', 'nggallery'),
                'galleries_in_feeds_help'  => __('NextGEN hides its gallery displays in feeds other than MediaRSS. This enables image galleries in feeds.', 'nggallery'),
                'galleries_in_feeds_no'    => __('No'),
                'galleries_in_feeds_yes'   => __('Yes'),
                'cache_label'        => __('Clear image cache', 'nggallery'),
                'cache_confirmation' => __("Completely clear the NextGEN cache of all image modifications?\n\nChoose [Cancel] to Stop, [OK] to proceed.", 'nggallery'),

                 'slug_field' => $this->_render_text_field(
                     (object)array('name' => 'misc_settings'),
                     'router_param_slug',
                     __('Permalink slug', 'nggallery'),
                     $this->object->get_model()->router_param_slug
                 ),

                'maximum_entity_count_field' => $this->_render_number_field(
                    (object)array('name' => 'misc_settings'),
                    'maximum_entity_count',
                    __('Maximum image count', 'nggallery'),
                    $this->object->get_model()->maximum_entity_count,
                    __('This is the maximum limit of images that NextGEN will restrict itself to querying', 'nggallery')
                        . " \n "
                        . __('Note: This limit will not apply to slideshow widgets or random galleries if/when those galleries specify their own image limits', 'nggallery'),
                    FALSE,
                    '',
                    1
                ),

                'random_widget_cache_ttl_field' => $this->_render_number_field(
                    (object)array('name' => 'misc_settings'),
                    'random_widget_cache_ttl',
                    __('Random widget cache duration', 'nggallery'),
                    $this->object->get_model()->random_widget_cache_ttl,
                    __('The duration of time (in minutes) that "random" widget galleries should be cached. A setting of zero will disable caching.', 'nggallery'),
                    FALSE,
                    '',
                    0
                ),

                'alternate_random_method_field' => $this->_render_radio_field(
                    (object)array('name' => 'misc_settings'),
                    'use_alternate_random_method',
                    __('Use alternative method of retrieving random image galleries', 'nggallery'),
                    C_NextGen_Settings::get_instance()->use_alternate_random_method,
                    __("Some web hosts' database servers disable or disrupt queries using 'ORDER BY RAND()' which can cause galleries to lose their randomness. NextGen provides an alternative (but not completely random) method to determine what images are fed into 'random' galleries.", 'nggallery')
                ),

                'alternate_random_method_field' => $this->_render_radio_field(
                    (object)array('name' => 'misc_settings'),
                    'use_alternate_random_method',
                    __('Use alternative method of retrieving random image galleries', 'nggallery'),
                    C_NextGen_Settings::get_instance()->use_alternate_random_method,
                    __("Some web hosts' database servers disable or disrupt queries using 'ORDER BY RAND()' which can cause galleries to lose their randomness. NextGen provides an alternative (but not completely random) method to determine what images are fed into 'random' galleries.", 'nggallery')
                ),

                'disable_fontawesome_field' => $this->_render_radio_field(
                    (object)array('name' => 'misc_settings'),
                    'disable_fontawesome',
                    __('Do not enqueue FontAwesome', 'nggallery'),
                    C_NextGen_Settings::get_instance()->disable_fontawesome,
                    __("Warning: your theme or another plugin must provide FontAwesome or your gallery displays may appear incorrectly", 'nggallery')
                )
            ),
            TRUE
        );
	}

    function cache_action()
    {
        C_Cache::get_instance()->flush_galleries();
		C_Photocrati_Transient_Manager::flush();
    }

	function save_action()
	{
		if (($settings = $this->object->param('misc_settings')))
        {
			// The Media RSS setting is actually a local setting, not a global one
			$local_settings = C_NextGen_Settings::get_instance();
			$local_settings->set('useMediaRSS', intval($settings['useMediaRSS']));
            unset($settings['useMediaRSS']);
            
            $settings['galleries_in_feeds'] = intval($settings['galleries_in_feeds']);

            // It's important the router_param_slug never be empty
            if (empty($settings['router_param_slug']))
                $settings['router_param_slug'] = 'nggallery';

			// If the router slug has changed, then flush the cache
			if ($settings['router_param_slug'] != $this->object->get_model()->router_param_slug) {
				C_Photocrati_Transient_Manager::flush('displayed_gallery_rendering');
			}

            // Do not allow this field to ever be unset
            $settings['maximum_entity_count'] = intval($settings['maximum_entity_count']);
            if ($settings['maximum_entity_count'] <= 0) $settings['maximum_entity_count'] = 500;

			// Save both setting groups
			$this->object->get_model()->set($settings)->save();
			$local_settings->save();
		}
	}
}
