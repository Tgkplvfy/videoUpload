<?php
/**
 * Http请求操作类
 * @author jiangsf
 *
 */
class Ap_Util_Http {
    /**
     *
     * @var array 图片文件头信息
     */
    private static $headers = array (
            Ap_Common_Vars::FILE_TYPE_JPG => 'Content-Type: image/jpeg',
            Ap_Common_Vars::FILE_TYPE_GIF => 'Content-Type: image/gif',
            Ap_Common_Vars::FILE_TYPE_PNG => 'Content-Type: image/png',
            Ap_Common_Vars::FILE_TYPE_JSON => 'Content-type: application/json;charset=UTF-8',
    );
    /**
     * 浏览器声明
     * 
     * @var string
     */
    public static $userAgent = 'Mozilla/4.0';
    /**
     * 超时时间
     * 
     * @var number
     */
    public static $timeout = 30;
    
    /**
     * 发送POST请求
     *
     * @param string $url            
     * @param array $param            
     */
    public static function post($url, $param) {
        $curl = curl_init ();
        curl_setopt ( $curl, CURLOPT_URL, $url );
        curl_setopt ( $curl, CURLOPT_POST, 1 );
        curl_setopt ( $curl, CURLOPT_POSTFIELDS, $param );
        curl_setopt ( $curl, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt ( $curl, CURLOPT_TIMEOUT, self::$timeout );
        curl_setopt ( $curl, CURLOPT_USERAGENT, self::$userAgent );
        $result = curl_exec ( $curl );
        curl_close( $curl );
        return $result;
    }
    
    /**
     * GET请求某个页面
     *
     * @param string $url            
     * @param array $param            
     */
    public static function get($url, $param = NULL) {
        if (is_array ( $param )) {
            $param = http_build_query ( $param );
            if (preg_match ( '/\?/', $url )) {
                $url .= '&';
            } else {
                $url .= '?';
            }
            $url .= $param;
        }
        $curl = curl_init ();
        curl_setopt ( $curl, CURLOPT_URL, $url );
        curl_setopt ( $curl, CURLOPT_HEADER, 0 );
        curl_setopt ( $curl, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt ( $curl, CURLOPT_TIMEOUT, self::$timeout );
        curl_setopt ( $curl, CURLOPT_USERAGENT, self::$userAgent );
        $result = curl_exec ( $curl );
        curl_close( $curl );
        return $result;
    }
    
    /**
     * 通过文件类型输出相应的头信息 文件类型定义在Ap_Common_Vars::FILE_TYPE_*
     *
     * @param number $filetype
     *            文件类型
     */
    public static function header($filetype) {
        header ( self::$headers [$filetype] );
    }
    
    /**
     * 跳转到新的URL，会中断程序执行
     *
     * @param string $url            
     */
    public static function redirect($url) {
        header ( "Location:$url" );
        exit ();
    }
    
    /**
     * 以子窗口的模式打开
     * @param str $url
     */
    public static function jsOpenWindow($url) {
    	echo "<script type='text/javascript' charset='UTF-8'>";
    	echo "window.open('".$url."','window','height=630,width=770,top=300,left=400,toolbar=1,menubar=1,scrollbars=no,resizable=yes,location=yes,status=1');";
    	echo "</script>";
    	exit;
    }
    
    /**
     * 关闭当前窗口,刷新父页面
     */
    public static function parentJsRefresh() {
    	echo "<script type='text/javascript' charset='UTF-8'>";
    	echo "window.opener.refresh();";//重点就在这里刷新  //可以为任意你想刷新时调用的方法 确保函数父页面中存在，子页面能访问
    	echo "window.close();";
    	echo "</script>";
    	exit;
    }
    
    /**
     * 获取用户的设备类型
     * @param string $agent
     * @return string "Android", "iPhone", "iPod", "iPad", "Windows Phone", "MQQBrowser", "Windows NT", "Macintosh"
     */
    public static function getDevice($agent = '') {
        if (empty($agent)) {
            $agent = $_SERVER['HTTP_USER_AGENT'];
        }
        $ary = array("Android", "iPhone", "iPod", "iPad", "Windows Phone", "MQQBrowser", "Windows NT", "Macintosh");
        $device = "unknow";
        foreach ($ary as $key) {
            if (stripos($agent, $key) > -1) {
                $device = $key;
                break;
            }
        }
        return $device;
    }

    /**
     * 获取用户的操作系统名称
     * @param string $agent
     */
    public static function getSystem()
    {
    	$sys = $_SERVER['HTTP_USER_AGENT'];
    	if(stripos($sys, "NT 6.1"))
    	$os = "Windows 7";
    	elseif(stripos($sys, "NT 6.0"))
    	$os = "Windows Vista";
    	elseif(stripos($sys, "NT 5.1"))
    	$os = "Windows XP";
    	elseif(stripos($sys, "NT 5.2"))
    	$os = "Windows Server 2003";
    	elseif(stripos($sys, "NT 5"))
    	$os = "Windows 2000";
    	elseif(stripos($sys, "NT 4.9"))
    	$os = "Windows ME";
    	elseif(stripos($sys, "NT 4"))
    	$os = "Windows NT 4.0";
    	elseif(stripos($sys, "98"))
    	$os = "Windows 98";
    	elseif(stripos($sys, "95"))
    	$os = "Windows 95";
    	elseif(stripos($sys, "Mac"))
    	$os = "Mac";
    	elseif(stripos($sys, "Linux"))
    	$os = "Linux";
    	elseif(stripos($sys, "Unix"))
    	$os = "Unix";
    	elseif(stripos($sys, "FreeBSD"))
    	$os = "FreeBSD";
    	elseif(stripos($sys, "SunOS"))
    	$os = "SunOS";
    	elseif(stripos($sys, "BeOS"))
    	$os = "BeOS";
    	elseif(stripos($sys, "OS/2"))
    	$os = "OS/2";
    	elseif(stripos($sys, "PC"))
    	$os = "Macintosh";
    	elseif(stripos($sys, "AIX"))
    	$os = "AIX";
    	else
    		$os = "未知操作系统";
    
    	return $os;
    }
    
    /**
     * 判断是否是wap访问
     * @return number $is_wap
     */
    public static function getIsWap() {
    	$is_wap = 0;
    	if(isset($_SERVER['HTTP_VIA']) && strpos($_SERVER['HTTP_VIA'],"wap")>0){
    		$is_wap = 1;
    	}elseif (isset( $_SERVER['HTTP_ACCEPT'] ) && ((!empty($_SERVER['HTTP_ACCEPT']) && strpos(strtoupper($_SERVER['HTTP_ACCEPT']),"VND.WAP") > 0) || strpos(strtoupper($_SERVER['HTTP_ACCEPT']),"UC/") > 0 ) ){
    		$is_wap = 1;
    	}else {
            if (array_key_exists('HTTP_USER_AGENT', $_SERVER)) {
                $iUSER_AGENT=strtolower (trim($_SERVER['HTTP_USER_AGENT']));
                if(preg_match('/(blackberry|configuration\/cldc|hp|hp-|htc|htc_|htc-|iemobile|kindle|midp|mmp|motorola|mobile|nokia|opera mini|opera|Googlebot-Mobile|YahooSeeker\/M1A1-R2D2|android|iphone|ipod|mobi|palm|palmos|pocket|portalmmm|ppc|smartphone|sonyericsson|sqh|spv|symbian|treo|up.browser|up.link|vodafone|windows ce|xda|xda_)/i', $iUSER_AGENT)){
                    $is_wap = 1;
                }
            }
    	}
    	 
    	return $is_wap;
    }
    
    
    /**
     * 判断来自哪个端
     * @return string  pc  wap
     * 
     */
    public static function getVisitType() {
    	$type = self::getIsWap();
    	if ($type == 1) {
    		return '2';
    	}
    	
    	return '1';
    }
    
    /**
     * 获取来源url
     * @return string
     */
    public static function getFromUrl() {
    	$fromUrl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
    	
    	return $fromUrl;
    }

}
