<?php
/**
 * Description of Sc_Storage
 * 本地文件管理 
 * @todo 并发文件处理
 * @author xilei
 */
class Sc_Storage {
    
    static private $_sync = NULL;
    
    //默认文件权限
    static private $defaultMod = 0600;
    
    /**
     * 获取connect可用操作
     * @return type
     */
    static public function supportMethods(){
        return array('download');
    }
    /**
     * 初始化同步方式
     */
    static private function initSync(){
        if(static::$_sync != NULL){
            return;
        }
        $type = Sc::getConfig('sync_type');
        if(!file_exists(Sc::$rootDir."/sync/{$type}.php")){
            Sc_Log::record("[storage initSync]not found {$type},default http",  Sc_Log::WARN);
            $type = 'http';            
        }
        require Sc::$rootDir."/sync/{$type}.php";
        $class = 'Sc_Sync_'.ucfirst($type);
        static::$_sync = new $class();
    }
    
    /**
     * 设置创建文件权限
     * @param type $mod
     */
    static public function setMod($mod){
        if(!empty($mod)) 
        static::$defaultMod = $mod;
    }

    /**
    * 下载 action
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
        if(!Sc::checkHash($_info[0])){
            Sc_Log::record("[storage download] bad hash parameter {$params[0]}", Sc_Log::ERROR);
            return Sc::S_BAD_PARAMETER;
        }
        $filename = static::getAvaiablePath($_info[0],false).$_info[0].(isset($_info[1]) ? '.'.$_info[1] : '' );
        if(!is_readable($filename)){
            Sc_Log::record("[storage download] file not exists {$filename}", Sc_Log::ERROR);
            return Sc::S_FILE_NOT_EXISTS;
        }
        
        if(!static::$_sync->sendFile($filename,Sc::getFromNode())){
            Sc_Log::record('[storage download] failure '.static::$_sync->error(),  Sc_Log::ERROR);
            return Sc::S_FAILURE;
        }        
        return Sc::S_SUCCESS;
    }
    
    /**
     * action
     * @todo 文件上传,保留方法
     */
    static public function upload(){
        
    }

    
    /**
     * 同步文件到本地
     * @param type $params
     * @return type
     */
    static public function syncFile($node,$hashname){
        if(static::$_sync == NULL){
            static::initSync();
        }
        if(Sc::checkNode($node)){
            Sc_Log::record("[storage syncFile] bad node parameter {$node}", Sc_Log::ERROR);
            return Sc::S_BAD_PARAMETER;
        }
        $_info = explode('.',$hashname);
        if(!Sc::checkHash($_info[0])){
            Sc_Log::record("[storage syncFile] bad hashname parameter {$hashname}", Sc_Log::ERROR);
            return Sc::S_BAD_PARAMETER;
        }
       
        $path = static::getAvaiablePath($_info[0]);
        if(empty($path)){
            return Sc::S_FAILURE;
        }
        
        if(property_exists(static::$_sync, 'remote')){
            static::$_sync->remote = Sc::buildUrl('storage', 'download',array(
                'hashname'=>$hashname
            ));
        }
        
        $savefile = $path.$hashname;
        if(!static::$_sync->download($node,$savefile)){
             Sc_Log::record("[storage syncFile] save failed ".static::$_sync->error(), Sc_Log::ERROR);
             return Sc::S_SYNC_FAILED;
        }
        if(static::$defaultMod)chmod($savefile, static::$defaultMod);
        return array(
            'hash'=>$_info[0],
            'file'=>$savefile
        );
    }
    

    /**
     * 添加文件到storage管理
     * @param type $filename[文件位置/url]
     * @param type $name,文件名[可选]
     * @return type
     */
    static public function saveFile($filename,$name=''){
        if(Sc_Log::enableLevel(Sc_Log::WARN) && strpos($filename,'http://')!==FALSE && empty($name)){
            Sc_Log::record("[storage saveFile] open http stream mush have name parameter {$filename}",Sc_Log::WARN);
            //return static::BAD_PARAMETER;
        }
        $source = fopen($filename,'rb');
        if(!$source){
            Sc_Log::record("[storage saveFile] open failed {$filename}",Sc_Log::ERROR);
            return Sc::S_FAILURE;
        }
        $name = empty($name) ? basename($filename) : $name;
        $hash = Sc::hash($name);
        $suffix = strrchr($name, '.');
        $path = static::getAvaiablePath($hash);
        if(empty($path)){
            fclose($source);
            return Sc::S_FAILURE;
        }
        $savefile = $path.$hash.$suffix;
        $dist = fopen($savefile,'wb');
        if(!$dist){
            fclose($source);
            Sc_Log::record("[storage saveFile] open failed {$savefile}",Sc_Log::ERROR);
            return Sc::S_FAILURE;
        }
        $length = stream_copy_to_stream($source,$dist);
        fclose($source);
        fclose($dist);
        if(!$length){
           Sc_Log::record("[storage saveFile] stream copy failed {$source} to {$savefile}",Sc_Log::ERROR);
           return Sc::S_FAILURE;
        }
        if(static::$defaultMod)chmod($savefile, static::$defaultMod);
        return array(
            'hash'=>$hash,
            'file'=>$savefile,
            'suffix'=>$suffix
        );
    }
    
    /**
     * 删除文件
     * @param type $name
     * @return boolean
     */
    static public function delete($name){
        if(empty($name)){
            return false;
        }
        $hash = Sc::hash($name);
        $suffix = strrchr($name, '.');
        $path = static::getAvaiablePath($hash,false);
        $savefile = $path.$hash.$suffix;
        return @unlink($savefile);
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
        $savefile = $path.$hash.$suffix;
        if(file_exists($savefile)){
            return $savefile;
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
        if(strlen($hash)<2)return false;
        $savedir = Sc::getStoragePath($hash);        
        if($iscreate && !is_dir($savedir) && !mkdir($savedir,0755,true)){
            Sc_Log::record("[storage getAvaiablePath] directory creation failed {$savedir} ",  Sc_Log::ERROR);
            return false;
        }
        return $savedir.'/';
    }  
}
