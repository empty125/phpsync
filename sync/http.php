<?php
/**
 * Description of Sc_Sync_Http
 * @author xilei
 */
class Sc_Sync_Http {
    
    private $_error = '';
    
    /**
     * 将被Sc_Stroage添加
     * @var type 
     */
    public  $remote = '';
    /**
     * 获取文件
     * @param type $remote
     * @param type $savename
     * @return boolean
     */
    public function download($node,$savename){
        $cp = curl_init($this->remote);
        $fp = fopen($savename,"wb");
        curl_setopt_array($cp, array(
            CURLOPT_FILE=>$fp,
            CURLOPT_HEADER=>0
        ));
        curl_exec($cp);
        if(curl_errno($cp)){
            $this->_error = curl_error($cp);
            return false;
        }
        curl_close($cp);
        fclose($fp);
        return true;
    }
    
    /**
     * 发送文件
     * @param type $filename
     * @return boolean
     */
    public function sendFile($filename,$node){
        Sc_Util::setDownloadHeader(basename($filename),filesize($filename));
        ob_clean();
        flush();
        readfile($filename);
        return true;
    }
    
    /**
     * 错误
     * @return type
     */
    public function error(){
        return $this->_error;
    }
}
