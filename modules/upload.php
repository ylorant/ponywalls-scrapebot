<?php
namespace Modules;
use \Scrapebot;
use \Events;
use \Memory;

class Upload extends Module
{
	private $destination;
	private $useCurl;
	private $curlParams = array();
	
	public $queue;
	
	public function init()
	{
		$this->destination = $this->config->get('Upload.Destination');
		$this->useCurl = Scrapebot::parseBool($this->config->get('Upload.UseCURL'));
		
		if($this->useCurl)
		{
			$this->curlParams = array(
			'url' => $this->config->get('Upload.CURL.PostURL'),
			'filefield' => $this->config->get('Upload.CURL.FileField'),
			'keywordsfield' => $this->config->get('Upload.CURL.KeywordsField')
			);
		}
		
		Events::hook(array($this, 'upload'), 5);
		Events::bind('data.local', array($this, 'addToQueue'));
	}
	
	public function addToQueue($path, $data)
	{
		Scrapebot::message('Adding wallpaper to upload queue');
		Memory::set('modules->modules|upload->queue|'.$path, $data);
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
			$ch = curl_init();
		    curl_setopt($ch, CURLOPT_HEADER, 0);
		    curl_setopt($ch, CURLOPT_VERBOSE, 0);
		    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		    curl_setopt($ch, CURLOPT_URL, $this->curlParams['url']);
		    curl_setopt($ch, CURLOPT_POST, true);
		    $post = array(
				$this->curlParams['filefield'] => '@'.$key,
				$this->curlParams['keywordsfield'] => join(' ', $data['keywords'])
			);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post); 
			curl_exec($ch);
		}
		Scrapebot::message('Uploaded '.$key.'.');
		unset($this->queue[$key]);
	}
}

$this->setClassName('upload', 'Modules\Upload');
