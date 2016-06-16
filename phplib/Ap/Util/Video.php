<?php
	 
/**
 * 视频处理
 *
 * @author       yuanxch@mail.open.com.cn
 * @version      1.0
 * @copyright    
 * @access       public
 * @date         2013/6/3
 */
class Ap_Util_Video
{
    

	/**
	 * 生成缩略图 
	 * @param string $source_file
	 * @param string $size 图片大小
	 * @param string $thumb 图片保存地址+名称
	 * @return     boolean
	*/
	public static function createThumb( $source_file, $size, $thumb )
	{
		$cmd = "ffmpeg -i %s -t 0.001 -s %s %s";        
		$cmd = sprintf( $cmd, $source_file, $size, $thumb );
	    $rs  = shell_exec( $cmd );
        return file_exists( $thumb );
	} // end func


	
	/**
	 * 获得视频的播放时间长度
	 * @param      string $file
	 * @access     public
	 * @return     float 单位:秒
	 * @update     2013/6/8
	*/
	public static function getLong( $file )
	{
		$cmd = "ffmpeg -i %s 2>&1 | grep 'Duration' | cut -d ' ' -f 4 | sed s/,//";
		$cmd = sprintf( $cmd, $file );
		$duration = shell_exec( $cmd );        
		list( $h, $i, $s ) = explode( ':', $duration );
		$long = sprintf( "%.3f", $h*3600 + $i*60 + $s );
		return $long;
	    
	} // end func


	
	/**
	 * 创建指定的格式的视频
	 * @param string $source_file 源文件
	 * @param array $setting 设置 array( 'b:v'=>'128k', 'bufsize'=>'128k' );
	 * @param string $dest_file 目录文件 
	 * @return void
	*/
	public static function createVideo( $source_file, $setting, $dest_file )
	{
	    $cmd = "ffmpeg -i %s -b:v %s -bufsize %s %s";
		$bv = isset( $setting['b:v'] )? $setting['b:v'] : '128k';
		$bufsize = isset( $setting['bufsize'] )? $setting['bufsize'] : '128k';
		$cmd = sprintf( $cmd, $source_file, $bv, $bufsize, $dest_file );
		return shell_exec( $cmd );
	} // end func


	/**
 	 * 视频转出图片
	 * @param string $src 视频源
	 * @param string $t 时间点
	 * @param string $save_path 保存地址
	 * @return boolean
	 */
	public static function video2image( $src, $t, $save_path )
	{
		$cmd = "ffmpeg -i %s -ss %s -f image2 -vframes 1 %s";
		$cmd = sprintf( $cmd, $src, $t, $save_path );
		$rs = shell_exec( $cmd );
		return file_exists( $save_path );
	}

} // end class
?>

