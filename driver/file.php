<?php
define('SC_DRIVER_FILE_PATH', Sc::$rootDir.'/data/tracker_record');

/**
 * 简单文件存储信息
 * base on filesystem
 * 依靠文件系统,每个文件信息一个文件,可能会导致小文件太多,但可以减少锁问题
 * @todo 单文件hashtable
 * @author xilei
 */
class Sc_Driver_File {
    
    //key delimiter
    const DELIMITER = '_';

    private $_error = '';
    
    public function __construct(){
        $basePath = SC_DRIVER_FILE_PATH;
        if(!is_dir($basePath) && !@mkdir($basePath,0755,true)){
            Sc_Log::record("[driver file] dir creation failed {$basePath}",  Sc_Log::ERROR);
            throw new Exception('dir creation failed', -100);
        }  
    }
    /**
     * 
     * @param type $data
     * @return boolean
     */
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
    /**
     * 
     * @param type $hash
     * @return boolean
     */
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
    
    /**
     * @param type $params
     * @param type $start
     * @param type $limit
     */
    public function getPage($params=array(),$start=0,$limit=10){
        $this->_error = "getPage is not supported yet";
        return false;
    }
    
    /**
     * 
     * @param type $hash
     * @param type $node
     * @return int|boolean
     */
    public function delete($hash,$node=NULL){
       if(empty($hash)){
            $this->_error='hash is required';
            return false;
       }
       
       $_hash = array();
       $condition = array();
       if(is_string($hash)){           
            $condition[empty($node) ? $hash : $hash.self::DELIMITER.$node] = 1;
            $_hash[$hash] = 1;
       }elseif(is_array($hash)){
          $hasharr = isset($hash['hash']) ? array($hash) : $hash;
          foreach($hasharr as $item){
              if(empty($item['hash'])){
                 continue;
              }
            $condition[empty($item['node']) ? $item['hash'] : $item['hash'].self::DELIMITER.$item['node']] =1;
            $_hash[$item['hash']] = 1;
          }
       }else{
           $this->_error = "hash must be array|string";
           return false;
       }
       unset($hash);
       
       $dcount = 0;
       foreach($_hash as $hkey => $kvalue){
            $filename = '';
            $persistence = $this->_readData($hkey, $filename);
            if($persistence === false){
                continue;
            }
            if(empty($persistence)){
                continue;
            }
            
            $delnum = 0;
            $newPersistence = array();
            foreach ($persistence as $key =>$item){
                if(!isset($condition[$item['hash']]) && !isset($condition[$key])){
                    $newPersistence[$key] = $item;
                }else{
                    $delnum++;
                }
            }           
            if(!$this->_writeData($filename, $newPersistence)){
                continue;
            }
            $dcount+=$delnum;
       }
       return $dcount;
    }
    
    /**
     * 
     * @param type $hash
     * @param type $node
     * @return type
     */
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
            return -1;
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
        if(isset($filename)) $filename = $_filename;
        
        $data = array();
        if(file_exists($_filename)){
            $data = require($_filename);
        }else{
            return array();
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
        $savedir = SC_DRIVER_FILE_PATH."/{$hash[0]}/{$hash[1]}";        
        if($iscreate && !is_dir($savedir) && !mkdir($savedir,0755,true)){
            $this->_error = "directory creation failed {$savedir} ";
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
        if(empty($persistence)) {
            return @unlink($filename);
        }
        $savedata = "<?php return !defined('SC_DRIVER_FILE_PATH') ?  false : "
        . str_replace(array("\n","\t"," "),"",var_export($persistence,true)).";?>";
        $f = fopen($filename, 'w+');
        if($f){
            if(!flock($f, LOCK_EX)){//LOCK_NB
                $this->_error = "lock file {$filename} failed";
                fclose($f);
                return false;
            }
            fwrite($f, $savedata);
            flock($f, LOCK_UN);
        }else{
            $this->_error = "open file {$filename} failed";
            return false;
        }        
        fclose($f);
        return true;
    }
    
    public function error(){
        $_error = $this->_error;
        $this->close();
        return $_error;
    }
    
    public function close(){
        $this->_error = '';
        return true;
    }   
}
