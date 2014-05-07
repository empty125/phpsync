<?php
/**
 * nameserver 简单web管理 文件信息
 * 
 * 强烈建议将此文件名更改,如:admin_536995c42d4ca.php
 * @author xilei
 */
;
//设置 admin key
define('SC_ADMIN_KEY','123456');

session_name('PHPSYNC_ADMIN_ID');
session_start();

require __DIR__.'/sc.php';

define('SC_INDEXFILE', basename(__FILE__));

Sc_Admin::init();

Sc_Admin::module();

/**
 * admin class
 */
class Sc_Admin{
    
    static public $modules =array('auth','manager','error','quit','syncFile');
    
    static public $currentModule = '';
    
    static public $viewData = array();
    
    /**
     * 认证
     * @param type $params
     */
    static public function auth($params){
        if(isset($params['adminkey']) && trim($params['adminkey']) === SC_ADMIN_KEY){
            $_SESSION['adminkey'] = hash('md5', $params['adminkey']);
            self::redirect('manager');
        }
        self::$currentModule = 'auth';
    }
    
    static public function isAuth(){
        return isset($_SESSION['adminkey']);
    }

    /**
     * 管理主页
     */
    static public function manager(){
        self::$viewData = array(
            array('hash'=>'82341a6c6f03e3af261a95ba81050c0a','node'=>'192.168.1.1','savetime'=>time(),'suffix'=>'.jpg'),
            array('hash'=>'82341a6c6f03e3af261a95ba81050c0a','node'=>'192.168.1.2','savetime'=>time(),'suffix'=>'.jpg')
        );
        self::$currentModule = 'manager';
    }
    
    /**
     * 错误页
     * @param type $message
     * @param type $code
     */
    static public function error($message,$code = NULL){
               
        if(!empty($code)){
            Sc_Util::sendHttpStatus($code);
        }
        self::$viewData = array(
            'message'=>$message
        );
        self::$currentModule = 'error';
    }
    
    /**
     * 退出
     */
    static public function quit(){
        $_SESSION['adminkey'] = NULL;
        session_destroy();
        self::redirect('auth');
    }
    
    /**
     * 删除记录
     */
    static public function delete(){
        
    }
    
    /**
     * 同步文件(节点)
     */
    static public function syncFile(){
        
    }
    
    /**
     * 检查权限
     * @return boolean
     */
    static public function checkAccess(){
        $nameserver= Sc::getConfig('name_server');
        if(self::$currentModule == 'syncFile'){
            $client = Sc_Util::get_client_ip();
            if(!empty($client)&&!empty($nameserver)&&Sc::checkIsNameServer($client)){
                return;
            }else{
                Sc_Util::sendHttpStatus(403);exit;
            }
        }
        
        if(!Sc::checkIsNameServer()) {
            header("Location:http://{$nameserver}/".SC_INDEXFILE);
            exit();
        }
        
        if(self::$currentModule != 'auth'){
            if(!self::isAuth()){
                self::redirect('auth');
            }elseif(empty(self::$currentModule)){
                self::redirect('manager');                
            }elseif(!in_array(self::$currentModule, self::$modules)){
                self::error('模块没有找到',404);
            }
        }
    }
    

    static public function redirect($module){
        $target = SC_INDEXFILE."?m={$module}";
        if(!headers_sent()){
            header("Location:".$target);
        }else{
            echo "<!DOCTYPE html><html><head><meta charset='utf-8'/><meta http-equiv='Refresh' content='0;URL={$target}'></head></html>";
        }
        exit;
    }
    
    static public function init(){
        self::$currentModule =isset($_REQUEST['m']) ? $_REQUEST['m'] : '';
    }

    static public function module(){      
        self::checkAccess();
        
        if(self::$currentModule!='error'){
            if(isset($_REQUEST['m'])) unset($_REQUEST['m']);
            call_user_func(array(self,self::$currentModule), $_REQUEST);
        }
    }
    
    
    static public function driver(){
        
    }
}
?><!DOCTYPE HTML>
<html lang="cn-ZH">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" type="text/css" href="http://libs.baidu.com/bootstrap/3.0.3/css/bootstrap.min.css"/>    
    <title>phpsync &middot; admin</title>
</head>
<body>
<div class="page-header">
    <?php if(Sc_Admin::isAuth()): ?><div class="container" style="text-align: right"><a href="<?php echo $adminfile."?m=quit"; ?> ">退出</a></div><?php endif; ?>
</div>
<div class="container">
<?php if(!Sc_Admin::isAuth() or Sc_Admin::$currentModule == 'auth'): ?>

<div class="well" style="text-align:center">
    <form class="form-inline" method="post" action="">
     <div class="form-group">
         <input type="password" class="form-control"  placeholder="admin key" name="adminkey"/>
     </div>
     <input type="hidden" name="m" value="auth"/>
     <button type="submit"  class="btn btn-primary">Enter</button>
 </form>
 </div>
<?php else:?>
<script src="http://libs.baidu.com/jquery/2.0.0/jquery.min.js"></script>
<?php if (Sc_Admin::$currentModule == 'manager'): ?>
<div class="panel panel-primary">
    <div class="panel-heading">tracker文件记录管理</div>
    <div class="panel-body">
        <div style="text-align:center">
            <form class="form-inline" method="get" action="" >
          <div class="form-group">
            <input type="text" class="form-control" placeholder="name">
          </div>
          <div class="form-group">
            <input type="text" class="form-control" placeholder="node">
          </div>
           <input type="hidden" name="m" value="manager"/>
          <button type="submit" class="btn btn-default">search</button>
        </form>
        </div>
    </div>
     <table class="table table-hover">
     <thead>
       <tr class="success">
         <th>hash</th>
         <th>node</th>
         <th>time</th>
         <th>suffix</th>
         <th>#</th>
       </tr>
     </thead>
     <tbody>
       <?php if(!empty(Sc_Admin::$viewData)): foreach (Sc_Admin::$viewData as $item): ?>
       <tr>
         <td><?php echo $item['hash']; ?></td>
         <td><?php echo $item['node']; ?></td>
         <td><?php echo empty($item['savetime']) ? '': date('Y-m-d H:i:s',$item['savetime']); ?></td>
         <td><?php echo $item['suffix']; ?></td>
         <td><a href="javascript:void(0)" class="del">delete</a></td>
       </tr>  
       <?php endforeach;else: ?>
       <tr><td colspan="5" style="text-align: center;color:silver;">没有文件记录。</td></tr>
       <?php endif; ?>
     </tbody>
   </table>
   <div style="margin-right:10px;margin-left: 10px;"><ul class="pager">
    <li class="previous"><a href="#">prev</a></li>
    <li class="next"><a href="#">next</a></li>
   </ul></div>
        
</div>
<?php elseif(Sc_Admin::$currentModule == 'error'): ?>
<div class="alert alert-danger"><?php
    echo (empty(Sc_Admin::$viewData['message']) ? '' : Sc_Admin::$viewData['message']); ?>
    <a href="<?php echo SC_INDEXFILE.(Sc_Admin::isAuth() ? '?m=manager' : '?m=auth')  ?>">返回</a></div>
<?php else:?>
    
<?php endif;endif;?>
</div>
</body>
</html>
 