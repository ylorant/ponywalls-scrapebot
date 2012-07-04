<?php

include('core/config.class.php');
include('core/memory.class.php');
include('core/db.class.php');
include('core/main.class.php');
include('core/module.class.php');
include('core/modules.class.php');

if(in_array('-v', $argv))
	Scrapebot::$verbose = true;

$scrapebot = new Scrapebot();
$scrapebot->init();
$scrapebot->run();
