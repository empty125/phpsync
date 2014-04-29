<?php

require 'common.php';

require Sc::$rootDir.'/driver/file.php';
$d = new Sc_Driver_File();
var_dump(defined('SC_DRIVER_FILE_KEY'));
$d->add(array(
   'hash'=>'hash1',
   'node'=>'192.168.1.1',
   'fhash'=>'hashfile1'
));
var_dump($d->get('hash1'));
echo $d->error();