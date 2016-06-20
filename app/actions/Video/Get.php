<?php 

/**
 * 获取视频列表
 */

// use MongoDB\BSON\Regex;

class VideoGetAction extends Ap_Base_Action 
{
    public function execute () 
    {
        // $appkey   = trim($_GET['token']);
        $search   = isset($_GET['search']) ? trim($_GET['search']) : '';
        $page     = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $pagesize = isset($_GET['pagesize']) ? (int) $_GET['pagesize'] : 20;

        # 获取搜索参数
        $apMongo    = new Ap_DB_MongoDB ();
        $collection = $apMongo->getCollection('video');

        $where = array(
            // 'bucket_id' => '$in "www"'
        );
        if ($search) $where['title'] = new MongoRegex("/{$search}/");
        
        $total = $collection->find($where)->count();
        
        $list = $collection->find($where)
            ->limit($pagesize)
            ->skip(($page - 1)*$pagesize)
            ->sort(array('_id'=>1));

        $this->response(array(
            'list' => iterator_to_array($list), 
            'page' => $page, 
            'pagesize' => $pagesize, 
            'total' => $total
        ));
    }
}