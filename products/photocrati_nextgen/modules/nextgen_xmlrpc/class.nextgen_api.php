<?php

/**
 * Class C_NextGen_API
 * @implements I_NextGen_API
 */
class C_NextGen_API extends C_Component
{
	const CRON_KEY = 'nextgen.api.task_list';
	
	/* NOTE: these constants' numeric values MUST remain the same, do NOT change the values */
	const ERR_NO_TASK_LIST = 1001;
	const ERR_NOT_AUTHENTICATED = 1002;
	const ERR_NOT_AUTHORIZED = 1003;
	const ERR_JOB_NOT_ADDED = 1004;
	const ERR_FTP_NOT_AUTHENTICATED = 1101;
	const ERR_FTP_NOT_CONNECTED = 1102;
	const ERR_FTP_NO_PATH = 1103;
	
	const INFO_NO_JOB_LIST = 6001;
	const INFO_JOB_LIST_FINISHED = 6002;
	const INFO_JOB_LIST_UNFINISHED = 6003;
	const INFO_EXECUTION_LOCKED = 6004;
	
	public static $_instances = array();
	
	var $_start_time;

    /**
     * @param bool|string $context
     * @return C_NextGen_API
     */
	public static function get_instance($context = false)
	{
		if (!isset(self::$_instances[$context]))
			self::$_instances[$context] = new C_NextGen_API($context);
		return self::$_instances[$context];
	}

	function define($context = false)
	{
		parent::define($context);
		
		$this->implement('I_NextGen_API');
		
		$this->_start_time = time();
	}
	
	function should_stop_execution()
	{
		$timeout = defined('NGG_API_JOB_HANDLER_TIMEOUT') ? intval(NGG_API_JOB_HANDLER_TIMEOUT) : (intval(ini_get('max_execution_time')) - 3);
		$timeout = $timeout > 0 ? $timeout : 27; /* most hosts have a limit of 30 seconds execution time, so 27 should be a safe default */
		
		return (time() - $this->_start_time >= $timeout);
	}
	
	function is_execution_locked()
	{
		$lock_time = get_option('ngg_api_execution_lock', 0);
		
		if ($lock_time == 0)
			return false;
			
		$lock_max = defined('NGG_API_EXECUTION_LOCK_MAX') ? intval(NGG_API_EXECUTION_LOCK_MAX) : 0;
		$lock_max = $lock_max > 0 ? $lock_max : 60 * 5; /* if the lock is 5 minutes old assume something went wrong and the lock couldn't be unset */
		
		$time_diff = time() - $lock_time;
		
		if ($time_diff > $lock_max)
			return false;
		
		return true;
	}
	
	function set_execution_locked($locked)
	{
		if ($locked)
			update_option('ngg_api_execution_lock', time(), false);
		else
			update_option('ngg_api_execution_lock', 0, false);
	}
	
	function get_job_list()
	{
		return get_option('ngg_api_job_list');
	}
	
	function add_job($job_data, $app_config, $task_list)
	{
		$job_list = $this->get_job_list();
		$job_id = uniqid();
		
		while (isset($job_list[$job_id]))
			$job_id = uniqid();
		
		$job = array(
			'id' => $job_id,
			'post_back' => array(
				'token' => md5($job_id),
			),
			'data' => $job_data,
			'app_config' => $app_config,
			'task_list' => $task_list
		);
		
		$job_list[$job_id] = $job;
		
		update_option('ngg_api_job_list', $job_list, false);
		
		return $job_id;
	}
	
	function _update_job($job_id, $job)
	{
		$job_list = $this->get_job_list();
		
		if (isset($job_list[$job_id])) {
			$job_list[$job_id] = $job;
			
			update_option('ngg_api_job_list', $job_list, false);
		}
	}
	
