<?php
session_start();
//Authenticate this user.
require_once 'functions.php';
$userid=authenticate_admin_access('../login.php','../status.php');
$auth=get_auth();

//Test for the victimid.
$victimid=verify_victimid();

//Test for context.
$context=verify_context();

//Check that this account is not on hold in this context.
redirect_on_hold($userid,$context,'display_error.php','account_admin.php','display_error.php');

if(isset($_GET['confirm']))
    {
    $result=$auth->delete_other_account($userid,$victimid,$context);
    if(is_auth_error($result))
        header('Location: display_error.php?error='.rawurlencode($result->get_msg()));
    else
        header('Location: account_admin.php');
    exit;
    }

//Get the victim's name.
$name=$auth->get_username($victimid);
?>
<html>
<head>
<title>Delete User Account</title>
</head>
<body>
<table>
  <tr><th>
    <table>
      <caption><b>Are you sure you want to delete the account <?=$name?>?</b></caption>
      <tr>
        <td><a href="delete_account.php?victimid=<?=$victimid?>&confirm=YES">Yes, delete this account.</a></td>
        <td><a href="edit_account.php?victimid=<?=$victimid?>">Do not delete this account.</a></td>
      </tr>
    </table>
  </th></tr>
</table>
</body>
</html>
