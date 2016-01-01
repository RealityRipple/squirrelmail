<?php

/**
 * Merakchange password backend
 *
 * @author Edwin van Elk <edwin at eve-software.com>
 * @copyright 2004-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage change_password
 */

/**
 * Config vars
 */

global $merak_url, $merak_selfpage, $merak_action;

// The Merak Server

$merak_url = "http://localhost:32000/";
$merak_selfpage = "self.html";
$merak_action = "self_edit";

// get overrides from config.
if ( isset($cpw_merak) && is_array($cpw_merak) && !empty($cpw_merak) ) {
  foreach ( $cpw_merak as $key => $value ) {
    if ( isset(${'merak_'.$key}) )
      ${'merak_'.$key} = $value;
  }
}

global $squirrelmail_plugin_hooks;
$squirrelmail_plugin_hooks['change_password_dochange']['merak'] =
   'cpw_merak_dochange';
$squirrelmail_plugin_hooks['change_password_init']['merak'] =
   'cpw_merak_init';

/**
 * Check if php install has all required extensions.
 */
function cpw_merak_init() {
    global $oTemplate;

    if (!function_exists('curl_init')) {
        // user_error('Curl module NOT available!', E_USER_ERROR);
        error_box(_("PHP Curl extension is NOT available! Unable to change password!"));
        // close html and stop script execution
        $oTemplate->display('footer.tpl');
        exit();
    }
}

/**
 * This is the function that is specific to your backend. It takes
 * the current password (as supplied by the user) and the desired
 * new password. It will return an array of messages. If everything
 * was successful, the array will be empty. Else, it will contain
 * the errormessage(s).
 * Constants to be used for these messages:
 * CPW_CURRENT_NOMATCH -> "Your current password is not correct."
 * CPW_INVALID_PW -> "Your new password contains invalid characters."
 *
 * @param array data The username/currentpw/newpw data.
 * @return array Array of error messages.
 */
function cpw_merak_dochange($data)
{
   // unfortunately, we can only pass one parameter to a hook function,
   // so we have to pass it as an array.
   $username = $data['username'];
   $curpw = $data['curpw'];
   $newpw = $data['newpw'];

   $msgs = array();

   global $merak_url, $merak_selfpage, $merak_action;

   $ch = curl_init();
   curl_setopt ($ch, CURLOPT_URL, $merak_url . $merak_selfpage);
   curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
   curl_setopt ($ch, CURLOPT_TIMEOUT, 10);
   curl_setopt ($ch, CURLOPT_USERPWD, "$username:$curpw");
   curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);
   $result = curl_exec ($ch);
   curl_close ($ch);

   if (strpos($result, "401 Access denied") <> 0) {
      array_push($msgs, _("Cannot change password! (Is user 'Self Configurable User' ?) (401)"));
      return $msgs;
   }

   // Get URL from: <FORM METHOD="POST" ACTION="success.html?id=a9375ee5e445775e871d5e1401a963aa">

   $str = stristr($result, "<FORM");
   $str = substr($str, 0, strpos($str, ">") + 1);
   $str = stristr($str, "ACTION=");
   $str = substr(stristr($str, "\""),1);
   $str = substr($str, 0, strpos($str, "\""));

   // Extra check to see if the result contains 'html'
   if (!stristr($str, "html")) {
      array_push($msgs, _("Cannot change password!") . " (1)" );
      return $msgs;
   }

   $newurl = $merak_url . $str;

   // Get useraddr from: $useraddr = <INPUT TYPE="HIDDEN" NAME="usraddr" VALUE="mail@hostname.com">

   $str = stristr($result, "usraddr");
   $str = substr($str, 0, strpos($str, ">") + 1);
   $str = stristr($str, "VALUE=");
   $str = substr(stristr($str, "\""),1);
   $str = substr($str, 0, strpos($str, "\""));

   // Extra check to see if the result contains '@'
   if (!stristr($str, "@")) {
      array_push($msgs, _("Cannot change password!") . " (2)" );
      return $msgs;
   }

   $useraddr = $str;

   //Include (almost) all input fields from screen

   $contents2 = $result;

   $tag = stristr($contents2, "<INPUT");

   while ($tag) {
      $contents2 = stristr($contents2, "<INPUT");
      $tag = substr($contents2, 0, strpos($contents2, ">") + 1);

      if (GetSub($tag, "TYPE") == "TEXT" ||
          GetSub($tag, "TYPE") == "HIDDEN" ||
          GetSub($tag, "TYPE") == "PASSWORD") {
         $tags[GetSub($tag, "NAME")] = GetSub($tag, "VALUE");
      }

      if ((GetSub($tag, "TYPE") == "RADIO" ||
           GetSub($tag, "TYPE") == "CHECKBOX") &&
          IsChecked($tag)) {
         $tags[GetSub($tag, "NAME")] = GetSub($tag, "VALUE");
      }
      $contents2 = substr($contents2, 1);
   }

   $tags["action"]   = $merak_action;
   $tags["usraddr"]  = $useraddr;
   $tags["usr_pass"] = $newpw;
   $tags["usr_conf"] = $newpw;

   $str2 = "";
   foreach ($tags as $key => $value) {
      $str2 .= $key . "=" . urlencode($value) . "&";
   }

   $str2 = trim($str2, "&");

   // Change password!

   $ch = curl_init();
   curl_setopt ($ch, CURLOPT_URL, $newurl);
   curl_setopt ($ch, CURLOPT_POST, 1);
   curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
   curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);
   curl_setopt ($ch, CURLOPT_POSTFIELDS, $str2);
   $result=curl_exec ($ch);
   curl_close ($ch);

   if (strpos($result, "Failure") <> 0) {
     array_push($msgs, _("Cannot change password!") .  " (3)");
     return $msgs;
   }

   return $msgs;
}

function GetSub($tag, $type) {

   $str = stristr($tag, $type . "=");
   $str = substr($str, strlen($type) + 1);
   $str = trim($str, '"');

   if (!strpos($str, " ") === false) {
      $str = substr($str, 0, strpos($str, " "));
      $str = trim($str, '"');
   }

   if (!(strpos($str, '"') === false)) {
      $str = substr($str, 0, strpos($str, '"'));
   }

   $str = trim($str, '>');

   return $str;
}

function IsChecked($tag) {

   if (!(strpos(strtolower($tag), 'checked') === false)) {
      return true;
   }

   return false;
}
