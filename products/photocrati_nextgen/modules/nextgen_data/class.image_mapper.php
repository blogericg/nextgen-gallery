<?php

/**
 * Class C_Image_Mapper
 * @mixin Mixin_NextGen_Table_Extras
 * @mixin Mixin_Gallery_Image_Mapper
 * @implements I_Image_Mapper
 */
class C_Image_Mapper extends C_CustomTable_DataMapper_Driver
{
    public static $_instance = NULL;

	/**
	 * Defines the gallery image mapper
	 * @param string|false $context (optional)
     * @param mixed $not_used
	 */
	function define($context=FALSE, $not_used=FALSE)
	{
		// Add 'attachment' context
		if (!is_array($context)) $context = array($context);
		array_push($context, 'attachment');

		// Define the mapper
		$this->_primary_key_column = 'pid';
		parent::define('ngg_pictures', $context);
		$this->add_mixin('Mixin_NextGen_Table_Extras');
		$this->add_mixin('Mixin_Gallery_Image_Mapper');
		$this->implement('I_Image_Mapper');
		$this->set_model_factory_method('image');

		// Define the columns
		$this->define_column('pid', 		'BIGINT', 0);
		$this->define_column('image_slug',	'VARCHAR(255)');
		$this->define_column('post_id',		'BIGINT', 0);
		$this->define_column('galleryid',	'BIGINT', 0);
		$this->define_column('filename',	'VARCHAR(255)');
		$this->define_column('description',	'TEXT');
		$this->define_column('alttext',		'TEXT');
		$this->define_column('imagedate',	'DATETIME');
		$this->define_column('exclude',		'INT', 0);
		$this->define_column('sortorder',	'BIGINT', 0);
		$this->define_column('meta_data',	'TEXT');
        $this->define_column('extras_post_id', 'BIGINT', 0);
		$this->define_column('updated_at',  'BIGINT');

		// Mark the columns which should be unserialized
		$this->add_serialized_column('meta_data');
	}

	function initialize($object_name=FALSE)
	{
		parent::initialize('ngg_pictures');
	}

    /**
     * @param bool|string $context
     * @return C_Image_Mapper
     */
    static function get_instance($context = False)
    {
        if (is_null(self::$_instance)) {
            $klass = get_class();
            self::$_instance  = new $klass($context);
        }
        return self::$_instance;
    }
}

/**
 * Sets the alttext property as the post title
 */
class Mixin_Gallery_Image_Mapper extends Mixin
{
	function destroy($image)
	{
		$retval = $this->call_parent('destroy',$image);

		// Delete tag associations with the image
		if (!is_numeric($image)) $image = $image->{$image->id_field};
		wp_delete_object_term_relationships( $image, 'ngg_tag');

		C_Photocrati_Transient_Manager::flush('displayed_gallery_rendering');
		return $retval;
	}


    function _save_entity($entity)
    {
		$entity->updated_at = time();

        // If successfully saved then import metadata
        $retval = $this->call_parent('_save_entity', $entity);
        if ($retval)
        {
            include_once(NGGALLERY_ABSPATH . '/admin/functions.php');
            $image_id = $this->get_id($entity);
			if (!isset($entity->meta_data['saved']))
                nggAdmin::import_MetaData($image_id);
	        C_Photocrati_Transient_Manager::flush('displayed_gallery_rendering');
        }
        return $retval;
    }

	function reimport_metadata($image_or_id)
	{
		$image = NULL;
		if (is_int($image_or_id))
			$image = $this->object->find($image_or_id);
		else
		    $image = $image_or_id;

		// Reset all image details that would have normally been imported
		if (is_array($image->meta_data))
		    unset($image->meta_data['saved']);

		if (!class_exists('nggAdmin'))
            include_once(NGGALLERY_ABSPATH . '/admin/functions.php');

		nggAdmin::import_MetaData($image);

		return $this->object->save($image);
	}

    /**
     * Retrieves the id from an image
     * @param $image
     * @return bool
     */
    function get_id($image)
    {
        $retval = FALSE;

        // Have we been passed an entity and is the id_field set?
        if ($image instanceof stdClass) {
            if (isset($image->id_field)) {
                $retval = $image->{$image->id_field};
            }
        }

        // Have we been passed a model?
        else $retval = $image->id();

        // If we still don't have an id, then we'll lookup the primary key
        // and try fetching it manually
        if (!$retval) {
            $key = $this->object->get_primary_key_column();
            $retval = $image->$key;

        }

        return $retval;
    }


	function get_post_title($entity)
	{
		return $entity->alttext;
	}

	function set_defaults($entity)
	{
		// If not set already, we'll add an exclude property. This is used
		// by NextGEN Gallery itself, as well as the Attach to Post module
		$this->object->_set_default_value($entity, 'exclude', 0);

		// Ensure that the object has a description attribute
		$this->object->_set_default_value($entity, 'description', '');

		// If not set already, set a default sortorder
		$this->object->_set_default_value($entity, 'sortorder', 0);

		// The imagedate must be set
        if ((!isset($entity->imagedate)) OR is_null($entity->imagedate) OR $entity->imagedate == '0000-00-00 00:00:00')
            $entity->imagedate = date("Y-m-d H:i:s");

		// If a filename is set, and no alttext is set, then set the alttext
		// to the basename of the filename (legacy behavior)
		if (isset($entity->filename)) {
			$path_parts = M_I18n::mb_pathinfo( $entity->filename);
			$alttext = ( !isset($path_parts['filename']) ) ?
				substr($path_parts['basename'], 0,strpos($path_parts['basename'], '.')) :
				$path_parts['filename'];
			$this->object->_set_default_value($entity, 'alttext', $alttext);
		}

        // Set unique slug
        if (isset($entity->alttext) && empty($entity->image_slug)) {
            $entity->image_slug = nggdb::get_unique_slug( sanitize_title_with_dashes( $entity->alttext ), 'image' );
        }

		// Ensure that the exclude parameter is an integer or boolean-evaluated
		// value
		if (is_string($entity->exclude)) $entity->exclude = intval($entity->exclude);

		// Trim alttext and description
		$entity->description = trim($entity->description);
		$entity->alttext	 = trim($entity->alttext);
	}

	/**
	 * Finds all images for a gallery
     *
	 * @param int|C_Gallery|stdClass $gallery
	 * @param bool $model
	 * @return array
	 */
	function find_all_for_gallery($gallery, $model = FALSE)
	{
		$retval = array();
		$gallery_id = 0;

		if (is_object($gallery))
		{
			if (isset($gallery->id_field))
			    $gallery_id = $gallery->{$gallery->id_field};
			else {
				$key = $this->object->get_primary_key_column();
				if (isset($gallery->$key)) $gallery_id = $gallery->$key;
			}
		}
		elseif (is_numeric($gallery)) {
            $gallery_id = $gallery;
        }

		if ($gallery_id)
		    $retval = $this->object->select()->where(array("galleryid = %s", $gallery_id))->run_query(FALSE, $model);

		return $retval;
	}
}
