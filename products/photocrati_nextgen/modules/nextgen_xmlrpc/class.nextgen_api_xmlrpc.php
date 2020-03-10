<?php

/**
 * Class C_NextGen_API_XMLRPC
 * @implements I_NextGen_API_XMLRPC
 */
class C_NextGen_API_XMLRPC extends C_Component
{
	public static $_instances = array();
	
	function define($context = false)
	{
		parent::define($context);
		$this->implement('I_NextGen_API_XMLRPC');
	}

    /**
     * @param bool|string $context
     * @return C_NextGen_API_XMLRPC
     */
	public static function get_instance($context = false)
	{
		if (!isset(self::$_instances[$context]))
			self::$_instances[$context] = new C_NextGen_API_XMLRPC($context);
		return self::$_instances[$context];
	}
  
	/**
	 * Gets the version of NextGEN Gallery installed
	 * @return array
	 */
	function get_version()
	{
		return array('version' => NGG_PLUGIN_VERSION);
	}

	/**
	 * Login a user
	 * @param $username
	 * @param $password
	 * @return bool|WP_Error|WP_User
	 */
	function _login($username, $password, $blog_id=1)
	{
		$retval = FALSE;

		if (!is_a(($user_obj = wp_authenticate($username, $password)), 'WP_Error')) {
			wp_set_current_user($user_obj->ID);
			$retval = $user_obj;

			if (is_multisite()) switch_to_blog($blog_id);
		}

		return $retval;
	}

	function _can_manage_gallery($gallery_id_or_obj, $check_upload_capability=FALSE)
	{
		$retval = FALSE;

		// Get the gallery object, if we don't have it already
		$gallery = NULL;
		if (is_int($gallery_id_or_obj)) {
			$gallery_mapper = C_Gallery_Mapper::get_instance();
			$gallery = $gallery_mapper->find($gallery_id_or_obj);
		}
		else $gallery = $gallery_id_or_obj;

		if ($gallery) {
			$security = $this->get_registry()->get_utility('I_Security_Manager');
			$actor	  = $security->get_current_actor();
			if ($actor->get_entity_id() == $gallery->author) 			$retval = TRUE;
			elseif ($actor->is_allowed('nextgen_edit_gallery_unowned')) $retval = TRUE;

			// Optionally, check if the user can upload to this gallery
			if ($retval && $check_upload_capability) {
				$retval = $actor->is_allowed('nextgen_upload_image');
			}
		}

		return $retval;
	}

	function _add_gallery_properties($gallery)
	{
		if (is_object($gallery)) {

			$image_mapper	= C_Image_Mapper::get_instance();
			$storage		= C_Gallery_Storage::get_instance();

			// Vladimir's Lightroom plugins requires the 'id' to be a string
			// Ask if he can accept integers as well. Currently, integers break
			// his plugin
			$gallery->gid = (string) $gallery->gid;

			// Set other gallery properties
            $tmp = $image_mapper->select('DISTINCT COUNT(*) as counter')->where(array("galleryid = %d", $gallery->gid))->run_query(FALSE, FALSE, TRUE);
			$image_counter = array_pop($tmp);
			$gallery->counter = $image_counter->counter;
			$gallery->abspath = $storage->get_gallery_abspath($gallery);
		}
		else return FALSE;

		return TRUE;
	}

