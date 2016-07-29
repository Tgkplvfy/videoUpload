<?php
/**
 * Mysqli数据库操作基类
 * 
 * @author jiangsf
 * example:
 * Ap_DB_Mysqli::$sDebg = 1;打开debug模式,输出提示信息.
 */
class Ap_DB_Mysqli {

	//调式模式
	public static $DEBUG = 0;
	private static $obj = array();
    //缓存连接
    private static $dbconn = array();
	var $dbhost;
	var $dbuser;
	var $dbpass;
	var $dbname;
	var $dbport;
	
	/**
	 * @var mysqli
	 */
	var $_db;
	public function __construct($dbhost, $dbuser, $dbpass, $dbname, $dbport=3306) {
		$this->dbhost = $dbhost;
		$this->dbuser = $dbuser;
		$this->dbpass = $dbpass;
		$this->dbname = $dbname;
		$this->dbport = $dbport;
	}
	
	/**
	 * 获取dbname的全局唯一的数据库实例（多例模式）dbname做区分
	 * @param string $dbhost
	 * @param string $dbuser
	 * @param string $dbpass
	 * @param string $dbname
	 * @return Ap_DB_Mysqli
	 */
	/*
    public static function getInstance($dbhost, $dbuser, $dbpass, $dbname,$dbport=3306) {
		if (empty(Ap_DB_Mysqli::$obj[$dbname])) {
			self::$obj[$dbname] = new Ap_DB_Mysqli($dbhost, $dbuser, $dbpass, $dbname, $dbport);
		} 
		return self::$obj[$dbname];
	}
    */
	public static function getInstance($dbhost, $dbuser, $dbpass, $dbname,$dbport=3306) {
        $hashKey = md5($dbhost . $dbuser . $dbpass . $dbname . $dbport);
		if (empty(Ap_DB_Mysqli::$obj[$hashKey])) {
			self::$obj[$hashKey] = new Ap_DB_Mysqli($dbhost, $dbuser, $dbpass, $dbname, $dbport);
		} 
		return self::$obj[$hashKey];
	}
	
	/**
	 * 连接数据库 返回唯一的数据库连接
	 * 
	 * @return mysqli
	 */
	public function connect() {
        $hashKey = md5($this->dbhost.$this->dbuser.$this->dbpass.$this->dbname.$this->dbport);
        
	    if (isset(self::$dbconn[$hashKey]) && !self::$dbconn[$hashKey]->ping()) { //长时间未使用导致超时，进行重连
	        self::$dbconn[$hashKey]->close();
	        unset(self::$dbconn[$hashKey]);
	    }
		if (!isset(self::$dbconn[$hashKey])) {
			self::$dbconn[$hashKey] = new mysqli ( $this->dbhost, $this->dbuser, $this->dbpass, $this->dbname ,$this->dbport);
			if (mysqli_connect_errno()) {
				Ap_Log::error(" Connect error, Error No: " . mysqli_connect_errno() .
						" Error message: " . mysqli_connect_error () .
						" Host: " . $this->dbhost ." user:" . $this->dbuser . " db:" . $this->dbname
						. " Method:" . __METHOD__ ." Line:" . __LINE__
					);
                unset(self::$dbconn[$hashKey]);
				return false;
			} else {
                self::$dbconn[$hashKey]->query("set names 'utf8'");
			}
		}
        $this->_db = self::$dbconn[$hashKey];
		return self::$dbconn[$hashKey];
	}

	

	/**
	 * 执行SQL
	 * 
	 * @param $string $sql        	
	 * @return mysqli_result
	 */
	public function query($sql) {
		$this->connect ();
		$rs = $this->_db->query ( $sql );
		if( $this->_db->errno ){
			Ap_Log::error("Failed to execute SQL: " . $sql . 
				" Error No: " . $this->_db->errno . " Error message: " . $this->_db->error
				. " Method:" . __METHOD__ ." Line:" . __LINE__
			);
		}
		//Ap_Log::debug($sql);
        //$this->__halt( $sql . " error:" . $this->_db->error );
		return $rs;
	}
	
	/**
	 * 获取影响的行数
	 * @return int
	 */
	public function affect_rows() {
	    return $this->_db->affected_rows;
	}
	
	/**
	 * 关闭数据库
	 */
	public function close() {
		$this->_db->close ();
	}
	
	/**
	 * 获取一行数组
	 * 
	 * @param string $sql        	
	 * @param array $type        	
	 */
	public function fetchArray($sql, $type = MYSQLI_ASSOC) {
		$result = $this->query ( $sql );
		$row = $result->fetch_array ( $type );
		$result->free ();
		return $row;
	}
	
	/**
	 * 通过一个SQL语句获取一行信息(字段型)
	 *
	 * @access public
	 * @param string $sql SQL语句内容
	 * @return mixed
	 */
	public function fetchRow($sql) {
		return $this->fetchArray($sql);
	}
	
	/**
	 * 获取当前行数据的key字段的value值
	 * @param string $sql
	 * @param string $key
	 * @return mixed
	 */
	public function fetchValue($sql, $key) {
		$row = $this->fetchArray($sql);
		return @$row[$key];
	}
	
