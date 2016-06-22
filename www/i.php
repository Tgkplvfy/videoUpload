<?php
//ini_set('memory_limit','1024M');
set_time_limit(0);
include( "autoload.php" );
//('display_errors',1);
//error_reporting(2047);


ini_set('yaf.library', ROOT_PATH.'/phplib');

$file = $_GET['file'];
$seek = (int)$_GET['seek'];
//$token = $_GET['token'];

$save_path = $_SERVER['V_SRC_THUMB']. '/' . "{$file}_{$seek}.jpg";
$src = $_SERVER['V_DEST_DIR'] . '/' . $file . "/H.mp4";

$utilVideo = new Ap_Util_Video();
$rs = $utilVideo -> video2image($src, $seek, $save_path);

if ($rs) {
    $imageAdapter = new Ap_ImageAdapter();
    $picHashKey = $imageAdapter->write( $save_path );
    $url = $imageAdapter->getUrl($picHashKey, 120, 68);
    if ($url) {
    	outJson(0, $url);
    }
    outJson(-1);
}


/**
 * 输出json
 * @param      none
 * @access     public
 * @return     void
 * @update     2014/9/9
*/
function outJson($result = 0, $data = array(), $errmsg = '') {
    //$callback = $_GET['callback'];
    $return = array( 'result' => $result, 'errmsg' => $errmsg, 'data'=>$data);
    //$json = json_encode( $return );
    Ap_Log::video_upload_api(print_r($return, true));
    //echo $callback . "(".$json.");";
    echo json_encode($return);
    exit;
} // end func
