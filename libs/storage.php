<?php
/**
 * Description of Sc_Storage
 * 文件同步  
 * @author xilei
 */
class Sc_Storage {
    
    const SUCCESS = 1;
    const FAILURE =-1;//致命错误
    
    const  BAD_PARAMETER = -200;
    
    static private $_sync = NULL;
    
    /**
     * 初始化同步方式
     */
    static private function initSync(){
        $type = Sc::getConfig('sync_type');
        if(!file_exists(__DIR__."/sync/{$type}.php")){
            $type = 'sqlite';
        }
        $class = 'Sc_'.ucfirst($type);
        static::$_sync = new $class();
    }
    
    /**
     * 传输给其他节点
     */
    static public function download($params){
        
    }
    
    /**
     * 
     * @todo 文件上传,保留方法,咱暂不实现
     */
    static public function upload(){
        
    }
 
    
}
