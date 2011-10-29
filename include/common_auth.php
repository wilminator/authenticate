<?php
define('AUTH_INCLUDE_DIR','auth/');
if(strtolower($_SERVER["HTTP_HOST"])=='ccii.wilminator.com')
    define('AUTH_PASSWORD_DIR','/home/wilminat/public_html/ccii/cfg/');
else
    define('AUTH_PASSWORD_DIR','/home/wilminat/public_html/cfg/');

require_once AUTH_INCLUDE_DIR.'authentication.php';
?>
