<?php

header("Content-type:text/html;charset=utf-8");

/**
 * 生成二维码 吴智彬
 * @param  string  $url  url连接
 * @param  integer $size 尺寸 纯数字
 */
function qrcode($url,$size=4)
{
    Vendor('Phpqrcode.phpqrcode');
    QRcode::png($url,false,QR_ECLEVEL_L,$size,2,false,0xFFFFFF,0x000000);
}

/**
 * [截取中文字符串数据长度 吴智彬]
 * @param  [type]  $str     [要截取的字符串]
 * @param  integer $start   [开始位置，默认从0开始]
 * @param  [type]  $length  [截取长度]
 * @param  string  $charset [字符编码，默认UTF－8]
 * @param  boolean $suffix  [是否在截取后的字符后面显示省略号，true显示，默认false截取显示，不截取不显示]
 * @return [type]           [截取后的字符串]
 *
 * 模版使用：{{$vo.title|msubstr=0,5,'utf-8',true}}
 */
function msubstr($str, $start=0, $length, $charset="utf-8", $suffix=false)  
{  
  if(function_exists("mb_substr")){  
    if($suffix)  
        return mb_substr($str, $start, $length, $charset)."...";  
      elseif(strlen($str) > $length)
        return mb_substr($str, $start, $length, $charset)."...";  
      else
        return mb_substr($str, $start, $length, $charset);  
  }elseif(function_exists('iconv_substr')) {  
    if($suffix)  
      return iconv_substr($str,$start,$length,$charset)."...";  
    elseif(strlen($str) > $length)
      return iconv_substr($str,$start,$length,$charset)."...";  
    else
      return iconv_substr($str,$start,$length,$charset);  
  }  
  $re['utf-8'] = "/[x01-x7f]|[xc2-xdf][x80-xbf]|[xe0-xef][x80-xbf]{2}|[xf0-xff][x80-xbf]{3}/";  
  $re['gb2312'] = "/[x01-x7f]|[xb0-xf7][xa0-xfe]/";  
  $re['gbk']    = "/[x01-x7f]|[x81-xfe][x40-xfe]/";  
  $re['big5']   = "/[x01-x7f]|[x81-xfe]([x40-x7e]|xa1-xfe])/";  
  preg_match_all($re[$charset], $str, $match);  
  $slice = join("",array_slice($match[0], $start, $length));  
  if($suffix) return $slice."…";
  if(strlen($str) > $length) return $slice."…";
  return $slice;
}

/**
 * [show 打印数据的函数]
 * @param  [type] $data [所有类型数据]
 * @return [type]       [木有]
 */
function show($data)
{
  if(is_null($data)){
    echo 'is null';
  }elseif(is_scalar($data)){
    echo $data;
  }elseif(is_array($data) || is_object($data)){
    echo '<pre>';
      print_r($data);
    echo '</pre>';
  }else{
    dump($data);
  }
  exit;
}



/**
 * @param $data
 * @param array $replace
 * @param string $suffix
 * @return mixed
 *
 * 传入说明:
 * $arr = [
        'is_pay'=>['0'=>'未付款','1'=>'已付款','2'=>'已取消'],
        'is_receipt'=>['0'=>'未发货','1'=>'已发货'],
        'is_ship'=>['0'=>'未收货','1'=>'已收货'],
        'is_recharge'=>['0'=>'未充值','1'=>'已充值'],
        'created_at'=>['date','Y-m-d H:i:s'],
        'total_price'=>['price']
        ];
 *  格式 :
 *  (1) 字段下标=> 状态替换的对应文字
 *  (2) 字段下标=> ['date','Y-m-d H:i:s']   使用函数date进行'Y-m-d H:i:s'处理
 *  (3) 字段下标=> ['str','123']   使用函数 在原数据上拼接 '123'
 *  执行函数 传入的格式 原值在参数集合最后进行执行 call_user_func(...$val)
 *
 */
function replace_array_value($data, array $replace, $suffix="")
{
    $arr=['replace'=>$replace, 'suffix'=>$suffix];
    array_walk($data,function(&$v,$k,$arr){
        extract($arr);
        $fun=['date','str','price'];
        foreach ($replace as $key=> $val) {
            if(array_key_exists($key,$v)){
                if($v[$key]=== null || $v[$key] === ''){
                    $v[$key.$suffix]=$val['null']?:'';
                }else{
                    if(in_array($val[0],$fun)){
                        switch ($val[0]) {
                            case 'str':
                                $v[$key.$suffix] = $v[$key].$val[1];
                                break;
                            case 'price':
                                $v[$key.$suffix] = number_format(intval(trim($v[$key]), 10)/100,2);
                                break;
                            default:
                                $val[]=$v[$key];
                                $v[$key.$suffix] = call_user_func(...$val);
                                break;
                        }
                    }else{
                        $v[$key.$suffix]=$val[$v[$key]];
                    }
                }
            }
        }
    },$arr);
    return $data;
}



/**
 * 将xml转为array
 * @param  string $xml xml字符串
 * @return array       转换得到的数组
 */
function xmltoArray($xml){
    //禁止引用外部xml实体
    libxml_disable_entity_loader(true);
    $result= json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    return $result;
}


function httpPost($url,$data = null){
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
    if (!empty($data)){
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    }
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($curl);
    curl_close($curl);
    return $output;
}

/**
 * 接口返回
 * @param $e
 * @param string $msg
 * @param int $status
 */
function toJson($e,$msg='',$status=200,$jsoncallback='')
{
    if(is_array($e)){
        $data=array_merge($e,['status'=>$status,'msg'=>$msg]);
    }else{
        if(!empty($msg)){ // jsonp
            $jsoncallback = $msg;
        }
        $data = [
            'status' => $e->getCode(),
            'msg' =>   $e->getMessage(),
        ];
    }

    $data['state']   = (!empty($data['status']) and $data['status']== 200) ? "success" : "fail";

    header('Content-Type:application/json; charset=utf-8');

    if(empty($jsoncallback)){
        exit(json_encode($data));
    }else{
        exit($jsoncallback."(".json_encode($data).")");
    }
}

















