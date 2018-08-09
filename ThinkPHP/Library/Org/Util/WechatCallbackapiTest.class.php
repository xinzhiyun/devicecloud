<?php
namespace Org\Util;
use Think\Exception;

class WechatCallbackapiTest
{
	public function valid()
    {
        $echoStr = $_GET["echostr"];

        if($this->checkSignature()){
        	echo $echoStr;
        	exit;
        }
    }

    public function responseMsg()
    {
		//get post data, May be due to the different environments
        //设置了register_globals禁止,不能用$GLOBALS["HTTP_RAW_POST_DATA"];
        // 2018-5-17 潘
		// $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        $postStr = file_get_contents("php://input");
      	//extract post data
		if (!empty($postStr)){
                /* libxml_disable_entity_loader is to prevent XML eXternal Entity Injection,
                   the best way is to check the validity of xml by yourself */
                libxml_disable_entity_loader(true);
              	$postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
                $fromUsername = $postObj->FromUserName;
                $toUsername = $postObj->ToUserName;
                $keyword = trim($postObj->Content);
                $time = time();
                $textTpl = "<xml>
							<ToUserName><![CDATA[%s]]></ToUserName>
							<FromUserName><![CDATA[%s]]></FromUserName>
							<CreateTime>%s</CreateTime>
							<MsgType><![CDATA[%s]]></MsgType>
							<Content><![CDATA[%s]]></Content>
							<FuncFlag>0</FuncFlag>
							</xml>";             
				if(!empty( $keyword ))
                {
              		$msgType = "text";
                	$contentStr = "行行，DSB";
                	$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                	echo $resultStr;
                }else{
                	echo "Input something...";
                }

        }else {
        	echo "嗨！";
        	exit;
        }
    }

    // public function responseMsg(){
    //     /*
    //     获得请求时POST:XML字符串
    //     不能用$_POST获取，因为没有key
    //      */
    //     $xml_str = file_get_contents("php://input");
    //     if(empty($xml_str)){
    //         die('');
    //     }
    //     if(!empty($xml_str)){
    //         // 解析该xml字符串，利用simpleXML
    //         libxml_disable_entity_loader(true);
    //         //禁止xml实体解析，防止xml注入
    //           $request_xml = simplexml_load_string($xml_str, 'SimpleXMLElement', LIBXML_NOCDATA);
    //         //判断该消息的类型，通过元素MsgType
    //         switch ($request_xml->MsgType){
    //             case 'event':
    //                 //判断具体的时间类型（关注、取消、点击）
    //                 $event = $request_xml->Event;
    //                   if ($event=='subscribe') { // 关注事件
    //                       $this->_doSubscribe($request_xml);
    //                   }elseif ($event=='CLICK') {//菜单点击事件
    //                       $this->_doClick($request_xml);
    //                   }elseif ($event=='VIEW') {//连接跳转事件
    //                       $this->_doView($request_xml);
    //                   }else{

    //                   }
    //                 break;
    //             case 'text'://文本消息
    //                 $this->_doText($request_xml);
    //                 break;
    //             case 'image'://图片消息
    //                 $this->_doImage($request_xml);
    //                 break;
    //             case 'voice'://语音消息
    //                 $this->_doVoice($request_xml);
    //                 break;
    //             case 'video'://视频消息
    //                 $this->_doVideo($request_xml);
    //                 break;
    //             case 'shortvideo'://短视频消息
    //                 $this->_doShortvideo($request_xml);
    //                 break;
    //             case 'location'://位置消息
    //                 $this->_doLocation($request_xml);
    //                 break;
    //             case 'link'://链接消息
    //                 $this->_doLink($request_xml);
    //                 break;
    //         }        
    //     }        
    // }

    // private $_msg_template = array(
    //     'text' => '<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime><MsgType><![CDATA[text]]></MsgType><Content><![CDATA[%s]]></Content></xml>',//文本回复XML模板
    //     'image' => '<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime><MsgType><![CDATA[image]]></MsgType><Image><MediaId><![CDATA[%s]]></MediaId></Image></xml>',//图片回复XML模板
    //     'music' => '<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime><MsgType><![CDATA[music]]></MsgType><Music><Title><![CDATA[%s]]></Title><Description><![CDATA[%s]]></Description><MusicUrl><![CDATA[%s]]></MusicUrl><HQMusicUrl><![CDATA[%s]]></HQMusicUrl><ThumbMediaId><![CDATA[%s]]></ThumbMediaId></Music></xml>',//音乐模板
    //     'news' => '<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime><MsgType><![CDATA[news]]></MsgType><ArticleCount>%s</ArticleCount><Articles>%s</Articles></xml>',// 新闻主体
    //     'news_item' => '<item><Title><![CDATA[%s]]></Title><Description><![CDATA[%s]]></Description><PicUrl><![CDATA[%s]]></PicUrl><Url><![CDATA[%s]]></Url></item>',//某个新闻模板
    // );

    // /**
    //  * 发送文本信息
    //  * @param  [type] $to      目标用户ID
    //  * @param  [type] $from    来源用户ID
    //  * @param  [type] $content 内容
    //  * @return [type]          [description]
    //  */
    // private function _msgText($to, $from, $content) {
    //     $response = sprintf($this->_msg_template['text'], $to, $from, time(), $content);
    //     die($response);
    // }

    // //关注后做的事件
    // private function _doSubscribe($request_xml){
    //     //处理该关注事件，向用户发送关注信息
    //     $content = '你好';
    //     $this->_msgText($request_xml->FromUserName, $request_xml->ToUserName, $content);
    // }
		
	private function checkSignature()
	{
        // you must define TOKEN by yourself
        if (!C('TOKEN')) {
            throw new Exception('TOKEN is not defined!');
        }
        
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        		
		$token = C('TOKEN');
		$tmpArr = array($token, $timestamp, $nonce);
        // use SORT_STRING rule
		sort($tmpArr, SORT_STRING);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );
		
		if( $tmpStr == $signature ){
			return true;
		}else{
			return false;
		}
	}
}

?>