<?php
/**
 * 统一配置文件,所有节点包括nameserver的都要一样
 */
return array(
    //指定 nameserver 地址,地址加到nodes中,此节点即使nameserver又是节点
    'name_server'=>'127.0.0.1/phpsync',
    
    //所有节点信息 属性: 权重,路径;
    'nodes'=>array(
         '127.0.0.1'=>array('weight'=>1,'path'=>'/phpsync')
     ),
         
    //nameserver专属配置,文件信息记录方式file[待实现],sqlite
    'save_type'=>'sqlite',
    
    //node之间文件传输方式,http,cmd[待实现,async ]
    'sync_type'=>'http',
    
    //日志级别
    'log_level'=>'all'
);

