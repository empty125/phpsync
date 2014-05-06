<?php
/**
 * nameserver 简单web管理 文件信息
 * 
 * 强烈建议将此文件名更改,如admin_formzuser.php
 * @author xilei
 */

//设置 admin key
define('SC_ADMIN_KEY','123456');

session_name('PHPSYNC_ADMIN_ID');
session_start();


require __DIR__.'/sc.php';

$adminfile = basename(__FILE__);

if(!Sc::checkIsNameServer()){
    $nameserver = Sc::getConfig('name_server');
    header("Location:http://{$nameserver}/$adminfile");
    exit();
}

Sc_Admin::module();

/**
 * admin class
 */
class Sc_Admin{
    
    static public $modules =array('auth','manager','error','quit');
    
    static public $currentModule = '';
    
    static public $viewData = array();
    
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


    static public function manager(){
        self::$viewData = array(
            array('hash'=>'82341a6c6f03e3af261a95ba81050c0a','node'=>'192.168.1.1','savetime'=>time(),'suffix'=>'.jpg'),
            array('hash'=>'82341a6c6f03e3af261a95ba81050c0a','node'=>'192.168.1.2','savetime'=>time(),'suffix'=>'.jpg')
        );
        self::$currentModule = 'manager';
    }
    
    static public function error($message,$code = NULL){
        if(!empty($code)){
            Sc_Util::sendHttpStatus($code);
        }
        self::$viewData = array(
            'message'=>$message
        );
        
        self::$currentModule = 'error';
    }
    
    static public function quit(){
        $_SESSION['adminkey'] = NULL;
        session_destroy();
        self::redirect('auth');
    }
    
    static public function delete(){
        
    }

    static public function isAuthModule($module){
        return $module == 'auth';
    }
    
    static public function redirect($module){
         header("Location:{$adminfile}?m=$module");exit;
    }

    static public function module(){
        $module = isset($_REQUEST['m']) && in_array($_REQUEST['m'], self::$modules) ?
        $_REQUEST['m'] : '' ;
        if(empty($module)){
            if(self::isAuth()){
                self::error('模块没有找到',404);
            }
            return ;
        }
        
        if(!self::isAuthModule($module) && !self::isAuth()){
             self::redirect('auth');
        }
        
        if(isset($_REQUEST['m'])) unset($_REQUEST['m']);
        call_user_func(array(self,$module), $_REQUEST);
       
    }
}
?><!DOCTYPE HTML>
<html lang="en-US">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" type="text/css" href="http://libs.baidu.com/bootstrap/3.0.3/css/bootstrap.css"/>
    <script src="http://libs.baidu.com/jquery/1.9.0/jquery.js"></script>
    <title>phpsync &middot; admin</title>
</head>
<body>
<div class="page-header">
    <?php if(Sc_Admin::isAuth()): ?><div class="container" style="text-align: right"><a href="<?php echo $adminfile."?m=quit"; ?> ">退出</a></div><?php endif; ?>
</div>
<div class="container">
<?php if(!Sc_Admin::isAuth() or Sc_Admin::$currentModule == 'auth'): ?>

<div class="well" style="text-align:center">
 <form class="form-inline" method="post">
     <div class="form-group">
         <input type="password" class="form-control"  placeholder="admin key" name="adminkey"/>
     </div>
     <input type="hidden" name="m" value="auth"/>
     <button type="submit"  class="btn btn-primary">Enter</button>
 </form>
 </div>
<?php elseif (Sc_Admin::$currentModule == 'manager'): ?>
<div class="panel panel-primary">
    <div class="panel-heading">tracker文件记录管理</div>
    <div class="panel-body">
        <div style="text-align:center">
        <form class="form-inline" >
          <div class="form-group">
            <input type="text" class="form-control" placeholder="name">
          </div>
          <div class="form-group">
            <input type="text" class="form-control" placeholder="node">
          </div>
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
       <?php foreach (Sc_Admin::$viewData as $item): ?>
       <tr>
         <td><?php echo $item['hash']; ?></td>
         <td><?php echo $item['node']; ?></td>
         <td><?php echo empty($item['savetime']) ? '': date('Y-m-d H:i:s',$item['savetime']); ?></td>
         <td><?php echo $item['suffix']; ?></td>
         <td><a href="javascript:void(0)" class="del">delete</a></td>
       </tr>  
       <?php endforeach; ?>
     </tbody>
   </table>
   <div style="margin-right:10px;margin-left: 10px;"><ul class="pager">
  <li class="previous"><a href="#">prev</a></li>
  <li class="next"><a href="#">next</a></li>
   </ul></div>
        
</div>
<?php elseif(Sc_Admin::$currentModule == 'error'): ?>
    <div class="alert alert-danger"><?php
    echo empty(Sc_Admin::$viewData['message']) ? '' : Sc_Admin::$viewData['message']; ?>
        <a href="<?php echo $adminfile.(Sc_Admin::isAuth() ? '?m=manager' : '?m=auth')  ?>">返回</a></div>
<?php else:?>
    
<?php endif;?>
</div>
</body>
</html>
 