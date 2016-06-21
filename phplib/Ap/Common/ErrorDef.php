<?php
/**
 * 慕课网 [MODEL|CONTROLER|HEAPER]
 *
 * @author    chendingyou <chendy@mail.open.com.cn>
 * @since     2013-07-05
 * @copyright Copyright (c) 2013 Open Inc. (http://www.mukewang.com)
 * @desc      系统返回码定义功能
 */
class Ap_Common_ErrorDef {
	const ERROR_SUCCESS = 0; // 成功
	const ERROR_POST = - 1; // 请求非法
	const ERROR_PARAM = - 2; // 参数有误
	const ERROR_ACTS_CTRL = - 4; // 粒度控制命中
	const ERROR_CONTENT_FILTER = - 5; // 内容过滤命中
	const ERROR_NOT_LOGIN = - 11; // 用户未登录
	const ERROR_USER_NULL = - 12; // 用户不存在
	const ERROR_USER_STATUS = - 13; // 用户未通过审核
	const ERROR_USER_PROHIBIT_WEEK = - 14; // 用户被禁言一周
	const ERROR_USER_PROHIBIT_EVER = -15; // 用户被永久禁言
	const ERROR_USER_FROZEN = -16; // // 用户被冻结
	const ERROR_MSG_NULL = - 21; // 私信内容为空
	const ERROR_MSG_TOO_LONG = - 22; // 私信超过长度
	const ERROR_MYSQL_CONNECT = - 51; // 数据库连接失败
	const ERROR_MYSQL_EXECUTE = - 52; // 数据库连接失败
	const ERROR_OP_DENY = - 99; // 没有权限操作
	const ERROR_SYSTEM = - 100; // 系统出错
    const ERROR_REOPERATION = - 110; //重复操作
	const ERROR_WIKI_KEY_Duplicate = 601; //wiki key 重复；
	const ERROR_USER_CHECK_EMAIL = - 17;//用户邮箱未验证通过
	const ERROR_USER_CHECK_MP = -18;
	const ERROR_USER_CHECK_PASS = -19;
    const ERROR_USERNAME_EMPTY = -1500; //用户名为空
    const ERROR_PASSWORD_EMPTY = -1501; //密码为空
	/**
	 * 未知错误
	 * 
	 * @var int
	 */
	const ERROR_UNKNOW = - 9999; // 未知错误
	/**
	 * 文件不存在
	 * 
	 * @var int
	 */
	const ERROR_FILE_NOTEXISTS = - 500; // 文件不存在
	/**
	 * 不是有效的文件格式
	 * 
	 * @var int
	 */
	const ERROR_FILE_INVALID = - 501; // 不是有效的文件格式
	/**
	 * API返回出错
	 * 
	 * @var int
	 */
	const ERROR_API_WRONG = - 505; // API返回出错
	/**
	 * 答案错误
	 * 
	 * @var int
	 */
	const ERROR_WRONG_ANSWER = - 200; // 答案错误
	const ERROR_FREE_PROGRAM = - 201; //自由编程
	const E_COMMON_CODE_BASE = - 100000; // 各子域名(分组)错误基础代码
	const E_HOMEPAGE_BASE = - 101000; // 首页
	const E_COURSE_BASE = - 102000; // 课程
	const ID_NOTNULL = - 102001;
	const COURSENOTEXIST = - 102002;
	const CHAPTERNOTEXIST = - 102004;
	const MEDIANOTEXIST = - 102005;
	
	
	const E_MESSAGE_BASE = - 103000; // 信息
	
	const E_USER_BASE = - 104000; // 用户
	
	const E_SPACE_BASE = - 105000; // SPACE
	const USERNOLOGIN = - 105001;
	
	//问题和笔记
	const QUESNOTEXIST = - 102003;
	const QUESDESCNOTNULL = - 102006;
	const QUESNOANSWER = - 102007;
	const ANSWERDESCNOTNULL = - 102008;
	const CEPINGEXAMEMPTY = - 102009;
	const HASSUPPORTED = - 102010;
	const NOTE_NOT_EXISTS = -102011; //笔记不存在
	const ANSWER_NOT_EXISTS = -102012;// 回复不存在
    const HASPRAISED = -102013;
    const HASCOLLECTED = -102014;
    const NOT_ALLOW_COLLECT = -102015;
	
    const NEED_VERIFY_CODE = -103001;//需要验证码
    const VERIFY_CODE_WRONG = -103002;//验证码错误
    
