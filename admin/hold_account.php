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

if(isset($_POST['reason']))
    {
    $result=$auth->set_hold($userid,$victimid,$context,$_POST['reason']);
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
<title>Hold Account</title>
</head>
<body>
<form method="post" action="hold_account.php?victimid=<?=$victimid?>">
<table>
  <tr>
    <th>Hold Reason</th>
    <td><input name="reason" size="64" maxlength="64"></td>
  </tr>
  <tr><td><input type="submit" value="Place Hold"></td></tr>
</table>
<p><a href="edit_account.php?victimid=<?=$victimid?>">Do not place this account on hold.</a></p>
</form>
</body>
</html>
