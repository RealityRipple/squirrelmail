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
 */

/* Path for SquirrelMail required files. */
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

/*
 * $squirrelmail_language is set by a cookie when the user selects
 * language and logs out
 */
set_up_language($squirrelmail_language, TRUE);

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
$custom_css = 'none';          
displayHtmlHeader( "$org_name - " . _("Login"), $header, FALSE );

echo '<body text="#000000" bgcolor="#FFFFFF" link="#0000CC" vlink="#0000CC" alink="#0000CC" onload="squirrelmail_loginpage_onload();">' .
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

echo html_tag( 'table',
    html_tag( 'tr',
        html_tag( 'td',
            '<center>'.
            ( isset($org_logo) && $org_logo
              ? '<img src="' . $org_logo . '" alt="' .
                sprintf(_("%s Logo"), $org_name) .'"' . $width_and_height .
                ' /><br />' . "\n"
              : '' ).
            ( $hide_sm_attributions ? '' :
            '<small>' . sprintf (_("SquirrelMail version %s"), $version) . '<br />' ."\n".
            '  ' . _("By the SquirrelMail Development Team") . '<br /></small>' . "\n" ) .
            html_tag( 'table',
                html_tag( 'tr',
                    html_tag( 'td',
                        '<b>' . sprintf (_("%s Login"), $org_name) . "</b>\n",
                    'center', '#DCDCDC' )
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
                                    '<input type="hidden" name="just_logged_in" value="1" />' . "\n",
                                'left', '', 'width="*"' )
                            ) ,
                        'center', '#ffffff', 'border="0" width="100%"' ) ,
                    'left', '#FFFFFF' )
                ) . 
                html_tag( 'tr',
                    html_tag( 'td',
                        '<center><input type="submit" value="' . _("Login") . '" /></center>',
                    'left' )
                ),
            '', '#ffffff', 'border="0" width="350"' ) . '</center>',
        'center' )
    ) ,
'', '#ffffff', 'border="0" cellspacing="0" cellpadding="0" width="100%"' );
do_hook('login_form');
echo '</form>' . "\n";

do_hook('login_bottom');
echo "</body>\n".
     "</html>\n";
?>
