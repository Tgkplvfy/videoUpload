<?php 

class Authorize_Secret 
{
    // 校验请求是否合法
    public static function verifyRequest () 
    {
		# 校验请求 token
		if ( ! isset($_REQUEST['token']) OR strpos($_REQUEST['token'], ':') === FALSE) 
			return FALSE;

		list($appkey, $signature) = explode(':', trim($_REQUEST['token']));
		$secret = $this->_getAppSecret($appkey);

		if ( ! $appInfo OR $signature !== self::getSignature($secret)) 
			return FALSE;

		return TRUE;
    }

	# 获取当前请求的APP信息
	private function _getAppSecret ($appkey = '') 
	{
		$MongoDB = new Ap_DB_MongoDB ();
		$appInfo = $MongoDB->getCollection('auth_keys')->findOne(array('appkey' => $appkey));

		return $appInfo ? $appInfo['secret'] : FALSE;
	}

    # 获取签名字符串
    public function getSignature ($secret, $params) 
    {
        unset($params['signature']);

        $params = array_filter($input, function($val){
            if (is_string($val)) return strlen($val);
            return true;
        });

        ksort($params);
        $signature = hash_hmac('sha1', http_build_query($params), $secret);
        $url_sign  = $this->urlsafe_Base64Encode($signature);

        return $url_sign;
    }

    // URL 安全的base64加密
    private function urlsafe_Base64Encode ($string) 
    {
        $find = array('+', '/');
        $repl = array('-', '_');

        return str_replace($find, $repl, base64_encode($string));
    }

}