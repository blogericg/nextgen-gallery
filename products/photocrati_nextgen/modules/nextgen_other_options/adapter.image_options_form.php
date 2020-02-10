<?php

/**
 * Class A_Image_Options_Form
 * @mixin C_Form
 * @adapts I_Form using "image_options" context
 */
class A_Image_Options_Form extends Mixin
{
	function get_model()
	{
		return C_Settings_Model::get_instance();
	}

	function get_title()
	{
		return __('Image Options', 'nggallery');
	}

	/**
	 * Returns the options available for sorting images
	 * @return array
	 */
	function _get_image_sorting_options()
	{
		return array(
			__('Custom',         'nggallery') => 'sortorder',
			__('Image ID',       'nggallery') => 'pid',
			__('Filename',       'nggallery') => 'filename',
			__('Alt/Title Text', 'nggallery') => 'alttext',
			__('Date/Time',      'nggallery') => 'imagedate'
		);
	}


	/**
	 * Returns the options available for sorting directions
	 * @return array
	 */
	function _get_sorting_direction_options()
	{
		return array(
			__('Ascending',  'nggallery') => 'ASC',
			__('Descending', 'nggallery') => 'DESC'
		);
	}


	/**
	 * Returns the options available for matching related images
	 */
	function _get_related_image_match_options()
	{
		return array(
			__('Categories', 'nggallery') => 'category',
			__('Tags',       'nggallery') => 'tags'
		);
	}
        
    /**
     * Tries to create the gallery storage directory if it doesn't exist already
     * @return bool
     */
    function _create_gallery_storage_dir()
    {
        $fs = C_Fs::get_instance();

        $gallerypath = $this->object->get_model()->get('gallerypath');
        $gallerypath = $fs->join_paths($fs->get_document_root('galleries'), $gallerypath);

        if (!@file_exists($gallerypath))
        {
            @mkdir($gallerypath);
            return @file_exists($gallerypath);
        }

        return TRUE;
    }

    /**
     * Renders the form
     */
    function render()
    {
		$settings = $this->object->get_model();
		return $this->render_partial('photocrati-nextgen_other_options#image_options_tab', array(
			'gallery_path_label'			=>	__('Where would you like galleries stored?', 'nggallery'),
			'gallery_path_help'				=>	__('Where galleries and their images are stored', 'nggallery'),
			'gallery_path'					=>	$settings->gallerypath,
            'gallery_path_error_state'      => !$this->object->_create_gallery_storage_dir(),
            'gallery_path_error_message'    => __('Gallery path does not exist and could not be created', 'nggallery'),
			'delete_image_files_label'		=>	__('Delete Image Files?', 'nggallery'),
			'delete_image_files_help'		=>	__('When enabled, image files will be removed after a Gallery has been deleted', 'nggallery'),
			'delete_image_files'			=>	$settings->deleteImg,
			'show_related_images_label'		=>	__('Show Related Images on Posts?', 'nggallery'),
			'show_related_images_help'		=>	__('When enabled, related images will be appended to each post by matching the posts tags/categories to image tags', 'nggallery'),
			'show_related_images'			=>	$settings->activateTags,
			'related_images_hidden_label'	=>	__('(Show Customization Settings)', 'nggallery'),
			'related_images_active_label'	=>	__('(Hide Customization Settings)', 'nggallery'),
			'match_related_images_label'	=>	__('How should related images be matched?', 'nggallery'),
			'match_related_images'			=>	$settings->appendType,
			'match_related_image_options'	=>	$this->object->_get_related_image_match_options(),
			'max_related_images_label'		=>	__('Maximum # of related images to display', 'nggallery'),
			'max_related_images'			=>	$settings->maxImages,
			'related_images_heading_label'	=>	__('Heading for related images', 'nggallery'),
			'related_images_heading'		=>	$settings->relatedHeading,
			'sorting_order_label'			=>	__("What's the default sorting method?", 'nggallery'),
			'sorting_order_options'			=>	$this->object->_get_image_sorting_options(),
			'sorting_order'					=>	$settings->galSort,
			'sorting_direction_label'		=>	__('Sort in what direction?', 'nggallery'),
			'sorting_direction_options'		=>	$this->object->_get_sorting_direction_options(),
			'sorting_direction'				=>	$settings->galSortDir,
			'automatic_resize_label'		=>	__('Automatically resize images after upload', 'nggallery'),
			'automatic_resize_help'			=>	__('It is recommended that your images be resized to be web friendly', 'nggallery'),
			'automatic_resize'				=>	$settings->imgAutoResize,
			'resize_images_label'			=>	__('What should images be resized to?', 'nggallery'),
			'resize_images_help'			=>	__('After images are uploaded, they will be resized to the above dimensions and quality', 'nggallery'),
			'resized_image_width_label'		=>	__('Width:', 'nggallery'),
			'resized_image_height_label'	=>	__('Height:', 'nggallery'),
			'resized_image_quality_label'	=>	__('Quality:', 'nggallery'),
			'resized_image_width'			=>	$settings->imgWidth,
			'resized_image_height'			=>  $settings->imgHeight,
			'resized_image_quality'			=>	$settings->imgQuality,
			'backup_images_label'			=>	__('Backup the original images?', 'nggallery'),
			'backup_images_yes_label'		=>	__('Yes'),
			'backup_images_no_label'		=>	__('No'),
			'backup_images'					=>	$settings->imgBackup
                    
		), TRUE);
	}

