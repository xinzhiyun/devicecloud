<?php
namespace Api\Controller;

use Common\Tool\GatewayClient;
use Think\Log;

class DeviceController extends AppframeController
{
    private static $devices_model;
    public function __construct()
    {
        self::$devices_model = M('devices');
        parent::__construct();
    }

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
                GatewayClient::action(99, $post['deviceID']);//开启设备刷新
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
            $data = [];
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
            
            // 验证设备是否属于该客户

            if (empty($post['mode']) || empty($post['deviceID']) ) {
                E('数据不完整', 40001);
            }

            $mode = (string)$post['mode'];
            if(empty($post['data'])){
                $post['data']='';
            }

            $res = GatewayClient::action($mode, $post['deviceID'], $post['data']);
            if($res){
                E('成功',200);
            }else{
                E('失败',40010);
            }
        } catch (\Exception $e) {
            self::toJson($e);
        }
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


    // -------------------------------------------------------------------------------------------------
    // ------------------------------------------设备云 云端接口------------------------------------------
    // -------------------------------------------------------------------------------------------------

    // 检查数据来源
    private static function checkIP(){


    }

    // 更新的字段
    private static $statuField = [
        // 小写         => 数据库字段
        'rawtds'        => 'RawTDS',
        'puretds'       => 'PureTDS',
    ];

    /**
     * 更新devicecloud表devices(设备云API)
     */
    public function updataDevicesStatu()
    {
        self::checkIP();
        try {
            $post = I('post.');
            if (empty($post['deviceID']) || empty($post['data'])) {
                E('数据不完整', 40001);
            } else {
                $map['deviceid'] = $post['deviceID'];
            }

            $DeviceStatuData = self::getJsonArray($post['data']);

            $newDeviceStatuData = [];
            // 过滤数据
            foreach ($DeviceStatuData as $key=>$val){
                $key = strtolower($key);
                if(isset(self::$statuField[$key])){
                    $newDeviceStatuData[ self::$statuField[$key] ] = $val;
                }
            }

            if( empty(self::getDeviceStatu($map)) ){
                $newDeviceStatuData['DeviceID'] = $post['deviceID'];
                $res = self::addDeviceStatu($newDeviceStatuData);
            }else{
                $res = self::saveDeviceStatu($map, $newDeviceStatuData);
            }

            if($res){
                E('更新成功!',200);
            }else{
                E('更新失败!',40010);
            }
        } catch (\Exception $e) {
            $this->toJson($e);
        }
    }

    /**
     * 获取devicecloud表devices(设备云API)
     */
    public function getDeviceStatus()
    {
        try {
            $post = I('post.');
            if ( empty($post['deviceID']) ) {
                E('数据不完整', 201);
            } else {
                $map['deviceid'] = $post['deviceID'];
            }

            $fields = self::getJsonArray($post['fields']);
            $newDeviceStatuData = [];

            $field = [];
            // 过滤数据
            foreach ($fields as $val){
                $key = strtolower($val);
                if(isset(self::$statuField[$key])){
                    $field[] = self::$statuField[$key];
                }
            }
            $field  = implode(',',$field);

            $data = self::$devices_model->where($map)->field($field)->find();

            if(empty($data)){ // 为空创建数据

                $newDeviceStatuData = [
                    'deviceid'  => $post['deviceID'],
                ];
                $res = self::addDeviceStatu($newDeviceStatuData);
                $this->toJson($res,'创建成功',201);
            }

            foreach ($data as $key=>$val){
                $key = strtolower($key);
                if(isset(self::$statuField[$key])){
                    $resData[ self::$statuField[$key] ] = $val;
                }
            }

            $this->toJson($resData,'获取成功');

        } catch (\Exception $e) {
            $this->toJson($e);
        }
    }

    /**
     * 查询devicecloud的设备信息
     */
    public function getDevicecloudDevice()
    {
        try {
            $post = I('get.');
            if ( empty($post['deviceID']) ) {
                E('数据不完整', 201);
            } else {
                $map['deviceid'] = $post['deviceID'];
            }


            $data = self::$devices_model->where($map)->find();

            if(!empty($data['acid'])){
                $account = M('account')->where("id='{$data['acid']}'")->find();
                if(empty($account['domain'])){
                    E('该设备未绑定客户未设置参数',40003);
                }else{
                    $this->toJson(['domain'=>$account['domain'], 'type_id'=>1],'获取成功!',200);
                }
            }else{
                E('该设备未绑定客户!',40002);
            }

        } catch (\Exception $e) {
            $this->toJson($e);
        }
    }

    // 解析json 转数据
    public static function getJsonArray($data)
    {
        $json = htmlspecialchars_decode($data);
        return json_decode($json,true);
    }
    /**
     * 添加设备信息
     */
    private static function addDeviceStatu( $data )
    {
        $data['addtime'] = time();
        return self::$devices_model->add($data );
    }
    /**
     * 修改设备状态信息
     */
    private static function saveDeviceStatu($map, $data )
    {
        if(empty($map)){
            return false;
        }
        $data['updatetime'] = time();
        return self::$devices_model->where($map)->save($data );
    }
    /**
     * 获取设备状态的信息
     */
    private static function getDeviceStatu($map)
    {
        return  self::$devices_model->where($map )->find();
    }
}