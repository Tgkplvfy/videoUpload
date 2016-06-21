<?php
/**
 * 文件类型与分组
 *
 * @author    jiangsf <jiangsf@mail.open.com.cn>
 * @since     2013-10-10
 * @copyright Copyright (c) 2013 Open Inc. (http://www.mukewang.com)
 * @desc      文件类型与分组
 */

class Ap_Common_FileType {
    /**
     * @var int 普通文件
     */
    const FILE = 0;
    /**
     * @var int 图片文件
     */
    const IMAGE = 1;
    /**
     * @var int 视频文件
     */
    const VIDEO = 2;
    /**
     * @var int 语音文件
     */
    const AUDIO = 3;
    
    private static $types = array(
    	0 => 'file',
        1 => 'image',
        2 => 'video',
        3 => 'audio',
    );
    
    private static $type_ids = array(
    	'file' => 0,
        'image' => 1,
        'video' => 2,
        'audio' => 3,
    );
    
    /**
     * 获取文件类型的字符串
     * 
     * @param int $type 类型ID
     * @return string 
     */
    public static function getTypeName($type) {
        if (isset(self::$types[$type])) {
            return self::$types[$type];
        }
        return self::$types[0];
    }
    
    /**
     * 通过文件类型名获取类型ID
     * 
     * @param string $typename
     * @return number
     */
    public static function getTypeID($typename) {
        if (isset(self::$type_ids[$typename])) {
            return self::$type_ids[$typename];
        }
        return 0;
    }
    
    /**
     * 通过MIME获取文件类型ID
     * 
     * @param string $mime
     * @return number
     */
    public static function getTypeIDbyMIME($mime) {
        foreach (self::$type_ids as $key => $type) {
            if (strpos($mime, $key) !== false) {
                return $type;
            }
        }
        return 0;
    }
}
