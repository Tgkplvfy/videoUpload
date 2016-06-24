<?php
/**
 * 系统常量定义
 * 
 * @author qirl
 */
class Ap_Vars {

    # MongoDB 相关
    const MONGO_VIDEO_DB        = 'storage';
    const MONGO_TBL_VIDEO       = 'video';
    const MONGO_TBL_BUCKETVIDEO = 'bucket_video';

    # Gearman 相关
    const GEARMAN_FUN_DEFAULT    = 'imooc_video_convert'; # Gearman 默认转码任务

    # fastDFS 相关
    const FASTDFS_FAIL_DIRECTORY = '/storage/fails/';    # fastDFS 转储失败后，fallback目录

    # 文件上传状态
    const FILESTATUS_UPLOADED = 1;   # 已上传（未保存到文件系统）
    const FILESTATUS_SAVED    = 2;   # 以保存到文件系统
    const FILESTATUS_QUEUEING = 3;   # 转码队列排队中
    const FILESTATUS_TRANSING = 4;   # 转码中
    const FILESTATUS_TRANSED  = 5;   # 转码成功
    const FILESTATUS_FINISHED = 6;   # 转码成功并保存成功

    # 默认转码类型 type 代表类型编码，用于标示
    public static $transSettings = array(
        array('type'=>'0', 'mime_type'=>'video/mp4',    'fps'=>15, 'audio_bps'=>'64K', 'video_bps'=>'256K', 'width'=>'720',  'height'=>'480', 'encrypt'=>0), 
        array('type'=>'1', 'mime_type'=>'video/mp4',    'fps'=>20, 'audio_bps'=>'64K', 'video_bps'=>'384K', 'width'=>'1280', 'height'=>'720', 'encrypt'=>0), 
        array('type'=>'2', 'mime_type'=>'video/mp4',    'fps'=>25, 'audio_bps'=>'64K', 'video_bps'=>'512K', 'width'=>'1280', 'height'=>'720', 'encrypt'=>0), 
        array('type'=>'3', 'mime_type'=>'video/mpegts', 'fps'=>15, 'audio_bps'=>'64K', 'video_bps'=>'256K', 'width'=>'720',  'height'=>'480', 'encrypt'=>1), 
        array('type'=>'4', 'mime_type'=>'video/mpegts', 'fps'=>20, 'audio_bps'=>'64K', 'video_bps'=>'384K', 'width'=>'1280', 'height'=>'720', 'encrypt'=>1), 
        array('type'=>'5', 'mime_type'=>'video/mpegts', 'fps'=>25, 'audio_bps'=>'64K', 'video_bps'=>'512K', 'width'=>'1280', 'height'=>'720', 'encrypt'=>1) 
    );
    
}