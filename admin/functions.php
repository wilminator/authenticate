<?php
require_once 'common_auth.php';

function verify_victimid()
    {
    if(!isset($_GET['victimid']))
        {
        header('Location: account_admin.php');
        exit;
        }
    return $_GET['victimid'];
    }

function verify_context()
    {
    if(!isset($_SESSION['context']))
        {
        header('Location: account_admin.php');
        exit;
        }
    return $_SESSION['context'];
    }
    
function fix_permissions_list($permissions)
    {
    $retval=array();
    foreach($permissions as $item)
        $retval[]="$item[context].$item[permission]";
    return $retval;
    }
?>
