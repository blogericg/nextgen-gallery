<?php

/**
 * Associates a Display Type with a collection of images
 *
 * * Properties:
 * - source				(gallery, album, recent_images, random_images, etc)
 * - container_ids		(gallery ids, album ids, tag ids, etc)
 * - display_type		(name of the display type being used)
 * - display_settings	(settings for the display type)
 * - exclusions			(excluded entity ids)
 * - entity_ids			(specific images/galleries to include, sorted)
 * - order_by
 * - order_direction
 *
 * @mixin Mixin_Displayed_Gallery_Validation
 * @mixin Mixin_Displayed_Gallery_Instance_Methods
 * @mixin Mixin_Displayed_Gallery_Queries
 * @implements I_Displayed_Gallery
 */
class C_Displayed_Gallery extends C_DataMapper_Model
{
	var $_mapper_interface = 'I_Displayed_Gallery_Mapper';

	function define($properties=array(), $mapper=FALSE, $context=FALSE)
	{
		parent::define($mapper, $properties, $context);
		$this->add_mixin('Mixin_Displayed_Gallery_Validation');
		$this->add_mixin('Mixin_Displayed_Gallery_Instance_Methods');
		$this->add_mixin('Mixin_Displayed_Gallery_Queries');
		$this->implement('I_Displayed_Gallery');
	}

	/**
	 * Initializes a display type with properties
     * @param array|stdClass|C_Displayed_Gallery $properties
	 * @param FALSE|C_Displayed_Gallery_Mapper $mapper
	 * @param FALSE|string|array $context
	 */
	function initialize($properties=array(), $mapper=FALSE, $context=FALSE)
	{
		if (!$mapper) $mapper = $this->get_registry()->get_utility($this->_mapper_interface);
		parent::initialize($mapper, $properties);
	}
}

/**
 * Provides validation
 */
class Mixin_Displayed_Gallery_Validation extends Mixin
{
	function validation()
	{
		// Valid sources
		$this->object->validates_presence_of('source');

		// Valid display type?
		$this->object->validates_presence_of('display_type');
		if (($display_type = $this->object->get_display_type())) {
            foreach ($this->object->display_settings as $key => $val) $display_type->settings[$key] = $val;
            $this->object->display_settings = $display_type->settings;
			if (!$display_type->validate()) {
				foreach ($display_type->get_errors() as $property => $errors) {
					foreach ($errors as $error) {
						$this->object->add_error($error, $property);
					}
				}
			}
			$this->object->display_type = $display_type->name;

			// Is the display type compatible with the source? E.g., if we're
			// using a display type that expects images, we can't be feeding it
			// galleries and albums
			if (($source = $this->object->get_source())) {
				if (!$display_type->is_compatible_with_source($source)) {
					$this->object->add_error(
						__('Source not compatible with selected display type', 'nggallery'),
						'display_type'
					);
				}
			}

            // Allow ONLY recent & random galleries to have their own maximum_entity_count
            if (!empty($this->object->display_settings['maximum_entity_count'])
            &&  in_array($this->object->source, array('random_images', 'recent_images', 'random', 'recent'))) {
                $this->object->maximum_entity_count = $this->object->display_settings['maximum_entity_count'];
            }

            // If no maximum_entity_count has been given, then set a maximum
			if (!isset($this->object->maximum_entity_count))
			{
				$settings = C_NextGen_Settings::get_instance();
				$this->object->maximum_entity_count = $settings->get('maximum_entity_count', 500);
			}
		}
		else {
			$this->object->add_error('Invalid display type', 'display_type');
		}

		return $this->object->is_valid();
	}
}

class Mixin_Displayed_Gallery_Queries extends Mixin
{
    // The "alternative" approach to using "ORDER BY RAND()" works by finding X image PID in a kind of shotgun-blast
    // like scattering in a second query made via $wpdb that is then fed into the query built by _get_image_entities().
    // This variable is used to cache the results of that inner quasi-random PID retrieval so that multiple calls
    // to $displayed_gallery->get_entities() don't return different results for each invocation. This is important
    // for NextGen Pro's galleria module in order to 'localize' the results of get_entities() to JSON.
    protected static $_random_image_ids_cache = array();

