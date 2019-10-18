<?php
use MongoDB\Driver\Manager;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\WriteConcern;
use MongoDB\Driver\Query;
use MongoDB\Driver\Command;

class MongoDb {

    protected $mongodb;
    protected $database;
    protected $collection;
    protected $bulk;
    protected $writeConcern;
    protected $defaultConfig
        = [
            'hostname' => 'localhost',
            'port' => '27017',
            'username' => '',
            'password' => '',
            'database' => 'test'
        ];

    public function __construct($config) {
        $config = array_merge($this->defaultConfig, $config);
        $mongoServer = "mongodb://";
        if ($config['username']) {
            $mongoServer .= $config['username'] . ':' . $config['password'] . '@';
        }
        $mongoServer .= $config['hostname'];
        if ($config['port']) {
            $mongoServer .= ':' . $config['port'];
        }
        $mongoServer .= '/' . $config['database'];

        $this->mongodb = new Manager($mongoServer);
        $this->database = $config['database'];
        $this->collection = $config['collection'];
        $this->bulk = new BulkWrite();
        $this->writeConcern = new WriteConcern(WriteConcern::MAJORITY, 100);
    }

    public function query($where = [], $option = []) {
        $query = new Query($where, $option);
        $result = $this->mongodb->executeQuery("$this->database.$this->collection", $query);

        return json_encode($result);
    }

    public function count($where = []) {
        $command = new Command(['count' => $this->collection, 'query' => $where]);
        $result = $this->mongodb->executeCommand($this->database, $command);
        $res = $result->toArray();
        $count = 0;
        if ($res) {
            $count = $res[0]->n;
        }

        return $count;
    }

    /**
     * upsert : 可选，这个参数的意思是，如果不存在update的记录，是否插入objNew,true为插入，默认是false，不插入。
     * multi : 可选，mongodb 默认是false,只更新找到的第一条记录，如果这个参数为true,就把按条件查出来多条记录全部更新。
    */
    public function update($where = [], $update = [], $upsert = false) {
        $this->bulk->update($where, ['$set' => $update], ['multi' => true, 'upsert' => $upsert]);
        $result = $this->mongodb->executeBulkWrite("$this->database.$this->collection", $this->bulk, $this->writeConcern);

        return $result->getModifiedCount();
    }

    public function insert($data = []) {
        $this->bulk->insert($data);
        $result = $this->mongodb->executeBulkWrite("$this->database.$this->collection", $this->bulk, $this->writeConcern);

        return $result->getInsertedCount();
    }
	
    /**
     * $limit 1 值替换第一条  0替换当前集合的所有符合的文档
    */
    public function delete($where = [], $limit = 1) {
        $this->bulk->delete($where, ['limit' => $limit]);
        $result = $this->mongodb->executeBulkWrite("$this->database.$this->collection", $this->bulk, $this->writeConcern);

        return $result->getDeletedCount();
    }
}