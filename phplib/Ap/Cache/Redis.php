<?php
/**
 * Redis缓存类
 * @author jiangsf
 */
class Ap_Cache_Redis {
	/**
	 * Redis主库
	 * @var Redis
	 */
	protected $_Redis = null;
	/**
	 * Redis从库数组
	 * @var Array
	 */
	protected $_slaves = array();
	/**
	 * Redis从库数量
	 * @var number
	 */
	protected $_cnt = 0;
	/**
	 * Redis当前从库序列
	 * @var number
	 */
	protected $_index = 0;
	
	/**
	 * 全局唯一的Ap_Cache_Redis实例
	 * @var Ap_Cache_Redis
	 */
	private static $_instance = null;

	/**
	 * 配置信息
	 *
	 *  - server = Redis服务器地址
	 *  - port = 端口 (默认: 6379)
	 *  - timeout = 超时时间 (默认: 0)
	 *  - persistent = 是否使用长连接 (默认: true)
	 *
	 * @var array
	 */
	public $settings = array();
	
	public function __construct($env = 'product') {
		$config  = new Yaf_Config_Ini(APP_PATH.'/conf/redis.ini', $env);
        $conf = $config->toArray();
        //changed by yuanxch 2015/7/3
        //$ini = new Ap_Util_Ini();
        //$conf = $ini->getRedisConf($env);
		$settings = $conf['master'];
		$this->_cnt = $settings['slaves'];
		$this->_Redis = $this->_connect($settings);
		
		//@todo 从库是初始化连接 还是后期用到的时候再连接？这里需要思考一下，暂时采用先连接的方法
		for ($i = 0; $i < $this->_cnt; $i ++) {
			$settings = $conf['slave' . $i];
			array_push($this->_slaves, $this->_connect($settings));
		}
	}
	
	/**
	 * 获取全局唯一的Redis缓存实例
	 * @return Ap_Cache_Redis
	 */
	public static function getInstance() {
		if (! Ap_Cache_Redis::$_instance) {
			Ap_Cache_Redis::$_instance = new Ap_Cache_Redis();
		}
		return Ap_Cache_Redis::$_instance;
	}
	
	/**
	 * 获取一个Slave实例
	 * @param $id number 指定的从库ID
	 * @return Redis
	 */
	protected function getSlave($id = -1) {
		if ($this->_cnt < 1) {
			return $this->_Redis;
		}
		if ($id > -1 && $id < $this->_cnt) {
			return $this->_slaves[$id];
		}
		//@todo Redis从库顺序轮循, 是否考虑随机轮循？ 当前顺序某种意义上来说也是随机的，因为比较平均
		$this->_index = ($this->_index+1 < $this->_cnt) ? $this->_index++ : 0;
		return $this->_slaves[$this->_index];
	}

	/**
	 * 链接到缓存服务器
	 *
	 * @return Redis
	 */
	protected function _connect($settings) {
		try{
			$redis = new Redis();
			if (empty($settings['persistent'])) {
				$res = $redis->connect($settings['server'], $settings['port'], $settings['timeout']);
			} else {
				$res = $redis->pconnect($settings['server'], $settings['port'], $settings['timeout']);
			}
			if(!$res){
				Ap_Log::error(sprintf('redis TIME=%s FILE=%s LINE=%s MESSAGE=%s SERVER=%s', date('Y-m-d H:i:s'),
				__FILE__, __LINE__, 'connect error', $settings['server']));
			}
		}catch(Exception $e){
			Ap_Log::error(sprintf('redis TIME=%s FILE=%s LINE=%s MESSAGE=%s SERVER=%s', date('Y-m-d H:i:s'),
			__FILE__, __LINE__, $e->getMessage(), $settings['server']));
		}
		
		return $res ? $redis : false;
		
	}

