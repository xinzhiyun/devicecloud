<?php
return array(
	//'配置项'=>'配置值'
    'DB_TYPE' => 'mysql',
    'DB_USER' => 'root',
    'DB_PWD' =>  'root',
    'DB_HOST' => '127.0.0.1',
    'DB_PORT' => '3306',
    'DB_NAME' => 'devicecloud',
    'db_charset' => 'utf8',
    'DB_PREFIX' => '',    // 数据库表前缀

    //定义特定的数据连接 云端配置
    'SUPERVISE_DB' => array(
        'DB_TYPE' => 'mysql',
        'DB_USER' => 'root',
        'DB_PWD' =>  'root',
        'DB_HOST' => '127.0.0.1',
        'DB_PORT' => '3306',
        'db_name' => 'devicecloud',
        'db_charset' => 'utf8',
        'DB_PREFIX' => '',    // 数据库表前缀
    ),
);