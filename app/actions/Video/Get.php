<?php 

/**
 * 获取视频列表
 */

// use MongoDB\BSON\Regex;

class VideoGetAction extends Ap_Base_Action 
{
    public function execute () 
    {
        $token    = isset($_REQUEST['token']) ? explode(':', trim($_REQUEST['token'])) : '';
        $search   = isset($_GET['search']) ? trim($_GET['search']) : '';
        $ids      = isset($_GET['ids']) ? trim($_GET['ids']) : '';
        $page     = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $pagesize = isset($_GET['pagesize']) ? (int) $_GET['pagesize'] : 20;

        # 获取搜索参数
        $apMongo    = new Ap_DB_MongoDB ();
        $collection = $apMongo->getCollection('bucketvideo');

        $where = array('bucket_id' => $token[0], 'src_video_id'=>'');
        if ($search) $where['title'] = new MongoRegex("/{$search}/");
        if ($ids) $where['dst_video_id'] = array('$in' => $ids);
        
        $total = $collection->find($where)->count();
        
        $list = $collection->find($where)
            ->limit($pagesize)
            ->skip(($page - 1)*$pagesize)
            ->sort(array('_id'=>1));

        $list = iterator_to_array($list);
        foreach ($list as $item) {
            $vids[] = $item['dst_video_id'];
        }

        $vlist = $apMongo->getCollection('video')->find(array(
            '_id' => array ('$in' => $vids)
        ));

        $this->response(array(
            'list' => iterator_to_array($vlist), 
            'page' => $page, 
            'pagesize' => $pagesize, 
            'total' => $total
        ));
    }
}