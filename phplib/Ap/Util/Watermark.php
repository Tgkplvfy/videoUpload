<?php

/**
 * 水印相关功能
 *
 * @author       qirl@mail.open.com.cn
 * @version      1.0
 * @copyright    
 * @access       public\
 * @date		 2013/6/3
 * <code>
 */
class Ap_Util_Watermark
{
    // public static function getWaterMarkId 

    // 获取默认的水印文件
    public static function getDefaultWaterMark () 
    {
        $path = ROOT_PATH . Ap_Vars::DEFAULT_WATERMARK;

        $data = array(
            '_id'      => new MongoId(), 
            'md5_file' => md5_file($path), 
            'content'  => Ap_File::getFileBase64($path, FALSE)
        );

        $m_watermark = new Ap_Model_Watermark();
        $m_watermark->insert($data);
        
        return $data;
    }

    // 获取上传的水印文件
    public static function getWaterMarkFile ($file) 
    {
        if ($file['error'] == 0) {
            $tmpfile = $file['tmp_name'];

            $data = array(
                '_id'      => new MongoId(), 
                'md5_file' => md5_file($tmpfile), 
                'content'  => Ap_File::getFileBase64($tmpfile, FALSE)
            );

            $m_watermark = new Ap_Model_Watermark();
            $m_watermark->insert($data);
            
            return $data;
        }
    }

    public static function getWaterMarkMongoId ($md5) 
    {
        // 
    }
    
} // end class