<?php
namespace Api\Controller;

use Common\Tool\GatewayClient;
use Think\Log;

class DeviceController extends AppframeController
{

    // 页面控制 socket绑定
    public function bind()
    {
        try {
            $post = I('get.');
            if (empty($post['client_id']) || empty($post['deviceID']) ) {
                E('数据不完整', 40001);
            }

            if(!empty($_REQUEST['jsoncallback'])){
                $jsoncallback = htmlspecialchars($_REQUEST['jsoncallback']);
            }

            if(GatewayClient::bind($post['client_id'], $post['deviceID'])){
                GatewayClient::action(99, $post['deviceID']);
                E('成功',200);
            }else{
                E('失败',40010);
            }
        } catch (\Exception $e) {
            self::toJson($e,$jsoncallback);
        }
    }

    /**
     * web端 设备操作
     *   *  参数 mode
     *   1   //开机
     *   2   //关机
     *   3   //冲洗
     *   4   //取消冲洗
     *   5   //复位滤芯  "Pram":[1,2] 滤芯级数
     *   6   //绑定设备
     *   7   //解绑设备
     *   8   //充值100天  "Pram":{"mode":2,”val”:100}
     *   9   //充值100L   "Pram":{"mode":1,”val”:100}}
     *   10  //租赁模式修改  'Pram' 0 买断模式  1 流量 2 时长 3 时长和流量
     *   11  //滤芯模式修改  'Pram' 0 时长 1 流量 2 时长和流量
     */
    public function deviceAction()
    {
        try {
            $post = I('get.');
            if (empty($post['mode']) || empty($post['deviceID']) ) {
                E('数据不完整', 40001);
            }

            if($post['mode']>5){
                E('数据错误', 40002);
            }

            if(!empty($post['data'])){
                $data = explode('_',$post['data']);
            }

            $jsoncallback = htmlspecialchars($_REQUEST['jsoncallback']);

            $res = GatewayClient::action($post['mode'], $post['deviceID'], $data);
            Log::write($res,'设备返回');
            //$res = self::action(['mode'=>$post['mode'], 'deviceID'=>$post['deviceID'], 'data'=>$data]);
            if(empty($res)){
                E('设备操作失败!',40010);
            }else{
                E('成功',200);
            }

        } catch (\Exception $e) {
            self::toJson($e,$jsoncallback);
        }
    }

    /**
     * 服务器端操作
     */
    public function serviceDeviceAction()
    {
        try {
            $post = I('post.');
            // 验证服务器的白名单


            if (empty($post['mode']) || empty($post['deviceID']) ) {
                E('数据不完整', 40001);
            }

            $mode = (string)$post['mode'];
            switch ($mode){
                case '5':
                    if(!empty($post['data'])){
                        $data = explode('_',$post['data']);
                    }
                    break;
                case '8':
                    $data = $post['data'];
                    break;
                case '9':
                    $data = $post['data'];
                    break;
                case '10':
                    $data = $post['data'];
                    break;
                case '11':
                    $data = $post['data'];
                    break;
            }

            $res = GatewayClient::action($mode, $post['deviceID'], $data);
            if($res){
                E('成功',200);
            }else{
                E('失败',40010);
            }
        } catch (\Exception $e) {
            self::toJson($e);
        }
    }

    /**
     * @param $post
     *  参数 mode
     *   1   //开机
     *   2   //关机
     *   3   //冲洗
     *   4   //取消冲洗
     *   5   //复位滤芯  "Pram":[1,2] 滤芯级数
     *   6   //绑定设备
     *   7   //解绑设备
     *   8   //充值100天  "Pram":{"mode":2,”val”:100}
     *   9   //充值100L   "Pram":{"mode":1,”val”:100}}
     *   10  //租赁模式修改  'Pram' 0 买断模式  1 流量 2 时长 3 时长和流量
     *   11  //滤芯模式修改  'Pram' 0 时长 1 流量 2 时长和流量
     *
     */
    public static function action($post=[])
    {
//        $mode = (string)$post['mode'];
//        switch ($mode){
//            case '5':
//                $data = $post['data'];
//                break;
//            case '8':
//                $data = $post['data'];
//                break;
//            case '9':
//                $data = $post['data'];
//                break;
//            case '10':
//                $data = $post['data'];
//                break;
//            case '11':
//                $data = $post['data'];
//                break;
//        }

        $data = $post['data'];

        // 发送
        return GatewayClient::action($post['mode'], $post['deviceID'], $data);
    }





    // -------------------------------------------------------------------------------------------------
    // ------------------------------------------APP 设备的操作------------------------------------------
    // -------------------------------------------------------------------------------------------------

    // 获取登陆用户的产品型号 和 经销商
    public function getDeviceDataList()
    {

        $data['typelist'] = M('DeviceType')->field('id type_id,typename')->select();

        $data['vendorslist'] = M('Vendors')->field('id vid,name')->select();

        self::toJson($data,'获取成功',200);
    }


    // 设备入库及更新
    public function pullDevice()
    {
        try {
            $post = I('post.');

            if (empty($post['deviceID']) ) {
                E('数据不完整', 40001);
            }
            $post['deviceID'] = trim($post['deviceID']);

            if(empty($_SESSION['acid'])){
                E('请登录!', 40003);
            }
            Log::write(json_encode($post),'设备入库');

            // 主库
            $DevicesCloud = D('DevicesCloud');

            $device_could_info = $DevicesCloud->where("deviceid='{$post['deviceID']}'")->find();
            if(empty($device_could_info )){
                $res = $DevicesCloud->add( ['deviceid'=>$post['deviceID'], 'addtime'=>time(), 'acid'=>$_SESSION['acid']] );
                if(empty($res)) E('入库失败!',40004);
            }else{
                $res = $DevicesCloud->where('id='.$device_could_info['id'])->save( [ 'addtime'=>time(), 'acid'=>$_SESSION['acid']] );
                if(empty($res)) E('入库更新失败!',40005);
            }

            // 客户库
            $device_model = M('devices',$_SESSION['DB_CONFIG']['DB_PREFIX']);
            $device_info = $device_model->where("device_code='{$post['deviceID']}'")->find();

            if( !empty($device_info) ){
                if(!empty($post['type_id'])){
                    $saveData['type_id'] = $post['type_id'];
                }
                if(!empty($post['vid'])){
                    if( $this->bindDevice($device_info['id'], $post['vid']) ){
                        $saveData['binding_statu'] = 1;
                    }
                }
                $saveData['addtime'] = time();

                $res =  $device_model->where('id='.$device_info['id'])->save($saveData);

            } else {
                $addData['device_code'] = $post['deviceID'];
                $addData['addtime']     = time();

                if(!empty($post['type_id'])){
                    $addData['type_id'] = $post['type_id'];
                }
                $res = $device_model->add($addData);

                if(!empty($post['vid'])){
                    if( $this->bindDevice($res, $post['vid']) ){
                        $saveData['binding_statu'] = 1;
                        $device_model->where('id='.$res)->save($saveData);
                    }
                }
            }

            if($res){
                E('成功',200);
            }else{
                E('失败',40010);
            }
        } catch (\Exception $e) {
            toJson($e);
        }
    }

    // 绑定设备或更新绑定信息
    public function bindDevice($did, $vid)
    {
        $binding_model = M('binding');
        $device_bind = $binding_model->where('did='.$did)->find();
        $time = time();
        if(empty($device_bind)){
            return $binding_model->add(['did'=>$did, 'vid'=>$vid, 'addtime'=>$time]);
        }else{
            return $binding_model->where('did='.$did)->save(['vid'=>$vid, 'addtime'=>$time]);
        }
    }

}