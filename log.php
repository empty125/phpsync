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
    
    private static $_data = array();
    
    public static  $suffix = '';
    
    public static $levels = array('error','warn','notice','info');

    public static function record($message,$level=self::ERROR){
        if(in_array($level, static::$levels)){
            static::$_data[] = "{$level}:{$message}[".static::$suffix."]\r\n";
        }
    }
    
    public static function save(){
        if(empty(static::$_data)){
            return ;
        }
        $logfile = Sc::$rootDir.'/data/log/phpsync'.date('Y-n').'.log';
        error_log(date('Y-n-j H:i:s')."\r\n".implode(static::$_data),3,$logfile);
        static::$_data =array();
    }
    
    public static function enableLevel($level){
        return in_array($level, static::$levels);
    }
}
