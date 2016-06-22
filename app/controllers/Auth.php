<?php

class AuthController extends Ap_Base_Control {

    public function IndexAction () 
    {
        echo "hello oauth2.0, we're running fast";
        // print_r($this->actions);
    }

    # 获取 AccessToken
    public function access_tokenAction () 
    {
        echo 'access token action';
    }
    
}