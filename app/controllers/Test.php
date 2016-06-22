<?php

/**
 * 接口调用示例
 */
class TestController extends Ap_Base_Control {

    public $actions = array (
        'oauth' => 'actions/Test/OAuth.php', 
        'server' => 'actions/Test/OAuthServer.php'
    );

}