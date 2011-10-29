<?php
session_start();
//Authenticate this user.
require_once 'functions.php';
$userid=authenticate_admin_access('../login.php','../status.php');
?>
<html>
<head>
<title>Administraion Menu</title>
</head>
<body>
<center>
<table>
  <tr><td><a href="account_admin.php">Administer Accounts</a></td></tr>
  <tr><td><a href="assign_permissions.php">Administer Permissions</a></td></tr>
  <tr><td><a href="bulk_email.php">Send Bulk Email</a></td></tr>
  <tr><td><a href="view_errors.php">View Error Log</a></td></tr>
  <tr><td></td></tr>
  <tr><td><b><a href="../status.php">Return to the login status page.</a></b></td></tr>
</table>
</center>
</body>
</html>
