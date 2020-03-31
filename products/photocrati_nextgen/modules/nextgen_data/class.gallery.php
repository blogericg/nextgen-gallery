<?php

class Mixin_NextGen_Gallery_Validation
{
	/**
     * Validates whether the gallery can be saved
     */
    function validation()
    {
        // If a title is present, we can auto-populate some other properties
        if (($this->object->title)) {

	        // Strip html
	        $this->object->title = M_NextGen_Data::strip_html($this->object->title, TRUE);
	        $sanitized_title = str_replace(' ', '-', $this->object->title);

	        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
		        $sanitized_title = remove_accents($sanitized_title);

            // If no name is present, use the title to generate one
            if (!($this->object->name))
                $this->object->name = apply_filters('ngg_gallery_name', sanitize_file_name($sanitized_title));

            // Assign a slug; possibly updating the current slug if it was conceived by a method other than sanitize_title()
            // NextGen 3.2.19 and older used a method adopted from esc_url() which would convert ampersands to "&amp;"
            // and allow slashes in gallery slugs which breaks their ability to be linked to as children of albums
            $sanitized_slug = sanitize_title($sanitized_title);
            if (empty($this->object->slug) || $this->object->slug !== $sanitized_slug)
            {
                $this->object->slug = $sanitized_slug;
                $this->object->slug = nggdb::get_unique_slug($this->object->slug, 'gallery');
            }
        }

        // Set what will be the path to the gallery
        $storage = C_Gallery_Storage::get_instance();
        if (!($this->object->path))
        {
            $this->object->path = $storage->get_gallery_relpath($this->object);
        }

	    // Ensure that the gallery path is restricted to $fs->get_document_root('galleries')
	    $fs = C_Fs::get_instance();
        $root = $fs->get_document_root('galleries');
        $storage->flush_gallery_path_cache($this->object);
        $gallery_abspath = $storage->get_gallery_abspath($this->object);
        if (strpos($gallery_abspath, $root) === FALSE) {
        	$this->object->add_error(sprintf(__("Gallery path must be located in %s", 'nggallery'), $root), 'gallerypath');
	        $this->object->path = $storage->get_upload_relpath($this->object);
        }
        $this->object->path = trailingslashit($this->object->path);

        // Check for '..' in the path
        $sections = explode(DIRECTORY_SEPARATOR, trim($this->object->path, '/\\'));
        if (in_array('..', $sections, TRUE)) {
            $this->object->add_error(__("Gallery paths may not use '..' to access parent directories)", 'nggallery'));
        }

        // Establish some rules on where galleries can go
        $abspath = $storage->get_gallery_abspath($this->object);

        // Galleries should at least be a sub-folder, not directly in WP_CONTENT
        $not_directly_in = array(
            'content' => wp_normalize_path(WP_CONTENT_DIR),
            'wordpress root' => $fs->get_document_root()
        );
        if (!empty($_SERVER['DOCUMENT_ROOT']))
            $not_directly_in['document root'] = $_SERVER['DOCUMENT_ROOT'];
        foreach ($not_directly_in as $label => $dir) {
            if ($abspath == $dir) {
                $this->object->add_error(sprintf(__("Gallery path must be a sub-directory under the %s directory", 'nggallery'), $label), 'gallerypath');
            }
        }

        $ABSPATH = wp_normalize_path(ABSPATH);

        // Disallow galleries from being under these directories at all
        $not_ever_in = array(
            'plugins'          => wp_normalize_path(WP_PLUGIN_DIR),
            'must use plugins' => wp_normalize_path(WPMU_PLUGIN_DIR),
            'wp-admin'         => $fs->join_paths($ABSPATH, 'wp-admin'),
            'wp-includes'      => $fs->join_paths($ABSPATH, 'wp-includes'),
            'themes'           => get_theme_root()
        );
        foreach ($not_ever_in as $label => $dir) {
            if (strpos($abspath, $dir) === 0) {
                $this->object->add_error(sprintf(__("Gallery path cannot be under %s directory", 'nggallery'), $label), 'gallerypath');
            }
        }

        // Regardless of where they are just don't let the path end in any of these
        $never_named = array(
            'wp-admin',
            'wp-includes',
            'wp-content'
        );
        foreach ($never_named as $name) {
            if ($name=== end($sections)) {
                $this->object->add_error(sprintf(__("Gallery path cannot end with a directory named %s", 'nggallery'), $name), 'gallerypath');
            }
        }

        unset($storage);

        $this->object->validates_presence_of('title');
		$this->object->validates_presence_of('name');
        $this->object->validates_uniqueness_of('slug');
        $this->object->validates_numericality_of('author');

        return $this->object->is_valid();
    }
}

/**
 * Creates a model representing a NextGEN Gallery object
 * @mixin Mixin_NextGen_Gallery_Validation
 * @implements I_Gallery
 */
class C_Gallery extends C_DataMapper_Model
{
	var $_mapper_interface = 'I_Gallery_Mapper';

    /**
     * Defines the interfaces and methods (through extensions and hooks)
     * that this class provides
     */
    function define($properties = array(), $mapper = FALSE, $context = FALSE)
    {
        parent::define($mapper, $properties, $context);
		$this->add_mixin('Mixin_NextGen_Gallery_Validation');
        $this->implement('I_Gallery');
    }

	/**
	 * Instantiates a new model
	 * @param array|stdClass $properties (optional)
	 * @param C_Gallery_Mapper|false $mapper (optional)
	 * @param string|bool $context (optional)
	 */
	function initialize($properties=array(), $mapper=FALSE, $context=FALSE)
	{
		if (!$mapper)
			$mapper = $this->get_registry()->get_utility($this->_mapper_interface);
		parent::initialize($mapper, $properties);
	}

	function get_images()
	{
		$mapper = C_Image_Mapper::get_instance();
		return $mapper->select()->where(array('galleryid = %d', $this->gid))->order_by('sortorder')->run_query();
	}
}
