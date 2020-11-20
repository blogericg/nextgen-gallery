<?php

/**
 * Class A_Watermarks_Form
 * @mixin C_Form
 * @adapts I_Form using "watermarks" context
 */
class A_Watermarks_Form extends Mixin
{
	function get_model()
	{
		return C_Settings_Model::get_instance();
	}

	function get_title()
	{
		return __('Watermarks', 'nggallery');
	}

	/**
	 * Gets all fonts installed for watermarking
	 * @return array
	 */
	function _get_watermark_fonts()
	{
		$retval = array();
        $path = implode(DIRECTORY_SEPARATOR, array(
           rtrim(NGGALLERY_ABSPATH, "/\\"),
            'fonts'
        ));
		foreach (scandir($path) as $filename) {
			if (strpos($filename, '.') === 0) continue;
			else $retval[] = $filename;
		}
		return $retval;
	}

	/**
	 * Gets watermark sources, along with their respective fields
	 * @return array
	 */
	function _get_watermark_sources()
	{
		// We do this so that an adapter can add new sources
		return array(
			__('Using an Image', 'nggallery') => 'image',
			__('Using Text', 'nggallery') => 'text'
		);
	}

	/**
	 * Renders the fields for a watermark source (image, text)
	 * @return array
	 */
	function _get_watermark_source_fields()
	{
		$retval = array();
		foreach ($this->object->_get_watermark_sources() as $label => $value) {
			$method = "_render_watermark_{$value}_fields";
            if ($this->object->has_method($method)) {
                $retval[$value] = $this->object->call_method($method);
            }
		}
		return $retval;
	}

	/**
	 * Render fields that are needed when 'image' is selected as a watermark
	 * source
	 * @return string
	 */
	function _render_watermark_image_fields()
	{
        $message = __('An absolute or relative (to the site document root) file system path', 'nggallery');
        if (ini_get('allow_url_fopen'))
            $message = __('An absolute or relative (to the site document root) file system path or an HTTP url', 'nggallery');

        return $this->object->render_partial('photocrati-nextgen_other_options#watermark_image_fields', array(
			'image_url_label'			=>	__('Image URL:', 'nggallery'),
			'watermark_image_url'		=>	$this->object->get_model()->wmPath,
            'watermark_image_text'      =>  $message
		), TRUE);
	}

	/**
	 * Render fields that are needed when 'text is selected as a watermark
	 * source
	 * @return string
	 */
	function _render_watermark_text_fields()
	{
		$settings = $this->object->get_model();
		return $this->object->render_partial('photocrati-nextgen_other_options#watermark_text_fields', array(
			'fonts'						=>	$this->object->_get_watermark_fonts($settings),
			'font_family_label'			=>	__('Font Family:', 'nggallery'),
			'font_family'				=>	$settings->wmFont,
			'font_size_label'			=>	__('Font Size:', 'nggallery'),
			'font_size'					=>	$settings->wmSize,
			'font_color_label'			=>	__('Font Color:', 'nggallery'),
			'font_color'				=>	strpos($settings->wmColor, '#') === 0 ?
											$settings->wmColor : "#{$settings->wmColor}",
			'watermark_text_label'		=>	__('Text:', 'nggallery'),
			'watermark_text'			=>	$settings->wmText,
			'opacity_label'				=>	__('Opacity:', 'nggallery'),
			'opacity'					=>	$settings->wmOpaque,
		), TRUE);
	}

