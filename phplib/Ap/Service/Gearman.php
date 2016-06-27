<?php

/**
 * Gearman 队列服务
 *
 * @author    qirl <qirl@mail.open.com.cn>
 * @since     2016-6-24
 * @copyright Copyright (c) 2016 Open Inc. (http://www.imooc.com)
 */
class Ap_Service_Gearman {

    public function __construct ($host = '', $port = '') 
    {
        // try {
        //     $this->_client = new GearmanClient();
    	// 	$this->_client->addServers($host, $port);
        // } catch (Exception $e) {
        //     // Ap_Log::log($e->getMessage());
        // }
    }

    # 添加视频转码任务
    public static function createVideoJobs ($file) 
    {
        $gmclient = new GearmanClient();
        $config   = new Yaf_Config_Ini(ROOT_PATH . '/conf/gearman.ini', 'product');
        $host = isset($_SERVER['GEARMAN_HOST']) ? $_SERVER['GEARMAN_HOST'] : $config->get('host');
        $port = isset($_SERVER['GEARMAN_PORT']) ? $_SERVER['GEARMAN_PORT'] : $config->get('port');

        $gmclient->addServer($host, $port);

        $apMongo = new Ap_DB_MongoDB();
        $pendingJobs = $apMongo->getCollection(Ap_Vars::MONGO_TBL_VIDEO)->find(array(
            'src_id' => $file['_id'], 
            'status' => Ap_Vars::FILESTATUS_SAVED 
        ));
        $jobs = iterator_to_array($pendingJobs);
        if (empty($jobs)) return TRUE;

        foreach ($jobs as $job) {
            $fragment = array (
                'mime_type' => $job['mime_type'], 
                'fps'       => $job['fps'], 
                'audio_bps' => $job['audio_bps'], 
                'video_bps' => $job['video_bps'], 
                'width'     => $job['width'], 
                'height'    => $job['height'], 
                'encrypt'   => $job['encrypt']
            );

            $uniqKey  = (string) $job['_id'];
            $workload = json_encode(array('_id' => $uniqKey, 'fragment' => $fragment));

            try {
                $result  = $gmclient->doBackground(Ap_Vars::GEARMAN_FUN_DEFAULT, $workload, $uniqKey);
                if (!$result) {
                    sleep(1);
                    $result = $gmclient->doBackground(Ap_Vars::GEARMAN_FUN_DEFAULT, $workload, $uniqKey);
                    if(!$result){
                        $errno = $gmclient->getErrno();
                        // Ap_Log::log($errno . ':' . $gmclient->error());
                    }
                }
            } catch (Exception $e) {
                // Ap_Log::log($e->getMessage());
            }
        }
    }
}