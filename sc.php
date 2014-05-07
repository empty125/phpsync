<?php
/**
 * 简单分布式环境中文件<b>被动</b>同步
 * https://github.com/empty125/phpsync
 * 
 * 改动,不使用命名空间,使用前缀代替,简化结构;
 * bootstrap file,this is a free software!
 * 
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @author xilei
 */
class Sc {
   
   //tracker error code
    
   const T_SUCCESS = 1;
   const T_FAILURE = -1;
   
   const T_BAD_PARAMETER = -400;
   const T_S_NOT_AVAILABLE_NODES = -201;
   
   //storage error code
   
   const S_SUCCESS = 1;
   const S_FAILURE = -1;
   
   const S_BAD_PARAMETER = -400;
   const S_FILE_NOT_EXISTS = -404;

   const S_SYNC_FAILED = -500;
   /**
    *配置文件缓存
    * @var type 
    */
   static private $_conf = NULL;
   
   /**
    * core class autoload
    * @var type 
    */
   static private $_classMap=array(
       'Sc_Tracker'=>'./libs/tracker.php',
       'Sc_Storage'=>'./libs/storage.php',
       'Sc_Log'=>'./libs/log.php',
       'Sc_Client'=>'./libs/client.php',
       'Sc_Util'=>'./libs/util.php',
       'Sc_Client_Warpper'=>'./libs/client_warpper.php'
   );
   
   /**
    * client ip
    * @var type 
    */
   static private $_client_ip = NULL;
   
   static public $rootDir = NULL;
   
   static public $dataPath = NULL;
   
   static public function init(){
       static::$rootDir = str_replace("\\", '/',  __DIR__);
       
       static::$dataPath = static::$rootDir.'/data';
       if(!is_writable(static::$dataPath)){
           exit('data path is not writable');
       }
      
       date_default_timezone_set('Asia/Shanghai');
       
       set_error_handler(array('Sc','errorHandler'));
       
       set_exception_handler(array('Sc','exceptionHandler'));
       
       spl_autoload_register(array('Sc','autoload')); 
       
       register_shutdown_function(array('Sc','shutdown'));    
   }
   
   /**
    * autoload
    * @param type $className
    */
   static public function autoload($className){
       if(isset(static::$_classMap[$className])){
           require __DIR__.'/'.static::$_classMap[$className];
       }
   }
   
   /**
    * shutdown
    */
   static public function shutdown(){
       if(class_exists('Sc_Log')){
           Sc_Log::save();
       }
   }
   
   /**
    * exception handle
    */
   static public function exceptionHandler($e){
     if(Sc_Log::enableLevel(Sc_Log::ERROR)){       
        Sc_Log::record("[execption]".$e->getMessage().";file:".$e->getFile().",line:".$e->getLine(),  Sc_Log::ERROR);
     }
   }
   
   /**
    * error handle
    */
   static public function errorHandler($errno, $errstr, $errfile, $errline){
       switch($errno){
           case E_ERROR:
           case E_PARSE:
           case E_USER_ERROR:
           case E_CORE_ERROR:
           case E_COMPILE_ERROR:
               if(Sc_Log::enableLevel(Sc_Log::ERROR)){
                 Sc_Log::record("[error]$errstr;code: {$errno}, file:".$errfile." line: $errline",  Sc_Log::ERROR);
               }
               break;
           case E_WARNING:
           case E_USER_WARNING:
               if(Sc_Log::enableLevel(Sc_Log::WARN)){
                 Sc_Log::record("[error]$errstr;code: {$errno}, file:".$errfile." line: $errline",  Sc_Log::WARN);
               }
               break;
       }
   }

   /**
    * 获取配置项
    * @param type $name
    * @return type
    */
   static public function getConfig($name){
       if(static::$_conf == NULL){
           $filename = Sc::$rootDir.'/config.php';
           if(!file_exists($filename)){
               throw new Exception('配置文件没有找到', -100);
           }
           static::$_conf = require($filename);
       }
       return static::$_conf[$name];
   }
   
   /**
    * 是否为cli模式
    * @return type
    */
   static public function isCli(){
      static $_cli = NULL;
      if($_cli ===NULL){
         $_cli = php_sapi_name() == 'cli';
      }
      return $_cli;
   }
   
   /**
    * 检查是否是name server
    * @return type
    */
   static public function checkIsNameServer($client = NULL){
        $_isnameserver = false;
        if(static::isCli()){
            $_isnameserver = true;
        }else{
            $ip = empty($client) ? $_SERVER['SERVER_ADDR'] : $client;
            $_isnameserver = strpos(static::getConfig('name_server'),$ip) === 0;
        }
        return $_isnameserver;
   } 

   /**
   * 
   * @return type
   */
   static public function getFromNode(){
     if(static::isCli()){
         //cli 模式下需要手动指定IP[本机]
         return isset($_SERVER["argv"][1]) ? $_SERVER["argv"][1] : '';
     }
     
     if(empty(static::$_client_ip)){
         static::$_client_ip = Sc_Util::get_client_ip();
     }
     return static::$_client_ip;
   }
   
   
   /**
    * 获取文件存储路径
    * @return type
    */
   static public function getStoragePath($hash){
       return static::$rootDir."/data/storage/{$hash[0]}/{$hash[1]}";
   }
   
   /**
    * 创建请求url
    * @param type $module
    * @param type $method
    * @param type $params
    * @return string
    */
   static public function buildUrl($module,$method,$params=array()){
       $url ="";
       switch($module){
           case 'tracker':
               $url = "http://".static::getConfig("name_server")."/connect.php?r=tracker.{$method}";
               if(!empty($params)){
                   $url.= '&'.http_build_query($params);
               }       
               break;
           case 'storage':
               $_nodes = Sc::getConfig('nodes');
               $node = $params['node'];
               unset($params['node']);
               $url = "http://".$node.(isset($_nodes[$node]['path']) ? "/".$_nodes[$node]['path']: "")
                   ."/connect.php?r=storage.{$method}";
               if(!empty($params)){
                   $url.='&'.http_build_query($params);
               }
               break;
       }
       return $url;
   }

   /**
    * 
    * @param type $hash
    * @return type
    */
   static public function checkHash($hash){
       return !empty($hash) && preg_match('/^\w{32}$/', $hash);
   }
   
   /**
    * 
    * @param type $string
    * @param type $isFile
    * @return type
    */
   static public function hash($string,$isFile=false){
       return $isFile ?  md5_file($string): md5($string);
   }
   
   
   /**
    * 检查节点格式(IP)
    * @todo ipv6
    */
   static public function checkNode($node){
       return !empty($node) && count($_node = explode('.', $node))==4 
              && is_numeric($max = max($_node)) && $max<255 && $max>0;
   }
   
   /**
    * 消息格式(这里要求所有的节点都支持msgpack)
    * @param type $data
    * @return type
    */
   static public function pack($data){
       if(function_exists('msgpack_pack')){
           return msgpack_pack($data);
       }
       return serialize($data);
   }
   
   /**
    * 消息格式
    * @param type $str
    * @return type
    */
   static public function unpack($str){
       if(function_exists('msgpack_unpack')){
           return msgpack_unpack($str);
       }
       return unserialize($str);
   }
   
}

Sc::init();