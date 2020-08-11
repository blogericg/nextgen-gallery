<?php

/**
 * Class C_MVC_View
 * @mixin Mixin_Mvc_View_Instance_Methods
 * @implements I_MVC_View
 */
class C_MVC_View extends C_Component
{
    var $_template = '';
    var $_engine   = '';
    var $_params   = array();
    var $_queue    = array();

	function __construct($template, $params = array(), $engine = 'php', $context = FALSE)
	{
		$this->_template = $template;
		$this->_params   = (array) $params;
        $this->_engine   = $engine;

        $context = $context ? array_unique([$context, $template]) : $template;

		parent::__construct($context);
	}
    
    function define($context = FALSE)
    {
        parent::define($context);
        $this->implement('I_MVC_View');
        $this->add_mixin('Mixin_Mvc_View_Instance_Methods');
    }
}

class Mixin_Mvc_View_Instance_Methods extends Mixin
{
    /**
     * Returns the variables to be used in the template
     * @return array
     */
    function get_template_vars()
    {
        $retval = array();
     
        foreach ($this->object->_params as $key => $value) {
           if (strpos($key, '_template') === 0) {
              $value = $this->object->get_template_abspath($value);
           }
           $retval[$key] = $value;
        }
        
        return $retval;
    }
    
    /**
     * @param string $value (optional)
     * @return string
     */
    function get_template_abspath($value=NULL)
    {
        if (!$value)
            $value = $this->object->_template;
        
        if (strpos($value, DIRECTORY_SEPARATOR) !== FALSE && @file_exists($value)) {
            // key is already abspath
        }
        else
            $value = $this->object->find_template_abspath($value);
        
        return $value;
    }

    /**
     * Renders the view (template)
     * @param bool $return (optional)
     * @return string|NULL
     */
    function render($return = FALSE)
    {
        $element = $this->object->render_object();

        $content = $this->object->rasterize_object($element);

        if (!$return) {
            echo $content;
        }

        return $content;
    }
    
    function render_object()
    {
        // We use underscores to prefix local variables to avoid conflicts wth
        // template vars
        $__element = $this->start_element($this->object->_template, 'template', $this->object);

        $template_vars = $this->object->get_template_vars();
        extract($template_vars);
      
        include($this->object->get_template_abspath());
        
        $this->end_element();
      
        return $__element;
    }

    function rasterize_object($element)
    {
    	return $element->rasterize();
    }

    function start_element($id, $type = null, $context = null)
    {
    	if ($type == null)
    	{
    		$type = 'element';
    	}
    	
    	$count = count($this->object->_queue);
    	$element = new C_MVC_View_Element($id, $type);
    	
    	if ($context != null)
    	{
    		if (!is_array($context))
    		{
    			$context = array('object' => $context);
    		}
    		
    		foreach ($context as $context_name => $context_value)
    		{
    			$element->set_context($context_name, $context_value);
    		}
    	}
    	
    	$this->object->_queue[] = $element;
    	
    	if ($count > 0)
    	{
    		$old_element = $this->object->_queue[$count - 1];
    		
    		$content = ob_get_contents();
    		ob_clean();
    		
    		$old_element->append($content);
    		$old_element->append($element);
    	}
    	
    	ob_start();
    	
    	return $element;
    }
    
    function end_element()
    {
    	$content = ob_get_clean();
    	
    	$element = array_pop($this->object->_queue);
    	
    	if ($content != null)
    	{
    		$element->append($content);
    	}
    	
    	return $element;
    }
    
    /**
     * Renders a sub-template for the view
     * @param string $__template
     * @param array $__params
     * @param bool $__return Unused
     * @return NULL
     */
    function include_template($__template, $__params = null, $__return=FALSE)
    {
      // We use underscores to prefix local variables to avoid conflicts wth
      // template vars
			if ($__params == null) {
				$__params = array();
			}

			$__params['template_origin'] = $this->object->_template;

			$__target = $this->object->get_template_abspath($__template);
			$__origin_target = $this->object->get_template_abspath($this->object->_template);
			$__image_before_target = $this->object->get_template_abspath('photocrati-nextgen_gallery_display#image/before');
			$__image_after_target = $this->object->get_template_abspath('photocrati-nextgen_gallery_display#image/after');

			if ($__origin_target != $__target)
			{
				if ($__target == $__image_before_target)
				{
					$__image = isset($__params['image']) ? $__params['image'] : null;
					
					$this->start_element('nextgen_gallery.image_panel', 'item', $__image);
				}
				
				if ($__target == $__image_after_target)
				{
					$this->end_element();
				}
				
				extract($__params);
	
				include($__target);
				
				if ($__target == $__image_before_target)
				{
					$__image = isset($__params['image']) ? $__params['image'] : null;
					
					$this->start_element('nextgen_gallery.image', 'item', $__image);
				}
				
				if ($__target == $__image_after_target)
				{
					$this->end_element();
				}
			}

			return NULL;
    }
    
    
    /**
     * Gets the absolute path of an MVC template file
     *
     * @param string $path
     * @param string|false $module (optional)
     * @return string
     */
   function find_template_abspath($path, $module=FALSE)
   {
       $fs       = C_Fs::get_instance();
       $settings = C_NextGen_Settings::get_instance();

       // We also accept module_name#path, which needs parsing.
       if (!$module)
           list($path, $module) = $fs->parse_formatted_path($path);

       // Append the suffix
       $path = $path . '.php';

	   // First check if the template is in the override dir
	   if (!($retval = $this->object->get_template_override_abspath($module, $path))) {
		   $retval = $fs->join_paths(
			   $this->object->get_registry()->get_module_dir($module),
			   $settings->mvc_template_dirname,
			   $path
		   );
	   }

       if (!@file_exists($retval))
           throw new RuntimeException("{$retval} is not a valid MVC template");

       return $retval;
   }


	function get_template_override_dir($module_id = NULL)
	{
		$root = trailingslashit(path_join(WP_CONTENT_DIR, 'ngg'));
		if (!@file_exists($root) && is_writable(trailingslashit(WP_CONTENT_DIR)))
		    wp_mkdir_p($root);

		$modules = trailingslashit(path_join($root, 'modules'));

		if (!@file_exists($modules) && is_writable($root))
		    wp_mkdir_p($modules);

		if ($module_id)
		{
			$module_dir = trailingslashit(path_join($modules, $module_id));
			if (!@file_exists($module_dir) && is_writable($modules))
			    wp_mkdir_p($module_dir);

			$template_dir = trailingslashit(path_join($module_dir, 'templates'));
			if (!@file_exists($template_dir) && is_writable($module_dir))
			    wp_mkdir_p($template_dir);

			return $template_dir;
		}

		return $modules;
	}

	function get_template_override_abspath($module, $filename)
	{
		$fs       = C_Fs::get_instance();
		$retval   = NULL;

		$abspath = $fs->join_paths($this->object->get_template_override_dir($module), $filename);
		if (@file_exists($abspath)) $retval = $abspath;

		return $retval;
	}

    /**
     * Adds a template parameter
     * @param $key
     * @param $value
     */
    function set_param($key, $value)
   {
       $this->object->_params[$key] = $value;
   }


    /**
     * Removes a template parameter
     * @param $key
     */
    function remove_param($key)
   {
       unset($this->object->_params[$key]);
   }

    /**
     * Gets the value of a template parameter
     * @param $key
     * @param null $default
     * @return mixed
     */
    function get_param($key, $default=NULL)
   {
       if (isset($this->object->_params[$key])) {
           return $this->object->_params[$key];
       }
       else return $default;
   }
}
