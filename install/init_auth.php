<?php
function init_system($host,$sysuser,$syspass,$db,$svc_user,$svc_pass,$svc_host,$user,$password,$sysadmin_email)
    {
    //Try to connect as a db superuser.
    $resource=mysql_connect($host,$sysuser,$syspass);
    if($resource===false)
        return "Failed to connect to the server $host with user $sysuser and password $syspass";

    $md5_pass=md5($password);
    $query=<<<EOD
DROP DATABASE IF EXISTS `$db`;

FLUSH TABLES;

CREATE DATABASE `$db`;
USE `$db`;

GRANT SELECT,INSERT,UPDATE,DELETE ON $db.* TO $svc_user@'$svc_host' IDENTIFIED BY '$svc_pass';

CREATE TABLE `errors` (
  `errorid` int(11) unsigned NOT NULL auto_increment,
  `date` datetime NOT NULL default '0000-00-00 00:00:00',
  `error` int(11) unsigned NOT NULL default '0',
  `function` varchar(32) NOT NULL default '',
  `msg` varchar(255) NOT NULL default '',
  `ip` varchar(15) NOT NULL default '',
  `offenderid` int(11) unsigned NOT NULL default '0',
  `victimid` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`errorid`),
  KEY `date` (`date`),
  KEY `error` (`error`),
  KEY `ip` (`ip`),
  KEY `offenderid` (`offenderid`),
  KEY `victimid` (`victimid`)
) TYPE=MyISAM;

CREATE TABLE `error_data` (
  `errorid` int(11) unsigned NOT NULL,
  `name` varchar(64) NOT NULL default '',
  `data` text NOT NULL default '',
  KEY `errorid` (`errorid`),
  KEY `name` (`name`)
) TYPE=MyISAM;

CREATE TABLE `permissions` (
  `permissionid` int(11) unsigned NOT NULL auto_increment,
  `context` varchar(64) NOT NULL default '',
  `permission` varchar(64) NOT NULL default '',
  PRIMARY KEY  (`permissionid`),
  UNIQUE KEY `context_permission` (`context`,`permission`),
  KEY `permission` (`permission`)
) TYPE=MyISAM;

INSERT INTO `permissions` (permissionid, context, permission) VALUES (1, '_', 'SUPERUSER');
INSERT INTO `permissions` (permissionid, context, permission) VALUES (2, '_', 'CREATE_SYSTEM_ACCOUNTS');
INSERT INTO `permissions` (permissionid, context, permission) VALUES (3, '_', 'UPDATE_ACCOUNTS');
INSERT INTO `permissions` (permissionid, context, permission) VALUES (4, '_', 'DELETE_ACCOUNTS');
INSERT INTO `permissions` (permissionid, context, permission) VALUES (5, '_', 'ALTER_PERMISSIONS');
INSERT INTO `permissions` (permissionid, context, permission) VALUES (6, '_', 'ALTER_OTHERS_DATA');
INSERT INTO `permissions` (permissionid, context, permission) VALUES (7, '_', 'ACCESS_OTHERS_DATA');
INSERT INTO `permissions` (permissionid, context, permission) VALUES (8, '_', 'LIST_ACCOUNTS');
INSERT INTO `permissions` (permissionid, context, permission) VALUES (9, '_', 'LIST_EMAILS');
INSERT INTO `permissions` (permissionid, context, permission) VALUES (10, '_', 'VIEW_ERROR_LOG');

CREATE TABLE `user_data` (
  `userid` int(11) unsigned NOT NULL default '0',
  `context` varchar(64) NOT NULL default '',
  `name` varchar(64) NOT NULL default '',
  `value` varchar(64) NOT NULL default '',
  PRIMARY KEY  (`userid`,`context`,`name`)
) TYPE=MyISAM;

CREATE TABLE `user_membership` (
  `userid` int(11) unsigned NOT NULL default '0',
  `context` varchar(64) NOT NULL default '',
  PRIMARY KEY  (`userid`,`context`),
  KEY `context` (`context`)
) TYPE=MyISAM;

INSERT INTO `user_membership` (userid, context) VALUES (1, '_');

CREATE TABLE `user_permissions` (
  `userid` int(11) unsigned NOT NULL default '0',
  `permissionid` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`userid`,`permissionid`),
  KEY `permissionid` (`permissionid`)
) TYPE=MyISAM;

INSERT INTO `user_permissions` (userid, permissionid) VALUES (1, 1);

CREATE TABLE `users` (
  `userid` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(16) NOT NULL,
  `password` varchar(32) NOT NULL,
  `email` varchar(64) NOT NULL,
  `last_login` datetime NOT NULL default '0000-00-00 00:00:00',
  `acct_type` enum('User','System') NOT NULL default 'User',
  PRIMARY KEY  (`userid`),
  KEY `email` (`email`),
  UNIQUE KEY `name_acct_type` (`name`,`acct_type`)
) TYPE=MyISAM;

INSERT INTO `users` (userid, name, password, email, last_login, acct_type) VALUES (1, '$user', '$md5_pass', '$sysadmin_email', NOW(), 'User');

CREATE TABLE `logins` (
  `userid` int(11) unsigned NOT NULL,
  `auth_code` varchar(40) NOT NULL default '',
  `ip` varchar(15) NULL,
  `last_auth` datetime NOT NULL default '0000-00-00 00:00:00',
  UNIQUE KEY  (`userid`,`auth_code`,`ip`),
  KEY `last_auth` (`last_auth`)
) TYPE=MyISAM;

EOD;
    foreach(explode(';',$query) as $stub)
        {
        if(trim($stub)!='')
            $result=mysql_query($stub,$resource);
        if($result===false)
            {
            $error=mysql_error();
            mysql_close($resource);
            return "Failed Query: $stub Error: $error";
            }
        }
    mysql_close($resource);
    return false;
    }
?>
