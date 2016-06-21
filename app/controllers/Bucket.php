<?php

class BucketController extends Ap_Base_Control {

    public $actions = array (
        'bucketget'    => 'actions/Bucket/Get.php',    # 获取视频信息
        'bucketpost'   => 'actions/Bucket/Post.php',   # 修改视频信息
        'bucketput'    => 'actions/Bucket/Put.php',    # 上传视频文件
        'bucketdelete' => 'actions/Bucket/Delete.php'  # 删除视频文件
    );
}