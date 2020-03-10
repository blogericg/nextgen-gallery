<?php

/**
 * Class A_Lightbox_Manager_Form
 * @mixin C_Form
 * @adapts I_Form using "lightbox_effects" context
 */
class A_Lightbox_Manager_Form extends Mixin
{
    function get_model()
    {
        return C_Settings_Model::get_instance();
    }

    function get_title()
    {
        return __('Lightbox Effects', 'nggallery');
    }

    function render()
    {
        $form_manager = C_Form_Manager::get_instance();

        // retrieve and render the settings forms for each library
        $sub_fields = array();

        $form_manager->add_form(NGG_LIGHTBOX_OPTIONS_SLUG, 'custom_lightbox');
        foreach ($form_manager->get_forms(NGG_LIGHTBOX_OPTIONS_SLUG, TRUE) as $form) {
            $form->enqueue_static_resources();
            $sub_fields[$form->context] = $form->render(FALSE);
        }

        // Highslide and jQuery.Lightbox were removed in 2.0.73 due to licensing. If a user has selected
        // either of those options we silently make their selection fallback to Fancybox
        $selected = $this->object->get_model()->thumbEffect;
        if (in_array($selected, array('highslide', 'lightbox')))
            $selected = 'fancybox';

        // Render container tab
        return $this->render_partial(
            'photocrati-nextgen_other_options#lightbox_library_tab',
            array(
                'lightbox_library_label' => __('What lightbox would you like to use?', 'nggallery'),
                'libs'       => C_Lightbox_Library_Manager::get_instance()->get_all(),
                'selected'   => $selected,
                'sub_fields' => $sub_fields,
                'lightbox_global'   => $this->object->get_model()->thumbEffectContext,
            ),
            TRUE
        );
    }

    function save_action()
    {
        $settings = $this->object->get_model();

        // Ensure that a lightbox library was selected
        if (($id = $this->object->param('lightbox_library_id')))
        {
            $lightboxes = C_Lightbox_Library_Manager::get_instance();
            if (!$lightboxes->get($id)) {
                $settings->add_error('Invalid lightbox effect selected');
            }
            else $settings->thumbEffect = $id;
        }

        // Get thumb effect context
        if (($thumbEffectContext = $this->object->param('thumbEffectContext'))) {
            $settings->thumbEffectContext = $thumbEffectContext;
        }
        $settings->save();

        // Save other forms
        $form_manager = C_Form_Manager::get_instance();
        $form_manager->add_form(NGG_LIGHTBOX_OPTIONS_SLUG, 'custom_lightbox');
        foreach ($form_manager->get_forms(NGG_LIGHTBOX_OPTIONS_SLUG, TRUE) as $form) {
            if ($form->has_method('save_action')) $form->save_action();
        }
    }
}
