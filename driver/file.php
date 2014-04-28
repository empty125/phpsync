<?php
/**
 * 简单文件存储信息
 *
 * @author xilei
 */
class Sc_Driver_File {
    
    private $_basePath = '';
    
    private $_error = '';
    
    public function __construct(){
        $basePath = Sc::$rootDir.'/data/.tracker_record';
        if(!is_dir($basePath) && mkdir($basePath,0755,true)){
            Sc_Log::record("[driver file] dir creation failed {$basePath}",  Sc_Log::ERROR);
            throw new Exception('dir creation failed', -100);
        }
        $this->_basePath = $basePath;
    }
    
    public function add($data){
        
    }
    
    public function get($hash){
        
    }
    
    public function getPage($start=0,$limit=10){
        
    }
    
    public function delete($hash,$node=NULL){
        
    }
    
    public function exists($hash,$node){
        
    }
    
    public function error(){
        return $this->_error;
    }
    
    public function close(){
        
    }   

    public function __destruct() {
        ;
    }
}
