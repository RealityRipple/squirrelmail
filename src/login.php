<?php

/**
 * login.php -- simple login screen
 *
 * Copyright (c) 1999-2003 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This a simple login screen. Some housekeeping is done to clean
 * cookies and find language.
 *
 * $Id$
 * @package squirrelmail
 */

/** Path for SquirrelMail required files. */
define('SM_PATH','../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'functions/strings.php');
require_once(SM_PATH . 'config/config.php');
require_once(SM_PATH . 'functions/i18n.php');
require_once(SM_PATH . 'functions/plugin.php');
require_once(SM_PATH . 'functions/constants.php');
require_once(SM_PATH . 'functions/page_header.php');
require_once(SM_PATH . 'functions/html.php');
require_once(SM_PATH . 'functions/global.php');
require_once(SM_PATH . 'functions/imap_general.php');

/**
 * $squirrelmail_language is set by a cookie when the user selects
 * language and logs out
 */
set_up_language($squirrelmail_language, TRUE, TRUE);

/**
 * Find out the base URI to set cookies.
 */
if (!function_exists('sqm_baseuri')){
    require_once(SM_PATH . 'functions/display_messages.php');
}
$base_uri = sqm_baseuri();

/*
 * In case the last session was not terminated properly, make sure
 * we get a new one.
 */

sqsession_destroy();
 
header('Pragma: no-cache');

/**
 * This detects if the IMAP server has logins disabled, and if so, 
 * squelches the display of the login form and puts up a message
 * explaining the situation.
 */
$imap = sqimap_create_stream($imapServerAddress, $imapPort, $use_imap_tls);
$logindisabled = sqimap_capability($imap,'LOGINDISABLED');
sqimap_logout($imap);
if ($logindisabled) {
    $string = "The IMAP server is reporting that logins are disabled.<br>";
    if (!$use_imap_tls) {
        $string .= "The use of TLS may allow SquirrelMail to login.<br>";
    }
    $string .= "Please contact your system administrator.";
    error_box($string,$color);
    exit;
}

do_hook('login_cookie');

/* Output the javascript onload function. */

$header = "<script language=\"JavaScript\" type=\"text/javascript\">\n" .
          "<!--\n".
          "  function squirrelmail_loginpage_onload() {\n".
          "    document.forms[0].js_autodetect_results.value = '" . SMPREF_JS_ON . "';\n".
          "    var textElements = 0;\n".
          "    for (i = 0; i < document.forms[0].elements.length; i++) {\n".
          "      if (document.forms[0].elements[i].type == \"text\" || document.forms[0].elements[i].type == \"password\") {\n".
          "        textElements++;\n".
          "        if (textElements == " . (isset($loginname) ? 2 : 1) . ") {\n".
          "          document.forms[0].elements[i].focus();\n".
          "          break;\n".
          "        }\n".
          "      }\n".
          "    }\n".
          "  }\n".
          "// -->\n".
          "</script>\n";

if (@file_exists($theme[$theme_default]['PATH']))
   @include ($theme[$theme_default]['PATH']);

displayHtmlHeader( "$org_name - " . _("Login"), $header, FALSE );

echo "<body text=\"$color[8]\" bgcolor=\"$color[4]\" link=\"$color[7]\" vlink=\"$color[7]\" alink=\"$color[7]\" onLoad=\"squirrelmail_loginpage_onload()\">" .
     "\n" . '<form action="redirect.php" method="post">' . "\n";

$username_form_name = 'login_username';
$password_form_name = 'secretkey';
do_hook('login_top');

$loginname_value = (sqGetGlobalVar('loginname', $loginname) ? htmlspecialchars($loginname) : '');

/* If they don't have a logo, don't bother.. */
if (isset($org_logo) && $org_logo) {
    /* Display width and height like good little people */
    $width_and_height = '';
    if (isset($org_logo_width) && is_numeric($org_logo_width) &&
     $org_logo_width>0) {
        $width_and_height = " width=\"$org_logo_width\"";
    }
    if (isset($org_logo_height) && is_numeric($org_logo_height) &&
     $org_logo_height>0) {
        $width_and_height .= " height=\"$org_logo_height\"";
    }
}

if(sqgetGlobalVar('mailto', $mailto)) {
    $rcptaddress = '<input type="hidden" name="mailto" value="' . urlencode($mailto) . '" />' . "\n";
} else {
    $rcptaddress = '';
}
echo html_tag( 'table',
    html_tag( 'tr',
        html_tag( 'td',
            '<center>'.
            ( isset($org_logo) && $org_logo
              ? '<img src="' . $org_logo . '" alt="' .
                sprintf(_("%s Logo"), $org_name) .'"' . $width_and_height .
                ' /><br />' . "\n"
              : '' ).
            ( (isset($hide_sm_attributions) && $hide_sm_attributions) ? '' :
            '<small>' . sprintf (_("SquirrelMail version %s"), $version) . '<br />' ."\n".
            '  ' . _("By the SquirrelMail Development Team") . '<br /></small>' . "\n" ) .
            html_tag( 'table',
                html_tag( 'tr',
                    html_tag( 'td',
                        '<b>' . sprintf (_("%s Login"), $org_name) . "</b>\n",
                    'center', $color[0] )
                ) .
                html_tag( 'tr',
                    html_tag( 'td',  "\n" .
                        html_tag( 'table',
                            html_tag( 'tr',
                                html_tag( 'td',
                                    _("Name:") ,
                                'right', '', 'width="30%"' ) .
                                html_tag( 'td',
                                    '<input type="text" name="' . $username_form_name .'" value="' . $loginname_value .'" />' ,
                                'left', '', 'width="*"' )
                                ) . "\n" .
                            html_tag( 'tr',
                                html_tag( 'td',
                                    _("Password:") ,
                                'right', '', 'width="30%"' ) .
                                html_tag( 'td',
                                    '<input type="password" name="' . $password_form_name . '" />' . "\n" .
                                    '<input type="hidden" name="js_autodetect_results" value="SMPREF_JS_OFF" />' . "\n" .
                                    $rcptaddress .
                                    '<input type="hidden" name="just_logged_in" value="1" />' . "\n",
                                'left', '', 'width="*"' )
                            ) ,
                        'center', $color[4], 'border="0" width="100%"' ) ,
                    'left', $color[4] )
                ) . 
                html_tag( 'tr',
                    html_tag( 'td',
                        '<center><input type="submit" value="' . _("Login") . '" /></center>',
                    'left' )
                ),
            '', $color[4], 'border="0" width="350"' ) . '</center>',
        'center' )
    ) ,
'', $color[4], 'border="0" cellspacing="0" cellpadding="0" width="100%"' );

switch ($allow_frames) {
   case 4:    // if $use_frames unset, fall through to case 2
      if (isset($_COOKIE['set_use_frames'])) {
         $use_frames = $_COOKIE['set_use_frames'];
         break;
      }
   case 2:    // Do not use frames
      $use_frames = 0;
      break;
   case 3:    // if $use_frames unset, fall through to case 1
      if (isset($_COOKIE['set_use_frames'])) {
         $use_frames = $_COOKIE['set_use_frames'];
         break;
      }
   default:   // default is also to use frames
   case 1:    // use frames
      $use_frames = 1;
      break;
}

if ($allow_frames > 2) {
   echo "<center><table border=0><tr><td>\n";
   echo "<small><input type=radio name=set_use_frames value=1";
   if ($use_frames) echo " checked=true";
   echo '> ' . _("Frames") . "\n";
   echo "</small></td><td nowrap>\n";
   echo "<small><input type=radio name=set_use_frames value=0";
   if (!$use_frames) echo " checked=true";
   echo '> ' . _("No Frames") . "\n";
   echo "</td></tr></table></center>\n";
}

do_hook('login_form');
echo '</form>' . "\n";

do_hook('login_bottom');
echo "</body>\n".
     "</html>\n";
?>