	/**
	 * 写入数据到缓存
	 *
	 * @param string $key 键名
	 * @param mixed $value 值
	 * @param integer $duration 缓存时间=生命周期
	 * @return boolean 写入成功返回true，否则为false
	 */
	public function write($key, $value, $duration = 0) {
		if(!$this->_Redis){
			return false;
		}
		if (!is_int($value)) {
			$value = serialize($value);
		}
		if ($duration === 0) {
			return $this->_Redis->set($key, $value);
		}

		return $this->_Redis->setex($key, $duration, $value);
	}

	/**
	 * 写入数据到缓存(json格式)
	 *
	 * @param string $key 键名
	 * @param mixed $value 值
	 * @param integer $duration 缓存时间=生命周期
	 * @return boolean 写入成功返回true，否则为false
	 */
	public function writejson($key, $value, $duration = 0) {
		if(!$this->_Redis){
			return false;
		}
		if (!is_int($value)) {
			$value = json_encode($value);
		}
		if ($duration === 0) {
			return $this->_Redis->set($key, $value);
		}

		return $this->_Redis->setex($key, $duration, $value);
	}
	
	/**
	 * 批量写入到缓存（key=>value)
	 * @param array $data
	 */
	public function writeMulti($data) {
		if(!$this->_Redis){
			return false;
		}
	    return $this->_Redis->mset($data);
	}

	/**
	 * 从缓存中读取数据
	 *
	 * @param string $key 键名
	 * @return mixed 缓存的值，如果没有则返回false
	 */
	public function read($key) {
		if(!$this->getSlave()){
			return false;
		}
		$value = $this->getSlave()->get($key);
		if (ctype_digit($value)) {
			$value = (int)$value;
		}
		if ($value !== false && is_string($value)) {
			$value = unserialize($value);
		}
		return $value;
	}

	/**
	 * 从缓存中读取数据
	 *
	 * @param string $key 键名
	 * @return mixed 缓存的值，如果没有则返回false
	 */
	public function readJson($key, $array=false) {
		if(!$this->getSlave()){
			return false;
		}
		$value = $this->getSlave()->get($key);
		if (ctype_digit($value)) {
			$value = (int)$value;
		}
		if ($value !== false && is_string($value)) {
			$value = json_decode($value, $array);
		}
		return $value;
	}
	
	/**
	 * 读取一组key
	 * 
	 * @param array $keys
	 */
	public function readMulti($keys, $unserialize = true) {
		if(!$this->getSlave()){
			return false;
		}
	    $values = $this->getSlave()->mget($keys);
	    
	    if( $unserialize ) {
    	    foreach ($values as $key => $data) {
    	        $values[$key] = unserialize($data);
    	    }
	    }
	    //var_dump($expression)
	    return $values;
	}

	/**
	 * 将指定键值递增
	 *
	 * @param string $key 键名
	 * @param integer $offset 增量，默认为1
	 * @return 递增后的键值，失败返回false
	 * @throws CacheException when you try to increment with compress = true
	 */
	public function increment($key, $offset = 1) {
		if(!$this->_Redis){
			return false;
		}
		return (int)$this->_Redis->incrBy($key, $offset);
	}

	/**
	 * 将指定键值递减
	 *
	 * @param string $key 键名
	 * @param integer $offset 递减数，默认为1
	 * @return 递减后的键值，失败返回false
	 * @throws CacheException when you try to decrement with compress = true
	 */
	public function decrement($key, $offset = 1) {
		if(!$this->_Redis){
			return false;
		}
		return (int)$this->_Redis->decrBy($key, $offset);
	}

	/**
	 * 从缓存中删除指定的键值
	 *
	 * @param string $key 键名
	 * @return boolean
	 */
	public function delete($key) {
		if(!$this->_Redis){
			return false;
		}
		return $this->_Redis->delete($key) > 0;
	}
	
	public function smembers($key) {
		if(!$this->getSlave()){
			return false;
		}
	    return $this->getSlave()->sMembers($key);
	}
	
	public function srem($key, $value) {
		if(!$this->_Redis){
			return false;
		}
	    return $this->_Redis->srem($key, $value);
	}
	
	public function scard($key) {
		if(!$this->_Redis){
			return false;
		}
	    return $this->_Redis->scard($key);
	}

