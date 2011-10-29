<?php
session_start();

#Authenticate this user.
require_once 'common_auth.php';
$userid=authenticate_login('login.php');

#Reset the error variable
$error='';

#Attempt to cancel this account.
if(isset($_POST['DELETE']))
    {
    if ($_POST['pass']!=$_POST['pass2'])
        $error='Your passwords did not match.';
    else
        {
        $auth=get_auth();
        $result=$auth->delete_account($_SESSION['userid'],$_POST['pass']);
        if(!is_auth_error($result))
            {
            unset($_SESSION['userid']);
            header('Location: account_canceled.php');
            exit;
            }
        $error=$result->get_msg();
        }
    }
?>
<html>
<head>
<title>Cancel Account</title>
<body onload="document.form_data.pass.focus();">
<h1 style="color:red"><?=$error?></h1>
<h1>Cancel Your Account</h1>
<p>In order to cancel your account, please enter your password in both boxes:</p>
<form method="post" name="form_data">
  <table>
    <tr><th>Enter Password</th><td><input type="password" name="pass"></td></tr>
    <tr><th>Confirm Password</th><td><input type="password" name="pass2"></td></tr>
    <tr><td></td><td><input type="submit" name="DELETE" value="Cancel this account"></td></tr>
    <tr><td>&nbsp;</td></tr>
    <tr><td><a href="logout.php">I do not wish to cancel my account.</a></td></tr>
  </table>
</form>
</body>
</html>