	function save_action($image_options)
	{
		$save = TRUE;
		if (($image_options)) {

			// Update the gallery path. Moves all images to the new location
			if (isset($image_options['gallerypath']) && (!is_multisite() || get_current_blog_id() == 1)) {
				$fs               = C_Fs::get_instance();
				$root             = $fs->get_document_root('galleries');
                $image_options['gallerypath'] = $fs->add_trailing_slash($image_options['gallerypath']);

                $gallery_abspath = $fs->get_absolute_path($fs->join_paths($root, $image_options['gallerypath']));
                if ($gallery_abspath[0] != DIRECTORY_SEPARATOR) $gallery_abspath = DIRECTORY_SEPARATOR.$gallery_abspath;

				if (strpos($gallery_abspath, $root) === FALSE) {
					$this->object->get_model()->add_error(sprintf(__("Gallery path must be located in %s", 'nggallery'), $root), 'gallerypath');
					$storage = C_Gallery_Storage::get_instance();
					$image_options['gallerypath'] = trailingslashit($storage->get_upload_relpath());
					unset($storage);
				}
			}
			elseif (isset($image_options['gallerypath'])) {
				unset($image_options['gallerypath']);
			}

			// Sanitize input
			foreach ($image_options as $key => &$value) {
				switch ($key) {
					case 'imgAutoResize':
					case 'deleteImg':
					case 'imgWidth':
					case 'imgHeight':
					case 'imgBackup':
					case 'imgQuality':
					case 'activateTags':
					case 'maxImages':
						$value = intval($value);
						break;
					case 'galSort':
						$value = esc_html($value);
						if (!in_array(strtolower($value), array_values($this->_get_image_sorting_options()))) {
							$value = 'sortorder';
						}
						break;
					case 'galSortDir':
						$value = esc_html($value);
						if (!in_array(strtoupper($value), array('ASC', 'DESC')))	{
							$value = 'ASC';
						}
						break;
					case 'relatedHeading':
						$value = M_NextGen_Data::strip_html($value, TRUE);
						break;
				}
			}

			// Update image options
			if ($save) $this->object->get_model()->set($image_options)->save();
		}
	}

	/**
	 * Copies one directory to another
	 * @param string $src
	 * @param string $dst
	 * @return boolean
	 */
	function recursive_copy($src, $dst)
	{
		$retval = TRUE;
		$dir = opendir($src);
		@mkdir($dst);
		while(false !== ( $file = readdir($dir)) ) {
			if (( $file != '.' ) && ( $file != '..' )) {
				if ( is_dir($src . '/' . $file) ) {
					if (!$this->object->recursive_copy($src . '/' . $file,$dst . '/' . $file)) {
						$retval = FALSE;
						break;
					}
				}
				else {
					if (!copy($src . '/' . $file,$dst . '/' . $file)) {
						$retval = FALSE;
						break;
					}
				}
			}
		}
		closedir($dir);
		return $retval;
	}

	/**
	 * Deletes all files within a particular directory
	 * @param string $dir
	 * @return boolean
	 */
	function recursive_delete($dir)
	{
		$retval = FALSE;
        $fp = opendir($dir);
		while(false !== ( $file = readdir($fp)) ) {
			if (( $file != '.' ) && ( $file != '..' )) {
                $file = $dir.'/'.$file;
				if ( is_dir($file) ) {
					$retval = $this->object->recursive_delete($file);
				}
				else {
					$retval = unlink($file);
				}
			}
		}
        closedir($fp);
        @rmdir($dir);
		return $retval;
	}
}
