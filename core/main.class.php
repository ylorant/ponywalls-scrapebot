<?php

class Scrapebot
{
	public static $verbose = false;
	public static $forked = false;
	private $config;
	private static $status = false;
	public $modules;
	
	const VERSION = '0.1';
	
	public function init()
	{
		Scrapebot::message('Scrapebot for Ponywalls, version '. Scrapebot::VERSION);	
		Config::setInstance(new ConfigParser('conf/'));
		Config::load();
		Scrapebot::message('Loading shared memory...');
		Memory::init($this);
		Scrapebot::message('Connecting to database...');
		DB::connect(Config::get('Database.Engine'),
					Config::get('Database.Host'),
					Config::get('Database.Port'),
					Config::get('Database.User'),
					Config::get('Database.Password'),
					Config::get('Database.Name'));
		$this->modules = new ModuleManager($this->config);
	}
	
	public static function fork($callback, $params)
	{
		$pid = pcntl_fork();
		if ($pid == -1)
			return false;
		else if ($pid)
			return true;
		else
		{
			self::$verbose = true;
			call_user_func_array($callback, $params);
			die();
		}
	}
	
	public function run()
	{
		Scrapebot::message('Running.');
		while(true)
		{
			Events::tick();
			
			if(self::$forked == false)
				Memory::iteration();
			
			usleep(10000);
			pcntl_waitpid(-1, $st, WNOHANG);
		}
	}
	
	public static function parseBool($bool)
	{
		if(in_array($bool, array('true', 'on', '1')))
			return true;
		else
			return false;
	}
	
	public static function message($message, $level = E_NOTICE)
	{
		if(!self::$verbose)
			return;
		
		switch($level)
		{
			case E_NOTICE:
				$prefix = '[INFO]';
				break;
			case E_WARNING:
				$prefix = '[WARN]';
				break;
			case E_ERROR:
				$prefix = '[ERROR]';
				break;
			default:
				$prefix = '[MSG]';
		}
		
		if(self::$status !== false)
		{
			echo "\r";
			echo str_repeat(' ', self::$status);
			echo "\r";
			self::$status = false;
		}
		
		echo $prefix.' '.$message.PHP_EOL;
	}
	
	public static function status($message)
	{
		if(!self::$verbose)
			return;
		
		if(self::$status !== false)
		{
			echo "\r";
			echo str_repeat(' ', self::$status);
			echo "\r";
		}
		
		if($message > 79)
			$message = substr($message,0,76).'...';
		
		self::$status = strlen($message);
		echo $message;
	}
}
