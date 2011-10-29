<?php
session_start();

#Authenticate this user.
require_once 'common_auth.php';
$userid=authenticate_login('login.php');

$auth=get_auth();
#If this is an attempt to logout, do so.
if(isset($_POST['OP']))
    {
    $result=$auth->logout($_SESSION['userid']);
    unset($_SESSION['userid']);
    header('Location: login.php');
    exit;
    }

#Get user login count
$userid=$_SESSION['userid'];
$login_times=$auth->count_logins($userid,$userid);
if(is_numeric($login_times))
    $login_text=($login_times==1?'':" $login_times times.");
else
    $login_text='';

#Get user name
$name=$auth->get_username($userid);

#Check for user admin
$is_admin=$auth->is_admin($userid);
?>
<html>
<head>
<title>You are logged in</title>
</head>
<body>
<p>You are currently logged in as <?=$name.$login_text?>.</p>
<form method="post">
<table>
<tr><td><a href="change_password.php">Change your password.</a></td></tr>
<tr><td><a href="change_email.php">Change your email address on our records.</a></td></tr>
<tr><td>&nbsp;</td></tr>
<?php
if($is_admin===false)
    echo "<tr><td><a href=\"admin/\">Administer the account system.</a></td></tr>\n<tr><td>&nbsp;</td></tr>\n";
?>
<tr><td><a href="cancel_account.php">Cancel your account with our site.</a></td></tr>
<tr><td>&nbsp;</td></tr>
<tr>
  <td>If you want to logout, click the button to the right.</td>
  <td><input type="submit" name="OP" value="Log Out"</td>
</tr>
</table>
</form>
</body>
</html>
