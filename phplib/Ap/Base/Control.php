<?php 

/**
 * Rest_Controller 基类：
 * ------------------------------------------------
 * 继承后默认为Controller添加RestFUL接口的路由actions
 * 例：'videoget' => 'actions/Video/Get.php'
 * ------------------------------------------------
 */

use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\ResourceServer;
use OAuth2\Server\Repositories\AccessTokenRepository;
use OAuth2\Server\Repositories\ClientRepository;
use OAuth2\Server\Repositories\ScopeRepository;

class Ap_Base_Control extends Yaf_Controller_Abstract
{
	# OAuth 2.0 服务
	// protected $_oauthServer;

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
				$restActions[$controller . $item] = 'actions/' . ucfirst($controller) . '/' . ucfirst($item) . '.php';  # 增加路由
			});

			# 给当前Controller添加restFUL的action路由, 并设定rest action的名字
			$this->actions = array_merge($this->actions, $restActions);
			$request->setActionName($controller . $method);
		}

		if ($controller != 'demo')
			$this->verifyRequest();
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
		# 校验请求 token
		if ( ! isset($_REQUEST['token']) OR strpos($_REQUEST['token'], ':') === FALSE) {
			$this->response(NULL, 400, 'Invalid token !');
		}

		list($appkey, $signature) = explode(':', trim($_REQUEST['token']));
		$appInfo = $this->_getAppInfo($appkey);

		if ( ! $appInfo OR $signature != $appInfo['secret']) {
			$this->response(NULL, 401, 'Invalid token !');
		}

		return TRUE;
	}

	# 获取当前请求的APP信息
	private function _getAppInfo ($appkey = '') 
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