	function remove_job($job_id)
	{
		$job_list = $this->get_job_list();
		
		if (isset($job_list[$job_id])) {
			unset($job_list[$job_id]);
			
			update_option('ngg_api_job_list', $job_list, false);
		}
	}
	
	function get_job($job_id)
	{
		$job_list = $this->get_job_list();
		
		if (isset($job_list[$job_id]))
			return $job_list[$job_id];
		
		return null;
	}
	
	function get_job_data($job_id)
	{
		$job = $this->get_job($job_id);
		
		if ($job != null)
			return $job['data'];
		
		return null;
	}
	
	function get_job_task_list($job_id)
	{
		$job = $this->get_job($job_id);
		
		if ($job != null)
			return $job['task_list'];
		
		return null;
	}
	
	function set_job_task_list($job_id, $task_list)
	{
		$job = $this->get_job($job_id);
		
		if ($job != null) {
			$job['task_list'] = $task_list;
			
			$this->_update_job($job_id, $job);
			
			return true;
		}
		
		return false;
	}
	
	function get_job_post_back($job_id)
	{
		$job = $this->get_job($job_id);
		
		if ($job != null)
			return $job['post_back'];
		
		return null;
	}
	
	function authenticate_user($username, $password, $token, $regenerate_token = false)
	{
		$user_obj = null;
		
		if ($token != null) {
			$users = get_users(array('meta_key' => 'nextgen_api_token', 'meta_value' => $token));
			
			if ($users != null && count($users) > 0)
				$user_obj = $users[0];
		}
		
		if ($user_obj == null) {
			if ($username != null && $password != null) {
				$user_obj = wp_authenticate($username, $password);
				$token = get_user_meta($user_obj->ID, 'nextgen_api_token', true);
				
				if ($token == null)
					$regenerate_token = true;
			}
		}
		
		if (is_a($user_obj, 'WP_Error'))
			$user_obj = null;
		
		if ($regenerate_token) {
			if ($user_obj != null) {
				$token = '';
					
				if (function_exists('random_bytes'))
					$token = bin2hex(random_bytes(16));
				else if (function_exists('openssl_random_pseudo_bytes'))
					$token = bin2hex(openssl_random_pseudo_bytes(16));
				else {
					for ($i = 0; $i < 16; $i++)
						$token .= bin2hex(mt_rand(0, 15));
				}
				
				update_user_meta($user_obj->ID, 'nextgen_api_token', $token);
			}
		}
		
		return $user_obj;
	}
	
