<?php 

/**
 * 视频上传
 */
class TestuploadAction extends Ap_Base_Action 
{
    const MONGO_VIDEO_DB        = 'storage';
    const MONGO_TBL_VIDEO       = 'video';
    const MONGO_TBL_BUCKETVIDEO = 'bucketvideo';

    const GEARMAN_FUN_DEFAULT    = 'imooc_video_convert'; # Gearman 默认转码任务

    const TRANSCODE_JOB_WAITING =  1;   # 转码等待开始（未开始）
    const TRANSCODE_JOB_STARTED =  2;   # 转码队列进行中
    const TRANSCODE_JOB_DONE    =  3;   # 转码完成
    const TRANSCODE_JOB_FAILED  = -1;   # 转码失败

    # 内置转码类型 type 代表类型编码，用于标示
    public static $transSettings = array(
        array('type'=>'0', 'mime_type'=>'video/mp4',    'fps'=>15, 'audio_bps'=>'64K', 'video_bps'=>'256K', 'width'=>'720',  'height'=>'480', 'encrypt'=>0), 
        array('type'=>'1', 'mime_type'=>'video/mp4',    'fps'=>20, 'audio_bps'=>'64K', 'video_bps'=>'384K', 'width'=>'1280', 'height'=>'720', 'encrypt'=>0), 
        array('type'=>'2', 'mime_type'=>'video/mp4',    'fps'=>25, 'audio_bps'=>'64K', 'video_bps'=>'512K', 'width'=>'1280', 'height'=>'720', 'encrypt'=>0), 
        array('type'=>'3', 'mime_type'=>'video/mpegts', 'fps'=>15, 'audio_bps'=>'64K', 'video_bps'=>'256K', 'width'=>'720',  'height'=>'480', 'encrypt'=>1), 
        array('type'=>'4', 'mime_type'=>'video/mpegts', 'fps'=>20, 'audio_bps'=>'64K', 'video_bps'=>'384K', 'width'=>'1280', 'height'=>'720', 'encrypt'=>1), 
        array('type'=>'5', 'mime_type'=>'video/mpegts', 'fps'=>25, 'audio_bps'=>'64K', 'video_bps'=>'512K', 'width'=>'1280', 'height'=>'720', 'encrypt'=>1) 
    );

