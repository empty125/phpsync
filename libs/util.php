<?php
/**
 * Description of Sc_Util
 *
 * @author xilei
 */
class Sc_Util {
    
    /**
     * HTTP download header
     * @param type $filename 文件名
     * @param type $filesize 文件大小
     * @param type $hasHz 是否为中文
     */
   static  public function setDownloadHeader($filename,$filesize,$hasHz=false){
        header("Content-type:application/octet-stream");
        header("Accept-Ranges:bytes");
        if(!empty($filesize)){
            header("Accept-length:$filesize");
        }
        if($hasHz){
            $ua = $_SERVER["HTTP_USER_AGENT"];
            if(preg_match('/Firefox/i', $ua)) {
                header('Content-Disposition: attachment; filename*="utf8\'\'' . $filename . '"');
            } elseif (preg_match('/Chrome/i', $ua)) {
                header("Content-Disposition:attachment;filename=" . ($filename));
            } else {
                header("Content-Disposition:attachment;filename=" . urlencode($filename));
            }
        }else{
            header("Content-Disposition:attachment;filename=" .$filename);
        }
        header("Content-Transfer-Encoding: binary");
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
    }
   
    /**
     * HTTP 状态吗
     * @staticvar array $_status
     * @param type $code
     */
   static public  function sendHttpStatus($code) {
        static $_status = array(
            // Informational 1xx
            100 => 'Continue',
            101 => 'Switching Protocols',

            // Success 2xx
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',

            // Redirection 3xx
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',  // 1.1
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            // 306 is deprecated but reserved
            307 => 'Temporary Redirect',

            // Client Error 4xx
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested Range Not Satisfiable',
            417 => 'Expectation Failed',

            // Server Error 5xx
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported',
            509 => 'Bandwidth Limit Exceeded'
        );
        if(isset($_status[$code])) {
            header('HTTP/1.1 '.$code.' '.$_status[$code]);
        }
    }
    
    /**
     * @todo 代理
     */
   static public function get_client_ip() {       
        return isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : '';    
   }
   
   
   /**
    * http 
    * @param type $remote
    * @param type $PostParams post参数
    * @return boolean
    */
   static public function http($remote,$PostParams=array()){
        if(Sc_Log::enableLevel(Sc_Log::DEBUG)){
            Sc_Log::record("[http] query url:{$remote} ",  Sc_Log::DEBUG);
        }
        $ch = curl_init($remote);
        curl_setopt_array($ch, array(
            CURLOPT_HEADER=>0,
            CURLOPT_RETURNTRANSFER=>1,
            CURLOPT_TIMEOUT=>3
        ));
        if(!empty($PostParams)){
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($PostParams));
        }
        if(curl_errno($ch)){
            Sc_Log::record("[http] failure,".curl_error($ch),  Sc_Log::ERROR);
            return false;
        }
        $content = curl_exec($ch);
        if(empty($content) && Sc_Log::enableLevel(Sc_Log::NOTICE)){
            $info = curl_getinfo($ch);
            Sc_Log::record("[http]remote server {$info['url']} response code {$info['http_code']}",  Sc_Log::NOTICE);
        }
        curl_close($ch);
        return $content;
   }
    
}
