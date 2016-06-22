<?php 

class ScreenshotGetAction extends Ap_Base_Action 
{
    public function execute () 
    {
        $videoId = isset($_GET['id']) ? trim($_GET['id']) : '';
        $seekPos = isset($_GET['seek']) ? (int) $_GET['seek'] : 0;

        if ( ! $videoId OR ! $seekPos) 
            $this->response(NULL, 400, '请求参数不正确！');

        $video = $this->getVideoInfo ($videoId);
        if ( ! $video OR ! isset($video['filename'])) 
            $this->response(NULL, 400, '没有找到视频！');
        
        $file = $video['filename'];
        $_src = 'http://video.mukewang.com/' . $video['filename'];
        $path = ROOT_PATH . '/storage/' . "{$videoId}_{$seekPos}.jpg";

        $utilVideo = new Ap_Util_Video();
        $rs = $utilVideo->video2image($_src, $seekPos, $path);

        if ($rs) {
            $imageAdapter = new Ap_ImageAdapter();
            $picHashKey   = $imageAdapter->write($path);
            $url          = $imageAdapter->getUrl($picHashKey);

            if ($url) {
                if (file_exists($path))
                    unlink($path); # 删除临时文件截图
                $this->response($url);
            } else {
                $this->response(NULL, 500, '保存截图失败！');
            }
        }

        $this->response(NULL, 500, '截图失败！');
    }

    # 获取视频信息
    private function getVideoInfo ($videoId) 
    {
        $apMongo = new Ap_DB_MongoDB();
        $collection = $apMongo->getCollection('video');

        $video = $collection->findOne(array('_id'=>new MongoId($videoId)));
        return $video;
    }
}