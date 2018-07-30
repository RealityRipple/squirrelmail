<?php

/**
  * SquirrelMail Secure Login Plugin
  * Copyright (c) 2002 Graham Norbury <gnorbury@bondcar.com>
  * Copyright (c) 2003-2008 Paul Lesniewski <paul@squirrelmail.org>
  * Licensed under the GNU GPL. For full terms see the file COPYING.
  *
  * @package plugins
  * @subpackage secure_login
  *
  */


/**
  * Register this plugin with SquirrelMail
  *
  */
function squirrelmail_plugin_init_secure_login()
{

   global $squirrelmail_plugin_hooks;

   $squirrelmail_plugin_hooks['login_cookie']['secure_login'] = 'secure_login_check';
   $squirrelmail_plugin_hooks['webmail_top']['secure_login'] = 'secure_login_logout';
   $squirrelmail_plugin_hooks['configtest']['secure_login'] = 'sl_check_configuration';

}



/**
  * Returns info about this plugin
  *
  */
function secure_login_info()
{

   return array(
                 'english_name' => 'Secure Login',
                 'authors' => array(
                    'Paul Lesniewski' => array(
                       'email' => 'paul@squirrelmail.org',
                       'sm_site_username' => 'pdontthink',
                    ),
                 ),
                 'version' => '1.4',
                 'required_sm_version' => '1.2.8',
                 'requires_configuration' => 0,
                 'requires_source_patch' => 0,
                 'required_plugins' => array(),
                 'summary' => 'Ensures SSL security is enabled during login (at least).',
                 'details' => 'This plugin automatically enables a secure HTTPS/SSL-encrypted connection for the SquirrelMail login page if it hasn\'t already been requested by the referring hyperlink or bookmark.  Optionally, the secure connection can be turned off again after successful login.  This utility is intended to prevent passwords and email contents being transmitted over the Internet in the clear after people browse to the login page without including https:// in its address.',
               );

}



/**
  * Returns version info about this plugin
  *
  */
function secure_login_version()
{

   $info = secure_login_info();
   return $info['version'];

}



/**
  * Validate that this plugin is configured correctly
  *
  * @return boolean Whether or not there was a
  *                 configuration error for this plugin.
  *
  */
function sl_check_configuration()
{

   include_once(SM_PATH . 'plugins/secure_login/functions.php');
   return sl_check_configuration_do();

}



/**
  * Makes sure login is done in HTTPS protocol
  *
  */
function secure_login_check()
{

   include_once(SM_PATH . 'plugins/secure_login/functions.php');
   secure_login_check_do();

}



/** 
  * Redirects back to HTTP protocol after login if necessary
  * 
  */
function secure_login_logout()
{

   include_once(SM_PATH . 'plugins/secure_login/functions.php');
   secure_login_logout_do();

}



