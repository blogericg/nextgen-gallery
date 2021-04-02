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
        M_Ajax::pass_data_to_js('uppy', 'NggUppyCoreSettings', $this->object->get_uppy_core_settings());
        M_Ajax::pass_data_to_js('uppy', 'NggUppyDashboardSettings', $this->object->get_uppy_dashboard_settings());
        M_Ajax::pass_data_to_js('uppy', 'NggXHRSettings', $this->object->get_uppy_xhr_settings());
    }

    function get_allowed_image_mime_types()
    {
        return ['image/gif', 'image/jpg', 'image/jpeg', 'image/pjpeg', 'image/png'];
    }

    function get_uppy_note()
    {
        $core_settings = $this->object->get_uppy_core_settings();
        $max_size = $core_settings['restrictions']['maxfileSize'];
        $max_size_megabytes = round((int)$max_size / (1024 * 1024));
        return sprintf(__('You may select files up to %dMB', 'nggallery'), $max_size_megabytes);
    }

    function get_uppy_xhr_settings()
    {
        return apply_filters('ngg_uppy_xhr_settings', [
            'timeout'   => intval(NGG_UPLOAD_TIMEOUT) * 1000,
            'limit'     => intval(NGG_UPLOAD_LIMIT),
            'fieldName' => 'file'
        ]);
    }

    function get_uppy_core_settings()
    {
        return apply_filters('ngg_uppy_core_settings', [
            'locale' => $this->object->get_uppy_locale(),
            'restrictions' => [
                'maxfileSize' => wp_max_upload_size(),
                'allowedFileTypes' => $this->can_upload_zips()
                    ? array_merge($this->object->get_allowed_image_mime_types(), ['.zip'])
                    : get_allowed_mime_types()
            ]
        ]);
    }

    function get_uppy_dashboard_settings()
    {
        return apply_filters('ngg_uppy_dashboard_settings', [
            'inline'                        => true,
            'target'                        => '#uploader',
            'width'                         => '100%',
            'proudlyDisplayPoweredByUppy'   => false,
            'hideRetryButton'               => true,
            'note'                          => $this->object->get_uppy_note(),
            'locale'                        => [
                'strings' => [
                    'dropPaste' => $this->can_upload_zips()
                        ? __('Drag image and ZIP files here or %{browse}', 'nggallery')
                        : __('Drag image files here or %{browse}', 'nggallery')
                ]
            ]
        ]);
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

    function can_upload_zips()
    {
        $global_settings = C_NextGen_Global_Settings::get_instance();
        return (!is_multisite() || (is_multisite() && $global_settings->get('wpmuZipUpload')));
    }

    function get_i18n_strings() {
        return [
            'locale'             => $this->object->get_uppy_locale(),
            'no_image_uploaded'  => __('No images were uploaded successfully.',      'nggallery'),
            'one_image_uploaded' => __('1 image was uploaded successfully.',         'nggallery'),
            'x_images_uploaded'  => __('{count} images were uploaded successfully.', 'nggallery'),
            'manage_gallery'     => __('Manage gallery > {name}',                    'nggallery'),
            'image_failed'       => __('Image {filename} failed to upload: {error}', 'nggallery'),
            'drag_files_here'    => $this->can_upload_zips()
                ? __('Drag image and ZIP files here or %{browse}', 'nggallery')
                : __('Drag image files here or %{browse}', 'nggallery')
        ];
    }

    function render()
    {
        return $this->object->render_partial('photocrati-nextgen_addgallery_page#upload_images', [
            'galleries'             => $this->object->get_galleries(),
	        'nonce'                 => M_Security::create_nonce('nextgen_upload_image')
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