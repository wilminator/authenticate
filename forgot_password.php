<?php
session_start();

//If we are logged in, then jump to the logout page.
require_once 'common_auth.php';
confirm_not_logged_in('status.php');

#Reset the error variable
$error='';

#Attempt to reset this account's password by name
if(isset($_POST['FORGOT_NAME']))
    {
    require_once 'common_auth.php';
    $auth=get_auth();
    $result=$auth->reset_account_by_name($_POST['username']);
    if(!is_auth_error($result))
        {
        //Yay!  The reset is ready.
        header("Location: reset_ready.php");
        }
    $error='There was an error processing your request. Please try again later. '.$result->get_msg();
    }

#Attempt to reset this account's password by email
if(isset($_POST['FORGOT_EMAIL']))
    {
    require_once 'common_auth.php';
    $auth=get_auth();
    $result=$auth->reset_account_by_email($_POST['email']);
    if(!is_auth_error($result))
        {
        //Yay!  The reset is ready.
        header("Location: reset_ready.php");
        }
    if($result->get_error()==AERR_FAILURE)
        $error='The username or password given was incorrect.  Please try again.'.$result->get_msg();
    else
        $error='There was an error processing your request. Please try again later. '.$result->get_msg();
    }
?>
<html>
<head>
<title>Forgotten username or password</title>
</head>
<body  onload="document.form_data.username.focus();">
<h1><?=$error?></h1>
<form method="post">
<table>
<tr>
  <td colspan="2">If you cannot remember your password but can remember your account name, then please enter it here:</td>
</tr>
<tr>
  <td><input name="username"></td>
  <td><input type="submit" name="FORGOT_NAME" value="Reset the account with this name."</td>
</tr>
<tr><td>&nbsp;</td></tr>
<tr>
  <td colspan="2">If you cannot remember your account name, please enter the email address you used when you created your account:</td>
</tr>
<tr>
  <td><input name="email"></td>
  <td><input type="submit" name="FORGOT_EMAIL" value="Reset the account with this email."</td>
</tr>
<tr><td>&nbsp;</td></tr>
<tr><td><a href="status.php">Return to the login status page.</a></td></tr>
</table>
</form>
</body>
</html>