	/**
	 * Returns a single image object
	 * @param array $args (blog_id, username, password, pid)
     * @param bool $return_model (optional)
     * @return object|IXR_Error
	 */
	function get_image($args, $return_model=FALSE)
	{
		$retval		= new IXR_Error(403, 'Invalid username or password');
		$blog_id	= intval($args[0]);
		$username	= strval($args[1]);
		$password   = strval($args[2]);
		$image_id	= intval($args[3]);

		// Authenticate the user
		if ($this->_login($username, $password, $blog_id)) {

			// Try to find the image
			$image_mapper = C_Image_Mapper::get_instance();
			if (($image = $image_mapper->find($image_id, TRUE))) {

				// Try to find the gallery that the image belongs to
				$gallery_mapper = C_Gallery_Mapper::get_instance();
				if (($gallery = $gallery_mapper->find($image->galleryid))) {

					// Does the user have sufficient capabilities?
					if ($this->_can_manage_gallery($gallery)) {
						$storage = C_Gallery_Storage::get_instance();
						$image->imageURL	= $storage->get_image_url($image,'full', TRUE);
						$image->thumbURL	= $storage->get_thumb_url($image, TRUE);
						$image->imagePath	= $storage->get_image_abspath($image);
						$image->thumbPath	= $storage->get_thumb_abspath($image);
						$retval = $return_model ? $image : $image->get_entity();
					}

					else {
						$retval = new IXR_Error(403, "You don't have permission to manage gallery #{$image->galleryid}");
					}
				}
				else {
					// No gallery found
					$retval = new IXR_Error(404, "Gallery not found (with id #{$image->gallerid})");
				}
			}
			else {
				// No image found
				$retval = new IXR_Error(404, "Image not found (with id #{$image_id})");
			}
		}

		return $retval;
	}

	/**
	 * Returns a collection of images
	 * @param array $args (blog_id, username, password, gallery_id
     * @return array|IXR_Error
	 */
	function get_images($args)
	{
		$retval		= new IXR_Error(403, 'Invalid username or password');
		$blog_id	= intval($args[0]);
		$username	= strval($args[1]);
		$password   = strval($args[2]);
		$gallery_id	= intval($args[3]);

		// Authenticate the user
		if ($this->_login($username, $password, $blog_id)) {

			// Try to find the gallery
			$mapper = C_Gallery_Mapper::get_instance();
			if (($gallery = $mapper->find($gallery_id, TRUE))) {

				// Does the user have sufficient capabilities?
				if ($this->_can_manage_gallery($gallery)) {
					$retval = $gallery->get_images();
				}
				else {
					$retval = new IXR_Error(403, "You don't have permission to manage gallery #{$image->galleryid}");
				}
			}

			// No gallery found
			else {
				$retval = new IXR_Error(404, "Gallery not found (with id #{$image->gallerid}");
			}
		}

		return $retval;
	}

	/**
	 * Uploads an image to a particular gallery
	 * @param $args (blog_id, username, password, data)
	 *
	 * Data is an assoc array:
	 *			  o string name
	 *			  o string type (optional)
	 *			  o base64 bits
	 *			  o bool overwrite (optional)
	 *			  o int gallery
	 *			  o int image_id  (optional)
	 * @return object|IXR_Error
	 */
	function upload_image($args)
	{
		$retval		= new IXR_Error(403, 'Invalid username or password');
		$blog_id	= intval($args[0]);
		$username	= strval($args[1]);
		$password   = strval($args[2]);
		$data		= $args[3];
		$gallery_id = isset($data['gallery_id']) ? $data['gallery_id'] : $data['gallery'];
		if (!isset($data['override'])) $data['override'] = FALSE;
		if (!isset($data['overwrite']))$data['overwrite']= FALSE;
		if (!isset($data['image_id'])) $data['image_id']=FALSE;
		$data['override'] = $data['overwrite'];

		// Authenticate the user
		if ($this->_login($username, $password, $blog_id))
		{
			// Try to find the gallery
			$mapper = C_Gallery_Mapper::get_instance();
			if (($gallery = $mapper->find($gallery_id, TRUE)))
			{
				// Does the user have sufficient capabilities?
				if ($this->_can_manage_gallery($gallery, TRUE))
				{
					// Upload the image
					$storage = C_Gallery_Storage::get_instance();
					try {
                        $image = $storage->upload_base64_image(
                            $gallery,
                            $data['bits'],
                            $data['name'],
                            $data['image_id'],
                            $data['override']
                        );
                        if ($image)
                        {
							$image = is_int($image) ? C_Image_Mapper::get_instance()->find($image, TRUE) : $image;
                            $storage          = C_Gallery_Storage::get_instance();
                            $image->imageURL  = $storage->get_image_url($image);
                            $image->thumbURL  = $storage->get_thumb_url($image);
                            $image->imagePath = $storage->get_image_abspath($image);
                            $image->thumbPath = $storage->get_thumb_abspath($image);
                            $retval           = $image->get_entity();
                        } else {
                            $retval = new IXR_Error(500, "Could not upload image");
                        }
                    }
                    catch (Exception $exception) {
					    $retval = new IXR_Error(500, 'Could not upload image: ' . $exception->getMessage());
                    }
				}
				else {
					$retval = new IXR_Error(403, "You don't have permission to upload to gallery #{$gallery_id}");
				}
			}
			else {
                // No gallery found
				$retval = new IXR_Error(404, "Gallery not found (with id #{$gallery_id}");
			}
		}

		return $retval;
	}

