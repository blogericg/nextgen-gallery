<?php

/**
 * Class A_Upload_Images_Form
 * @mixin C_Form
 * @adapts I_Form for "upload_images" context
 */
class A_Upload_Images_Form extends Mixin
{
    function get_title()
    {
        return __("Upload Images", 'nggallery');
    }

    function get_i18n_strings()
    {
        return array(
            'no_image_uploaded'     =>  __('No images were uploaded successfully.', 'nggallery'),
            'one_image_uploaded'    =>  __('1 image was uploaded successfully.', 'nggallery'),
            'x_images_uploaded'     =>  __('{count} images were uploaded successfully.', 'nggallery'),
            'image_errors'          =>  __('The following errors occured:', 'nggallery'),
            'manage_gallery'        =>  __('Manage gallery > {name}', 'nggallery')
        );
    }

    /**
     * Plupload stores its i18n JS *mostly* as "en.js" or "ar.js" - but some as zh_CN.js so we must check both if the
     * first does not match.
     *
     * @return bool|string
     */
    function _find_plupload_i18n()
    {
        $fs     = C_Fs::get_instance();
        $router = C_Router::get_instance();
        $locale = get_locale();

        $dir = M_Static_Assets::get_static_abspath('photocrati-nextgen_addgallery_page#plupload-2.1.1/i18n');

        $tmp = explode('_', $locale, 2);

        $retval = FALSE;

        if (file_exists($dir . $tmp[0] . '.js'))
            $retval = $tmp[0];
        else if (file_exists($dir . DIRECTORY_SEPARATOR . $tmp[0] . '.js'))
            $retval = $tmp[0];
        else if (file_exists($dir . $locale . '.js'))
            $retval = $locale;

        if ($retval)
            $retval = M_Static_Assets::get_static_url('photocrati-nextgen_addgallery_page#plupload-2.1.1/i18n/' . $retval . '.js');

        return $retval;
    }

    function enqueue_static_resources()
    {
        wp_enqueue_style('ngg.plupload.queue');
        wp_enqueue_script('browserplus');
        wp_enqueue_script('ngg.plupload.queue');
        wp_localize_script('ngg.plupload.queue', 'NggUploadImages_i18n', $this->object->get_i18n_strings());

        $i18n = $this->_find_plupload_i18n();
        if (!empty($i18n))
            wp_enqueue_script('ngg.plupload.i18n', $i18n, array('ngg.plupload.full'), NGG_SCRIPT_VERSION);

    }

    function render()
    {
        return $this->object->render_partial('photocrati-nextgen_addgallery_page#upload_images', array(
            'plupload_options' => json_encode($this->object->get_plupload_options()),
            'galleries'        => $this->object->get_galleries(),
	        'nonce'             => M_Security::create_nonce('nextgen_upload_image')

        ), TRUE);
    }

    function get_plupload_options()
    {
        $retval = array();

        $retval['runtimes']             = 'browserplus,html5,silverlight,html4';
        $retval['max_file_size']        = strval(round( (int) wp_max_upload_size() / 1024 )).'kb';
        $retval['filters']              = $this->object->get_plupload_filters();
        $retval['flash_swf_url']        = includes_url('js/plupload/plupload.flash.swf');
        $retval['silverlight_xap_url']  = includes_url('js/plupload/plupload.silverlight.xap');
        $retval['debug']                = TRUE;
        $retval['prevent_duplicates']   = TRUE;

        return $retval;
    }

    function get_plupload_filters()
    {
        $retval                     = new stdClass;
        $retval->mime_types         = array();

        $imgs                       = new stdClass;
        $imgs->title                = "Image files";
        $imgs->extensions           = "jpg,jpeg,gif,png,JPG,JPEG,GIF,PNG";
        $retval->mime_types[]       = $imgs;

        $settings = C_NextGen_Settings::get_instance();
        if (!is_multisite() || (is_multisite() && $settings->get('wpmuZipUpload')))
        {
            $zips                   = new stdClass;
            $zips->title            = "Zip files";
            $zips->extensions       = "zip,ZIP";
            $retval->mime_types[]   = $zips;
        }

        $retval->xss_protection = TRUE;

        return $retval;
    }

    function get_galleries()
    {
        $galleries = array();
        
        if (M_Security::is_allowed('nextgen_edit_gallery')) {
		      $gallery_mapper = C_Gallery_Mapper::get_instance();
		      $galleries = $gallery_mapper->find_all();
		      
		      if (!M_Security::is_allowed('nextgen_edit_gallery_unowned'))
		      {
		      	$galleries_all = $galleries;
		      	$galleries = array();
		      	
		      	foreach ($galleries_all as $gallery)
		      	{
		      		if (wp_get_current_user()->ID == (int)$gallery->author)
		      		{
		      			$galleries[] = $gallery;
		      		}
		      	}
		      }
        }
        
        return $galleries;
    }
}
