<?php
/**
 * 配置文件
 * @author jiangsf <jiangsf@mail.open.com.cn>
 * @date 2013/10/21
 */

class Ap_Util_Config {
    
    /**
     * 获取配置文件节中配置的变量值
     * @param string $file 配置文件
     * @param string $section 配置节
     * @param string $name 变量名
     * @return Yaf_Config_Simple
     */
    public static function get($file, $name, $section = 'product') {
         $filekey = $file . $section;
         if (!Yaf_Registry::has($filekey)) {
             $config = new Yaf_Config_Ini(ROOT_PATH . '/conf/' . $file,  $section);
             Yaf_Registry::set($filekey, $config);
         } else {
             $config = Yaf_Registry::get($filekey);
         }
         return $config->get($name);
    }
    
    /**
     * 按照某个product获取其中的参数，不是数组的参数
     * @return 数组
     * @param unknown $file
     * @param unknown $name
     */
    public static function getArray($file, $name='') {
    	$conf = new Yaf_Config_Ini(ROOT_PATH . '/conf/' . $file);
    	
    	return $conf->get($name);
    }
    
    
    
}

?>
