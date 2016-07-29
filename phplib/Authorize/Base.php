<?php 

class Authorize_Base 
{
    private $request_token     = ''; # 请求的原始token

    private $request_timestamp = 0;  # 请求的原始timestamp
    
    // 检验请求合法性 无校验
    public static function verifyRequest () 
    {
        return TRUE;
    }

    // 获取请求Token信息
    public static function getRequestToken () 
    {
        if (isset($_SERVER['HTTP_TOKEN']) && strpos($_SERVER['HTTP_TOKEN'], ':') !== FALSE) 
            $this->request_token = $_SERVER['HTTP_TOKEN'];

        if (isset($_SERVER['HTTP_TIMESTAMP'])) 
            $this->request_timestamp = $_SERVER['HTTP_TIMESTAMP'];
        
        if (isset($_REQUEST['token']) && strpos($_REQUEST['token'], ':') !== FALSE) 
            $this->request_token = $_REQUEST['token'];
            
        if (isset($_REQUEST['timestamp'])) 
            $this->request_timestamp = $_REQUEST['timestamp'];
    }

    // 获取请求app信息
    public static function getRequestClientInfo () 
    {
        list($appkey, $secret) = explode(':', trim($_REQUEST['token']));
    }
}