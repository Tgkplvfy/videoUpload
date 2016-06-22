<?php 

# 测试OAuth接口认证
# use OAuth2\Client\Grant\AuthorizationCode;
class OAuthServerAction extends Ap_Base_Action 
{
    private $_appkey = '';
    private $_secret = '';

    public function execute () 
    {
        // echo 'testing... oauth';

        $client = new AuthorizationCode();
    }
}