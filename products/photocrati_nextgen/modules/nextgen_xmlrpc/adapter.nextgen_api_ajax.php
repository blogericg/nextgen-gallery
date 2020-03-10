<?php

/**
 * Provides AJAX actions for JSON API interface
 * @mixin C_Ajax_Controller
 * @adapts I_Ajax_Controller
 */
class A_NextGen_API_Ajax extends Mixin
{
	var $nextgen_api = NULL;
	var $_nextgen_api_locked = false;
	var $_shutdown_registered = false;
	var $_error_handler_registered = false;
	var $_error_handler_old = null;

	function get_nextgen_api()
	{
		if (is_null($this->nextgen_api))
			$this->nextgen_api = C_NextGen_API::get_instance();
			
		return $this->nextgen_api;
	}
	
	function _authenticate_user($regenerate_token = false)
	{
		$api = $this->get_nextgen_api();
		$username = $this->object->param('q');
		$password = $this->object->param('z');
		$token = $this->object->param('tok');
		
		return $api->authenticate_user($username, $password, $token, $regenerate_token);
	}
	
	function get_nextgen_api_token_action()
	{
		$regen = $this->object->param('regenerate_token') ? true : false;
		$user_obj = $this->_authenticate_user($regen);
		$response = array();

		if ($user_obj != null) {
			$response['result'] = 'ok';
			$response['result_object'] = array(
				'token' => get_user_meta($user_obj->ID, 'nextgen_api_token', true),
			);
		}
		else {
			$response['result'] = 'error';
			$response['error'] = array('code' => C_NextGen_API::ERR_NOT_AUTHENTICATED, 'message' => __('Authentication Failed.', 'nggallery'));
		}
		
		return $response;
	}
	
	function get_nextgen_api_path_list_action()
	{
		$api = $this->get_nextgen_api();
		$app_config = $this->object->param('app_config');
		$user_obj = $this->_authenticate_user();
		$response = array();

		if ($user_obj != null && !is_a($user_obj, 'WP_Error'))
		{
			wp_set_current_user($user_obj->ID);
			
			$ftp_method = isset($app_config['ftp_method']) ? $app_config['ftp_method'] : 'ftp';
			$creds = array(
				'connection_type' => $ftp_method == 'sftp' ? 'ssh' : 'ftp',
				'hostname' => $app_config['ftp_host'],
				'port' => $app_config['ftp_port'],
				'username' => $app_config['ftp_user'],
				'password' => $app_config['ftp_pass'],
			);

			require_once(ABSPATH . 'wp-admin/includes/file.php');
			
			$wp_filesystem = $api->create_filesystem_access($creds);
			$root_path = null;
			$base_path = null;
			$plugin_path = null;
			
			if ($wp_filesystem)
			{
				$root_path = $wp_filesystem->wp_content_dir();
				$base_path = $wp_filesystem->abspath();
				$plugin_path = $wp_filesystem->wp_plugins_dir();
			}
			else
			{
				// fallbacks when unable to connect, try to see if we know the path already
				$root_path = get_option('ngg_ftp_root_path');
				
				if (defined('FTP_BASE'))
					$base_path = FTP_BASE;
					
				if ($root_path == null && defined('FTP_CONTENT_DIR'))
					$root_path = FTP_CONTENT_DIR;
					
				if (defined('FTP_PLUGIN_DIR'))
					$plugin_path = FTP_PLUGIN_DIR;
					
				if ($base_path == null && $root_path != null)
					$base_path = dirname($root_path);
				
				if ($root_path == null && $base_path != null)
					$root_path = rtrim($base_path, '/\\') . '/wp-content/';
					
				if ($plugin_path == null && $base_path != null)
					$plugin_path = rtrim($base_path, '/\\') . '/wp-content/plugins/';
			}
	
			if ($root_path != NULL)
			{
				$response['result'] = 'ok';
				$response['result_object'] = array(
					'root_path' => $root_path,
					'wp_content_path' => $root_path,
					'wp_base_path' => $base_path,
					'wp_plugin_path' => $plugin_path,
				);
			}
			else
			{
				if ($wp_filesystem != null)
				{
					$response['result'] = 'error';
					$response['error'] = array('code' => C_NextGen_API::ERR_FTP_NO_PATH, 'message' => __('Could not determine FTP path.', 'nggallery'));
				}
				else
				{
					$response['result'] = 'error';
					$response['error'] = array('code' => C_NextGen_API::ERR_FTP_NOT_CONNECTED, 'message' => __('Could not connect to FTP to determine path.', 'nggallery'));
				}
			}
		}
		else 
		{
			$response['result'] = 'error';
			$response['error'] = array('code' => C_NextGen_API::ERR_NOT_AUTHENTICATED, 'message' => __('Authentication Failed.', 'nggallery'));
		}
		
		return $response;
	}

	function _get_max_upload_size() 
	{
	  static $max_size = -1;

	  if ($max_size < 0) {
	    $post_max_size = $this->_parse_size(ini_get('post_max_size'));
	    if ($post_max_size > 0) {
	      $max_size = $post_max_size;
	    }

	    $upload_max = $this->_parse_size(ini_get('upload_max_filesize'));
	    if ($upload_max > 0 && $upload_max < $max_size) {
	      $max_size = $upload_max;
	    }
	  }
	  return $max_size;
	}

