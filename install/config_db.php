<?php
if (isset($_POST['DO_INIT']))
    {
    //Reset the DB.
    require_once 'init_auth.php';
    $init=init_system(
        $_POST['svr_addr'],
        $_POST['svr_user'],
        $_POST['svr_pass'],
        $_POST['db'],
        $_POST['svc_user'],
        $_POST['svc_pass'],
        $_POST['svc_host'],
        $_POST['adm_user'],
        $_POST['adm_pass'],
        $_POST['email']);
    if($init===false)
        {
        $handle=fopen('auth_pass.php','w');
        fwrite($handle,file_get_contents('base_pass.php'));
        fwrite($handle,"
#DB data
\$db_host='{$_POST['svr_addr']}';
\$db_user='{$_POST['svc_user']}';
\$db_pass='{$_POST['svc_pass']}';
\$database='{$_POST['db']}';

#SMTP data
\$smtp_host='{$_POST['smtp_addr']}';
\$smtp_user='{$_POST['smtp_user']}';
\$smtp_pass='{$_POST['smtp_pass']}';
\$smtp_from='{$_POST['smtp_from']}';
\$smtp_addr='{$_POST['smtp_email']}';
?>");
        header('Location: conf_done.html');
        }
    else
        var_dump($init);
    exit;
    }
?>
<html>
<head>
<title>Init DB</title>
</head>
<body>
<form method="post">
<table>
<tr><th>DB Server address</th><td><input name="svr_addr" value="localhost"></td></tr>
<tr><th>DB Server user</th><td><input name="svr_user" value="root"></td></tr>
<tr><th>DB Server password</th><td><input type="password" name="svr_pass" value="problemex"></td></tr>
<tr><th>Database name</th><td><input name="db" value="authentication"></td></tr>
<tr><th>Service DB account</th><td><input name="svc_user" value="authenticate"></td></tr>
<tr><th>Service DB password</th><td><input type="password" name="svc_pass" value="t0Tal_AuthEnT1cAtIoN"></td></tr>
<tr><th>Service account host</th><td><input name="svc_host" value="localhost"></td></tr>
<tr><th>System admin account</th><td><input name="adm_user" value="admin"></td></tr>
<tr><th>System admin password</th><td><input type="password" name="adm_pass" value="problemex"></td></tr>
<tr><th>System admin email</th><td><input name="email" value="wilminator@wilminator.com"></td></tr>
<tr><th>SMTP Server address</th><td><input name="smtp_addr" value="smtp.bizmail.yahoo.com"></td></tr>
<tr><th>SMTP Server user</th><td><input name="smtp_user" value="wilminator@wilminator.com"></td></tr>
<tr><th>SMTP Server password</th><td><input name="smtp_pass" value="problemex"></td></tr>
<tr><th>SMTP Server from name</th><td><input name="smtp_from" value="The Wilminator"></td></tr>
<tr><th>SMTP Server return address</th><td><input name="smtp_email" value="wilminator@wilminator.com"></td></tr>
<tr><td colspan="2"><input type="submit" name="DO_INIT" value="Initialize the DB"></td></tr>
</table>
</form>
</body>
</html>
