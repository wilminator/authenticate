<?php
if(!function_exists('generate_traceback'))
    exit;

$result=generate_traceback(1);
"$line[file]:$line[line] $line[function]($args)\r\n";
preg_match('/$(.+?):.+? (.+?)\(/',$result[0],$grep);
list($junk,$file,$function)=$grep;

var_dump($grep);
exit;

#DB data
$db_host='localhost';
$db_name='authenticate';
$db_pass='t0Tal_AuthEnT1cAtIoN';
$database='authentication';

#SMTP data
$smtp_host='66.163.171.161';
$smtp_user='wilminator@wilminator.com';
$smtp_pass='problemex';
$from_name='The Wilminator';
$from_addr='wilminator@wilminator.com';
?>