	function get_entities($limit=FALSE, $offset=FALSE, $id_only=FALSE, $returns='included')
	{
		$retval     = array();
        $source_obj = $this->object->get_source();
        $max        = $this->object->get_maximum_entity_count();

        if (!$limit || (is_numeric($limit) && $limit > $max))
            $limit = $max;

		// Ensure that all parameters have values that are expected
		if ($this->object->_parse_parameters())
        {
			// Is this an image query?
			if (in_array('image', $source_obj->returns)) {
				$retval = $this->object->_get_image_entities($source_obj, $limit, $offset, $id_only, $returns);
			}

			// Is this a gallery/album query?
			elseif (in_array('gallery', $source_obj->returns)) {
				$retval = $this->object->_get_album_and_gallery_entities($source_obj, $limit, $offset, $id_only, $returns);
			}
		}

		return $retval;
	}

	/**
	 * Gets all images in the displayed gallery
	 * @param stdClass $source_obj
	 * @param int $limit
	 * @param int $offset
	 * @param boolean $id_only
	 * @param string $returns
	 */
	function _get_image_entities($source_obj, $limit, $offset, $id_only, $returns)
	{
		// TODO: This method is very long, and therefore more difficult to read
		// Find a way to minimalize or segment
		$settings		= C_NextGen_Settings::get_instance();
		$mapper	        = C_Image_Mapper::get_instance();
		$image_key		= $mapper->get_primary_key_column();
		$select			= $id_only ? $image_key : $mapper->get_table_name().'.*';
		if (strtoupper($this->object->order_direction) == 'DSC') $this->object->order_direction = 'DESC';
		$sort_direction	= in_array(strtoupper($this->object->order_direction), array('ASC', 'DESC'))
							? $this->object->order_direction
							: $settings->galSortDir;
		
		$sort_by		= in_array(strtolower($this->object->order_by), array_merge(C_Image_Mapper::get_instance()->get_column_names(), array('rand()')))
							? $this->object->order_by
							: $settings->galSort;

		// Quickly sanitize
		global $wpdb;
		$this->object->container_ids	= $this->object->container_ids 
											? array_map(array($wpdb, '_escape'), $this->object->container_ids)
											: array();
		$this->object->entity_ids		= $this->object->entity_ids
											? array_map(array($wpdb, '_escape'), $this->object->entity_ids)
											: array();
		$this->object->exclusions		= $this->object->exclusions
											? array_map(array($wpdb, '_escape'), $this->object->exclusions)
											: array();

		// Here's what this method is doing:
		// 1) Determines what results need returned
		// 2) Determines from what container ids the results should come from
		// 3) Applies ORDER BY clause
		// 4) Applies LIMIT/OFFSET clause
		// 5) Executes the query and returns the result

		// We start with the most difficult query. When returns is "both", we
		// need to return a list of both included and excluded entity ids, and
		// mark specifically which entities are excluded
		if ($returns == 'both') {

			// We need to add two dynamic columns, one called "sortorder" and
			// the other called "exclude".
			$if_true		= 1;
			$if_false		= 0;
			$excluded_set	= $this->object->entity_ids;
			if (!$excluded_set) {
				$if_true	= 0;
				$if_false	= 1;
				$excluded_set = $this->object->exclusions;
			}
			$sortorder_set	= $this->object->sortorder ? $this->object->sortorder :  $excluded_set;

			// Add sortorder column
			if ($sortorder_set) {
				$select = $this->object->_add_find_in_set_column(
					$select,
					$image_key,
					$sortorder_set,
					'new_sortorder',
					TRUE
				);
				// A user might want to sort the results by the order of
				// images that they specified to be included. For that,
				// we need some trickery by reversing the order direction
				$sort_direction = $this->object->order_direction == 'ASC' ? 'DESC' : 'ASC';
				$sort_by = 'new_sortorder';
			}

			// Add exclude column
			if ($excluded_set) {
				$select = $this->object->_add_find_in_set_column(
					$select,
					$image_key,
					$excluded_set,
					'exclude'
				);
				$select .= ", IF (exclude = 0 AND @exclude = 0, $if_true, $if_false) AS 'exclude'";
			}

			// Select what we want
			$mapper->select($select);
		}

		// When returns is "included", the query is relatively simple. We
		// just provide a where clause to limit how many images we're returning
		// based on the entity_ids, exclusions, and container_ids parameters
		if ($returns == 'included') {

			// If the sortorder propery is available, then we need to override
			// the sortorder
			if ($this->object->sortorder) {
				$select = $this->object->_add_find_in_set_column(
					$select,
					$image_key,
					$this->object->sortorder,
					'new_sortorder',
					TRUE
				);
				$sort_direction = $this->object->order_direction == 'ASC' ? 'DESC' : 'ASC';
				$sort_by = 'new_sortorder';
			}
			$mapper->select($select);

			// Filter based on entity_ids selection
			if ($this->object->entity_ids) {
				$mapper->where(array("{$image_key} IN %s", $this->object->entity_ids));
			}

			// Filter based on exclusions selection
			if ($this->object->exclusions) {
				$mapper->where(array("{$image_key} NOT IN %s", $this->object->exclusions));
			}

			// Ensure that no images marked as excluded at the gallery level are returned
            if (empty($this->object->skip_excluding_globally_excluded_images))
			    $mapper->where(array("exclude = %d", 0));
		}

		// When returns is "excluded", it's a little more complicated as the
		// query is the negated form of the "included". entity_ids become the
		// list of exclusions, and exclusions become the list of entity_ids to
		// return. All results we return must be marked as excluded
		elseif ($returns == 'excluded') {

			// If the sortorder propery is available, then we need to override
			// the sortorder
			if ($this->object->sortorder) {
				$select = $this->object->_add_find_in_set_column(
					$select,
					$image_key,
					$this->object->sortorder,
					'new_sortorder',
					TRUE
				);
				$sort_direction = $this->object->order_direction == 'ASC' ? 'DESC' : 'ASC';
				$sort_by = 'new_sortorder';
			}

			// Mark each result as excluded
			$select .= ", 1 AS exclude";
			$mapper->select($select);

			// Is this case, entity_ids become the exclusions
			$exclusions = $this->object->entity_ids;

			// Remove the exclusions always takes precedence over entity_ids, so
			// we adjust the list of ids
			if ($this->object->exclusions) foreach ($this->object->exclusions as $excluded_entity_id) {
				if (($index = array_search($excluded_entity_id, $exclusions)) !== FALSE) {
					unset($exclusions[$index]);
				}
			}

			// Filter based on exclusions selection
			if ($exclusions) {
				$mapper->where(array("{$image_key} NOT IN %s", $exclusions));
			}

			// Filter based on selected exclusions
			else if ($this->object->exclusions) {
				$mapper->where(array("{$image_key} IN %s", $this->object->exclusions));
			}

			// Ensure that images marked as excluded are returned as well
			$mapper->where(array("exclude = 1"));
		}

		// Filter based on containers_ids. Container ids is a little more
		// complicated as it can contain gallery ids or tags
		if ($this->object->container_ids) {

			// Container ids are tags
			if ($source_obj->name == 'tags') {
				$term_ids = $this->object->get_term_ids_for_tags($this->object->container_ids);
				$mapper->where(array("{$image_key} IN %s",get_objects_in_term($term_ids, 'ngg_tag')));
			}

			// Container ids are gallery ids
			else {
				$mapper->where(array("galleryid IN %s", $this->object->container_ids));
			}
		}

		// Filter based on excluded container ids
		if ($this->object->excluded_container_ids) {

			// Container ids are tags
			if ($source_obj->name == 'tags') {
				$term_ids = $this->object->get_term_ids_for_tags($this->object->excluded_container_ids);
				$mapper->where(array("{$image_key} NOT IN %s",get_objects_in_term($term_ids, 'ngg_tag')));
			}

			// Container ids are gallery ids
			else {
				$mapper->where(array("galleryid NOT IN %s", $this->object->excluded_container_ids));
			}
		}

		// Adjust the query more based on what source was selected
		if (in_array($this->object->source, array('recent', 'recent_images')))
		{
			$sort_direction = 'DESC';
			$sort_by = apply_filters('ngg_recent_images_sort_by_column', 'imagedate');
		}
		elseif ($this->object->source == 'random_images' && empty($this->object->entity_ids)) {
            // A gallery with source=random and a non-empty entity_ids is treated as being source=images & image_ids=(entity_ids)
            // In this case however source is random but no image ID are pre-filled.
            //
            // Here we must transform our query from "SELECT * FROM ngg_pictures WHERE gallery_id = X" into something
            // like "SELECT * FROM ngg_pictures WHERE pid IN (SELECT pid FROM ngg_pictures WHERE gallery_id = X ORDER BY RAND())"
            $table_name = $mapper->get_table_name();
            $where_clauses = array();
            $old_where_sql = '';

            // $this->get_entities_count() works by calling count(get_entities()) which means that for random galleries
            // there will be no limit passed to this method -- adjust the $limit now based on the maximum_entity_count
            $max = $this->object->get_maximum_entity_count();
            if (!$limit || (is_numeric($limit) && $limit > $max))
                $limit = $max;

            foreach ($mapper->_where_clauses as $where) {
                $where_clauses[] = '(' . $where . ')';
            }

            if ($where_clauses)
                $old_where_sql = 'WHERE ' . implode(' AND ', $where_clauses);

            $noExtras = '/*NGG_NO_EXTRAS_TABLE*/';

            // TODO: remove this constant. It was only introduced for a short period of time before the setting was
            // TODO: added to Other Options > Misc to allow users easier configuration.
            if (C_NextGen_Settings::get_instance()->use_alternate_random_method
            || (defined('NGG_DISABLE_ORDER_BY_RAND') && NGG_DISABLE_ORDER_BY_RAND))
            {
                // Check if the random image PID have been cached and use them (again) if already found
                $id = $this->object->ID();
                if (!empty(self::$_random_image_ids_cache[$id]))
                {
                    $image_ids = self::$_random_image_ids_cache[$id];
                }
                else {
                    global $wpdb;
                    // Prevent infinite loops: retrieve the image count and if needed just pull in every image available
                    $total = $wpdb->get_var("SELECT COUNT(`pid`) FROM {$wpdb->nggpictures} {$old_where_sql}");
                    $image_ids = array();
                    if ($total <= $limit)
                    {
                        $image_ids = $wpdb->get_col("SELECT `pictures`.`pid` FROM {$wpdb->nggpictures} `pictures` {$old_where_sql} LIMIT {$total}");
                    }
                    else {
                        // Start retrieving random ID from the DB and hope they exist; continue looping until our count is full
                        $segments = ceil($limit / 4);
                        while(count($image_ids) < $limit) {
                            $newID = $this->_query_random_ids_for_cache($segments, $old_where_sql);
                            $image_ids = array_merge(array_unique($image_ids), $newID);
                        }
                    }

                    // Prevent overflow
                    if (count($image_ids) > $limit) {
                        array_splice($image_ids, $limit);
                    }

                    // Give things an extra shake
                    shuffle($image_ids);

                    // Cache these ID in memory so that any attempts to call get_entities() more than once will result
                    // in the same images being retrieved for the duration of that page execution.
                    self::$_random_image_ids_cache[$id] = $image_ids;
                }

                $image_ids = implode(',', $image_ids);

                // Replace the existing WHERE clause with one where aready retrieved "random" PID are included
                $mapper->_where_clauses = array(" {$noExtras} `{$image_key}` IN ({$image_ids}) {$noExtras}");
            }
            else {
                // Replace the existing WHERE clause with one that selects from a sub-query that is randomly ordered
                $sub_where = "SELECT `{$image_key}` FROM `{$table_name}` i {$old_where_sql} ORDER BY RAND() LIMIT {$limit}";
                $mapper->_where_clauses = array(" {$noExtras} `{$image_key}` IN (SELECT `{$image_key}` FROM ({$sub_where}) o) {$noExtras}");
            }
		}

		// Apply a sorting order
		if ($sort_by)
		    $mapper->order_by($sort_by, $sort_direction);

		// Apply a limit
		if ($limit)
		{
			if ($offset)
			    $mapper->limit($limit, $offset);
			else
			    $mapper->limit($limit);
		}

		$results = $mapper->run_query();

		return $results;
	}

