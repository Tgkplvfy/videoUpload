<?php
/**
 * 日志操作类
 * 
 * @author wangpd
 *
 */
class Ap_Log {
    const LOG_LEVEL_FATAL = 0x01;
    const LOG_LEVEL_WARNING = 0x02;
    const LOG_LEVEL_NOTICE = 0x04;
    const LOG_LEVEL_TRACE = 0x08;
    const LOG_LEVEL_DEBUG = 0x10;
    const LOG_EMAIL = 'php@imooc.com';
    public static $arrLogLevels = array (
            self::LOG_LEVEL_FATAL => 'FATAL',
            self::LOG_LEVEL_WARNING => 'WARNING',
            self::LOG_LEVEL_NOTICE => 'NOTICE',
            self::LOG_LEVEL_TRACE => 'TRACE',
            self::LOG_LEVEL_DEBUG => 'DEBUG' 
    );
    private static $conf = NULL;
    
    /**
     * 写log
     * 
     * @param string $message            
     * @param string $filename
     *            log文件
     */
    private static function __log($message, $filename, $loads='') {
        $datetime = date ( "Y-m-d H:i:s", time () );
        $ip = Ap_Util_IP::get ();
        $url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        if (!empty($loads)) {
            $loads = http_build_query($_REQUEST);
        }
        $message = "{$datetime}\t{$ip}\t{$url}\t{$message}\t{$loads}\r\n";
        $rs = error_log ( $message, 3, $filename );
    }

    public static function video_upload_api($msg)
    {
        self::__log($msg, ROOT_PATH . '/logs/videoUploadApi.log');
    }
    
    /**
     * api请求与响应log
     * 
     * @param string $message            
     */
    public static function api($message) {
        self::log(array('info'=>$message, 'type' => 'api'));
    }
    
    /**
     * rabbitmq日志,包括建立连接错误及入队前
     * 
     * @param string $message
     * @param string $type 连接错误或入队前debug
     * @return boolean
     */
    public static function rabbit($message, $type='debug'){
    	return self::log(array('info'=>$message, 'type' => $type), 'rabbit_'.$type);
    }
    
    /**
     * 记录消息队列业务日志
     *
     * @param string $message
     * @param string $type 连接错误或入队前debug
     * @return boolean
     */
    public static function queues($message, $type='message'){
    	return self::log(array('info'=>$message, 'type' => $type), 'queues/'.$type);
    }
    
    /**
     * 记录Debug日志
     * 
     * @param string $message
     */
    public static function debug($message, $filename = null) {
        return self::log(array('info'=>$message, 'type' => 'debug'), 'debug');
    }
    
    /**
     * 记录error日志
     * 
     * @param string $message
     */
    public static function error($message, $level = 0) {
        self::log(array('info'=>$message, 'type' => 'error'));
        if ($level == self::LOG_LEVEL_FATAL) {
            $filename = '/tmp/log_email.txt';
            if (file_exists($filename) && (time() - filemtime($filename) > 600)) { //每小时只发送一封邮件
                $que = new Ap_Service_Queue();
                $que->sendmail(self::LOG_EMAIL, '网站出现重大错误', $message);
            }
            touch($filename, time());
        }
    }
    
    /**
     * 写入分布式日志
     * 
     * @param array $info 日志信息，数组形式
     * @param string $collection 保存到哪个集合 默认为log
     * @return boolean
     */
    public static function log($info, $collection = 'log') {
        try {
            if (empty(self::$conf)) {
                self::$conf = new Yaf_Config_Ini(APP_PATH.'/conf/mongodb.ini');
            }
            $conf = self::$conf->get('log');
            //$mon = new MongoClient("mongodb://" . $conf->host, $conf->option->toArray());
            //$coll = $mon->selectCollection($conf->db, 'log');
            
            if (!is_array($info)) {
                $info = array('info' => $info);
            }
            if (empty($info['type'])) {
                $info['type'] = 'log';
            }
            $info['time'] = new MongoDate();
            $info['ip'] = Ap_Util_IP::get ();
            $info['url'] = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
            $info['param'] = http_build_query($_POST);
            if (Yaf_Application::app()) {
                $dis = Yaf_Application::app()->getDispatcher()->getRequest();
                $info['module'] = constant('MODULE');
                $info['action'] = $dis->action;
                $info['uid'] = Ap_Service_Data_Loginuser::uId();
            }
            //$coll->save($info);
            
            //同时保存一份到文件中
            $date = date ( "Ymd" );
            $filename = Ap_Constants::LOG_SAVE_PATH . $collection . "_" . $date . ".log";
            if (!file_exists($filename)) {
                error_log ( 'new debug started', 3, $filename );
                chmod($filename, 0777);
            }
            $loads = $info['type'] == 'error' ? 1 : '';
            //为了避免没有$info['info']报错，做的一个兼容信息
            if( empty($info['info']) ){
                $info['info'] = '';
            }
            self::__log($info['info'], $filename, $loads); //同时保存一份到文件中
            return true;
        } catch (Exception $ex) {
            $date = date ( "Ymd" );
            $filename = Ap_Constants::LOG_SAVE_PATH . "log_" . $date . ".log";
            if (!file_exists($filename)) {
                error_log ( 'new debug started', 3, $filename );
                chmod($filename, 0777);
            }
            self::__log(json_encode($info), $filename);
        }
        return FALSE;
    }
    
