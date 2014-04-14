<?php
/**
 * Description of Sc_Http
 * @author xilei
 */
class Sc_Http {
    
    private $_error = '';
    
    /**
     * 获取文件
     * @param type $remote
     * @param type $savename
     * @return boolean
     */
    public function download($remote,$savename){
        $cp = curl_init($remote);
        $fp = fopen($savename,"wb");
        curl_setopt($cp, CURLOPT_FILE, $fp);
        curl_setopt($cp, CURLOPT_HEADER, 0);
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
    public function sendFile($filename){
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
