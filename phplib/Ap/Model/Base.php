<?php 

class Ap_Model_Base 
{

    protected $idkey = '_id'; # id 字段
    protected $table = '';    # 表名

    public $mongoClient; # 公开的mongoClient对象
    public $Collection;  # 公开的Collection对象

    public function __construct () 
    {
        $this->mongoClient = new Ap_DB_MongoDB();
        $this->Collection  = $this->mongoClient->getCollection($this->table);
    }

    # 插入一条数据
    public function insert ($data) 
    {
        $this->Collection->save($data);
    }

    # 根据条件获取一条数据
    public function getOne ($where) 
    {
        return $this->Collection->findOne($where);
    }

    # 根据主键获取一条数据
    public function getOneById ($id) 
    {
        return $this->Collection->findOne(array(
            $this->idkey => $this->getIdObj($id)
        ));
    }

    # 获取筛选条件的总量
    public function getCount ($where) 
    {
        return $this->Collection->find($where)->count();
    }

    # 获取多条数据
    public function getMany ($where, $limit = 0, $skip = 0, $order = array()) 
    {
        $list = $this->Collection->find($where);

        return iterator_to_array($list);
    }

    # 删除一条数据
    public function delete ($id) 
    {
        // return 
    }

    # 更新一条数据
    public function update ($id, $data) 
    {
        // 
    }

    # 获取Id，MongoId对象的Id
    public function getIdObj ($id) 
    {
        if ($id instanceof MongoId) return $id;

        if (is_string($id)) return new MongoId($id);

        return FALSE;
    }
}