    const PUBLISH_TOO_SHORT = -103003;//输入字数过少
    const USER_IS_LOCKED = -103004; // 用户已被冻结
    const PUBLISH_AFTER_ONEHOUR = -103005; //天亮再发吧
    const REG_TOO_OFEN = -103006; //注册频繁
    const OPTION_FREQUENTLY_24 = -103007; //操作频繁
    const COOMENT2QUESTION = -103008; //建议将评论发到问答
    
    const OPUS_OVER_LENGTH = -104001;//文件过大
    const OPUS_OVER_ALL_LENGTH = -104002; //存储已满
    const OPUS_IS_EXISTS = -104003;// 重名

	
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
            self::ERROR_REOPERATION => '重复操作',
	        // USER
			self::ERROR_USER_NULL => '用户不存在',
			self::ERROR_USER_STATUS => '用户未通过审核',
	        self::ERROR_USER_PROHIBIT_EVER => '被永久禁言',
	        self::ERROR_USER_PROHIBIT_WEEK => '被禁言一周',
	        self::ERROR_USER_FROZEN => '账户被冻结',
	        self::ERROR_USER_CHECK_EMAIL => '账户邮箱未验证通过',
	        self::ERROR_USER_CHECK_MP => '同学,您的学习经验不足,不能发哦!快去学习吧!',
	        self::ERROR_USER_CHECK_PASS => '密码错误',
            self::ERROR_USERNAME_EMPTY => '用户名为空',
            self::ERROR_PASSWORD_EMPTY => '密码为空',
	        
			self::ERROR_MSG_NULL => '私信内容为空',
			self::ERROR_MSG_TOO_LONG => '私信内容太长',
			self::ERROR_OP_DENY => '没有相关操作权限',
			self::ERROR_SYSTEM => '系统错误',
			self::ERROR_FILE_NOTEXISTS => '文件不存在',
			self::ERROR_FILE_INVALID => '不是有效的文件格式',
			self::ERROR_API_WRONG => 'API返回出错',
			self::ERROR_UNKNOW => '未知错误',
			self::ERROR_WRONG_ANSWER => '答案错误',
			self::ERROR_FREE_PROGRAM => '自由编程',
			// 课程
			self::ID_NOTNULL => '课程ID不能为空',
			self::COURSENOTEXIST => '课程不存在或被删除',
			self::QUESNOTEXIST => '问题不存在或被删除',
			self::CHAPTERNOTEXIST => '章不存在或被删除',
			self::MEDIANOTEXIST => '节不存在或被删除',
			self::QUESDESCNOTNULL => '问题描述不能为空',
			self::QUESNOANSWER => '该问题没有回复',
			self::ANSWERDESCNOTNULL => '回复不能为空',
			self::CEPINGEXAMEMPTY => '测评问题列表为空',
			self::HASSUPPORTED => '您已经支持过了',
            self::HASPRAISED => '您已经称赞过了',
            self::HASCOLLECTED => '您已经采集过了',
            self::NOT_ALLOW_COLLECT  => '不允许采集自己的笔记',
			
			// SPACE
			self::USERNOLOGIN => '该页面需要先登录才能访问', 
			self::NOTE_NOT_EXISTS => '笔记不存在',
			self::ANSWER_NOT_EXISTS => '回复不存在',
	
			// 问题和笔记
			//wiki
			self::ERROR_WIKI_KEY_Duplicate =>'wiki词条已存在',
			self::NEED_VERIFY_CODE => '请输入验证码',
			self::VERIFY_CODE_WRONG => '验证码错误',
			self::COOMENT2QUESTION => '建议将评论发到问答',

			// 求课
			self::PUBLISH_TOO_SHORT => '输入字数过少',
			self::USER_IS_LOCKED => '因发表过度频繁，账号已被冻结，请联系群管理员',
			self::PUBLISH_AFTER_ONEHOUR => '请先看会儿课程再发吧',
			self::REG_TOO_OFEN => '今天您注册用户数已超限',
			self::OPTION_FREQUENTLY_24 => '你发问题超限了，明天在试试吧！',
			self::OPUS_OVER_LENGTH => '上传文件不能超过10M！',
			self::OPUS_OVER_ALL_LENGTH => '你的作品存储容量已满！',
			self::OPUS_IS_EXISTS => '你输入的文件名已存在！'
		)

	;
}
