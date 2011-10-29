<?php
session_start();
//Authenticate this user.
require_once 'functions.php';
$userid=authenticate_admin_access('../login.php','../status.php');
$auth=get_auth();

//Test for the victimid.
$victimid=verify_victimid();

//Test for context.
$context=verify_context();

//Check that this account is not on hold in this context.
redirect_on_hold($userid,$context,'display_error.php','account_admin.php','display_error.php');

#Check this user's alter permissions status
$has_access=check_permission($userid,$context,'ALTER_PERMISSIONS','display_error.php');

if(isset($_POST['UPDATE_PERMISSIONS']) && $has_access)
    {
    #Get available permissions for this context.
    $permissions=$auth->list_permissions($userid,$context);
    foreach($permissions as $permission)
        {
        list($pcontext,$permission_name)=array_values($permission);
        $result=$auth->set_permission($userid,$victimid,$pcontext,$permission_name,isset($_POST["P_{$pcontext}_{$permission_name}"]));
        if(is_auth_error($result))
            header('Location: display_error.php?error='.rawurlencode($result->get_msg()));
        }
    if($_POST['NEW_PERMISSION']!='')
        {
        $permission=$_POST['NEW_PERMISSION'];
        list($pcontext,$permission_name)=explode('.',$permission,2);
        if($permission_name!='')
            {
            $result=$auth->set_permission($userid,$victimid,$pcontext,$permission_name,true);
            if(is_auth_error($result))
                header('Location: display_error.php?error='.rawurlencode($result->get_msg()));
            }
        }
    }

#Load the web objects.
require_once 'web_objects.php';

//Get the victim's name.
$name=$auth->get_username($victimid);
#Get available permissions for this context.
$permissions=$auth->list_permissions($userid,$context);
$cpermissions=array_map(create_function('$a', 'return implode(".",$a);'),$permissions);

#Get permission list for the person for this context.
$upermissions=$auth->list_account_permissions($userid,$victimid,$context);
$upermissions=array_map(create_function('$a', 'return implode(".",$a);'),$upermissions);
?>
<html>
<head>
<title>Edit User Permissions</title>
</head>
<body>
<form method="post">
<table><caption><b><?=$name?></b></caption>
<?php
#set index counter
$count=0;
foreach($cpermissions as $index=>$permission)
    {
    list($pcontext,$permission_name)=array_values($permissions[$index]);
    if($count%3==0)
        echo "<tr>\n";
    $set=in_array($permission,$upermissions);
    echo '<td>';
    make_checkbox("P_{$pcontext}_$permission_name",$set);
    echo "{$pcontext}.$permission_name</td>\n";
    if ($count%3==2)
        echo "</tr>\n";
    $count++;
    }
if ($count%3!=0)
    echo "</tr>\n";
?>
<tr><th>Permission Name</th><td colspan="2"><input name="NEW_PERMISSION" value="" size="64"></th></tr>
<tr><th colspan="3"><input type="submit" name="UPDATE_PERMISSIONS" value="Update Permissions"></th></tr>
</table>
</form>
<p><a href="edit_account.php?victimid=<?=$victimid?>">Return to the options menu.</a></p>
</body>
</html>
