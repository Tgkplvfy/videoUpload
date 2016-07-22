<?php 

/**
 * 获取视频列表
 */
class InfoAction extends Ap_Base_Action 
{

    public function execute () 
    {
        $bkt_video_id = $this->getRequest()->getParam('bkt_video_id');
        $requesttoken = Yaf_Registry::get('request_token');
        $appkey = isset($requesttoken['appkey']) ? $requesttoken['appkey'] : '';

        $m_video  = new Ap_Model_Video();
        $m_bvideo = new Ap_Model_BucketVideo();
        $bktvideo = $m_bvideo->getOne(array('_id'=>new MongoId($bkt_video_id), 'bucket_id'=>$appkey));
        if (empty($bktvideo)) $this->response(NULL, 404, 'video not found!');

        $video_info = $m_video->getOneById($bktvideo['dst_video_id']);
        if (empty($video_info)) $this->response(NULL, 404, 'video not found!');

        $video_info['_id']      = $bktvideo['_id'];      # 仍使用buckt_video的_id返回
        $video_info['title']    = $bktvideo['title'];    # 使用bucket_video中的标题返回
        $video_info['stringid'] = $bkt_video_id;         # 同第一条

        # TODO: 获取文件上传水印？

        # 获取当次上传的转码文件
        $subs = $m_bvideo->getMany(array('upload_id'=>$bktvideo['upload_id'], 'src_video_id'=>$bktvideo['dst_video_id']));
        if ($subs) {
            foreach ($subs as $sub) $subids[] = $sub['dst_video_id'];
            $video_info['subfiles'] = $m_video->getMany(array('_id'=>array('$in'=>$subids))); 
        }

        $this->response($video_info);
    }
}