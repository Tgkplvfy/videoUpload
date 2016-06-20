<?php 

class TestGetAction extends Ap_Base_Action 
{
    const GEARMAN_FUN_DEFAULT = 'imooc_video_convert';

    public static $funcs = array(
        0 => 'testHello', 
        1 => 'testMongo', 
        2 => 'testGearman', 
        3 => 'testToken'
    );

    public function execute () 
    {
        $method = isset($_GET['func']) ? (int) $_GET['func'] : 0;

        $method = self::$funcs[$method];
        $this->$method();
    }

    public function testHello () 
    {
        echo 'Hello Test ! 以下方法可用 ~_~ [添加?func=] <br /><br />';
        foreach (self::$funcs as $key => $val) {
            echo $key . ': ' . $val . '<br>';
        }

        // var_dump($this->getRequest()->getParams());
        // var_dump($_GET);
    }

    public function testMongo () 
    {
        $mongo = new Ap_DB_MongoDB ();

        $collection = $mongo->getCollection('video');
        $res = $collection->find(array('md5_file'=>'9f036fb5ba36e0eef3910795dbb38884'));

        // var_dump($res);
        foreach ($res as $key => $val) {
            var_dump($val);
        }
    }

    public function testGearman () 
    {
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

    public function testToken () 
    {
        $secret_hash = hash_hmac('sha1', 'parameters=1,2,3,4,5,6&priority=normal&title=Python入门教程之装饰器7-2', 'shizhan_secret');

        echo 'hash_hmac: ' . $secret_hash . '<br />';
        echo 'base64: ' . base64_encode($secret_hash) . '<br />';
    }

}