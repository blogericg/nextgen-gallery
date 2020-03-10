<?php

/**
 * Provides instance methods for C_CustomTable_DataMapper_Driver
 * @mixin C_CustomTable_DataMapper_Driver
 */
class C_CustomTable_DataMapper_Driver_Mixin extends Mixin
{
	/**
	 * Gets the name of the primary key column
	 * @return string
	 */
	function get_primary_key_column()
	{
		return $this->object->_primary_key_column;
	}


	/**
	 * Selects which fields to collect from the table.
	 * NOTE: Not protected from SQL injection - DO NOT let your users specify DB columns
	 * @param string $fields
     * @return object
	 */
	function select($fields=NULL)
	{
		// Create a fresh slate
		$this->object->_init();
		if (!$fields OR $fields == '*') $fields = $this->get_table_name().'.*';
		$this->object->_select_clause = "SELECT {$fields}";

		return $this->object;
	}

	/**
	 * Determines whether we're going to execute a SELECT statement
	 * @return boolean
	 */
	function is_select_statement()
	{
		return ($this->object->_select_clause) ? TRUE : FALSE;
	}

	/**
	 * Determines if we're going to be executing a DELETE statement
	 * @return bool
	 */
	function is_delete_statement()
	{
		return $this->object->_delete_clause ? TRUE : FALSE;
	}


	/**
	 * Start a delete statement
	 */
	function delete()
	{
		// Create a fresh slate
		$this->object->_init();
		$this->object->_delete_clause = "DELETE";
		return $this->object;
	}


	/**
	 * Orders the results of the query
	 * This method may be used multiple of times to order by more than column
	 * @param $order_by
	 * @param $direction
     * @return object
	 */
	function order_by($order_by, $direction='ASC')
	{
		// We treat the rand() function as an exception
		if (preg_match("/rand\(\s*\)/", $order_by)) {
			$order = 'rand()';
		}
		else {
			$order_by	= $this->object->_clean_column($order_by);

			// If the order by clause is a column, then it should be backticked
			if ($this->object->has_column($order_by)) $order_by = "`{$order_by}`";

			$direction	= $this->object->_clean_column($direction);
			$order		= "{$order_by} {$direction}";
		}

		$this->object->_order_clauses[] = $order;

		return $this->object;
	}

	/**
	 * Specifies a limit and optional offset
	 * @param integer $max
	 * @param integer $offset
     * @return object
	 */
	function limit($max, $offset=0)
	{
		if ($offset)
			$limit = $this->_wpdb()->prepare("LIMIT %d, %d", max(0, $offset), $max);
		else
			$limit = $this->_wpdb()->prepare("LIMIT %d", max(0, $max));
		if ($limit) $this->object->_limit_clause = $limit;

		return $this->object;
	}


    /**
     * Specifics a group by clause for one or more columns
     * @param array|string $columns
     * @return object
     */
    function group_by($columns=array())
    {
        if (!is_array($columns)) $columns = array($columns);
        $this->object->_group_by_columns = array_merge($this->object->_group_by_columns, $columns);
        return $this->object;
    }


	/**
	 * Adds a where clause to the driver
	 * @param array $where_clauses
	 * @param string $join
	 */
	function _add_where_clause($where_clauses, $join)
	{
		$clauses = array();

		foreach ($where_clauses as $clause) {
			extract($clause);
			if ($this->object->has_column($column)) $column = "`{$column}`";
			if (!is_array($value)) $value = array($value);
			foreach ($value as $index => $v) {
				$v = $clause['type'] == 'numeric' ? $v : "'{$v}'";
				$value[$index] = $v;
			}
			if ($compare == 'BETWEEN') {
				$value = "{$value[0]} AND {$value[1]}";
			}
			else {
				$value = implode(', ', $value);
				if (strpos($compare, 'IN') !== FALSE) $value = "({$value})";
			}

			$clauses[] = "{$column} {$compare} {$value}";
		}

		$this->object->_where_clauses[] = implode(" {$join} ", $clauses);
	}


	/**
	 * Returns the total number of entities known
	 * @return int
	 */
	function count()
	{
		$retval = 0;

		$key = $this->object->get_primary_key_column();
		$results = $this->object->run_query(
			"SELECT COUNT(`{$key}`) AS `{$key}` FROM `{$this->object->get_table_name()}`"
		);
		if ($results && isset($results[0]->$key))
			$retval = (int)$results[0]->$key;

		return $retval;
	}

