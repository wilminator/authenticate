<?php
session_start();
echo SID;

//If we are logged in, then jump to the logout page.
require_once 'common_auth.php';
confirm_not_logged_in('status.php');

if(isset($_REQUEST['return_page']))
    $return_page=$_REQUEST['return_page'];
else
    $return_page='status.php';

#Reset the error variable
$error='';

#Attempt a login if selected.
if(isset($_POST['LOGIN']))
    {
    $auth=get_auth();
    $result=$auth->login($_POST['name'],$_POST['pass']);
    if(!is_auth_error($result))
        {
        //Yay!  The account was made.  Forward to the new account notice page.
        $_SESSION['userid']=$result;
        header("Location: $return_page");
        exit;
        }
    if($result->get_error()==AERR_FAILURE)
        $error='The username or password given was incorrect.  Please try again.'.$result->get_msg();
    else
        $error='There was an error processing your request. Please try again later. '.$result->get_msg();
    }
?>
<html>
<head>
<title>Account Login</title>
<body onload="document.form_data.name.focus();">
<h1 style="color:red"><?=$error?></h1>
<h1>Account Login:</h1>
<form method="post" name="form_data">
  <table>
    <tr><th>Enter Username</th><td><input name="name" size="16" maxlength="16"></td></tr>
    <tr><th>Enter Password</th><td><input type="password" name="pass"></td></tr>
    <tr><td></td><td><input type="submit" name="LOGIN" value="Login"></td></tr>
    <tr><td>&nbsp;</td></tr>
    <tr><td><a href="forgot_password.php">I forgot my password or username.</a></td></tr>
    <tr><td>&nbsp;</td></tr>
    <tr><td><a href="make_account.php">I want to make an account.</a></td></tr>
  </table>
</form>
</body>
</html>