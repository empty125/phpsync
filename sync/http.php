<?php
/**
 * Description of Sc_Http
 * @author xilei
 */
class Sc_Http {
    
    public function download($remote,$savename){
        $cp = curl_init($remote);
        $fp = fopen($savename,"wb");
        curl_setopt($cp, CURLOPT_FILE, $fp);
        curl_setopt($cp, CURLOPT_HEADER, 0);
        curl_exec($cp);
        if(curl_errno($cp)){
            return false;
        }
        curl_close($cp);
        fclose($fp);
        return true;
    }
    
    public function sendFile($filename){
        Sc_Util::setDownloadHeader(basename($filename),filesize($filename));
        ob_clean();
        flush();
        readfile($filename);
    }
}
