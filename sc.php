<?php
/**
 * 简单分布式环境中文件被动同步
 * https://github.com/empty125/phpsync
 * 
 * 改动,不使用命名空间,使用前缀代替,简化结构;
 * bootstrap file,this is a free software!
 * 
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @author xilei
 */
class Sc {
   
   /**
    *配置文件缓存
    * @var type 
    */
   static private $_conf = NULL;
   
   /**
    * 用于class autoload
    * @var type 
    */
   static private $_classMap=array(
       'Sc_Tracker'=>'./libs/tracker.php',
       'Sc_Storage'=>'./libs/storage.php',
       'Sc_Log'=>'./libs/log.php',
       'Sc_Client'=>'./libs/client.php',
       'Sc_Util'=>'./libs/util.php',
       'Sc_File'=>'./driver/file.php',
       'Sc_Sqlite'=>'./driver/sqlite.php',
       'Sc_Http'=>'./sync/http.php',
       'Sc_Cmd'=>'./sync/cmd.php',
       'Sc_Client_Warpper'=>'./libs/client_warpper.php'
   );
   
   /**
    *  是否是name server
    * @var type 
    */
   static private $_isnameserver = FALSE;
   
   /**
    * client ip
    * @var type 
    */
   static private $_client_ip = NULL;
   
   static public $rootDir = NULL;

   
   static public function init(){
       static::$rootDir = str_replace("\\", '/',  __DIR__);
       
       define('SC_ISFCGI', php_sapi_name() == 'cgi-fcgi');
       date_default_timezone_set('Asia/Shanghai');
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
    * 检查是否是name server
    * @return type
    */
   static public function checkIsNameServer(){
        if(static::$_isnameserver === NULL){
            static::$_isnameserver = strpos(static::$conf['name_server'],$_SERVER['SERVER_ADDR'])!==false
             || strpos(static::$conf['name_server'],$_SERVER['HTTP_HOST'])!==false; 
        }
        return static::$_isnameserver;
   } 
  
  /**
   * 
   * @return type
   */
   static public function getFromNode(){
     if(empty(static::$_client_ip)){
         static::$_client_ip = Sc_Util::get_client_ip();
     }
     return static::$_client_ip;
   }
   
   /**
    * 
    * @param type $hash
    * @return type
    */
   static public function checkHash($hash){
       return !empty($hash) && strlen($hash)==32;
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
       return !empty($node) && count($_node = explode('.', $node))==4 && max($_node)<=255;
   }
   
   /**
    * 消息格式
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