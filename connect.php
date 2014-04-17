<?php
/**
 * 处理节点间请求
 * 检查节点是否合法
 * 路由tracker和node请求
 * 设置日志级别
 * 检查请求是否来自配置的节点
 */
define('SC_ISFCGI', strpos(php_sapi_name(),'fcgi')!==FALSE);

require __DIR__.'/sc.php';

if(Sc::isCli()){
    exit("not support in cli\r\n");
}
Sc_Log::$suffix = 'from '.Sc::getFromNode();
Sc_Log::setLevels(Sc::getConfig('log_level'));


$nodes = Sc::getConfig('nodes');
if(!isset($nodes[Sc::getFromNode()])){
    Sc_Util::sendHttpStatus(403);exit;
}

//--------------------------------------------------------

$route = filter_input(INPUT_GET, 'r');

list($module,$method) = explode('.', $route);
if(!isset($method)){
    Sc_Util::sendHttpStatus(400);exit;
}

unset($_GET['r']);
$params = array();
foreach($_GET as $key=>$value){
    $params[$key] =  addslashes($value);
}

switch($module){
    case 'tracker':
        if(Sc::checkIsNameServer()){
            Sc_Log::record("[connect] try connect this node,but this not a nameserver",  Sc_Log::ERROR);
            Sc_Util::sendHttpStatus(403);exit;
        }
        if(!in_array($method,Sc_Tracker::supportMethods())){
            Sc_Util::sendHttpStatus(400);exit;
        }        
        $result = call_user_func(array(Sc_Tracker,$method), $params);
        if(is_array($result)){
            $_data = array('code'=>  Sc::T_SUCCESS);
            if(isset($result['data'])){
                $_data['data'] = $result['data'];
            }
            echo Sc::pack($_data);
            if(SC_ISFCGI){
                fastcgi_finish_request();
            }
            Sc_Tracker::callAfter($method, $result);
        }else{
            echo Sc::pack(array('code'=>$result));
        }
        break;
    case 'storage':
        if(!in_array($method, Sc_Storage::supportMethods())){
            Sc_Util::sendHttpStatus(400);exit;
        }
        //storage
        $result = call_user_func(array(Sc_Storage,$method), $params);
        if($result<0){
            Sc_Util::sendHttpStatus(404);//失败,全部返回404
        }
        break;
    default :
        Sc_Util::sendHttpStatus(400);
        break;
}