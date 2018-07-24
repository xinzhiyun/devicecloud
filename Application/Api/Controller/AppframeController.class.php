<?php
namespace Api\Controller;
use Think\Controller;

class AppframeController extends Controller {

    /**
     * 接口返回
     * @param $e
     * @param string $msg
     * @param int $status
     */
    public function toJson($e,$msg='',$status=200,$jsoncallback='')
    {
        if(is_array($e)){
            $data=array_merge($e,['status'=>$status,'msg'=>$msg]);
        }elseif(is_object($e)){
            if(!empty($msg)){ // jsonp
                $jsoncallback = $msg;
            }
            $data = [
                'status' => $e->getCode(),
                'msg' =>   $e->getMessage(),
            ];
        }else{
            $data = $e;
        }


        $data['state']   = (!empty($data['status']) and $data['status']== 200) ? "success" : "fail";

        header('Content-Type:application/json; charset=utf-8');

        if(empty($jsoncallback)){
            exit(json_encode($data));
        }else{
            exit($jsoncallback."(".json_encode($data).")");
        }
    }

    /**
     * 检查操作频率
     * @param int $duration 距离最后一次操作的时长
     */
    protected function check_last_action($duration){
    	
    	$action=MODULE_NAME."-".CONTROLLER_NAME."-".ACTION_NAME;
    	$time=time();
    	
    	$session_last_action=session('last_action');
    	if(!empty($session_last_action['action']) && $action==$session_last_action['action']){
    		$mduration=$time-$session_last_action['time'];
    		if($duration>$mduration){
    			$this->error("您的操作太过频繁，请稍后再试~~~");
    		}else{
    			session('last_action.time',$time);
    		}
    	}else{
    		session('last_action.action',$action);
    		session('last_action.time',$time);
    	}
    }
}