<?php
/** 
 * 共享图片服务类
 * @author Jiangsf
 *
 */
class Ap_ImageAdapter {
    /**
     * 默认扩展名
     * 
     * @var string
     */
    const DEFAULT_EXT = 'jpg';
    /**
     * hashkey的密钥长度
     * 
     * @var int
     */
    const KEYLENGTH = 24;
    /**
     * 当前版本字串
     * 
     * @var string
     */
    protected $version = 'a';
    /**
     * 最后的错误码
     * 
     * @var int
     */
    protected $lastError = Ap_Common_ErrorDef::ERROR_SUCCESS;
    
    /**
     * @var Ap_Cache_MemCache 缓存
     */
    protected $_cache = NULL;
    
    /**
     * @var Ap_DB_Mysqli 数据库
     */
    protected $_db = null;
    
    /**
     * 写入图片到共享存储中
     * <p>写入后得到一个唯一的key值 可以存起来用于读取</p>
     * <p>$src_filename支持远程URL</p>
     *
     * @param string $src_filename
     *            源文件名 支持URL
     * @param array $watermark 是否加水印
     * @param array $thumbs 生成的缩略图大小
     * @return string 唯一的key值
     */
    public function write($src_filename, $watermark = 0, $thumbs = array()) {
        if (substr($src_filename, 0, 7) == 'http://') { //处理远程文件
            $content = Ap_Util_Http::get($src_filename);
        } elseif (! file_exists ( $src_filename )) { // 要上传的文件不存在
            $this->lastError = Ap_Common_ErrorDef::ERROR_FILE_NOTEXISTS;
            return false;
        } else {
            $content = file_get_contents($src_filename);
        }
        //$hashkey = $this->getHashKey ( $dest_filename );
        $meta['watermark'] = $watermark;
        $hashkey = Ap_Service_Storage::writePicture($content, '', $meta); //保存图片
        $meta['_id'] = Ap_Service_Storage::getSand($hashkey) . str_repeat('0', 8); //保存图片的原始key
        Ap_Service_Storage::writePicture($content, '', $meta);
        // @todo 发送队列，自动生成指定尺寸的图片
        if (!empty($thumbs)) {
            $que = new Ap_Service_Queue();
            foreach ($thumbs as $thumb) {
                $que->thumbnail($hashkey, $thumb['width'], $thumb['height'], $watermark);
            }
        }
        return $hashkey;
    }
    
    /**
     * 制作缩略图 得到一个缩略后的key 这个key可以直接浏览该图片
     * @param string $hashkey
     * @param number $width
     * @param number $height
     * @return number
     */
    public function thumbnail($hashkey, $width = 500, $height = 500) {
        $width = empty($width) ? 2000 : $width;
        $height = empty($height) ? 2000 : $height;
        $content = $this->read($hashkey);
        $im = new Imagick();
        $im->readimageblob($content);
        
        if ($im->getimagemimetype() == 'image/gif') { //动态图单独处理
            $im->rewind();
            $images = $im->coalesceImages();
            foreach($images as $img){
                $im = $img; //gif图片取第一帧
                break;
            }
        }
        
        $geo = $im->getImageGeometry();
        if ($geo['width'] < $width && $geo['height'] < $height) {
            return $hashkey;
        }
        if(($geo['width']/$width) < ($geo['height']/$height)) {
            $d = $geo['height'] / $height;
            $width = ceil($geo['width']/$d);
        } else {
            $d = $geo['width'] / $width;
            $height = ceil($geo['height']/$d);
        }
        
        $conf = Ap_Util_Config::get('image.ini', 'image');
        $quality = $conf->get('quality') ? $conf->get('quality') : 90;
        $im->setImageCompression(Imagick::COMPRESSION_JPEG);
        $im->setimagecompressionquality($quality);
        $im->ThumbnailImage($width, $height);
        
        $hashkey = substr($hashkey, 0, -8);
        $hashkey .= sprintf('%04d%04d', $width, $height);
        $meta['_id'] = $hashkey; //保存图片的原始key
        Ap_Service_Storage::writePicture($im->getimageblob(), '', $meta);
        return $hashkey;
    }
    
