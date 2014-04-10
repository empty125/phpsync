<?php

header("WWW-Authenticate:Basic realm='a key'");
header('HTTP/1.0 401 Unauthorized');
echo "You are unauthorized to enter this page.";

