<?php 

/**
 * Yaf Restful Router
 */
class Ap_Base_Router implements Yaf_Route
{
	public function route (Yaf_Request_Abstract $request) 
	{
		// 获取当前HTTP请求方式，并以此确定调用的Action
		$method = strtolower($request->getMethod());
		if ($method === 'post') 
		{
			$post = $request->getPost();
			$_method = strtolower($request->getPost('_method')); 
			if (in_array($post['_method'], array('put', 'delete'))) 
				$method = $post['_method'];
		}

		// 
	}
}