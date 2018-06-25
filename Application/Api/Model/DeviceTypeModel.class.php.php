<?php
namespace Admin\Model;
use Think\Model;
/**
 * 设备操作
 */
class DeviceTypeModel extends Model{


    protected $tableName;
    protected $tablePrefix;

    public function __construct()
    {
        //初始化数据库连接
        $this->connection   = $_SESSION['DB_CONFIG'];
        $this->tablePrefix  = $_SESSION['DB_CONFIG']['DB_PREFIX'];
        $this->tableName    = 'device_type';
        parent::__construct();
    }

}