    /**
     * @param int $limit
     * @param string $where_sql Must be the full "WHERE x=y" string
     * @return int[]
     */
    public function _query_random_ids_for_cache($limit = 10, $where_sql = '')
    {
        global $wpdb;
        $mod = rand(3, 9);

        if (empty($where_sql))
            $where_sql = 'WHERE 1=1';

        return $wpdb->get_col(
            "SELECT `pictures`.`pid` from {$wpdb->nggpictures} `pictures`
                    JOIN (SELECT CEIL(MAX(`pid`) * RAND()) AS `pid` FROM {$wpdb->nggpictures}) AS `x` ON `pictures`.`pid` >= `x`.`pid`
                    {$where_sql}
                    AND `pictures`.`pid` MOD {$mod} = 0
                    LIMIT {$limit}"
        );
    }

	/**
	 * Gets all gallery and album entities from albums specified, if any
	 * @param stdClass $source_obj
	 * @param int $limit
	 * @param int $offset
	 * @param boolean $id_only
	 * @param array $returns
	 */
	function _get_album_and_gallery_entities($source_obj, $limit=FALSE, $offset=FALSE, $id_only=FALSE, $returns='included')
	{
		// Albums queries and difficult and inefficient to perform due to the
		// database schema. To complicate things, we're returning two different
		// types of entities - galleries, and sub-albums.
		// The user prefixes entity_id's with an 'a' to distinguish album ids
		// from gallery ids. E.g. entity_ids=[1, "a2", 3]
		$album_mapper	= C_Album_Mapper::get_instance();
		$album_key		= $album_mapper->get_primary_key_column();
		$gallery_mapper	= C_Gallery_Mapper::get_instance();
		$gallery_key	= $gallery_mapper->get_primary_key_column();
		$select			= $id_only ? $album_key.", sortorder" : $album_mapper->get_table_name().'.*';
		$retval			= array();

		// If no exclusions are specified, are entity_ids are specified,
		// and we're to return is "included", then we have a relatively easy
		// query to perform - we just fetch each entity listed in
		// the entity_ids field
		if ($returns == 'included' && $this->object->entity_ids && empty($this->object->exclusions)) {
			$retval = $this->object->_entities_to_galleries_and_albums(
				$this->object->entity_ids, $id_only, array(), $limit, $offset
			);
		}

		// It's not going to be easy. We'll start by fetching the albums
		// and retrieving each of their entities
		else {
			// Start the query
			$album_mapper->select($select);

            // Fetch the albums, and find the entity ids of the sub-albums and galleries
            $entity_ids   = array();
            $excluded_ids = array();

			// Filter by container ids. If container_ids === '0' we retrieve all existing gallery_ids and use
            // them as the available entity_ids for comparability with 1.9x
            $container_ids = $this->object->container_ids;
			if ($container_ids)
            {
                if ($container_ids !== array('0') && $container_ids !== array(''))
                {
                    $album_mapper->where(array("{$album_key} IN %s", $container_ids));
                    foreach ($album_mapper->run_query() as $album) {
                        $entity_ids = array_merge($entity_ids, (array) $album->sortorder);
                    }
                }
                else if ($container_ids === array('0') || $container_ids === array('')) {
                    foreach ($gallery_mapper->select($gallery_key)->run_query() as $gallery) {
                        $entity_ids[] = $gallery->$gallery_key;
                    }
                }
			}

			// Break the list of entities into two groups, included entities
			// and excluded entity ids
			// --
			// If a specific list of entity ids have been specified, then
			// we know what entity ids are meant to be included. We can compute
			// the intersect and also determine what entity ids are to be
			// excluded
			if ($this->object->entity_ids) {

				// Determine the real list of included entity ids. Exclusions
				// always take precedence
				$included_ids = $this->object->entity_ids;
				foreach ($this->object->exclusions as $excluded_id) {
					if (($index = array_search($excluded_id, $included_ids)) !== FALSE) {
						unset($included_ids[$index]);
					}
				}
				$excluded_ids = array_diff($entity_ids, $included_ids);
			}

			// We only have a list of exclusions.
			elseif ($this->object->exclusions) {
				$included_ids = array_diff($entity_ids, $this->object->exclusions);
				$excluded_ids = array_diff($entity_ids, $included_ids);
			}

			// We have no entity ids and no exclusions
			else {
				$included_ids = $entity_ids;
			}

			// We've built our two groups. Let's determine how we'll focus on them
			// --
			// We're interested in only the included ids
			if ($returns == 'included')
				$retval = $this->object->_entities_to_galleries_and_albums(
                    $included_ids,
                    $id_only,
                    array(),
                    $limit,
                    $offset
                );

			// We're interested in only the excluded ids
			elseif ($returns == 'excluded')
				$retval = $this->object->_entities_to_galleries_and_albums(
                    $excluded_ids,
                    $id_only,
                    $excluded_ids,
                    $limit,
                    $offset
                );

			// We're interested in both groups
			else {
				$retval = $this->object->_entities_to_galleries_and_albums(
                    $entity_ids,
                    $id_only,
                    $excluded_ids,
                    $limit,
                    $offset
                );
			}
		}

		return $retval;
	}