	/**
	 * Edits an image object
	 * @param $args (blog_id, username, password, image_id, alttext, description, exclude, other_properties
     * @return IXR_Error|object
	 */
	function edit_image($args)
	{
		$alttext 		= strval($args[4]);
		$description	= strval($args[5]);
		$exclude		= intval($args[6]);
		$properties		= isset($args[7]) ? (array)$args[7] : array();

		$retval = $this->get_image($args, TRUE);
		if (!($retval instanceof IXR_Error)) {
			$retval->alttext 		= $alttext;
			$retval->description 	= $description;
			$retval->exclude		= $exclude;

			// Other properties can be specified using an associative array
			foreach ($properties as $key => $value) {
				$retval->$key = $value;
			}

			// Unset any dynamic properties not part of the schema
			foreach (array('imageURL', 'thumbURL', 'imagePath', 'thumbPath') as $key) {
				unset($retval->$key);
			}

			$retval = $retval->save();
		}

		return $retval;
	}

	/**
	 * Deletes an existing image from a gallery
	 * @param array $args (blog_id, username, password, image_id)
     * @return bool
	 */
	function delete_image($args)
	{
		$retval = $this->get_image($args, TRUE);
		if (!($retval instanceof IXR_Error)) {
			$retval = $retval->destroy();
		}
		return $retval;
	}

	/**
	 * Creates a new gallery
	 * @param array $args (blog_id, username, password, title)
     * @return int|IXR_Error
	 */
	function create_gallery($args)
	{
		$retval		= new IXR_Error(403, 'Invalid username or password');
		$blog_id	= intval($args[0]);
		$username	= strval($args[1]);
		$password   = strval($args[2]);
		$title		= strval($args[3]);

		// Authenticate the user
		if ($this->_login($username, $password, $blog_id)) {

			$security = $this->get_registry()->get_utility('I_Security_Manager');
			if ($security->is_allowed('nextgen_edit_gallery')) {
				$mapper = C_Gallery_Mapper::get_instance();
				if (($gallery = $mapper->create(array('title'	=>	$title))) && $gallery->save()) {
					$retval = $gallery->id();
				}
				else $retval = new IXR_Error(500, "Unable to create gallery");

			}
			else $retval = new IXR_Error(403, "Sorry, but you must be able to manage galleries. Check your roles/capabilities.");
		}

		return $retval;
	}

	/**
	 * Edits an existing gallery
	 * @param array $args (blog_id, username, password, gallery_id, name, title, description, preview_pic_id)
     * @return int|bool|IXR_Error
	 */
	function edit_gallery($args)
	{
		$retval		= new IXR_Error(403, 'Invalid username or password');
		$blog_id	= intval($args[0]);
		$username	= strval($args[1]);
		$password   = strval($args[2]);
		$gallery_id = intval($args[3]);
		$name		= strval($args[4]);
		$title		= strval($args[5]);
		$galdesc	= strval($args[6]);
		$image_id	= intval($args[7]);
		$properties = isset($args[8]) ? (array) $args[8] : array();

		// Authenticate the user
		if ($this->_login($username, $password, $blog_id)) {

			$mapper = C_Gallery_Mapper::get_instance();
			if (($gallery = $mapper->find($gallery_id, TRUE))) {
				if ($this->_can_manage_gallery($gallery)) {
					$gallery->name	= $name;
					$gallery->title = $title;
					$gallery->galdesc = $galdesc;
					$gallery->previewpic = $image_id;
					foreach ($properties as $key => $value) {
						$gallery->$key = $value;
					}

					// Unset dynamic properties not part of the schema
					unset($gallery->counter);
					unset($gallery->abspath);

					$retval = $gallery->save();
				}
				else $retval = new IXR_Error(403, "You don't have permission to modify this gallery");
			}
			else $retval = new IXR_Error(404, "Gallery #{$gallery_id} doesn't exist");
		}

		return $retval;
	}

