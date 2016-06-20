<?php 

// 基础动作类
class Ap_Base_Action extends Yaf_Action_Abstract 
{

	// 验证API token 待定
	public function execute () 
	{
		# 验证api token 
		// $token = trim($_REQUEST['token']);
		// if (strpos($token, ':') === -1) {
		// 	$this->response(NULL, 400, 'Invalid token !');
		// }

		// list($appkey, $signature) = explode(':', $token);
		// $appInfo = $this->getAppInfo();

		// if ( ! $appInfo) {
		// 	$this->response(NULL, 401, 'Invalid token !');
		// }

		# 
	}
	
	// Action's Response Method 
	public function response ($data, $code = 200, $msg = 'OK', $eof = TRUE) 
	{
		$response = json_encode(array(
			'code' => $code, 
			'data' => $data, 
			'msg'  => $msg
		));

		if ( ! $eof) return $response;

		header('Content-Type: application/json');
		exit($response);
	}

	# 获取当前请求的APP信息
	private function _getAppInfo ($appkey = '') 
	{
		$MongoDB = new Ap_DB_MongoDB ();
		$collection = $MongoDB->getCollection('video');

		$appInfo = $collection->findOne(array('appkey'=>$appkey));

		return $appInfo ? $appInfo : FALSE;
	}

	# 获取当前请求的签名
	private function _getSignature ($appkey, $secret) 
	{
		$sign = md5($secret);
		$base64Sign = $this->_urlsafeBase64Encode($str);

		return $appkey . ':' . $base64Sign;
	}


	# URL 安全的Base64编码
	private function _urlsafeBase64Encode ($str = '') 
	{
		return str_replace('+/', '-_', base64_encode($str));
	}

}