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

#Check this user's alter permissions status
$has_access=check_permission($userid,$context,'ALTER_PERMISSIONS','display_error.php');

#Now get the permissions that this account can administer in $context.
$permissions=$auth->list_permissions($userid,$context);
#If there is an error, hide it.
if(is_auth_error($permissions))
    $permissions=array();
#Make a select array from the permissions
$permissions=fix_permissions_list($permissions);
if(isset($_POST['permission']))
    $_SESSION['permission']=$_POST['permission'];
elseif(!isset($_SESSION['permission']) || $_SESSION['permission']=='')
    $_SESSION['permission']=reset($permissions);
$permission=$_SESSION['permission'];

#Now get the accounts that this account can administer in $context.
$accounts=$auth->list_accounts($userid,$context);
#If there is an error, hide it.
if(is_auth_error($accounts))
    $accounts=array();

if(isset($_POST['UPDATE_PERMISSIONS']) && $has_access)
    {
    foreach($accounts as $account)
        {
        list($victimid,$name,$login_count,$status,$hold_info)=array_values($account);
        list($pcontext,$permission_name)=explode('.',$permissions[$permission],2);
        $result=$auth->set_permission($userid,$victimid,$pcontext,$permission_name,isset($_POST["U_$victimid"]));
        if(is_auth_error($result))
            header('Location: display_error.php?error='.rawurlencode($result->get_msg()));
        }
    }
?>
<html>
<head>
<title>Permission Administration</title>
</head>
<body>
<form method="post"><table><tr>
    <th>Context</th>
    <td><?php make_select('context',$context,$contexts,array('size'=>1)); ?></td>
    <td><?php make_select('permission',$permission,$permissions,array('size'=>1)); ?></td>
    <td><input type="submit" name="SET_CONTEXT" value="Set Context and Permission"></td>
</tr></table></form>
<table><tr>
  <td><a href="index.php">Return to the main admin menu</a></td>
</tr></table>
<?php
if ($has_access)
    {
?>
<form method="post">
<table><caption><b><?=$permissions[$permission]?></b></caption>
<?php
    #set index counter
    $count=0;
    list($pcontext,$permission_name)=explode('.',$permissions[$permission],2);
    foreach($accounts as $account)
        {
        list($victimid,$name,$login_count,$status,$hold_info)=array_values($account);
        if($count%3==0)
            echo "<tr>\n";
        $set=(check_permission($victimid,$pcontext,$permission_name)===true);
        echo '<td>';
        make_checkbox("U_$victimid",$set);
        echo "$name</td>\n";
        if ($count%3==2)
            echo "</tr>\n";
        $count++;
        }
    if ($count%3!=0)
        echo "</tr>\n";
?>
<tr><th colspan="3"><input type="submit" name="UPDATE_PERMISSIONS" value="Update Permissions"></th></tr>
</table>
</form>
<?php
    } //$has_access
?>
</body>
</html>
