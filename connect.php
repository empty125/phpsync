<?php
/**
 * 处理节点间请求
 * 检查节点是否合法
 * 路由tracker和node请求
 * 设置日志级别
 */

require __DIR__.'/sc.php';

Sc_Log::$suffix = 'from '.Sc::getFromNode();
Sc_Log::setLevels(Sc::getConfig('log_level'));

//$route = filter_input(INPUT_GET, 'r');
$data =Sc_Tracker::search(array(
    'name'=>'a.jpg',
    'nnode'=>'127.0.0.1'
));
print_r($data);
//Sc_Tracker::afterSearch($data['after']);
/*
$data = Sc_Tracker::add(array(
    'name'=>'a.jpg',
    'node'=>'192.168.2.1',
    'fhash'=>Sc::hash('a'))
);

Sc_Tracker::afterAdd($data['after']);
*/
//检查请求是否来自配置的节点



