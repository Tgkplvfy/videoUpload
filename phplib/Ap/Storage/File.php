<?php

/**
 * 默认文件存储引擎
 */
class Ap_Storage_File implements Ap_Base_Storage 
{
	public function __construct () 
	{
		$this->_fdfs = new FastDFS();
	}

	public function write ($file, $path) 
	{
		// $
	}

	public function read () 
	{
		// 
	}

}