	/**
	 * Returns the generated SQL query to be executed
     * @param bool $no_entities Default = false
	 * @return string
	 */
	function get_generated_query($no_entities=FALSE)
	{
		$sql = array();

		if	   ($this->object->is_select_statement()) $sql[] = $this->object->_select_clause;
		elseif ($this->object->is_delete_statement()) $sql[] = $this->object->_delete_clause;
		$sql[] = 'FROM `'.$this->object->get_table_name().'`';
		$where_clauses = array();
		foreach ($this->object->_where_clauses as $where) {
			$where_clauses[] = '('.$where.')';
		}
		if ($where_clauses) $sql[] = 'WHERE '.implode(' AND ', $where_clauses);

		if ($this->object->is_select_statement()) {
			if ($this->object->_group_by_columns) $sql[] = 'GROUP BY '.implode(', ', $this->object->_group_by_columns);
			if ($this->object->_order_clauses) $sql[] = 'ORDER BY '.implode(', ', $this->object->_order_clauses);
			if ($this->object->_limit_clause) $sql[] = $this->object->_limit_clause;
		}
		return implode(' ', $sql);
	}


	/**
	 * Run the query
	 * @param string|bool $sql (optional) run the specified SQL
     * @param object|bool $model (optional)
     * @param bool $no_entities (optional)
     * @return array
	 */
	function run_query($sql=FALSE, $model=FALSE, $no_entities=FALSE)
	{
		$results = FALSE;
        $retval = array();

		// Or generate SQL query
		if (!$sql)
            $sql = $this->object->get_generated_query($no_entities);

		// If we have a SQL statement to execute, then heck, execute it!
		if ($sql)
        {
            if ($this->object->debug) {
				var_dump($sql);
			}

            // Try getting the result from cache first
            if ($this->is_select_statement() && $this->object->_use_cache) {
	            $results = $this->object->get_from_cache($sql);
            }
        }

        if (!$results) {
            $this->_wpdb()->query($sql);
            $results = $this->_wpdb()->last_result;
            if ($this->is_select_statement()) {
	            $this->object->cache($sql, $results);
            }
        }

        if ($results) {
            $retval = array();
            // For each row, create an entity, update it's properties, and add it to the result set
            if ($no_entities) $retval = $results;
            else {
                $id_field = $this->get_primary_key_column();
                foreach ($results as $row) {
                    if ($row) {
                        if (isset($row->$id_field)) {
	                        if ($model) $retval[] = $this->object->convert_to_model($row);
                            else $retval[] = $this->object->_convert_to_entity($row);
                        }
                    }
                }
            }
        }
        elseif ($this->object->debug) {
            var_dump("No entities returned from query");
        }

        // Just a safety check
        if (!$retval) $retval = array();

		return $retval;
	}

	/**
	 * Stores the entity
	 * @param object $entity
     * @return bool|object
	 */
	function _save_entity($entity)
	{
		$retval = FALSE;

		unset($entity->id_field);
		$primary_key = $this->object->get_primary_key_column();
		if (isset($entity->$primary_key) && $entity->$primary_key > 0) {
			if($this->object->_update($entity)) $retval = intval($entity->$primary_key);
		}
		else {
			$retval = $this->object->_create($entity);
			if ($retval) {
				$new_entity = $this->object->find($retval);
				foreach ($new_entity as $key => $value) $entity->$key = $value;
			}
		}
		$entity->id_field = $primary_key;

		// Clean cache
		if ($retval) {
			$this->object->_cache = array();
		}

		return $retval;
	}

	/**
	 * Converts an entity to something suitable for inserting into a database column
	 * @param object $entity
	 * @return array
	 */
	function _convert_to_table_data($entity)
	{
		$data = (array) $entity;
		foreach ($data as $key => $value) {
			if (is_array($value)) $data[$key] = $this->object->serialize($value);
		}

		return $data;
	}


	/**
	 * Destroys/deletes an entity
	 * @param object|C_DataMapper_Model|int $entity
	 * @return boolean
	 */
	function destroy($entity)
	{
		$retval = FALSE;
		$key = $this->object->get_primary_key_column();

		// Find the id of the entity
		if (is_object($entity) && isset($entity->$key)) {
			$id = (int)$entity->$key;
		}
		else {
			$id = (int)$entity;
		}

		// If we have an ID, then delete the post
		if (is_numeric($id)) {
			$sql = $this->object->_wpdb()->prepare(
		      "DELETE FROM `{$this->object->get_table_name()}` WHERE {$key} = %s",
			  $id
			);
			$retval = $this->object->_wpdb()->query($sql);
		}

		return $retval;
	}

	/**
	 * Creates a new record in the database
	 * @param object $entity
	 * @return boolean
	 */
	function _create($entity)
	{
		$retval = FALSE;
		$id =  $this->object->_wpdb()->insert(
			$this->object->get_table_name(),
			$this->object->_convert_to_table_data($entity)
		);
		if ($id) {
			$key = $this->object->get_primary_key_column();
			$retval = $entity->$key = intval($this->object->_wpdb()->insert_id);
		}
		return $retval;
	}