	/**
	 * Returns all galleries
	 * @param array $args (blog_id, username, password)
     * @return array|IXR_Error
	 */
	function get_galleries($args)
	{
		$retval		= new IXR_Error(403, 'Invalid username or password');
		$blog_id	= intval($args[0]);
		$username	= strval($args[1]);
		$password   = strval($args[2]);

		// Authenticate the user
		if ($this->_login($username, $password, $blog_id)) {

			// Do we have permission?
			$security = $this->get_registry()->get_utility('I_Security_Manager');
			if ($security->is_allowed('nextgen_edit_gallery')) {
				$mapper 		= C_Gallery_Mapper::get_instance();
				$retval			= array();
				foreach ($mapper->find_all() as $gallery) {
					$this->_add_gallery_properties($gallery);
					$retval[$gallery->{$gallery->id_field}] = (array)$gallery;
				}
			}
			else $retval = new IXR_Error( 401, __( 'Sorry, you must be able to manage galleries' ) );
		}

		return $retval;
	}

	/**
	 * Gets a single gallery instance
	 * @param array $args (blog_id, username, password, gallery_id)
     * @param bool $return_model
     * @return object|bool|IXR_Error
	 */
	function get_gallery($args, $return_model=FALSE)
	{
		$retval		= new IXR_Error(403, 'Invalid username or password');
		$blog_id	= intval($args[0]);
		$username	= strval($args[1]);
		$password   = strval($args[2]);
		$gallery_id	= intval($args[3]);

		// Authenticate the user
		if ($this->_login($username, $password, $blog_id)) {
			$mapper = C_Gallery_Mapper::get_instance();
			if (($gallery = $mapper->find($gallery_id, TRUE))) {
				if ($this->_can_manage_gallery($gallery)) {
					$this->_add_gallery_properties($gallery);
					$retval = $return_model ? $gallery : $gallery->get_entity();
				}
				else $retval = new IXR_Error(403, "Sorry, but you don't have permission to manage gallery #{$gallery->gid}");
			}
			else $retval = FALSE;
		}

		return $retval;
	}

	/**
	 * Deletes a gallery
	 * @param array $args (blog_id, username, password, gallery_id)
     * @return bool
	 */
	function delete_gallery($args)
	{
		$retval = $this->get_gallery($args, TRUE);

		if (!($retval instanceof IXR_Error) and is_object($retval)) {
			$retval = $retval->destroy();
		}

		return $retval;
	}

	/**
	 * Creates a new album
	 * @param array $args (blog_id, username, password, title, previewpic, description, galleries
     * @return int|IXR_Error
	 */
	function create_album($args)
	{
		$retval		= new IXR_Error(403, 'Invalid username or password');
		$blog_id	= intval($args[0]);
		$username	= strval($args[1]);
		$password   = strval($args[2]);
		$title		= strval($args[3]);
		$previewpic = isset($args[4]) ? intval($args[4]): 0;
		$desc		= isset($args[5]) ? strval($args[5]) : '';
		$sortorder  = isset($args[6]) ? $args[6] : '';
		$page_id	= isset($args[7]) ? intval($args[7]) : 0;

		// Authenticate the user
		if ($this->_login($username, $password, $blog_id)) {

			// Is request allowed?
			$security = $this->get_registry()->get_utility('I_Security_Manager');
			if ($security->is_allowed('nextgen_edit_album')) {

				$mapper = C_Album_Mapper::get_instance();
				$album = $mapper->create(array(
					'name'			=>	$title,
					'previewpic'	=>	$previewpic,
					'albumdesc'		=>	$desc,
					'sortorder'		=>	$sortorder,
					'pageid'		=>	$page_id
				));

				if ($album->save()) $retval = $album->id();
				else $retval = new IXR_Error(500, "Unable to create album");
			}
		}

		return $retval;
	}


