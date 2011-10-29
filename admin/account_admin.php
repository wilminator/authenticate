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
$on_hold=on_hold_in_context($userid,$context,'display_error.php');

#Now get the accounts that this account can administer in $context.
$accounts=$auth->list_accounts($userid,$context);
#If there is an error, hide it.
if(is_auth_error($accounts))
    $accounts=array();

#Now check for privileges
$can_update=check_permission($userid,$context,'UPDATE_ACCOUNTS','display_error.php');
$can_create=check_permission($userid,$context,'CREATE_SYSTEM_ACCOUNTS','display_error.php');
?>
<html>
<head>
<title>User Account Administration</title>
</head>
<body>
<form method="post"><table><tr>
    <th>Context</th>
    <td><?php make_select('context',$context,$contexts,array('size'=>1)); ?></td>
    <td><input type="submit" name="SET_CONTEXT" value="Set Context"></td>
</tr></table></form>
<?php
if($on_hold===false)
    {
    echo "<table><tr>\n";
    if($can_create)
        echo '<td><a href="create_account.php">Create a system account</a></td>';
    echo "<td><a href=\"index.php\">Return to the main admin menu</a></td>\n</tr></table>\n";
    echo "<table border=\"1\">\n<tr>\n<th>Name</th>\n<th>Login Count</th>\n<th>Status</th>\n";
    if ($can_update)
      echo "<th>Hold Info</th>\n";
    echo "<tr>\n";
    foreach($accounts as $accountid=>$account)
        {
        echo "<tr>\n<td><a href=\"edit_account.php?victimid=$accountid\">{$account['name']}</a></td>\n";
        if ($can_update===false)
            echo "<td><a href=\"boot_account.php?victimid=$accountid\">{$account['login_count']}</a></td>\n";
        else
            echo "<td>{$account['login_count']}</td>\n";
        echo "<td>{$account['status']}</td>\n";
        if ($can_update===false)
            echo "<td><a href=\"unhold_account.php?victimid=$accountid\">{$account['hold_info']}</a></td>\n";
        echo "<td><a href=\"edit_account.php?victimid=$accountid\">Edit account</a></td>\n";
        echo "</tr>";
        }
    echo "</table>\n";
    }
?>
</body>
</html>
