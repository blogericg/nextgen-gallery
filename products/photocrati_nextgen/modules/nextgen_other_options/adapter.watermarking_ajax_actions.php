<?php

/**
 * Class A_Watermarking_Ajax_Actions
 * @mixin C_Ajax_Controller
 * @adapts I_Ajax_Controller
 */
class A_Watermarking_Ajax_Actions extends Mixin
{
	/**
	 * Gets the new watermark preview url based on the new settings
	 * @return array
	 */
	function get_watermark_preview_url_action()
	{
		if (M_Security::is_allowed('nextgen_edit_settings')) {
			$settings = C_NextGen_Settings::get_instance();
			$imagegen = C_Dynamic_Thumbnails_Manager::get_instance();
			$mapper = C_Image_Mapper::get_instance();
			$image = $mapper->find_first();
			$storage = C_Gallery_Storage::get_instance();
			$watermark_options = $this->param('watermark_options');
			$sizeinfo = array(
				'quality' => 100,
				'height' => 250,
				'crop' => false,
				'watermark' => true,
				'wmFont' => trim(esc_sql($watermark_options['wmFont'])),
				'wmType' => trim(esc_sql($watermark_options['wmType'])),
				'wmPos' => trim(esc_sql($watermark_options['wmPos'])),
				'wmXpos' => intval(trim($watermark_options['wmXpos'])),
				'wmYpos' => intval(trim($watermark_options['wmYpos'])),
				'wmPath' => trim(esc_sql($watermark_options['wmPath'])),
				'wmText' => trim(esc_sql($watermark_options['wmText'])),
				'wmOpaque' => intval(trim($watermark_options['wmOpaque'])),
				'wmFont' => trim(esc_sql($watermark_options['wmFont'])),
				'wmSize' => intval(trim($watermark_options['wmSize'])),
				'wmColor' => trim(esc_sql($watermark_options['wmColor']))
			);
			$size = $imagegen->get_size_name($sizeinfo);
			$storage->generate_image_size($image, $size, $sizeinfo);
			$storage->flush_image_path_cache($image, $size);
			$thumbnail_url = $storage->get_image_url($image, $size);
			return array('thumbnail_url' => $thumbnail_url);
		} else {
			return array('thumbnail_url' => '', 'error' => 'You are not allowed to perform this operation');
		}
	}
}
