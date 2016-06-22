<?php 

/**
 * 视频上传
 */
class VideoPutAction extends Ap_Base_Action 
{
    const MONGO_VIDEO_DB         = 'storage';
    const MONGO_VIDEO_COLLECTION = 'video';

    const GEARMAN_FUN_DEFAULT    = 'imooc_video_convert'; # Gearman 默认转码任务

    const TRANSCODE_JOB_WAITING =  1;   # 转码等待开始（未开始）
    const TRANSCODE_JOB_STARTED =  2;   # 转码队列进行中
    const TRANSCODE_JOB_DONE    =  3;   # 转码完成
    const TRANSCODE_JOB_FAILED  = -1;   # 转码失败

    public static $transSettings = array(
        array('mime_type'=>'video/mp4',    'fps'=>15, 'audio_bps'=>'64K', 'video_bps'=>'256K', 'width'=>'720',  'height'=>'480', 'encrypt'=>0), 
        array('mime_type'=>'video/mp4',    'fps'=>20, 'audio_bps'=>'64K', 'video_bps'=>'384K', 'width'=>'1280', 'height'=>'720', 'encrypt'=>0), 
        array('mime_type'=>'video/mp4',    'fps'=>25, 'audio_bps'=>'64K', 'video_bps'=>'512K', 'width'=>'1280', 'height'=>'720', 'encrypt'=>0), 
        array('mime_type'=>'video/mpegts', 'fps'=>15, 'audio_bps'=>'64K', 'video_bps'=>'256K', 'width'=>'720',  'height'=>'480', 'encrypt'=>1), 
        array('mime_type'=>'video/mpegts', 'fps'=>20, 'audio_bps'=>'64K', 'video_bps'=>'384K', 'width'=>'1280', 'height'=>'720', 'encrypt'=>1), 
        array('mime_type'=>'video/mpegts', 'fps'=>25, 'audio_bps'=>'64K', 'video_bps'=>'512K', 'width'=>'1280', 'height'=>'720', 'encrypt'=>1) 
    );

    public function execute () 
    {
        if ($this->getRequest()->isPost()) 
        {
            $post = $this->getRequest()->getPost();
            $file = $this->getRequest()->getFiles();
        }

        // 01. 获取并检验参数 TODO
        if ( ! isset($file['files'])) {
            $this->response(NULL, 400, 'no files uploaded');
        }

        # 校验上传后的转码设置，最多设置6个转码参数
        $parameter = array_slice($post['parameter'], 0, 6);
        $transcode = array();
        foreach ($parameter as $key => $val) {
            if (isset(self::$transSettings[$key]))
                $transcode[] = array_merge(self::$transSettings[$key], array('status'=>self::TRANSCODE_JOB_WAITING));
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
        $files = $this->saveToMongoDB($savedFiles, $post['title'], $transcode);

        // 04. 加入转码队列
        $this->sendToQueue($files, $transcode);

        // 05. 返回信息
        $fileList = array ();
        foreach ($files as $file) {
            $fileList[] = array(
                '_id' => (string) $file['_id'], 
                'pic' => $file['pic']
            );
        }
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
    private function saveToMongoDB ($savedFiles, $title = '', $transcode) 
    {
        $apMongo     = new Ap_DB_MongoDB ();
        $MongoClient = $apMongo->getCollection(self::MONGO_VIDEO_COLLECTION);

        $files      = array();
        $imgAdapter = new Ap_ImageAdapter ();
        foreach ($savedFiles as $file) 
        {
            # 缩略图文件转储到MongoDB并更新字段值
            $picHashKey = $imgAdapter->write($file['pic']);
            $mongoData  = array(
                '_id'       => new MongoId(), 
                'bucket_id' => 'www', 
                'filename'  => $file['saveas'], 
                'title'     => $title, 
                'size'      => $file['size'], 
                'mime_type' => $file['mime_type'], 
                'md5_file'  => $file['md5'], 
                'pic'       => $imgAdapter->getURL($picHashKey, '720', '480'), 
                'duration'  => $file['duration'], 
                'fragment'  => $transcode 
            );

            # 删除临时缩略图文件
            $MongoClient->save($mongoData);

            $files[] = $mongoData;
        }

        return $files;
    }

    # 加入转码队列
    private function sendToQueue ($files, $params) 
    {
        if (empty($files) OR empty($params)) return TRUE;

        # 每个文件执行所有转码任务
        foreach ($files as $file) {
            $job = $this->createJobs($file, $params);
            if ( ! $job) continue;
        }

        return TRUE;
    }

    /**
     * @func 创建任务队列
     * @param $files 要转码的文件列表
     * @param $param 转码设定的参数
     *
     * @return boolean 创建成功状态
     */
    private function createJobs ($file, $params) 
    {
        // $job = array ();
        $gmclient = new GearmanClient();
        $gmclient->addServer($_SERVER['GEARMAN_HOST'], $_SERVER['GEARMAN_PORT']);

        $fileid = (string) $file['_id'];
        $i = 0;
        foreach ($params as $param) {
            $uniqKey = $fileid . '_' . $i++;
            $workload = json_encode(array('_id' => $fileid, 'fragment' => $param));
            try {
                $result  = $gmclient->doBackground(self::GEARMAN_FUN_DEFAULT, $workload, $uniqKey);
                if (!$result) {
                    sleep(1);
                    $result = $gmclient->doBackground(self::GEARMAN_FUN_DEFAULT, $workload, $uniqKey);
                    if(!$result){
                        $errno = $gmclient->getErrno();
                        // Ap_Log::log($errno . ':' . $gmclient->error());
                    }
                }
            } catch (Exception $e) {
                // Ap_Log::log($e->getMessage());
            }
        }
    }

}