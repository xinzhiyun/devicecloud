<?php
namespace Api\Controller;

use Common\Tool\GatewayClient;

class DeviceController extends AppframeController
{

    // 页面控制 socket绑定
    public function bind()
    {
        try {
            $post = I('post.');
            if (empty($post['client_id']) || empty($post['deviceID']) ) {
                E('数据不完整', 40001);
            }

            if(GatewayClient::bind($post['client_id'], $post['deviceID'])){
                E('成功',200);
            }else{
                E('成功',40010);
            }
        } catch (\Exception $e) {
            self::toJson($e);
        }
    }
}