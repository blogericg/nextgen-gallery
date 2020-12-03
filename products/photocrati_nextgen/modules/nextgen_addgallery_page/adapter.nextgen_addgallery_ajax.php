<?php

/**
 * Class A_NextGen_AddGallery_Ajax
 * @mixin C_Ajax_Controller
 * @adapts I_Ajax_Controller
 */
class A_NextGen_AddGallery_Ajax extends Mixin
{
	function cookie_dump_action()
	{
        foreach ($_COOKIE as $key => &$value) {
            if (is_string($value)) $value = stripslashes($value);
        }

		return array('success' => 1, 'cookies' => $_COOKIE);
	}

    function create_new_gallery_action()
    {
        $gallery_name    = urldecode($this->param('gallery_name'));
        $gallery_mapper  = C_Gallery_Mapper::get_instance();

        $retval = [
            'gallery_name' => esc_html($gallery_name),
            'gallery_id'   => NULL
        ];

        if (!$this->validate_ajax_request('nextgen_upload_image', TRUE))
        {
            $action = 'nextgen_upload_image';
            $retval['allowed']        = M_Security::is_allowed($action);
            $retval['verified_token'] = (!$_REQUEST['nonce'] || wp_verify_nonce($_REQUEST['nonce'], $action));
            $retval['error']          = __("No permissions to upload images. Try refreshing the page or ensuring that your user account has sufficient roles/privileges.", 'nggallery');
            return $retval;
        }

        if (strlen($gallery_name) > 0)
        {
            $gallery = $gallery_mapper->create(['title' => $gallery_name]);
            if (!$gallery->save())
                $retval['error'] = $gallery->get_errors();
            else
                $retval['gallery_id'] = $gallery->id();
        }
        else {
            $retval['error'] = __("No gallery name specified", 'nggallery');
        }

        return $retval;
    }

    function upload_image_action()
    {
        $created_gallery = FALSE;
        $gallery_id      = intval($this->param('gallery_id'));
        $gallery_name    = urldecode($this->param('gallery_name'));
        $gallery_mapper  = C_Gallery_Mapper::get_instance();

        $retval = [
            'gallery_name' => esc_html($gallery_name)
        ];

        if ($this->validate_ajax_request('nextgen_upload_image', TRUE))
        {
        	  if (!class_exists('DOMDocument')) {
                  $retval['error'] = __("Please ask your hosting provider or system administrator to enable the PHP XML module which is required for image uploads", 'nggallery');
	          }
	          else {
		          // We need to create a gallery
		          if ($gallery_id == 0)
		          {
			          if (strlen($gallery_name) > 0)
			          {
				          $gallery = $gallery_mapper->create(['title' =>  $gallery_name]);
				          if (!$gallery->save())
				          {
					          $retval['error'] = $gallery->get_errors();
				          }
				          else {
					          $created_gallery = TRUE;
					          $gallery_id      = $gallery->id();
				          }
			          }
			          else {
				          $retval['error'] = __("No gallery name specified", 'nggallery');
			          }
		          }

		          // Upload the image to the gallery
		          if (empty($retval['error']))
		          {
			          $retval['gallery_id'] = $gallery_id;
			          $settings = C_NextGen_Settings::get_instance();
			          $storage = C_Gallery_Storage::get_instance();

			          try {
				          if ($storage->is_zip())
				          {
					          if (($results = $storage->upload_zip($gallery_id)))
						          $retval = $results;
					          else
						          $retval['error'] = __('Failed to extract images from ZIP', 'nggallery');
				          }
				          elseif (($image_id = $storage->upload_image($gallery_id))) {
					          $retval['image_ids'] = array($image_id);

					          // check if image was resized correctly
					          if ($settings->get('imgAutoResize'))
					          {
						          $image_path = $storage->get_full_abspath($image_id);
						          $image_thumb = new C_NggLegacy_Thumbnail($image_path, true);

						          if ($image_thumb->error)
							          $retval['error'] = sprintf(__('Automatic image resizing failed [%1$s].', 'nggallery'), $image_thumb->errmsg);
					          }

					          // check if thumb was generated correctly
					          $thumb_path = $storage->get_image_abspath($image_id, 'thumb');
					          if (!file_exists($thumb_path))
						          $retval['error'] = __('Thumbnail generation failed.', 'nggallery');
				          }
				          else {
					          $retval['error'] = __('Image generation failed', 'nggallery');
				          }
			          }
			          catch (E_NggErrorException $ex) {
				          $retval['error'] = $ex->getMessage();
				          if ($created_gallery)
				              $gallery_mapper->destroy($gallery_id);
			          }
			          catch (Exception $ex) {
				          $retval['error'] = sprintf(__("An unexpected error occurred: %s", 'nggallery'), $ex->getMessage());
			          }
		          }
	          }
		}
        else {
            $action = 'nextgen_upload_image';
            $retval['allowed']        = M_Security::is_allowed($action);
            $retval['verified_token'] = (!$_REQUEST['nonce'] || wp_verify_nonce($_REQUEST['nonce'], $action));
            $retval['error']          = __("No permissions to upload images. Try refreshing the page or ensuring that your user account has sufficient roles/privileges.", 'nggallery');
        }

        // Sending a 500 header is used for uppy.js to determine upload failures
        if (!empty($retval['error']))
            header('HTTP/1.1 500 Internal Server Error');

        return $retval;
    }

