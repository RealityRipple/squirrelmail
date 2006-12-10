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
$sInitLocation = 'redirect';

/**
 * Include the SquirrelMail initialization file.
 */
require('../include/init.php');

/* SquirrelMail required files. */
require_once(SM_PATH . 'functions/imap_general.php');
require_once(SM_PATH . 'functions/strings.php');

header('Pragma: no-cache');
$location = get_location();

// session_set_cookie_params (0, $base_uri);

sqsession_unregister ('user_is_logged_in');
sqsession_register ($base_uri, 'base_uri');

/* get globals we me need */
sqGetGlobalVar('login_username', $login_username);
sqGetGlobalVar('secretkey', $secretkey);
if(!sqGetGlobalVar('squirrelmail_language', $squirrelmail_language) || $squirrelmail_language == '') {
    $squirrelmail_language = $squirrelmail_default_language;
}
if (!sqgetGlobalVar('mailtodata', $mailtodata)) {
    $mailtodata = '';
}

/* end of get globals */

set_up_language($squirrelmail_language, true);
/* Refresh the language cookie. */
sqsetcookie('squirrelmail_language', $squirrelmail_language, time()+2592000,
          $base_uri);

if (!isset($login_username)) {
    logout_error( _("You must be logged in to access this page.") );
    exit;
}

if (!sqsession_is_registered('user_is_logged_in')) {
    do_hook('login_before', $null);

    $onetimepad = OneTimePadCreate(strlen($secretkey));
    $key = OneTimePadEncrypt($secretkey, $onetimepad);

    /* remove redundant spaces */
    $login_username = trim($login_username);

    /* Verify that username and password are correct. */
    if ($force_username_lowercase) {
        $login_username = strtolower($login_username);
    }

    $imapConnection = sqimap_login($login_username, $key, $imapServerAddress, $imapPort, 0);
    /* From now on we are logged it. If the login failed then sqimap_login handles it */

    /* regenerate the session id to avoid session hyijacking */
    sqsession_destroy();
    @sqsession_is_active();
    session_regenerate_id();
    /**
     * The cookie part. session_start and session_regenerate_session normally set
     * their own cookie. SquirrelMail sets another cookie which overwites the
     * php cookies. The sqsetcookie function sets the cookie by using the header
     * function which gives us full control how the cookie is set. We do that
     * to add the HttpOnly cookie attribute which blocks javascript access on
     * IE6 SP1.
     * sqsetcookieflush is needed to send out the headers. sqsetcookie caches
     * the cookies to be send. If we don't do that we only can send 1 single cookie
     * which is not sufficient.
     */
    sqsetcookie(session_name(),session_id(),false,$base_uri);
    sqsetcookie('key', $key, false, $base_uri);
    sqsetcookieflush();

    sqsession_register($onetimepad, 'onetimepad');

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

    if (isset($sqimap_capabilities['NAMESPACE']) && $sqimap_capabilities['NAMESPACE'] == true) {
        $namespace = sqimap_get_namespace($imapConnection);
        sqsession_register($namespace, 'sqimap_namespace');
    }

    sqimap_logout($imapConnection);
    sqsession_register($delimiter, 'delimiter');

    $username = $login_username;
    sqsession_register ($username, 'username');
    do_hook('login_verified', $null);
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
    if ( strpos($session_expired_location, 'compose.php') !== FALSE ) {
        $compose_new_win = getPref($data_dir, $username, 'compose_new_win', 0);
        if ($compose_new_win) {
            // do not prefix $location here because $session_expired_location is set to PHP_SELF
            // of the last page
            $redirect_url = $session_expired_location;
        } else {
            $redirect_url = $location.'/webmail.php?right_frame='.urldecode($session_expired_location);
        }
    }
    unset($session_expired_location);
}

if($mailtodata != '') {
    $redirect_url  = $location . '/webmail.php?right_frame=compose.php&mailtodata=';
    $redirect_url .= urlencode($mailtodata);
}

/* Write session data and send them off to the appropriate page. */
session_write_close();
header("Location: $redirect_url");
exit;

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
