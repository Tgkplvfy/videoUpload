<?php 

/**
 * 获取视频列表
 */

class HxkAction extends Ap_Base_Action 
{
    private $fps_settings = array(
        'low' => 15, 
        'medium' => 20, 
        'high' => 25 
    );

    public function execute () 
    {
        $definition   = $this->getRequest()->getParam('definition');
        $bkt_video_id = $this->getRequest()->getParam('bkt_video_id');
        $requesttoken = Yaf_Registry::get('request_token');
        $appkey = isset($requesttoken['appkey']) ? $requesttoken['appkey'] : '';

        $m_video  = new Ap_Model_Video();
        $m_bvideo = new Ap_Model_BucketVideo();
        $bktvideo = $m_bvideo->getOne(array('_id'=>new MongoId($bkt_video_id), 'bucket_id'=>$appkey));
        if (empty($bktvideo)) $this->response(NULL, 404, 'video not found!');

        // 获取子文件，并从子文件中获取相应清晰度的ts文件信息
        $subs = $m_bvideo->getMany(array('upload_id'=>$bktvideo['upload_id'], 'src_video_id'=>$bktvideo['dst_video_id']));
        if ( ! $subs) $this->response(NULL, 404, 'no transcode files!');

        $fps = $this->fps_settings[$definition];
        foreach ($subs as $sub) $subids[] = $sub['dst_video_id'];
        $video = $m_video->getOne(array(
            '_id'       => array('$in' => $subids), 
            'mime_type' => 'video/mpegts', 
            // 'status'    => Ap_Vars::FILESTATUS_FINISHED, 
            'fps'       => $fps
        ));
        if ( ! $video) $this->response(NULL, 404, 'no specified video definition!');
        if ($video['status'] != Ap_Vars::FILESTATUS_FINISHED) $this->response(NULL, 404, 'file status:' . $video['status']);

        $secretkey = $video['secret_key'];
        $key_info = pack("H32", $secretkey);
        $encryptor = new Ap_EncryptCommon();
        $hxk = $encryptor->m3u8Encrypt($key_info);
        $this->response($hxk);
    }
}