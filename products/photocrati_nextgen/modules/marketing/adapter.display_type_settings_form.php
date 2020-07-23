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

    function get_upsell_popups()
    {
        $i18n = $this->get_i18n();

        $ecommerce = new C_Marketing_Block_Popup(
            $i18n->ecommerce_and_print_lab,
            M_Marketing::get_i18n_fragment('feature_not_available', __('Ecommerce and Print Lab functionality', 'nggallery')),
            M_Marketing::get_i18n_fragment('lite_coupon'),
            'fa-shopping-cart',
            'ngg',
            'display_settings',
            'enable_ecommerce'
        );

        $proofing = new C_Marketing_Block_Popup(
            $i18n->proofing,
            M_Marketing::get_i18n_fragment('feature_not_available', __('proofing', 'nggallery')),
            M_Marketing::get_i18n_fragment('lite_coupon'),
            'fa-star',
            'ngg',
            'display_settings',
            'enable_proofing'
        );

        return [
            'ecommerce' => '<div>'.$ecommerce->render().'</div>',
            'proofing'  => '<div>'.$proofing->render().'</div>'
        ];
    }

    function get_i18n()
    {
        $i18n = new stdClass;
        $i18n->requires_pro             = __("Requires NextGEN Pro", "nggallery");
        $i18n->enable_proofing          = __('Enable proofing?', 'nggallery');
        $i18n->enable_ecommerce         = __('Enable ecommerce?', 'nggallery');
        $i18n->yes                      = __('Yes', 'nggallery');
        $i18n->no                       = __('No', 'nggallery');
        $i18n->ecommerce_and_print_lab  = __("Ecommerce and Print Lab Integration");
        $i18n->proofing                 = __("Proofing");

        return $i18n;
    }

    function enqueue_static_resources()
    {
        wp_enqueue_script(
            'ngg_display_type_settings_marketing',
            M_Static_Assets::get_static_url('photocrati-marketing#display_type_settings.min.js'),
            ['jquery-ui-dialog'],
            NGG_SCRIPT_VERSION,
            TRUE
        );
        wp_localize_script(
            'ngg_display_type_settings_marketing',
           'ngg_display_type_settings_marketing',
           ['upsells' => $this->get_upsell_popups(), 'i18n' => (array)$this->get_i18n()]
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