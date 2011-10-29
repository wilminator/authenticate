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

if(isset($_POST['pass']))
    {
    if($_POST['pass']!=$_POST['pass2'])
            header('Location: display_error.php?error=Passwords%20do%20not%20match.');
    $result=$auth->change_other_password($userid,$victimid,$context,$_POST['pass']);
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
<title>Change Account Password</title>
<script>
function validate()
    {
    var my_form=document.form_data;
    if(my_form.pass.value!=my_form.pass2.value)
        {
        alert('Your new passwords do not match.  Please reenter them.');
        my_form.pass.value='';
        my_form.pass2.value='';
        my_form.pass.focus();
        return false;
        }
    return true;
    }
</script>
</head>
<body>
<form method="post" name="form_data" action="change_password.php?victimid=<?=$victimid?>" onsubmit="return validate();">
<table>
  <tr>
    <th>New Password</th>
    <td><input type="password" name="pass"></td>
  </tr>
  <tr>
    <th>Confirm Password</th>
    <td><input type="password" name="pass2"></td>
  </tr>
  <tr><td><input type="submit" value="Change Password"></td></tr>
</table>
</form>
<p><a href="edit_account.php?victimid=<?=$victimid?>">Do not change password.</a></p>
</body>
</html>