	/**
	 * Takes a list of entities, and returns the mapped galleries and sub-albums
     *
	 * @param array $entity_ids
     * @param bool $id_only
     * @param array $exclusions
     * @param int $limit
     * @param int $offset
	 * @return array
	 */
	function _entities_to_galleries_and_albums($entity_ids,
                                               $id_only = FALSE,
                                               $exclusions = array(),
                                               $limit = FALSE,
                                               $offset = FALSE)
	{
		$retval			= array();
		$gallery_ids	= array();
		$album_ids		= array();
        $album_mapper	= C_Album_Mapper::get_instance();
		$album_key		= $album_mapper->get_primary_key_column();
		$gallery_mapper	= C_Gallery_Mapper::get_instance();
		$image_mapper   = C_Image_Mapper::get_instance();
		$gallery_key	= $gallery_mapper->get_primary_key_column();
		$album_select	= ($id_only ? $album_key : $album_mapper->get_table_name().'.*').", 1 AS is_album, 0 AS is_gallery, name AS title, albumdesc AS galdesc";
		$gallery_select = ($id_only ? $gallery_key : $gallery_mapper->get_table_name().'.*').", 1 AS is_gallery, 0 AS is_album";

		// Modify the sort order of the entities
		if ($this->object->sortorder) {
			$sortorder = array_intersect($this->object->sortorder, $entity_ids);
			$entity_ids = array_merge($sortorder,array_diff($entity_ids, $sortorder));
		}

		// Segment entity ids into two groups - galleries and albums
		foreach ($entity_ids as $entity_id) {
			if (substr($entity_id, 0, 1) == 'a')
				$album_ids[]	= intval(substr($entity_id, 1));
			else
				$gallery_ids[]	= intval($entity_id);
		}

		// Adjust query to include an exclude property
		if ($exclusions) {
			$album_select = $this->object->_add_find_in_set_column(
				$album_select,
				$album_key,
				$this->object->exclusions,
				'exclude'
			);
			$album_select = $this->object->_add_if_column(
				$album_select,
				'exclude',
				0,
				1
			);
			$gallery_select = $this->object->_add_find_in_set_column(
				$gallery_select,
				$gallery_key,
				$this->object->exclusions,
				'exclude'
			);
			$gallery_select = $this->object->_add_if_column(
				$gallery_select,
				'exclude',
				0,
				1
			);
		}

		// Add sorting parameter to the gallery and album queries
		if ($gallery_ids) {
			$gallery_select = $this->object->_add_find_in_set_column(
				$gallery_select,
				$gallery_key,
				$gallery_ids,
				'ordered_by',
				TRUE
			);
		}
		else {
			$gallery_select .= ", 0 AS ordered_by";
		}
		if ($album_ids) {
			$album_select = $this->object->_add_find_in_set_column(
				$album_select,
				$album_key,
				$album_ids,
				'ordered_by',
				TRUE
			);
		}
		else {
			$album_select .= ", 0 AS ordered_by";
		}

		// Fetch entities
		$galleries	= $gallery_mapper->select($gallery_select)->where(
			array("{$gallery_key} IN %s", $gallery_ids)
		)->order_by('ordered_by', 'DESC')->run_query();
		$counts = $image_mapper->select('galleryid, COUNT(*) as counter')->where(
			array(array("galleryid IN %s", $gallery_ids), array('exclude = %d', 0)))->group_by('galleryid')->run_query(FALSE, FALSE, TRUE);
		$albums		= $album_mapper->select($album_select)->where(
			array("{$album_key} IN %s", $album_ids)
		)->order_by('ordered_by', 'DESC')->run_query();

		// Reorder entities according to order specified in entity_ids
		foreach ($entity_ids as $entity_id) {
			if (substr($entity_id, 0, 1) == 'a') {
                $album = array_shift($albums);
                if ($album) $retval[] = $album;
            }

			else {
                $gallery = array_shift($galleries);
                if ($gallery) {
                	foreach ($counts as $id => $gal_count) {
                		if ($gal_count->galleryid == $gallery->gid) {
		              		$gallery->counter = intval($gal_count->counter);
		              		unset($counts[$id]);
                		}
                	}

                	$retval[] = $gallery;
                }
            }

		}

		// Sort the entities
		if ($this->object->order_by && $this->object->order_by != 'sortorder')
			usort($retval, array(&$this, '_sort_album_result'));
		if ($this->object->order_direction == 'DESC')
			$retval = array_reverse($retval);

		// Limit the entities
		if ($limit)
			$retval = array_slice($retval, $offset, $limit);

		return $retval;
	}

