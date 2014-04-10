<?php
/**
 * Description of Sc_Tracker
 *
 * @author xilei
 */
class Sc_Tracker {
    
    const SUCCESS = 1;
    const FAILURE =-1;
    
    static private $_driver = NULL;
    
    /**
     * 初始化文件信息存储方式
     */
    static private function initDriver(){
        $type = Sc::getConfig('save_type');
        if(!file_exists(__DIR__."/driver/{$type}.php")){
            $type = 'sqlite';
        }
        $class = 'Sc_'.ucfirst($type);
        static::$_driver = new $class();
    }
    
    static public function search($params){
        if(static::$_driver == NULL){
            static::initDriver();
        }
        
    }
    
    static public function afterSearch(){
        if(static::$_driver == NULL){
            static::initDriver();
        }
    }
    
    static public function randomOne($nodes){
        if(count($nodes)==1){
            return key($nodes);
        }        
        $sum = array_sum($nodes);        
        $lastv = 0;
        foreach($nodes as $k=>$v){
            $nodes[$k] +=$lastv;
            $lastv = $nodes[$k];
        }
        
        $rand = mt_rand(1, $sum);
        $lastv = 1;        
        foreach($nodes as $k=>$v){
            if($rand>=$lastv && $rand<$v){
               return $k;
            }
            $lastv = $v;
        }
        return array_rand($nodes);//当权重都一样
    }   
   
}
