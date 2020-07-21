<?php

/**
 * @property C_Form $object
 */
class A_Marketing_Display_Type_Settings_Form extends Mixin
{

    function _get_field_names()
    {
        $ret = $this->call_parent('_get_field_names');
        $ret[] = 'marketing_ecommerce_block';
        return $ret;
    }

    function get_i18n()
    {
        return [
            'label' => __('Enable proofing and ecommerce?', 'nggallery'),
            'alert' => __('Upgrade today! The modal feature is not yet implemented and is part of NGG-939', 'nggallery')
        ];
    }

    function enqueue_static_resources()
    {
        wp_enqueue_script(
            'ngg_display_type_settings_marketing',
            C_Router::get_instance()->get_static_url('photocrati-marketing#display_type_settings.js'),
            [],
            NGG_SCRIPT_VERSION,
            TRUE
        );
        wp_localize_script(
            'ngg_display_type_settings_marketing',
           'ngg_display_type_settings_marketing_i18n',
           $this->get_i18n()
        );
    }

    function _render_marketing_ecommerce_block_field($display_type)
    {
        return $this->object->render_partial('photocrati-marketing#display_type_settings', [
            'display_type' => $display_type,
            'i18n' => $this->get_i18n()
        ], TRUE);
    }
}