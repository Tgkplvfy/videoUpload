<?php


/**
 * ip
 * @author       yuanxch
 * @version      1.0
 * @copyright    Copyright (c) 2005-2013 Open Inc. (http://www.open.com.cn)
 * @access       public
 */
final class Ap_Util_IP {
     
    /**
	 * 获得IP地址
	 * @param $type 0返回有.的ip    1返回不含.的ip  @edited by jiangwb 2013-10-12
	 * @return string 
	 */
	public static function get($type=0)
	{
		if( isset( $HTTP_SERVER_VARS["HTTP_X_FORWARDED_FOR"] ) )
		{
			$ip = $HTTP_SERVER_VARS["HTTP_X_FORWARDED_FOR"];
		}
		elseif( isset( $HTTP_SERVER_VARS["HTTP_CLIENT_IP"] ) )
		{
			$ip = $HTTP_SERVER_VARS["HTTP_CLIENT_IP"];
		}
		elseif( isset( $HTTP_SERVER_VARS["REMOTE_ADDR"] ) )
		{
			$ip = $HTTP_SERVER_VARS["REMOTE_ADDR"];
		}
		elseif( getenv("HTTP_X_FORWARDED_FOR") )
		{
			$ip = getenv("HTTP_X_FORWARDED_FOR");
		}
		elseif( getenv("HTTP_CLIENT_IP") )
		{
			$ip = getenv("HTTP_CLIENT_IP");
		}
		elseif( getenv("REMOTE_ADDR") )
		{
			$ip = getenv("REMOTE_ADDR");
		}
		else
		{
			$ip = "0.0.0.0";
		}
		        
        $ip = preg_match("/[\d\.]{7,15}/", $ip, $ip_arr) ? $ip_arr[0] : "0.0.0.0";
        
		if($ip!='Unknown' && $type==1){
			$ip = str_replace('.', '', $ip);
		}
		
		return $ip;
	}
} // end class
