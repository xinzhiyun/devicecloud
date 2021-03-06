<?php
namespace Api\Controller;

use Common\Tool\Communal;
use Common\Tool\GatewayClient;

class LoginController extends AppframeController
{
    // 获取管理端配置
    public function index()
    {
        try {
            $post = I('post.');
            if ( empty($post['user']) ) {
                E('数据不完整', 40001);
            }
            $data = M('','',C('SUPERVISE_DB'))->table('account')->where("user='{$post['user']}'")->find();

            if(!empty($data)){
                $_SESSION['acid'] = $data['id'];
                self::toJson(['data'=>$data],'成功',200);
            }else{
                E('失败',40010);
            }
        } catch (\Exception $e) {
            self::toJson($e);
        }
    }

    // 获取管理端配置
    public function wxLogin()
    {
        try {
            $post = I('post.');
            if ( empty($post['appid']) ) {
                E('数据不完整', 40001);
            }
            $data = M('','',C('SUPERVISE_DB'))->table('account')->where("wx_appid='{$post['appid']}'")->find();

            if(!empty($data)){
                $_SESSION['acid'] = $data['id'];
                self::toJson(['data'=>$data],'成功',200);
            }else{
                E('失败',40010);
            }
        } catch (\Exception $e) {
            self::toJson($e);
        }
    }

    // APP 身份登录
    public function appLogin()
    {
        try {
            $post = I('post.');
            if (empty($post['user']) || empty($post['pwd']) ) {
                E('数据不完整', 40001);
            }
            unset($_SESSION['DB_CONFIG']);
            $data = M('','',C('SUPERVISE_DB'))->table('account')->where("user='{$post['user']}'")->find();

            if(!empty($data) && $data['password'] == md5($post['pwd']) ){
                $_SESSION['acid'] = $data['id'];
                Communal::setDB($data);
                E('成功',200);
            }else{
                E('账号或密码错误!',40010);
            }
        } catch (\Exception $e) {
            self::toJson($e);
        }
    }


}