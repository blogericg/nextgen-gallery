<?php

/**
 * Class A_Displayed_Gallery_Trigger_Resources
 * @mixin C_Display_Type_Controller
 * @adapts I_Display_Type_Controller
 */
class A_Displayed_Gallery_Trigger_Resources extends Mixin
{
    protected $run_once = FALSE;

    function enqueue_frontend_resources($displayed_gallery)
    {
        $this->call_parent('enqueue_frontend_resources', $displayed_gallery);
        return $this->enqueue_displayed_gallery_trigger_buttons_resources($displayed_gallery);
    }

    function enqueue_displayed_gallery_trigger_buttons_resources($displayed_gallery = FALSE)
    {
        $retval = FALSE;

        M_Gallery_Display::enqueue_fontawesome();

        if (!$this->run_once
        &&  !empty($displayed_gallery)
        &&  !empty($displayed_gallery->display_settings['ngg_triggers_display'])
        &&  $displayed_gallery->display_settings['ngg_triggers_display'] !== 'never')
        {
            $pro_active = FALSE;
            if (defined('NGG_PRO_PLUGIN_VERSION'))
                $pro_active = 'NGG_PRO_PLUGIN_VERSION';
            if (defined('NEXTGEN_GALLERY_PRO_VERSION'))
                $pro_active = 'NEXTGEN_GALLERY_PRO_VERSION';
            if (!empty($pro_active))
                $pro_active = constant($pro_active);
            if (!is_admin() && (empty($pro_active) || version_compare($pro_active, '1.0.11') >= 0))
            {
                wp_enqueue_style('fontawesome');
                $retval = TRUE;
                $this->run_once = TRUE;
            }
        }

        return $retval;
    }
}

