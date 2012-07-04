<?php

class SharedMemory
{
    private $shmid;
    private $main;
    
    public function init($main)
    {
	$this->shmid = ftok(__FILE__, 't');
	$this->shmid = shmop_open($this->shmid, 'c', 0666);
	$data = array('lock' => false);
    }
    
    public function set($path, $value)
    {
	$data = array();
	do
	{
	    $data = unserialize(shmop_read($this->shmid, 0, shmop_count($this->shmid)));
	}
	while(!$data['lock']);
    }
}

class Memory
{
	private static $instance;
	
	public static function getInstance()
	{
		if(!DB::$instance)
			DB::$instance = new SharedMemory();
		
		return DB::$instance;
	}
	
	public static function __callStatic($func, $args)
	{
		$self = DB::getInstance();
		call_user_func_array(array($self, $func), $args);
	}
}
