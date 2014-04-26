<?php
/**
 * Description of Sc_Tracker
 * 文件分布信息管理
 * @author xilei
 */
class Sc_Tracker {
    
    static private $_driver = NULL;
    
    /**
     * 获取connect可用操作
     * @return type
     */
    static public function supportMethods(){
        return array('search','add','delete');
    }


    /**
     * 初始化文件信息存储方式
     */
    static private function initDriver(){
        if(static::$_driver!=NULL){
            return;
        }
        $type = Sc::getConfig('save_type');
        if(!file_exists(Sc::$rootDir."/driver/{$type}.php")){
            Sc_Log::record("[tracker initDriver]not found {$type},default sqlite",  Sc_Log::WARN);
            $type = 'sqlite';
        }
        require Sc::$rootDir."/driver/{$type}.php";
        $class = 'Sc_Driver_'.ucfirst($type);
        static::$_driver = new $class();
    }
    
    /**
     * 获取一个可用的节点
     * @param type $params
     * array(
     *  'name'=>'',//文件名
     *  'nnode'=>'',//删除此节点上的文件记录[可选,默认空]
     *  'add'=>1 //是否搜索后添加[1/0 可选,默认1]
     * )
     */
    static public function search($params){        
        $nodes = Sc::getConfig('nodes');
        if(empty($nodes)){
            Sc_Log::record("[tracker search] nodes is empty",  Sc_Log::ERROR);
            return Sc::T_S_NOT_AVAILABLE_NODES;
        }
        if(empty($params['name'])){
            Sc_Log::record("[tracker search] bad hash parameter {$params['name']}",  Sc_Log::ERROR);
            return Sc::T_BAD_PARAMETER;
        }
        $hash = Sc::hash($params['name']);
        if(static::$_driver == NULL){
            static::initDriver();
        }
        $anodes = static::$_driver->get($hash);
        $_metadata = array();
        $_rnode = array();
        
        $nnode = isset($params['nnode']) ? $params['nnode'] : '';
        $afterdata = array();
        foreach($anodes as $node){
            if(isset($nodes[$node['node']]) &&  $node['node']!=$nnode){
                $_rnode[$node['node']] = isset($nodes[$node['node']]['weight']) ? $nodes[$node['node']]['weight'] : 1;
                $_metadata[$node['node']] = $node;
            }else{
                $afterdata['delete'][]= array('hash'=>$node['hash'],'node'=>$node['node']);
            }
        }
        
        if(empty($_rnode)){
            return Sc::T_S_NOT_AVAILABLE_NODES;
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
     * 完成search之后才能调用,异步模式使用[fastcgi]
     * @param type $data
     */
    static public function afterSearch($data){
        if(!empty($data['delete']) && static::$_driver->delete($data['delete'])===FALSE){
            if(Sc_Log::enableLevel(Sc_Log::ERROR)){
                Sc_Log::record('[tracker after search] failure,'.var_export($data['dalete'], true).static::$_driver->error(),Sc_Log::ERROR);
            } 
            return false;
        }
        $r = static::$_driver->exists($data['add']['hash'],$data['add']['node']);
        if($r === -1 && Sc_Log::enableLevel(Sc_Log::WARN)){
            Sc_Log::record('[tracker after saerch]exists failure,'.var_export($data, true).static::$_driver->error(),Sc_Log::WARN);
        }elseif($r && Sc_Log::enableLevel(Sc_Log::NOTICE)){
            Sc_Log::record('[tracker after saerch] exists,'.var_export($data, true),Sc_Log::NOTICE);
            return true;
        }
        if(!empty($data['add']) && !static::$_driver->add($data['add'])){
            if(Sc_Log::enableLevel(Sc_Log::ERROR)){
                Sc_Log::record('[tracker after search] failure,'.var_export($data['add'], true).static::$_driver->error(),Sc_Log::ERROR);
            }
            return false;
        }
        static::destoryDriver();
        return true;
    }
    
    
    /**
     * 
     * @param type $params
     * array(
     *  'hash'=>'',
     *  'fhash'=>'',
     *  'node'=>'',[可选]
     *  'suffix'=>''
     * )
     */
    static public function add($params){
        if(static::$_driver == NULL){
            static::initDriver();
        }        
        if(!Sc::checkHash($params['hash'])){
            Sc_Log::record("[tracker add] bad hash parameter {$params['hash']}",  Sc_Log::ERROR);
            return Sc::T_BAD_PARAMETER;
        }
        if(!Sc::checkHash($params['fhash'])){
            Sc_Log::record("[tracker add] bad fhash parameter {$params['fhash']}", Sc_Log::ERROR);
            return Sc::T_BAD_PARAMETER;
        }
        if(!empty($params['node']) && !Sc::checkNode($params['node'])){
            Sc_Log::record("[tracker add] bad node parameter {$params['node']}",  Sc_Log::ERROR);
            return Sc::T_BAD_PARAMETER;
        }else{
            $params['node'] = Sc::getFromNode();
        }
        
        $savedata = array(
            'hash' => $params['hash'],
            'fhash'=> $params['fhash'],
            'node' => $params['node']
        );
       // $suffix = strrchr($params['name'], '.');
       // $savedata['suffix'] = ($suffix == false ? '' : $suffix);
        if(!empty($params['suffix'])){
            $savedata['suffix'] = $params['suffix'];
        }
        return array('after'=>$savedata);
    }

    /**
     * 添加之后调用(执行操作)
     * @param type $data
     * @return boolean
     */
    static public function afterAdd($data){
        if(empty($data)){
            return false;
        }
        $r = static::$_driver->exists($data['hash'],$data['node']);
        if($r === -1 && Sc_Log::enableLevel(Sc_Log::WARN)){
            Sc_Log::record('[tracker after add] exists failure,'.var_export($data, true).static::$_driver->error(),Sc_Log::WARN);
        }elseif($r && Sc_Log::enableLevel(Sc_Log::NOTICE) ){
            Sc_Log::record('[tracker after add] exists,'.var_export($data, true),Sc_Log::NOTICE);
            return true;
        }
        if(!static::$_driver->add($data)){
            if(Sc_Log::enableLevel(Sc_Log::ERROR)){
                Sc_Log::record('[tracker after add] failure,'.var_export($data, true).static::$_driver->error(),Sc_Log::ERROR);
            }
            return false;
        }
        static::destoryDriver();
        return true;
    }
    
    /**
     * 删除
     * @param type $params
     * array(
     *  'hash'=>'',
     *  'node'=>''[可选]
     * )
     * @return type
     */
    static public function delete($params){
        if(static::$_driver == NULL){
            static::initDriver();
        }
        if(!Sc::checkHash($params['hash'])){
            Sc_Log::record("[tracker delete] bad hash parameter {$params['hash']}",  Sc_Log::ERROR);
            return Sc::T_BAD_PARAMETER;
        }
        
        
        if(isset($params['node']) && !Sc::checkNode($params['node'])){
            Sc_Log::record("[tracker delete] bad node parameter {$params['node']}",  Sc_Log::ERROR);
            return Sc::T_BAD_PARAMETER;
        }
        
        if($params['node'] == 'ALL'){
            $params['node'] = NULL; 
        }else{
            $nodes = array(Sc::getFromNode());
            if(isset($params['node'])){
                $nodes[] = $params['node'];
            }
        }
        
        return array('after'=> array(
            'hash'=>$params['hash'],
            'node'=>$nodes
        ));
    }
    
    /**
     * 删除之后调用(执行操作)
     * @param type $data
     */
    static public function afterDelete($data){
        if(empty($data)){
            return false;
        }
        $deleteData = array();
        foreach($data['node'] as $v){
            if(empty($v)) continue;
            $deleteData[] = array('hash'=>$data['hash'],'node'=>$v); 
        }
        if(!static::$_driver->delete($deleteData)){
            if(Sc_Log::enableLevel(Sc_Log::ERROR)){
                Sc_Log::record('[tracker after delete] failure,'.var_export($deleteData, true).static::$_driver->error(),Sc_Log::ERROR);
            }
            return false;
        }
        static::destoryDriver();
        return true;
    }
    
    /**
     * 
     * @param type $method
     * @param type $data
     * @return type
     */
    static public function callAfter($method,$data){
        $afterMethod = 'after'.ucfirst($method);
        if(method_exists(Sc_Tracker,$afterMethod)){
          return  static::$afterMethod(isset($data['after']) ? $data['after'] : NULL);
        }
    }
    
    /**
     * 销毁driver
     */
    static public function destoryDriver(){
        if(method_exists(static::$_driver,'close')){
            static::$_driver->close();
        }
        static::$_driver=NULL;
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
