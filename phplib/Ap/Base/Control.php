<?php 

class Ap_Base_Control extends Yaf_Controller_Abstract
{

	// init Restful API request dispatch
	public function init () 
	{
		$request = Yaf_Dispatcher::getInstance()->getRequest();

		$module = $request->getModuleName();

		# 根目录下的执行REST Dispatch
		if ($module == 'Index') 
		{
			$method = strtolower($request->getMethod());
			if ($method === 'post' && isset($_POST['_method'])) {
                $method = strtolower( $_POST['_method'] );          # fallback method
            }

			$request->setActionName(strtolower($request->getControllerName()) . $method);
		}
	}
	
	
}