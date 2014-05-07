<?php
require 'common.php';
require Sc::$rootDir.'/driver/file.php';

class Test{
    
    static public $d = NULL;
    
    static public function testAdd(){
        if(empty(self::$d)){
            self::$d = new Sc_Driver_File();
        }
        self::$d->add(array(
            'hash'=>'hash1',
            'node'=>'192.168.1.1',
            'fhash'=>'hashfile1'
         ));
        self::$d->add(array(
            'hash'=>'hash1',
            'node'=>'192.168.1.2',
            'fhash'=>'hashfile2'
        ));
    }
    
    static public function testGet(){
        if(empty(self::$d)){
            self::$d = new Sc_Driver_File();
        }
        var_dump(self::$d->get('hash1'));
    }
    
    static public function testDel(){
        if(empty(self::$d)){
            self::$d = new Sc_Driver_File();
        }
        var_dump(self::$d->delete('82341a6c6f03e3af261a95ba81050c0a'));
    }
    
}
//$a = 1/0;
Test::testDel();
