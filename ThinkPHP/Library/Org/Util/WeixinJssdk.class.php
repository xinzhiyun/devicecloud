<?php
namespace Org\Util;

class WeixinJssdk
{
  public $appId = 'wx57d57fb99d6d838d';

  public $appSecret = 'ec36152955830ec4191507724f3377a6';

  public function __construct($appId, $appSecret) {
    $this->appId = $appId;
    $this->appSecret = $appSecret;
  }

  public function getSignPackage() {
    $jsapiTicket = $this->getJsApiTicket();

    // 注意 URL 一定要动态获取，不能 hardcode.
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

    $timestamp = time();
    $nonceStr = $this->createNonceStr();

    // 这里参数的顺序要按照 key 值 ASCII 码升序排序
    $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

    $signature = sha1($string);

    $signPackage = array(
      "appId"     => $this->appId,
      "nonceStr"  => $nonceStr,
      "timestamp" => $timestamp,
      "url"       => $url,
      "signature" => $signature,
      "rawString" => $string
    );
    return $signPackage; 
  }

  public function createNonceStr($length = 16) {
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $str = "";
    for ($i = 0; $i < $length; $i++) {
      $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
    }
    return $str;
  }

  public function getJsApiTicket() {
    // jsapi_ticket 应该全局存储与更新，以下代码以写入到文件中做示例
    $data = json_decode($this->get_php_file($this->appId."_jsapi_ticket.php"));
    if ($data->expire_time < time()) {
      $accessToken = $this->getAccessToken();
      // 如果是企业号用以下 URL 获取 ticket
      // $url = "https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket?access_token=$accessToken";
      $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
      $res = json_decode($this->httpGet($url));
      $ticket = $res->ticket;
      if ($ticket) {
        $data->expire_time = time() + 7000;
        $data->jsapi_ticket = $ticket;
        $this->set_php_file($this->appId."_jsapi_ticket.php", json_encode($data));
      }
    } else {
      $ticket = $data->jsapi_ticket;
    }

    return $ticket;
  }

  public function getAccessToken() {

    // access_token 应该全局存储与更新，以下代码以写入到文件中做示例
    $data = json_decode($this->get_php_file($this->appId."_access_token.php"));
//    dump($this->appId);exit;
    if ($data->expire_time < time()) {
      // 如果是企业号用以下URL获取access_token
      // $url = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid=$this->appId&corpsecret=$this->appSecret";
      $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appId&secret=$this->appSecret";
      $res = json_decode($this->httpGet($url));
      $access_token = $res->access_token;
      if ($access_token) {
        $data->expire_time = time() + 7000;
        $data->access_token = $access_token;
        $this->set_php_file($this->appId."_access_token.php", json_encode($data));
      }
    } else {
      $access_token = $data->access_token;
    }
    return $access_token;
  }

  public function httpGet($url) {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_TIMEOUT, 500);
    // 为保证第三方服务器与微信服务器之间数据传输的安全性，所有微信接口采用https方式调用，必须使用下面2行代码打开ssl安全校验。
    // 如果在部署过程中代码在此处验证失败，请到 http://curl.haxx.se/ca/cacert.pem 下载新的证书判别文件。
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, true);
    curl_setopt($curl, CURLOPT_URL, $url);

    $res = curl_exec($curl);
    curl_close($curl);

    return $res;
  }

  public function get_php_file($filename) {
    return trim(substr(file_get_contents(LIB_PATH.'Org/Util/weixin/'.$filename), 15));
  }

  public function set_php_file($filename, $content) {
    $fp = fopen(LIB_PATH.'Org/Util/weixin/'.$filename, "w");
    fwrite($fp, "<?php exit();?>" . $content);
    fclose($fp);
  }


    // 微信支付部分
  // 1: 获取用户openID: 在关注者与公众号产生消息交互后,公众号可获得关注者的OpenID,对于不同公众号,同一用户的openid不同id
  /**
   * 
   * 通过跳转获取用户的openid，跳转流程如下：
   * 1、设置自己需要调回的url及其其他参数，跳转到微信服务器https://open.weixin.qq.com/connect/oauth2/authorize
   * 2、微信服务处理完成之后会跳转回用户redirect_uri地址，此时会带上一些参数，如：code
   * 
   * @return 用户的openid
   */
  public function GetOpenid()
  {
    //通过code获得openid
    if (!isset($_GET['code'])){
      //触发微信返回code码
      $baseUrl = urlencode('http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].$_SERVER['QUERY_STRING']);
      $url = $this->__CreateOauthUrlForCode($baseUrl);
      Header("Location: $url");
      exit();
    } else {
      //获取code码，以获取openid
      $code = $_GET['code'];
      $openid = $this->getOpenidFromMp($code);
      return $openid;
    }
  }

  // 辅助功能

  /**
   * 
   * 构造获取code的url连接
   * @param string $redirectUrl 微信服务器回跳的url，需要url编码
   * 
   * @return 返回构造好的url
   */
  public function __CreateOauthUrlForCode($redirectUrl)
  {
    $urlObj["appid"] = $this->appId;
    $urlObj["redirect_uri"] = "$redirectUrl";
    $urlObj["response_type"] = "code";
    $urlObj["scope"] = "snsapi_base";
    $urlObj["state"] = "STATE"."#wechat_redirect";
    $bizString = $this->ToUrlParams($urlObj);
    return "https://open.weixin.qq.com/connect/oauth2/authorize?".$bizString;
  }

  /**
   * 
   * 拼接签名字符串
   * @param array $urlObj
   * 
   * @return 返回已经拼接好的字符串
   */
  public function ToUrlParams($urlObj)
  {
    $buff = "";
    foreach ($urlObj as $k => $v)
    {
      if($k != "sign"){
        $buff .= $k . "=" . $v . "&";
      }
    }
    
    $buff = trim($buff, "&");
    return $buff;
  }

  /**
   * 
   * 通过code从工作平台获取openid机器access_token
   * @param string $code 微信跳转回来带上的code
   * 
   * @return openid
   */
  public function GetOpenidFromMp($code)
  {
    $url = $this->__CreateOauthUrlForOpenid($code);
    // 初始化curl
    $ch = curl_init();
    // 设置超时
    curl_setopt($ch, CURLOPT_TIMEOUT, $this->curl_timeout);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,FALSE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    if($CURL_PROXY_HOST != "0.0.0.0" && $CURL_PROXY_PORT != 0){
      curl_setopt($ch,CURLOPT_PROXY, $CURL_PROXY_HOST);
      curl_setopt($ch,CURLOPT_PROXYPORT, $CURL_PROXY_PORT);
    }
    // 运行curl，结果以jason形式返回
    $res = curl_exec($ch);
    curl_close($ch);
    //取出openid
    $data = json_decode($res,true);
    $this->data = $data;
    $openid = $data['openid'];
    return $openid;
  }

  /**
   * 
   * 构造获取open和access_toke的url地址
   * @param string $code，微信跳转带回的code
   * 
   * @return 请求的url
   */
  public function __CreateOauthUrlForOpenid($code)
  {
    $urlObj["appid"] = $this->appId;
    $urlObj["secret"] = $this->appSecret;
    $urlObj["code"] = $code;
    $urlObj["grant_type"] = "authorization_code";
    $bizString = $this->ToUrlParams($urlObj);
    return "https://api.weixin.qq.com/sns/oauth2/access_token?".$bizString;
  }

}

