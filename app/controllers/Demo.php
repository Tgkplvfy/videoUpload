<?php

/**
 * 接口调用示例
 */
class DemoController extends Ap_Base_Control {

    public $actions = array (
        'demoget'    => 'actions/Demo/Get.php',    # 接口调用表单示例
        'demopost'   => 'actions/Demo/Post.php',   # 修改视频信息
        // 'demoput'    => 'actions/Demo/Put.php',    # 上传视频文件
        // 'demodelete' => 'actions/Demo/Delete.php'  # 删除视频文件
    );
}