    public function execute () 
    {
        $post = $this->getRequest()->getPost();
        $file = $this->getRequest()->getFiles();

        // 01. 获取并检验参数 TODO
        if ( ! isset($file['files'])) {
            $this->response(NULL, 400, 'no files uploaded');
        }
        $bucketid = 'www';
        if (isset($post['token'])) {
            $tokenarr = explode(':', $post['token']);
            $bucketid = $tokenarr[0];
        }

        # 校验上传后的转码设置，最多设置6个转码参数
        $parameter = array_slice($post['parameter'], 0, 6, TRUE);
        $transcode = array();
        foreach ($parameter as $key => $val) {
            isset(self::$transSettings[$key]) && $transcode[] = self::$transSettings[$key];
        }

        // 02. 保存文件 FastDFS
        // $allowTypes = array('avi', 'mp4', 'flv', 'jpg', 'jpeg');    # 测试允许jpg类型
        $allowTypes = array('avi', 'mp4', 'flv');                   # 允许文件类型
        $allowSize  = 2147483648;                                   # 最大 2GB
        $Uploader   = new Ap_Util_Upload($file['files'], NULL, $allowTypes, $allowSize);
        if ( ! $Uploader->upload()) # 上传失败！
        {
            // Ap_Log::log($Uploader->last_error);
            $this->response(NULL, 500, 'upload failed'); # 失败错误信息
        }

        // 03. 转储文件信息到 MongoDB
        $savedFiles  = $Uploader->getSaveInfo();
        $files = $this->saveToMongoDB($savedFiles, $post['title'], $bucketid, $transcode);

        // 04. 加入转码队列
        $this->sendToQueue($files);

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
    private function saveToMongoDB ($savedFiles, $title = '', $bucketid, $transcode) 
    {
        $apMongo  = new Ap_DB_MongoDB ();
        $videoTbl = $apMongo->getCollection(self::MONGO_TBL_VIDEO);

        $files      = array();
        $imgAdapter = new Ap_ImageAdapter ();
        foreach ($savedFiles as $file) 
        {
            # 缩略图文件转储到MongoDB并更新字段值
            $picHashKey = $imgAdapter->write($file['pic']);
            $videoThumb = $imgAdapter->getURL($picHashKey, '720', '480');
            $mongoData  = array(
                '_id'       => new MongoId(), 
                // 'bucket_id' => 'www', 
                'filename'  => $file['saveas'], 
                'size'      => $file['size'], 
                'mime_type' => $file['mime_type'], 
                'md5_file'  => $file['md5'], 
                'pic'       => $videoThumb, 
                'watermark' => '', 
                'duration'  => $file['duration'] 
                // 'fragment'  => $transcode 
            );

            $videoTbl->save($mongoData);
            $apMongo->getCollection(self::MONGO_TBL_BUCKETVIDEO)->save(array(
                '_id'          => new MongoId(), 
                'bucket_id'    => $bucketid, 
                'title'        => $title, 
                'src_video_id' => $mongoData['_id'], 
                'dst_video_id' => '' 
            ));
            if (file_exists($file['pic'])) unlink($file['pic']); # 删除临时缩略图文件

            # 存储需要转码的文件信息
            foreach ($transcode as $trans) {
                $transed = $videoTbl->findOne(array('transType' => $trans['type'], 'src_id' => $mongoData['_id']));
                if ($transed) continue;

                $transFile = array(
                    # 原始文件信息
                    '_id'      => new MongoId(), 
                    'src_id'   => $mongoData['_id'], 
                    'filename' => $file['saveas'], 
                    # 文件基本信息
                    'title'     => $title, 
                    'size'      => 0, 
                    'mime_type' => $trans['mime_type'], 
                    'md5_file'  => '', 
                    'pic'       => $videoThumb, 
                    'duration'  => $file['duration'], 
                    # 文件转码信息
                    "transType"          => $trans['type'], 
                    "fps"                => $trans['fps'], 
                    "audio_bps"          => $trans['audio_bps'], 
                    "video_bps"          => $trans['video_bps'], 
                    "width"              => $trans['width'], 
                    "height"             => $trans['height'], 
                    "encrypt"            => $trans['encrypt'], 
                    "status"             => self::TRANSCODE_JOB_WAITING, 
                    "convert_begin_time" => '', 
                    "convert_end_time"   => '', 
                    "proc_id"            => '', 
                    "secret_key"         => '', 
                    "fragment_duration"  => '', 
                    "fragments"          => '' 
                );

                # 删除临时缩略图文件
                $videoTbl->save($transFile);
                $apMongo->getCollection(self::MONGO_TBL_BUCKETVIDEO)->save(array(
                    '_id'          => new MongoId(), 
                    'bucket_id'    => $bucketid, 
                    'title'        => $title, 
                    'src_video_id' => $mongoData['_id'], 
                    'dst_video_id' => $transFile['_id'] 
                ));
            }

            $files[] = $mongoData;
        }

        return $files;
    }

    # 加入转码队列
    private function sendToQueue ($files) 
    {
        if (empty($files)) return TRUE;

        # 每个文件执行所有转码任务
        foreach ($files as $file) {
            $job = $this->createJobs($file);
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
    private function createJobs ($file) 
    {
        $gmclient = new GearmanClient();
        $gmclient->addServer($_SERVER['GEARMAN_HOST'], $_SERVER['GEARMAN_PORT']);

        $apMongo = new Ap_DB_MongoDB();
        $pengingJobs = $apMongo->getCollection(self::MONGO_TBL_VIDEO)->find(array(
            'src_id' => $file['_id'], 
            'status' => self::TRANSCODE_JOB_WAITING 
        ));
        $jobs = iterator_to_array($pengingJobs);
        if (empty($jobs)) return TRUE;

        foreach ($jobs as $job) {
            $fragment = array (
                'mime_type' => $job['mime_type'], 
                'fps'       => $job['fps'], 
                'audio_bps' => $job['audio_bps'], 
                'video_bps' => $job['video_bps'], 
                'width'     => $job['width'], 
                'height'    => $job['height'], 
                'encrypt'   => $job['encrypt']
            );

            $uniqKey  = (string) $job['_id'];
            $workload = json_encode(array('_id' => $uniqKey, 'fragment' => $fragment));

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