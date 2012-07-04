<?php

class SharedMemory
{
	private $shmid;
	private $main;
	
	public function init($main)
	{
		$this->main = $main;
		$this->shmid = ftok(__FILE__, 't');
		$this->shmid = shmop_open($this->shmid, 'c', 0666, 32768); //Opens 32KB of shared memory
		if($this->shmid === FALSE)
		{
			Scrapebot::message('Cannot create shared memory segment', E_ERROR);
			die();
		}
		else
			Scrapebot::message('Shared memory segment created: '.$this->shmid);
		$data = array();
		shmop_write($this->shmid, serialize($data), 0);
	}
	
	public function set($path, $value)
	{
		$data = shmop_read($this->shmid, 0, shmop_size($this->shmid));
		$len = strlen($data);
		$data = unserialize($data);
		$data[$path] = $value;
		shmop_write($this->shmid, str_repeat("\0", $len), 0);
		shmop_write($this->shmid, serialize($data), 0);
	}
	
	public function iteration()
	{
		$data = shmop_read($this->shmid, 0, shmop_size($this->shmid));
		$len = strlen($data);
		$data = unserialize($data);
		shmop_write($this->shmid, str_repeat("\0", $len), 0);
		shmop_write($this->shmid, serialize(array()), 0);
		foreach($data as $key => $value)
		{
			$ref = $this->main;
			$path = explode('->', $key);
			foreach($path as $el)
			{
				if(strpos($el, '|') !== FALSE)
				{
					$el = explode('|', $el);
					if($el[0])
						$ref = &$ref->{$el[0]};
					
					array_shift($el);
					foreach($el as $v)
					{
						if(!isset($ref[$v]))
							$ref[$v] = 0;
						$ref = &$ref[$v];
					}
				}
				else
					$ref = &$ref->$el;
			}
			$ref = $value;
		}
	}
}

class Memory
{
	private static $instance;
	
	public static function getInstance()
	{
		if(!self::$instance)
			self::$instance = new SharedMemory();
		
		return self::$instance;
	}
	
	public static function __callStatic($func, $args)
	{
		$self = self::getInstance();
		call_user_func_array(array($self, $func), $args);
	}
}
