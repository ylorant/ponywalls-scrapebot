<?php

class ModuleManager
{
	public $modules = array();
	private $classNames = array();
	private $config;
	
	public function __construct()
	{
		$initial = Config::get('Modules.Autoload');
		$initial = explode(',', str_replace(' ', '', $initial));
		
		Scrapebot::message('Loading modules: '.Config::get('Modules.Autoload'));
		$this->load($initial);
	}
	
	public function load($list)
	{
		if(!is_array($list))
			$list = array($list);
		
		foreach($list as $module)
		{
			if(isset($this->modules[$module]))
				continue;
			
			Scrapebot::message('Loading module '.$module.'...');
			if(!isset($this->classNames[$module]) && is_file('modules/'.$module.'.php'))
				include('modules/'.$module.'.php');
			elseif(!is_file('modules/'.$module.'.php'))
				continue;
				
			$this->init($module);
		}
	}
	
	public function setClassName($plugin, $class)
	{
		$this->classNames[$plugin] = $class;
	}
	
	public function init($name)
	{
		Scrapebot::message('Initializing module '.$name.'...');
		$this->modules[$name] = new $this->classNames[$name]($this->config);
	}
}

class Events
{
	private $hooks = array();
	private $events = array();
	
	public static $instance;
	
	public static function getInstance()
	{
		if(!self::$instance)
			self::$instance = new self();
		
		return self::$instance;
	}
	
	public static function __callStatic($func, $args)
	{
		$self = self::getInstance();
		call_user_func_array(array($self, 's_'.$func), $args);
	}
	
	public function s_hook($func, $interval)
	{
		$this->hooks[] = array('call' => $func, 'interval' => $interval, 'last' => 0);
		Scrapebot::message('Hook added for func '.$func[1].' every '.$interval.' seconds.');
	}
	
	public function s_unhook($func)
	{
		foreach($this->hooks as $i => $hook)
		{
			if($hook['call'] == $func)
				unset($this->hooks[$i]);
		}
	}
	
	public function s_tick()
	{
		foreach($this->hooks as &$hook)
		{
			if($hook['last'] + $hook['interval'] < time())
			{
				$hook['last'] = time();
				call_user_func_array($hook['call'], array());
			}
		}
	}
	
	public function s_bind($ev, $func)
	{
		if(!isset($this->events[$ev]))
			$this->events[$ev] = array();
		$this->events[$ev][] = $func;
		Scrapebot::message('Event '.$ev.' bound on func '.get_class($func[0]).'::'.$func[1]);
	}
	
	public function s_unbind($ev, $func)
	{
		if(isset($this->events[$ev]))
		{
			foreach($this->events as $k => $v)
			{
				if($v == $func)
					unset($this->events[$ev][$k]);
			}
		}
	}
	
	public function s_trigger($ev)
	{
		$args = func_get_args();
		array_shift($args);
		if(isset($this->events[$ev]))
		{
			foreach($this->events[$ev] as $event)
				call_user_func_array($event, $args);
		}
	}
}
