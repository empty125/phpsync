<?php
/**
 * Description of Sc_Sqlite
 * 字段
 * hash,文件名(包括后缀)hash
 * node,节点
 * fhash,文件hash
 * savetime,记录时间
 * suffix,后缀
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

    function __construct(){
        
    }
    
    /**
     * 局限性: hash 不是文件hash而是文件名(包括后缀)
     */
    public function create(){
        if(!$this->isConnected){
            $this->connect();
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
        //$this->_handle->busyTimeout(10);
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
        if(!$this->isConnected){
            $this->connect();
        }
        $statement = $this->_handle->prepare('INSERT INTO node_files(hash,node,savetime,suffix,fhash)VALUES(:hash,:node,:savetime,:suffix,:fhash)');
        if($statement === false) {
            return false;
        }
        $statement->bindValue(':hash',$this->_handle->escapeString($data['hash']));
        $statement->bindValue(':node',$this->_handle->escapeString($data['node']));
        $statement->bindValue(':fhash',$this->_handle->escapeString($data['fhash']));
        $statement->bindValue(':savetime',time());
        $statement->bindValue(':suffix',empty($data['suffix'])? '' : $this->_handle->escapeString($data['suffix']));
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
        if(!$this->isConnected){
            $this->connect();
        }
        if(empty($hash)){
            return false;
        }
        $statement = $this->_handle->prepare('SELECT * FROM node_files WHERE hash=:hash');
        if($statement === false) {
            return false;
        }
        $statement->bindValue(':hash',$this->_handle->escapeString($hash));
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
     * 获取所有
     * @return type
     */
    public function getAll(){
        if(!$this->isConnected){
            $this->connect();
        }
        $result = $this->_handle->query('SELECT * FROM node_files');
        $rows = array();
        while($res = $result->fetchArray(SQLITE3_ASSOC)){
            $rows[] = $res;
        }        
        $result->finalize();
        return $rows;
    }
    
    /**
     * 删除信息
     * @param type $hash
     * @param type $node
     * @return boolean
     */
    public function delete($hash,$node=NULL){
        if(!$this->isConnected){
            $this->connect();
        }
        if(empty($hash)){
            return false;
        }
        $sql = 'DELETE FROM node_files WHERE hash = :hash';
        if(!empty($node)){
            $sql.=' AND node = :node';
        }
        $statement = $this->_handle->prepare($sql);
        if($statement === false) {
            return false;
        }
        $statement->bindValue(':hash',$this->_handle->escapeString($hash));
        if(!empty($node)){
            $statement->bindValue(':node',$this->_handle->escapeString($node));
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
        if(!$this->isConnected){
            $this->connect();
        }
        $statement = $this->_handle->prepare('SELECT COUNT(*) AS count,savetime FROM node_files WHERE hash=:hash,node=:node');
        if($statement === false) {
            return false;
        }
        $statement->bindValue(':hash',$hash);
        $statement->bindValue(':node',$node);
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
        return $this->_handle->lastErrorMsg();
    }


    public function __destruct(){
        if(is_resource($this->handle)){
            $this->handle->close();
        }
    }
}
