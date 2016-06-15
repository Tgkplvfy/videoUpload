<?php 

// 基础动作类
class Ap_Base_Action extends Yaf_Action_Abstract 
{

	// implement Parent Class Method
	public function execute () {}
	
	// Action's Response Method 
	public function response ($data, $code = 200, $msg = 'OK', $eof = TRUE) 
	{
		$response = json_encode(array(
			'code' => $code, 
			'data' => $data, 
			'msg' => $msg
		));

		if ( ! $eof) return $response;

		header('Content-Type: application/json');
		exit($response);
	}

}