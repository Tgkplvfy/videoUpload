<?php

/**
 * 所有在Bootstrap类中, 以_init开头的方法, 都会被Yaf调用,
 * 这些方法, 都接受一个参数:Yaf_Dispatcher $dispatcher
 * 调用的次序, 和申明的次序相同
 */
class Bootstrap extends Yaf_Bootstrap_Abstract{

    public function _initConfig(Yaf_Dispatcher $dispatcher) {
        $dispatcher->autoRender(FALSE); # 关闭默认的模板渲染
    }

    // 请求分发 按照HTTP REQUEST METHOD
    public function _initRestfulDispatcher(Yaf_Dispatcher $dispatcher) {
    }

    // 初始化路由
    public function _initRoute (Yaf_Dispatcher $dispatcher) {
        $router = $dispatcher->getRouter();

        # 获取上传视频文件基本信息
        $route = new Yaf_Route_Regex('/video\/([\w]{24})/', array('controller' => 'video', 'action' => 'info'), array(1 => 'bkt_video_id'));
        $router->addRoute('videoinfo', $route);

        # 获取视频M3U8信息
        $route = new Yaf_Route_Regex(
            '/video\/([\w]{24})\/(low|medium|high).m3u8/', 
            array('controller' => 'video', 'action' => 'm3u8'), 
            array(1 => 'bkt_video_id', 2 => 'definition'));
        $router->addRoute('videom3u8', $route);

        # 获取视频HXK,M3U8机密key信息
        $route = new Yaf_Route_Regex(
            '/video\/([\w]{24})\/(low|medium|high)\.hxk/', 
            array('controller' => 'video', 'action' => 'hxk'), 
            array(1 => 'bkt_video_id', 2 => 'definition'));
        $router->addRoute('videohxk', $route);
    }

    // 这个貌似没有用~~
    public function _initDefaultName(Yaf_Dispatcher $dispatcher) {
        $dispatcher->setDefaultModule("Index")->setDefaultController("Index")->setDefaultAction("index");
    }
}