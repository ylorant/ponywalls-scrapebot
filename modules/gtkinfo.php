<?php
namespace Modules;
use \Events;
use \Scrapebot;
use \Memory;
use \Gtk;
use \GtkWindow;
use \GtkEntry;
use \GtkHBox;
use \GtkVBox;
use \GtkButton;
use \GtkImage;
use \GtkLabel;
use \GdkPixbuf;
use \Gdk;

if(!extension_loaded('php_gtk2'))
{
	dl('cairo.so');
	dl('php_gtk2.so');
}

class GtkInfo extends Module
{
	private $window;
	private $image;
	private $keywords;
	private $imagepath;
	private $loop;
	private $keywordCache;
	private $insertHandlerID;
	private $autocompleteSelect;
	
	public $list;
	
	public function init()
	{
		$this->list = array();
		$this->loop = false;
		$this->keywordCache = array('twilight', 'sparkle');
		
		$this->window = new GtkWindow();
		$this->keywords = new GtkEntry();
		$this->image = new GtkImage();
		$mainBox = new GtkVBox();
		$keywordBox = new GtkHBox();
		$buttonBox = new  GtkHBox();
		$validateButton = new GtkButton("Send");
		$skipButton = new GtkButton("Skip");
		
		$this->keywords->activate_default = true;
		
		$keywordBox->pack_start(new GtkLabel("Keywords:"), false, false);
		$keywordBox->pack_start($this->keywords);
		
		$buttonBox->pack_start(new GtkLabel());
		$buttonBox->pack_start($validateButton, false, false);
		$buttonBox->pack_start($skipButton, false, false);
		
		$mainBox->pack_start($this->image);
		$mainBox->pack_start($keywordBox, false, false);
		$mainBox->pack_start($buttonBox, false, false);
		
		$validateButton->connect_simple('clicked', array($this, 'sendWallpaper'));
		$skipButton->connect_simple('clicked', array($this, 'skipWallpaper'));
		$this->insertHandlerID = $this->keywords->connect_after('insert-text', array($this, 'autocompleteKeyword'));
		$this->keywords->connect('key-press-event', array($this, 'validAutocompletion'));
		$this->keywords->connect_after('key-release-event', array($this, 'selectAutocomplete'));
		
		$this->window->add($mainBox);
		$this->window->set_default_size(500, 350);
		$this->window->set_position(Gtk::WIN_POS_CENTER);
		$this->window->connect_simple('destroy', array($this, 'stop'));
		
		Events::bind('added.local', array($this, 'wallpaperAdded'));
		Events::hook(array($this, 'iteration'), -1);
	}
	
	public function validAutocompletion($widget, $event)
	{
		if ($event->keyval == Gdk::KEY_Tab)
		{
			$text = $this->keywords->get_property("text");
			$text .= " ";
			$this->keywords->set_text($text);
			$this->keywords->set_position(strlen($text));
			return true;
		}
		
	}
	
	public function selectAutocomplete()
	{
		if(!empty($this->autocompleteSelect))
		{
			$this->keywords->select_region($this->autocompleteSelect[0], $this->autocompleteSelect[1]);
			$this->autocompleteSelect = null;
		}
	}
	
	public function autocompleteKeyword($widget, $newText)
	{
		$text = $this->keywords->get_property("text");
		$cursorPosition = $this->keywords->get_property("cursor-position")+1;
		$sub = substr($text, 0, $cursorPosition-1);
		$sub .= $newText;
		$post = substr($text, $cursorPosition);
		$words = explode(" ", $sub);
		$currentWord = array_pop($words);
		
		if(strlen($currentWord))
		{
			foreach($this->keywordCache as $keyword)
			{
				if(strpos($keyword, $currentWord) === 0)
				{
					$complete = substr($keyword, strlen($currentWord));
					$this->keywords->block($this->insertHandlerID);
					$this->autocompleteSelect = array(strlen($sub), strlen($sub.$complete));
					$this->keywords->set_text($sub.$complete.$post);
					$this->keywords->unblock($this->insertHandlerID);
					$this->keywords->realize();
					Gtk::main_iteration_do();
					return true;
				}
			}
		}
	}
	
	public function wallpaperAdded($path)
	{
		$this->list[] = $path;
	}
	
	public function iteration()
	{
		Gtk::main_iteration_do(false);
		
		if(empty($this->list))
			return;
		
		if(!$this->loop)
		{
			$path = array_shift($this->list);
			$this->imagepath = $path;
			$pixbuf = GdkPixbuf::new_from_file_at_size($path, 500, 300);
			
			$this->image->set_from_pixbuf($pixbuf);
			$this->keywords->set_text('');
			
			$this->window->resize(500, 350);
			$this->window->show_all();
			
			$this->loop = true;
		}
	}
	
	public function stop()
	{
		echo "\n";
		die();
	}
	
	public function sendWallpaper()
	{
		$data = array();
		$data["keywords"] = explode(' ', $this->keywords->get_text());
		
		$this->keywordCache = array_merge($this->keywordCache, $data["keywords"]);
		
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
		
		$this->window->hide_all();
		$this->loop = false;
		
		Events::trigger('data.local', $this->imagepath, $data);
	}
	
	public function skipWallpaper()
	{
		Scrapebot::message("Skipped.");
		$this->window->hide_all();
		$this->loop = false;
	}
}


$this->setClassName('gtkinfo', 'Modules\GtkInfo');
