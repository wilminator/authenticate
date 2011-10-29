<?php
session_start();

#Authenticate this user.
require_once 'common_auth.php';
$userid=authenticate_login('login.php');

#Reset the error variable
$error='';

#Attempt to change this account's email
if(isset($_POST['CHANGE_EMAIL']))
    {
    if($_POST['email']!=$_POST['email2'])
        $error.='The email addresses entered do not match.  Please retype them.<br>';
    else
        {
        $auth=get_auth();
        $result=$auth->change_email($_SESSION['userid'],$_POST['pass'],$_POST['email']);
        if(!is_auth_error($result))
            {
            //Yay!  The email change is ready.
            header("Location: new_email.php");
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
        alert ('Your new email addresses do not match.  Please reenter them.');
        form.email.focus();
        form.email.select();
        return false;
        }
    reutrn true;
    }
</script>
<body onload="document.form_data.pass.focus();">
<h1 style="color:red"><?=$error?></h1>
<h1>Change Password:</h1>
<form method="post" name="form_data" onsubmit="return validate();">
  <table>
    <tr><th>Enter Password</th><td><input type="password" name="pass"></td></tr>
    <tr><th>Enter New Email</th><td><input name="email"></td></tr>
    <tr><th>Confirm New Email</th><td><input name="email2"></td></tr>
    <tr><td clspan="2"><input type="submit" name="CHANGE_EMAIL" value="Change Password"></td></tr>
  </table>
</form>
</body>
</html>