	function get_import_root_abspath()
	{
		if ( is_multisite() ) {
			$root = C_Gallery_Storage::get_instance()->get_upload_abspath();
		} else {
			$root = NGG_IMPORT_ROOT;
		}
		$root = str_replace('/', DIRECTORY_SEPARATOR, $root);

		return untrailingslashit($root);
	}

    function browse_folder_action()
    {
        $retval = array();
        $html = array();
        
        if ($this->validate_ajax_request('nextgen_upload_image', TRUE))
        {
		      if (($dir = urldecode($this->param('dir')))) {
		          $fs = C_Fs::get_instance();
			      $root = $this->get_import_root_abspath();
			      $browse_path = $fs->join_paths($root, $dir);
			      if (strpos(realpath($browse_path), realpath($root)) !== FALSE) {
                      if (@file_exists($browse_path))
                      {
                          $files = scandir($browse_path);
                          natcasesort($files);
                          if (count($files) > 2)
                          {
                              /* The 2 accounts for . and .. */
                              $html[] = "<ul class=\"jqueryFileTree\" style=\"display: none;\">";
                              foreach ($files as $file) {
                                  $file_path = $fs->join_paths($browse_path, $file);
                                  $rel_file_path = str_replace($root, '', $file_path);
                                  if (@file_exists($file_path) && $file != '.' && $file != '..' && is_dir($file_path))
                                  {
                                      $html[] = "<li class=\"directory collapsed\"><a href=\"#\" rel=\"" . htmlentities($rel_file_path) . "/\">" . htmlentities($file) . "</a></li>";
                                  }
                              }
                              $html[] = "</ul>";
                          }
                          $retval['html'] = implode("\n", $html);
                      }
                      else {
                          $retval['error'] = __("Directory does not exist.", 'nggallery');
                      }
                  }
                  else {
                      $retval['error'] = __("No permissions to browse folders. Try refreshing the page or ensuring that your user account has sufficient roles/privileges.", 'nggallery');
                  }
		      }
		      else {
		          $retval['error'] = __("No directory specified.", 'nggallery');
		      }
	      }
        else {
          $retval['error'] = __("No permissions to browse folders. Try refreshing the page or ensuring that your user account has sufficient roles/privileges.", 'nggallery');
        }

        return $retval;
    }

