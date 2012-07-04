<?php
namespace Modules;
use \Events;
use \Scrapebot;

class FileData extends Module
{
	private $regexes = array();
	private $correspondences = array();
	
	public function init()
	{
		$this->regexes = $this->config->get('FileData.Regexes');
		$this->correspondences = $this->config->get('FileData.Correspondences');
		
		foreach($this->correspondences as &$c)
		{
			$c = explode(',', $c);
			foreach($c as &$el)
			{
				if(strpos($el, ':') !== false)
				{
					$exp = explode(':', $el);
					switch($exp[0])
					{
						case 'array':
							$exp[1] = explode('/', $exp[1]);
							$el = array('field' => $exp[1][0], 'action' => 'split', 'value' => $exp[1][1]);
							break;
					}
				}
			}
		}
		
		Events::bind('added.local.forked', array($this, 'wallpaperAdded'));
	}
	
	public function wallpaperAdded($path)
	{
		//Checking that file does not exists in database
		$md5 = md5_file($path);
		
		$filename = explode('/', $path);
		$filename = array_pop($filename);
		$data = array('keywords' => array(), 'md5' => $md5);
		foreach($this->regexes as $id => $regex)
		{
			if(preg_match($regex, $filename, $matches))
			{
				foreach($this->correspondences[$id] as $i => $field)
				{
					if(is_array($field))
					{
						switch($field['action'])
						{
							case 'split':
								$data[$field['field']] = explode($field['value'], $matches[$i]);
								break;
						}
					}
					else
						$data[$field] = $matches[$i];
				}
			}
		}
		
		if(!empty($data['keywords']))
		{
			foreach($data['keywords'] as $key => $word)
			{
				if(strpos($word, ':'))
				{
					$word = explode(':', $word, 2);
					$data[$word[0]] = $word[1];
					unset($data['keywords'][$key]);
				}
			}
		}
		Scrapebot::message('Got data from file.');
		Events::trigger('data.local', $path, $data);
	}
}

$this->setClassName('filedata', 'Modules\FileData');

?>
