<?php

/**
 * Class A_NextGen_Basic_Tagcloud_Form
 * @mixin C_Form
 * @adapts I_Form for "photocrati-nextgen_basic_tagcloud" context
 */
class A_NextGen_Basic_Tagcloud_Form extends Mixin_Display_Type_Form
{
	function get_display_type_name()
	{
		return NGG_BASIC_TAGCLOUD;
	}

    function _get_field_names()
    {
        return array(
            'nextgen_basic_tagcloud_number',
            'nextgen_basic_tagcloud_display_type'
        );
    }

    function enqueue_static_resources()
    {
        $this->object->enqueue_style(
            'nextgen_basic_tagcloud_settings-css',
            $this->get_static_url('photocrati-nextgen_basic_tagcloud#settings.css')
        );
    }

    function _render_nextgen_basic_tagcloud_number_field($display_type)
    {
        return $this->_render_number_field(
            $display_type,
            'number',
            __('Maximum number of tags', 'nggallery'),
            $display_type->settings['number']
        );
    }

    function _render_nextgen_basic_tagcloud_display_type_field($display_type)
    {
        $types = array();
        $skip_types = array(
            NGG_BASIC_TAGCLOUD,
            NGG_BASIC_SINGLEPIC,
            NGG_BASIC_COMPACT_ALBUM,
            NGG_BASIC_EXTENDED_ALBUM
        );

        if (empty($display_type->settings['gallery_display_type'])
        &&  !empty($display_type->settings['gallery_type']))
        {
            $display_type->settings['gallery_display_type'] = $display_type->settings['display_type'];
        }

        $skip_types = apply_filters('ngg_basic_tagcloud_excluded_display_types', $skip_types);

        $mapper = C_Display_Type_Mapper::get_instance();
        $display_types = $mapper->find_all();
        foreach ($display_types as $dt) {
            if (in_array($dt->name, $skip_types))
                continue;
            if (!empty($dt->hidden_from_ui))
                continue;
            $types[$dt->name] = $dt->title;
        }

        return $this->_render_select_field(
            $display_type,
            'gallery_display_type',
            __('Display type', 'nggallery'),
            $types,
            $display_type->settings['gallery_display_type'],
            __('The display type that the tagcloud will point its results to', 'nggallery')
        );
    }
}
