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

    // 这个貌似没有用~~
    public function _initDefaultName(Yaf_Dispatcher $dispatcher) {
        $dispatcher->setDefaultModule("Index")->setDefaultController("Index")->setDefaultAction("index");
    }
}