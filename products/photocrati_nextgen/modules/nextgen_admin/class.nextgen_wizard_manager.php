<?php

/**
 * Class C_NextGEN_Wizard
 */
class C_NextGEN_Wizard
{
	var $_id = null;
	var $_active = false;
	var $_priority = 100;
	var $_data = array();
	var $_steps = array();
	var $_current_step = null;
	var $_view = null;
	
	function __construct($id)
	{
		$this->_id = $id;
	}
	
	function get_id()
	{
		return $this->_id;
	}
	
	function is_active()
	{
		return $this->_active;
	}
	
	function set_active($active)
	{
		$this->_active = $active;
	}
	
	function get_priority()
	{
		return $this->_priority;
	}
	
	function set_priority($priority)
	{
		$this->_priority = $priority;
	}
	
	function is_completed()
	{
		if (isset($this->_data['state']))
			return $this->_data['state'] == 'completed';
		
		return false;
	}
	
	function set_completed()
	{
		$this->_data['state'] = 'completed';
	}
	
	function is_cancelled()
	{
		if (isset($this->_data['state']))
			return $this->_data['state'] == 'cancelled';
		
		return false;
	}
	
	function set_cancelled()
	{
		$this->_data['state'] = 'cancelled';
	}
	
	function add_step($step_id, $label = null, $properties = null)
	{
		$step = array(
			'label' => $label,
			'target_anchor' => 'top center',
			'popup_anchor' => 'bottom center',
			'target_wait' => '0',
		);
		
		if ($properties != null) {
			$step = array_merge($step, $properties);
		}
		
		$this->_steps[$step_id] = $step;
	}
	
	function get_step_list()
	{
		return array_keys($this->_steps);
	}
	
	function get_step_property($step_id, $prop_name)
	{
		if (isset($this->_steps[$step_id][$prop_name])) {
			return $this->_steps[$step_id][$prop_name];
		}
		
		return null;
	}
	
	function set_step_property($step_id, $prop_name, $prop_value)
	{
		if (!isset($this->_steps[$step_id])) {
			$this->add_step($step_id);
		}
		
		if (isset($this->_steps[$step_id])) {
			$this->_steps[$step_id][$prop_name] = $prop_value;
		}
	}
	
	function get_step_label($step_id)
	{
		return $this->get_step_property($step_id, 'label');
	}
	
	function set_step_label($step_id, $label)
	{
		$this->set_step_property($step_id, 'label', $label);
	}
	
	function get_step_text($step_id)
	{
		return $this->get_step_property($step_id, 'text');
	}
	
	function set_step_text($step_id, $text)
	{
		$this->set_step_property($step_id, 'text', $text);
	}
	
	function get_step_target_anchor($step_id)
	{
		return $this->get_step_property($step_id, 'target_anchor');
	}
	
	function set_step_target_anchor($step_id, $anchor)
	{
		$this->set_step_property($step_id, 'target_anchor', $anchor);
	}
	
	function get_step_target_wait($step_id)
	{
		return $this->get_step_property($step_id, 'target_wait');
	}
	
	function set_step_target_wait($step_id, $wait)
	{
		$this->set_step_property($step_id, 'target_wait', $wait);
	}
	
	function get_step_optional($step_id)
	{
		return $this->get_step_property($step_id, 'optional');
	}
	
	function set_step_optional($step_id, $optional)
	{
		$this->set_step_property($step_id, 'optional', $optional);
	}
	
	function get_step_lazy($step_id)
	{
		return $this->get_step_property($step_id, 'lazy');
	}
	
	function set_step_lazy($step_id, $lazy)
	{
		$this->set_step_property($step_id, 'lazy', $lazy);
	}
	
	function get_step_context($step_id)
	{
		return $this->get_step_property($step_id, 'context');
	}
	
	function set_step_context($step_id, $context)
	{
		$this->set_step_property($step_id, 'context', $context);
	}
	
	function get_step_popup_anchor($step_id)
	{
		return $this->get_step_property($step_id, 'popup_anchor');
	}
	
	function set_step_popup_anchor($step_id, $anchor)
	{
		$this->set_step_property($step_id, 'popup_anchor', $anchor);
	}
	
	function get_step_target($step_id)
	{
		return $this->get_step_property($step_id, 'target');
	}
	
	function set_step_target($step_id, $target, $target_anchor = null, $popup_anchor = null)
	{
		$this->set_step_property($step_id, 'target', $target);
		
		if ($target_anchor != null)
			$this->set_step_target_anchor($step_id, $target_anchor);
		
		if ($popup_anchor != null)
			$this->set_step_popup_anchor($step_id, $popup_anchor);
	}
	
	function get_step_view($step_id)
	{
		return $this->get_step_property($step_id, 'view');
	}
	
	function set_step_view($step_id, $view)
	{
		$this->set_step_property($step_id, 'view', $view);
	}
	
	function get_step_condition($step_id)
	{
		return $this->get_step_property($step_id, 'condition');
	}
	
	function set_step_condition($step_id, $condition_type, $condition_value, $condition_context = null, $condition_timeout = -1)
	{
		$condition = array(
			'type' => $condition_type,
			'value' => $condition_value,
			'context' => $condition_context,
			'timeout' => $condition_timeout,
		);
		
		$this->set_step_property($step_id, 'condition', $condition);
	}
	
	function get_current_step()
	{
		return $this->_current_step;
	}
	
