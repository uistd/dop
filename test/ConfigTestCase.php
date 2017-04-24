<?php
$file = 'protocol/build.ini';
$config = parse_ini_file($file, true);
print_r($config);