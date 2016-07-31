<?php

$root = dirname(dirname(__DIR__));
define("APP_PATH",  $root);
define("ROOT_PATH", $root);

ini_set('yaf.library', APP_PATH . '/phplib');
date_default_timezone_set('Asia/Shanghai');

$app = new Yaf_Application(APP_PATH . '/conf/app.ini');
$app->execute('main');


# 转移视频文件主程序
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
            $video = saveOriginalVideo($video);

            if ( ! $video) {
                logg("video save failed: {$video_id} of course {$media['course_id']} chapter {$media['chapter_id']}");
                continue;
            }

            $subfiles = $db->fetchAll('SELECT * 
                FROM tbl_course_video_process 
                WHERE video_id = ' . $video_id);

            if (empty($subfiles)) {
                logg("video subfiles empty: {$video_id} of course {$media['course_id']} chapter {$media['chapter_id']}");
                continue;
            }

            $subs = array();
            foreach ($subfiles as $file) 
            {
                if ($file['mime_type'] != 'mp4' OR $file['mime_type'] != 'megts') continue;

                if ($file['mime_type'] == 'mp4') $subs[] = saveMp4Video();
                if ($file['mime_type'] == 'megts') $subs[] = saveTsVideo();
            }

            $bucket = saveBktVideo($video, $subs);
            saveLinkInfo($video, $bucket);
        }

    }
    return $videos;
}


# 保存视频源文件
function saveOriginalVideo()
{
}

# 保存转码MP4文件
function saveMp4Video()
{
}

# 保存转码TS文件
function saveTsVideo()
{
}

# 保存文件到FastDFS
function saveToFastDFS ($path = '', $ext = '')
{
    try {
        $fDFS = new Ap_Storage_FastDFS();
        $file = $fDFS->write($path, NULL, $ext);
    } catch (Exception $e) {
        logg('FastDFS failed: ' . $e->getMessage());
    }
}

# 保存信息到MongoDB
function saveToMongoDB () 
{
}

# 记录操作日志
function logg($msg)
{
    $logfile = date('Ymd') . ' - transfer.log';
    if ( ! file_exists($logfile)) touch($logfile);

    file_put_contents($logfile, $msg . "\n", FILE_APPEND);
}
