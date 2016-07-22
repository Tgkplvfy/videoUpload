<?php 

/**
 * Rest_Controller 基类：
 * ------------------------------------------------
 * 继承后默认为Controller添加RestFUL接口的路由actions
 * 例：'videoget' => 'actions/Video/Get.php'
 * ------------------------------------------------
 */

class Ap_Base_Control extends Yaf_Controller_Abstract
{
	public $actions = array();
	
	// init Restful API request dispatch
	public function init () 
	{
		$request = Yaf_Dispatcher::getInstance()->getRequest();

		# 仅当当前请求的模块和动作都是Index的时候，启用RestFUL模式
		$module     = strtolower($request->getModuleName());      # 当前请求模块名
		$controller = strtolower($request->getControllerName());  # 当前请求模块名
		$action     = strtolower($request->getActionName());      # 当前请求动作名

		# 根目录下的执行REST Dispatch
		if ($module == 'index' && strtolower($action) == 'index') 
		{
			# 判断当前HTTP请求方式
			$method = strtolower($request->getMethod());
			if ($method === 'post' && isset($_POST['_method'])) {
                $method = strtolower( $_POST['_method'] );          # fallback method
            }

			# 增加默认的RestFUL actions
			$restActions = array ();
			$methods = array('get', 'post', 'put', 'delete');
			array_walk($methods, function($item, $key) use ($controller, &$restActions) {
				$restActions[$controller . $item] = 'actions/' . ucfirst($controller) . '/REST_' . ucfirst($item) . '.php';  # 增加路由
			});

			# 给当前Controller添加restFUL的action路由, 并设定rest action的名字
			$this->actions = array_merge($this->actions, $restActions);
			$request->setActionName($controller . $method);
		}

		# 检验请求是否合法
		if ($controller != 'demo') 
		{
			$res = $this->verifyRequest();
			if ($res !== TRUE) 
				$this->response(NULL, 401, 'invalid token!');
			else 
				Yaf_Registry::set('request_token', $this->getTokenInfo()); # 全局注册request_token信息
		}
	}

	# 接口返回数据 JSON 格式
	public function response ($data, $code = 200, $msg = 'OK', $eof = TRUE) 
	{
		$response = json_encode(array(
			'code' => $code, 
			'data' => $data, 
			'msg'  => $msg
		));

		if ( ! $eof) return $response;

		header('Content-Type: application/json');
		# cors settings
		header('Access-Control-Allow-Origin: *');
		exit($response);
	}

	# 检验请求是否合法 deprecated for now
	public function verifyRequest () 
	{
        $config      = new Yaf_Config_Ini(ROOT_PATH . '/conf/app.ini', 'product');
        $verifyType  = $config->application->get('apiauth');
		$verifyClass = 'Authorize_' . ucfirst($verifyType);

		$verifyResult = FALSE;

		try {
			$verifyResult = $verifyClass::verifyRequest();
		} catch (Exception $e) {
			// Ap_Log::Log('');
		}

		return $verifyResult;
	}

	# 获取请求API的token信息
	public function getTokenInfo () 
	{
		$token = isset($_REQUEST['token']) ? $_REQUEST['token'] : '';

		$token_info = explode(':', $token);
		return array( 'appkey' => isset($token_info[0]) ? $token_info[0] : '' );
	}

}