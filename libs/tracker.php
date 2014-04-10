<?php
/**
 * Description of Sc_Tracker
 *
 * @author xilei
 */
class Sc_Tracker {
    
    const SUCCESS = 1;
    const FAILURE =-1;//致命错误
    
    const  E_NOT_AVAILABLE_NODES = -200;
    const  E_BAD_PARAMETER = -201;
    
    
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
    
    /**
     * 获取一个可用的节点
     * @param type $params
     * array(
     *  'hash'=>'',//文件名hash
     *  'nnode'=>'',//删除此节点上的文件记录[可选,默认空]
     *  'add'=>1 //是否搜索后添加[1/0 可选,默认1]
     * )
     */
    static public function search($params){
        if(static::$_driver == NULL){
            static::initDriver();
        }
        $nodes = Sc::getConfig('nodes');
        if(empty($nodes)){
            Sc_Log::record("[tracker] nodes is empty",  Sc_Log::WARN);
            return static::E_NOT_AVAILABLE_NODES;
        }
        if(empty($params['hash']) ||  strlen($params['hash'])){
            Sc_Log::record("[tracker] bad hash parameter {$params['hash']}",  Sc_Log::WARN);
            return static::E_BAD_PARAMETER;
        }
        $anodes = static::$_driver->get($params['hash']);
        $_metadata = array();
        $_rnode = array();
        
        $nnode = isset($params['nnode']) ? $params['nnode'] : '';
        $afterdata = array();
        foreach($anodes as $node){
            if(isset($nodes[$node['node']]) &&  $node['node']!=$nnode){
                $_rnode[$node['node']] = isset($nodes[$node['node']]['weight']) ? $nodes[$node['node']]['weight'] : 1;
            }else{
                $afterdata['delete'][]= array('hash'=>$node['hash'],'node'=>$node['node']);
            }
        }
        
        if(empty($_rnode)){
            return static::E_NOT_AVAILABLE_NODES;
        }
        
        $rdata = $_metadata[static::randomOne($_rnode)];
        
        if(!isset($params['add']) || $params['add'] == 1){
            $sdata = $rdata;
            $sdata['node'] = Sc::getFromNode();
            unset($sdata['savetime']);
            $afterdata['add'] =$sdata;
        }
        unset($nodes,$_rnode,$_metadata,$anodes);
        
        return array('data'=>$rdata,'after'=>$afterdata);
    }
    
    /**
     * 完成search之后执行,异步模式使用fastcgi
     * @param type $data
     */
    static public function afterSearch($data){
        if(static::$_driver == NULL){
            static::initDriver();
        }
        if(!empty($data['delete']) && static::$_driver->delete($data['delete'])===FALSE){
            if(Sc_Log::enableLevel(Sc_Log::ERROR)){
                Sc_Log::record('[tracker] delete failure,'.  var_export($data['dalete'], true).static::$_driver->error(),Sc_Log::ERROR);
            }                
        }
        if(!empty($data['add']) && !static::$_driver->add($data['add'])){
            if(Sc_Log::enableLevel(Sc_Log::ERROR)){
                Sc_Log::record('[tracker] add failure,'.  var_export($data['add'], true).static::$_driver->error(),Sc_Log::ERROR);
            } 
        }
    }
    
    
    /**
     * 
     * @param type $params
     */
    static public function doAdd($params){
        
    }


    static public function afterAdd($data){
        
    }

    /**
     * 加权随机
     * @param type $nodes
     * array(
     *  'node'=>'weight'
     * )
     * @return type
     */
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
