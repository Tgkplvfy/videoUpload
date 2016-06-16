<?php 

class DemoPostAction extends Ap_Base_Action 
{

    public function execute () 
    {
        $data = array(
            'bucket_id' => 'www', 
            'token' => 'Jshuw235mkkdjgmclt_e2iwrjm', 
            'title' => 'a file title', 
            'priority' => 'normal' 
            // 'target' => json_encode(self::$transParams)
        );

        $apMongo = new Ap_DB_MongoDB ();
        $MongoClient = $apMongo->getCollection('video');

        // $res = $MongoClient->save($data);
        // print_r($res);

        $cursor = $MongoClient->find(array('bucket_id'=>'www'));
        foreach($cursor as $item) 
        {
            print_r($item);
        }
    }

}