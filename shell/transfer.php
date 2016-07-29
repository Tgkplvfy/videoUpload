<?php

$root = dirname(dirname(__DIR__));
define("APP_PATH",  $root);
define("ROOT_PATH", $root);

ini_set('yaf.library', APP_PATH . '/phplib');
date_default_timezone_set('Asia/Shanghai');

$app = new Yaf_Application(APP_PATH . '/conf/app.ini');
$app->execute('main');

# $conn = new Ap_DB_Conn();
# $db = $conn->linkDB('course');

# 转移视频文件主程序---
function main () 
{
    echo "Start Transfering...\n";
    $course_id = 1;

    while (TRUE) {
        $videos = getCourseVideos($course_id);
        $course_id++;
        if ($videos === FALSE) break; # 课程循环完毕，退出
        if (empty($videos)) continue; # 课程没有视频，继续

		# 循环处理视频数据，上传fastDFS, 并保存MongoDB
        foreach ($videos as $video) {
            $video['sub_videos'] = getSubVideos($video);
            saveVideo($video);
        }
    }
}

# 根据课程ID获取课程下的所有视频
function getCourseVideos($course_id)
{
	$conn = new Ap_DB_Conn();
	$db = $conn->linkDB('course');
    if ($course_id > 10) return FALSE;
    $medias = $db->fetchAll('SELECT * 
        FROM tbl_course_media 
        WHERE type = 1 and type_id > 0 
        and status = 1 and course_id = ' . $course_id);

    $videos = array();
    foreach ($medias as $media) 
    {
        // if ( ! isset($media['type_id'])) continue;
        $video_id = $media['type_id'];
        $video_info = $db->fetchArray('SELECT * 
            FROM tbl_course_video 
            WHERE id = ' . $video_id);

        if ( ! $video_info) {
            # 课程视频信息不存在，记录异常，继续
            logg("video not found in tbl_course_video: {$video_id} of course {$media['course_id']} chapter {$media['chapter_id']}");
            continue;
        } else {
            # 获取视频下的转码视频文件
            $video_info['subfiles'] = $db->fetchAll('SELECT * 
                FROM tbl_course_video_process 
                WHERE video_id = ' . $video_id);
            // 
        }

        # print_r($video_info);
        $videos[] = $video_info;
    }
    return $videos;
}

# getSubVideos 根据视频信息获取其转码视频文件信息
function getSubVideos($video)
{
}

# saveVideo 保存视频文件信息
function saveVideo ($video)
{
}

# 记录操作日志
function logg($msg)
{
    $logfile = date('Ymd') . ' - transfer.log';
    if ( ! file_exists($logfile)) touch($logfile);

    file_put_contents($logfile, $msg . "\n", FILE_APPEND);
}