	/**
	 * Updates a record in the database
	 * @param object $entity
     * @return int|bool
	 */
	function _update($entity)
	{
		$key = $this->object->get_primary_key_column();

		return $this->object->_wpdb()->update(
			$this->object->get_table_name(),
			$this->object->_convert_to_table_data($entity),
			array($key => $entity->$key)
		);
	}

	/**
	 * Fetches the last row
	 * @param array $conditions
	 * @return object
	 */
	function find_last($conditions=array(), $model=FALSE)
	{
		$retval = NULL;

		// Get row number for the last row
		$this->select()->limit(1)->order_by('date', 'DESC');
		if ($conditions) $this->where_and($conditions);

		$results = $this->run_query();
		if ($results) {
			$retval = $model? $this->object->convert_to_model($results[0]) : $results[0];
		}

		return $retval;
	}

	function _add_column($column_name, $datatype, $default_value=NULL)
	{
		$sql = "ALTER TABLE `{$this->get_table_name()}` ADD COLUMN `{$column_name}` {$datatype}";
		if ($default_value) {
			if (is_string($default_value)) $default_value = str_replace("'", "\\'", $default_value);
			$sql .= " NOT NULL DEFAULT " . (is_string($default_value) ? "'{$default_value}" : "{$default_value}");
		}
		
		$return = $this->object->_wpdb()->query($sql) ? TRUE : FALSE;
  	$this->object->update_columns_cache();
  	return $return;
	}

	function _remove_column($column_name)
	{
		$sql = "ALTER TABLE `{$this->get_table_name()}` DROP COLUMN `{$column_name}`";
		$return = $this->object->_wpdb()->query($sql) ? TRUE : FALSE;
  	$this->object->update_columns_cache();
  	return $return;
	}

	function get_column_names()
	{
		return array_keys($this->object->_columns);
	}

	/**
	 * Migrates the schema of the database
	 */
	function migrate()
	{
		if (!$this->object->_columns) {
			throw new E_ColumnsNotDefinedException("Columns not defined for {$this->get_table_name()}");
		}

        $added = FALSE;
        $removed = FALSE;

		// Add any missing columns
		foreach ($this->object->_columns as $key => $properties) {
			if (!in_array($key, $this->object->_table_columns)) {
				if ($this->object->_add_column($key, $properties['type'], $properties['default_value'])) $added = TRUE;
			}
		}

		// Remove any columns not defined
//		foreach ($this->object->_table_columns as $key) {
//			if (!isset($this->object->_columns[$key])) {
//				$this->object->_remove_column($key);
//                $removed = TRUE;
//			}
//		}

		if ($added OR $removed) {
            // var_dump($this->object->_table_columns);
            $this->object->lookup_columns();
            // var_dump($added, $removed);
        }
	}


	function _init()
	{
		$this->object->_where_clauses = array();
		$this->object->_order_clauses = array();
        $this->object->_group_by_columns = array();
		$this->object->_limit_clause = '';
		$this->object->_select_clause = '';
	}
}

/**
 * Class C_CustomTable_DataMapper_Driver
 * @mixin C_CustomTable_DataMapper_Driver_Mixin
 */
class C_CustomTable_DataMapper_Driver extends C_DataMapper_Driver_Base
{
	/**
	 * The WordPress Database Connection
	 * @var wpdb
	 */
	var $_where_clauses = array();
	var $_order_clauses = array();
    var $_group_by_columns = array();
	var $_limit_clause = '';
	var $_select_clause = '';
	var $_delete_clause = '';
    public $_use_cache = TRUE;

	function define($object_name=FALSE, $context=FALSE)
	{
		parent::define($object_name, $context);
		$this->add_mixin('C_CustomTable_DataMapper_Driver_Mixin');
		$this->implement('I_CustomTable_DataMapper');
	}

	function initialize($object_name=FALSE)
	{
		parent::initialize();
		if (!isset($this->_primary_key_column))
			$this->_primary_key_column = $this->_lookup_primary_key_column();
		$this->migrate();
	}

	/**
	 * Returns the database connection object for WordPress
	 * @global wpdb $wpdb
	 * @return wpdb
	 */
	function _wpdb()
	{
		global $wpdb;
		return $wpdb;
	}

	/**
	 * Looks up the primary key column for this table
	 */
	function _lookup_primary_key_column()
	{
		$key = $this->_wpdb()->get_row("SHOW INDEX FROM {$this->get_table_name()} WHERE Key_name='PRIMARY'", ARRAY_A);
		if (!$key) throw new Exception("Please specify the primary key for {$this->get_table_name ()}");
		return $key['Column_name'];
	}
}
