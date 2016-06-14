<?php

/**
 * 文件上传
 *
 * @author       yuanxch@mail.open.com.cn
 * @version      1.0
 * @copyright    
 * @access       public\
 * @date		 2013/6/3
 * <code>
 * 多文件上传:<input type="file" name="file[]">
 * //命名方式
 * Ap_Util_Upload::$sNameing = 'uniqid'; 
 * $up = new Ap_Util_Upload( $_FILES['file'] , Ap_Constants::VIDEO_SAVE_PATH, $allow_type, $max_size );
 * $succ_num = $up->upload();
 * if( $succ_num > 0 ) $infos = $up->getSaveInfo();
 * </code>
 */
class Ap_Util_Upload
{
	//debug模式
	public static $sDebug = false;

	//报告模式
	public static $sReport = false;

	//命名规则 datetime:date('YmdHis'), time:time(), md5:md5_file(),uniqid:uniqid(),false:保持,uuid:Ap_Util_UUID::v4();
	public static $sNameing = false;

	//用户上传的文件
	public $user_post_file = array();

	//存放用户上传文件的路径
	public $save_file_path;

	//文件最大尺寸
	public $max_file_size;

	//记录最后一次出错信息
	public $last_error;

	//默认允许用户上传的文件类型
	public $allow_type = array('avi', 'flv', 'mp4');

	//最终保存的文件名
	public $final_file_path;

	//返回一组有用信息，用于提示用户。
	public $save_info = array();

	/**
	 * 构造函数，用与初始化相关信息，用户待上传文件、存储路径等
	 * @param Array $file  用户上传的文件
	 * @param String $path  存储用户上传文件的路径
	 * @param Array $type   此数组中存放允计用户上传的文件类型
	 * @param Integer $size 允许用户上传文件的大小(字节)
	 */
	function __construct( $file, $path, $type = null, $size = 2097152 )
	{
		$this->user_post_file = $file;
		$this->save_file_path = $path;
		$this->max_file_size  = $size;  //如果用户不填写文件大小，则默认为2M.

		if ( $type ) {
			$this->allow_type = $type;  
		}
	} // end func



	/**
	 * 存储用户上传文件，检验合法性通过后，存储至指定位置。
	 * @access public
	 * @param string $input_name
	 * @return int 值为0时上传失败，非0表示上传成功的个数。
	 */
	public function upload()
	{
		//上传数量
		$fnum = count( $this->user_post_file['name'] );

		//处理每一个上传文件
		for ( $i = 0; $i < $fnum; $i++ ) {
			//如果当前文件上传成功，则执行下一步。
			if ( $this->user_post_file['error'][$i] == 0 )
			{
				//取当前文件名、临时文件名、大小、扩展名，后面将用到。
				$name      = $this->user_post_file['name'][$i];
				$tmpname   = $this->user_post_file['tmp_name'][$i];
				$size      = $this->user_post_file['size'][$i];
				$mime_type = $this->user_post_file['type'][$i];
				$type      = $this->__getFileExt( $this->user_post_file['name'][$i] );

				//检测当前上传文件大小是否合法。
				if ( !$this->__checkSize($size) )
				{
					$this->last_error = "The file size is too big. File name is: {$name}";
					$this->__halt( $this->last_error );
					continue;
				}

				//检测当前上传文件扩展名是否合法。
				if (!$this->__checkType( $type ) )
				{
					$this->last_error = "Unallowable file type: ."  .$type . " File name is: " . $name;
					$this->__halt( $this->last_error );
					continue;
				}

				//检测当前上传文件是否非法提交。
				if( !is_uploaded_file( $tmpname ) )
				{
					$this->last_error = "Invalid post file method. File name is: " . $name;
					$this->__halt( $this->last_error );
					continue;
				}

				$saveFile = $this->saveFile($tmpname);
				if ($saveFile !== FALSE) {
					$this->last_error = $this->user_post_file['error'][$i];
					$this->__halt( $this->last_error );
					continue;
				}

				//存储当前文件的有关信息，以便其它程序调用。
				$this->save_info[] =  array(
					"name" => $name,
					"type" => $type,
					"mime_type" => $mime_type,
					"size" => $size,
					"saveas" => $saveas,
					"path" => $this->final_file_path
				);
			}
		}

		return count( $this->save_info ); //返回上传成功的文件数目
	}

	/**
	 * 返回一些有用的信息，以便用于其它地方。
	 * @return Array 返回最终保存的路径
	 */
	function getSaveInfo()
	{
		return $this->save_info;
	}

	// 保存文件
	public function saveFile ($path) 
	{
		$fDFS = new Ap_Storage_FastDFS();
		$info = $fDFS->write($path);
	}

	/**
	 * 检测用户提交文件大小是否合法
	 * @param Integer $size 用户上传文件的大小
	 * @return boolean 如果为true说明大小合法，反之不合法
	 */
	private function __checkSize( $size )
	{
		return $size > $this->max_file_size ? false : true;
	}

	/**
	 * 检测用户提交文件类型是否合法
	 * @return boolean 如果为true说明类型合法，反之不合法
	 */
	private function __checkType( $extension )
	{
		foreach ( $this->allow_type as $type )
		{
			if ( strcasecmp( $extension , $type ) == 0 )
				return true;
		}
		return false;
	}

	/**
	 * 取文件扩展名
	 * @param  String $filename 给定要取扩展名的文件
	 * @return String 返回给定文件扩展名
	 */
	private function __getFileExt( $filename )
	{
		$stuff = pathinfo( $filename );
		return $stuff['extension'];
	}	

	/**
	 * 取给定文件文件名，不包括扩展名。
	 * @param String $filename 给定要取文件名的文件
	 * @return String 返回文件名
	 */
	private function __getBaseName( $filename, $type )
	{
		$basename = basename( $filename, $type );
		return $basename;
	}
	/**
	 * 消息控制 
	 * @param      string $msg
	 * @reutrn void
	 */
	private function __halt( $msg )
	{
		if( self::$sReport )
		{
			printf( "<b><UploadFile Error:></b> %s <br>\n" , $msg  );
		}
	    
	} // end func
    
} // end class
?>