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
        // var_dump($this->mongoClient);
        $this->Collection  = $this->mongoClient->getCollection($this->table);
    }

    public function insert ($data) 
    {
        $this->Collection->save($data);
    }

    public function getOne ($where) 
    {
        return $this->Collection->findOne($where);
    }

    public function getOneById ($id) 
    {
        return $this->Collection->findOne(array(
            $this->idkey => $this->getIdObj($id)
        ));
    }

    public function getCount ($where) 
    {
        return $this->Collection->find($where)->count();
    }

    public function getMany ($where, $limit = 0, $skip = 0, $order = array()) 
    {
        $list = $this->Collection->find($where);

        return iterator_to_array($list);
    }

    public function delete ($id) 
    {
        // return 
    }

    public function update ($id, $data) 
    {
        // 
    }

    public function getIdObj ($id) 
    {
        if ($id instanceof MongoId) return $id;

        if (is_string($id)) return new MongoId($id);

        return FALSE;
    }
}