    /**
     * 缓存头像
     * @param number $uid
     * @param number $hashkey
     * @param number $lifetime
     */
    public function cacheHead($uid, $userinfo, $lifetime=0) {
        $cache = $this->initCache();
        return $cache->write('head-'.$uid, $userinfo, $lifetime);
    }
    
    /**
     * 初始化缓存
     * 
     * @return Ap_Util_Cache_MemAdapter
     */
    protected function initCache() {
        if (empty($this->_cache)) {
            $cache = new Ap_Cache_MemCache();
            $this->_cache = $cache->getMemCacheHandler();
        }
        return $this->_cache;
    }
    
    /**
     * 获取最后的错误状态码
     * 
     * @return number
     */
    public function getError() {
        return Ap_Common_ErrorDef::$error_desc[$this->lastError];
    }
    
    /**
     * 从共享存储中读取图片
     *
     * @param string $filename            
     * @return mixed
     */
    public function read($hashKey) {
        return Ap_Service_Storage::read($hashKey);
    }
    
    /**
     * 从共享存储中输出图片到浏览器
     *
     * @param string $filename            
     * @return boolean
     */
    public function output($hashKey) {
        $this->outputHeader ( $hashKey );
        echo $this->read ( $hashKey );
        return true;
    }
    
    /**
     * 输出图片HTTP头
     * 
     * @param strng $hashKey            
     */
    private function outputHeader($hashKey) {
        Ap_Util_Http::header ( Ap_Common_Vars::FILE_TYPE_JPG ); // 默认输出JPG格式的图片文件
    }
    
    /**
     * 从共享存储中删除文件
     *
     * @param string $filename            
     * @return boolean
     */
    public function unlink($hashKey) {
        //@todo 删除图片
        return true;
    }
    
    /**
     * 文件是否在共享存储中存在
     *
     * @param string $filename            
     * @return boolean
     */
    public function exists($hashKey) {
        return Ap_Service_Storage::fileExists($hashKey);
    }
    
    /**
     * 文件大小
     *
     * @param string $filename            
     * @return number
     */
    public function filesize($hashKey) {
        $meta = Ap_Service_Storage::getMeta($hashKey);
        return $meta['filesize'];
    }
    
    /**
     * 通过哈希Key获取图片的URL
     *
     * @param string $hashKey            
     * @param number $width            
     * @param number $height            
     * @return string
     */
    public static function getUri($hashKey, $width = 0, $height = 0, $sex = 0) {
        //@todo 如果hashkey不存在 则404
        $filename = $hashKey;
        $url = Ap_Util_Config::get('image.ini', 'image.url');
        
        // added by jiangwb 2013-12-24新增默认男女头像
        if (empty($filename)) {
        	if (!in_array($width, array(40, 80, 160))) {
        		if ($width > 80) {
        			$width = 160;
        			$height = 160;
        		} else {
        			$width = 80;
        			$height = 80;
        		}
        	}
        	
        	if ($sex == 1) {
        		$filename = 'images/man-'.$width.'.png';
        	} elseif ($sex == 2) {
        		$filename = 'images/girl-'.$width.'.png';
        	} else {
        		$filename = 'images/unknow-'.$width.'.png';
        	}
        	
        	return $url. $filename;
        }
        
        if ($width > 0) {
            $filename .= '-' . $width . '-' . $height;
        }
        $filename .= '.' . self::DEFAULT_EXT;
        
        return $url . $filename;
    }
    
    /**
     * 获取头像地址
     * 
     * @param string $hashkey
     * @param number $width
     * @param number $height
     * @return string
     */
    public static function getHead($hashkey, $width = 0, $height = 0) {
        $filename = 'user/'.$hashkey;
        if ($width > 0) {
            $filename .= '-' . $width . '-' . $height;
        }
        $filename .= '.' . self::DEFAULT_EXT;
        $url = Ap_Util_Config::get('image.ini', 'image.url');
        return $url . $filename;
    }
    
