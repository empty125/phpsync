<?php
/**
 * Description of Sc
 * 改动,不使用命名空间,使用前缀代替,简化代码,不要求可扩展性
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
       'Sc_Log'=>'./log.php',
       'Sc_Client'=>'./client.php',
       'Sc_Util'=>'./util.php',
       'Sc_File'=>'./driver/file.php',
       'Sc_Sqlite'=>'./driver/sqlite.php',
       'Sc_Http'=>'./sync/http.php',
       'Sc_Cmd'=>'./sync/cmd.php',
       'Sc_Client_Warpper'=>'./client_warpper.php'
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
           require __DIR__.static::$_classMap[$className];
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
           $filename = __DIR__.'/config.php';
           if(file_exists($filename)){
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
        if(static::$IS_NAME_SERVER === NULL){
            static::$IS_NAME_SERVER = strpos(static::$conf['name_server'],$_SERVER['SERVER_ADDR'])!==false
             || strpos(static::$conf['name_server'],$_SERVER['HTTP_HOST'])!==false; 
        }
        return static::$IS_NAME_SERVER;
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
   
   
   
}