	/**
	 * Returns the total number of entities in this displayed gallery
	 * @param string $returns
	 * @return int
	 */
	function get_entity_count($returns='included')
	{
        $retval = 0;

		// Is this an image query?
		$source_obj = $this->object->get_source();
		if (in_array('image', $source_obj->returns)) {
			$retval =  count($this->object->_get_image_entities($source_obj, FALSE, FALSE, TRUE, $returns));
		}

		// Is this a gallery/album query?
		elseif (in_array('gallery', $source_obj->returns)) {
			$retval = count($this->object->_get_album_and_gallery_entities($source_obj, FALSE, FALSE, TRUE, $returns));
		}

        $max = $this->get_maximum_entity_count();

        if ($retval > $max) {
        	$retval = $max;
        }

        return $retval;
	}

    // Honor the gallery 'maximum_entity_count' setting ONLY when dealing with random & recent galleries. All
    // others will always obey the *global* 'maximum_entity_count' setting.
    function get_maximum_entity_count()
    {
        $max = intval(C_NextGen_Settings::get_instance()->get('maximum_entity_count', 500));

        $sources = C_Displayed_Gallery_Source_Manager::get_instance();
        $source_obj = $this->object->get_source();
        if (in_array($source_obj, array(
            $sources->get('random'),
            $sources->get('random_images'),
            $sources->get('recent'),
            $sources->get('recent_images'))))
            $max = intval($this->object->maximum_entity_count);

        return $max;
    }

