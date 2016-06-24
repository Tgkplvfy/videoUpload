<?php

/**
 * GearmanClient封装
 * @author       yuanxch<yuanxch@mail.open.com.cn>
 * @version      1.0
 * @copyright    
 * @access       public
 */
class Ap_Gearman_Client
{
	
	private $_client = null;
	public static $success = GEARMAN_SUCCESS;
	
	/**
	 * 创建GearmanClient实例
	 * @param      none
	 * @access     public
	 * @return     void
	 * @update     2013/6/7
	*/
	function __construct()
	{
		$this->_client = new GearmanClient();
		$this->addServers( Ap_Constants::GEARMAN_SERVERS );
	    
	} // end func


	
	/**
	 * 添加一台server
	 * @param	   string $host
	 * @param      string $port
	 * @see GearmanClient::addServe
	 * @access     public
	 * @return     void
	 * @update     2013/6/7
	*/
	function addServer( $host, $port )
	{
		$this->_client->addServer( $host, $port );
	    
	} // end func


	
	/**
	 * 添加多台servers 
	 * @param      string $servers
	 * @see GearmanClient::addServers
	 * @access     public
	 * @return     void
	 * @update     2013/6/7
	*/
	function addServers( $servers )
	{
		$this->_client->addServers( $servers );
	    
	} // end func

	
	
	/**
	 * 后台执行任务(多用于长时间处理的任务)
	 * 成功返回job_handle
	 * @param      string $function_name
	 * @param      string $workload
	 * @param      string $unique
	 * @see GearmanClient::doBackground
	 * @access     public
	 * @return     string|false
	 * @update     2013/6/7
	*/
	function doBackground( $function_name , $workload , $unique = null  )
	{
		$job_handle = $this->_client->doBackground( $function_name , $workload , $unique );

		if( $this->_client->returnCode() != GEARMAN_SUCCESS )
		{
			return false;
		}
		return $job_handle;
	    
	} // end func


	
	/**
	 * 前台执行
	 * @param      string $function_name
	 * @param      string $workload
	 * @param      string $unique
	 * @see GearmanClient::do
	 * @access     public
	 * @return     string|false
	 * @update     2013/6/7
	*/
	function doNormal( $function_name, $workload, $unique = null )
	{
		$job_handle = $this->_client->doNormal( $function_name , $workload , $unique );
		return $job_handle;
	    
	} // end func
	
	/**
	 * job状态
	 *
	 * 主要用于监测job工作状态
	 * @param      sring $job_handle
	 * @see        GearmanClient::jobStatus
	 * @access     public
	 * @return     array
	 * @update     2013/6/7
	*/
	function jobStatus( $job_handle )
	{
		return $this->_client->jobStatus( $job_handle );
	} // end func


	
	/**
	 * 执行状态
	 *
	 * 主要用于监测工作状态
	 * @see        GearmanClient::jobStatus
	 * @access     public
	 * @return     array
	 * @update     2013/6/7
	*/
	function doStatus()
	{
		return $this->_client->doStatus();
	} // end func

	/**
	 * client状态 
	 *
	 * @param      none
	 * @access     public
	 * @see GearmanWorker::returnCode
	 * @return     int
	 * @update     2013/6/7
	*/
	function returnCode()
	{
		 return $this->_client->returnCode();
	} // end func

	
} // end class

?>