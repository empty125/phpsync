<?php
/**
 * Description of Sc_Client
 *
 * @author xilei
 */
class Sc_Client {
    
    /**
     * 查询tracker
     */
    static private function _dotracker($method,$params=NULL){
        $result = array();
        if(Sc::checkIsNameServer()){
            $_data = call_user_func(array(Sc_Tracker,$method),$params);
            if(is_array($_data)){
                Sc_Tracker::callAfter($method, $_data);
            }else{
                return array('code'=>$_data);
            }
            $result = array('code'=>  Sc::T_SUCCESS);
            if(isset($_data['data'])){
                $result['data'] = $_data['data'];
            }
        }else{
            $result  =Sc::unpack(Sc_Util::http(Sc::buildUrl('tracker',$method,$params)));
        }
        return $result;        
    }
    
    /**
     * 获取文件
     * @param type $name
     * @param type $nnode
     * @return boolean
     */
    static public function fetchFile($name){
        if(empty($name)){
            return false;
        }
        //先检查本地是否存在
        $savefile = Sc_Storage::exists($name);
        if($savefile != false){
            return $savefile;
        }
        
        //请求tracker
        $result = static::_dotracker('search',array('name'=>$name));
        if(!isset($result['code'])|| empty($result['data']) || $result['code'] != Sc::T_SUCCESS){
            return false;  
        }
       
        //请求storage 同步文件 @todo 考虑是否检查node为此节点
        $data = $result['data'];
        $hashname = $data['hash'].$data['suffix'];
        
        if(Sc_Log::enableLevel(Sc_Log::DEBUG)){
            Sc_log::record("[client fetchFile] get file from node {$data['node']},hash: {$data['hash']}",  Sc_Log::DEBUG);
        }
        
        $result = Sc_Storage::syncFile($data['node'], $hashname);
        //同步失败
        if(!isset($result['file'])){  
            $deleteParams = array('hash'=>$data['hash']);
            if($result == Sc::S_SYNC_FAILED){
               //删除原来节点的数据
               $deleteParams['node'] = $data['node'];
            }   
            Sc_Storage::delete($name);
            static::_dotracker('delete',$deleteParams);
            return false;
        }
       
        //检查fhash
        if(Sc::hash($result['file'],true) !=$data['fhash']){
            Sc_Storage::delete($name);
            static::_dotracker('delete',array('hash'=>$data['hash']));
            return false;
        }
        
        return $result['file'];
    }
    
    /**
     * 添加文件到此节点
     * @param type $filename
     * @param type $name
     * @return boolean
     */
    static public function saveFile($filename,$name=''){
        if(!file_exists($filename)){
            return false;
        }
        $result = Sc_Storage::saveFile($filename,$name);
        if(!is_array($result)){
            return false;
        }
        static::_dotracker('add',array(
             'hash'=>$result['hash'],
           'suffix'=>$result['suffix'],
            'fhash'=>Sc::hash($result['file'],true)
        ));
        return true;
    }
    
    /**
     * 删除此节点文件
     * @param type $name
     * @return boolean
     */
    static public function deleteFile($name){
        if(empty($name)){
            return false;
        }
        Sc_Storage::delete($name);
        static::_dotracker('delete',array(
            'hash'=>Sc::hash($name)
        ));
        return true;
    }

}