	/**
	 * Returns all included entities for the displayed gallery
	 * @param int $limit
	 * @param int $offset
	 * @param boolean $id_only
	 * @return array
	 */
	function get_included_entities($limit=FALSE, $offset=FALSE, $id_only=FALSE)
	{
		return $this->object->get_entities($limit, $offset, $id_only, 'included');
	}

	/**
	 * Adds a FIND_IN_SET call to the select portion of the query, and
	 * optionally defines a dynamic column
	 * @param string $select
	 * @param string $key
	 * @param array $array
	 * @param string $alias
	 * @param boolean $add_column
	 * @return string
	 */
	function _add_find_in_set_column($select, $key, $array, $alias, $add_column=FALSE)
	{
        $array = array_map('intval', $array);
		$set = implode(",", array_reverse($array));
		if (!$select) $select = "1";
		$select .= ", @{$alias} := FIND_IN_SET({$key}, '{$set}')";
		if ($add_column) $select .= " AS {$alias}";
		return $select;
	}

	function _add_if_column($select, $alias, $true=1, $false=0)
	{
		if (!$select) $select = "1";
		$select .= ", IF(@{$alias} = 0, {$true}, {$false}) AS {$alias}";
		return $select;
	}

	/**
	 * Parses the list of parameters provided in the displayed gallery, and
	 * ensures everything meets expectations
	 * @return boolean
	 */
	function _parse_parameters()
	{
		$valid = FALSE;

		// Ensure that the source is valid
        if (C_Displayed_Gallery_Source_Manager::get_instance()->get($this->object->source)) $valid = TRUE;

		// Ensure that exclusions, entity_ids, and sortorder have valid elements.
		// IE likes to send empty array as an array with a single element that
		// has no value
		if ($this->object->exclusions && !$this->object->exclusions[0]) {
			$this->object->exclusions = array();
		}
		if ($this->object->entity_ids && !$this->object->entity_ids[0]) {
			$this->object->entity_ids = array();
		}
		if ($this->object->sortorder && !$this->object->sortorder[0]) {
			$this->object->sortorder = array();
		}

		return $valid;
	}

