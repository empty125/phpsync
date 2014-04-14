<?php
/**
 * Description of Sc_Storage
 * 本地文件管理 
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
    * 下载给其他节点
    * @param type $params
    * array(
    *   'hashname'
    * )
    */
    static public function download($params){
        if(static::$_sync == NULL){
            static::initSync();
        }
        $_info = explode('.', $params['hashname']);
        if(Sc::checkHash($_info[0])){
            Sc_Log::record("[storage download] bad hash parameter {$params[0]}", Sc_Log::NOTICE);
            return static::BAD_PARAMETER;
        }
        $filename = Sc::getFileDir($_info[0]).(isset($_info[1]) ? '.'.$_info[1] : '' );
        if(!is_readable($filename)){
            return static::FILE_NOT_EXISTS;
        }
        
        if(!static::$_sync->sendFile($filename)){
            Sc_Log::record('[storage download] failure '.static::$_sync->error(),  Sc_Log::ERROR);
            return static::FAILURE;
        }        
        return static::SUCCESS;
    }
    
    /**
     * 同步文件到本地
     * @param type $params
     * @return type
     */
    static public function syncFile($params){
        if(static::$_sync == NULL){
            static::initSync();
        }
        if(empty($params['remote'])){
            Sc_Log::record("[storage syncFile] bad remote parameter {$params['remote']}", Sc_Log::NOTICE);
            return static::BAD_PARAMETER;
        }
        if(empty($params['hashname'])){
            Sc_Log::record("[storage syncFile] bad name parameter {$params['hashname']}", Sc_Log::NOTICE);
            return static::BAD_PARAMETER;
        }
        $_info = explode('.', $params['hashname']);
        $path = static::getAvaiablePath($_info[0]);
        if(empty($path)){
            return static::FAILURE;
        }
        $savename = $path.(isset($_info[1]) ? $_info[1] : '');
        if(!static::$_sync->download($params['remote'],$savename)){
             Sc_Log::record("[storage syncFile] save failed ".static::$_sync->error(), Sc_Log::ERROR);
             return static::FAILURE;
        }
        return static::SUCCESS;
    }

    /**
     * 检查本地之否存在,存在返回路径
     * @param type $name
     * @return string
     */
    static public function exists($name){
        if(empty($name)){
            return false;
        }
        $suffix = strrchr($name, '.');
        $hash = Sc::hash($name);        
        
        $path = static::getAvaiablePath($hash,false);
        $filename = $path.$hash.$suffix;
        if(file_exists($filename)){
            return $filename;
        }else{
            return false;
        }
    }
    
    /**
     * 获取可用的文件存储路径
     * @param type $hash
     * @return boolean
     */
    static public function getAvaiablePath($hash,$iscreate=true){
        $savedir = Sc::$rootDir.'/data/file/'.$hash[0].'/';        
        if($iscreate && !is_dir($savedir) && !@mkdir($savedir,0600,true)){
            Sc_Log::record("[storage getAvaiablePath] directory creation failed {$savedir} ",  Sc_Log::ERROR);
            return false;
        }
        return $savedir;
    }
    
    
    /**
     * 
     * @todo 文件上传,保留方法,暂不实现
     */
    static public function upload(){
        
    }
    
}
