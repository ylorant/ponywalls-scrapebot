<?php
namespace Modules;
use \Events;
use \Scrapebot;

class LocalScrape extends Module
{
	private $folders = array();
	private $configuredFolders = array();
	private $iterator = 0;
	private $filecount = 0;
	private $fp = null;
	private $analyzed = null;
	private $types = array();
	private $sizes = array();
	private $outpath;
	private $current = 0;
	
	public function init()
	{
		if(!$this->config->get('LocalScrape.Folders'))
			return false;
		
		foreach($this->config->get('LocalScrape.Folders') as $name => $folder)
		{
			$this->folders[] = $folder;
			$this->configuredFolders[$name] = $folder;
		}
		
		$this->outpath = $this->config->get('LocalScrape.OutPath');
		if($this->config->get('LocalScrape.NoOutput'))
			$this->outpath = null;
		$this->types = explode(',', str_replace(' ','', $this->config->get('LocalScrape.MIMETypes')));
		$sizes = explode(',', str_replace(' ','', $this->config->get('LocalScrape.AllowedSizes')));
		
		$analyzeInterval = null;
		$analyzeInterval = $this->config->get('LocalScrape.AnalyzeInterval');
		if($analyzeInterval === null)
			$analyzeInterval = 5;
		
		foreach($sizes as $s)
			$this->sizes[] = explode('x', $s);
		
		//Events::hook(array($this, 'updateDirectory'), 10);
		Events::hook(array($this, 'walkDirectory'), $analyzeInterval);
		
		$this->updateDirectory();
	}
	
	public function walkDirectory()
	{
		//~ while(!isset($this->fp[$this->iterator]))
		//~ {
			//~ $this->iterator++;
			//~ if($this->iterator >= count($this->fp))
				//~ break;
		//~ }
		
		//If there is still data to read from the filelist
		if($this->iterator < count($this->fp))
		{
			
			$fname = $this->fp[$this->iterator];
			$file = $this->folders[$this->current].'/'.$fname;
			Scrapebot::status($fname);
			if(is_dir($file))
			{
				if(!in_array($fname, array('', '.', '..')) && !in_array($file, $this->folders))
					$this->folders[] = $file;
			}
			else
			{
				$finfo = finfo_open(FILEINFO_MIME_TYPE);
				$mime = finfo_file($finfo, $file);
				
				//If the file has the mimetype we want
				if(in_array($mime, $this->types))
				{
					$size = getimagesize($file);
					
					foreach($this->sizes as $s)
					{
						$catch = false;
						if($size[0] == $s[0] && $size[1] == $s[1])
							$catch = true;
						
						if($catch == true)
						{
							$outpath = $this->outpath;
							if($outpath !== null)
							{
								if(preg_match_all('#{f:([a-z0-9]+)}#isU', $this->outpath, $matches))
								{
									foreach($matches[1] as $match)
										$outpath = str_replace('{f:'.$match.'}', $this->configuredFolders[$match], $outpath);
								}
								
								$outpath = str_replace('{filename}', $fname, $outpath);
								$outdir = explode('/', $outpath);
								array_pop($outdir);
								$outdir = join('/', $outdir);
								
								if(!is_dir($outdir))
									mkdir($outdir, 0777, true);
								
								Scrapebot::fork(array($this, 'copyImage'), array($file, $outpath));
								Events::trigger('added.local', $outpath);
							}
							else
							{
								Events::trigger('added.local', $file);
								Events::trigger('added.local.forked', $file);
							}
							$this->filecount++;
							Scrapebot::message($this->filecount.': '.$fname);
						}
					}
				}
				
				fputs($this->analyzed, $fname."\n");
			}
			$this->iterator++;
		}
		else
		{
			fclose($this->analyzed);
			$this->fp = null;
			$this->current++;
			if($this->current >= count($this->folders))
				$this->current = 0;
			$this->updateDirectory();
			Scrapebot::message("Finished analysis of ".$this->folders[$this->current]);
		}
		
	}
	
	public function copyImage($source, $dest)
	{
		copy($source, $dest);
		Event::trigger('added.local.forked', $dest);
	}
	
	public function updateDirectory()
	{
		$folder = $this->folders[$this->current];
		if(empty($this->fp))
		{
			$content = scandir($folder);
			array_shift($content);
			array_shift($content);
			if(is_file($folder.'/scrapebot.lst'))
			{
				$read = file($folder.'/scrapebot.lst', FILE_IGNORE_NEW_LINES);
				$content = array_diff($content, $read);
			}
			
			$this->fp = $content;
			sort($this->fp);
			$this->analyzed = fopen($folder.'/scrapebot.lst', 'a+');
			$this->iterator = 0;
		}
	}
}

$this->setClassName('localscrape', 'Modules\LocalScrape');
