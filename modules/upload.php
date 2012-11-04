<?php
namespace Modules;
use \Scrapebot;
use \Events;
use \Memory;
use \Config;

class Upload extends Module
{
	private $destination;
	private $useCurl;
	private $curlParams = array();
	
	public $queue;
	
	public function init()
	{
		$this->destination = Config::get('Upload.Destination');
		$this->useCurl = Scrapebot::parseBool(Config::get('Upload.UseCURL'));
		
		if($this->useCurl)
		{
			$this->curlParams = array(
			'url' => Config::get('Upload.CURL.PostURL'),
			'filefield' => Config::get('Upload.CURL.FileField'),
			'keywordsfield' => Config::get('Upload.CURL.KeywordsField')
			);
		}
		
		Events::hook(array($this, 'upload'), 5);
		Events::bind('data.local', array($this, 'addToQueue'));
	}
	
	public function addToQueue($path, $data)
	{
		Scrapebot::message('Adding wallpaper to upload queue');
		
		if(Scrapebot::$forked)
			Memory::set('modules->modules|upload->queue|'.$path, $data);
		else
			$this->queue[$path] = $data;
	}
	
	public function upload()
	{
		if(count($this->queue) == 0)
			return;
		
		$key = array_shift(array_keys($this->queue));
		$data = $this->queue[$key];
		
		Scrapebot::message('Uploading '.$key.'...');
		if($this->useCurl)
		{
			Scrapebot::fork(function($path, $data)
			{
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_VERBOSE, 0);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_URL, $this->curlParams['url']);
				curl_setopt($ch, CURLOPT_POST, true);
				$post = array(
					$this->curlParams['filefield'] => '@'.$path,
					$this->curlParams['keywordsfield'] => join(' ', $data['keywords'])
				);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $post); 
				curl_exec($ch);
				Scrapebot::message('Uploaded '.$path.'.');
			}, array($key, $data));
		}
		unset($this->queue[$key]);
	}
}

$this->setClassName('upload', 'Modules\Upload');