    function _get_preview_image()
    {
		xdebug_break();
        $registry = $this->object->get_registry();
        $storage  = C_Gallery_Storage::get_instance();
        $image    = C_Image_Mapper::get_instance()->find_first();
		$imagegen = C_Dynamic_Thumbnails_Manager::get_instance();
		$settings = C_NextGen_Settings::get_instance();
		$watermark_setting_keys = array('wmFont', 'wmType', 'wmPos', 'wmXpos', 'wmYpos', 'wmPath', 'wmText', 'wmOpaque', 'wmFont', 'wmSize', 'wmColor');
		$watermark_options = array();
		foreach ($watermark_setting_keys as $watermark_setting_key) {
			$watermark_options[$watermark_setting_key] = $settings->get($watermark_setting_key);
		}
        $sizeinfo = array(
			'quality' => 100,
			'height' => 250,
			'crop' => false,
			'watermark' => true,
			'wmFont' => trim(esc_sql($watermark_options['wmFont'])),
			'wmType' => trim(esc_sql($watermark_options['wmType'])),
			'wmPos' => trim(esc_sql($watermark_options['wmPos'])),
			'wmXpos' => trim(intval($watermark_options['wmXpos'])),
			'wmYpos' => trim(intval($watermark_options['wmYpos'])),
			'wmPath' => trim(esc_sql($watermark_options['wmPath'])),
			'wmText' => trim(esc_sql($watermark_options['wmText'])),
			'wmOpaque' =>intval(trim($watermark_options['wmOpaque'])),
			'wmFont' => trim(esc_sql($watermark_options['wmFont'])),
			'wmSize' => trim(intval($watermark_options['wmSize'])),
			'wmColor' => trim(esc_sql($watermark_options['wmColor']))
		);
		$size = $imagegen->get_size_name($sizeinfo);
        $url = $image ? $storage->get_image_url($image, $size) : NULL;
        $abspath = $image ? $storage->get_image_abspath($image, $size) : NULL;
        return (array('url' => $url, 'abspath' => $abspath));
    }

	function render()
	{
	    /** @var C_Photocrati_Settings_Manager $settings */
		$settings = $this->get_model();
        $image    = $this->object->_get_preview_image();

		return $this->render_partial('photocrati-nextgen_other_options#watermarks_tab', array(
            'watermark_automatically_at_upload_value'     => $settings->get('watermark_automatically_at_upload', 0),
			'watermark_automatically_at_upload_label'     => __('Automatically watermark images during upload:', 'nggallery'),
            'watermark_automatically_at_upload_label_yes' => __('Yes', 'nggallery'),
            'watermark_automatically_at_upload_label_no'  => __('No', 'nggallery'),
            'notice'					=>	__('Please note: You can only activate the watermark under Manage Gallery. This action cannot be undone.', 'nggallery'),
            'watermark_source_label'	=>	__('How will you generate a watermark?', 'nggallery'),
			'watermark_sources'			=>	$this->object->_get_watermark_sources(),
			'watermark_fields'			=>	$this->object->_get_watermark_source_fields($settings),
			'watermark_source'			=>	$settings->wmType,
			'position_label'			=>	__('Position:', 'nggallery'),
			'position'					=>	$settings->wmPos,
			'offset_label'				=>	__('Offset:', 'nggallery'),
			'offset_x'					=>	$settings->wmXpos,
			'offset_y'					=>	$settings->wmYpos,
			'hidden_label'				=>	__('(Show Customization Options)', 'nggallery'),
			'active_label'				=>	__('(Hide Customization Options)', 'nggallery'),
            'thumbnail_url'             => $image['url'],
            'preview_label'             => __('Preview of saved settings:', 'nggallery'),
            'refresh_label'             => __('Refresh preview image', 'nggallery'),
            'refresh_url'               => $settings->ajax_url
		), TRUE);
	}

	function save_action()
	{
		if (($settings = $this->object->param('watermark_options'))) {

			// Sanitize
			foreach ($settings as $key => &$value) {
				switch($key) {
					case 'wmType': 
						if (!in_array($value, array('', 'text', 'image'))) {
							$value = '';
						}
						break;

					case 'wmPos':
						if (!in_array($value, array('topLeft', 'topCenter', 'topRight', 'midLeft', 'midCenter', 'midRight', 'botLeft', 'botCenter', 'botRight'))) {
							$value = 'midCenter';
						}
						break;

					case 'wmXpos':
					case 'wmYpos':
						$value = intval($value);
						break;
					case 'wmText':
						$value = M_NextGen_Data::strip_html($value);
						break;
				}
			}

			$this->object->get_model()->set($settings)->save();
            $image = $this->object->_get_preview_image();
            if (is_file($image['abspath']))
                @unlink($image['abspath']);
		}
	}
}
