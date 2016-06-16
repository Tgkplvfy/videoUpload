<?php 

// 视频上传
class VideoPutAction extends Ap_Base_Action 
{
    const MONGO_VIDEO_DB         = 'storage';
    const MONGO_VIDEO_COLLECTION = 'video';

    const GEARMAN_FUN_DEFAULT    = 'imooc_video_convert'; # Gearman 默认转码任务

    public static $transSettings = array(
        array('mime_type'=>'video/mp4', 'fps'=>15, 'audio_bps'=>'64K', 'video_bps'=>'256K', 'width'=>'720', 'hight'=>'480', 'encrypt'=>0), 
        array('mime_type'=>'video/mp4', 'fps'=>20, 'audio_bps'=>'64K', 'video_bps'=>'384K', 'width'=>'1280', 'hight'=>'720', 'encrypt'=>0), 
        array('mime_type'=>'video/mp4', 'fps'=>25, 'audio_bps'=>'64K', 'video_bps'=>'512K', 'width'=>'1280', 'hight'=>'720', 'encrypt'=>0), 
        array('mime_type'=>'video/mpegts', 'fps'=>15, 'audio_bps'=>'64K', 'video_bps'=>'256K', 'width'=>'720', 'hight'=>'480', 'encrypt'=>1), 
        array('mime_type'=>'video/mpegts', 'fps'=>20, 'audio_bps'=>'64K', 'video_bps'=>'384K', 'width'=>'1280', 'hight'=>'720', 'encrypt'=>1), 
        array('mime_type'=>'video/mpegts', 'fps'=>25, 'audio_bps'=>'64K', 'video_bps'=>'512K', 'width'=>'1280', 'hight'=>'720', 'encrypt'=>1) 
    );

    public function execute () 
    {
        if ($this->getRequest()->isPost()) 
        {
            $post = $this->getRequest()->getPost();
            $file = $this->getRequest()->getFiles();
        }

        print_r($post);
        exit();

        // 01. 获取并检验参数 TODO
        if ( ! isset($file['files'])) {
            $this->response(NULL, 400, 'no files uploaded');
        }

        // 校验上传后的转码设置
        $parameter = $post['parameter'];
        $transcode = array();
        foreach ($parameter as $key => $val) {
            $transcode[] = self::$transSettings[$key];
        }

        // 02. 保存文件 FastDFS
        $allowTypes = array('avi', 'mp4', 'flv', 'jpg', 'jpeg');    # 测试允许jpg类型
        $allowSize  = 2147483648;                                   # 最大 2GB
        $Uploader   = new Ap_Util_Upload($file['files'], NULL, $allowTypes, $allowSize);
        if ( ! $Uploader->upload()) # 上传失败！
        {
            // Ap_Log::log($Uploader->last_error);
            $this->response(NULL, 500, 'upload failed'); # 失败错误信息
        }

        // 03. 转储文件信息到 MongoDB
        $savedFiles  = $Uploader->getSaveInfo();
        $apMongo     = new Ap_DB_MongoDB ();
        $MongoClient = $apMongo->getCollection(self::MONGO_VIDEO_COLLECTION);

        $fileList = array();
        $files = array();
        foreach ($savedFiles as $file) 
        {
            $mongoData = array(
                '_id'       => new MongoId(), 
                'bucket_id' => 'www', 
                'filename'  => $file['saveas'], 
                'title'     => $post['title'], 
                'size'      => $file['size'], 
                'mime_type' => $file['mime_type'], 
                'md5_file'  => $file['md5'], 
                'pic'       => $file['pic'], 
                'duration'  => $file['duration'], 
                'fragment'  => array() 
            );

            $MongoClient->save($mongoData);

            $files[] = $mongoData;
            $fileList[] = array(
                '_id' => $mongoData['_id'], 
                'pic' => $mongoData['pic'] 
            );
        }

        // 04. 加入转码队列
        $this->sendToQueue($files, $params);

        // 05. 返回信息
        $this->response($fileList);
    }

    # 校验请求参数
    private function verifyParams () 
    {
        // 
    }

    # 保存上传文件
    private function saveFiles ($files) 
    {
        // 
    }

    # 转储MongoDB
    private function saveToMongoDB ($fileInfos) 
    {
        // 
    }

    # 加入转码队列
    private function sendToQueue ($files, $params) 
    {
        if (empty($params)) return TRUE;
    }

    /**
     * @func 创建任务队列
     * @param $files 要转码的文件列表
     * @param $param 转码设定的参数
     *
     * @return boolean 创建成功状态
     */
    private function createJobs ($files, $param) 
    {
        // 
    }

}