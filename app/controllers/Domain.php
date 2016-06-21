<?php

class DomainController extends Ap_Base_Control {

    public $actions = array (
        'domainget'    => 'actions/Domain/Get.php',    # 获取视频信息
        'domainpost'   => 'actions/Domain/Post.php',   # 修改视频信息
        'domainput'    => 'actions/Domain/Put.php',    # 上传视频文件
        'domaindelete' => 'actions/Domain/Delete.php'  # 删除视频文件
    );
}