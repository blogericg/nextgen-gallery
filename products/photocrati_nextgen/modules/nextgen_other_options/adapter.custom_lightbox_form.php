<?php

/**
 * Class A_Custom_Lightbox_Form
 * @mixin C_Form
 * @adapts I_Form using "custom_lightbox" context
 */
class A_Custom_Lightbox_Form extends Mixin
{
    function get_model()
    {
        return C_Lightbox_Library_Manager::get_instance()->get('custom_lightbox');
    }

    /**
     * Returns a list of fields to render on the settings page
     */
    function _get_field_names()
    {
        return array(
            'lightbox_library_code',
            'lightbox_library_styles',
            'lightbox_library_scripts'
        );
    }

    /**
     * @param $lightbox
     * @return mixed
     */
    function _render_lightbox_library_code_field($lightbox)
    {
        return $this->_render_text_field(
            $lightbox,
            'code',
            __('Code', 'nggallery'),
            $lightbox->code
        );
    }

    /**
     * @param $lightbox
     * @return mixed
     */
    function _render_lightbox_library_styles_field($lightbox)
    {
        return $this->_render_textarea_field(
            $lightbox,
            'styles',
            __('Stylesheet URL', 'nggallery'),
            implode("\n", $lightbox->styles)
        );
    }

    /**
     * @param $lightbox
     * @return mixed
     */
    function _render_lightbox_library_scripts_field($lightbox)
    {
        return $this->_render_textarea_field(
            $lightbox,
            'scripts',
            __('Javascript URL', 'nggallery'),
            implode("\n", $lightbox->scripts)
        );
    }

    function _convert_to_urls($input)
    {
        $retval = array();

        $urls = explode("\n", $input);
        foreach ($urls as $url) {
            if (strpos($url, home_url()) === 0) {
                $url = str_replace(home_url(), '', $url);
            }
            elseif (strpos($url, 'http') === 0) {
                $url = str_replace('https://', '//', $url);
                $url = str_replace('http://', '//', $url);
            }
            $retval[] = $url;
        }

        return $retval;
    }

    function save_action()
    {
        $settings = C_NextGen_Settings::get_instance();
        $modified = FALSE;

        if ($params = $this->param('custom_lightbox')) {
            if (array_key_exists('scripts', $params)) {
                $settings->thumbEffectScripts   = $this->_convert_to_urls($params['scripts']);
                $modified = TRUE;
            }

            if (array_key_exists('styles', $params)) {
                $settings->thumbEffectStyles    = $this->_convert_to_urls($params['styles']);
                $modified = TRUE;
            }

            if (array_key_exists('code', $params)) {
                $settings->thumbEffectCode = $params['code'];
                $modified = TRUE;
            }
        }

        if ($modified) $settings->save();
    }
}