<?php
if(!function_exists('generate_traceback'))
    exit;

$result=explode("\r\n",generate_traceback(1));

preg_match('#([^/]+?):.+? (.+?)\(#',$result[0],$grep);
list($junk,$file,$function)=$grep;

if($file!=='authentication.php' || $function !=='require')
    exit;


