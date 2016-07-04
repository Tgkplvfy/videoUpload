<?php 

class Authorize_None 
{
    // 检验请求合法性 无校验
    public static function verifyRequest () 
    {
        return TRUE;
    }
}