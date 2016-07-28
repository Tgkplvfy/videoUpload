<?php 

/**
 * 获取签名的算法
 * - 根据API请求参数，获取请求签名
 * - @author qirl 2016-07-18
 */

class Signature 
{
    private static $appkey = 'imooc';
    private static $secret = 'upload';

    /**
     * @function getSignature 获取API请求的Token
     * @param $params API请求的参数。通常是 $_REQUEST
     * 
     * @return Base64编码的签名
     */
    public function getToken ($params = array()) 
    {
        # 默认使用$_REQUEST作为参数
        $params = empty($params) ? $_REQUEST : $params;

        # 获取BASE64编码的签名
        $signature = self::getSignature($params);
        
        # 根据签名获取token
        $token = self::$appkey . ':' . $signature;

        return $token;
    }

    /**
     * @function getSignature 获取API请求的签名
     * @param $params API请求的参数。通常是 $_REQUEST
     * 
     * @return Base64编码的签名
     */
    public static function getSignature ($params) 
    {
        // unset($params['token']);                # 过滤token参数
        // unset($params['timestamp']);            # 过滤timestamp参数
        // unset($params['_']);                    # 过滤jquery请求添加的下划线参数

        # 过滤掉请求参数中的数组
        $params = array_filter($params, function($val){
            if (is_string($val)) return strlen($val);
            return false;
        });

        # 排序
        ksort($params);
        # 获取签名
        $signature = hash_hmac('sha1', http_build_query($params), self::$secret);
        # 获取Base64编码的签名
        $url_sign  = self::urlsafe_Base64Encode($signature);

        return $url_sign;
    }

    // URL 安全的base64编码
    private static function urlsafe_Base64Encode ($string) 
    {
        $find = array('+', '/');
        $repl = array('-', '_');

        return str_replace($find, $repl, base64_encode($string));
    }

}