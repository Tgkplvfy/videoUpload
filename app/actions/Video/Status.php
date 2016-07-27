<?php 

/**
 * 获取视频列表
 */

class StatusAction extends Ap_Base_Action 
{

    public function execute () 
    {
        $bkt_video_ids = $this->getRequest()->getParam('bkt_video_ids');
        $bkt_ids_arr   = array_unique(explode(',', $bkt_video_ids));
        $requesttoken  = Yaf_Registry::get('request_token');
        $appkey        = isset($requesttoken['appkey']) ? $requesttoken['appkey'] : '';

        $mongoids = array();
        foreach ($bkt_ids_arr as $id) $mongoids[] = new MongoId($id);

        $m_video  = new Ap_Model_Video();
        $m_bvideo = new Ap_Model_BucketVideo();
        $bktvideos = $m_bvideo->getMany(array('_id'=>array('$in'=>$mongoids), 'bucket_id'=>$appkey));

        // 获取bkt中的视频ID。根据视频ID获取转码文件ID
        $video_status = array();
        foreach ($bktvideos as $bktvideo) {
            $subs = $m_bvideo->getMany(array('upload_id'=>$bktvideo['upload_id'], 'src_video_id'=>$bktvideo['dst_video_id']));
            if ( ! $subs) 
            {
                $video_status[(string)$bktvideo['_id']] = array();
                continue;
            }
            $subids = array();
            foreach ($subs as $sub) $subids[] = $sub['dst_video_id'];
            $videos = $m_video->getMany(array('_id' => array('$in' => $subids)));
            if ( ! $videos) 
            {
                $video_status[(string)$bktvideo['_id']] = array();
                continue;
            }

            $statuses = array();
            foreach ($videos as $video) {
                $statuses[] = $video['status'];
            }

            $video_status[(string)$bktvideo['_id']] = $statuses;
        }

        $this->response($video_status);
    }
}