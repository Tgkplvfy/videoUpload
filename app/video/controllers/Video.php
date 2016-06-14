<?php

class VideoController extends Yaf_Controller_Abstract {
    
    public function indexAction() {

        $method = strtolower($_SERVER['REQUEST_METHOD']);
        if ($method == 'post') 
        {
            $post = $this->getRequest()->getPost();

            // 针对表单提交，无法使用PUT，DELETE，可在表单中增加_method字段
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
        var_dump($this->getRequest()->getPost());

        var_dump($this->getRequest()->getFiles());
    }

    // 删除操作
    public function deleteAction () 
    {
        // 
    }

    // 视频上传接口
    public function putAction () 
    {
        $post = $this->getRequest()->getPost();
        $file = $this->getRequest()->getFiles();

        // 01. 检验参数
        $
        // 02. 保存文件 FastDFS
        // 03. 存储 MongoDB
        // 04. 加入转码队列
        // 05. 返回信息
    }
}