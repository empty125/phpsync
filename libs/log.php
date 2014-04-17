<?php
/**
 * Description of Sc_Log
 * 
 * @author xilei
 */
class Sc_Log {
    
    const ERROR  = 'error'; 
    const WARN   = 'warn';
    const NOTICE = 'notice';
    const INFO   = 'info';
    const DEBUG  = 'debug';
    
    static private $_data = array();
    
    static public $suffix = '';
    
    static public $levels = array('error','warn','notice','info','debug');
    
    /**
     * 记录日志
     * @param type $message
     * @param type $level
     */
    static public function record($message,$level=self::ERROR){
        if(in_array($level, static::$levels)){
            static::$_data[] = "{$level}:{$message}[".static::$suffix."]\r\n";
        }
    }
    
    /**
     * 写入日志
     * @return type
     */
    static public function save(){
        if(empty(static::$_data)){
            return ;
        }
        $path = Sc::$rootDir.'/data/log';
        if(!is_dir($path)){
            @mkdir($path,0755,true);
        }
        $logfile = $path.'/phpsync'.date('Y-n').'.log';
        error_log(date('Y-n-j H:i:s')."\r\n".implode(static::$_data),3,$logfile);
        static::$_data =array();
    }
    
    /**
     * 检查日志级别是否可用
     * @param type $level
     * @return type
     */
    static public function enableLevel($level){
        return in_array($level, static::$levels);
    }
    
    /**
     * 设置日志级别
     * @param type $levels
     * @param type $delimiter
     */
    static public function setLevels($levels,$delimiter=','){
        if(is_array($levels)){
            static::$levels = $levels;
        }elseif(is_string($levels)&& ($levels = strtolower($levels))!='all'){
            static::$levels = explode($delimiter,$levels);
        }
    }
}
