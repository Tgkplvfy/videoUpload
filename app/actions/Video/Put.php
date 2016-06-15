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

        // 02. 保存文件 FastDFS
        $Uploader = new Ap_Util_Upload($file['files'], NULL, array('avi', 'mp4', 'flv', 'jpg', 'jpeg'), 2147483648); # 最大 2GB

        # 上传失败！
        if ( ! $Uploader->upload()) 
        {
            // $this->response($Uploader->last_error); // 失败错误信息
            echo $Uploader->last_error;
            exit();
        }

        // 03. 存储 MongoDB
        $savedFiles = $Uploader->getSaveInfo();
        $apMongo    = new Ap_DB_MongoDB ();
        foreach ($savedFiles as $file) 
        {
            $mongoData = array(
                'bucket_id' => 'www', 
                'filename'  => $fileInfo['saveas'], 
                'title'     => $post['title'], 
                'size'      => $fileInfo['']
            );
            $apMongo->insert('video', $fileInfo);
        }

        // 04. 加入转码队列
        $queue = new Ap_Queue_Transcode ();
        $queue->AddToJob('transcode', 'low', $fileInfo);

        // 05. 返回信息
        $this->response(json_encode(array('error'=>0)));
    }
}