	/**
	 * 清除所有的Redis缓存
	 * @return boolean
	 */
	public function clear($prefix='') {
		if(!$this->_Redis){
			return false;
		}
		$keys = $this->_Redis->getKeys($prefix.'*'); //@不可用
		$this->_Redis->del($keys);

		return true;
	}

	/**
	 * 断开连接
	 * @return void
	 */
	public function __destruct() {
		if(!$this->_Redis){
			return false;
		}
		$this->_Redis->close();
		for($i = 0; $i < $this->_cnt; $i++) {
			$this->getSlave($i)->close();
		}
	}
	
	/**
	 * 添加data到队列
	 * @param string $key
	 * @param mixed $data
	 */
	public function push($key, $data) {
		if(!$this->_Redis){
			return false;
		}
		return $this->_Redis->lPush($key, json_encode($data));
	}
	
	/**
	 * 添加字符串到队列中
	 * 
	 * @param string $key
	 * @param string $value
	 */
	public function lpush($key, $value) {
		if(!$this->_Redis){
			return false;
		}
		return $this->_Redis->lpush($key, $value);
	}
	
	/**
	 * 从队尾删除元素并返回被删除元素值
	 * 
	 * @param string $key
	 */
	public function rpop($key) {
		if(!$this->_Redis){
			return false;
		}
		return $this->_Redis->rPop($key);
	}
	
	public function brpop($key, $timeout=0){
		if(!$this->_Redis){
			return false;
		}
		return $this->_Redis->brPop($key, $timeout);
	}
	
	public function rpoplpush($key, $dest){
		if(!$this->_Redis){
			return false;
		}
		return $this->_Redis->rpoplpush($key, $dest);
	}
	
	public function brpoplpush($key, $dest, $timeout=0){
		if(!$this->_Redis){
			return false;
		}
		return $this->_Redis->brpoplpush($key, $dest, $timeout);
	}
	/**
	 * 从队列中pop一个数据 先入先出原则
	 * @param unknown $key
	 * @return mixed
	 */
	public function pop($key) {
		if(!$this->_Redis){
			return false;
		}
		$data = $this->_Redis->rPop($key);
		return json_decode($data);
	}
	
	public function publish($key, $data) {
		if(!$this->_Redis){
			return false;
		}
		return $this->_Redis->publish($key, json_encode($data));
	}
	
	/**
	 * 获取List长度
	 * @param string $key
	 * @return number
	 */
	public function llen($key) {
		if(!$this->_Redis){
			return false;
		}
		return $this->_Redis->lLen($key);
	}
	
	/**
	 * 获取list的某段序列
	 * 
	 * @param string $key
	 * @param number $start
	 * @param number $end
	 * @return array
	 */
	public function lrange($key, $start, $len) {
		if(!$this->getSlave()){
			return false;
		}
	    $data = $this->getSlave()->lrange($key, $start, $len-1);
	    if(!$data){
	    	return false;
	    }
	    foreach ($data as $key => $val) {
	        $data[$key] = json_decode($val);
	    }
	    return $data;
	}
	
	/**
	 * list按value删除
	 * 
	 * @param string $key
	 * @param string $value
	 * @param int $count //删除等于value的count个元素， >0 从表头开始 <0 从表尾开始，-0，删除全部,遍历列表
	 */
	public function lrem($key ,$value, $count=0){
		if(!$this->_Redis){
			return false;
		}
		return $this->_Redis->lrem($key, $value, $count);
	}
	
	/**
	 * 修剪列表，只保留只能区间的元素，可以利用这个功能做list清空操作
	 * 
	 * @param string $key
	 * @param int $start
	 * @param int $stop
	 */
	public function ltrim($key, $start, $stop){
		if(!$this->_Redis){
			return false;
		}
		return $this->_Redis->ltrim($key, $start, $stop);
	}
	
	public function range($key, $start, $len) {
		if(!$this->getSlave()){
			return false;
		}
	    $data = $this->getSlave()->lrange($key, $start, $len-1);
	    if(!$data){
	    	return false;
	    }
	    foreach ($data as $key => $val) {
	        $data[$key] = $val;
	    }
	    return $data;
	}
	