	function set_current_step($step_id)
	{
		$this->_current_step = $step_id;
	}
	
	function get_view()
	{
		return $this->_view;
	}
	
	function set_view($view)
	{
		$this->_view = $view;
	}
	
	function toData()
	{
		$steps = array();
		$view = $this->_view;
		$current_step = $this->_current_step;
		
		foreach ($this->_steps as $step_id => $step)
		{
			if ($current_step == null)
				$current_step = $step_id;
			
			if ($current_step == $step_id && isset($step['view']))
				$view = $step['view'];
			
			$step['id'] = $step_id;
			$steps[] = $step;
		}
		
		$ret = new stdClass;
		$ret->id = $this->_id;
		$ret->view = $view;
		$ret->steps = $steps;
		$ret->current_step = $this->_current_step;
		
		return $ret;
	}
	
	function _set_data($data)
	{
		if ($data == null)
			$data = array();
		
		$this->_data = $data;
	}
}

/**
 * Class C_NextGEN_Wizard_Manager
 * @implements I_NextGEN_Wizard_Manager
 */
class C_NextGEN_Wizard_Manager extends C_Component
{
	static $_instances = array();
	
	var $_active = false;
	var $_wizards = array();
	var $_wizards_data = array();
	var $_starter = null;
	var $_handled_query = false;
	
	/**
	 * Returns an instance of the wizard manager
     * @param bool|string $context
	 * @return C_NextGEN_Wizard_Manager
	 */
	static function get_instance($context=FALSE)
	{
		if (!isset(self::$_instances[$context])) {
			$klass = get_class();
			self::$_instances[$context] = new $klass($context);
		}
		return self::$_instances[$context];
	}

	/**
	 * Defines the instance
	 * @param mixed $context
	 */
	function define($context=FALSE)
	{
		parent::define($context);
		
		$this->implement('I_NextGEN_Wizard_Manager');
		
		$this->_wizards_data = get_option('ngg_wizards');
	}
	
	function add_wizard($id, $active = false, $priority = 100)
	{
		$wizard = new C_NextGEN_Wizard($id);
		$wizard->set_active($active);
		$wizard->set_priority($priority);
		
		if (isset($this->_wizards_data[$id]))
			$wizard->_set_data($this->_wizards_data[$id]);
		
		$this->_wizards[$id] = $wizard;
			
		return $wizard;
	}
	
	function remove_wizard($id)
	{
		if (isset($this->_wizards[$id]))
			unset($this->_wizards[$id]);
	}
	
	function get_wizard($id)
	{
		if (isset($this->_wizards[$id]))
			return $this->_wizards[$id];
			
		return null;
	}
	
	function _sort_wizards($wizard1, $wizard2)
	{
		$diff = $wizard1->get_priority() - $wizard2->get_priority();
		
		if ($diff == 0) {
			$wizard_ids = array_keys($this->_wizards);
			$index1 = array_search($wizard1->get_id(), $wizard_ids, true);
			$index2 = array_search($wizard2->get_id(), $wizard_ids, true);
			
			if ($index1 !== false && $index2 !== false)
				$diff = $index1 - $index2;
		}
		
		return $diff;
	}
	
	function get_next_wizard()
	{
		if (!$this->is_active())
			return null;
			
		$wizards = $this->_wizards;
		
		if (count($wizards) > 0) {
			if (count($wizards) > 1)
				uasort($wizards, array($this, '_sort_wizards'));
			
			foreach ($wizards as $id => $wizard) {
				if ($wizard->is_active())
					return $wizard;
			}
		}
		
		return null;
	}
	
	function get_running_wizard()
	{
		if (!$this->is_active())
			return null;
			
		$wizards = $this->_wizards;
		
		if (count($wizards) > 0) {
			if (count($wizards) > 1)
				uasort($wizards, array($this, '_sort_wizards'));
			
			foreach ($wizards as $id => $wizard) {
				if ($wizard->is_active() && $wizard->get_current_step() != null)
					return $wizard;
			}
		}
		
		return null;
	}
	
	function get_starter()
	{
		return $this->_starter;
	}
	
	function set_starter($starter)
	{
		$this->_starter = $starter;
	}
	
	function is_active()
	{
		return $this->_active;
	}
	
	function set_active($active)
	{
		$this->_active = $active;
	}
	
	function generate_wizard_query($wizard, $action, $params = array())
	{
		
	}
	
	function handle_wizard_query($parameters = NULL, $force = false)
	{
		if ($this->_handled_query && !$force)
			return;
			
		if ($parameters == null)
			$parameters = $_REQUEST;
			
		// determine if we're currently in the middle of a wizard (i.e. wizard that involves multiple pages)
		// if so then determine the current step
		if (isset($parameters['ngg_wizard'])) {
			$wizard = $this->get_wizard($parameters['ngg_wizard']);
			
			if ($wizard != null) {
				$wizard->set_active(true);
				$steps = $wizard->get_step_list();
				$count = count($steps);
				$current_step = isset($parameters['ngg_wizard_step']) ? $parameters['ngg_wizard_step'] : null;
				
				if ($current_step != null) {
					$idx = array_search($current_step, $steps);
				
					if ($idx !== false) {
						$idx++;
					
						if ($idx < $count) {
							$wizard->set_current_step($steps[$idx]);
						}
					}
				}
				else if ($count > 0) {
					$wizard->set_current_step($steps[0]);
				}
			}
			
			$this->_handled_query = true;
		}
	}
}
