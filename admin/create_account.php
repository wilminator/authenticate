<?php
session_start();
//Authenticate this user.
require_once 'functions.php';
$userid=authenticate_admin_access('../login.php','../status.php');
$auth=get_auth();

//Check that this account is not on hold in this context.
redirect_on_hold($userid,$context,'display_error.php','account_admin.php','display_error.php');

$error='';

if(isset($_POST['CREATE_ACCOUNT']))
    {
    if($_POST['pass']!=$_POST['pass2'])
        $error.='The passwords entered do not match.  Please retype them.<br>';
    else
        {
        //Check that this account is not on hold in this context.
        on_hold_in_context($userid,$_POST['context']);
        //Create the system account.
        $result=$auth->create_system_account($userid,$_POST['context'],$_POST['name'],$_POST['pass']);
        if(!is_auth_error($result))
            {
            //Yay!  The account was made.
            header("Location: account_admin.php");
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
<title>Create account</title>
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
    if(form.context.value='')
        {
        alert ('You must specify a context.');
        form.context.focus();
        return false;
        }
    reutrn true;
    }
</script>
<body>
<h1 style="color:red"><?=$error?></h1>
<h1>Create Account:</h1>
<form method="post" name="form_data" on submit="return validate();">
  <table>
    <tr><th>Account Name</th><td><input name="name" size="16" maxlength="16"></td></tr>
    <tr><th>Account Context</th><td><input name="context"></td></tr>
    <tr><th>Enter Password</th><td><input type="password" name="pass"></td></tr>
    <tr><th>Confirm Password</th><td><input type="password" name="pass2"></td></tr>
    <tr><td colspan="2" align="center"><input type="submit" name="CREATE_ACCOUNT" value="Create System Account"></td></tr>
  <tr><td colspan="2" align="center"><a href="account_admin.php">Return to the account list.</a></td></tr>
  </table>
</form>
</body>
</html>
