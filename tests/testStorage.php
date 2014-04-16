<?php

require __DIR__.'/common.php';

/**
 * syncFile和saveFile是在不用的用途上,实现方式也是不同的
 */
class Test{
    
  static public function saveFile(){
      Sc_Storage::saveFile('http://img0.bdstatic.com/img/image/shouye/lyjiuzhaig.jpg','saveimg.jpg');
  }  
  
  
  static public function syncFile(){
      Sc_Storage::syncFile('http://img0.bdstatic.com/img/image/shouye/lyjiuzhaig.jpg','8b06645c18917c54c2668085c0f8cf94.jpg');
  }


  static public function download(){
      if(Sc_Storage::download(array('hashname'=>'8b06645c18917c54c2668085c0f8cf93.jpg'))<0){
          Sc_Util::sendHttpStatus(404);
      }
  }
}

//Test::saveFile();
Test::download();