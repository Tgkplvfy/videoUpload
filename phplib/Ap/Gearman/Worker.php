<?php

/**
 * Gearman worker封装
 * @author       yuanxch<yuanxch@mail.open.com.cn>
 * @version      1.0
 * @copyright    
 * @access       public
 */
class Ap_Gearman_Worker
{
	
	private $_worker = null;
	public static $success = GEARMAN_SUCCESS;
	
	/**
	 * 创建GearmanWoker实例
	 * @param      none
	 * @access     public
	 * @return     void
	 * @update     2013/6/7
	*/
	function __construct()
	{
		$this->_worker = new GearmanWorker();
	    
	} // end func

	/**
	 * 添加一台server
	 * @param	   string $host
	 * @param      string $port
	 * @see GearmanWoker::addServe
	 * @access     public
	 * @return     void
	 * @update     2013/6/7
	*/
	function addServer( $host, $port )
	{
		$this->_worker->addServer( $host, $port );
	    
	} // end func
	
	/**
	 * 添加多台servers 
	 * @param      string $servers
	 * @see GearmanWoker::addServers
	 * @access     public
	 * @return     void
	 * @update     2013/6/7
	*/
	function addServers( $servers )
	{
		$this->_worker->addServers( $servers );
	    
	} // end func
	
	/**
	 * 添加映射方法 
	 *
	 * @param      string $function_name 
	 * @param	   string $function callable
	 * @param      stirng &$context 
	 * @param      int    $timeout 
	 * @see GearmanWorker::addFunction 
	 * @access     public
	 * @return     GearmanWoker
	 * @update     2013/6/7
	*/
	function addFunction( $function_name , $function , &$context = null , $timeout = null )
	{
	    $this->_worker->addFunction( $function_name , $function , $context , $timeout );
		return $this->_worker;
	} 
	
	/**
	 * 执行worker 
	 * @param      none
	 * @access     public
	 * @return     void
	 * @update     2013/6/7
	*/
	function work()
	{
	    $this->_worker->work();
	} // end func
	
	/**
	 * woker状态 
	 *
	 * @param      none
	 * @access     public
	 * @see GearmanWorker::returnCode
	 * @return     int
	 * @update     2013/6/7
	*/
	function returnCode()
	{
		 return $this->_worker->returnCode();
	} // end func

}

?>