    function import_folder_action()
    {
	    $retval = array();

	    if ( $this->validate_ajax_request( 'nextgen_upload_image' , $_REQUEST['nonce']) ) {
		    if ( ( $folder = $this->param( 'folder' ) ) ) {
			    $storage = C_Gallery_Storage::get_instance();
			    $fs      = C_Fs::get_instance();
			    try {
				    $keep_files = $this->param( 'keep_location' ) == 'on';
                    $gallery_title = $this->param('gallery_title', NULL);
                    if (empty($gallery_title)) $gallery_title = NULL;
				    $root = $this->get_import_root_abspath();
				    $import_path = $fs->join_paths($root, $folder);
				    if (strpos(realpath($import_path), realpath($root)) !== FALSE) {
                        $retval = $storage->import_gallery_from_fs(
                            $import_path,
                            FALSE,
                            !$keep_files,
                            $gallery_title
                        );
                        if (!$retval) {
                            $retval = array('error' => "Could not import folder. No images found.");
                        }
                    }
                    else {
                        $retval['error'] = __( "No permissions to import folders. Try refreshing the page or ensuring that your user account has sufficient roles/privileges.", 'nggallery' );
                    }
			    } catch ( E_NggErrorException $ex ) {
				    $retval['error'] = $ex->getMessage();
			    } catch ( Exception $ex ) {
				    $retval['error']         = __( "An unexpected error occured.", 'nggallery' );
				    $retval['error_details'] = $ex->getMessage();
			    }
		    } else {
			    $retval['error'] = __( "No folder specified", 'nggallery' );
		    }
	    } else {
		    $retval['error'] = __( "No permissions to import folders. Try refreshing the page or ensuring that your user account has sufficient roles/privileges.", 'nggallery' );
	    }

	    return $retval;
    }

    function import_media_library_action()
    {
        $retval          = array();
        $created_gallery = FALSE;
        $gallery_id      = intval($this->param('gallery_id'));
        $gallery_name    = urldecode($this->param('gallery_name'));
        $gallery_mapper  = C_Gallery_Mapper::get_instance();
        $image_mapper    = C_Image_Mapper::get_instance();
        $attachment_ids  = $this->param('attachment_ids');

        if ($this->validate_ajax_request('nextgen_upload_image', $_REQUEST['nonce']))
        {
            if (empty($attachment_ids) || !is_array($attachment_ids))
            {
                $retval['error'] = __('An unexpected error occured.', 'nggallery');
            }

            if (empty($retval['error']) && $gallery_id == 0)
            {
                if (strlen($gallery_name) > 0)
                {
                    $gallery = $gallery_mapper->create(array('title' => $gallery_name));
                    if (!$gallery->save())
                    {
                        $retval['error'] = $gallery->get_errors();
                    }
                    else {
                        $created_gallery = TRUE;
                        $gallery_id = $gallery->id();
                    }
                }
                else {
                    $retval['error'] = __('No gallery name specified', 'nggallery');
                }
            }

            if (empty($retval['error']))
            {
                $retval['gallery_id'] = $gallery_id;
                $storage = C_Gallery_Storage::get_instance();

                foreach ($attachment_ids as $id) {
                    try {
                        $abspath    = get_attached_file($id);
                        $file_data  = @file_get_contents($abspath);
                        $file_name  = M_I18n::mb_basename($abspath);
                        $attachment = get_post($id);

                        if (empty($file_data))
                        {
                            $retval['error'] = __('Image generation failed', 'nggallery');
                            break;
                        }

                        $image = $storage->upload_image($gallery_id, $file_name, $file_data);
                        if ($image)
                        {
                            // Potentially import metadata from WordPress
                            $image = $image_mapper->find($image);
                            if (!empty($attachment->post_excerpt))
                                $image->alttext = $attachment->post_excerpt;
                            if (!empty($attachment->post_content))
                                $image->description = $attachment->post_content;

                            $image = apply_filters('ngg_medialibrary_imported_image', $image, $attachment);
                            $image_mapper->save($image);
                            $retval['image_ids'][] = $image->{$image->id_field};
                        }
                        else {
                            $retval['error'] = __('Image generation failed', 'nggallery');
                            break;
                        }
                    }
                    catch (E_NggErrorException $ex) {
                        $retval['error'] = $ex->getMessage();
                        if ($created_gallery)
                            $gallery_mapper->destroy($gallery_id);
                    }
                    catch (Exception $ex) {
                        $retval['error'] = __('An unexpected error occured.', 'nggallery');
                        $retval['error_details'] = $ex->getMessage();
                    }
                }
            }
        }
        else {
            $retval['error'] = __('No permissions to upload images. Try refreshing the page or ensuring that your user account has sufficient roles/privileges.', 'nggallery');
        }

        if (!empty($retval['error']))
            return $retval;
        else
            $retval['gallery_name'] = esc_html($gallery_name);

        return $retval;
    }
}
