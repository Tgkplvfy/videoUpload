<?php
/**
 * 分布式存储服务
 *
 * @author    jiangsf <jiangsf@mail.open.com.cn>
 * @since     2013-8-26
 * @copyright Copyright (c) 2013 Open Inc. (http://www.mukewang.com)
 */
class Ap_Service_Storage {
    const DELAY_TIME = 120; //设置主从复制延迟时间
    const CHUNK_SIZE = 15728640; //数据分块大小
    private static $conf = NULL;
    /**
     * @var string 最后的错误信息
     */
    public static $lastError = NULL;
    
    
    /**
     * 获取节配置信息
     * 
     * @param string $section
     */
    public static function getConf($section) {
        if (empty(self::$conf)) {
            self::$conf = new Yaf_Config_Ini(ROOT_PATH.'/conf/mongodb.ini');
        }
        return self::$conf->get($section);
    }
    
    /**
     * 获取当前时间戳的16进制值
     * @return string
     */
    public static function getTimeStamp() {
        $time = time();
        return dechex($time);
    }
    
    /**
     * 通过文件类型选取写入服务器
     * 
     * @param int $type
     * @return MongoClient
     */
    private static function getWriteServer($type) {
        $typename = Ap_Common_FileType::getTypeName($type);
        $conf = self::getConf($typename); //这里进行文件组选取
        $servers = explode(',', $conf->servers);
        $sid = array_rand($servers); //选择一个写入服务器，当前采用随机选择
        $server = trim( $servers[$sid] );
        $conf = self::getConf($server); //获取选定的写入服务器的配置
        return $conf;
    }

