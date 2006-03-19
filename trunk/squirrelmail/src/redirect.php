<?php

/**
 * Prevents users from reposting their form data after a successful logout.
 *
 * Derived from webmail.php by Ralf Kraudelt <kraude@wiwi.uni-rostock.de>
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
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
require_once(SM_PATH . 'functions/global.php');
require_once(SM_PATH . 'functions/i18n.php');
require_once(SM_PATH . 'functions/strings.php');
require_once(SM_PATH . 'config/config.php');
require_once(SM_PATH . 'functions/prefs.php');
require_once(SM_PATH . 'functions/imap.php');
require_once(SM_PATH . 'functions/plugin.php');
require_once(SM_PATH . 'functions/constants.php');
require_once(SM_PATH . 'functions/page_header.php');

/* Before starting the session, the base URI must be known. Assuming */
/* that this file is in the src/ subdirectory (or something).        */
$base_uri = sqm_baseuri();

header('Pragma: no-cache');
$location = get_location();

session_set_cookie_params (0, $base_uri);
sqsession_is_active();

sqsession_unregister ('user_is_logged_in');
sqsession_register ($base_uri, 'base_uri');

/* get globals we me need */
sqGetGlobalVar('login_username', $login_username);
sqGetGlobalVar('secretkey', $secretkey);
if(!sqGetGlobalVar('squirrelmail_language', $squirrelmail_language) || $squirrelmail_language == '') {
    $squirrelmail_language = $squirrelmail_default_language;
}
if (!sqgetGlobalVar('mailto', $mailto)) {
    $mailto = '';
}

/* end of get globals */

set_up_language($squirrelmail_language, true);
/* Refresh the language cookie. */
sqsetcookie('squirrelmail_language', $squirrelmail_language, time()+2592000,
          $base_uri);

if (!isset($login_username)) {
    include_once(SM_PATH .  'functions/display_messages.php' );
    logout_error( _("You must be logged in to access this page.") );
    exit;
}

if (!sqsession_is_registered('user_is_logged_in')) {
    do_hook ('login_before');

    $onetimepad = OneTimePadCreate(strlen($secretkey));
    $key = OneTimePadEncrypt($secretkey, $onetimepad);
    sqsession_register($onetimepad, 'onetimepad');

    /* remove redundant spaces */
    $login_username = trim($login_username);

    /* Verify that username and password are correct. */
    if ($force_username_lowercase) {
        $login_username = strtolower($login_username);
    }

    $imapConnection = sqimap_login($login_username, $key, $imapServerAddress, $imapPort, 0);

    $sqimap_capabilities = sqimap_capability($imapConnection);

    /* Server side sorting control */
    if (isset($sqimap_capabilities['SORT']) && $sqimap_capabilities['SORT'] == true &&
        isset($disable_server_sort) && $disable_server_sort) {
        unset($sqimap_capabilities['SORT']);
    }

    /* Thread sort control */
    if (isset($sqimap_capabilities['THREAD']) && $sqimap_capabilities['THREAD'] == true &&
        isset($disable_thread_sort) && $disable_thread_sort) {
        unset($sqimap_capabilities['THREAD']);
    }

    sqsession_register($sqimap_capabilities, 'sqimap_capabilities');
    $delimiter = sqimap_get_delimiter ($imapConnection);

    sqimap_logout($imapConnection);
    sqsession_register($delimiter, 'delimiter');

    $username = $login_username;
    sqsession_register ($username, 'username');
    sqsetcookie('key', $key, false, $base_uri);
    do_hook ('login_verified');

}

/* Set the login variables. */
$user_is_logged_in = true;
$just_logged_in = true;

/* And register with them with the session. */
sqsession_register ($user_is_logged_in, 'user_is_logged_in');
sqsession_register ($just_logged_in, 'just_logged_in');

/* parse the accepted content-types of the client */
$attachment_common_types = array();
$attachment_common_types_parsed = array();
sqsession_register($attachment_common_types, 'attachment_common_types');
sqsession_register($attachment_common_types_parsed, 'attachment_common_types_parsed');

$debug = false;

if ( sqgetGlobalVar('HTTP_ACCEPT', $http_accept, SQ_SERVER) &&
    !isset($attachment_common_types_parsed[$http_accept]) ) {
    attachment_common_parse($http_accept, $debug);
}

/* Complete autodetection of Javascript. */
checkForJavascript();

/* Compute the URL to forward the user to. */
$redirect_url = $location . '/webmail.php';

if ( sqgetGlobalVar('session_expired_location', $session_expired_location, SQ_SESSION) ) {
    sqsession_unregister('session_expired_location');
    $compose_new_win = getPref($data_dir, $username, 'compose_new_win', 0);
    if ($compose_new_win) {
        // do not prefix $location here because $session_expired_location is set to PHP_SELF
        // of the last page
        $redirect_url = $session_expired_location;
    } elseif ( strpos($session_expired_location, 'webmail.php') === FALSE ) {
        $redirect_url = $location.'/webmail.php?right_frame='.urldecode($session_expired_location);
    }
    unset($session_expired_location);
}
if($mailto != '') {
    $redirect_url  = $location . '/webmail.php?right_frame=compose.php&mailto=';
    $redirect_url .= urlencode($mailto);
}

/* Write session data and send them off to the appropriate page. */
session_write_close();
header("Location: $redirect_url");

/* --------------------- end main ----------------------- */

function attachment_common_parse($str, $debug) {
    global $attachment_common_types, $attachment_common_types_parsed;

    $attachment_common_types_parsed[$str] = true;

    /*
     * Replace ", " with "," and explode on that as Mozilla 1.x seems to
     * use "," to seperate whilst IE, and earlier versions of Mozilla use
     * ", " to seperate
     */

    $str = str_replace( ', ' , ',' , $str );
    $types = explode(',', $str);

    foreach ($types as $val) {
        // Ignore the ";q=1.0" stuff
        if (strpos($val, ';') !== false)
            $val = substr($val, 0, strpos($val, ';'));

        if (! isset($attachment_common_types[$val])) {
            $attachment_common_types[$val] = true;
        }
    }
    sqsession_register($attachment_common_types, 'attachment_common_types');
}

?>