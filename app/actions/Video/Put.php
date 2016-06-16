<?php 

// 视频上传
class VideoPutAction extends Ap_Base_Action 
{
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

        // 02. 保存文件 FastDFS
        $allowTypes = array('avi', 'mp4', 'flv', 'jpg', 'jpeg');    # 测试允许jpg类型
        $allowSize  = 2147483648;                                   # 最大 2GB
        $Uploader = new Ap_Util_Upload($file['files'], NULL, $allowTypes, $allowSize);
        if ( ! $Uploader->upload()) # 上传失败！
        {
            // Ap_Log::log($Uploader->last_error);
            $this->response(NULL, 500, 'upload failed'); # 失败错误信息
        }

        // 03. 转储文件信息到 MongoDB
        $savedFiles = $Uploader->getSaveInfo();
        $apMongo    = new Ap_DB_MongoDB ();
        foreach ($savedFiles as $file) 
        {
            $mongoData = array(
                'bucket_id' => 'www', 
                'filename'  => $file['saveas'], 
                'title'     => $post['title'], 
                'size'      => $file['size'], 
                'mime_type' => $file['mime_type'], 
                'md5_file'  => '', 
                'pic'       => '', 
                'duration'  => '', 
                'fragment'  => array() 
            );

            print_r($mongoData);
            // $apMongo->insert('video', $mongoData);
        }

        // 04. 加入转码队列
        // $queue = new Ap_Queue_Transcode ();
        // $queue->AddToJob('transcode', 'low', $fileInfo);

        // 05. 返回信息
        // $this->response($mongoData);
        // $this->response(array(
        //     'id' => 'dsfdsg', 
        //     'pic' => 'askiofvdv.jpg'
        // ));
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
    private function sendToQueue ($file, $setting) 
    {
        // 
    }

}