<?php
namespace Modules;
use \Events;
use \Scrapebot;
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

class GtkInfo extends Module
{
	private $window;
	private $image;
	private $keywords;
	private $imagepath;
	
	public function init()
	{
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
		$validateButton->connect_simple('clicked', array($this, 'skipWallpaper'));
		
		$this->window->add($mainBox);
		$this->window->set_default_size(500, 350);
		$this->window->set_position(Gtk::WIN_POS_CENTER);
		$this->window->connect_simple('destroy', array($this, 'stop'));
		
		Events::bind('added.local', array($this, 'wallpaperAdded'));
	}
	
	public function wallpaperAdded($path)
	{
		$this->imagepath = $path;
		$pixbuf = GdkPixbuf::new_from_file_at_size($path, 500, 300);
		//~ $pixbuf->scale_simple(500, 300, Gdk::INTERP_NEAREST);
		$this->image->set_from_pixbuf($pixbuf);
		$this->keywords->set_text('');
		
		$this->window->resize(500, 350);
		$this->window->show_all();
		Gtk::main();
	}
	
	public function stop()
	{
		die();
	}
	
	public function sendWallpaper()
	{
		$data = array();
		$data["keywords"] = explode(' ', $this->keywords->get_text());
		
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
		Gtk::main_quit();
		Gtk::main_iteration();
		
		Events::trigger('data.local', $this->imagepath, $data);
	}
	
	public function skipWallpaper()
	{
		$this->window->hide_all();
		Gtk::main_quit();
		Gtk::main_iteration();
	}
}


$this->setClassName('gtkinfo', 'Modules\GtkInfo');