	function _parse_size($size) 
	{
	  $unit = preg_replace('/[^bkmgtpezy]/i', '', $size); 
	  $size = preg_replace('/[^0-9\.]/', '', $size);
	  if ($unit) {
	    return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
	  }
	  else {
	    return round($size);
	  }
	}
	
	function _get_max_upload_files() 
	{
		return intval(ini_get('max_file_uploads'));
	}

	function enqueue_nextgen_api_task_list_action()
	{
		$api = $this->get_nextgen_api();
		$user_obj = $this->_authenticate_user();
		$response = array();

		if ($user_obj != null && !is_a($user_obj, 'WP_Error'))
		{
			wp_set_current_user($user_obj->ID);
			$app_config = $this->object->param('app_config');
			$task_list = $this->object->param('task_list');
			$extra_data = $this->object->param('extra_data');
			
			if (is_string($app_config))
				$app_config = json_decode($app_config, true);
			
			if (is_string($task_list))
				$task_list = json_decode($task_list, true);
			
			if (is_string($extra_data))
				$extra_data = json_decode($extra_data, true);
			
			foreach ($_FILES as $key => $file) {
				if (substr($key, 0, strlen('file_data_')) == 'file_data_')
					$extra_data[substr($key, strlen('file_data_'))] = $file;
			}
			
			if ($task_list != null)
			{
				$task_count = count($task_list);
				$auth_count = 0;
				
				foreach ($task_list as &$task_item)
				{
					$task_id = isset($task_item['id']) ? $task_item['id'] : null;
					$task_name = isset($task_item['name']) ? $task_item['name'] : null;
					$task_type = isset($task_item['type']) ? $task_item['type'] : null;
					$task_query = isset($task_item['query']) ? $task_item['query'] : null;
					
					$type_parts = explode('_', $task_name);
					$type_context = array_pop($type_parts);
					$type_action = array_pop($type_parts);
					
					$task_auth = false;
					
					switch ($task_type)
					{
						case 'gallery_add':
						{
							$task_auth = M_Security::is_allowed('nextgen_edit_gallery');
							
							break;
						}
						case 'gallery_remove':
						case 'gallery_edit':
						{
							$query_id = $api->get_query_id($task_query['id'], $task_list);
							$gallery = null;
							
							// The old NextGEN XMLRPC API had this logic so replicating it here for safety
							if ($query_id) {
								$gallery_mapper = C_Gallery_Mapper::get_instance();
								$gallery = $gallery_mapper->find($query_id);
							}
							
							if ($gallery != null) {
								
								$task_auth = (wp_get_current_user()->ID == $gallery->author || M_Security::is_allowed('nextgen_edit_gallery_unowned'));
							}
							else {
								$task_auth = M_Security::is_allowed('nextgen_edit_gallery');
							}
							
							break;
						}
						case 'album_add':
						{
							$task_auth = M_Security::is_allowed('nextgen_edit_album');
							
							break;
						}
						case 'album_remove':
						{
							$task_auth = M_Security::is_allowed('nextgen_edit_album');
							
							break;
						}
						case 'album_edit':
						{
							$task_auth = M_Security::is_allowed('nextgen_edit_album');
							
							break;
						}
						case 'image_list_move':
						{
							break;
						}
					}
					
					if ($task_auth)
					{
						$auth_count++;
					}
					
					$task_item['auth'] = $task_auth ? 'allow' : 'forbid';
				}
				
				if ($task_count == $auth_count)
				{
					$job_id = $api->add_job(array('user' => $user_obj->ID, 'clientid' => $this->object->param('clientid')), $app_config, $task_list);
					
					if ($job_id != null)
					{
						$post_back = $api->get_job_post_back($job_id);
						$handler_delay = defined('NGG_API_JOB_HANDLER_DELAY') ? intval(NGG_API_JOB_HANDLER_DELAY) : 0;
						$handler_delay = $handler_delay > 0 ? $handler_delay : 30; /* in seconds */
						$handler_maxsize = defined('NGG_API_JOB_HANDLER_MAXSIZE') ? intval(NGG_API_JOB_HANDLER_MAXSIZE) : 0;
						$handler_maxsize = $handler_maxsize > 0 ? $handler_maxsize : $this->_get_max_upload_size(); /* in bytes */
						$handler_maxfiles = $this->_get_max_upload_files();
						
						$response['result'] = 'ok';
						$response['result_object'] = array('job_id' => $job_id, 'job_post_back' => $post_back, 'job_handler_url' => home_url('?photocrati_ajax=1&action=execute_nextgen_api_task_list'), 'job_handler_delay' => $handler_delay, 'job_handler_maxsize' => $handler_maxsize, 'job_handler_maxfiles' => $handler_maxfiles);
						
						if (!defined('NGG_API_SUPPRESS_QUICK_EXECUTE') || NGG_API_SUPPRESS_QUICK_EXECUTE == false) {
							if (!$api->is_execution_locked()) {
								$this->_start_locked_execute();
								
								try {
									$result = $api->handle_job($job_id, $api->get_job_data($job_id), $app_config, $api->get_job_task_list($job_id), $extra_data);
				
									$response['result_object']['job_result'] = $api->get_job_task_list($job_id);
									
									if ($result) {
										// everything was finished, remove job
										$api->remove_job($job_id);
									}
								}
								catch (Exception $e) {
								}

								$this->_stop_locked_execute();
							}
						}
					}
					else
					{
						$response['result'] = 'error';
						$response['error'] = array('code' => C_NextGen_API::ERR_JOB_NOT_ADDED, 'message' => __('Job could not be added.', 'nggallery'));
					}
				}
				else
				{
					$response['result'] = 'error';
					$response['error'] = array('code' => C_NextGen_API::ERR_NOT_AUTHORIZED, 'message' => __('Authorization Failed.', 'nggallery'));
				}
			}
			else
			{
				$response['result'] = 'error';
				$response['error'] = array('code' => C_NextGen_API::ERR_NO_TASK_LIST, 'message' => __('No task list was specified.', 'nggallery'));
			}
		}
		else 
		{
			$response['result'] = 'error';
			$response['error'] = array('code' => C_NextGen_API::ERR_NOT_AUTHENTICATED, 'message' => __('Authentication Failed.', 'nggallery'));
		}

		return $response;
	}
	
