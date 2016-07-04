<?php 

class Authorize_Secret 
{
    // 校验请求是否合法
    public static function verifyRequest () 
    {
		# 校验请求 token
		if ( ! isset($_REQUEST['token']) OR strpos($_REQUEST['token'], ':') === FALSE) 
			return FASLE;

		list($appkey, $secret) = explode(':', trim($_REQUEST['token']));
		$appInfo = self::_getAppInfo($appkey);

		if ( ! $appInfo OR $secret != $appInfo['secret']) 
			return FALSE;

		return TRUE;
    }

	# 获取当前请求的APP信息
	private static function _getAppInfo ($appkey = '') 
	{
		$MongoDB = new Ap_DB_MongoDB ();
		$appInfo = $MongoDB->getCollection('auth_keys')->findOne(array('appkey' => $appkey));

		# 添加测试appkey和secret
		if ( ! $MongoDB->getCollection('auth_keys')->findOne(array('appkey' => 'imooc'))) 
		{
			$MongoDB->getCollection('auth_keys')->save(array(
				'_id' => new MongoId(), 
				'uid' => 1, 
				'appkey' => 'imooc', 
				'secret' => 'upload'
			));
		}
		return $appInfo ? $appInfo : FALSE;
	}

}