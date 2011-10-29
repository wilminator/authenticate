<?php
session_start();

//If we are logged in, then jump to the logout page.
require_once 'common_auth.php';
confirm_not_logged_in('status.php');

#Reset the error variable
$error='';

#Attempt to make the new account
if(isset($_POST['MAKE_ACCOUNT']))
    {
    if($_POST['pass']!=$_POST['pass2'])
        $error.='The passwords entered do not match.  Please retype them.<br>';
    if($_POST['email']!=$_POST['email2'])
        $error.='The email addresses entered do not match.  Please retype them.<br>';
    if($error=='')
        {
        require_once 'common_auth.php';
        $auth=get_auth();
        $result=$auth->create_account($_POST['name'],$_POST['pass'],$_POST['email']);
        if(!is_auth_error($result))
            {
            //Yay!  The account was made.  Forward to the new account notice page.
            header('Location: new_account.php');
            }
        //There was an error.  Decipher common errors.
        switch($result->get_msg())
            {
            case 'Email address in use.':
                $error='The email address given is already in use.  Please choose a new one or try to reset your old account.';
                break;
            case 'Name in use.':
                $error='The email address given is already in use.  Please choose a new one or try to reset your old account.';
                break;
            default:
                $error='There was an error processing your request. Please try again later. '.$result->get_msg();
                break;
            }
        }
    }
?>
<html>
<head>
<title>Create Account</title>
<script>
    function validate()
        {
        var my_form=window.document.form_data;
        if(my_form.name.value.length<6)
            {
            alert('Your username must be at least 6 characters long.');
            my_form.name.focus();
            my_form.name.select();
            return false;
            }
        if(my_form.name.value.length>16)
            {
            alert('Your username must be no more than 16 characters long.');
            my_form.name.focus();
            my_form.name.select();
            return false;
            }
        if(my_form.name.value.search(/[^a-zA-Z0-9_]/)!=-1)
            {
            alert('Your username may only consist of letters, numbers, and the underscore.');
            my_form.name.focus();
            my_form.name.select();
            return false;
            }
        if(my_form.pass.value.length<6)
            {
            alert('Your password must be at least 6 characters long.');
            my_form.pass.value='';
            my_form.pass2.value='';
            my_form.pass.focus();
            return false;
            }
        if(my_form.pass.value!=my_form.pass2.value)
            {
            alert('Your passwords do not match.');
            my_form.pass.value='';
            my_form.pass2.value='';
            my_form.pass.focus();
            return false;
            }
        var email=my_form.email.value.match(/@/);
        if(email==null || email.length!=1)
            {
            alert('The email address given does not look like a valid email address.');
            my_form.email.focus();
            my_form.email.select();
            return false;
            }
        if(my_form.email.value.search(/.+?@.+\.[^\.]+/)!=0)
            {
            alert('The email address given does not look like a valid email address.');
            my_form.email.focus();
            my_form.email.select();
            return false;
            }
        if(my_form.email.value!=my_form.email2.value)
            {
            alert('Your email addresses do not match.');
            my_form.email.focus();
            my_form.email.select();
            return false;
            }
        return true;
        }
</script>
</head>
<body onload="document.form_data.name.focus();">
<h1 style="color:red"><?=$error?></h1>
<p>In order to make your account, please enter the following information:</p>
<form method="post" onsubmit="return validate();" name="form_data">
  <table>
    <tr><th>Enter Username</th><td><input name="name" size="16" maxlength="16"></td></tr>
    <tr><th>Enter Password</th><td><input type="password" name="pass"></td></tr>
    <tr><th>Confirm Password</th><td><input type="password" name="pass2"></td></tr>
    <tr><th>Enter Valid Email Address</th><td><input name="email" size="32"></td></tr>
    <tr><th>Confirm Email Address</th><td><input name="email2" size="32"></td></tr>
    <tr><td clspan="2"><input type="submit" name="MAKE_ACCOUNT" value="Create Account"></td></tr>
  </table>
</form>
</body>
</html>
