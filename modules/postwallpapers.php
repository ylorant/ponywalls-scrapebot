<?php

class PostWallpapers extends Module
{
	private $regexes = array();
	private $correspondences = array();
	
	public function init()
	{
		$this->regexes = $this->config->get('PostWallpapers.Regexes');
		$this->correspondences = $this->config->get('PostWallpapers.Correspondences');
		
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
		
		DB::prepare('SELECT id FROM walls WHERE md5 = ?');
		DB::execute(array($md5));
		
		if(!DB::fetch())
		{
			$filename = explode('/', $path);
			$filename = array_pop($filename);
			$data = array();
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
			
			file_put_contents('out.lst', serialize($data)."\n",FILE_APPEND);
		}
	}
}

$this->setClassName('postwallpapers', 'PostWallpapers');

?>
