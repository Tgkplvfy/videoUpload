<?php 

class Authorize_Signature 
{
    # 调试模式
    public static $is_debug = FALSE;

    # appkey
    public static $my_appkey = FALSE;

    # secret
    public static $my_secret = FALSE;

    # 请求参数
    public static $my_params = NULL;

    # 过滤后的参数
    public static $my_filered = NULL;

    # hash 值
    public static $my_hashed = '';

    # base64编码值
    public static $my_encoded = '';

    # 最终token
    public static $my_token = '';

    // 校验请求是否合法
    public static function verifyRequest () 
    {
		# 校验请求 token
		if ( ! isset($_REQUEST['token']) OR strpos($_REQUEST['token'], ':') === FALSE) 
			return FALSE;

		list($appkey, $signature) = explode(':', trim($_REQUEST['token']));
		$secret = self::getAppSecret($appkey);
		if ( ! $secret) 
			return FALSE;

        $my_sig = self::getSignature($secret, $_REQUEST);
		if ($signature !== $my_sig) 
			return array( 'signature' => $my_sig );

		return TRUE;
    }

	# 获取当前请求的APP信息
	private static function getAppSecret ($appkey = '') 
	{
		$MongoDB = new Ap_DB_MongoDB ();
		$appInfo = $MongoDB->getCollection('auth_keys')->findOne(array('appkey' => $appkey));

        Yaf_Registry::set('request_appkey', $appInfo['appkey']);
        Yaf_Registry::set('request_secret', $appInfo['secret']);

		return $appInfo ? $appInfo['secret'] : FALSE;
	}

    # 获取签名字符串
    public static function getSignature ($secret, $params) 
    {
        // unset($params['signature']);
        unset($params['token']);
        unset($params['_']);
        $params = array_filter($params, function($val){
            if (is_string($val)) return strlen($val);
            return false;
        });

        ksort($params);
        $signature = hash_hmac('sha1', http_build_query($params), $secret);
        $url_sign  = self::urlsafe_Base64Encode($signature);

        return $url_sign;
    }

    // URL 安全的base64加密
    private static function urlsafe_Base64Encode ($string) 
    {
        $find = array('+', '/');
        $repl = array('-', '_');

        return str_replace($find, $repl, base64_encode($string));
    }

}