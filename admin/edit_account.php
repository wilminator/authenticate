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

//Get the victim's name.
$name=$auth->get_username($victimid);

#Check if this account is a system account.
$is_system=$auth->is_system_account($victimid);

#Now check for update privileges
$can_update=check_permission($userid,$context,'UPDATE_ACCOUNTS','display_error.php');

#Get hold status.
if($can_update)
    {
    $has_hold=$auth->get_hold($userid,$victimid,$context);
    $hold_action=$has_hold!=false?'Release hold':'Place hold';
    $hold_link=$has_hold!=false?'unhold_account.php':'hold_account.php';
    }

#Now check for alter permissions privileges
$can_alter_p=check_permission($userid,$context,'ALTER_PERMISSIONS','display_error.php');

#Now check for access data privileges
$can_access=check_permission($userid,$context,'ACCESS_OTHERS_DATA','display_error.php');

#Now check for alter permissions privileges
$can_alter_d=check_permission($userid,$context,'ALTER_OTHERS_DATA','display_error.php');

#Now check for delete privileges
if($is_system===true)
    $can_delete=check_permission($userid,$context,'DELETE_ACCOUNTS','display_error.php');
else
    $can_delete=check_permission($userid,$context,'CREATE_SYSTEM_ACCOUNTS','display_error.php');
?>
<html>
<head>
<title>Edit User Account</title>
</head>
<body>
<table>
  <caption><b><?=$name?></b></caption>
<?php
if($can_update)
    echo"
  <tr><td><a href=\"change_password.php?victimid=$victimid\">Change password</a></td></tr>
  <tr><td><a href=\"cancel_email.php?victimid=$victimid\">Cancel email change</a></td></tr>
  <tr><td><a href=\"cancel_reset.php?victimid=$victimid\">Cancel account reset</a></td></tr>
  <tr><td><a href=\"boot_account.php?victimid=$victimid\">Boot account</a></td></tr>
  <tr><td><a href=\"<?=$hold_link?>?victimid=$victimid\">$hold_action</a></td></tr>
";
if($can_alter_p)
    echo"
  <tr><td><a href=\"edit_permissions.php?victimid=$victimid\">Edit permissions</a></td></tr>
";
if($can_access || $can_alter_d)
    echo"
  <tr><td><a href=\"account_data.php?victimid=$victimid\">Edit data</a></td></tr>
";
if($can_delete)
    echo"
  <tr><td><a href=\"delete_account.php?victimid=$victimid\">Delete account</a></td></tr>
";
?>
  <tr><td><a href="account_admin.php">Return to the account list.</a></td></tr>
</table>
</body>
</html>
