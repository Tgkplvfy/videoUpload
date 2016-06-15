<?php

class VideoController extends Ap_Base_Control {

    public $actions = array (
        'videoget'    => 'actions/Video/Get.php',    # 获取视频信息
        'videopost'   => 'actions/Video/Post.php',   # 修改视频信息
        'videoput'    => 'actions/Video/Put.php',    # 上传视频文件
        'videodelete' => 'actions/Video/Delete.php'  # 删除视频文件
    );
}