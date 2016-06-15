<?php

// ini_set('memory_limit','1024M');
// set_time_limit(0);

date_default_timezone_set('Asia/Shanghai');

/* 指向public的上一级 */
define("APP_PATH",  realpath(dirname(__FILE__) . '/../'));

// 获取当前请求的module
$ary = explode('/', $_SERVER['REQUEST_URI']);
$modules = array('index', 'video', 'bucket', 'demo');
$module  = (isset($ary[1]) && strlen($ary[1]) > 0) ? $ary[1] : 'index';
$module  = in_array($module, $modules) ? $module : 'index';

define("MODULE", $module);

$app = new Yaf_Application(APP_PATH . "/conf/app.ini");

try {
	$app->bootstrap()->run();
} catch (Exception $e) {
	var_dump($e->getMessage());
	// Log Exception
}