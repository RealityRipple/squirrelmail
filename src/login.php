<?php

/**
 * login.php -- simple login screen
 *
 * This a simple login screen. Some housekeeping is done to clean
 * cookies and find language.
 *
 * @copyright &copy; 1999-2005 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 */

/**
 * Path for SquirrelMail required files.
 * @ignore
 */
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
require_once(SM_PATH . 'functions/forms.php');

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
if($imap_auth_mech == 'login') {
    /**
     * detect disabled login, only when imapServerAddress contains
     * server address and not mapping. See sqimap_get_user_server()
     */
    if (substr($imapServerAddress, 0, 4) != "map:") {
        $imap = sqimap_create_stream($imapServerAddress, $imapPort, $use_imap_tls);
        $logindisabled = sqimap_capability($imap,'LOGINDISABLED');
        sqimap_logout($imap);
        if ($logindisabled) {
            $string = _("The IMAP server is reporting that plain text logins are disabled.").'<br />'.
                _("Using CRAM-MD5 or DIGEST-MD5 authentication instead may work.").'<br />';
            if (!$use_imap_tls) {
                $string .= _("Also, the use of TLS may allow SquirrelMail to login.").'<br />';
            }
            $string .= _("Please contact your system administrator and report this error.");
            error_box($string,$color);
            exit;
        }
    }
}

do_hook('login_cookie');

$loginname_value = (sqGetGlobalVar('loginname', $loginname) ? htmlspecialchars($loginname) : '');

/* Output the javascript onload function. */

$header = "<script language=\"JavaScript\" type=\"text/javascript\">\n" .
          "<!--\n".
          "  function squirrelmail_loginpage_onload() {\n".
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

if (! isset($color) || ! is_array($color)) {
    // Add default color theme, if theme loading fails
    $color = array();
    $color[0]  = '#dcdcdc';  /* light gray    TitleBar               */
    $color[1]  = '#800000';  /* red                                  */
    $color[2]  = '#cc0000';  /* light red     Warning/Error Messages */
    $color[4]  = '#ffffff';  /* white         Normal Background      */
    $color[7]  = '#0000cc';  /* blue          Links                  */
    $color[8]  = '#000000';  /* black         Normal text            */
}

displayHtmlHeader( "$org_name - " . _("Login"), $header, FALSE );

echo "<body text=\"$color[8]\" bgcolor=\"$color[4]\" link=\"$color[7]\" vlink=\"$color[7]\" alink=\"$color[7]\" onLoad=\"squirrelmail_loginpage_onload()\">" .
     "\n" . '<form action="redirect.php" method="post" onSubmit="document.forms[0].js_autodetect_results.value=\'' . SMPREF_JS_ON .'\';">' . "\n";

$username_form_name = 'login_username';
$password_form_name = 'secretkey';
do_hook('login_top');

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
    $rcptaddress = addHidden('mailto', $mailto);
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
            '<small>' . _("SquirrelMail Webmail Application") . '<br />' ."\n".
            '  ' . _("By the SquirrelMail Project Team") . '<br /></small>' . "\n" ) .
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
                                    addInput($username_form_name, $loginname_value),
                                'left', '', 'width="*"' )
                                ) . "\n" .
                            html_tag( 'tr',
                                html_tag( 'td',
                                    _("Password:") ,
                                'right', '', 'width="30%"' ) .
                                html_tag( 'td',
                                    addPwField($password_form_name).
                                    addHidden('js_autodetect_results', SMPREF_JS_OFF).
                                    $rcptaddress .
                                    addHidden('just_logged_in', '1'),
                                    'left', '', 'width="*"' )
                                ) .
                                concat_hook_function('login_form') ,
                            'center', $color[4], 'border="0" width="100%"' ) ,
                        'left', $color[4] )
                        ) .
                        html_tag( 'tr',
                                html_tag( 'td',
                                    '<center>'. addSubmit(_("Login")) .'</center>',
                                    'left' )
                                ),
                        '', $color[4], 'border="0" width="350"' ) . '</center>',
                        'center' )
                        ) ,
                        '', $color[4], 'border="0" cellspacing="0" cellpadding="0" width="100%"' );
echo '</form>' . "\n";

do_hook('login_bottom');

?>
</body></html>