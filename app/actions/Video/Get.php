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
        $m_video       = new Ap_Model_Video();
        $m_bucketvideo = new Ap_Model_BucketVideo();

        $where = array('bucket_id' => $token[0], 'src_video_id'=>'');
        if ($search) $where['title'] = new MongoRegex("/{$search}/");
        if ($ids) $where['dst_video_id'] = array('$in' => $ids);
        
        $total = $m_bucketvideo->getCount($where);
        $list  = $m_bucketvideo->Collection->find($where)
            ->limit($pagesize)
            ->skip(($page - 1)*$pagesize)
            ->sort(array('_id'=>1));

        # 获取视频信息
        $vlist = array();
        foreach ($list as $item) {
            $v_id = $item['dst_video_id'];
            $info = $m_video->getOneById($v_id);
            if ( ! $info) continue;

            $info['stringid'] = (string) $item['_id']; # 增加字符串类型ID便于前段读取
            $info['subfiles'] = $m_video->getMany(array('src_id'=>$v_id));
            $vlist[] = $info;
        }

        $this->response(array(
            'list' => $vlist, 
            'page' => $page, 
            'pagesize' => $pagesize, 
            'total' => $total
        ));
    }
}