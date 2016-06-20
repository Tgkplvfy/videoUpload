<?php 

/**
 * 获取视频列表
 */

// use MongoDB\BSON\Regex;

class VideoGetAction extends Ap_Base_Action 
{
    public function execute () 
    {
        $appkey   = trim($_GET['token']);
        $search   = trim($_GET['search']);
        $page     = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $pagesize = isset($_GET['pagesize']) ? (int) $_GET['pagesize'] : 20;

        # 获取搜索参数
        $apMongo    = new Ap_DB_MongoDB ();
        $collection = $apMongo->getCollection('video');

        $data = $collection->find(array('title'=>new MongoRegex("/{$search}/")))->limit($pagesize)->skip(($page - 1)*$pagesize)->sort(array('_id'=>-1));

        $list = iterator_to_array($data);
        echo json_encode($list);
    }
}