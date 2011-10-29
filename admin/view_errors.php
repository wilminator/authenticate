<?php
session_start();
//Authenticate this user.
require_once 'functions.php';
$userid=authenticate_admin_access('../login.php','../status.php');
$auth=get_auth();

#Load the web objects library.
require_once 'web_objects.php';

#Get the list of contexts this account is a member of.
$contexts=$auth->list_admin_memberships($userid);
if(is_auth_error($contexts))
    {
    header('Location: display_error.php?error='.rawurlencode($contexts->get_msg()));
    exit;
    }
$contexts=set_values_as_keys($contexts);
if(isset($_POST['context']))
    $_SESSION['context']=$_POST['context'];
elseif(!isset($_SESSION['context']) || $_SESSION['context']=='')
    $_SESSION['context']=reset($contexts);
$context=$_SESSION['context'];

//Check that this account is not on hold in this context.
on_hold_in_context($userid,$context);
?>
<html>
<head>
<title>Error Log Administration</title>
</head>
<body>
<form method="post">
  <table>
    <tr>
      <th>Context</th>
      <td><?php make_select('context',$context,$contexts,array('size'=>1)); ?></td>
    </tr>
    <tr>
      <th>Filters</th>
    </tr>
    <tr>
      <th>Severity</th>
      <td><?php make_select('context',$context,$contexts,array('size'=>1)); ?></td>
    </tr>
    <tr>
      <th>Dates</th>
      <td><?php make_select('context',$context,$contexts,array('size'=>1)); ?></td>
    </tr>
    <tr>
      <th>User</th>
      <td><?php make_select('context',$context,$contexts,array('size'=>1)); ?></td>
    </tr>
    <tr>
      <th>Victim</th>
      <td><?php make_select('context',$context,$contexts,array('size'=>1)); ?></td>
    </tr>
  </table>
</form>
<table><tr>
</body>
</html>
