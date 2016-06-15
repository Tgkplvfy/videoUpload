<?php

// ini_set('memory_limit','1024M');
// set_time_limit(0);

date_default_timezone_set('Asia/Shanghai');
define("ROOT_PATH",  realpath(dirname(__FILE__) . '/../'));

try {
    $app = new Yaf_Application(ROOT_PATH . "/conf/app.ini");
	$app->bootstrap()->run();
} catch (Exception $e) {
	print_r($e->getMessage());
	// Log Exception
}