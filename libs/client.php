<?php

/**
 * Description of Sc_Client
 *
 * @author xilei
 */
class Sc_Client {
    
    static private $_nodes = NULL;
    
    /**
     * build tracker 请求url
     * @param type $method
     * @param type $params
     * @return string
     */
    static private function trackerUrl($method,$params=NULL){
        $url = "http://".Sc::getConfig("name_server")."/connect.php?r=tracker.{$method}";
        if(!empty($params)){
           $url.= '&'.http_build_query($params);
        }
        return $url;
    }
    
    /**
     * build storage 请求url
     * @param type $node
     * @param type $method
     * @param type $params
     * @return string
     */
    static private function storageUrl($node,$method,$params=NULL){
        if(static::$_nodes == NULL){
            static::$_nodes = Sc::getConfig('nodes');
        }
        $url = "http://".$node.(isset(static::$_nodes[$node]['path']) ? "/".static::$_nodes[$node]['path']: "")."/connect.php?r=storage.{$method}";
        if(!empty($params)){
           $url.='&'.http_build_query($params);
        }
        return $url;
    }
    
    /**
     * 查询tracker
     */
    static public function tracker($method,$params){
        $result = array();
        if(Sc::checkIsNameServer()){
            $_data = Sc_Tracker::search($params);
            if(is_array($result)){
                Sc_Tracker::callAfter($method, $result);
            }
            $result = array('code'=>  Sc::T_SUCCESS);
            if(isset($_data['data'])){
                $result['data'] = $_data['data'];
            }
        }else{
            $result  =Sc::unpack(static::query(static::trackerUrl($method,$params)));
        }
        return $result;        
    }

    static public function fetchFile($name,$nnode = NULL){
        if(empty($name)){
            return false;
        }
        //检查本地是否存在
        $savefile = Sc_Storage::exists($name);
        if($savefile != false){
            return $savefile;
        }
        //请求tracker
        $result = static::search(array('name'=>$name));
        if(!isset($result['code']) || $result['code'] != Sc::T_SUCCESS){
            return false;  
        }
        
        //请求storage 同步文件
        $data = $result['data'];
        $hashname = $data['hash'].$data['suffix'];
        $result = Sc_Storage::syncFile(static::storageUrl($data['node'], 'download',array(
            'hashname'=>$hashname
            )
        ), $hashname);
        
        //同步失败
        if(!isset($result['file'])){
            if($result != Sc::S_FAILURE){
                return false;
            }
            //
            
        }
        
        
    }
    
    static public function query($remote){  
        $ch = curl_init($remote);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if(curl_error($ch)){
            return false;
        }
        $content = curl_exec($ch);
        curl_close($ch);
        return $content;
    }
    
}