	/**
	 * 添加或更新有序集合元素的值
	 * 
	 * @param string $key
	 * @param int $score
	 * @param string $member
	 */
	public function zadd($key, $score, $member) {
		if(!$this->_Redis){
			return false;
		}
		return $this->_Redis->zadd($key, $score, $member);
	}
	
	/**
	 * 获取zset的某段序列
	 *
	 * @param string $key
	 * @param number $start
	 * @param number $end
	 * @return array
	 */
	public function zrange($key, $start, $len) {
		if(!$this->getSlave()){
			return false;
		}
		$data = $this->getSlave()->zrange($key, $start, $len-1);
		if(!$data){
			return false;
		}
		foreach ($data as $key => $val) {
			$data[$key] = json_decode($val);
		}
		return $data;
	}
	
	/**
	 * 获取zset的某段序列
	 *
	 * @param string $key
	 * @param number $start
	 * @param number $end
	 * @return array
	 */
	public function zranges($key, $start, $len, $withscores=false) {
		if(!$this->getSlave()){
			return false;
		}
		$data = $this->getSlave()->zrange($key, $start, $len, $withscores);
		return $data;
	}
	
	/**
	 * 获取指定模式的所有key
	 * 
	 * @param unknown $pattern_key
	 */
	public function keys($pattern_key) {
		if(!$this->getSlave()){
			return false;
		}
	    return $this->getSlave()->keys( $pattern_key );
	    
	}
	
	/**
	 * 从缓存中读取数据
	 *
	 * @param string $key 键名
	 * @return mixed 缓存的值，如果没有则返回false
	 */
	public function get($key) {
		if(!$this->getSlave()){
			return false;
		}
		$value = $this->getSlave()->get($key);
		if (ctype_digit($value)) {
			$value = (int)$value;
		}
		return $value;
	}
	
	/**
	 * 从zset结构中获取一个key的member成员对应的score
	 * 
	 * @param string $key
	 * @param string $member
	 */
	public function zscore($key, $member){
		if(!$this->getSlave()){
			return false;
		}
		return $this->getSlave()->zScore($key, $member);
	}
	
	/**
	 * 获取zset结构中一个key的成员总数
	 * 
	 * @param string $key
	 */
	public function zcard($key){
		if(!$this->getSlave()){
			return false;
		}
		$count = $this->getSlave()->zCard($key);
		return $count ? $count : 0;
	}
	
	/**
	 * 在zset结构中按score值范围删除成员
	 * 
	 * @param string $key
	 * @param float $min
	 * @param float $max
	 */
	public function zRemRangeByScore($key, $min, $max){
		if(!$this->_Redis){
			return false;
		}
		return $this->_Redis->zRemRangeByScore($key, $min, $max);
	}
	
	/**
	 * 删除指定区间内的所有成员
	 *
	 * @param string $key
	 * @param int $start
	 * @param int $stop
	 */
	public function zRemRangeByRank($key, $start, $stop){
		if(!$this->_Redis){
			return false;
		}
		return $this->_Redis->zRemRangeByRank($key, $start, $stop);
	}
	
	public function zRem($key, $member){
		if(!$this->_Redis){
			return false;
		}
		return $this->_Redis->zRem($key, $member);
	}
	
	public function zIncrBy($key, $increment, $member){
		if(!$this->_Redis){
			return false;
		}
		return $this->_Redis->zIncrBy($key, $increment, $member);
	}
	
	/**
	 * 验证一个hash结构 key 或 域是否存在
	 * 
	 * @param string $key
	 * @param string $field
	 * @return int 存在返回1  不存在返回0
	 */
	public function hexists($key, $field){
		if(!$this->getSlave()){
			return false;
		}
		return $this->getSlave()->hExists($key, $field);
	}
	/**
	 * 在hash结构中设置一个key的域名称及值
	 * 
	 * @param string $key
	 * @param string $field
	 * @param string $value
	 */
	public function hset($key, $field, $value){
		if(!$this->_Redis){
			return false;
		}
		return $this->_Redis->hSet($key, $field, $value);
	}
	