	/**
	 * 获取所有行数据的key字段的value值
	 * @param string $sql
	 * @param string $key
	 * @return Array
	 * <p>返回一个包含字段值的数组</p>
	 */
	public function fetchValues($sql, $key) {
		$rows = $this->fetchAll($sql);
		$ary = array();
		foreach ($rows as $row) {
			$ary[] = $row[$key];
		}
		return $ary;
	}
	
	/**
	 * 获取一个对象
	 * @param string $sql        	
	 * @param Object $type        	
	 */
	public function fetchObject($sql, $className = 'stdClass') {
		$result = $this->query ( $sql );
		$row = $result->fetch_object ( $className );
		$result->free ();
		return $row;
	}
	
	/**
	 * 获取一个对象数组 
	 * @param string $sql        	
	 * @param Object $type        	
	 */
	public function fetchObjects($sql, $className = 'stdClass') {
		$result = $this->query ( $sql );
		$objects = array();
		while($row = $result->fetch_object ( $className )) {
			$objects[] = $row;
		}
		$result->free ();
		return $objects;
	}
	
	/**
	 * 获取所有数据行
	 * @param string $sql
	 * @param string $type
	 * @return array
	 */
	public function fetchAll($sql, $type = MYSQLI_ASSOC) {
		$result = $this->query ( $sql );
		$rows = array();
		if ( $result ) { 
			while ($row = $result->fetch_array($type)) {
				$rows[] = $row;
			}
			$result->free ();
		}
		
		return $rows;
	}

	/**
	 * 通过一个SQL语句获取全部信息(字段型)
	 *
	 * @access public
	 * @param string $sql SQL语句
	 * @param string $key 一般为id字段
	 * @return array
	 */
	public function getArray($sql, $key='') {
		$rows = $this->fetchAll($sql);
		
		if( $key == '' ) {
		    return $rows;
		}
		
		$ary = array();
		foreach ($rows as $row) {
		    $ary[$row[$key]] = $row;
		}
		return $ary;
		
	}
	
	/**
	 * 插入一行数据到表格，并返回主键ID
	 * @param string $table
	 * @param array $data
	 * @return number 失败返回0
	 */
	public function insert($table, $data) {
	    if( !is_array( $data ) || empty( $data ) ) {
	        return 0;
	    }
		$this->connect();
		$columns = $values = array();		
		foreach ( $data as $k => $v ) {
			array_push($columns, "`$k`");
			$v = $this->_db->real_escape_string($v);
			array_push($values, "'$v'");
		}		
		$columns = implode(',', $columns);
		$values = implode(',', $values);
		$sql = "insert into {$table} ( {$columns} ) values ( {$values} )";
		if ($this->query($sql)) {
			return $this->insert_id();
		}
		return 0;
	}



	/**
	 * 修改某个值
	 * @param  string $table
	 * @param  array $data
	 * @param array $condition
	 * @return boolean 
	 * @added by jiangwb 2013-07-09
	 */
	public function update($table,$data,$condition=array()){
	    if( !is_array( $data ) || empty( $data ) ) {
	        return false;
	    }
		$where='';
		if(!empty($condition)){
		    if( is_array( $condition ) ) {
    			foreach($condition as $k=>$v){
    				$where.=$k."='".$this->filter($v)."' and ";
    			}
			    $where='where '.$where .'1=1';
		    } else {
		        $where='where '.$condition; //需要在dao中过滤
		    }
		    
		}
		$updatastr = '';
		if(!empty($data)){
			foreach($data as $k=>$v){
				$updatastr.= "`$k`"."='".$this->escape($v)."',";
			}
			$updatastr = 'set '.rtrim($updatastr,',');
		}
		$sql = "update {$table} {$updatastr} {$where}";
// 		echo $sql;
		return $this->query($sql);
	}
	
	/**
	 * last insert id
	 * @param      none
	 * @access     public
	 * @return     void
	 * @update     2013/6/13 16:17:03
	*/
	function insert_id()
	{
		return $this->_db->insert_id;	    
	} // end func



	/**
	 * 中断控制
	 * @param      string $msg
	 * @access     private
	 * @return     void
	 * @update     2013/6/14 11:45:33
	*/
	private function __halt( $msg )
	{
        /*
		if(self::$DEBUG)
		{
			printf( "mysqli:%s<br/>\r\n", $msg );
		}
        */
	} // end func

	/**
	 * 转义特殊字符
	 * @param string $str
	 * @return string
	 */
	public function escape( $str ) { 
	    return empty( $str ) ? $str : mysql_escape_string( strval( $str ) );
	}

	/**
	 * 对查询条件值过滤
	 * @param string $str
	 * @return string
	 */
	public function filter( $str ) {
	    
	    if( empty( $str ) ) {
	        return $str;
	    }
	    $str = str_replace( '%', '\%', $str );
	    $str = str_replace( '_', '\_', $str );
	    
	    $str = mysql_escape_string( $str );
	    
	    return $str;
	    
	}
}
