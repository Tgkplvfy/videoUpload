<?php 

/**
 * 获取视频列表
 */

class HxkAction extends Ap_Base_Action 
{

    public function execute () 
    {
        // var_dump($_GET);

        $this->response($_GET);
    }
}