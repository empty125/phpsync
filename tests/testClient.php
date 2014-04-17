<?php

require __DIR__.'/common.php';

class Test{
    
    static public function testFetch(){
       var_dump(Sc_Client::fetchFile('img.jpg'));
    }
    
    static public function testSave(){
       var_dump(Sc_Client::saveFile(__DIR__.'/meizhi.jpg'));
    }    
    
    static public function testDelete(){
       var_dump(Sc_Client::deleteFile('img.jpg'));
    }
    
}

//Test::testDelete();
Test::testFetch();