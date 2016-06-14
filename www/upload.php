<?php
//ini_set('memory_limit','1024M');
set_time_limit(0);
include( "autoload.php" );
//('display_errors',1);
//error_reporting(2047);


ini_set('yaf.library', ROOT_PATH.'/phplib');
date_default_timezone_set('Asia/Shanghai');

//优先注册smarty的自动加载类 jiangsf
//本来不优先加载也可以，但是在测试环境下会先执行yaf的autoloader，然后会警告

define('DEF_SIZE', '720x480');

$uid = isset($_GET['uid']) ? intval($_GET['uid']) : 0;
$debug = isset( $_GET['debug'] ) ? $_GET['debug'] : false ;
$name = isset( $_POST['name'] ) ? intval( $_POST['name'] ) : '';
$video_id = isset( $_POST['vid'] ) ? intval( $_POST['vid'] ) : 0;
$watermark = isset( $_GET['watermark'] ) ? intval( $_GET['watermark'] ) : 0;
$size = isset( $_POST['picture_size'] ) ? $_POST['picture_size'] : DEF_SIZE;
list( $H, $W ) = explode( 'x', $size );


if ( $debug ) {
    var_dump( $_FILES, $_POST );
}

if ( !isset( $_FILES['file'] )  ) {
    exit( 'no file upload! ' );
}

//命名方式
Ap_Util_Upload::$sNameing = 'uuid';
//充许的上传类型
$allow_type = explode( ',', Ap_Constants::VIDEO_TYPE );
//上传最大限制
$max_size = Ap_Constants::VIDEO_ALLOW_MAXSIZE;
//上传对象
$up = new Ap_Util_Upload( $_FILES['file'] , $_SERVER['V_SRC_DIR'], $allow_type, $max_size );
//上传,成功数量
$succ_num = $up->upload();


//上传失败
if ( $succ_num <= 0 ) 
{
    $err = implode( ",", $_FILES['file']['error'] );
    outJson(0, NULL, $err);
}

//上传成功
$infos = $up->getSaveInfo();
$utilVideo = new Ap_Util_Video();
$tbl = new Ap_Dao_Video();
$imageAdapter = new Ap_ImageAdapter();
$retPicInfo = array();  

foreach ( $infos as $k=>$info ) 
{
    $data = array();
    $thumb_pic = $_SERVER['V_SRC_THUMB'].'/' .$info['saveas']. '.jpg';
    //生成缩略图
    $rs = $utilVideo->createThumb( $info['path'] , $size, $thumb_pic );
    //写入到图片存储
    if (!$rs) {
         outJson(0, NULL, '创建缩略图失败');
    } else {
        $picHashKey = $imageAdapter->write( $thumb_pic );
        if ( $picHashKey ) 
        {
            $data['media_pic'] = $picHashKey;
            $url = $imageAdapter->getURL( $picHashKey, $H, $W );
            $retPicInfo[$k]['video_pic'] = $url;
        } else {
            outJson(0, NULL, '写入缩略图失败');
        }
    }

    //写入到库
    $data['media_url'] = $info['saveas'];
    $data['duration'] = $utilVideo->getLong( $info['path'] );
    $data['name'] = $name;
    $data['create_time'] = time();
    $data['watermark'] = $watermark;
    $data['uid'] = $uid;
    //更新
    if ( $video_id ) 
    {
        $upRs = $tbl->update( $video_id, $data );
        if (!$upRs) {
            outJson(0, NULL, '更新数据库失败');
        }
    } 
    //添加
    else 
    {
        $video_id = $tbl->insert( $data );
        if (!$video_id) {
            outJson(0, NULL, '插入数据库失败');
        }
    }

    //任务分发
    $jobs = insert_process($video_id, $info['saveas']);
    foreach ($jobs as $job) {
        func_workload($job);
    }

    $retPicInfo[$k]['video_id'] = $video_id;
    $retPicInfo[$k]['video_url'] = $info['saveas']; 
}
outJson(1, $retPicInfo, '成功');




/**
 * 视频处理任务
 * @param      int $video_id
 * @param      string $video_url
 * @access     public
 * @return     void
 * @update     2014/9/9
*/
function insert_process($videoId, $videoUrl) 
{
    global $video_config;
    $return = array();
    $dao = new Ap_Dao_VideoProcess();
    $data['video_id'] = $videoId;
    $data['video_url'] = $videoUrl;
    $data['mktime'] = time();

    foreach ($video_config['default'] as $fname=>$parameter) {
        $data['filename'] = $fname;
        $data['parameter'] = $parameter;
        $pid = $dao->insert($data);
        if (!$pid) {
            outJson(0, NULL, '创建process失败');
        }
        $return[] = array(
            'processId' => $pid,
            'videoId' => $videoId,
            'inFile' => $videoUrl,
            'outFile' => $fname,
            'parameter' => str_replace("x", ".", $parameter),
        );
    }

    unset($data);
    unset($dao);
    return $return;
} // end func





/**
 * 转码任务分发
 * @param      none
 * @access     public
 * @return     void
 * @update     2014/9/9
*/
function func_workload( $job ) 
{
    global $gearman_config, $watermark;
    
    $conf = $gearman_config['default'];
    $gmclient= new GearmanClient();
    $gmclient->addServer($conf['host'], $conf['port']);

    //35860#3807#78f20ced-a5ca-4544-8177-850447e87d39.mp4#H.mp4#25.512K.128K.1280.720.mp4#[0,1]
    $workload = implode("#", $job);
    $workload .= '#' . $watermark;  

    $uniqKey = $job['processId'] . "-" . substr($job['inFile'], 0, strrpos($job['inFile'], "."));
    $result = $gmclient->doBackground(GEARMAN_FUN_DEFAULT, $workload, $uniqKey);

    //写失败之后，停1秒，二次写。
    if (!$result) {
        sleep(1);
        $result = $gmclient->doBackground(GEARMAN_FUN_DEFAULT, $workload, $uniqKey);  
        if(!$result){
            $errno = $gmclient->getErrno();
            outJson(0, $gmclient->error(), '提交到gearman队列失败:' . $errno);
        }      
    }
} // end func



/**
 * 输出json
 * @param      none
 * @access     public
 * @return     void
 * @update     2014/9/9
*/
function outJson($result = 0, $data = array(), $errmsg = '') {
    global $uid;

    $return = array( 'result' => $result, 'errmsg' => $errmsg, 'data'=>$data, 'uid' => $uid );
    $json = json_encode( $return );
    Ap_Log::video_upload_api(print_r($return, true));
    exit($json);
} // end func
