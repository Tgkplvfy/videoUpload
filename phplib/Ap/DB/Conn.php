<?php
/**
 * DB连接基类
 * @author jiangsf
 *
 */
class Ap_DB_Conn {
	/**
	 * 数据库
	 * @var Ap_DB_Mysqli
	 */
	public $db;

    //有故障的host
    public static $failover;
	
	/**
	 * 获取一个数据库连接 数据库必须在conf/db下有ini配置文件
	 * 
	 * @param string $dbname 数据库名
	 * @return Ap_DB_Mysqli
	 */
	public function linkDB( $dbname ) {

		$this->db = self::getConn($dbname);
		return $this->db;
	}
	
	/**
	 * 获取一个数据库连接 数据库必须在conf/db.ini下有配置节信息
	 * 
	 * @param string $dbname 数据库名
	 * @return Ap_DB_Mysqli
	 */
	public static function getConn( $dbname ) {
        global $db_config;
        if ( $db_config ) {
            $db = $db_config[$dbname];
        } else {
            if (Yaf_Registry::has('sandbox')) {
                $ini_file = APP_PATH . '/conf/test-mysql.ini';
            } else {
                //mod by yuanxc 2014/1/22
                $ini_file = ROOT_PATH . '/conf/mysql.ini';
                if ( !file_exists( $ini_file ) ) {
                    $ini_file = APP_PATH . '/conf/mysql.ini';
                }
            }
            $conf = new Yaf_Config_Ini($ini_file);
            $db = $conf->get($dbname);          
        }
   
        
        //replaced by next lines. changed by yuanxch 2015/8/18
	    //$db = Ap_DB_Mysqli::getInstance($db['host'], $db['user'], $db['pass'], $db['db'], $db['port']);
	    //return $db;

        $hosts = explode(",", $db['host']);
        $dbinst = false;
        $i = 0;
        do {
            if (is_array(self::$failover) && in_array($hosts[$i], self::$failover)) continue;
            $dbinst = Ap_DB_Mysqli::getInstance($hosts[$i], $db['user'], $db['pass'], $db['db'], $db['port']);
        } while ((!$dbinst || !$dbinst->connect()) && (self::$failover[] = $hosts[$i]) && isset($hosts[++$i]));        

	    return $dbinst;
	}


	/**
	 * sql查询
	 * @param      string $sql
	 * @access     public
	 * @return     unknown
	 * @update     2013/7/10
	*/
	function query( $sql )
	{		
		return $this->db->query( $sql );
	    
	} // end func


}
