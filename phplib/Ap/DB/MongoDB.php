<?PHP
/**
 * 慕课网 [MODEL|CONTROLER|HEAPER]
 *
 * @author    chendingyou <chendy@mail.open.com.cn>
 * @since     2013-07-04
 * @copyright Copyright (c) 2013 Open Inc. (http://www.mukewang.com)
 * @desc      mongodb 类
 *
 * @sample    $mongo           = new Ap_DB_MongoDB();
 *            $mongoClient     = $mongo->getMongoClient();
 *            $mongoDB         = $mongo->getMongoDB();
 *            $MongoCollection = $mongo->getCollection($cname);
 */


class Ap_DB_MongoDB {

    /**
     *  模块名
     */
    protected $_moduleName = false;

    /**
     *  mongodb client handler
     */
    private $_mongoClient = false;

    /**
     *  mongodb db handler
     */
    private $_mongDB = false;

    public function __construct($module = "product") {

        if (!$this->_moduleName) {
            $this->_moduleName = $module;
        }

   }
   
   public static function connect($db, $collection) {
        if (empty(self::$conf)) {
            self::$conf = new Yaf_Config_Ini(ROOT_PATH.'/conf/mongodb.ini');
        }
        return self::$conf->get($db);
        $conf = new Yaf_Config_Ini(ROOT_PATH.'/conf/mongodb.ini');
        $conf = $conf->get($db);
        
        $servers = explode(',', $conf->servers);
        $sid = array_rand($servers); //选择一个写入服务器，当前采用随机选择
        $server = trim( $servers[$sid] );
        $conf = self::getConf($server); //获取选定的写入服务器的配置
        return $conf;
   }

    /**
     * desc 返回MongoClient句柄
     * @return MongoClient
     */
    public function getMongoClient() {

        if($this->_mongoClient !== false) {
            return $this->_mongoClient;
        }

        $mongo_ini = new Yaf_Config_Ini(APP_PATH . '/conf/mongodb.ini', $this->_moduleName);
        //$mongo = new MongoClient("mongodb://{$mongo_ini->user}:{$mongo_ini->pass}@{$mongo_ini->host}");
        $mongo = new MongoClient($mongo_ini->host);
        if ($mongo){
            $this->_mongoClient = $mongo;
            $this->_mongDB = $this->_mongoClient->selectDB($mongo_ini->db);
        }
        return $this->_mongoClient;
    }


    /**
     * desc 返回MongoDB句柄
     * @return MongoDB
     */
   public function getMongoDB() {

        if($this->_mongDB !== false) {
            return $this->_mongDB;
        }
        $mongoClient = $this->getMongoClient();

        return $this->_mongDB;
    }

    /**
     * desc 更改模块默认db库，返回MongoDB句柄
     *
     * @param  $dbname string 库名
     * @return MongoDB
     */
    public function setMongoDB($dbname) {

        if (!$dbname) {
            return false;
        }

        $mongoClient = $this->getMongoClient();
        if ($mongoClient) {
            $this->_mongDB = $mongoClient->selectDB($dbname);
            return $this->_mongDB;
        }

        return false;
    }

    /**
     * desc 获取文档类，返回MongoCollection句柄
     *
     * @param  $cName string collection name
     * @return MongoCollection
     */
    public function getCollection($cName) {

        if (!$cName) {
            return false;
        }

        $mongoDB = $this->getMongoDB();
        if ($mongoDB) {
            return new MongoCollection($mongoDB, $cName);
        }

        return false;
    }
}
