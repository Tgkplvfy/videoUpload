<?php
/**
 * 慕课网 [MODEL|CONTROLER|HEAPER]
 *
 * @author    chendingyou <chendy@mail.open.com.cn>
 * @since     2013-07-05
 * @copyright Copyright (c) 2013 Open Inc. (http://www.mukewang.com)
 * @desc      xx功能
 */

class Ap_Common_Vars {
    /**
     * 用户状态
     *
     */
    const USER_REFUSE = 0;     // 审核拒绝
    const USER_PASS   = 1;     // 通过审核
    const USER_PROHIBIT_NONE = 0; //未禁言
    const USER_PROHIBIT_WEEK = 1; //被禁言一周
    const USER_PROHIBIT_EVER = 2; //被永久禁言
    const MOOC_FAIRY_UID = 10000; //慕课女神账户ID
    const MOOC_COURSE_BBSID = 2 ; //慕课网课程论坛ID
    const MOOC_WENDA_COMMENT = 2000; //慕课网问答评论限制字符数
    const MOOC_COURSE_COMMENT = 300; //慕课网问答评论限制字符数
    
    const COOKIE_DOMAIN = '.imooc.com';
    
    //求课的状态
    const SEEK_DEFAULT = 0; //默认
    const SEEK_AGREE = 1; //通过审核
    const SEEK_SUPPORT_READY = 2; //达成支持数目标且有讲师应聘
    const SEEK_MAKE = 3; // 达成制作协议
    const SEEK_HAVE_COURSE = 4; // 关联课程id
    const SEEK_REFUSE = 5; // 拒绝
    const SEEK_EXPIRE = 6; // 过期
    //课程个性化关键字分割符
    const COURSE_KEYWORDS_SEPERATOR = ',';
    
    //首页老师数目及学生数目
    const INDEX_STUDENT_NUM = 24;
    const INDEX_TEACHER_NUM = 6;
    const INDEX_VIDEO_URL = 'web_index_video_url';
    const INDEX_COURSE_INFO_KEY = 'web_index_course_info';
    const INDEX_STUDENT_INFO_KEY = 'web_index_student_info';
    const INDEX_TEACHER_INFO_KEY = 'web_index_teacher_info';
    
    // 回复数超过多少设为精彩问答
    const COURSE_ELITE_REPLY_NUM = 5;
    
    
    //体验中心相关  @todo 上线后改成正式url
    const TIYAN_DOMAIN = 'http://t.imooc.com';
    const WWW_DOMAIN = 'http://www.imooc.com';
    
    /**
     * @var number 图片JPG格式
     */
    const FILE_TYPE_JPG = 1;
    /**
     * @var number 图片PNG格式
     */
    const FILE_TYPE_PNG = 2;
    /**
     * @var number 图片GIF格式
     */
    const FILE_TYPE_GIF = 3;
    /**
     * @var number JSON数据格式
     */
    const FILE_TYPE_JSON = 4;



    /**
     * @var string 一周热门
     */
    const MC_BBS_WEEK_HOT = 'mcbbsweekhot';

    /**
     * @var int 一周热门生存时间60s
     */
    const MC_BBS_WEEK_HOT_LIFETIME = 60;

    /**
     * @var string 一周热门
     */
    const MC_BBS_TOP = 'mcbbstop';

    /**
     * @var int 一周热门生存时间60s
     */
    const MC_BBS_TOP_LIFETIME = 300;

    
    /**
     * 用户状态
     *
     */
    public static $user_status = array(
        self::USER_REFUSE => '禁止',
        self::USER_PASS   => '正常',
    );

    /**
     * 用户状态
     *
     */
    public static $user_type = array(
        '1' => '学生',
        '2' => '老师',
        '3' => '管理员',
    );


    /**
     * 用户成就
     */
    public static $cfg_user_achieve = array(
        'course' => array(
            '5' => '小伙伴',
            '20' => '慕课学者',
            '50' => '我是老湿',
            '100' => '叫我叫兽',
            '200' => '请叫我大师',
        ),
        'question' => array(
            '5' => '不懂问问',
            '20' => '找到感觉',
            '100' => '不耻下问',
            '500' => '好奇的喵星人',
            '1000' => '我是讨论制造机',
        ),
        'reply' => array(
            '5' => '东张西望',
            '20' => '我话很多',
            '100' => '围观你们',
            '500' => '强势围观你们',
            '1000' => '我牛X啥都知道',
        ),
        'praisefrom' => array(
            '20'=>'你是好人',
            '50'=>'有人挺你',
            '100'=>'三十二个赞',
            '500'=>'我很有良心',
            '1000'=>'我有型总被赞',
        ),
        'praiseto' => array(
            '40'=>'很勤劳',
            '80'=>'老挺别人',
            '200'=>'全民被赞',
            '1000'=>'我是老好人',
            '5000'=>'慕课博爱教授',
        ),
        'note' => array(
            '10'=>'一份耕耘',
            '30'=>'找到组织',
            '100'=>'敏而好学',
            '400'=>'神笔马良',
            '1000'=>'笔耕不辍',
        ),
        'learn' => array(
            '36000'=>'青铜圣斗士',
            '144000'=>'黑暗圣斗士',   
            '360000'=>'白银圣斗士',
            '900000'=>'黄金圣斗士',
            '1800000'=>'北欧圣斗士'
        ),        
        'online' => array( 
            '72000'=>'水星',
            '180000'=>'金星',
            '720000'=>'地球',
            '1800000'=>'火星',
            '3600000'=>'木星',
        ),
        /*
        'learn' => array(
            '10'=>'好好学习',
            '40'=>'发奋刷强',
            '100'=>'凌云壮志',
            '250'=>'',
            '500'=>''
        ),        
        'online' => array(
            '20'=>'忽隐忽现',
            '50'=>'来去匆忙',
            '200'=>'孤单不寂寞',
            '500'=>'',
            '1000'=>'微风细雨夜相随',
        ),
        */
        'wiki' => array(
            '5' => '文思如泉',
            '10' => '满腹经纶',
            '20' => '才思敏捷高',
            '50' => '学富五车半',
            '100' => '才高八斗多半节',        
        ),
        'friend' => array(
            '8' => '气宇非凡',
            '20' => '交际达人',
            '50' => '我朋友很多',
            '80' => '够兄弟讲义气',
            '200' => '我不是一个人在战斗',
        ),
    );
    
    
    //作品默认目录
    const OPUS_ROOT_PATH = '/data/mfs/opus/';
    const FILE_DOWNLOAD_URL = 'http://file.imooc.com/';
   
    
    
}
