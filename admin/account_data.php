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

if(isset($_POST['UPDATE_DATA']))
    {
    #Get data list for the person for this context.
    $data=$auth->list_account_data($userid,$victimid,$context);
    foreach($data as $item)
        {
        list($pcontext,$data_name,$value)=array_values($item);
        if($_POST["D_{$pcontext}_{$data_name}"]!='')
            {
            if($auth->is_member($victimid,$pcontext)===false)
                {
                $result=$auth->set_data($userid,$victimid,"{$pcontext}.{$data_name}",$_POST["D_{$pcontext}_{$data_name}"]);
                if(is_auth_error($result))
                    header('Location: display_error.php?error='.rawurlencode($result->get_msg()));
                }
            }
        elseif($_POST["D_{$pcontext}_{$data_name}"]=='')
            {
            $result=$auth->set_data($userid,$victimid,"{$pcontext}.{$data_name}",null);
            if(is_auth_error($result))
                header('Location: display_error.php?error='.rawurlencode($result->get_msg()));
            }
        }
    if($_POST['NEW_DATA']!='')
        {
        $data=$_POST['NEW_DATA'];
        list($pcontext,$data_name)=explode('.',$data,2);
        if($auth->is_member($victimid,$pcontext)===false)
            {
            $result=$auth->set_data($userid,$victimid,$data,true);
            if(is_auth_error($result))
                header('Location: display_error.php?error='.rawurlencode($result->get_msg()));
            }
        }
    }

#Load the web objects.
require_once 'web_objects.php';

//Get the victim's name.
$name=$auth->get_username($victimid);
#Get data list for the person for this context.
$data=$auth->list_account_data($userid,$victimid,$context);

?>
<html>
<head>
<title>Edit User Data</title>
</head>
<body>
<form method="post">
<table><caption><b><?=$name?></b></caption>
<?php
#set index counter
$count=0;
foreach($data as $item)
    {
    list($dcontext,$name,$value)=$item;
    echo "<tr><th>$dcontext.$name</th><td>";
    make_input("D_{$dcontext}_$name",$value);
    echo "</td></tr>";
    $count++;
    }
if ($count%3!=0)
    echo "</tr>\n";
?>
<tr><th>Variable Name</th><td><input name="NEW_DATA" value="" size="64"></th></tr>
<tr><th>Variable Value</th><td><input name="NEW_VALUE" value="" size="64"></th></tr>
<tr><th colspan="2"><input type="submit" name="UPDATE_DATA" value="Update Permissions"></th></tr>
</table>
</form>
<p><a href="edit_account.php?victimid=<?=$victimid?>">Return to the options menu.</a></p>
</body>
</html>