	function _do_shutdown()
	{
		if ($this->_nextgen_api_locked)
			$this->get_nextgen_api()->set_execution_locked(false);
	}

	function _error_handler($errno, $errstr, $errfile, $errline ) 
	{
		return false;
	}
	
	function _start_locked_execute()
	{
		$api = $this->get_nextgen_api();
		
		if (!$this->_shutdown_registered) {
			register_shutdown_function(array($this, '_do_shutdown'));
			$this->_shutdown_registered = true;
		}

		if (!$this->_error_handler_registered) {
			//$this->_error_handler_old = set_error_handler(array($this, '_error_handler'));
			$this->_error_handler_registered = true;
		}
	
		$api->set_execution_locked(true);
		$this->_nextgen_api_locked = true;
	}
	
	function _stop_locked_execute()
	{
		$api = $this->get_nextgen_api();
		
		$api->set_execution_locked(false);
		$this->_nextgen_api_locked = false;

		if ($this->_error_handler_registered) {
			//set_error_handler($this->_error_handler_old);
			$this->_error_handler_registered = false;
		}
	}
	
	function execute_nextgen_api_task_list_action()
	{
		$api = $this->get_nextgen_api();
		$job_list = $api->get_job_list();
		$response = array();
		
		if ($api->is_execution_locked())
		{
			$response['result'] = 'ok';
			$response['info'] = array('code' => C_NextGen_API::INFO_EXECUTION_LOCKED, 'message' => __('Job execution is locked.', 'nggallery'));
		}
		else if ($job_list != null)
		{
			$this->_start_locked_execute();
			
			try {
				$extra_data = $this->object->param('extra_data');
				$job_count = count($job_list);
				$done_count = 0;
				$client_result = array();
				
				if (is_string($extra_data))
					$extra_data = json_decode($extra_data, true);
				
				foreach ($_FILES as $key => $file) {
					if (substr($key, 0, strlen('file_data_')) == 'file_data_')
						$extra_data[substr($key, strlen('file_data_'))] = $file;
				}
				
				foreach ($job_list as $job)
				{
					$job_id = $job['id'];
					$job_data = $job['data'];
					$result = $api->handle_job($job_id, $job_data, $job['app_config'], $job['task_list'], $extra_data);
				
					if (isset($job_data['clientid']) && $job_data['clientid'] == $this->object->param('clientid'))
						$client_result[$job_id] = $api->get_job_task_list($job_id);
						
					if ($result) {
						$done_count++;
						
						// everything was finished, remove job
						$api->remove_job($job_id);
					}
						
					if ($api->should_stop_execution())
						break;
				}
			}
			catch (Exception $e) {
 			}
			
			$this->_stop_locked_execute();
			
			if ($done_count == $job_count)
			{
				$response['result'] = 'ok';
				$response['info'] = array('code' => C_NextGen_API::INFO_JOB_LIST_FINISHED, 'message' => __('Job list is finished.', 'nggallery'));
			}
			else
			{
				$response['result'] = 'ok';
				$response['info'] = array('code' => C_NextGen_API::INFO_JOB_LIST_UNFINISHED, 'message' => __('Job list is unfinished.', 'nggallery'));
			}
			
			if (!defined('NGG_API_SUPPRESS_QUICK_SUMMARY') || NGG_API_SUPPRESS_QUICK_SUMMARY == false)
				$response['result_object'] = $client_result;
		}
		else
		{
			$response['result'] = 'ok';
			$response['info'] = array('code' => C_NextGen_API::INFO_NO_JOB_LIST, 'message' => __('Job list is empty.', 'nggallery'));
		}
		
		return $response;
	}
}
