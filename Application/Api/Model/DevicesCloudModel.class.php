<?php
namespace Api\Model;
use Think\Model;
/**
 * 设备操作
 */
class DevicesCloudModel extends Model{

//    protected $tableName;
//    protected $tablePrefix;
    public function __construct()
    {
        //初始化数据库连接
        $this->connection   = C('SUPERVISE_DB');
        $this->tablePrefix  = '';
        $this->tableName    = 'devices';
        parent::__construct();
    }
}
