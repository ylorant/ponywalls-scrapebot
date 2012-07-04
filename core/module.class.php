<?php
namespace Modules;

class Module
{
	protected $config;
	
	public function __construct($config)
	{
		$this->config = $config;
		
		$this->init();
	}
	
	public function init()
	{
		
	}
}
