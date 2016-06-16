<?php 

class TestGetAction extends Ap_Base_Action 
{
    const GEARMAN_FUN_DEFAULT = 'imooc_video_convert';

    public function execute () 
    {
        // $gearman_config = Yaf;
        $job = array ();

        $gmclient = new GearmanClient();
        $gmclient->addServer($_SERVER['GEARMAN_HOST'], $_SERVER['GEARMAN_PORT']);

        $workload = '{"_id":"576271503b46e19547000004", "fragment":{"mime_type":"video/mp4", "fps": "15", "audio_bps":"64K", "video_bps":"256K", "width":"720", "high":"480", "encrept":1}}';

        $uniqKey = uniqid();
        $result  = $gmclient->doBackground(self::GEARMAN_FUN_DEFAULT, $workload, $uniqKey);

        //写失败之后，停1秒，二次写。
        if (!$result) {
            sleep(1);
            $result = $gmclient->doBackground(self::GEARMAN_FUN_DEFAULT, $workload, $uniqKey);  
            if(!$result){
                $errno = $gmclient->getErrno();
                var_dump($errno, $gmclient->error());
            }      
        }

        var_dump($result);
    }
}