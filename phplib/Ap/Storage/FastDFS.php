<?php

/**
 * 封装FastDFS存储引擎
 */
class Ap_Storage_FastDFS implements Ap_Base_Storage 
{
	public function __construct () 
	{
		$this->_fdfs = new FastDFS();
	}

	public function move ($path) 
	{
		// 
	}

	public function write ($file, $path) 
	{
		$this->_fdfs->storage_upload_by_filename($path);
	}

	public function read () 
	{
		// 
	}

	public function delete() 
	{
		// 
	}
	
}