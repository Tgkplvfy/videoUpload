<?php

/**
 * 封装FastDFS存储引擎
 */
class Ap_Storage_FastDFS implements Ap_Base_Storage 
{
	// FastDFS 对象
	private $_fdfs;
	
	public function __construct () 
	{
		$this->_fdfs = new FastDFS();
	}

	// 移动文件
	public function move ($path) 
	{
		// 
	}

	// 写文件
	public function write ($file, $path = NULL, $ext = '') 
	{
		$result = $this->_fdfs->storage_upload_by_filename1($file, $ext);

		return $result;
	}

	// 读文件
	public function read () 
	{
		// 
	}

	// 删除文件
	public function delete() 
	{
		// 
	}
	
}