	/**
	 * Returns a list of term ids for the list of tags
	 * @global wpdb $wpdb
	 * @param array $tags
	 * @return array
	 */
	function get_term_ids_for_tags($tags=FALSE)
	{
		global $wpdb;

        // If no tags were provided, get them from the container_ids
        if (!$tags || !is_array($tags)) $tags = $this->object->container_ids;

		// Convert container ids to a string suitable for WHERE IN
		$container_ids = array();
        if (is_array($tags) && !in_array('all', array_map('strtolower', $tags))) {
			foreach ($tags as $ndx => $container) {
				$container = esc_sql(str_replace('%', '%%', $container));
				$container_ids[]= "'{$container}'";
			}
			$container_ids = implode(',', $container_ids);
		}

		// Construct query
        $query = "SELECT {$wpdb->term_taxonomy}.term_id FROM {$wpdb->term_taxonomy}
                  INNER JOIN {$wpdb->terms} ON {$wpdb->term_taxonomy}.term_id = {$wpdb->terms}.term_id
                  WHERE {$wpdb->term_taxonomy}.term_id = {$wpdb->terms}.term_id
                  AND {$wpdb->term_taxonomy}.taxonomy = %s";
        if (!empty($container_ids))
            $query .= " AND ({$wpdb->terms}.slug IN ({$container_ids}) OR {$wpdb->terms}.name IN ({$container_ids}))";
        $query .= " ORDER BY {$wpdb->terms}.term_id";
        $query = $wpdb->prepare($query, 'ngg_tag');

		// Get all term_ids for each image tag slug
		$term_ids = array();
        $results = $wpdb->get_results($query);
        if (is_array($results) && !empty($results))
        {
            foreach ($results as $row) {
                $term_ids[] = $row->term_id;
            }
        }

		return $term_ids;
	}

	/**
	 * Sorts the results of an album query
	 * @param stdClass $a
	 * @param stdClass $b
     * @return int
	 */
	function _sort_album_result($a, $b)
	{
		$key = $this->object->order_by;
		if (!isset($a->$key) || !isset($b->$key))
		    return 0;
		return strcmp($a->$key, $b->$key);
	}
}

