<?php
require_once AUTH_INCLUDE_DIR.'smtp.php';
require_once AUTH_INCLUDE_DIR.'auth_error.php';
require_once AUTH_INCLUDE_DIR.'auth_utility.php';
require_once AUTH_INCLUDE_DIR.'auth_defines.php';
require_once AUTH_INCLUDE_DIR.'auth_helpers.php';
require_once AUTH_INCLUDE_DIR.'auth_config.php';

class AUTHENTICATION
    {
    var $db_conn;

    //This is the constructor.  It sets up the DB connection.
    function AUTHENTICATION()
        {
        require AUTH_PASSWORD_DIR.'auth_pass.php'; 
        $this->db_connect($db_host,$db_user,$db_pass,$database);
        }

    //This function establishes a connection to the DB server.
    function db_connect($host,$user,$pass,$db)
        {
        $this->db_conn=@mysql_connect($host,$user,$pass);
        mysql_select_db($db);
        }

    //This function queries the DB.
    function db_query($query)
        {
        return mysql_query($query,$this->db_conn);
        }

    function db_get_new_id()
        {
        return mysql_insert_id();
        }

    function db_escape_string($string)
        {
        return mysql_escape_string($string);
        }

    function db_error()
        {
        return mysql_error();
        }
        
    function db_fetch_array($result)
        {
        return mysql_fetch_assoc($result);
        }

    //This function logs notable errors in the DB and returns an error object.
    function log_error($error,$function_name,$msg,$offenderid='',$victimid='',$other=array())
        {
        if($error<=AUTH_LOG_THRESHOLD)
            {
            $other['trace']=generate_traceback();
            $ip=$_SERVER['REMOTE_ADDR'];
            $escaped_msg=$this->db_escape_string($msg);
            $query="INSERT INTO errors (date,error,function,msg,ip,offenderid,victimid) VALUES (NOW(),$error,'$function_name','$escaped_msg','$ip','$offenderid','$victimid')";
            $result=$this->db_query($query);
            if($result!==false && count($other)>0)
                {
                $errorid=$this->db_get_new_id();
                $values=array();
                foreach($other as $name=>$value)
                    $values[]="($errorid,'$name','".$this->db_escape_string($value)."')";
                $values=implode(',',$values);
                $query="INSERT INTO error_data (errorid,name,data) VALUES $values";
                $result=$this->db_query($query);
                }
            }
        return new AUTH_ERROR($error,$msg);
        }

    //This function purges expired logins
    function purge_expired_logins()
        {
        if (AUTH_TIMEOUT!='')
            {
            $offset=(substr(AUTH_TIMEOUT,0,1)!='-'?'-'.AUTH_TIMEOUT:AUTH_TIMEOUT);
            $cutoff_date=date('Y-m-d H:i:s',strtotime($offset));
            $query="DELETE FROM logins WHERE last_auth<='$cutoff_date'";
            $result=$this->db_query($query);
            //If the query bombed, do_error.
            if($result===false)
                return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));
            }
        return false;
        }

    //This function purges expired logins
    function purge_expired_accounts()
        {
        if (AUTH_MAX_INACTIVE!='')
            {
            $offset=(substr(AUTH_MAX_INACTIVE,0,1)!='-'?'-'.AUTH_MAX_INACTIVE:AUTH_MAX_INACTIVE);
            $cutoff_date=date('Y-m-d H:i:s',strtotime($offset));
            $query="SELECT users.userid, max( permissions.permission )
                FROM permissions
                RIGHT  JOIN user_permissions
                    ON user_permissions.permissionid = permissions.permissionid
                    AND permissions.permission =  'SUPERUSER'
                RIGHT  JOIN users USING ( userid )
                WHERE last_login <=  '$cutoff_date'
                AND acct_type =  'User'
                GROUP  BY users.userid
                HAVING MAX( permissions.permission )  IS  NULL";
            $result=$this->db_query($query);
            //If the query bombed, do_error.
            if($result===false)
                return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));
            while($data=$this->db_fetch_array($result))
                {
                //Remove user data
                $query="DELETE FROM user_data WHERE userid=$data[userid]";
                $result=$this->db_query($query);
                //If the query bombed, do_error.
                if($result===false)
                    return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));
                //Remove user membership
                $query="DELETE FROM user_membership WHERE userid=$data[userid]";
                $result=$this->db_query($query);
                //If the query bombed, do_error.
                if($result===false)
                    return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));
                //Remove user permissions
                $query="DELETE FROM user_permissions WHERE userid=$data[userid]";
                $result=$this->db_query($query);
                //If the query bombed, do_error.
                if($result===false)
                    return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));
                }
            $query="DELETE FROM users WHERE last_login<='$cutoff_date' AND acct_type='User' AND userid!=1";
            $result=$this->db_query($query);
            //If the query bombed, do_error.
            if($result===false)
                return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));
            }
        return false;
        }

    function get_userid($auth_code)
        {
        $clean_code=$this->db_escape_string($auth_code);
        $query="SELECT userid, ip FROM logins WHERE auth_code='$clean_code'";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));
        $data=$this->db_fetch_array($result);
        #TODO: return the user id
        }

    function seek_old_login()
        {
        if(!isset($_COOKIE["auth_code"]))
            return true;
        if(get_magic_quotes_gpc())
            list($auth_code,$userid)=unserialize(stripslashes($_COOKIE["auth_code"]));
        else
            list($auth_code,$userid)=unserialize($_COOKIE["auth_code"]);
        if(($result=$this->authenticate($userid))!==false)
            return $result;
        return $userid;
        }

    //This function tries to log in an account.
    function login($name,$password)
        {
        $ip=$_SERVER['REMOTE_ADDR'];

        //Ensure that this name does not have any non-alphanum chars in it.
        if(strpos(rawurlencode($name),'%')!==false)
            //We should log this possible hacking attempt here.
            return $this->log_error(AERR_HACK,__FUNCTION__,AERR_MSG_HACK,'','',array('name'=>$name));

        //Get the userid if this login is good.
        $md5_pass=md5($password);
        $query="SELECT userid FROM users WHERE name='$name' and password='$md5_pass'";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));

        $data=$this->db_fetch_array($result);
        //If the login is bad then no data will be returned.
        if($data===false)
            return $this->log_error(AERR_FAILURE,__FUNCTION__,AERR_MSG_CANT_LOGIN);

        //Record the userid.
        $userid=$data['userid'];

        //Now purge outstanding logins.
        $this->purge_expired_logins();

        //Determine if this account is a system account.
        $is_system=$this->is_system_account($userid);
        if(is_auth_error($is_system))
            return $is_system;

        //Check to see if the AUTH_MAX_LOGIN_COUNT threshold has been reached.
        if($is_system===true && AUTH_MAX_LOGIN_COUNT>0) //This IS NOT a system account
            {
            $query="SELECT count(userid) count FROM logins WHERE userid=$userid";
            $result=$this->db_query($query);
            if($result===false)
                return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));
            $data=$this->db_fetch_array($result);
            if($data['count']>=AUTH_MAX_LOGIN_COUNT)
                return $this->log_error(AERR_FAILURE,__FUNCTION__,AERR_MSG_MAX_LOGINS);
            }

        //Generate a hash for this login.
        $last_login=date('Y-m-d H:i:s');
        $hash=$userid.$name.$password.$last_login.$ip.rand();
        $auth_code=sha1($hash);

        //Record this player's login data.
        if($is_system===false) //This IS a system account
            $query="INSERT INTO logins (userid,auth_code,last_auth) VALUES ($userid,'$auth_code','$last_login')";
        else
            $query="INSERT INTO logins (userid,auth_code,ip,last_auth) VALUES ($userid,'$auth_code','$ip','$last_login')";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));

        //Update the last logged in time.
        $query="UPDATE users SET last_login='$last_login' WHERE userid=$userid";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));

        //Set the hash code in a cookie.
        if($is_system===false) //This IS a system account
            $GLOBALS["__auth_code_$userid"]=$auth_code;
        else
            {
            //Generate expiration date.
            $expiry_date=strtotime(AUTH_TIMEOUT);
            //Set the authentication cookie.
            setcookie("auth_code",serialize(array($auth_code,$userid)),$expiry_date,'/');
            $_COOKIE["auth_code"]=serialize(array($auth_code,$userid));
            }
        //See if the account needs to be confirmed.
        $result=$this->get_data($userid,$userid,'_.Confirm');
        if(!is_null($result) && !is_auth_error($result))
            $result=$this->log_error(AERR_FAILURE,__FUNCTION__,AERR_MSG_UNCONFIRMED,$userid);
        if(is_auth_error($result))
            {
            $this->logout($userid);
            return $result;
            }

        //See if there is a hold on this account.
        $result=$this->has_hold($userid,'_');
        if($result!==false)
            {
            $this->logout($userid);
            return $result;
            }

        //See if the account had a pending account reconfirm.
        $result=$this->get_data($userid,$userid,'_.Reconfirm');
        if(is_auth_error($result))
            {
            $this->logout($userid);
            return $result;
            }
        if(!is_null($result))
            {
            $result=$this->cancel_email_change($userid,$userid,'_');
            if($result!==false)
                return $result;
            }

        //See if the account had a pending account reset.
        $result=$this->get_data($userid,$userid,'_.Reset');
        if(is_auth_error($result))
            {
            $this->logout($userid);
            return $result;
            }
        if(!is_null($result))
            {
            $result=$this->cancel_account_reset($userid,$userid,'_');
            if($result!==false)
                return $result;
            }

        //Return the userid.
        return $userid;
        }

    //This function checks to see if an account is a system account.
    function is_system_account($userid)
        {
        $query="SELECT acct_type FROM users WHERE userid=$userid and acct_type='System'";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));
        $data=$this->db_fetch_array($result);
        //If this is not a system account, then return TRUE (not an error, but false means no problems).
        if($data===false)
            return true;
        //Return no error.
        return false;
        }

    //This function checks to see if this account has a hold.
    function has_hold($userid,$context)
        {
        if(($is_user=$this->authenticate($userid))!==false)
            return $is_user;

        //See if there is a hold on this account.
        $result=$this->get_data($userid,$userid,"$context.Hold");
        if(!is_null($result))
            return $this->log_error(AERR_FAILURE,__FUNCTION__,AERR_MSG_ON_HOLD,$userid,'',array('context'=>$context));
        return false;
        }

    //This function checks to see if an account has a hold.
    function get_hold($viewerid,$userid,$context)
        {
        if(($is_user=$this->authenticate($viewerid))!==false)
            return $is_user;
        if($viewerid!=$userid)
            {
            $has_permission=$this->get_permission($viewerid,$context,"UPDATE_ACCOUNTS");
            if(is_auth_error($has_permission))
                return $has_permission;
            if($has_permission===true)
                return $this->log_error(AERR_NO_PERMISSION,__FUNCTION__,AERR_MSG_NO_PERMISSION,$viewerid,$userid,array('context'=>$context,'permission'=>$permission));
            }

        //See if there is a hold on this account.
        $query="SELECT value FROM user_data WHERE userid=$userid AND context='$context' AND name='Hold'";
        $result=$this->db_query($query);
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));
        #If there is no return value, then there is no hold
        $data=$this->db_fetch_array($result);
        if($data===false)
            return false;
        #Return the reason for the hold.
        return $data['value'];
        }

    function get_username($userid)
        {
        $query="SELECT name FROM users where userid=$userid";
        $result=$this->db_query($query);
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));
        $data=$this->db_fetch_array($result);
        //If there is no data, then the userid is bad.
        if($data===false)
            return $this->log_error(AERR_FAILURE,__FUNCTION__,AERR_MSG_NO_USERID);
        return $data['name'];
        }

    function count_logins($viewerid,$userid)
        {
        if(($is_user=$this->authenticate($viewerid))!==false)
            return $is_user;

        //Validate Permissions
        if($viewerid!=$userid)
            {
            $econtexts=list_memberships($viewerid,$viewerid);
            $ucontexts=list_memberships($userid,$userid);
            $contexts=array_intersect($econtexts,$ucontexts);
            $has_permission=true;
            foreach($contexts as $context)
                {
                $has_permission=$this->get_permission($viewerid,$context,"UPDATE_ACCOUNTS");
                if($has_permission===false)
                    break;
                if(is_auth_error($has_permission))
                    return $has_permission;
                }
            if ($has_permission!==false)
                return $this->log_error(AERR_NO_PERMISSION,__FUNCTION__,AERR_MSG_NO_PERMISSION,$viewerid,$userid);
            }

        //Count the user's logins.
        $query="SELECT count(userid) count FROM logins WHERE userid=$userid";
        $result=$this->db_query($query);
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));
        $data=$this->db_fetch_array($result);
        return $data['count'];
        }

    //This function logs out an account.
    function logout($userid)
        {
        if(($is_user=$this->authenticate($userid))!==false)
            return $is_user;

        //Determine if this account is a system account.
        $is_system=$this->is_system_account($userid);
        if(is_auth_error($is_system))
            return $is_system;

        //Update this player's login data.
        if($is_system===false) //This IS a system account
            $query="DELETE FROM logins WHERE auth_code='{$GLOBALS["__auth_code_$userid"]}' AND userid=$userid";
        else
            {
            $query="DELETE FROM logins WHERE auth_code='{$_COOKIE["auth_code"][0]}' AND userid=$userid";
            }
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));

        //Unset the hash code in a cookie or global.
        if($is_system===false) //This IS a system account
            $GLOBALS["__auth_code_$userid"]=null;
        else
            {
            //Generate expiration date.
            $expiry_date=strtotime('-1 day');
            //Remove the cookie.
            @setcookie("auth_code",'',$expiry_date,'/');
            unset($_COOKIE["auth_code"]);
            }
        return false;
        }

    //This function autneticates the id of the caller of the function.
    function authenticate($userid,$victimid='')
        {
        //Determine if this account is a system account.
        $is_system=$this->is_system_account($userid);
        if(is_auth_error($is_system))
            return $is_system;

        //Get the hash code FROM a cookie or global.
        if($is_system===true)
            {
            if(!isset($_COOKIE["auth_code"]))
                return $this->log_error(AERR_NO_AUTHENTICATION,__FUNCTION__,AERR_MSG_NO_COOKIE,$userid,$victimid);
            if(get_magic_quotes_gpc())
                list($auth_code,$authid)=unserialize(stripslashes($_COOKIE["auth_code"]));
            else
                list($auth_code,$authid)=unserialize($_COOKIE["auth_code"]);
            }
        else
            {
            if(!isset($GLOBALS["__auth_code_$userid"]))
                return $this->log_error(AERR_NO_AUTHENTICATION,__FUNCTION__,AERR_MSG_NO_COOKIE,$userid,$victimid);
            $auth_code=$GLOBALS["__auth_code_$userid"];
            $authid=$userid;
            }

        //Get the client ip.
        $ip=$_SERVER['REMOTE_ADDR'];
        //Get info on userid account.
        if($is_system===true)
            $query="SELECT last_auth, ip FROM logins WHERE userid=$userid AND userid=$authid AND auth_code='$auth_code'";
        else
            $query="SELECT last_auth, ip FROM logins WHERE userid=$userid AND auth_code='$auth_code'";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));
        $data=$this->db_fetch_array($result);
        //If no data is returned then the authenticate failed.
        if($data===false)
            return $this->log_error(AERR_NO_AUTHENTICATION,__FUNCTION__,AERR_MSG_NO_AUTH,$userid,$victimid);

        //Validate the auth_time.  If needed, log out.
        if(time()>strtotime(AUTH_TIMEOUT,strtotime($data['last_auth'])))
            {   
            $this->purge_expired_logins();
            return $this->log_error(AERR_NO_AUTHENTICATION,__FUNCTION__,AERR_MSG_OLD_LOGIN,$userid);
            }

        //Validate the IP.  If they do not match, then logout.
        //This helps clear out old logins.
        if($ip!=$data['ip'])
            {
            $this->logout($userid);
            return $this->log_error(AERR_NO_AUTHENTICATION,__FUNCTION__,AERR_MSG_NEW_IP,$userid);
            }

        //Update the last authenticated time.
        $last_auth=date('Y-m-d H:i:s');
        $query="UPDATE logins SET last_auth='$last_auth' WHERE userid=$userid";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));

        //If this is a user account, then try to update their cookie.
        if($is_system===true)
            {
            //Generate expiration date.
            $expiry_date=strtotime(AUTH_TIMEOUT);
            //Create the serial string.
            $string=serialize(array($auth_code,$userid));
            //Set the authentication cookie.
            @setcookie("auth_code",serialize(array($auth_code,$userid)),$expiry_date,'/');
            }

        //Return no errors.
        return false;
        }

    //This function gets a user's personal account data.
    function get_data($viewerid,$userid,$name)
        {
        if(($is_user=$this->authenticate($viewerid,$userid))!==false)
            return $is_user;
        list($context,$name)=explode('.',$name,2);
        //Check for hacking attempt.
        if(strpos(rawurlencode($context),'%')!==false
            || strpos(rawurlencode($name),'%')!==false)
            //We should log this possible hacking attempt here.
            return $this->log_error(AERR_HACK,__FUNCTION__,AERR_MSG_HACK,$viewerid,$userid,array('name'=>$name,'context'=>$context));

        //Validate Permissions
        if($viewerid!=$userid)
            {
            $has_permission=$this->get_permission($viewerid,$context,"ACCESS_OTHERS_DATA");
            if(is_auth_error($has_permission))
                return $has_permission;
            if($has_permission!==false)
                return $this->log_error(AERR_NO_PERMISSION,__FUNCTION__,AERR_MSG_NO_PERMISSION,$viewerid,$userid,array('context'=>$context,'permission'=>$permission));
            }
        $query="SELECT value FROM user_data WHERE name='$name' AND context='$context' AND userid=$userid";
        $result=$this->db_query($query);
        $data=$this->db_fetch_array($result);
        if($data===false)
            return null;
        return $data['value'];
        }

    //This function sets a user's personal account data.
    function set_data($editorid,$userid,$name,$value)
        {
        if(($is_user=$this->authenticate($editorid,$userid))!==false)
            return $is_user;
        list($context,$name)=explode('.',$name,2);
        //Check for hacking attempt.
        if(strpos(rawurlencode($context),'%')!==false
            || strpos($context,'.')!==false
            || strpos(rawurlencode($name),'%')!==false)
            //We should log this possible hacking attempt here.
            return $this->log_error(AERR_HACK,__FUNCTION__,AERR_MSG_HACK,$editorid,$userid,array('name'=>$name,'context'=>$context));

        //Prep value; It may be anything.
        $value=$this->db_escape_string($value);
        //Validate Permissions
        if($editorid!=$userid)
            {
            $has_permission=$this->get_permission($editorid,$context,"ACCESS_OTHERS_DATA");
            if(is_auth_error($has_permission))
                return $has_permission;
            if($has_permission!==false)
                return $this->log_error(AERR_NO_PERMISSION,__FUNCTION__,AERR_MSG_NO_PERMISSION,$editorid,$userid,array('context'=>$context,'permission'=>$permission));
            }
        if(is_null($value))
            $query="DELETE FROM user_data WHERE name='$name' AND context='$context' AND userid=$userid";
        else
            $query="REPLACE INTO user_data (userid,context,name,value) VALUES ($userid,'$context','$name','$value')";
        $result=$this->db_query($query);

        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));

        //Return no error
        return false;
        }

    //This function checks to see if an account has a specific permission.
    function get_permission($userid,$context,$permission,$victimid='')
        {
        //Check for hacking attempt.
        if(strpos(rawurlencode($permission),'%')!==false
            || strpos(rawurlencode($context),'%')!==false)
            //We should log this possible hacking attempt here.
            return $this->log_error(AERR_HACK,__FUNCTION__,AERR_MSG_HACK,$userid,$victimid,array('permission'=>$permission,'context'=>$context));

        $query="SELECT permission FROM user_permissions INNER JOIN permissions USING(permissionid) WHERE permission IN ('$permission','SUPERUSER') AND context IN ('$context','_') AND userid=$userid";
        $result=$this->db_query($query);

        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));

        $data=$this->db_fetch_array($result);
        if($data===false)
            return true;
        return false;
        }

    //This function sets or removes a specific permission an account may have.
    function set_permission($editorid,$userid,$context,$permission,$possesses)
        {
        if(($is_user=$this->authenticate($editorid,$userid))!==false)
            return $is_user;

        //Check for hacking attempt.
        if(strpos(rawurlencode($permission),'%')!==false
            || strpos(rawurlencode($context),'%')!==false)
            //We should log this possible hacking attempt here.
            return $this->log_error(AERR_HACK,__FUNCTION__,AERR_MSG_HACK,$viewerid,$userid,array('permission'=>$permission,'context'=>$context));

        //Idiot check- the only one who can alter the SUPERUSER
        //Permission is someone whith that permission.  Also, an
        //account with the SUPERUSER cannot remove their own SUPERUSER
        //permission.
        if ($permission=='SUPERUSER')
            {
            //If you don't have the SUPERUSER permission, then error.
            $has_su_permission=$this->get_permission($editorid,$context,"SUPERUSER");
            if(is_auth_error($has_su_permission))
                return $has_su_permission;
            if($has_su_permission!==false)
                return $this->log_error(AERR_NO_PERMISSION,__FUNCTION__,AERR_MSG_NO_PERMISSION,$editorid,$userid,array('context'=>$context,'permission'=>$permission));
            //If this SUPERUSER update is on self, then bail.
            if($userid==$editorid)
                return $this->log_error(AERR_NO_PERMISSION,__FUNCTION__,AERR_MSG_SELF_SUPERUSER,$editorid);
            }

        //Validate Permissions
        $has_permission=$this->get_permission($editorid,$context,"ALTER_PERMISSIONS",$userid);
        if(is_auth_error($has_permission))
            return $has_permission;
        if($has_permission!==false)
            return $this->log_error(AERR_NO_PERMISSION,__FUNCTION__,AERR_MSG_NO_PERMISSION,$editorid,$userid,array('context'=>$context,'permission'=>$permission));
        //First, see if the permission exists.
        $query="SELECT permissionid FROM permissions WHERE permission='$permission' AND context='$context'";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));

        //If no values were returned, then make the permission if it does not begin with a period.
        $data=$this->db_fetch_array($result);
        if($data===false)
            {
            if($context=='_')
                return $this->log_error(AERR_HACK,__FUNCTION__,AERR_MSG_BAD_CONTEXT,$editorid,$userid,array('permission'=>$permission,'context'=>$context));
            $query="REPLACE INTO permissions (context,permission) VALUES ('$context','$permission')";
            $result=$this->db_query($query);
            //If the query bombed, do_error.
            if($result===false)
                return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));

            $permissionid=$this->db_get_new_id($this->db_conn);
            }
        else
            $permissionid=$data['permissionid'];

        if($possesses===false)
            $query="DELETE FROM user_permissions WHERE permissionid=$permissionid and userid=$userid";
        else
            $query="REPLACE INTO user_permissions (userid,permissionid) VALUES ($userid,$permissionid)";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));

        return false;
        }

    //This function adds a context mebership to an account.
    function add_membership($userid,$context)
        {
        if(($is_user=$this->authenticate($userid))!==false)
            return $is_user;

        //If we are a part of this context, then bail.
        if($this->is_member($userid,$context)===false)
            return false;

        //System accounts cannot add membership.
        if($this->is_system_account($userid)===false)
            return $this->log_error(AERR_FAILURE,__FUNCTION__,AERR_MSG_NO_SYS_MEMBERSHIP,$userid,'',array('context'=>$context));

        //Check for hacking.
        if(strpos(rawurlencode($context),'%')!==false)
            return $this->log_error(AERR_HACK,__FUNCTION__,AERR_MSG_HACK,$userid,'',array('context'=>$context));

        //Add membership to this context.
        $query="REPLACE INTO user_membership (userid,context) VALUES ($userid,'$context')";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));

        //For argument- ensure that all system permissions are propogated
        //Into this context.
        $query="SELECT p1.permission FROM permissions p1 LEFT JOIN permissions p2 ON (p1.permission=p2.permission AND p2.context='$context') WHERE p1.context='_' AND p2.permissionid IS NULL";
        $result=$this->db_query($query);
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));
        while(($data=$this->db_fetch_array($result))!==false)
            {
            $query="INSERT INTO permissions (context,permission) VALUES ('$context','$data[permission]')";
            $result2=$this->db_query($query);
            if($result2===false)
                return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));
            }

        return false;
        }

    //This function identifies if an account is a member of a specific context.
    function is_member($userid,$context)
        {
        if(strpos(rawurlencode($context),'%')!==false)
            //We should log this possible hacking attempt here.
            return $this->log_error(AERR_HACK,__FUNCTION__,AERR_MSG_HACK,$userid,'',array('context'=>$context));

        $query="SELECT context FROM user_membership WHERE context='$context' AND userid=$userid";
        $result=$this->db_query($query);

        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));

        $data=$this->db_fetch_array($result);
        if($data===false)
            return true;  //Returning true to indicate NOT OK, but not error.

        //Return no errors.
        return false;
        }

    function email($to_name,$to,$subject,$message)
        {
        require AUTH_PASSWORD_DIR.'auth_pass.php';
        $result=email($smtp_host,$smtp_user,$smtp_pass,$from_name,$from_addr,$to_name,$to,$subject,$message);
        return $result;
        }

    //This function tries to create a new user account.
    function create_account($name,$password,$email)
        {
        //Ensure that this name does not have any non-alphanum chars in it.
        if(strpos(rawurlencode($name),'%')!==false)
            return $this->log_error(AERR_HACK,__FUNCTION__,AERR_MSG_HACK,'','',array('name'=>$name));

        //The name must have between 6 and 16 characters.
        if(strlen($name)<6 || strlen($name)>16)
            return $this->log_error(AERR_FAILURE,__FUNCTION__,AERR_MSG_BAD_NAME_LENGTH);

        //The password must have at least 6 characters.
        if(strlen($password)<6)
            return $this->log_error(AERR_FAILURE,__FUNCTION__,AERR_MSG_BAD_PASS_LENGTH);

        //Ensure that this email is valid
        if (preg_match_all('/@/',$email,$dud)!=1
            || preg_match_all('/\./',$email,$dud)<1 )
            return $this->log_error(AERR_FAILURE,__FUNCTION__,AERR_MSG_BAD_EMAIL);
        if(strpos($email,"'")!==false
            || strpos($email,"\\")!==false)
            return $this->log_error(AERR_HACK,__FUNCTION__,AERR_MSG_HACK,'','',array('email'=>$email));

        //Now purge expired accounts.
        $this->purge_expired_accounts();

        //First, see if the email address has already been used.
        $query="SELECT email FROM users WHERE email='$email'";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));

        //If a value was returned, then bail.
        $data=$this->db_fetch_array($result);
        if($data!==false)
            return $this->log_error(AERR_FAILURE,__FUNCTION__,AERR_MSG_USED_EMAIL);

        //Next, check to see if the name is already in use.
        $query="SELECT name FROM users WHERE name='$email'";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));

        //If a value was returned, then bail.
        $data=$this->db_fetch_array($result);
        if($data!==false)
            return $this->log_error(AERR_FAILURE,__FUNCTION__,AERR_MSG_USED_NAME);

        //OK, the scene is set.  Make the new account!
        $md5_pass=md5($password);
        $ip=$_SERVER['REMOTE_ADDR'];
        $last_login=date('Y-m-d H:i:s');
        $query="INSERT INTO users (name,password,email,last_login,acct_type) VALUES ('$name','$md5_pass','$email','$last_login','User')";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));

        //Get the id.
        $userid=$this->db_get_new_id($this->db_conn);

        //Now make and insert a confirmation hash.
        $hash=$ip.$last_login.$userid.$name.$password.$last_login.$ip;
        $confirm_hash=sha1($hash);
        $query="INSERT INTO user_data (userid,context,name,value) VALUES ($userid,'_','Confirm','$confirm_hash')";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));

        //Send the confirmation email.
        $site_address=get_current_url(true);
        $result=$this->email($name,$email,AUTH_NEW_ACCOUNT_SUBJECT,AUTH_NEW_ACCOUNT_INTRO."\n
In order to activate your account, click on the following link:
{$site_address}/signup.php?userid={$userid}&amp;confirm_hash={$confirm_hash}

If you cannot click on the above link, please copy and paste it into your web browser to activate your account.\n
".AUTH_NEW_ACCOUNT_OUTRO);
        if($result!==false)
            return $this->log_error(AERR_SYSTEM_FAILURE,__FUNCTION__,AERR_MSG_CANT_EMAIL,'','',array('email'=>$email,'error'=>$result));

        //Return no errors
        return false;
        }

    //This function confirms a new account with a confimation hash.
    function confirm_account($userid,$confirm_hash)
        {
        //Ensure the userid is numeric.
        if(!is_numeric($userid))
            return $this->log_error(AERR_HACK,__FUNCTION__,AERR_MSG_HACK,'','',array('userid'=>$userid));

        //Ensure that the hash does not have any non-alphanum chars in it.
        if(strpos(rawurlencode($confirm_hash),'%')!==false)
            return $this->log_error(AERR_HACK,__FUNCTION__,AERR_MSG_HACK,$userid,'',array('confirm_hash'=>$confirm_hash));

        $query="SELECT name FROM user_data WHERE userid=$userid AND context='_' AND name='Confirm' and value='$confirm_hash'";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));
        //If no value was returned, then bail.
        $data=$this->db_fetch_array($result);
        if($data===false)
            return $this->log_error(AERR_HACK,__FUNCTION__,AERR_MSG_BAD_AUTHCODE,$userid);

        $query="DELETE FROM user_data WHERE userid=$userid AND context='_' AND name='Confirm'";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));

        //Set the account to belong to the '_' context.
        //Allows for the account to be assigned super context permissions.
        //Does not assign super context permissions.
        $query="INSERT INTO user_membership (userid,context) VALUES ($userid,'_')";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));

        //For argument- ensure that all system permissions are propogated
        //Into this context.
        $query="SELECT p1.permission FROM permissions p1 LEFT JOIN permissions p2 ON (p1.permission=p2.permission AND p2.context='$context') WHERE p1.context='_' AND p2.permissionid IS NULL";
        $result=$this->db_query($query);
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));
        while(($data=$this->db_fetch_array($result))!==false)
            {
            $query="INSERT INTO permissions (context,permission) VALUES ('$context','$data[permission]')";
            $result2=$this->db_query($query);
            if($result2===false)
                return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));
            }

        return false;
        }

    //This function tries to create a new system account.
    function create_system_account($editorid,$context,$name,$password)
        {
        if(($is_user=$this->authenticate($editorid))!==false)
            return $is_user;
        $has_permission=$this->get_permission($editorid,"_","CREATE_SYSTEM_ACCOUNTS");
        if(is_auth_error($has_permission))
            return $has_permission;
        if($has_permission!==false)
            return $this->log_error(AERR_NO_PERMISSION,__FUNCTION__,AERR_MSG_NO_PERMISSION,$editorid,$userid,array('context'=>$context,'permission'=>$permission));

        //Ensure that this context and name does not have any non-alphanum chars in it.
        if(strpos(rawurlencode($context),'%')!==false
            || strpos(rawurlencode($name),'%')!==false)
            return $this->log_error(AERR_HACK,__FUNCTION__,AERR_MSG_HACK,$editorid,'',array('context'=>$context,'name'=>$name));

        //The context cannot be '_'.
        if($context=='_')
            return $this->log_error(AERR_FAILURE,__FUNCTION__,AERR_MSG_NO_SYS_MEMBERSHIP,$editorid);

        //First, see if the email address has already been used.
        $query="SELECT name FROM users WHERE name='$name'";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));
        //If a value was returned, then bail.
        $data=$this->db_fetch_array($result);
        if($data!==false)
            return $this->log_error(AERR_FAILURE,__FUNCTION__,AERR_MSG_USED_NAME);

        //OK, the scene is set.  Make the new account!
        $md5_pass=md5($password);
        $last_login=date('Y-m-d H:i:s');
        $query="INSERT INTO users (name,password,last_login,acct_type) VALUES ('$name','$md5_pass','$last_login','System')";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));

        //Get the id.
        $userid=$this->db_get_new_id($this->db_conn);

        //Now add the context.
        $query="REPLACE INTO user_membership (userid,context) VALUES ($userid,'$context')";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));

        //For argument- ensure that all system permissions are propogated
        //Into this context.
        $query="SELECT p1.permission FROM permissions p1 LEFT JOIN permissions p2 ON (p1.permission=p2.permission AND p2.context='$context') WHERE p1.context='_' AND p2.permissionid IS NULL";
        $result=$this->db_query($query);
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));
        while(($data=$this->db_fetch_array($result))!==false)
            {
            $query="INSERT INTO permissions (context,permission) VALUES ('$context','$data[permission]')";
            $result2=$this->db_query($query);
            if($result2===false)
                return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));
            }

        return false;
        }

    //This function tries to delete a user's own account.
    function delete_account($userid,$password)
        {
        if(($is_user=$this->authenticate($userid))!==false)
            return $is_user;

        //If you have the _.SUPERUSER permission, then error.
        $has_su_permission=$this->get_permission($userid,'_','SUPERUSER');
        if(is_auth_error($has_su_permission))
            return $has_su_permission;
        if($has_su_permission===false)
            return $this->log_error(AERR_NO_PERMISSION,__FUNCTION__,AERR_MSG_DELETE_SUPERUSER,$userid);

        //Determine if this account is a system account.
        $is_system=$this->is_system_account($userid);
        if(is_auth_error($is_system))
            return $is_system;
        if($is_system===false) //Is system account
            return $this->error_log(AERR_NO_PERMISSION,AERR_MSG_DELETE_SYSTEM,$userid);

        //Get the userid if this login is good.
        $md5_pass=md5($password);
        $query="SELECT userid FROM users WHERE userid=$userid and password='$md5_pass'";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));

        $data=$this->db_fetch_array($result);
        //If the login is bad then no data will be returned.
        if($data===false)
            return $this->log_error(AERR_FAILURE,__FUNCTION__,AERR_MSG_BAD_PASSWORD);

        //Logout account for the last time.
        $this->logout($userid);

        //Delete this account.
        $query="DELETE FROM user_permissions WHERE userid=$userid";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));
        $query="DELETE FROM user_data WHERE userid=$userid";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));
        $query="DELETE FROM users WHERE userid=$userid";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));
        $query="DELETE FROM user_permissions WHERE userid=$userid";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));
        }

    //This function tries to change an account's own password.
    function change_password($userid,$old_password,$new_password)
        {
        if(($is_user=$this->authenticate($userid))!==false)
            return $is_user;

        //The new password must have at least 6 characters.
        if(strlen($new_password)<6)
            return $this->log_error(AERR_FAILURE,__FUNCTION__,AERR_MSG_BAD_PASS_LENGTH);

        //Get the userid if this login is good.
        $md5_pass=md5($old_password);
        $query="SELECT userid FROM users WHERE userid=$userid and password='$md5_pass'";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));

        $data=$this->db_fetch_array($result);
        //If the login is bad then no data will be returned.
        if($data===false)
            return $this->log_error(AERR_FAILURE,__FUNCTION__,AERR_MSG_BAD_PASSWORD);

        //Update the password.
        $md5_pass=md5($new_password);
        $query="UPDATE users SET password='$md5_pass' WHERE userid=$userid";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));

        //Return no errors
        return false;
        }

    //This function tries to change a user account's own email.
    function change_email($userid,$password,$email)
        {
        if(($is_user=$this->authenticate($userid))!==false)
            return $is_user;

        //Ensure that this email is valid
        if (preg_match_all('/@/',$email,$dud)!=1
            || preg_match_all('/\./',$email,$dud)<1 )
            return $this->log_error(AERR_FAILURE,__FUNCTION__,AERR_MSG_BAD_EMAIL);
        if(strpos($email,"'")!==false
            || strpos($email,"\\")!==false)
            return $this->log_error(AERR_HACK,__FUNCTION__,AERR_MSG_HACK,$userid,'',array('email'=>$email));

        //Check the userid and password.
        $md5_pass=md5($password);
        $query="SELECT email FROM users WHERE userid=$userid and password='$md5_pass'";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));

        $data=$this->db_fetch_array($result);
        //If the login is bad then no data will be returned.
        if($data===false)
            return $this->log_error(AERR_FAILURE,__FUNCTION__,AERR_MSG_BAD_PASSWORD);

        //Set the old email address.
        $old_email=$data['email'];

        //See if the new email address is in use.
        $query="SELECT name FROM users WHERE email='$email'";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));

        $data=$this->db_fetch_array($result);
        //If there is data, then the email address is already in use.
        if($data!==false)
            return $this->log_error(AERR_FAILURE,__FUNCTION__,AERR_MSG_USED_EMAIL);

        //Save the name
        $name=$data['name'];

        //Update the email.
        $md5_pass=md5($new_password);
        $query="UPDATE users SET email='$email' WHERE userid=$userid";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));

        //now add a reconfirm on the account.
        $hash=$userid.$name.$password.date('Y-m-d H:i:s');
        $confirm_hash=sha1($hash);
        $query="INSERT INTO user_data (userid,context,name,value) VALUES ($userid,'_','Reconfirm','$confirm_hash'),($userid,'_','Reconfirm_Email','$email')";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));

        //Send the confirmation emails.
        $site_address=get_current_url(true);
        $result=$this->email($name,$email,AUTH_ACCOUNT_CHANGE_EMAIL_NEW_SUBJECT,AUTH_ACCOUNT_CHANGE_EMAIL_NEW_INTRO."
        If this is what you want to do, click on the following link:
{$site_address}/signup.php?userid={$userid}&amp;reconfirm_hash={$confirm_hash}

If you cannot click on the above link, please copy and paste it into your web browser to change your email address.
".AUTH_ACCOUNT_CHANGE_EMAIL_NEW_OUTRO);
        if($result!==false)
            return $this->log_error(AERR_SYSTEM_FAILURE,__FUNCTION__,AERR_MSG_CANT_EMAIL,'','',array('email'=>$email,'error'=>$result));
        $result=$this->email($name,$old_email,AUTH_ACCOUNT_CHANGE_EMAIL_OLD_SUBJECT,AUTH_ACCOUNT_CHANGE_EMAIL_OLD);
        if($result!==false)
            return $this->log_error(AERR_SYSTEM_FAILURE,__FUNCTION__,AERR_MSG_CANT_EMAIL,'','',array('email'=>$email,'error'=>$result));

        //Return no errors
        return false;
        }

    //This function confirms an email change using a confimation hash.
    function confirm_email_change($userid,$confirm_hash)
        {
        //Ensure the userid is numeric.
        if(!is_numeric($userid))
            return $this->log_error(AERR_HACK,__FUNCTION__,AERR_MSG_HACK,'','',array('userid'=>$userid));

        //Ensure that the hash does not have any non-alphanum chars in it.
        if(strpos(rawurlencode($confirm_hash),'%')!==false)
            return $this->log_error(AERR_HACK,__FUNCTION__,AERR_MSG_HACK,$userid,'',array('confirm_hash'=>$confirm_hash));

        $query="SELECT name FROM user_data WHERE userid=$userid AND context='_' AND name='Reconfirm' and value='$confirm_hash'";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));
        //If no value was returned, then bail.
        $data=$this->db_fetch_array($result);
        if($data===false)
            return $this->log_error(AERR_HACK,__FUNCTION__,AERR_MSG_BAD_AUTHCODE,$userid);

        //Get the new email address.
        $query="SELECT value FROM user_data WHERE userid=$userid AND context='_' AND name='Reconfirm_Email'";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));
        $data=$this->db_fetch_array($result);
        $email=$data['value'];

        //Update the old email.
        $query="UPDATE users SET email='$email' WHERE userid=$userid";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));

        //Delete the reference for the new email.
        $query="DELETE FROM user_data WHERE userid=$userid AND context='_' AND name IN ('Reconfirm','Reconfirm_Email')";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));

        return false;
        }

    //This function cancels an email address change.
    function cancel_email_change($editorid,$userid,$context)
        {
        if(($is_user=$this->authenticate($editorid,$userid))!==false)
            return $is_user;
        if($editorid!=$userid)
            {
            $has_permission=$this->get_permission($editorid,$context,"UPDATE_ACCOUNTS");
            if(is_auth_error($has_permission))
                return $has_permission;
            if($has_permission===true)
                return $this->log_error(AERR_NO_PERMISSION,__FUNCTION__,AERR_MSG_NO_PERMISSION,$editorid,$userid,array('context'=>$context,'permission'=>$permission));
            }
        if($this->is_member($userid,$context)!==false)
                return $this->log_error(AERR_NO_PERMISSION,__FUNCTION__,AERR_MSG_NOT_MEMBER,$editorid,$userid,array());
        //Cancel pending change
        $query="DELETE FROM user_data WHERE context='_' AND name IN ('Reconfirm','Reconfirm Email')";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));

        //Return no errors.
        return false;
        }

    //This function tries to change another account's password.
    function change_other_password($editorid,$userid,$context,$new_password)
        {
        if(($is_user=$this->authenticate($editorid,$userid))!==false)
            return $is_user;
        $has_permission=$this->get_permission($editorid,$context,"UPDATE_ACCOUNTS");
        if(is_auth_error($has_permission))
            return $has_permission;
        if($has_permission===true)
            return $this->log_error(AERR_NO_PERMISSION,__FUNCTION__,AERR_MSG_NO_PERMISSION,$editorid,$userid,array('context'=>$context,'permission'=>$permission));
        if($this->is_member($userid,$context)!==false)
            return $this->log_error(AERR_NO_PERMISSION,__FUNCTION__,AERR_MSG_NOT_MEMBER,'','',array('query'=>$query,'error'=>$this->db_error()));

        //Update the password.
        $md5_pass=md5($new_password);
        $query="UPDATE users SET password='$md5_pass' WHERE userid=$userid";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));

        //Return no errors
        return false;
        }

    //This function resets the account password by email FROM an email address.
    function reset_account_by_email($email)
        {
        //Ensure that this email is valid
        if (preg_match_all('/@/',$email,$dud)!=1
            || preg_match_all('/\./',$email,$dud)<1 )
            return $this->log_error(AERR_FAILURE,__FUNCTION__,AERR_MSG_BAD_EMAIL);
        if(strpos($email,"'")!==false
            || strpos($email,"\\")!==false)
            return $this->log_error(AERR_HACK,__FUNCTION__,AERR_MSG_HACK,'','',array('email'=>$email));

        //Get the userid if this login is good.
        $query="SELECT userid,name FROM users WHERE email='$email'";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));

        $data=$this->db_fetch_array($result);
        //If the login is bad then no data will be returned.
        if($data===false)
            return $this->log_error(AERR_FAILURE,__FUNCTION__,AERR_MSG_NO_EMAIL);
        $userid=$data['userid'];
        $name=$data['name'];

        //Set the reset hash on the account.
        $hash=$userid.$email.date('Y-m-d H:i:s');
        $confirm_hash=sha1($hash);
        $query="INSERT INTO user_data (userid,context,name,value) VALUES ($userid,'_','Reset','$confirm_hash')";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));

        //Send the confirmation email.
        $site_address=get_current_url(true);
        $result=$this->email($name,$email,AUTH_ACCOUNT_RESET_SUBJECT,AUTH_ACCOUNT_RESET_INTRO."
        If this is what you want to do, click on the following link:
{$site_address}/signup.php?userid={$userid}&amp;reset_hash={$confirm_hash}

If you cannot click on the above link, please copy and paste it into your web browser to reset your account.
".AUTH_ACCOUNT_RESET_OUTRO);
        if($result!==false)
            return $this->log_error(AERR_SYSTEM_FAILURE,__FUNCTION__,AERR_MSG_CANT_EMAIL,'','',array('email'=>$email,'error'=>$result));

        //Return no errors
        return false;
        }

    //This function resets the account password by email FROM an account name.
    function reset_account_by_name($name)
        {
        //Ensure that this name does not have any non-alphanum chars in it.
        if(strpos(rawurlencode($name),'%')!==false)
            return $this->log_error(AERR_HACK,__FUNCTION__,AERR_MSG_HACK,'','',array('name'=>$name));

        //Find the email address for this user and call the previous function.
        $query="SELECT email FROM users WHERE name='$name' AND acct_type='User'";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));
        //If no value was returned, then bail.
        $data=$this->db_fetch_array($result);
        if($data===false)
            return $this->log_error(AERR_FAILURE,__FUNCTION__,AERR_MSG_NO_NAME,'','',array('name'=>$name));

        //Now call reset_account_by_email.
        return $this->reset_account_by_email($data['email']);
        }

    //This function resets a user's password if the correct hash is given.
    function confirm_account_reset($userid,$confirm_hash)
        {
        //Ensure the userid is numeric.
        if(!is_numeric($userid))
            return $this->log_error(AERR_HACK,__FUNCTION__,AERR_MSG_HACK,'','',array('userid'=>$userid));

        //Ensure that the hash does not have any non-alphanum chars in it.
        if(strpos(rawurlencode($confirm_hash),'%')!==false)
            return $this->log_error(AERR_HACK,__FUNCTION__,AERR_MSG_HACK,$userid,'',array('confirm_hash'=>$confirm_hash));

        $query="SELECT name,email FROM user_data INNER JOIN users USING(userid) WHERE user_data.userid=$userid AND context='_' AND user_data.name='Reset' and value='$confirm_hash'";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));
        //If no value was returned, then bail.
        $data=$this->db_fetch_array($result);
        if($data===false)
            return $this->log_error(AERR_HACK,__FUNCTION__,AERR_MSG_BAD_AUTHCODE,$userid);
        $name=$data['name'];
        $email=$data['email'];

        //Delete the reference for the account reset.
        $query="DELETE FROM user_data WHERE userid=$userid AND context='_' AND name='Reset'";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));

        //Generate the new temporary password.
        $hash=$userid.$confirm_hash.date('Y-m-d H:i:s');
        $new_password=substr(sha1($hash),0,8);

        //Set the password
        $md5_pass=md5($new_password);
        $query="UPDATE users SET password='$md5_pass' WHERE userid=$userid";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));

        //Send an email with the new password.
        $site_address=get_current_url(true);
        $result=$this->email($name,$email,AUTH_ACCOUNT_RESET_DONE_SUBJECT,AUTH_ACCOUNT_RESET_DONE_INTRO."
This is your new password: {$new_password}
".AUTH_ACCOUNT_RESET_DONE_OUTRO);
        if($result!==false)
            return $this->log_error(AERR_SYSTEM_FAILURE,__FUNCTION__,AERR_MSG_CANT_EMAIL,'','',array('email'=>$email,'error'=>$result));

        //Return no errors.
        return false;
        }

    //This function cancels an account reset.
    function cancel_account_reset($editorid,$userid,$context)
        {
        if(($is_user=$this->authenticate($editorid,$userid))!==false)
            return $is_user;
        if($editorid!=$userid)
            {
            $has_permission=$this->get_permission($editorid,$context,"UPDATE_ACCOUNTS",$userid);
            if(is_auth_error($has_permission))
                return $has_permission;
            if($has_permission===true)
                return $this->log_error(AERR_NO_PERMISSION,__FUNCTION__,AERR_MSG_NO_PERMISSION,$editorid,$userid,array('context'=>$context,'permission'=>$permission));
            }
        if($this->is_member($userid,$context)!==false)
                return $this->log_error(AERR_NO_PERMISSION,__FUNCTION__,AERR_MSG_NOT_MEMBER,$editorid,$userid,array());
        //Cancel pending change
        $query="DELETE FROM user_data WHERE context='_' AND name='Reset'";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));

        return false;
        }

    //This function tries to delete another account.
    function delete_other_account($editorid,$userid,$context)
        {
        if(($is_user=$this->authenticate($editorid,$userid))!==false)
            return $is_user;
        $is_system=$this->is_system_account($userid);
        if(is_auth_error($is_system))
            return $is_system;
        elseif($is_system===true)
            {
            $has_permission=$this->get_permission($editorid,$context,"DELETE_ACCOUNTS");
            if(is_auth_error($has_permission))
                return $has_permission;
            if($has_permission===true)
                return $this->log_error(AERR_NO_PERMISSION,__FUNCTION__,AERR_MSG_NO_PERMISSION,$editorid,$userid,array('context'=>$context,'permission'=>$permission));
            if($this->is_member($userid,$context)!==false && $context!='_')
                return $this->log_error(AERR_NO_PERMISSION,__FUNCTION__,AERR_MSG_NOT_MEMBER,$editorid,$userid,array());
            }
        else
            {
            $has_permission=$this->get_permission($editorid,"_","CREATE_SYSTEM_ACCOUNTS");
            if(is_auth_error($has_permission))
                return $has_permission;
            if($has_permission===true)
                return $this->log_error(AERR_NO_PERMISSION,__FUNCTION__,AERR_MSG_NO_PERMISSION,$editorid,$userid,array('context'=>$context,'permission'=>$permission));
            }

        //SUPERUSER accounts may not be deleted.
        $has_su_permission=$this->get_permission($userid,"_","SUPERUSER");
        if(is_auth_error($has_su_permission))
            return $has_permission;
        if($has_su_permission===false)
            return $this->log_error(AERR_NO_PERMISSION,__FUNCTION__,AERR_MSG_DELETE_SUPERUSER,$editorid,$userid);

        //Delete this account.
        $query="DELETE FROM user_permissions WHERE userid=$userid";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));
        $query="DELETE FROM user_data WHERE userid=$userid";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));
        $query="DELETE FROM users WHERE userid=$userid";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));
        $query="DELETE FROM user_permissions WHERE userid=$userid";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));
        }


    //This function alters a hold on an account.
    function set_hold($editorid,$userid,$context,$status)
        {
        if(($is_user=$this->authenticate($editorid))!==false)
            return $is_user;
        $has_permission=$this->get_permission($editorid,$context."UPDATE_ACCOUNTS");
        if(is_auth_error($has_permission))
            return $has_permission;
        if($has_permission===true)
            return $this->log_error(AERR_NO_PERMISSION,__FUNCTION__,AERR_MSG_NO_PERMISSION,$editorid,$userid,array('context'=>$context,'permission'=>$permission));
        if($this->is_member($userid,$context)!==false)
                return $this->log_error(AERR_NO_PERMISSION,__FUNCTION__,AERR_MSG_NOT_MEMBER,$editorid,$userid,array());
        $is_system=$this->is_system_account($userid);
        if(is_auth_error($is_system))
            return $is_system;
        if($is_system===false)
            return $this->log_error(AERR_FAILURE,__FUNCTION__,AERR_MSG_CANT_HOLD_SYSTEM,$editorid,$userid,array());

        //Ensure that the status does not have any non-alphanum chars in it.
        if(strpos(rawurlencode(str_replace(' ','',$status)),'%')!==false)
            return $this->log_error(AERR_HACK,__FUNCTION__,AERR_MSG_HACK,'','',array('status'=>$status));

        if ($status!=false)
            {
            $result=$this->boot_account($editorid,$userid,$context);
            if($result!==false)
                return $result;
            $query="REPLACE INTO user_data (userid,context,name,value) VALUES ($userid,'$context','Hold','$status')";
            }
        else
            $query="DELETE FROM user_data WHERE userid=$userid AND name='Hold' AND context='$context'";
        #Un/set the hold.
        $result=$this->db_query($query);
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));
        return false;
        }

    //This function completely logs out one userid.
    //This is used in conjunction with account holds.
    function boot_account($editorid,$userid,$context)
        {
        if(($is_user=$this->authenticate($editorid))!==false)
            return $is_user;
        if($editorid!=$userid)
            {
            $has_permission=$this->get_permission($editorid,$context,"UPDATE_ACCOUNTS");
            if(is_auth_error($has_permission))
                return $has_permission;
            if($has_permission===true)
                return $this->log_error(AERR_NO_PERMISSION,__FUNCTION__,AERR_MSG_NO_PERMISSION,$editorid,$userid,array('context'=>$context,'permission'=>$permission));
            if($this->is_member($userid,$context)!==false)
                return $this->log_error(AERR_NO_PERMISSION,__FUNCTION__,AERR_MSG_NOT_MEMBER,$editorid,$userid,array());
            }
        $is_system=$this->is_system_account($userid);
        if(is_auth_error($is_system))
            return $is_system;
        if($is_system===false)
            return $this->log_error(AERR_FAILURE,__FUNCTION__,AERR_MSG_CANT_BOOT_SYSTEM,$editorid,$userid,array());

        $query="DELETE FROM logins WHERE userid=$userid";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));

        //Return no errors
        return false;
        }

    //This function returns an array of the memberships an account has.
    function is_admin($userid,$context='')
        {
        if(($is_user=$this->authenticate($userid))!==false)
            return $is_user;

        //See if the user has standard admin permissions.
        if($context!='')
            $query="SELECT p1.context FROM user_permissions INNER JOIN permissions p1 USING (permissionid) INNER JOIN permissions p2 using(permission) WHERE userid=$userid AND p2.context='_' AND p1.context='$context'";
        else
            $query="SELECT p1.context FROM user_permissions INNER JOIN permissions p1 USING (permissionid) INNER JOIN permissions p2 using(permission) WHERE userid=$userid AND p2.context='_'";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));
        $data=$this->db_fetch_array($result);
        //If there are no permissions, then return true.
        if($data===false)
            return true;

        //Return no errors.
        return false;
        }

    //This function returns an array of the memberships an account has.
    function list_memberships($viewerid,$userid)
        {
        if(($is_user=$this->authenticate($viewerid))!==false)
            return $is_user;

        $query="SELECT context FROM user_membership WHERE userid=$userid";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));
        $retval=array();
        while(($data=$this->db_fetch_array($result))!==false)
            $retval[]=$data['context'];

        //Return no errors
        return $retval;
        }

    //This function returns an array of the memberships an account has.
    function list_admin_memberships($userid)
        {
        if(($is_user=$this->authenticate($userid))!==false)
            return $is_user;

        $query="SELECT context FROM permissions WHERE context='_'";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));
        $data=$this->db_fetch_array($result);
        if($data===false)
            return $this->list_memberships($userid,$userid);

        $query="SELECT DISTINCT context FROM permissions";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));
        $retval=array();
        while(($data=$this->db_fetch_array($result))!==false)
            $retval[]=$data['context'];

        //Return no errors
        return $retval;
        }

    //This function retuns an array of accounts in a context.
    function list_accounts($editorid,$context)
        {
        if(($is_user=$this->authenticate($editorid))!==false)
            return $is_user;
        $has_permission=$this->get_permission($editorid,$context,"LIST_ACCOUNTS");
        if(is_auth_error($has_permission))
            return $has_permission;
        if($has_permission===true)
            return $this->log_error(AERR_NO_PERMISSION,__FUNCTION__,AERR_MSG_NO_PERMISSION,$editorid,$userid,array('context'=>$context,'permission'=>$permission));

        //Query all users of the context.
        if($context==='_')
            $query="SELECT users.userid, name FROM users order by name";
        else
            $query="SELECT users.userid, name FROM users INNER JOIN user_membership USING(userid) WHERE context='$context' order by name";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));
        $retval=array();
        while(($data=$this->db_fetch_array($result))!==false)
            {
            $status=array();
            $hold_info='';

            $is_system=$this->is_system_account($data['userid']);
            if(is_auth_error($is_system))
                return $is_system;
            if($is_system===false)
                $status[]='System';

            $query="SELECT name, value FROM user_data WHERE userid=$data[userid] AND name IN('Hold','Confirm','Reconfirm','Reset')";
            $result2=$this->db_query($query);
            if($result2===false)
                return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));
            while(($data2=$this->db_fetch_array($result2))!==false)
                {
                $status[]=$data2['name'];
                if($data2['name']=='Hold')
                    $hold_info=$data2['value'];
                }

            $query="SELECT count(userid) count FROM logins WHERE userid=$data[userid]";
            $result2=$this->db_query($query);
            if($result2===false)
                return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));
            $data2=$this->db_fetch_array($result2);

            $retval[$data['userid']]=array(
                'userid'=>$data['userid'],
                'name'=>$data['name'],
                'login_count'=>$data2['count'],
                'status'=>implode(',',$status),
                'hold_info'=>$hold_info);
            }

        //Return no errors
        return $retval;
        }

    //This function returns an array of permissions the system knows of.
    function list_permissions($viewerid,$context)
        {
        if(($is_user=$this->authenticate($viewerid))!==false)
            return $is_user;
        $has_permission=$this->get_permission($viewerid,$context,"ALTER_PERMISSIONS");
        if(is_auth_error($has_permission))
            return $has_permission;
        if($has_permission===true)
            return $this->log_error(AERR_NO_PERMISSION,__FUNCTION__,AERR_MSG_NO_PERMISSION,$viewerid,$userid,array('context'=>$context,'permission'=>$permission));

        //For argument- ensure that all system permissions are propogated
        //Into this context.
        $query="SELECT p1.permission FROM permissions p1 LEFT JOIN permissions p2 ON (p1.permission=p2.permission AND p2.context='$context') WHERE p1.context='_' AND p2.permissionid IS NULL";
        $result=$this->db_query($query);
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));
        while(($data=$this->db_fetch_array($result))!==false)
            {
            $query="INSERT INTO permissions (context,permission) VALUES ('$context','$data[permission]')";
            $result2=$this->db_query($query);
            if($result2===false)
                return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));
            }

        //Query all permissions of the context.
        $query="SELECT context, permission FROM permissions WHERE context='$context' OR '_'='$context'";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));
        $retval=array();
        while(($data=$this->db_fetch_array($result))!==false)
            $retval[]=$data;
        return $retval;
        }

    //This function lists the permissions an account has in a context.
    function list_account_permissions($viewerid,$userid,$context)
        {
        if(($is_user=$this->authenticate($viewerid,$userid))!==false)
            return $is_user;
        $has_permission=$this->get_permission($viewerid,$context,"ALTER_PERMISSIONS");
        if(is_auth_error($has_permission))
            return $has_permission;
        if($has_permission===true)
            return $this->log_error(AERR_NO_PERMISSION,__FUNCTION__,AERR_MSG_NO_PERMISSION,$viewerid,$userid,array('context'=>$context,'permission'=>$permission));
        if($this->is_member($userid,$context)!==false && $context!='_')
                return $this->log_error(AERR_NO_PERMISSION,__FUNCTION__,AERR_MSG_NOT_MEMBER,$viewerid,$userid,array());

        //Query all permissions of the context for this user.
        $query="SELECT context, permission FROM permissions INNER JOIN user_permissions USING(permissionid) WHERE userid=$userid AND (context='$context' OR '_'='$context')";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));
        $retval=array();
        while(($data=$this->db_fetch_array($result))!==false)
            $retval[]=array($data['context'],$data['permission']);
        return $retval;
        }

    //This function lists the data an account has in a context.
    function list_account_data($viewerid,$userid,$context)
        {
        if(($is_user=$this->authenticate($viewerid,$userid))!==false)
            return $is_user;
        $has_permission=$this->get_permission($viewerid,$context,"ACCESS_OTHERS_DATA");
        if(is_auth_error($has_permission))
            return $has_permission;
        if($has_permission===true)
            return $this->log_error(AERR_NO_PERMISSION,__FUNCTION__,AERR_MSG_NO_PERMISSION,$viewerid,$userid,array('context'=>$context,'permission'=>$permission));
        if($this->is_member($userid,$context)!==false)
                return $this->log_error(AERR_NO_PERMISSION,__FUNCTION__,AERR_MSG_NOT_MEMBER,$viewerid,$userid,array());

        //Query all data of the context for this user.
        $query="SELECT context, name, value FROM user_data WHERE userid=$userid AND (context='$context' OR '_'='$context')";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));
        $retval=array();
        while(($data=$this->db_fetch_array($result))!==false)
            $retval[]=array($data['context'],$data['name'],$data['value']);
        return $retval;
        }

    //This function lists the email addresses of accounts in a context.
    function list_email_addresses($viewerid,$context)
        {
        if(($is_user=$this->authenticate($viewerid))!==false)
            return $is_user;
        $has_permission=$this->get_permission($viewerid,$context,"LIST_EMAILS");
        if(is_auth_error($has_permission))
            return $has_permission;
        if($has_permission===true)
            return $this->log_error(AERR_NO_PERMISSION,__FUNCTION__,AERR_MSG_NO_PERMISSION,$viewerid,'',array('context'=>$context,'permission'=>$permission));

        //Query all permissions of the context for this user.
        $query="SELECT userid, name, email FROM users WHERE context='$context' OR '_'='$context'";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));
        $retval=array();
        while(($data=$this->db_fetch_array($result))!==false)
            $retval[]=array(
                'name'=>$data['name'],
                'email'=>$data['email']);
        return $retval;
        }

    function list_errors($viewerid,$context)
        {
        if(($is_user=$this->authenticate($viewerid))!==false)
            return $is_user;
        $has_permission=$this->get_permission($viewerid,$context,"VIEW_ERROR_LOG");
        if(is_auth_error($has_permission))
            return $has_permission;
        if($has_permission===true)
            return $this->log_error(AERR_NO_PERMISSION,__FUNCTION__,AERR_MSG_NO_PERMISSION,$viewerid,'',array('context'=>$context,'permission'=>$permission));

        //Query all errors of the context for this user.
        $query="SELECT errorid, date, error, function, msg, ip, offenderid, victimid FROM errors WHERE context='$context' OR '_'='$context'";
        $result=$this->db_query($query);
        //If the query bombed, do_error.
        if($result===false)
            return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));
        $retval=array();
        while(($data=$this->db_fetch_array($result))!==false)
            {
            $array=array(
                'name'=>$data['name'],
                'email'=>$data['email']);
            $query="SELECT name,data FROM error_data WHERE errorid=$data[errorid]";
            $result2=$this->db_query($query);
            //If the query bombed, do_error.
            if($result2===false)
                return $this->log_error(AERR_DB_ERROR,__FUNCTION__,AERR_MSG_BAD_QUERY,'','',array('query'=>$query,'error'=>$this->db_error()));
            while(($data2=$this->db_fetch_array($result2))!==false)
                $array[$data2['name']]=$data2['data'];
            $retval[]=$array;
            }
        return $retval;
        }

    function filter_error_list_by_date($list,$start_date,$end_date)
        {
        $start_date=date('Y-m-d H:i:s',strtotime($start_date));
        $end_date=date('Y-m-d H:i:s',strtotime($end_date));
        $retval=array();
        foreach($list as $item)
            if($item['date']>=$start_date && $item['date']<=$end_date)
                $retval[]=$item;
        return $retval;
        }

    function filter_error_list_by_field($list,$field,$value)
        {
        $retval=array();
        foreach($list as $item)
            if(isset($item[$field])
                && ((!is_null($value) && $item[$field]==$value)
                || (is_null($value) && is_null($item[$field]))))
                $retval[]=$item;
        return $retval;
        }

    function filter_error_list_by_field_contianing($list,$field,$value)
        {
        $retval=array();
        foreach($list as $item)
            if(isset($item[$field]) && strpos($value,$item[$field])==-1)
                $retval[]=$item;
        return $retval;
        }
    }
?>