    /**
     * 写入文件到存储服务中
     * 
     * @param string $filename 源文件地址
     * @param number $type 文件类型
     * @param array $metas 文件meta元数据
     * @return boolean|string
     */
    public static function write($filename, $type = 0, $metas = array()) {
        if (!is_file($filename)) {
            return false;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
        $mime = finfo_file($finfo, $filename);
        finfo_close($finfo);
        if ($type == 0) {
            $type = Ap_Common_FileType::getTypeIDbyMIME($mime);
        }
        
        $conf = self::getWriteServer($type);
        try {
            $mon = new MongoClient("mongodb://" . $conf->host, $conf->option->toArray());
            $collection = $mon->selectCollection($conf->db, $conf->collection); 
        } catch (Exception $ex) {
            self::$lastError = $ex->getMessage();
            return false;
        }
        
        //判断文件是否已经存在
        $md5 = md5_file($filename);
        $where = array( 'md5' => $md5 );
        $meta = $collection->findOne($where);
        $chunks = isset($meta['chunks']) ? $meta['chunks'] : 0;
        if (empty($meta)) {
            $chunks = self::saveChunk($mon, $conf, $filename, $md5);
        }
        
        //计算hashkey
        $sand = substr($md5, 0, 4);
        $prop = '00000000';
        $hashkey = self::getTimeStamp().$conf->server.$sand.$prop;
        $meta = array(
        	'_id' => new MongoId($hashkey),
            'md5' => $md5,
            'hits' => 0,
            'type' => $type,
            'mime' => $mime,
            'chunks' => $chunks,
            'filesize' => filesize($filename),
            'create_time' => time(),
            'viewtime' => 0,
        );
        if (!empty($metas)) {
            if (isset($metas['_id']) && strlen($metas['_id']) == 24) {
                try {
                    $mid = new MongoId($metas['_id']);
                    $metas['_id'] = $mid;
                } catch (Exception $ex) {
                    
                }
            }
            $meta = array_merge($meta, $metas);
        }
        $res = $collection->save($meta);
        
        if ($res['n'] > 0) {
            if (is_object($meta['_id'])) {
                return $meta['_id']->__toString();
            } else {
                return $meta['_id'];
            }
        }
        return false;
    }
    
    /**
     * 写入图片到mongo存储中<p>
     * metas包含width height mime mark等图片特有属性
     * @param string $buffer 图片的二进制数据
     * @param string $metas 图片的meta属性值
     * @return string|boolean 成功返回hashkey 失败返回false
     */
    public static function writePicture($buffer, $sand = '', $metas = array('width'=>0, 'height'=>0)) {
        $type = Ap_Common_FileType::IMAGE;
        $conf = self::getWriteServer($type);
        try {
            $mon = new MongoClient("mongodb://" . $conf->host, $conf->option->toArray());
            $collection = $mon->selectCollection($conf->db, $conf->collection); 
        } catch (Exception $ex) {
            self::$lastError = $ex->getMessage();
            return false;
        }
        
        //判断文件是否已经存在
        $md5 = md5($buffer);
        $where = array( 'md5' => $md5 );
        $meta = $collection->findOne($where);
        $chunks = 1;
        if (empty($meta)) {
            $coll = $mon->selectCollection($conf->db, $conf->collection . '.chunks');
            $chunk_key = substr($md5, 0, 24);
            self::saveBuffer($coll, $chunk_key, $buffer);
        }
        
        //计算hashkey
        if (empty($sand)) {
            $sand = substr($md5, 0, 4);
            $sand = self::getTimeStamp().$conf->server.$sand;
        }
        if (empty($metas['width']) || empty($metas['height'])) { //计算图片的长宽
            try {
                $im = new Imagick();
                $im->readimageblob($buffer);
                $metas['width'] = $im->getimagewidth();
                $metas['height'] = $im->getimageheight();
            } catch (Exception $ex) {
                Ap_Log::error('图片格式不正确，无法读取宽高'.$ex->getMessage());
                return false;   //图片格式不正确
            }
        }
        $prop = sprintf('%04d%04d', $metas['width'], $metas['height']);
        $hashkey = $sand.$prop;
        $meta = array(
        	'_id' => new MongoId($hashkey),
            'md5' => $md5,
            'hits' => 0,
            'type' => $type,
            'mime' => 'image/jpeg',
            'chunks' => $chunks,
            'filesize' => strlen($buffer),
            'create_time' => time(),
            'viewtime' => 0,
        );
        if (!empty($metas)) {
            if (isset($metas['_id']) && strlen($metas['_id']) == 24) {
                try {
                    $mid = new MongoId($metas['_id']);
                    $metas['_id'] = $mid;
                } catch (Exception $ex) {
                    
                }
            }
            $meta = array_merge($meta, $metas);
        }
        $res = $collection->save($meta);
        
        if ($res['n'] > 0) {
            if (is_object($meta['_id'])) {
                return $meta['_id']->__toString();
            } else {
                return $meta['_id'];
            }
        }
        return false;
    }
    
    /**
     * 保存分块数据 <p>
     * 数据块与文件meta元数据分离，这样数据块可以共享储存，只要拥有相同md5的数据块只在库中保存一份，各文件公用数据块
     * 
     * @param MongoClient $mon
     * @param string $db
     * @param string $collection
     * @param string $filename
     * @param string $md5
     * @return int
     */
    private static function saveChunk($mon, $conf, $filename, $md5) {
        $coll = $mon->selectCollection($conf->db, $conf->collection . '.chunks');
        $filesize = filesize($filename);
        $chunksize = $conf->chunksize ? $conf->chunksize : self::CHUNK_SIZE;
        $prekey = substr($md5, 0, 16);
        $start = hexdec(substr($md5, 16, 8));
        $handle = fopen($filename, 'rb');
        $chunks = 0;
        while (!feof($handle)) {
            $buffer = fread($handle, $chunksize);
            $chunk_key = $prekey . substr(sprintf('%08x', $start + $chunks), -8);
            self::saveBuffer($coll, $chunk_key, $buffer);
        
            $chunks ++;
        }
        fclose($handle);
        return $chunks;
    }
    
    /**
     * 存储数据到mongo chunk中
     * 
     * @param MongoCollection $coll
     * @param string $chunk_key
     * @param string $chunk_data
     * @return boolean
     */
    private static function saveBuffer($coll, $chunk_key, $buffer) {
        $mid = new MongoId( $chunk_key );
        $chunk_data = array(
                '_id' => $mid,
                'content' => new MongoBinData($buffer, MongoBinData::BYTE_ARRAY),
        );
        $res = $coll->save($chunk_data);
        return $res['n'] > 0;
    }
    
    /**
     * 通过hashkey获取读取服务器对象
     * 
     * @param string $key
     * @return MongoClient
     */
    public static function getReadServer($conf, $mid) {
        $mon = new MongoClient("mongodb://". $conf->host, $conf->option->toArray());
        if ($mid && (time() - $mid->getTimestamp() < self::DELAY_TIME) ) { //在延迟期内，从库可能还没有得到完整的数据，因此直接从主库中读取
            $mon->setReadPreference(MongoClient::RP_PRIMARY);
        } else {
            $mon->setReadPreference(MongoClient::RP_NEAREST);
        }
        return $mon;
    }
    
    /**
     * 从存储服务中读取文件
     * 
     * @param string $key
     * @return mixed
     */
    public static function read($key) {
        $server = substr($key, 8, 4);
        $conf = self::getConf($server);
        //老数据兼容
        if (empty($conf)) {
            $conf = self::getConf('0001');
            $mid = 0;
            $id = $key;
        } elseif (strlen($key) != 24) {
            return false;
        } else {
            $mid = new MongoId($key);
            $id = $mid;
        }

        $mon = self::getReadServer($conf, $mid);
        $collection = $mon->selectCollection($conf->db, $conf->collection); 
        $where = array( '_id' => $id );
        $meta = $collection->findOne($where, array('md5'=>1, 'chunks'=>1));
        //read from backup server
        if (empty($meta)) {
            $meta = self::readImageFromBakServer($id);
            //yxc移动数据(这里没有删除冷数据)
            if($meta) {
                $rs = $collection->save($meta);
            }
        }

        $collection->update( $where, array('$inc' => array('hits'=>1), '$set' => array('viewtime'=>time())) ); //更新访问量
        
        //读取chunk数据块组合成文件
        $coll = $mon->selectCollection($conf->db, $conf->collection . '.chunks');
        $prekey = substr($meta['md5'], 0, 16);
        $start = hexdec(substr($meta['md5'], 16, 8));
        $content = '';
        for($i = 0; $i < $meta['chunks']; $i++) {
            $chunk_key = $prekey . substr(sprintf('%08x', $start + $i), -8);
            $mid = new MongoId( $chunk_key );
            $where = array( '_id' => $mid );
            $file = $coll->findOne($where);

            //read from backup server
            if(!$file) {
                $file = self::readChunksFromBakServer($mid);
                if($file)
                    $coll->save($file);
            }

            $content .= $file['content']->bin;
        }
        return $content;
    }
    
    /**
     * 文件是否存在
     * 
     * @param string $key
     * @return boolean
     */
    public static function fileExists($key) {
        $server = substr($key, 8, 4);
        $conf = self::getConf($server);
        //老数据兼容
        if (empty($conf)) {
            $conf = self::getConf('0001');
            $mid = 0;
            $where = array( '_id' => $key );
        } elseif (strlen($key) != 24) {
            return false;
        } else {
            $mid = new MongoId($key);
            $where = array( '_id' => $mid );
        }
        $mon = self::getReadServer($conf, $mid);
        $collection = $mon->selectCollection($conf->db, $conf->collection);
         
        $file = $collection->findOne($where, array('md5'=>1));
        if( empty($file) ){
            $file = self::readImageFromBakServer($mid);
            if($file) {
                $collection->save($file);
            } else {
                return false;
            }
        }
        return true; 
    }
    
    /**
     * 获取文件的meta元数据
     * 
     * @param string $key
     * @return boolean
     */
    public static function getMeta($key) {
        $server = substr($key, 8, 4);
        $conf = self::getConf($server);
        //老数据兼容
        if (empty($conf)) {
            $conf = self::getConf('0001');
            $mid = 0;
            $where = array( '_id' => $key );
        } elseif (strlen($key) != 24) {
            return false;
        } else {
            $mid = new MongoId($key);
            $where = array( '_id' => $mid );
        }
        $mon = self::getReadServer($conf, $mid);
        $collection = $mon->selectCollection($conf->db, $conf->collection);
         
        $file = $collection->findOne($where);
        return $file;
    }
    
    /**
     * 删除文件 元数据仍然会保留
     * 
     * @param string $key
     * @return boolean
     */
    public static function remove($key) {
        if (strlen($key) != 24) {
            return false;
        }

        $server = substr($key, 8, 4);
        $conf = self::getConf($server);
        $mon = new MongoClient("mongodb://" . $conf->host, $conf->option->toArray());
        $collection = $mon->selectCollection($conf->db, $conf->collection);
         
        $where = array(
            '_id' => new MongoId($key),
        );
        $res = $collection->remove($where);
        return $res['n'];
    }
    
    /**
     * 获取hashkey的前缀种子key
     * 
     * @param string $key
     * @return string
     */
    public static function getSand($key) {
        return substr($key, 0, 16);
    }



    
    /**
     * 备份服务器读取image
     * @param      none
     * @access     public
     * @return     void
     * @update     date time
    */
    public static function readImageFromBakServer($id) {
        $where = array( '_id' => $id );
        $bakconf = self::getConf('bak0001');
        $bakmongo = self::getReadServer($bakconf, false);
        $bakcollection = $bakmongo->selectCollection($bakconf->db, $bakconf->collection);
        return $bakcollection->findOne($where);        
    } // end func


    
    /**
     * 备份服务器读取chunks
     * @param      none
     * @access     public
     * @return     void
     * @update     date time
    */
    public static  function readChunksFromBakServer($id) {        
        $where = array( '_id' => $id );
        $bakconf = self::getConf('bak0001');
        $bakmongo = self::getReadServer($bakconf, false);
        $bakchunkcoll = $bakmongo->selectCollection($bakconf->db, $bakconf->collection . '.chunks');
        return $bakchunkcoll->findOne($where);        
        
    } // end func
    
    
}
