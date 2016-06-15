<?php

/**
 * 接口调用示例
 */
class DemoController extends Yaf_Controller_Abstract {

    public static $transParams = array (
        array('mime_type'=>'video/mp4', 'fps'=>15, 'audio_bps'=>'64K', 'video_bps'=>'256K', 'width'=>'720', 'hight'=>'480', 'encrypt'=>0), 
        array('mime_type'=>'video/mp4', 'fps'=>20, 'audio_bps'=>'64K', 'video_bps'=>'384K', 'width'=>'1280', 'hight'=>'720', 'encrypt'=>0), 
        array('mime_type'=>'video/mp4', 'fps'=>25, 'audio_bps'=>'64K', 'video_bps'=>'512K', 'width'=>'1280', 'hight'=>'720', 'encrypt'=>0), 
        array('mime_type'=>'video/mpegts', 'fps'=>15, 'audio_bps'=>'64K', 'video_bps'=>'256K', 'width'=>'720', 'hight'=>'480', 'encrypt'=>1), 
        array('mime_type'=>'video/mpegts', 'fps'=>20, 'audio_bps'=>'64K', 'video_bps'=>'384K', 'width'=>'1280', 'hight'=>'720', 'encrypt'=>1), 
        array('mime_type'=>'video/mpegts', 'fps'=>25, 'audio_bps'=>'64K', 'video_bps'=>'512K', 'width'=>'1280', 'hight'=>'720', 'encrypt'=>1) 
    );

    public function indexAction() {

        $params = array(
            'bucket_id' => 'www', 
            'token' => 'Jshuw235mkkdjgmclt_e2iwrjm', 
            'title' => 'a file title', 
            'priority' => 'high', 
            'target' => json_encode(self::$transParams)
        );

        $this->getView()->assign("params", $params);
    }

    // 获取签名的算法
    private function getToken () 
    {
        // 
    }

    // 获取签名字符串
    private function getSignature () 
    {
        // 
    }

    // URL 安全的Base64签名
    public function urlsafeBase64Encode () 
    {
        // 
    }

}