<?php
/**
 * Description of Sc_Sqlite
 * 字段
 * hash,文件名(包括后缀)hash
 * node,节点
 * fhash,文件hash
 * savetime,记录时间
 * suffix,后缀
 * 
 * @todo sqlite并发 database is locked 问题，读写优化
 * @author xilei
 */
class Sc_Sqlite {
    /**
     * 标记是否连接初始化
     * @var type 
     */
    protected $isConnected = false;
    
    /**
     * sqlite3 instance
     * @var type 
     */
    protected $_handle = NULL;
    
    private $_error = NULL;

    function __construct(){
        $this->connect();
    }
    
    /**
     * 局限性: hash 不是文件hash而是文件名(包括后缀)
     */
    public function create(){
        if(!$this->isConnected){
            $this->connect(SQLITE3_OPEN_READWRITE|SQLITE3_OPEN_CREATE);
        }
        $this->_handle->exec('CREATE TABLE node_files(hash TEXT NOT NULL,node TEXT NOT NULL,'
                . 'fhash TEXT NOT NULL,savetime INT,suffix TEXT, PRIMARY KEY(hash,node))');
        $this->_handle->exec('PRAGMA encoding=UTF8');
    }

    
    /**
     * 连接
     * @param type $mode
     * @throws Exception
     */
    public function connect($mode = SQLITE3_OPEN_READWRITE){
        if(!extension_loaded('sqlite3')){
            Sc_Log::record('must be sqlite3 extension');
            throw new Exception('must be sqlite3 extension',-100);
        }
        $dbfile = Sc::$rootDir.'/data/sys/phpsync.db';
        $this->_handle = new SQLite3($dbfile,$mode);  
        $this->isConnected=true;
        ////auto_vacuum = 1
        $this->_handle->busyTimeout(3000);//并发问题
    }
    
    /**
     * 添加节点文件对应信息
     * @param type $data
     *  array(
     *      'hash'=>'',
     *      'node'=>'',
     *      'fhash'=>'' 
     *      'savetime'=>0,
     *      'suffix'=>'',
     *  )
     * @return boolean
     */
    public function add($data){
        $statement = $this->_handle->prepare('INSERT INTO node_files(hash,node,savetime,suffix,fhash)VALUES(:hash,:node,:savetime,:suffix,:fhash)');
        if($statement === false) {
            return false;
        }
        $statement->bindValue(':hash',$this->_handle->escapeString($data['hash']),SQLITE3_TEXT);
        $statement->bindValue(':node',$this->_handle->escapeString($data['node']),SQLITE3_TEXT);
        $statement->bindValue(':fhash',$this->_handle->escapeString($data['fhash']),SQLITE3_TEXT);
        $statement->bindValue(':savetime',time(),SQLITE3_INTEGER);
        $statement->bindValue(':suffix',empty($data['suffix'])? '' : $this->_handle->escapeString($data['suffix']),SQLITE3_TEXT);
        $result = $statement->execute();
        $statement->close();
        return $result!==false;
    }
    
    /**
     * 根据文件名hash获取文件的分布信息
     * @param type $hash
     * @return boolean
     */
    public function get($hash){
        if(empty($hash)){
            $this->_error='hash is required';
            return false;
        }
        $statement = $this->_handle->prepare('SELECT * FROM node_files WHERE hash=:hash');
        if($statement === false) {
            return false;
        }
        $statement->bindValue(':hash',$this->_handle->escapeString($hash),SQLITE3_TEXT);
        $result = $statement->execute();
        
        $rows = array();
        while($res = $result->fetchArray(SQLITE3_ASSOC)){ 
            $rows[] = $res;
        }
        
        $result->finalize();
        $statement->close();
        return $rows;        
    }
    
    /**
     * 获取部分列表
     * @return type
     */
    public function getPage($start=0,$limit=10){
        $statement = $this->_handle->prepare('SELECT * FROM node_files LIMIT :limit OFFSET :start');
        if($statement === false) {
            return false;
        }
        $statement->bindValue(':start',$start,SQLITE3_INTEGER);
        $statement->bindValue(':limit',$limit,SQLITE3_INTEGER);
        $result = $statement->execute();
        
        $rows = array();
        while($res = $result->fetchArray(SQLITE3_ASSOC)){
            $rows[] = $res;
        }        
        $result->finalize();
        $statement->close();
        return $rows;
    }
    
    /**
     * 删除信息
     * @param string|array $hash 
     * @param type $node
     * [$hash,$node]
     * [array('hash'=>'','node'=>'')]
     * [array(array('hash'=>'','node'=>''),array('hash'=>'','node'=>''),...)]
     * @return boolean
     */
    public function delete($hash,$node=NULL){
        if(empty($hash)){
            $this->_error='hash is required';
            return false;
        }
        $condition = array();
        if(is_string($hash)){
            $condition[]['hash'] = $hash;
            if($node!==NULL){
                $condition[]['node'] = $node;
            }
        }else{
            $condition = isset($hash['hash']) ? array($hash) : $hash;
        }
        $sql = 'DELETE FROM node_files WHERE ';
        
        $conditionstr = array();
        $i = 0;
        $bindData = array();
        foreach($condition as $con){
            if(empty($con)){
                continue;
            }
            $temp = array();
            foreach($con as $k=>$v){
                $temp[] = "{$k} = :param{$i}";
                $bindData[":param{$i}"]=$v;
                $i++;
            }
            $conditionstr[] = "(".implode(' AND ', $temp).")";
        }
        $sql.=implode(' OR ', $conditionstr);
        unset($con,$condition,$conditionstr);
        $statement = $this->_handle->prepare($sql);
        if($statement === false) {
            return false;
        }
        foreach($bindData as $k=>$v){
            $statement->bindValue($k, $v,SQLITE3_TEXT);//这里只能用bindValue
        }
       
        $result = $statement->execute();
        $statement->close();
        return $result!==false;
    }
    
    /**
     * 检查是否存在
     * @param type $hash
     * @param type $node
     * @return boolean
     */
    public function exists($hash,$node){
        if(empty($hash)){
             $this->_error='hash is required';
             return -1;
        }
        if(empty($node)){
             $this->_error='node is required';
             return -1;
        }
        $statement = $this->_handle->prepare('SELECT COUNT(*) AS count FROM node_files WHERE hash=:hash AND node=:node');
        if($statement === false) {
            return -1;
        }
        $statement->bindValue(':hash',$hash,SQLITE3_TEXT);
        $statement->bindValue(':node',$node,SQLITE3_TEXT);
        $result = $statement->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        $result->finalize();
        $statement->close();
        return isset($row['count']) && $row['count']>0;
    }

    /**
     * 获取错误信息
     * @return type
     */
    public function error(){
        $error = $this->_handle->lastErrorMsg();
        if(!$this->_handle->lastErrorCode()){
            $error = $this->_error;
        }
        $this->close();
        return $error;
    }
    
    public function close(){
       if(!empty($this->_handle)){
           $this->_handle->close();
           $this->_handle=NULL;
       }
    }

    public function __destruct(){
       $this->close();
    }
}