	/**
	 * Returns all albums
	 * @param $args (blog_id, username, password)
	 * @return IXR_Error
	 */
	function get_albums($args)
	{
		$retval		= new IXR_Error(403, 'Invalid username or password');
		$blog_id	= intval($args[0]);
		$username	= strval($args[1]);
		$password   = strval($args[2]);

		// Authenticate the user
		if ($this->_login($username, $password, $blog_id)) {

			// Are we allowed?
			$security = $this->get_registry()->get_utility('I_Security_Manager');
			if ($security->is_allowed('nextgen_edit_album')) {

				// Fetch all albums
				$mapper = C_Album_Mapper::get_instance();
				$retval = array();
				foreach ($mapper->find_all() as $album) {
					// Vladimir's Lightroom plugins requires the 'id' to be a string
					// Ask if he can accept integers as well. Currently, integers break
					// his plugin
					$album->id = (string) $album->id;
					$album->galleries = $album->sortorder;

					$retval[$album->{$album->id_field}] = (array) $album;
				}
			}
			else $retval = new IXR_Error(403, "Sorry, you must be able to manage albums");


		}

		return $retval;
	}

	/**
	 * Gets a single album
	 * @param array $args (blog_id, username, password, album_id)
     * @param bool $return_model (optional)
     * @return object|bool|IXR_Error
	 */
	function get_album($args, $return_model=FALSE)
	{
		$retval		= new IXR_Error(403, 'Invalid username or password');
		$blog_id	= intval($args[0]);
		$username	= strval($args[1]);
		$password   = strval($args[2]);
		$album_id	= intval($args[3]);

		// Authenticate the user
		if ($this->_login($username, $password, $blog_id)) {

			// Are we allowed?
			$security = $this->get_registry()->get_utility('I_Security_Manager');
			if ($security->is_allowed('nextgen_edit_album')) {
				$mapper = C_Album_Mapper::get_instance();
				if (($album = $mapper->find($album_id, TRUE))) {
					// Vladimir's Lightroom plugins requires the 'id' to be a string
					// Ask if he can accept integers as well. Currently, integers break
					// his plugin
					$album->id = (string) $album->id;
					$album->galleries = $album->sortorder;

					$retval = $return_model ? $album : $album->get_entity();
				}
				else $retval = FALSE;

			}
			else $retval = new IXR_Error(403, "Sorry, you must be able to manage albums");
		}

		return $retval;
	}

	/**
	 * Deletes an existing album
	 * @param array $args (blog_id, username, password, album_id)
     * @return bool
	 */
	function delete_album($args)
	{
		$retval = $this->get_album($args, TRUE);

		if (!($retval instanceof IXR_Error)) {
			$retval = $retval->destroy();
		}

		return $retval;
	}

	/**
	 * Edit an existing album
	 * @param array $args (blog_id, username, password, album_id, name, preview pic id, description, galleries)
     * @return object|IXR_Error
	 */
	function edit_album($args)
	{
		$retval = $this->get_album($args, TRUE);

		if (!($retval instanceof IXR_Error)) {
			$retval->name 		= strval($args[4]);
			$retval->previewpic = intval($args[5]);
			$retval->albumdesc	= strval($args[6]);
			$retval->sortorder  = $args[7];

			$properties = isset($args[8]) ? $args[8] : array();
			foreach ($properties as $key => $value) $retval->$key = $value;
			unset($retval->galleries);

			$retval = $retval->save();
		}

		return $retval;
	}

	/**
	 * Sets the post thumbnail for a post to a NextGEN Gallery image
	 * @param $args (blog_id, username, password, post_id, image_id)
	 *
	 * @return IXR_Error|int attachment id
	 */
	function set_post_thumbnail($args)
	{
		$retval		= new IXR_Error(403, 'Invalid username or password');
		$blog_id	= intval($args[0]);
		$username	= strval($args[1]);
		$password   = strval($args[2]);
		$post_ID	= intval($args[3]);
		$image_id   = intval($args[4]);

		// Authenticate the user
		if ($this->_login($username, $password, $blog_id)) {
			if ( current_user_can( 'edit_post', $post_ID )) {
				$retval = C_Gallery_Storage::get_instance()->set_post_thumbnail($post_ID, $image_id);
			}
			else $retval = new IXR_Error(403, "Sorry but you need permission to do this");
		}

		return $retval;
	}
}

