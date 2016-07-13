<?php
/**
 * 系统文件基本信息操作
 * 
 * @author qirl
 */
class Ap_File {

    // public static function 

    # 默认转码类型 type 代表类型编码，用于标示
    /**
     * @func 获取文件的Base64编码，默认添加图片信息
     * @param $path 文件路径
     * @param $with_prefix 是否加base64前缀，图片可以直接输出
     */
    public static function getFileBase64 ($path = '', $with_prefix = TRUE) 
    {
        if (empty($path)) return FALSE;
        if ( ! file_exists($path)) return FALSE;

        $filestr = base64_encode(file_get_contents($path));
        if ($with_prefix !== TRUE)
            return chunk_split($filestr);

        $finfo  = getimagesize($path);
        $prefix = 'data:' . $finfo['mime'] . ';base64,';
        return chunk_split($prefix . $filestr);
    }
    
}