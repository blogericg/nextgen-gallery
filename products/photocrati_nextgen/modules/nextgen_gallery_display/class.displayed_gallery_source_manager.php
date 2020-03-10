<?php

class C_Displayed_Gallery_Source_Manager
{
    private $_sources               = array();
    private $_entity_types          = array();
    private $_registered_defaults   = array();

    /* @var C_Displayed_Gallery_Source_Manager */
    static $_instance = NULL;

    /**
     * @return C_Displayed_Gallery_Source_Manager
     */
    static function get_instance()
    {
        if (!isset(self::$_instance))
        {
            $klass = get_class();
            self::$_instance = new $klass();
        }
        return self::$_instance;
    }

    function register_defaults()
    {
        // Entity types must be registered first!!!
        // ----------------------------------------
        $this->register_entity_type('gallery',  'galleries');
        $this->register_entity_type('image',    'images');
        $this->register_entity_type('album',    'albums');

        // Galleries
        $galleries          = new stdClass;
        $galleries->name    = 'galleries';
        $galleries->title   = __('Galleries', 'nggallery');
        $galleries->aliases = array('gallery', 'images', 'image');
        $galleries->returns = array('image');
        $this->register($galleries->name, $galleries);

        // Albums
        $albums             = new stdClass;
        $albums->name       = 'albums';
        $albums->title      = __('Albums', 'nggallery');
        $albums->aliases    = array('album');
        $albums->returns    = array('album', 'gallery');
        $this->register($albums->name, $albums);

        // Tags
        $tags               = new stdClass;
        $tags->name         = 'tags';
        $tags->title        = __('Tags', 'nggallery');
        $tags->aliases      = array('tag', 'image_tags', 'image_tag');
        $tags->returns      = array('image');
        $this->register($tags->name, $tags);

        // Random Images;
        $random             = new stdClass;
        $random->name       = 'random_images';
        $random->title      = __('Random Images', 'nggallery');
        $random->aliases    = array('random', 'random_image');
        $random->returns    = array('image');
        $this->register($random->name, $random);

        // Recent Images
        $recent             = new stdClass;
        $recent->name       = 'recent_images';
        $recent->title      = __('Recent Images', 'nggallery');
        $recent->aliases    = array('recent', 'recent_image');
        $recent->returns    = array('image');
        $this->register($recent->name, $recent);

        $this->_registered_defaults = TRUE;
    }

    function register($name, $properties)
    {
        // We'll use an object to represent the source
        $object = $properties;
        if (!is_object($properties)) {
            $object = new stdClass;
            foreach ($properties as $k=>$v) $object->$k = $v;
        }

        // Set default properties
        $object->name = $name;
        if (!isset($object->title))   $object->title   = $name;
        if (!isset($object->returns)) $object->returns = array();
        if (!isset($object->aliases)) $object->aliases = array();

        // Add internal reference
        $this->_sources[$name] = $object;
        foreach ($object->aliases as $name) {
            $this->_sources[$name] = $object;
        }
    }

    function register_entity_type()
    {
        $aliases = func_get_args();
        $name = array_shift($aliases);
        $this->_entity_types[] = $name;
        foreach ($aliases as $alias) $this->_entity_types[$alias] = $name;
    }

    function deregister($name)
    {
        if (($source = $this->get($name))) {
            unset($this->_sources[$name]);
            foreach ($source->aliases as $alias) unset($this->_sources[$alias]);
        }
    }

    function deregister_entity_type($name)
    {
        unset($this->_entity_types[$name]);
    }

    function get($name_or_alias)
    {
        if (!$this->_registered_defaults) $this->register_defaults();

        $retval = NULL;

        if (isset($this->_sources[$name_or_alias])) $retval = $this->_sources[$name_or_alias];

        return $retval;
    }

    function get_entity_type($name)
    {
        if (!$this->_registered_defaults) $this->register_defaults();
        $found = array_search($name, $this->_entity_types);
        if ($found)
            return $this->_entity_types[$found];
        else
            return NULL;
    }

    function get_all()
    {
        if (!$this->_registered_defaults) $this->register_defaults();
        $retval = array();
        foreach (array_values($this->_sources) as $source_obj) {
            if (!in_array($source_obj, $retval)) $retval[] = $source_obj;
        }
        usort($retval, array(&$this, '__sort_by_name'));
        return $retval;
    }

    function __sort_by_name($a, $b)
    {
        return strcmp($a->name, $b->name);
    }

    function get_all_entity_types()
    {
        if (!$this->_registered_defaults) $this->register_defaults();
        return array_unique(array_values($this->_entity_types));
    }

    function is_registered($name)
    {
        return !is_null($this->get($name));
    }

    function is_valid_entity_type($name)
    {
        return !is_null($this->get_entity_type($name));
    }

    function deregister_all()
    {
        $this->_sources = array();
        $this->_entity_types = array();
        $this->_registered_defaults = FALSE;
    }

    function is_compatible($source, $display_type)
    {
        $retval = FALSE;

        if (($source = $this->get($source->name)))
        {
            // Get the real entity type names for the display type
            $display_type_entity_types = array();
            foreach ($display_type->entity_types as $type) {
                $result = $this->get_entity_type($type);
                if ($result)
                    $display_type_entity_types[] = $result;
            }

            foreach ($source->returns as $entity_type) {
                if (in_array($entity_type, $display_type_entity_types, TRUE))
                {
                    $retval = TRUE;
                    break;
                }
            }
        }

        return $retval;
    }
}