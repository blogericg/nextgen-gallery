<?php

/**
 * Provides a datamapper for galleries
 * @mixin Mixin_NextGen_Table_Extras
 * @mixin Mixin_Gallery_Mapper
 * @implements I_Gallery_Mapper
 */
class C_Gallery_Mapper extends C_CustomTable_DataMapper_Driver
{
    public static $_instance = NULL;

	/**
	 * Define the object
	 * @param string|bool $context (optional)
     * @param mixed $not_used Not used, exists only to prevent PHP warnings
	 */
	function define($context=FALSE, $not_used=FALSE)
	{
		// Add 'gallery' context
		if (!is_array($context)) $context = array($context);
		array_push($context, 'gallery');

		$this->_primary_key_column = 'gid';

		// Continue defining the object
		parent::define('ngg_gallery', $context);
		$this->set_model_factory_method('gallery');
		$this->add_mixin('Mixin_NextGen_Table_Extras');
		$this->add_mixin('Mixin_Gallery_Mapper');
		$this->implement('I_Gallery_Mapper');

		// Define the columns
		$this->define_column('gid',		'BIGINT', 0);
		$this->define_column('name',	'VARCHAR(255)');
		$this->define_column('slug',  	'VARCHAR(255)');
		$this->define_column('path',  	'TEXT');
		$this->define_column('title', 	'TEXT');
		$this->define_column('pageid', 	'INT', 0);
		$this->define_column('previewpic', 'INT', 0);
		$this->define_column('author', 	'INT', 0);
        $this->define_column('extras_post_id', 'BIGINT', 0);
	}

	function initialize($object_name=FALSE)
	{
		parent::initialize('ngg_gallery');
	}

	/**
	 * Returns a singleton of the gallery mapper
	 * @param bool|string $context
	 * @return C_Gallery_Mapper
	 */
    public static function get_instance($context = False)
    {
        if (!self::$_instance) {
            $klass = get_class();
            self::$_instance  = new $klass($context);
        }
        return self::$_instance;
    }
}

class Mixin_Gallery_Mapper extends Mixin
{
	/**
	 * Uses the title property as the post title when the Custom Post driver is used
     * @param object $entity
     * @return string
	 */
    function get_post_title($entity)
    {
        return $entity->title;
    }

    /**
     * @param string $slug
     * @return C_Gallery|stdClass|null
     */
    public function get_by_slug($slug)
    {
        $sanitized_slug = sanitize_title($slug);

        // Try finding the gallery by slug first; if nothing is found assume that the user passed a gallery id
        $retval = $this->object->select()->where(array('slug = %s', $sanitized_slug))->limit(1)->run_query();

        // NextGen used to turn "This & That" into "this-&amp;-that" when assigning gallery slugs
        if (empty($retval) && strpos($slug, '&') !== FALSE)
            return $this->get_by_slug(str_replace('&', '&amp;', $slug));

        return reset($retval);
    }

    function _save_entity($entity)
    {
        $storage = C_Gallery_Storage::get_instance();

        // A bug in NGG 2.1.24 allowed galleries to be created with spaces in the directory name, unreplaced by dashes
        // This causes a few problems everywhere, so we here allow users a way to fix those galleries by just re-saving
        if (FALSE !== strpos($entity->path, ' '))
        {
            $abspath = $storage->get_gallery_abspath($entity->{$entity->id_field});

            $pre_path = $entity->path;

            $entity->path = str_replace(' ', '-', $entity->path);

            $new_abspath = str_replace($pre_path, $entity->path, $abspath);

            // Begin adding -1, -2, etc until we have a safe target: rename() will overwrite existing directories
            if (@file_exists($new_abspath))
            {
                $max_count = 100;
                $count = 0;
                $corrected_abspath = $new_abspath;
                while (@file_exists($corrected_abspath) && $count <= $max_count) {
                    $count++;
                    $corrected_abspath = $new_abspath . '-' . $count;
                }
                $new_abspath = $corrected_abspath;
                $entity->path = $entity->path . '-' . $count;
            }

            @rename($abspath, $new_abspath);
        }
    
        $slug = $entity->slug;
  
        $entity->slug = str_replace(' ', '-', $entity->slug);
        $entity->slug = sanitize_title($entity->slug);
    
        if ($slug != $entity->slug)
            $entity->slug = nggdb::get_unique_slug($entity->slug, 'gallery');

        $retval = $this->call_parent('_save_entity', $entity);

        if ($retval)
        {
            wp_mkdir_p($storage->get_gallery_abspath($entity));
            do_action('ngg_created_new_gallery', $entity->{$entity->id_field});
            C_Photocrati_Transient_Manager::flush('displayed_gallery_rendering');
        }

        return $retval;
    }

    function destroy($gallery, $with_dependencies=FALSE)
    {
		$retval = FALSE;

		if ($gallery) {
			if (is_numeric($gallery))
            {
                $gallery_id = $gallery;
                $gallery = $this->object->find($gallery_id);
            }
            else {
			    $gallery_id = $gallery->{$gallery->id_field};
            }

			// TODO: Look into making this operation more efficient
			if ($with_dependencies) {
				$image_mapper = C_Image_Mapper::get_instance();

				// Delete the image files from the filesystem
				$settings = C_NextGen_Settings::get_instance();
				if ($settings->deleteImg)
				{
					$storage = C_Gallery_Storage::get_instance();
					$storage->delete_gallery($gallery);
				}

				// Delete the image records from the DB
                foreach ($image_mapper->find_all_for_gallery($gallery_id) as $image) {
				    $image_mapper->destroy($image);
                }

				$image_key = $image_mapper->get_primary_key_column();
				$image_table = $image_mapper->get_table_name();

				// Delete tag associations no longer needed. The following SQL statement
				// deletes all tag associates for images that no longer exist
				global $wpdb;
				$wpdb->query("
					DELETE wptr.* FROM {$wpdb->term_relationships} wptr
					INNER JOIN {$wpdb->term_taxonomy} wptt
					ON wptt.term_taxonomy_id = wptr.term_taxonomy_id
					WHERE wptt.term_taxonomy_id = wptr.term_taxonomy_id
					AND wptt.taxonomy = 'ngg_tag'
					AND wptr.object_id NOT IN (SELECT {$image_key} FROM {$image_table})"
				);

			}

			$retval = $this->call_parent('destroy', $gallery);

			if ($retval) {
				do_action('ngg_delete_gallery', $gallery);
				C_Photocrati_Transient_Manager::flush('displayed_gallery_rendering');
			}
		}

		return $retval;
	}

    function set_preview_image($gallery, $image, $only_if_empty=FALSE)
    {
        $retval = FALSE;

        // We need the gallery object
        if (is_numeric($gallery)) {
            $gallery = $this->object->find($gallery);
        }

        // We need the image id
        if (!is_numeric($image)) {
            if (method_exists($image, 'id')) {
                $image = $image->id();
            }
            else {
                $image = $image->{$image->id_field};
            }
        }

        if ($gallery && $image) {
            if (($only_if_empty && !$gallery->previewpic) OR !$only_if_empty) {
                $gallery->previewpic = $image;
                $retval = $this->object->save($gallery);
            }
        }

        return $retval;
    }

	/**
	 * Sets default values for the gallery
     * @param object $entity
	 */
	function set_defaults($entity)
	{
		// If author is missing, then set to the current user id
        // TODO: Using wordpress function. Should use abstraction
		$this->object->_set_default_value($entity, 'author', get_current_user_id());
	}
}
