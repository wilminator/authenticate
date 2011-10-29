<?php
//Define error codes.
define ('AERR_HACK',1);
define ('AERR_DB_ERROR',2);
define ('AERR_SYSTEM_FAILURE',3);
define ('AERR_NO_PERMISSION',4);
define ('AERR_NO_AUTHENTICATION',5);
define ('AERR_FAILURE',6);

//Define standard error messages.
//Hacks
define ('AERR_MSG_HACK'        ,'Possible hack.');
define ('AERR_MSG_BAD_CONTEXT' ,'Invalid context.');
define ('AERR_MSG_BAD_AUTHCODE','Bad authcode.');
//DB errors
define ('AERR_MSG_BAD_QUERY','Failed Query.');
//System failure
define ('AERR_MSG_CANT_EMAIL','Could not send email.');
//No permission
define ('AERR_MSG_NO_PERMISSION'   ,'Missing permission.');
define ('AERR_MSG_SELF_SUPERUSER'  ,'Cannot alter own SUPERUSER permission.');
define ('AERR_MSG_DELETE_SUPERUSER','Cannot delete superuser account.');
define ('AERR_MSG_DELETE_SYSTEM'   ,'Cannot delete system account.');
define ('AERR_MSG_NOT_MEMBER'      ,'Target account is not context member.');
//No authentication
define ('AERR_MSG_NO_COOKIE','Missing auth cookie.');
define ('AERR_MSG_NO_AUTH'  ,'Could not authenticate.');
define ('AERR_MSG_OLD_LOGIN','Expired login.');
define ('AERR_MSG_NEW_IP'   ,'Different IP.');
//Failures
define ('AERR_MSG_CANT_LOGIN'        ,'Could not login user.');
define ('AERR_MSG_UNCONFIRMED'       ,'Unconfirmed account.');
define ('AERR_MSG_ON_HOLD'           ,'Account on hold.');
define ('AERR_MSG_MAX_LOGINS'        ,'Max logins reached.');
define ('AERR_MSG_NO_SYS_MEMBERSHIP' ,'Invalid system account membership addition.');
define ('AERR_MSG_BAD_NAME_LENGTH'   ,'Invalid name length.');
define ('AERR_MSG_BAD_PASS_LENGTH'   ,'Invalid password length.');
define ('AERR_MSG_BAD_EMAIL'         ,'Bad email address.');
define ('AERR_MSG_USED_EMAIL'        ,'Email address in use.');
define ('AERR_MSG_USED_NAME'         ,'Name in use.');
define ('AERR_MSG_BAD_PASSWORD'      ,'Invalid password.');
define ('AERR_MSG_NO_EMAIL'          ,'Invalid email.');
define ('AERR_MSG_NO_NAME'           ,'Invalid name.');
define ('AERR_MSG_NO_USERID'         ,'Invalid userid.');
define ('AERR_MSG_CANT_BOOT_SYSTEM'  ,'Cannot boot system account.');
define ('AERR_MSG_CANT_HOLD_SYSTEM'  ,'Cannot hold system account.');
?>
