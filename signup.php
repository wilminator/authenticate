<?php
//Load the auth object generation function.
require_once 'common_auth.php';
$auth=get_auth();

//There should only be 2 variables.
if(count($_GET)!=2)
    {
    $auth->log_error(AERR_HACK,__FUNCTION__,AERR_MSG_HACK,'','',array('var_names'=>implode(',',array_keys($_GET)),'var_values'=>implode(',',array_values($_GET))));
    exit;
    }

//One of the variables needs to be the userid.
if(!isset($_GET['userid']))
    {
    $auth->log_error(AERR_HACK,__FUNCTION__,AERR_MSG_HACK,'','',array('var_names'=>implode(',',array_keys($_GET)),'var_values'=>implode(',',array_values($_GET))));
    exit;
    }

//Check for new accounts.
if(isset($_GET['confirm_hash']))
    {
    //Load the auth object generation function.
    $result=$auth->confirm_account($_GET['userid'],$_GET['confirm_hash']);
    if($result===false)
        header('Location: account_confirmed.php');
    $auth->log_error(AERR_HACK,__FUNCTION__,AERR_MSG_BAD_AUTHCODE,'','',array('var_names'=>implode(',',array_keys($_GET)),'var_values'=>implode(',',array_values($_GET))));
    exit;
    }

//Check for email address reconfirmations.
if(isset($_GET['reconfirm_hash']))
    {
    $result=$auth->confirm_email_change($_GET['userid'],$_GET['reconfirm_hash']);
    if($result===false)
        header('Location: address_confirmed.php');
    $auth->log_error(AERR_HACK,__FUNCTION__,AERR_MSG_BAD_AUTHCODE,'','',array('var_names'=>implode(',',array_keys($_GET)),'var_values'=>implode(',',array_values($_GET))));
    exit;
    }

//Check for account reset confirmations.
if(isset($_GET['reset_hash']))
    {
    $result=$auth->confirm_account_reset($_GET['userid'],$_GET['reset_hash']);
    if($result===false)
        header('Location: account_reset.php');
    $auth->log_error(AERR_HACK,__FUNCTION__,AERR_MSG_BAD_AUTHCODE,'','',array('var_names'=>implode(',',array_keys($_GET)),'var_values'=>implode(',',array_values($_GET))));
    exit;
    }
exit;
?>
