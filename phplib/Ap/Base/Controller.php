<?php 

class Ap_Base_Controller extends Yaf_Controller_Abstract
{
	// 默认返回数据的方法！
	public function response ($data, $code = 200, $msg = 'OK', $eof = TRUE) 
	{
		$response = json_encode(array(
			'code' => $code, 
			'data' => $data, 
			'msg' => $msg
		));

		$eof && exit($response);

		return $response;
	}
}