<?php

class VideoController extends Yaf_Controller_Abstract {

    public function indexAction() {

        $method = strtolower($_SERVER['REQUEST_METHOD']);
        if ($method == 'post') 
        {
            $post = $this->getRequest()->getPost();

            // 针对表单提交，无法使用PUT，DELETE，可在表单中增加_method字段模拟
            if (isset($post['_method'])) $method = $post['_method']; 
        }

        if (in_array($method, array('get', 'post', 'put', 'delete'))) 
            $methodFunc = $method.'Action';

        if (method_exists($this, $methodFunc))
            return $this->$methodFunc();
        else 
            exit('method not supported');
    }

    // 获取操作
    public function getAction () 
    {
        echo 'get action';
    }

    // 编辑操作
    public function postAction () 
    {
        echo 'post action';
        $post = $this->getRequest()->getPost();
        $file = $this->getRequest()->getFiles();
    }

    // 删除操作
    public function deleteAction () 
    {
        $request = $this->getRequest();
        var_dump($request->getMethod());
    }

    // 视频上传接口
    public function putAction () 
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


    // 解析HTTP原始请求主体的内容信息
    public function parseRawInput ($data) 
    {
        // $
    }
    
}