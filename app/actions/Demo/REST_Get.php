<?php 

class DemoGetAction extends Ap_Base_Action 
{
    public static $transParams = array (
        array('mime_type'=>'video/mp4', 'fps'=>15, 'audio_bps'=>'64K', 'video_bps'=>'256K', 'width'=>'720', 'hight'=>'480', 'encrypt'=>0), 
        array('mime_type'=>'video/mp4', 'fps'=>20, 'audio_bps'=>'64K', 'video_bps'=>'384K', 'width'=>'1280', 'hight'=>'720', 'encrypt'=>0), 
        array('mime_type'=>'video/mp4', 'fps'=>25, 'audio_bps'=>'64K', 'video_bps'=>'512K', 'width'=>'1280', 'hight'=>'720', 'encrypt'=>0), 
        array('mime_type'=>'video/mpegts', 'fps'=>15, 'audio_bps'=>'64K', 'video_bps'=>'256K', 'width'=>'720', 'hight'=>'480', 'encrypt'=>1), 
        array('mime_type'=>'video/mpegts', 'fps'=>20, 'audio_bps'=>'64K', 'video_bps'=>'384K', 'width'=>'1280', 'hight'=>'720', 'encrypt'=>1), 
        array('mime_type'=>'video/mpegts', 'fps'=>25, 'audio_bps'=>'64K', 'video_bps'=>'512K', 'width'=>'1280', 'hight'=>'720', 'encrypt'=>1) 
    );

    public function execute () 
    {
        $action = isset($_GET['action']) ? $_GET['action'] : '/video';
        $params = array(
            // 'bucket_id' => 'www', 
            'token' => 'imooc:upload', 
            'title' => 'a file title', 
            'priority' => 'high', 
            // 'target' => json_encode(self::$transParams)
        );

        $test = Ap_Vars::MONGO_TBL_VIDEO;

        $this->getView()->assign("params", $params);
        $this->getView()->assign("action", $action);
        $this->getView()->display('demo/index.phtml');
    }

    // 根据access和secret获取一个api请求的token
    public function getToken ($access, $secret) 
    {
        // 
    }

    # 获取签名算法
    public function getSignature () 
    {
        // 
    }

    // url安全的Base64编码
    public function urlsafe_Base64_Encode () 
    {
        // 
    }
}