    /**
     * 通过uid获取头像地址 如果传递了sex，则通过sex判断，如果没有sex则会去查库
     * <p>根据sex不同显示不同的默认头像</p>
     * @param number $uid
     * @param number $size
     * @return string
     */
    public function getHeadByUid($uid, $size=160, $sex = 0) {
        $cache = $this->initCache();
        $data = $cache->read('head-'.$uid);
        if (empty($data)) {
            $ap = new Ap_Service_Data_User();
            if ($uid > 0) {
            	 
				if($uid == Ap_Service_Data_Loginuser::Id()){
                	$data = Ap_Service_Data_Loginuser::Userinfo(); 
				}else{
					$data = $ap->GetUserInfo($uid, 'portrait, sex');
				}
                
                $this->cacheHead($uid, $data);
            } else {
                $data = array();
            }
        }
        if (!in_array($size, array(40, 80, 160, 220))) {
            if ($size > 160) {
                $size = 220;
            } elseif ($size > 80) {
                $size = 160;
            } else {
                $size = 80;
            }
        }
        if (empty($data['sex'])) {
            $data['sex'] = $sex ? $sex : 3;
        }
        if (empty($data['portrait'])) {
            if ($data['sex'] == 1) {
                $data['portrait'] = 'images/man-'.$size.'.png';
            } elseif ($data['sex'] == 2) {
                $data['portrait'] = 'images/girl-'.$size.'.png';
            } else {
                $data['portrait'] = 'images/unknow-'.$size.'.png';
            }
            $url = Ap_Util_Config::get('image.ini', 'image.url');
            return $url.$data['portrait'];
        }
        return $this->getHead($data['portrait'], $size, $size);
    }
    
    /**
     * 获取图片服务的URL
     * 
     * @return Yaf_Config_Simple
     */
    public static function getImageDomain() {
        $url = Ap_Util_Config::get('image.ini', 'image.url');
        return $url;
    }
    
    /**
     * 获取图片服务的URL
     * 
     * @return Yaf_Config_Simple
     */
    public static function getDownUrl() {
        $url = Ap_Util_Config::get('image.ini', 'image.down');
        return $url;
    }
    
    /**
     * 判断hashkey是否合法与存在
     * 
     * @param string $hashkey
     * @return boolean
     */
    public static function isKeyExists($hashkey) {
        return Ap_Service_Storage::fileExists($hashkey);
    }
    
    /**
     * 通过哈希Key获取图片的URL 等同于getURLFromKey方法
     * 这里是提供一个getURLFromKey简易的调用方法
     *
     * @param string $hashKey            
     * @param number $width            
     * @param number $height            
     * @return string
     */
    public function getURL($hashKey, $width = 0, $height = 0, $sex = 0) {
        return self::getUri ( $hashKey, $width, $height, $sex );
    }
    
    /**
     * 通过哈希Key获取图片的URL，静态方法getUri的别名
     *
     * @param string $hashKey            
     * @param number $width            
     * @param number $height            
     * @return string
     */
    public function getURLFromKey($hashKey, $width = 0, $height = 0) {
        return self::getUri ( $hashKey, $width, $height );
    }
    
    /**
     * 获取该URL的基本Key
     *
     * @param string $url            
     * @return string
     */
    public static function getBaseKeyFromUrl($url) {
        $name = basename ( $url );
        return substr ( $name, 0, self::KEYLENGTH );
    }
    
    /**
     * 通过hashKey获取文件的扩展名
     *
     * @param string $hashKey            
     * @return string
     */
    private function getExtension($hashKey) {
        $meta = Ap_Service_Storage::getMeta($hashKey);
        $ext = str_replace('image/', '', $meta['mime']);
        if (!in_array($ext, array('gif', 'jpg', 'png'))) {
            $ext = 'jpg';
        }
        return $ext;
    }
}