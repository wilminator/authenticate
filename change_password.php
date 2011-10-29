<?php
session_start();

#Authenticate this user.
require_once 'common_auth.php';
$userid=authenticate_login('login.php');

#Reset the error variable
$error='';

#Attempt to change this account's password
if(isset($_POST['CHANGE_PASS']))
    {
    if($_POST['pass']!=$_POST['pass2'])
        $error.='The passwords entered do not match.  Please retype them.<br>';
    else
        {
        $auth=get_auth();
        $result=$auth->change_password($_SESSION['userid'],$_POST['opass'],$_POST['pass']);
        if(!is_auth_error($result))
            {
            //Yay!  The password was changed.
            header("Location: password_changed.php");
            }
        if($result->get_error()==AERR_FAILURE)
            $error='The username or password given was incorrect.  Please try again.'.$result->get_msg();
        else
            $error='There was an error processing your request. Please try again later. '.$result->get_msg();
        }
    }
?>
<html>
<head>
<title>Change password</title>
<script>
function validate()
    {
    var form=document.form_data;
    if(form.pass.value!=form.pass2.value)
        {
        alert ('Your new passwords do not match.  Please reenter them.');
        form.pass.value='';
        form.pass2.value='';
        form.pass.focus();
        return false;
        }
    reutrn true;
    }
</script>
<body onload="document.form_data.opass.focus();">
<h1 style="color:red"><?=$error?></h1>
<h1>Change Password:</h1>
<form method="post" name="form_data" on submit="return validate();">
  <table>
    <tr><th>Enter Old Password</th><td><input type="password" name="opass"></td></tr>
    <tr><th>Enter New Password</th><td><input type="password" name="pass"></td></tr>
    <tr><th>Confirm New Password</th><td><input type="password" name="pass2"></td></tr>
    <tr><td clspan="2"><input type="submit" name="CHANGE_PASS" value="Change Password"></td></tr>
  </table>
</form>
</body>
</html>
