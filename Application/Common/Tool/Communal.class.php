<?php
/**
 * Created by PhpStorm.
 * User: 李振东
 * Time: 2018/3/29 下午2:37
 */
namespace Common\Tool;

use Think\Log;
class Communal
{
    /**
     * 设置数据库
     */
    public static function setDB($conf)
    {
        $_SESSION['DB_CONFIG']['DB_PREFIX'] = $conf['db_prefix'];
        $_SESSION['DB_CONFIG']['DB_USER']   = $conf['db_user'];
        $_SESSION['DB_CONFIG']['DB_PWD']    = $conf['db_password'];
        $_SESSION['DB_CONFIG']['DB_HOST']   = $conf['db_host'];
        $_SESSION['DB_CONFIG']['DB_PORT']   = $conf['db_port'];
        $_SESSION['DB_CONFIG']['DB_NAME']   = $conf['db_name'];
    }

}