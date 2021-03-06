<?php

/**
 * Class A_NextGen_Basic_Extended_Album_Form
 * @mixin C_Form
 * @adapts I_Form for the "photocrati-nextgen_basic_extended_album" context
 */
class A_NextGen_Basic_Extended_Album_Form extends Mixin_NextGen_Basic_Album_Form
{
	function get_display_type_name()
	{
		return NGG_BASIC_EXTENDED_ALBUM;
	}

    /**
     * Returns a list of fields to render on the settings page
     */
    function _get_field_names()
    {
        $fields = parent::_get_field_names();
        $fields[] = 'thumbnail_override_settings';
        return $fields;
    }

    /**
     * Enqueues static resources required by this form
     */
    function enqueue_static_resources()
    {
        $this->object->enqueue_script(
            'nextgen_basic_extended_albums_settings_script',
            $this->object->get_static_url('photocrati-nextgen_basic_album#extended_settings.js'),
            array('jquery.nextgen_radio_toggle')
        );
    }
}