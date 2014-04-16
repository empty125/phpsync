<?php
require __DIR__.'/../sc.php';

Sc_Log::$suffix = 'from '.Sc::getFromNode();
Sc_Log::setLevels(Sc::getConfig('log_level'));

Sc_Storage::setMod(0777);