	function create_filesystem_access($args, $method = null)
	{
		// taken from wp-admin/includes/file.php but with modifications
		if ( ! $method && isset($args['connection_type']) && 'ssh' == $args['connection_type'] && extension_loaded('ssh2') && function_exists('stream_get_contents') ) $method = 'ssh2';
		if ( ! $method && extension_loaded('ftp') ) $method = 'ftpext';
		if ( ! $method && ( extension_loaded('sockets') || function_exists('fsockopen') ) ) $method = 'ftpsockets'; //Sockets: Socket extension; PHP Mode: FSockopen / fwrite / fread

		if ( ! $method )
			return false;
			
		require_once(ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php');
		
		if ( ! class_exists( "WP_Filesystem_$method" ) ) {

			/**
			 * Filter the path for a specific filesystem method class file.
			 *
			 * @since 2.6.0
			 *
			 * @see get_filesystem_method()
			 *
			 * @param string $path   Path to the specific filesystem method class file.
			 * @param string $method The filesystem method to use.
			 */
			$abstraction_file = apply_filters( 'filesystem_method_file', ABSPATH . 'wp-admin/includes/class-wp-filesystem-' . $method . '.php', $method );

			if ( ! file_exists($abstraction_file) )
					return false;

			require_once($abstraction_file);
		}
		
		$method_class = "WP_Filesystem_$method";
 
		$wp_filesystem = new $method_class($args);
		
    //Define the timeouts for the connections. Only available after the construct is called to allow for per-transport overriding of the default.
    if ( ! defined('FS_CONNECT_TIMEOUT') )
        define('FS_CONNECT_TIMEOUT', 30);
    if ( ! defined('FS_TIMEOUT') )
        define('FS_TIMEOUT', 30);
        
		if ( is_wp_error($wp_filesystem->errors) && $wp_filesystem->errors->get_error_code() )
				return false;
 
		if ( !$wp_filesystem->connect() ) {
			if ($method == 'ftpext') // attempt connecting with alternative method
				return $this->create_filesystem_access($args, 'ftpsockets');
			
			return false; //There was an error connecting to the server.
		}
		
    // Set the permission constants if not already set.
    if ( ! defined('FS_CHMOD_DIR') )
        define('FS_CHMOD_DIR', ( fileperms( ABSPATH ) & 0777 | 0755 ) );
    if ( ! defined('FS_CHMOD_FILE') )
        define('FS_CHMOD_FILE', ( fileperms( ABSPATH . 'index.php' ) & 0777 | 0644 ) );
 
 		return $wp_filesystem;
	}
	
	// returns an actual scalar ID based on parametric ID (e.g. a parametric ID could represent the query ID from another task)
	function get_query_id($id, &$task_list)
	{
		$task_id = $id;
		
		if (is_object($task_id) || is_array($task_id))
		{
			$id = null;
			
			// it was specified that the query ID is referencing the query ID from another task
			if (isset($task_id['target']) && $task_id['target'] == 'task')
			{
				if (isset($task_id['id']) && isset($task_list[$task_id['id']]))
				{
					$target_task = $task_list[$task_id['id']];
					
					if (isset($target_task['query']['id']))
					{
						$id = $target_task['query']['id'];
					}
				}
			}
		}
		
		return $id;
	}
	
	// returns an actual scalar ID based on parametric ID (e.g. a parametric ID could represent the resulting object ID from another task)
	function get_object_id($id, &$result_list)
	{
		$task_id = $id;
		
		if (is_object($task_id) || is_array($task_id))
		{
			$id = null;
			
			// it was specified that the query ID is referencing the result from another task
			if (isset($task_id['target']) && $task_id['target'] == 'task')
			{
				if (isset($task_id['id']) && isset($result_list[$task_id['id']]))
				{
					$target_result = $result_list[$task_id['id']];
					
					if (isset($target_result['object_id']))
					{
						$id = $target_result['object_id'];
					}
				}
			}
		}
		
		return $id;
	}
	
	function _array_find_by_entry(array $array_target, $entry_key, $entry_value)
	{
		foreach ($array_target as $key => $value) {
			$item = $value;
			
			if (isset($item[$entry_key]) && $item[$entry_key] == $entry_value)
				return $key;
		}
		
		return null;
	}
	
	function _array_filter_by_entry(array $array_target, array $array_source, $entry_key)
	{
		foreach ($array_source as $key => $value) {
			$item = $value;
			
			if (isset($item[$entry_key])) {
				$find_key = $this->_array_find_by_entry($array_target, $entry_key, $item[$entry_key]);
				
				if ($find_key !== null) {
					unset($array_target[$find_key]);
				}
			}
		}
		
		return $array_target;
	}
	
	// Note: handle_job only worries about processing the job, it does NOT remove finished jobs anymore, the responsibility is on the caller to remove the job when handle_job returns true, this is to allow calling get_job_*() methods after handle_job has been called
	function handle_job($job_id, $job_data, $app_config, $task_list, $extra_data = null)
	{
		$job_user = $job_data['user'];
		$task_count = count($task_list);
		$done_count = 0;
		$skip_count = 0;
		$task_list_result = array();
		
		wp_set_current_user($job_user);
		
		/* This block does all of the filesystem magic:
		 * - determines web paths based on FTP paths
		 * - initializes the WP_Filesystem mechanism in case this host doesn't support direct file access
		 *   (this might not be 100% reliable right now due to NG core not making use of WP_Filesystem)
		 */
		// $ftp_path is assumed to be WP_CONTENT_DIR as accessed through the FTP mount point
		$ftp_path = rtrim($app_config['ftp_path'], '/\\');
		$full_path = rtrim($app_config['full_path'], '/\\');
		$root_path = rtrim(WP_CONTENT_DIR, '/\\');

		$creds = true; // WP_Filesystem(true) requests direct filesystem access
		$fs_sep = DIRECTORY_SEPARATOR;
		$wp_fs = null;

		require_once(ABSPATH . 'wp-admin/includes/file.php');
	
		if (get_filesystem_method() !== 'direct')
		{
			$fs_sep = '/';
			$ftp_method = isset($app_config['ftp_method']) ? $app_config['ftp_method'] : 'ftp';
			 
			$creds = array(
				'connection_type' => $ftp_method == 'sftp' ? 'ssh' : 'ftp',
				'hostname' => $app_config['ftp_host'],
				'port' => $app_config['ftp_port'],
				'username' => $app_config['ftp_user'],
				'password' => $app_config['ftp_pass'],
			);
		}
	
		if (WP_Filesystem($creds))
		{
			$wp_fs = $GLOBALS['wp_filesystem'];
		
			$path_prefix = $full_path;
		
			if ($wp_fs->method === 'direct')
			{
			    if (trim($ftp_path, " \t\n\r\x0B\\") == '') {
                    // Note: if ftp_path is empty, we assume the FTP account home dir is on wp-content
			        $path_prefix = $root_path . $full_path;
                }
                else
                    $path_prefix = str_replace($ftp_path, $root_path, $full_path);
			}
		}
		else {
			include_once(ABSPATH."wp-admin/includes/class-wp-filesystem-direct.php");
			if (!$wp_fs) $wp_fs = new WP_Filesystem_Direct($creds);
		}
		
		foreach ($task_list as &$task_item)
		{
			$task_id = isset($task_item['id']) ? $task_item['id'] : null;
			$task_name = isset($task_item['name']) ? $task_item['name'] : null;
			$task_type = isset($task_item['type']) ? $task_item['type'] : null;
			$task_auth = isset($task_item['auth']) ? $task_item['auth'] : null;
			$task_query = isset($task_item['query']) ? $task_item['query'] : null;
			$task_object = isset($task_item['object']) ? $task_item['object'] : null;
			$task_status = isset($task_item['status']) ? $task_item['status'] : null;
			$task_result = isset($task_item['result']) ? $task_item['result'] : null;
				
			// make sure we don't repeat execution of already finished tasks
			if ($task_status == 'done')
			{
				$done_count++;
				
				// for previously finished tasks, store the result as it may be needed by future tasks
				if ($task_id != null && $task_result != null)
				{
					$task_list_result[$task_id] = $task_result;
				}
				
				continue;
			}
			
			// make sure only valid and authorized tasks are executed
			if ($task_status == 'error' || $task_auth != 'allow')
			{
				$skip_count++;
				
				continue;
			}
			
			// the task query ID can be a simple (integer) ID or more complex ID that gets converted to a simple ID, for instance to point to an object that is the result of a previously finished task
			if (isset($task_query['id']))
			{
				$task_query['id'] = $this->get_object_id($task_query['id'], $task_list_result);
			}
			
			$task_error = null;
			
			switch ($task_type)
			{
				case 'gallery_add':
				{
					$mapper = C_Gallery_Mapper::get_instance();
					$gallery = null;
					$gal_errors = '';
					
					if (isset($task_query['id']))
					{
						$gallery = $mapper->find($task_query['id'], true);
					}
					
					if ($gallery == null)
					{
						$title = isset($task_object['title']) ? $task_object['title'] : '';
						$gallery = $mapper->create(array('title'	=> $title));
						
						if (!$gallery || !$gallery->save())
						{
							if ($gallery != null)
							{
								$gal_errors = $gallery->get_errors();
								
								if ($gal_errors != null)
									$gal_errors = ' [' . json_encode($gal_errors) . ']';
							}
							
							$gallery = null;
						}
					}
				
					if ($gallery != null)
					{
						$task_status = 'done';
						$task_result['object_id'] = $gallery->id();
					}
					else
					{
						$task_status = 'error';
						$task_error = array('level' => 'fatal', 'message' => sprintf(__('Gallery creation failed for "%1$s"%2$s.', 'nggallery'), $title, $gal_errors));
					}
					
					break;
				}
				case 'gallery_remove':
				case 'gallery_edit':
				{
					if (isset($task_query['id']))
					{
						$mapper = C_Gallery_Mapper::get_instance();
						$gallery = $mapper->find($task_query['id'], true);
						$error = null;
					
						if ($gallery != null)
						{
							if ($task_type == 'gallery_remove')
							{
								/**
								 * @var $mapper Mixin_Gallery_Mapper
								 */
								if (!$mapper->destroy($gallery, true))
								{
									$error = __('Failed to remove gallery (%1$s).', 'nggallery');
								}
							}
							else if ($task_type == 'gallery_edit')
							{
								if (isset($task_object['name']))
									$gallery->name = $task_object['name'];
									
								if (isset($task_object['title']))
									$gallery->title = $task_object['title'];
									
								if (isset($task_object['description']))
									$gallery->galdesc = $task_object['description'];
									
								if (isset($task_object['preview_image']))
									$gallery->previewpic = $task_object['preview_image'];
									
								if (isset($task_object['property_list']))
								{
									$properties = $task_object['property_list'];
									
									foreach ($properties as $key => $value) {
										$gallery->$key = $value;
									}
								}
								
								// this is used to determine whether the task is complete
								$image_list_unfinished = false;
								
								if (isset($task_object['image_list']) && $wp_fs != null)
								{
									$storage_path = isset($task_object['storage_path']) ? $task_object['storage_path'] : null;
									$storage_path = trim($storage_path, '/\\');
									
									$storage = C_Gallery_Storage::get_instance();
									$image_mapper = C_Image_Mapper::get_instance();
									$creds = true;
								
									$images_folder = $path_prefix . $fs_sep . $storage_path . $fs_sep;
									$images_folder = str_replace(array('\\', '/'), $fs_sep, $images_folder);
									
									$images = $task_object['image_list'];
									$result_images = isset($task_result['image_list']) ? $task_result['image_list'] : array();
									$images_todo = array_values($this->_array_filter_by_entry($images, $result_images, 'localId'));
									$image_count = count($images);
									$result_image_count = count($result_images);
									
									foreach ($images_todo as $image_index => $image) {
										$image_id = isset($image['id']) ? $image['id'] : null;
										$image_filename = isset($image['filename']) ? $image['filename'] : null;
										$image_path = isset($image['path']) ? $image['path'] : null;
										$image_data_key = isset($image['data_key']) ? $image['data_key'] : null;
										$image_action = isset($image['action']) ? $image['action'] : null;
										$image_status = isset($image['status']) ? $image['status'] : 'skip';

										if ($image_filename == null)
											$image_filename = basename($image_path);
										
										$ngg_image = $image_mapper->find($image_id, TRUE);
										// ensure that we don't transpose the image from one gallery to another in case a remoteId is passed in for the image but the gallery associated to the collection cannot be found
										if ($ngg_image && $ngg_image->galleryid != $gallery->id()) 
										{
											$ngg_image = null;
											$image_id = null;
										}
									
										$image_error = null;
										
										if ($image_action == "delete")
										{
											// image was deleted
											if ($ngg_image != null)
											{
												$settings = C_NextGen_Settings::get_instance();
												$delete_fine = true;

												if ($settings->deleteImg)
												{
													if (!$storage->delete_image($ngg_image))
														$image_error = __('Could not delete image file(s) from disk (%1$s).', 'nggallery');
												}
												else if (!$image_mapper->destroy($ngg_image)) 
												{
													$image_error = __('Could not remove image from gallery (%1$s).', 'nggallery');
												}
												
												if ($image_error == null)
												{
													do_action('ngg_delete_picture', $ngg_image->{$ngg_image->id_field}, $ngg_image);
													
													$image_status = 'done';
												}
											}
											else
											{
												$image_error = __('Could not remove image because image was not found (%1$s).', 'nggallery');
											}
										}
										else
										{
											/* image was added or edited and needs updating */
											$image_data = null;

											if ($image_data_key != null) {
												if (!isset($extra_data['__queuedImages'][$image_data_key])) {
													if (isset($extra_data[$image_data_key])) {
														$image_data_arr = $extra_data[$image_data_key];
														
														$image_data = file_get_contents($image_data_arr['tmp_name']);
													}
													
													if ($image_data == null)
														$image_error = __('Could not obtain data for image (%1$s).', 'nggallery');
												}
												else
													$image_status = 'queued';
											}
											else {
												$image_path = $images_folder . $image_path;

												if ($image_path != null && $wp_fs->exists($image_path)) {
													$image_data = $wp_fs->get_contents($image_path);
												}
												else {
													if (is_multisite())
														$image_error = __('Could not find image file for image (%1$s). Using FTP Upload Method in Multisite is not recommended.', 'nggallery');
													else
														$image_error = __('Could not find image file for image (%1$s).', 'nggallery');
												}
											
												// delete temporary image
												$wp_fs->delete($image_path);
											}

											if ($image_data != null)
											{
												try
												{
													$ngg_image = $storage->upload_base64_image($gallery, $image_data, $image_filename, $image_id, true);
													$image_mapper->reimport_metadata($ngg_image);
									
													if ($ngg_image != null)
													{
														$image_status = 'done';
														$image_id = is_int($ngg_image) ? $ngg_image : $ngg_image->{$ngg_image->id_field};
													}
												}
												catch (E_NoSpaceAvailableException $e) {
													$image_error = __('No space available for image (%1$s).', 'nggallery');
												}
												catch (E_UploadException $e) {
													$image_error = $e->getMessage . __(' (%1$s).', 'nggallery');						
												}
												catch (E_No_Image_Library_Exception $e) {
													$image_error = __('No image library present, image uploads will fail (%1$s).', 'nggallery');
									
													// no point in continuing if the image library is not present but we don't break here to ensure that all images are processed (otherwise they'd be processed in further fruitless handle_job calls)
												}
												catch (E_InsufficientWriteAccessException $e) {
													$image_error = __('Inadequate system permissions to write image (%1$s).', 'nggallery');
												}
												catch (E_InvalidEntityException $e) {
													$image_error = __('Requested image with id (%2$s) doesn\'t exist (%1$s).', 'nggallery');
												}
												catch (E_EntityNotFoundException $e) {
													// gallery doesn't exist - already checked above so this should never happen											
												}
											}
										}
									
										if ($image_error != null)
										{
											$image_status = 'error';
										
											$image['error'] = array('level' => 'fatal', 'message' => sprintf($image_error, $image_filename, $image_id));
										}
									
										if ($image_id)
										{
											$image['id'] = $image_id;
										}
									
										if ($image_status)
										{
											$image['status'] = $image_status;
										}
										
										if ($image_status != 'queued') {
											// append processed image to result image_list array
											$result_images[] = $image;
										}
							
										if ($this->should_stop_execution())
											break;
									}
								
									$task_result['image_list'] = $result_images;
									$image_list_unfinished = count($result_images) < $image_count;
									
									// if images have finished processing, remove the folder used to store the temporary images (the folder should be empty due to delete() calls above)
									if (!$image_list_unfinished && $storage_path != null && $storage_path != $fs_sep && $path_prefix != null && $path_prefix != $fs_sep)
										$wp_fs->rmdir($images_folder);
								}
								else if ($wp_fs == null)
								{
									$error = __('Could not access file system for gallery (%1$s).', 'nggallery');
								}

								if (!$gallery->save())
								{
									if ($error == null)
									{
										$gal_errors = '[' . json_encode($gallery->get_errors()) . ']';
										$error = __('Failed to save modified gallery (%1$s). ' . $gal_errors, 'nggallery');
									}
								}
							}
						}
						else
						{
							$error = __('Could not find gallery (%1$s).', 'nggallery');
						}
						
						// XXX workaround for $gallery->save() returning false even if successful
						if (isset($task_result['image_list']) && $gallery != null)
							$task_result['object_id'] = $gallery->id();
							
						if ($error == null)
						{
							$task_status = 'done';
							$task_result['object_id'] = $gallery->id();
						}
						else
						{
							$task_status = 'error';
							$task_error = array('level' => 'fatal', 'message' => sprintf($error, (string)$task_query['id']));
						}
					
						if ($image_list_unfinished)
						{
							// we override the status of the task when the image list has not finished processing
							$task_status = 'unfinished';
						}
					}
					else
					{
						$task_status = 'error';
						$task_error = array('level' => 'fatal', 'message' => __('No gallery was specified to edit.', 'nggallery'));
					}
				
					break;
				}
				case 'album_add':
				{
					$mapper = C_Album_Mapper::get_instance();
					
					$name = isset($task_object['name']) ? $task_object['name'] : '';
					$desc = isset($task_object['description']) ? $task_object['description'] : '';
					$previewpic = isset($task_object['preview_image']) ? $task_object['preview_image'] : 0;
					$sortorder = isset($task_object['sort_order']) ? $task_object['sort_order'] : '';
					$page_id = isset($task_object['page_id']) ? $task_object['page_id'] : 0;
					
					$album = null;
					
					if (isset($task_query['id']))
					{
						$album = $mapper->find($task_query['id'], true);
					}
					
					if ($album == null)
					{
						$album = $mapper->create(array(
							'name'			 =>	$name,
							'previewpic' =>	$previewpic,
							'albumdesc'	=>	$desc,
							'sortorder'	=>	$sortorder,
							'pageid'		 =>	$page_id
						));
						
						if (!$album || !$album->save())
						{
							$album = null;
						}
					}
				
					if ($album != null)
					{
						$task_status = 'done';
						$task_result['object_id'] = $album->id();
					}
					else
					{
						$task_status = 'error';
						$task_error = array('level' => 'fatal', 'message' => __('Album creation failed.', 'nggallery'));
					}
					
					break;
				}
				case 'album_remove':
				case 'album_edit':
				{
					if (isset($task_query['id']))
					{
						$mapper = C_Album_Mapper::get_instance();
						$album = $mapper->find($task_query['id'], true);
						$error = null;
					
						if ($album)
						{
							if ($task_type == 'album_remove')
							{
								if (!$album->destroy()) 
								{
									$error = __('Failed to remove album (%1$s).', 'nggallery');
								}
							}
							else if ($task_type == 'album_edit')
							{
								if (isset($task_object['name']))
									$album->name = $task_object['name'];
									
								if (isset($task_object['description']))
									$album->albumdesc = $task_object['description'];
									
								if (isset($task_object['preview_image']))
									$album->previewpic = $task_object['preview_image'];
									
								if (isset($task_object['property_list']))
								{
									$properties = $task_object['property_list'];
									
									foreach ($properties as $key => $value) {
										$album->$key = $value;
									}
								}
									
								if (isset($task_object['item_list']))
								{
									$item_list = $task_object['item_list'];
									$sortorder = $album->sortorder;
									$count = count($sortorder);
									$album_items = array();
									
									for ($index = 0; $index < $count; $index++) {
										$album_items[$sortorder[$index]] = $index;
									}

									foreach ($item_list as $item_info) {
										$item_id = isset($item_info['id']) ? $item_info['id'] : null;
										$item_type = isset($item_info['type']) ? $item_info['type'] : null;
										$item_index = isset($item_info['index']) ? $item_info['index'] : null;
										// translate ID in case this gallery has been created as part of this job
										$item_id = $this->get_object_id($item_id, $task_list_result);
										
										if ($item_id != null) {
											if ($item_type == 'album')
												$item_id = 'a' . $item_id;
											
											$album_items[$item_id] = $count + $item_index;
										}
									}
									
									asort($album_items);
									
									$album->sortorder = array_keys($album_items);
								}

								if (!$mapper->save($album))
								{
									$error = __('Failed to save modified album (%1$s).', 'nggallery');
								}
							}
						}
						else
						{
							$error = __('Could not find album (%1$s).', 'nggallery');
						}
						
						if ($error == null)
						{
							$task_status = 'done';
							$task_result['object_id'] = $album->id();
						}
						else
						{
							$task_status = 'error';
							$task_error = array('level' => 'fatal', 'message' => sprintf($error, (string)$task_query['id']));
						}
					}
					else
					{
						$task_status = 'error';
						$task_error = array('level' => 'fatal', 'message' => __('No album was specified to edit.', 'nggallery'));
					}
					
					break;
				}
				case 'gallery_list_get':
				{
					$mapper = C_Gallery_Mapper::get_instance();
					$gallery_list = $mapper->find_all();
					$result_list = array();
					
					foreach ($gallery_list as $gallery)
					{
						$gallery_result = array(
							'id' => $gallery->id(),
							'name' => $gallery->name,
							'title' => $gallery->title,
							'description' => $gallery->galdesc,
							'preview_image' => $gallery->previewpic,
						);
						
						$result_list[] = $gallery_result;
					}
					
					$task_status = 'done';
					$task_result['gallery_list'] = $result_list;
					
					break;
				}
				case 'image_list_move':
				{
					break;
				}
			}
			
			$task_item['result'] = $task_result;
			$task_item['status'] = $task_status;
			$task_item['error'] = $task_error;
		
			// for previously finished tasks, store the result as it may be needed by future tasks
			if ($task_id != null && $task_result != null)
			{
				$task_list_result[$task_id] = $task_result;
			}
			
			// if the task has finished, either successfully or unsuccessfully, increase count for done tasks
			if ($task_status != 'unfinished')
				$done_count++;
		
			if ($this->should_stop_execution())
				break;
		}
		
		$this->set_job_task_list($job_id, $task_list);
		
		if ($task_count > $done_count + $skip_count)
		{
			// unfinished tasks, return false
			
			return false;
		}
		else
		{
			$upload_method = isset($app_config['upload_method']) ? $app_config['upload_method'] : 'ftp';
			
			if ($upload_method == 'ftp') {
				// everything was finished, write status file
				$status_file = '_ngg_job_status_' . strval($job_id) . '.txt';
				$status_content = json_encode($task_list);
				
				if ($wp_fs != null)
				{
					$status_path = $path_prefix . $fs_sep . $status_file;
					$status_path = str_replace(array('\\', '/'), $fs_sep, $status_path);
					$wp_fs->put_contents($status_path, $status_content);
				}
				else
				{
					// if WP_Filesystem failed try one last desperate attempt at direct file writing
					$status_path = str_replace($ftp_path, $root_path, $full_path) . DIRECTORY_SEPARATOR . $status_file;
					$status_path = str_replace(array('\\', '/'), DIRECTORY_SEPARATOR, $status_path);
					file_put_contents($status_path, $status_content);
				}
			}
			
			return true;
		}
	}
}

