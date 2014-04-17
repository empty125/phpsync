<?php
/**
 * 统一配置文件,所有节点包括nameserver的都要一样
 */
return array(
    //指定 nameserver 地址,地址加到nodes中,此节点即是nameserver又是节点
    'name_server'=>'192.168.1.102/phpsync',
    
    //所有节点信息 属性: 权重,路径;
    'nodes'=>array(
         '192.168.1.102'=>array('weight'=>1,'path'=>'/phpsync'),
         '192.168.1.114'=>array('weight'=>1,'path'=>'/phpsync')
     ),
         
    //nameserver专属配置,文件信息记录方式file[待实现],sqlite
    'save_type'=>'sqlite',
    
    //node之间文件传输方式,http,cmd[待实现,async ]
    'sync_type'=>'http',
    
    //日志级别 error,warn,notice,info,debug
    'log_level'=>'all'
);

