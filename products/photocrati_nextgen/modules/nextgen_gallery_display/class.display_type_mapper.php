<?php

/**
 * Provides a datamapper to perform CRUD operations for Display Types
 *
 * @mixin Mixin_Display_Type_Mapper
 * @implements I_Display_Type_Mapper
 */
class C_Display_Type_Mapper extends C_CustomPost_DataMapper_Driver
{
	public static $_instances = array();

	function define($context=FALSE, $not_used=FALSE)
	{
		$object_name = 'display_type';

		// Add the object name to the context of the object as well
		// This allows us to adapt the driver itself, if required
		if (!is_array($context)) $context = array($context);
		array_push($context, $object_name);
		parent::define($object_name, $context);

		$this->add_mixin('Mixin_Display_Type_Mapper');
		$this->implement('I_Display_Type_Mapper');
		$this->set_model_factory_method($object_name);

		// Define columns
		$this->define_column('ID', 'BIGINT', 0);
		$this->define_column('name', 'VARCHAR(255)');
		$this->define_column('title', 'VARCHAR(255)');
		$this->define_column('preview_image_relpath', 'VARCHAR(255)');
		$this->define_column('default_source', 'VARCHAR(255)');
		$this->define_column('view_order', 'BIGINT', NGG_DISPLAY_PRIORITY_BASE);

		$this->add_serialized_column('settings');
		$this->add_serialized_column('entity_types');
	}

	function initialize($context=FALSE)
	{
		parent::initialize();
	}


	/**
	 * Gets a singleton of the mapper
	 * @param string|bool $context
	 * @return C_Display_Type_Mapper
	 */
    public static function get_instance($context = False)
    {
        if (!isset(self::$_instances[$context]))
            self::$_instances[$context] = new C_Display_Type_Mapper($context);
        return self::$_instances[$context];
    }
}

/**
 * Provides instance methods for the display type mapper
 */
class Mixin_Display_Type_Mapper extends Mixin
{
	/**
	 * Locates a Display Type by names
	 * @param string $name
     * @param bool $model
     * @return null|object
	 */
	function find_by_name($name, $model=FALSE)
	{
		$retval = NULL;
		$this->object->select();
		$this->object->where(array('name = %s', $name));
		$results = $this->object->run_query(FALSE, $model);
		if (!$results) {
			foreach ($this->object->find_all(FALSE, $model) as $entity) {
				if ($entity->name == $name || (isset($entity->aliases) && is_array($entity->aliases) && in_array($name, $entity->aliases))) {
					$retval = $entity;
					break;
				}
			}
		}
		else $retval = $results[0];

		return $retval;
	}

	/**
	 * Finds display types used to display specific types of entities
	 * @param string|array $entity_type e.g. image, gallery, album
     * @param bool $model (optional)
	 * @return array
	 */
	function find_by_entity_type($entity_type, $model=FALSE)
	{
		$find_entity_types = is_array($entity_type) ? $entity_type : array($entity_type);

		$retval = NULL;
		foreach ($this->object->find_all(FALSE, $model) as $display_type) {
			foreach ($find_entity_types as $entity_type) {
				if (isset($display_type->entity_types) && in_array($entity_type, $display_type->entity_types)) {
					$retval[] = $display_type;
					break;
				}
			}
		}

		return $retval;
	}

	/**
	 * Uses the title attribute as the post title
	 * @param stdClass $entity
	 * @return string
	 */
	function get_post_title($entity)
	{
		return $entity->title;
	}

	/**
	 * Sets default values needed for display types
     * @param object $entity (optional)
	 */
	function set_defaults($entity)
	{
		if (!isset($entity->settings)) $entity->settings = array();
		$this->_set_default_value($entity, 'preview_image_relpath', '');
		$this->_set_default_value($entity, 'default_source', '');
        $this->_set_default_value($entity, 'view_order', NGG_DISPLAY_PRIORITY_BASE);
        $this->_set_default_value($entity, 'settings', 'use_lightbox_effect', TRUE);
        $this->_set_default_value($entity, 'hidden_from_ui', FALSE); // todo remove later
		$this->_set_default_value($entity, 'hidden_from_igw', FALSE);
		$this->_set_default_value($entity, 'aliases', array());

        return $this->call_parent('set_defaults', $entity);
	}
}