    public static function read($collection, $type = '', $page = 1, $pagesize = 20) {
        if (empty(self::$conf)) {
            self::$conf = new Yaf_Config_Ini(APP_PATH.'/conf/mongodb.ini');
        }
        $conf = self::$conf->get('log');
        $mon = new MongoClient("mongodb://" . $conf->host, $conf->option->toArray());
        $mon->setReadPreference(MongoClient::RP_SECONDARY_PREFERRED);
        $coll = $mon->selectCollection($conf->db, 'log');
        
        $where = array();
        if (!empty($type)) {
            if (is_array($type)) {
                $where = $type;
            } else {
                $where['type'] = $type;
            }
        }
        $skip = ($page-1) * $pagesize;
        $data = $coll->find($where)->sort(array('time'=>-1))->skip($skip)->limit($pagesize);
        $array = array();
        foreach ($data as $row) {
            if ($row['time']) {
                $row['time'] = $row['time']->sec;
            }
            $array[] = $row;
        }
        return $array;
    }


	/**
	 * http log
	 */
	public static function http()
	{
		$date = date("Ymd");
		$message = self::__sCookieMsg() . "\t";
		if( isset($_SERVER["HTTP_USER_AGENT"]) )
		{
			$message .= isset( $_SERVER["HTTP_REFERER"] ) ? "REFER:" . $_SERVER["HTTP_REFERER"] . "\t" : "";
			$message .= "LANG:" . $_SERVER["HTTP_ACCEPT_LANGUAGE"] . "\t";
			$message .= "CODE:" . $_SERVER["HTTP_ACCEPT_ENCODING"] . "\t";
			$message .= "AGENT:" . $_SERVER["HTTP_USER_AGENT"] . "\t";
			$message .= "URI:" . $_SERVER["REQUEST_URI"] . "\t";
			$message .= "REQ:" . str_replace( "%2F" , "&" , http_build_query( $_REQUEST ) ). "\t";
		} else {
			$message = "http_user_agent is empty" . "\t";
		}

		self::__log( $message , Ap_Constants::LOG_SAVE_PATH . "http_" . $date . ".log" );	
	}

	/**
	 * cookie日志
	 */
	private static function __sCookieMsg()
	{
		$message = "";
		if( $_COOKIE )
		{
			foreach( $_COOKIE as $k=>$v )
			{
				$message .= "{$k}:{$v};";
			}			
		} else {
			$message = "COOKIE is empty";
		}
		return $message;
	}
	
	/**
	 * 写入渠道日志
	 * @param unknown $fileType    日志的类型  visit 访问  register注册  
	 * @param unknown $timeId      次ID
	 * @param unknown $imoocUuid 
	 * @param string $channelTag   渠道唯一标示
	 * @param string $urlTag       url唯一标示
	 * @param number $where        哪个端  1 pc 2wap 3android 4iphone 5ipad
	 * @param number $isOdl        新老用户  0 新  1老
	 */
    public static function __channel($fileType, $timeId, $timeDeep, $imoocUuid, $channelTag='', $urlTag='', $where=0, $isOld=1, $fromUrl='', $uId=0) {
        $ip = preg_match("/[\d\.]{7,15}/", Ap_Util_IP::get (), $ip_arr) ? $ip_arr[0] : "0.0.0.0";
        $ip = intval( ip2long($ip) );
        
        if (empty($fromUrl)) {
        	$fromUrl = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        }
        
        $fromUrl = urlencode($fromUrl);
        $ua = Ap_Common_Browser::getBrowserType();
        
        //如果是注册，则提交到注册日志表
        if (!empty($uId) && $fileType == 'register') {
        	$data = array(
        			'uid' => $uId,
        			'channel_marking' => $channelTag,
        			'promotion_marking' => $urlTag,
        			'imooc_uuid' => $imoocUuid,
        			'visitor_marking' => $timeId,
        			'deepness' => $timeDeep,
        			'ip' => $ip,
        			'platform' => $where,
        			'current_url' => $fromUrl,
        			'create_time' => time()
        	);
        	
        	$regLogData = new Ap_Service_Data_UserRegLog();
        	$regLogData->insertRow($data);
        	return true;
        }
        
        $message = time().','.$fromUrl.','.$timeId.','.$timeDeep.','.$imoocUuid.','.$ip.','.$ua.','.$channelTag.','.$urlTag.','.$where.','.$isOld."\r\n";
        
        $fileName = '/data/log/channel/'.$fileType.'/'.date('Y-m-d').'.txt';
        
        error_log ( $message, 3, $fileName );
    }
    
}
