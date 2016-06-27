<?php 

/**
 * 视频上传
 */
class VideoPutAction extends Ap_Base_Action 
{
    const MONGO_VIDEO_DB        = 'storage';
    const MONGO_TBL_VIDEO       = 'video';
    const MONGO_TBL_BUCKETVIDEO = 'bucketvideo';

    public function execute () 
    {
        $post = $this->getRequest()->getPost();
        $file = $this->getRequest()->getFiles();

        // 01. 获取并检验参数 TODO
        if ( ! isset($file['files'])) {
            $this->response(NULL, 400, 'no files uploaded');
        }

        # 获取bucket_id
        $tokenarr = explode(':', $post['token']);
        $bucketid = $tokenarr[0];

        # 校验上传后的转码设置，最多设置6个转码参数
        $parameter = array_slice($post['parameter'], 0, 6, TRUE);
        $transcode = array();
        foreach ($parameter as $key => $val) {
            isset(Ap_Vars::$transSettings[$key]) && $transcode[] = Ap_Vars::$transSettings[$key];
        }

        // 02. 上传文件、保存文件到 FastDFS
        // $allowTypes = array('avi', 'mp4', 'flv', 'jpg', 'jpeg');    # 测试允许jpg类型
        $allowTypes = array('avi', 'mp4', 'flv');                   # 允许文件类型
        $allowSize  = 2147483648;                                   # 最大 2GB
        $Uploader   = new Ap_Util_Upload($file['files'], NULL, $allowTypes, $allowSize);
        if ( ! $Uploader->upload()) # 上传失败！
        {
            // Ap_Log::log($Uploader->last_error);          # 需要在response前执行
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
        $videoTbl = $apMongo->getCollection(Ap_Vars::MONGO_TBL_VIDEO);

        $files    = array();
        
        foreach ($savedFiles as $file) 
        {
            $mongoFile = isset($file['saved']) ? $file['mongo'] : $this->__saveMainFile($file);
            $this->__saveBucketVideo($bucketid, $title, '', $mongoFile['_id']); # 保存上传记录

            if (isset($file['pic']) && file_exists($file['pic'])) unlink($file['pic']); # 删除临时缩略图文件

            # 存储需要转码的文件信息
            foreach ($transcode as $trans) {
                $transFile = $videoTbl->findOne(array('transType' => $trans['type'], 'src_id' => $mongoFile['_id']));
                if ( ! $transFile) {
                    $transFile = array(
                        # 原始文件信息
                        '_id'      => new MongoId(), 
                        'src_id'   => $mongoFile['_id'], 
                        'filename' => $file['filename'], 
                        # 文件基本信息
                        'title'     => $title, 
                        'size'      => 0, 
                        'mime_type' => $trans['mime_type'], 
                        'md5_file'  => '', 
                        'pic'       => $mongoFile['pic'], 
                        'duration'  => $file['duration'], 
                        # 文件转码信息
                        "transType"          => $trans['type'], 
                        "fps"                => $trans['fps'], 
                        "audio_bps"          => $trans['audio_bps'], 
                        "video_bps"          => $trans['video_bps'], 
                        "width"              => $trans['width'], 
                        "height"             => $trans['height'], 
                        "encrypt"            => $trans['encrypt'], 
                        "status"             => $file['status'], 
                        "convert_begin_time" => '', 
                        "convert_end_time"   => '', 
                        "proc_id"            => '', 
                        "secret_key"         => '', 
                        "fragment_duration"  => '', 
                        "fragments"          => '' 
                    );
                    $videoTbl->save($transFile);
                }

                # 如果已经保存过转码文件记录，不再记录！
                $this->__saveBucketVideo($bucketid, $title, $mongoFile['_id'], $transFile['_id']);
            }

            $files[] = $mongoFile;
        }

        return $files;
    }

    # 保存主文件
    private function __saveMainFile ($file) 
    {
        # 缩略图文件转储到MongoDB并更新字段值
        $imgAdapter = new Ap_ImageAdapter ();
        $picHashKey = $imgAdapter->write($file['pic']);
        $videoThumb = $imgAdapter->getURL($picHashKey, '720', '480');
        $mongoData  = array(
            '_id'       => new MongoId(), 
            // 'bucket_id' => 'www', 
            'filename'  => $file['filename'], 
            'size'      => $file['size'], 
            'mime_type' => $file['mime_type'], 
            'md5_file'  => $file['md5'], 
            'pic'       => $videoThumb, 
            'watermark' => '', 
            'status'    => $file['status'], 
            'duration'  => $file['duration'] 
            // 'fragment'  => $transcode 
        );

        $apMongo  = new Ap_DB_MongoDB ();
        $apMongo->getCollection(Ap_Vars::MONGO_TBL_VIDEO)->save($mongoData);
        
        return $mongoData;
    }

    # 保存视频文件
    private function __saveBucketVideo ($bucketid, $title, $src_video_id, $dst_video_id) 
    {
        $apMongo = new Ap_DB_MongoDB();
        $where = array(
            'bucket_id'    => $bucketid, 
            'title'        => $title, 
            'dst_video_id' => $dst_video_id
        );

        $tblBVideo = $apMongo->getCollection(Ap_Vars::MONGO_TBL_BUCKETVIDEO);
        if ( ! $tblBVideo->findOne($where)) {
            return $tblBVideo->save(array(
                '_id'          => new MongoId(), 
                'bucket_id'    => $bucketid, 
                'title'        => $title, 
                'src_video_id' => $src_video_id, 
                'dst_video_id' => $dst_video_id 
            ));
        }
    }

    # 加入转码队列
    private function sendToQueue ($files) 
    {
        if (empty($files)) return TRUE;

        # 每个文件执行所有转码任务
        foreach ($files as $file) {
            $job = Ap_Service_Gearman::createVideoJobs($file);
            if ( ! $job) continue;
        }

        return TRUE;
    }

}