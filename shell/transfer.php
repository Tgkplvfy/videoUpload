<?php

$root = dirname(__DIR__);
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

        $co_id = $media['course_id'];
        $ch_id = $media['chapter_id'];

        if ( ! $video_info) {
            # 课程视频信息不存在，记录异常，继续
            logg("video not found tbl_course_video: {$video_id} CO:{$co_id} CH:{$ch_id}");
            continue;
        } else {
            # 获取视频下的转码视频文件
            $video = saveOriginalVideo($video_info);

            if ( ! $video) {
                logg("video save failed: {$video_id} CO:{$co_id} CH:{$ch_id}");
                continue;
            }

            $subfiles = $db->fetchAll('SELECT * 
                FROM tbl_course_video_process 
                WHERE video_id = ' . $video_id);

            if (empty($subfiles)) {
                logg("video subfiles empty: {$video_id} CO:{$co_id} CH:{$ch_id}");
                continue;
            }

            foreach ($subfiles as $sub) {
                saveSubVideo($sub, $video, $video_info['name']);
            }

            # 送去转码队列
            # sendToQueue($subs);
            $apMongo = new Ap_DB_MongoDB();
            $apMongo->getCollection('video_mapping')->save(array(
                'video_id'    => $video_info['id'], 
                'mongo_id'    => $video['bkt_id'] 
            ));
        }

    }
    return $videos;
}


# 保存视频源文件
function saveOriginalVideo ($video)
{
    logg('start saving orignal video: ' . $video['id'] . "\n");
    $paths = explode('.', $video['media_url']);
    $file = '/video/myvideo/' . $video['media_url'];
    if ( ! file_exists($file)) {
        logg('video original file lost(ID): ' . $video['id'] . '_' . $video['media_url']);
        $file = '/video/play/' . $paths[0] . '/H.mp4';
    }

    if ( ! file_exists($file)) {
        logg('video files lost: videoid:' . $video['id'] . '_' . $video['media_url']);
        return FALSE;
    }

    # 保存文件到fastDFS
    $filename = saveToFastDFS($file, 'mp4');
    if ( ! $filename) return FALSE;

    # 获取文件基本信息
    $md5 = md5_file($file);
    $size = filesize($file);
    $imgAdapter = new Ap_ImageAdapter();
    $videoThumb = $imgAdapter->getURL($video['media_pic']);

    $finfo    = finfo_open(FILEINFO_MIME);
    $mimetype = finfo_file($finfo, $file);
    finfo_close($finfo);

    $mongoData = array(
        '_id'       => new MongoId(), 
        'filename'  => $filename, 
        'size'      => $size, 
        'mime_type' => $mimetype, 
        'md5_file'  => $md5, 
        'pic'       => $videoThumb, 
        'status'    => 2, 
        'duration'  => $video['duration'] 
    );

    $mongo = new Ap_DB_MongoDB();
    $res = $mongo->getCollection('video')->save($mongoData);

    $bktInfo = saveBktVideo($mongoData, $video['name']);

    logg("orignal video saved...");
    $mongoData['bkt_id'] = $bktInfo['_id'];
    $mongoData['upload_id'] = $bktInfo['upload_id'];
    return $mongoData;
}

# 保存视频转码文件
function saveSubVideo ($sub, $video, $title) 
{
    $param = explode('.', $sub['parameter']);
    if (count($param) != 5) return FALSE;
    if ($param[4] != 'mp4') return FALSE;
    logg("start saving sub video: {$sub['video_id']} {$sub['filename']}");
    $dimen = explode('x', $param[3]);

    $path = explode('.', $sub['video_url']);
    $path = $path[0];

    $file = '/video/play/' . $path . $sub['filename'];
    $filename = saveToFastDFS($file, 'mp4');
    if ( ! $filename) return FALSE;
    
    $transFile = array(
        # 原始文件信息
        '_id'      => new MongoId(), 
        'src_id'   => $video['_id'], 
        'filename' => $filename, 
        # 文件基本信息
        'size'      => $sub['byte'], 
        'mime_type' => 'video/mp4', 
        'md5_file'  => md5_file($file), 
        # 文件转码信息
        "fps"       => $param[0], 
        "video_bps" => $param[1], 
        "audio_bps" => $param[2], 
        "width"     => $dimen[0], 
        "height"    => $dimen[1], 
        "encrypt"   => 0, 
        "status"    => 6 
    );

    $mongo = new Ap_DB_MongoDB();
    $res = $mongo->getCollection('video')->save($transFile);
    $bktId = saveBktVideo($transFile, $title, $video['_id'], $video['upload_id']);
    logg("sub video saved...\n");

    return $bktId;
}

# 保存上传记录
function saveBktVideo ($video, $title = '', $src_id = NULL, $upload_id = NULL)
{
    $apMongo = new Ap_DB_MongoDB();
    $bktId   = new MongoId();

    $bktInfo = array(
        '_id'          => $bktId, 
        'bucket_id'    => 'transfer', 
        'upload_id'    => $upload_id ? $upload_id : $bktId, 
        'title'        => $title, 
        'watermark'    => '', 
        'src_video_id' => $src_id, 
        'dst_video_id' => $video['_id'] 
    );
    $apMongo->getCollection(Ap_Vars::MONGO_TBL_BUCKETVIDEO)->save($bktInfo);

    return $bktInfo;
}

# 保存文件到FastDFS
function saveToFastDFS ($path = '', $ext = '')
{
    $file = FALSE;
    try {
        $fDFS = new Ap_Storage_FastDFS();
        $file = $fDFS->write($path, NULL, $ext);
    } catch (Exception $e) {
        logg('FastDFS failed: ' . $e->getMessage());
    }

    return $file;
}

# 记录操作日志
function logg($msg)
{
    $logfile = 'transfer-' . date('Ymd') . '.log';
    if ( ! file_exists($logfile)) touch($logfile);

    file_put_contents($logfile, $msg . "\n", FILE_APPEND);
}
