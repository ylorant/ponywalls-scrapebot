<?php

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
		
		Events::bind('data.local', array($this, 'addToQueue'));
		Events::hook(array($this, 'upload'), 5);
	}
	
	public function addToQueue($path, $data)
	{
		
	}
	
	public function upload()
	{
		if($this->useCurl)
		{
			$ch = curl_init();
		    curl_setopt($ch, CURLOPT_HEADER, 0);
		    curl_setopt($ch, CURLOPT_VERBOSE, 0);
		    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		    curl_setopt($ch, CURLOPT_URL, $this->curlParams['url']);
		    curl_setopt($ch, CURLOPT_POST, true);
		    $post = array(
				$this->curlParams['filefield'] => $path,
				$this->curlParams['keywordsfield'] => join(' ', $data['keywords'])
			);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post); 
			curl_exec($ch);
		}
	}
}

$this->setClassName('upload', 'Upload');
