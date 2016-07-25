<?php
/**
 * 慕课网 [MODEL|CONTROLER|HEAPER]
 *
 * @author    qirl <qirenlong@imooc.com>
 * @since     2016-07-25
 * @copyright Copyright (c) 2013 Open Inc. (http://www.mukewang.com)
 * @desc      系统返回码定义功能
 */
class Ap_Common_ErrorDef {

	// 全局错误信息定义
	const ERROR_SUCCESS = 0; // 成功
	const ERROR_POST = - 1; // 请求非法
	const ERROR_PARAM = - 2; // 参数有误
	const ERROR_ACTS_CTRL = - 4; // 粒度控制命中
	const ERROR_CONTENT_FILTER = - 5; // 内容过滤命中
	const ERROR_NOT_LOGIN = - 11; // 用户未登录
	const ERROR_MYSQL_EXECUTE = - 52; // 数据库连接失败
    const ERROR_REOPERATION = - 110; //重复操作

	// 接口错误信息定义

	// 其他错误信息定义
	
	/**
	 * 错误信息定义
	 * 
	 * @var array
	 */
	public static $error_desc = array (
			self::ERROR_SUCCESS => '成功',
			self::ERROR_POST => '请求非法',
			self::ERROR_PARAM => '参数非法',
			self::ERROR_ACTS_CTRL => '您发送过于频繁，停下来喝杯茶吧！一分钟后再试。',
			self::ERROR_CONTENT_FILTER => '您的内容包含不当的用语，请修改！',
			self::ERROR_SYSTEM => '系统错误',
			self::ERROR_NOT_LOGIN => '没有登录',
			self::ERROR_MYSQL_EXECUTE => '数据库执行失败',
            self::ERROR_REOPERATION => '重复操作' 
		)

	;
}
