<?php
/**
 * 统一配置文件,所有节点包括nameserver的都要一样
 */
return array(
    'name_server'=>'127.0.0.1',
    
    'nodes'=>array(
       '192.168.2.1'=>array('weight'=>1,'path'=>'/phpsync'),
       '192.168.2.2'=>array('weight'=>1,'path'=>'/phpsync'),   
         '127.0.0.1'=>array('weight'=>1,'path'=>'/phpsync')
     ),
         
    'save_type'=>'sqlite',
         
    'connect_type'=>'http'
);

