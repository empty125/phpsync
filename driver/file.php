<?php
/**
 * 简单文件存储信息
 * 依靠文件系统,每个文件信息一个文件,可能会导致小文件太多,但可以减少锁问题
 * @todo 单文件hashtable
 * @author xilei
 */
define('SC_DRIVER_FILE_KEY', 1);

class Sc_Driver_File {
    
    //key delimiter
    const DELIMITER = '_';

    private $_basePath = '';
    
    private $_error = '';
    
    public function __construct(){
        $basePath = Sc::$rootDir.'/data/tracker_record';
        if(!is_dir($basePath) && !@mkdir($basePath,0755,true)){
            Sc_Log::record("[driver file] dir creation failed {$basePath}",  Sc_Log::ERROR);
            throw new Exception('dir creation failed', -100);
        }
        $this->_basePath = $basePath;
    }
    
    public function add($data){
        $required = array('hash','node','fhash');
        foreach($required as $key){
            if(empty($data[$key])){
                $this->_error = "{$key} is required";
                return false;
            }
        }
        $filename = '';
        $persistence = $this->_readData($data['hash'],$filename);
        //var_dump($persistence);exit;
        if($persistence === false){
            return false;
        }
        $key = $data['hash'].self::DELIMITER.$data['node'];
        if(isset($persistence[$key])){
            $this->_error = "{$data['hash']},{$data['node']} is recoed";
            return false;
        }
        
        $data['savetime'] = time();
        $data['suffix'] = isset($data['suffix']) ? $data['suffix'] : '';
        $persistence[$key] = $data; 
        return $this->_writeData($filename, $persistence);
    }
    
    public function get($hash){
        if(empty($hash)){
            $this->_error = "$hash is required";
            return false; 
        }
        $persistence =$this->_readData($hash);
        if($persistence === false){
            return false;
        }
        $data = array();
        foreach ($persistence as $item){
            if($item['hash'] == $hash){
                $data[] = $item;
            }
        }
        return $data;    
    }
    
    public function getPage($start=0,$limit=10){
        
    }
    
    public function delete($hash,$node=NULL){
       if(empty($hash)){
            $this->_error='hash is required';
            return false;
       }
       
       $condition = array();
       if(is_string($hash)){           
            $condition[empty($node) ? $hash : $hash.self::DELIMITER.$node] = 1;
       }elseif(is_array($hash)){
          $hasharr = isset($hash['hash']) ? array($hash) : $hash;
          foreach($hasharr as $item){
              if(empty($item['hash'])){
                 continue;
              }
            $condition[empty($item['node']) ? $item['hash'] : $item['hash'].self::DELIMITER.$item['node']] =1;
          }
       }else{
           $this->_error = "hash must be array|string";
           return false;
       }
       unset($hash);
       
       $filename = '';
       $persistence = $this->_readData($hash, $filename);
       if($persistence === false){
           return false;
       }
       if(empty($persistence)){
           return true;
       }
      
       $newPersistence = array();
       foreach ($persistence as $key =>$item){
           if(!isset($condition[$item['hash']]) && !isset($condition[$key])){
               $newPersistence[$key] = $item;
           }
       }
       if(empty($newPersistence)){
          return @unlink($filename);
       }
       return $this->_writeData($filename, $newPersistence);
    }
    
    public function exists($hash,$node){
        if(empty($hash)){
             $this->_error='hash is required';
             return -1;
        }
        if(empty($node)){
             $this->_error='node is required';
             return -1;
        }
        $persistence =$this->_readData($hash);
        if($persistence === false){
            return false;
        }
        $key = $hash.self::DELIMITER.$node;
        return isset($persistence[$key]);
    }
    
    /**
     * 读取内容
     * @param type $hash
     * @param type $filename
     * @return boolean
     */
    private function _readData($hash,&$filename){
        $path = $this->_path($hash);
        if(empty($path)){
            return false;
        }   
        $_filename = $path.$hash.'.php';

        $data = array();
        if(file_exists($_filename)){
            $data = require($_filename);
        }else{
            return array();
        }
        if(isset($filename)){
            $filename = $_filename;
        }
        return $data;
    }
    
    /**
     * 获取文件存储路径
     * @param type $hash
     * @param type $iscreate
     * @return boolean
     */
    private function _path($hash,$iscreate=true){
        if(strlen($hash)<2){
            $this->_error = "hash too short {$hash}";
            return false;
        }
        $savedir = $this->_basePath."/{$hash[0]}/{$hash[1]}";        
        if($iscreate && !is_dir($savedir) && !mkdir($savedir,0755,true)){
            $this->_error = "[driver file] directory creation failed {$savedir} ";
            return false;
        }
        return $savedir.'/';
        
    }
    
    /**
     * 写入数据
     * @param type $filename
     * @param type $persistence
     * @return boolean
     */
    private function _writeData($filename,$persistence){        
        $savedata = "<?php return !defined('SC_DRIVER_FILE_KEY') ?  false : ". var_export($persistence,true).";";
        $f = fopen($filename, 'wb+');
        if(!flock($f, LOCK_SH)){//LOCK_NB
            $this->_error = "lock file {$filename} failed";
            return false;
        }
        if($f){
            if(!flock($f, LOCK_SH)){//LOCK_NB
                $this->_error = "lock file {$filename} failed";
                return false;
            }
            fwrite($f, $savedata);
        }else{
            $this->_error = "open file {$filename} failed";
            return false;
        }
        //flock($f, LOCK_UN);
        fclose($f);
        return true;
    }
    
    public function error(){
        return $this->_error;
    }
    
    public function close(){
        
    }   
}