	/**
	 * 获取一个hash结构中域名的值
	 * 
	 * @param string $key
	 * @param string $field
	 */
	public function hget($key, $field){
		if(!$this->getSlave()){
			return false;
		}
		return $this->getSlave()->hGet($key, $field);	
	}
	/**
	 * 在hash结构中设置一个key的域名称及多个值
	 *
	 * @param string $key
	 * @param array  $array
	 */
	public function hmset($key, $array){
		if(!$this->_Redis){
			return false;
		}
		return $this->_Redis->hMset($key, $array);
	}
	/**
	 * 获取一个hash结构中域名的值或者模糊获取多个key
	 *
	 * @param string $key
	 */
	public function hgetall($key){
		if(!$this->getSlave()){
			return false;
		}
		return $this->getSlave()->hGetAll($key);
	}
	/**
	 * 为一个hash结构中一个key的域值加上增量increment
	 * 
	 * @param string $key
	 * @param string $field
	 * @param string $increment
	 */
	public function hincrby($key, $field, $increment){
		if(!$this->_Redis){
			return false;
		}
		return $this->_Redis->hIncrBy($key, $field, $increment);
	}
	
	/**
	 * 为一个hash结构中一个key的域值加上增量increment,针对float
	 * 
	 * @param string $key
	 * @param string $field
	 * @param string $increment
	 */
	public function hIncrByFloat($key, $field, $increment){
		if(!$this->_Redis){
			return false;
		}
		return $this->_Redis->hIncrByFloat($key, $field, $increment);
	}
	
	/**
	 * 
	 * 获取hash结构中一个key域的数量
	 * @param string $key
	 */
	public function hlen($key){
		if(!$this->_Redis){
			return false;
		}
		return $this->_Redis->hLen($key);
	}
	
	/**
	 * 判断一个key是否存在
	 * 
	 * @param string $key
	 */
	public function exists($key){
		if(!$this->_Redis){
			return false;
		}
		return $this->_Redis->exists($key);
	}
	
	/**
	 * 为key设置过期时间
	 *
	 * @param string $key
	 * @param int $timestamp 未来时间点的时间戳
	 */
	public function expireAt($key, $timestamp){
		if(!$this->_Redis){
			return false;
		}
		return $this->_Redis->expireAt($key, $timestamp);
	}
	
	/**
	 * 为key设置过期时间
	 *
	 * @param string $key
	 * @param string $time
	 */
	public function expire($key, $time){
		if(!$this->_Redis){
			return false;
		}
		return $this->_Redis->expire($key, $time);
	}
	
	/**
	 * 距离过期的时间
	 * 
	 * @param string $key
	 */
	public function ttl($key){
		if(!$this->getSlave()){
			return false;
		}
		return $this->getSlave()->ttl($key);
	}
	//20141212-----------------------------xujia@imooc.com--------------------start
	/**
	 * 集合添加
	 * @param string $key
	 * @param string $value
	 */
	public function sadd($key, $value){
		if(!$this->_Redis){
			return false;
		}
		return $this->_Redis->sAdd($key, $value);
	}
	/**
	 * hash 删除域中field
	 * @param $key 删除的域
	 * @param $field 删除的字段
	 */
	public function hdel($key,$field){
		if(!$this->_Redis){
			return false;
		}
		return $this->_Redis->hDel($key, $field);
	}
	/**
	 * 有序集合 获取按照分数 从大到小
	 */
	public function zrevrange($key, $start, $len, $withscores=false){
		if(!$this->_Redis){
			return false;
		}
		return $this->_Redis->zRevRange($key, $start, $len, $withscores);
	}
	
	public function zRevRank($key, $member){
		if(!$this->_Redis){
			return false;
		}
		return $this->_Redis->zRevRank($key, $member);
	}
	
	public function zRank($key, $member){
		if(!$this->_Redis){
			return false;
		}
		return $this->_Redis->zRank($key, $member);
	}
	
}
