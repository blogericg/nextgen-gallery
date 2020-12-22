<?php

/**
 * Class A_Upload_Images_Form
 * @mixin C_Form
 * @property C_MVC_Controller|A_Upload_Images_Form $object
 * @adapts I_Form for "upload_images" context
 */
class A_Upload_Images_Form extends Mixin
{
    function get_title()
    {
        return __("Upload Images", 'nggallery');
    }

    function enqueue_static_resources()
    {
        wp_enqueue_script('uppy');
        wp_enqueue_script('uppy_i18n');
        wp_enqueue_style('uppy');
        wp_enqueue_script('toastify');
        wp_enqueue_style('toastify');
        wp_localize_script('uppy', 'NggUploadImages_i18n', $this->object->get_i18n_strings());
    }

    function get_uppy_locale()
    {
        $locale = get_locale();
        $mapping = [
            'ar'    => 'ar_SA',
            'bg'    => 'bg_BG',
            'zh-cn' => 'zh_CN',
            'zh-tw' => 'zh_TW',
            'hr'    => 'hr_HR',
            'cs'    => 'cs_CZ',
            'da'    => 'da_DK',
            'nl'    => 'nl_NL',
            'en'    => 'en_US',
            'fi'    => 'fi_FI',
            'fr'    => 'fr_FR',
            'gl'    => 'gl_ES',
            'de'    => 'de_DE',
            'el'    => 'el_GR',
            'he'    => 'he_IL',
            'hu'    => 'hu_HU',
            'is'    => 'is_IS',
            'id'    => 'id_ID',
            'it'    => 'it_IT',
            'ja'    => 'ja_JP',
            'ko'    => 'ko_KR',
            'fa'    => 'fa_IR',
            'pl'    => 'pl_PL',
            'pt-br' => 'pt_BR',
            'pt'    => 'pt_PT',
            'ro'    => 'ro_RO',
            'ru'    => 'ru_RU',
            'sr'    => 'sr_RS',
            'sk'    => 'sk_SK',
            'es'    => 'es_ES',
            'sv'    => 'sv_SE',
            'th'    => 'th_TH',
            'tr'    => 'tr_TR',
            'vi'    => 'vi_VN',
        ];
        if (!empty($mapping[$locale]))
            $locale = $mapping[$locale];
        return $locale;

    }

    function get_i18n_strings() {
        return [
            'locale'             => $this->object->get_uppy_locale(),
            'no_image_uploaded'  => __('No images were uploaded successfully.',      'nggallery'),
            'one_image_uploaded' => __('1 image was uploaded successfully.',         'nggallery'),
            'x_images_uploaded'  => __('{count} images were uploaded successfully.', 'nggallery'),
            'manage_gallery'     => __('Manage gallery > {name}',                    'nggallery'),
            'image_failed'       => __('Image {filename} failed to upload: {error}', 'nggallery')
        ];
    }

    function render()
    {
        return $this->object->render_partial('photocrati-nextgen_addgallery_page#upload_images', [
            'galleries' => $this->object->get_galleries(),
	        'nonce'     => M_Security::create_nonce('nextgen_upload_image'),
            'max_size'  => wp_max_upload_size()
        ], TRUE);
    }

    function get_galleries()
    {
        $galleries = array();

        if (M_Security::is_allowed('nextgen_edit_gallery'))
        {
            $gallery_mapper = C_Gallery_Mapper::get_instance();
            $galleries = $gallery_mapper->find_all();

            if (!M_Security::is_allowed('nextgen_edit_gallery_unowned'))
            {
                $galleries_all = $galleries;
                $galleries = array();

                foreach ($galleries_all as $gallery) {
                    if (wp_get_current_user()->ID == (int)$gallery->author)
                        $galleries[] = $gallery;
                }
            }
        }

        return $galleries;
    }
}