/**
 * Provides instance methods useful for working with the C_Displayed_Gallery
 * model
 */
class Mixin_Displayed_Gallery_Instance_Methods extends Mixin
{
	function get_entity()
	{
		$entity = $this->call_parent('get_entity');
		unset($entity->post_author);
		unset($entity->post_date);
		unset($entity->post_date_gmt);
		unset($entity->post_title);
		unset($entity->post_excerpt);
		unset($entity->post_status);
		unset($entity->comment_status);
		unset($entity->ping_status);
		unset($entity->post_name);
		unset($entity->to_ping);
		unset($entity->pinged);
		unset($entity->post_modified);
		unset($entity->post_modified_gmt);
		unset($entity->post_parent);
		unset($entity->guid);
		unset($entity->post_type);
		unset($entity->post_mime_type);
		unset($entity->comment_count);
		unset($entity->filter);
		unset($entity->post_content_filtered);

		return $entity;
	}


	/**
	 * Gets the display type object used in this displayed gallery
	 * @return C_Display_Type
	 */
	function get_display_type()
	{
        return C_Display_Type_Mapper::get_instance()
            ->find_by_name($this->object->display_type, TRUE);
	}

	/**
	 * Gets the corresponding source instance
	 * @return stdClass
	 */
	function get_source()
	{
        return C_Displayed_Gallery_Source_Manager::get_instance()->get($this->object->source);
	}

	/**
	 * Returns the galleries queries in this displayed gallery
	 * @return array
	 */
	function get_galleries()
	{
		$retval = array();
		if (($source = $this->object->get_source())) {
			if (in_array('image', $source->returns)) {
				$mapper			= C_Gallery_Mapper::get_instance();
				$gallery_key	= $mapper->get_primary_key_column();
				$mapper->select();
				if ($this->object->container_ids) {
					$mapper->where(array("{$gallery_key} IN %s", $this->object->container_ids));
				}
				$retval			= $mapper->run_query();
			}
		}
		return $retval;
	}

	/**
	 * Gets albums queried in this displayed gallery
	 * @return array
	 */
	function get_albums()
	{
		$retval = array();
		if (($source = $this->object->get_source())) {
			if (in_array('album', $source->returns)) {
				$mapper		= C_Album_Mapper::get_instance();
				$album_key	= $mapper->get_primary_key_column();
				if ($this->object->container_ids) {
					$mapper->select()->where(array("{$album_key} IN %s", $this->object->container_ids));
				}
				$retval		= $mapper->run_query();
			}
		}
		return $retval;
	}

    /**
     * Returns a transient for the displayed gallery
     * @return string
     */
    function to_transient()
    {
	    $params = $this->object->get_entity();
	    unset($params->transient_id);

	    $key = C_Photocrati_Transient_Manager::create_key('displayed_galleries', $params);
		if (is_null(C_Photocrati_Transient_Manager::fetch($key, NULL))) {
			C_Photocrati_Transient_Manager::update($key, $params, NGG_DISPLAYED_GALLERY_CACHE_TTL);
		}

		$this->object->transient_id = $key;
		if (!$this->object->id()) $this->object->id($key);

        return $key;
    }


    /**
     * Applies the values of a transient to this object
     * @param string $transient_id
     * @return bool
     */
    function apply_transient($transient_id=NULL)
    {
		$retval = FALSE;

		if (!$transient_id && isset($this->object->transient_id)) $transient_id = $this->object->transient_id;

		if ($transient_id && ($transient = C_Photocrati_Transient_Manager::fetch($transient_id, FALSE))) {

			// Ensure that the transient is an object, not array
			if (is_array($transient)) {
				$obj = new stdClass();
				foreach ($transient as $key => $value) $obj->$key = $value;
				$transient = $obj;
			}
			$this->object->_stdObject = $transient;

			// Ensure that the display settings are an array
			$this->object->display_settings = $this->_object_to_array($this->object->display_settings);

			// Ensure that we have the most accurate transient id
			$this->object->transient_id = $transient_id;
			if (!$this->object->id()) $this->object->id($transient_id);
			$retval = TRUE;
		}
	    else {
		    unset($this->object->transient_id);
		    unset($this->object->_stdObject->transient_id);
		    $this->object->to_transient();
	    }

		return $retval;
    }

	public function _object_to_array($object)
	{
		$retval = $object;

		if (is_object($retval)) {
			$retval = get_object_vars($object);
		}

		if (is_array($retval)) {
			foreach ($retval as $key => $val) {
				if (is_object($val)) $retval[$key] = $this->_object_to_array($val);
			}
		}

		return $retval;
	}
}
