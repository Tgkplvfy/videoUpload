<?php 

/**
 * 获取视频列表
 */

// use MongoDB\BSON\Regex;

class VideoinfoGetAction extends Ap_Base_Action 
{

    public function execute () 
    {
        $token    = isset($_REQUEST['token']) ? explode(':', trim($_REQUEST['token'])) : '';
        $vid      = isset($_GET['id']) ? trim($_GET['id']) : '';

        # 获取搜索参数
        $m_video = new Ap_Model_Video();
        $m_bucketvideo = new Ap_Model_BucketVideo();

        $videoid = new MongoId($vid);

        $where = array('bucket_id' => $token[0], 'dst_video_id'=>$videoid);
        
        $video = $m_bucketvideo->getOne($where);
        if ( ! $video) $this->response(NULL, 404, 'not found');

        $videoInfo = $m_video->getOneById($videoid);

        // 是否包含转码文件，获取转码文件信息
        $subfiles = $m_bucketvideo->getMany(array('src_video_id' => $videoid, 'upload_id' => $video['upload_id']));
        if ($subfiles) {
            $subids   = array();
            foreach ($subfiles as $file) $subids[] = $file['dst_video_id'];
            $videoInfo['subfiles'] = $m_video->getMany(array('_id'=>array('$in'=>$subids))); # 获取当次上传的转码文件
        }

        $this->response($videoInfo);
    }
}