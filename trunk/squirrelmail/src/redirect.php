<?php

    /**
     **  redirect.php
     **  Derived from webmail.php by Ralf Kraudelt <kraude@wiwi.uni-rostock.de>
     **
     **  Copyright (c) 1999-2001 The Squirrelmail Development Team
     **  Licensed under the GNU GPL. For full terms see the file COPYING.
     **
     **  Prevents users from reposting their form data after a successful logout.
     **
     **  $Id$
     **/

    require_once('../functions/i18n.php');
    require_once('../functions/strings.php');
    require_once('../config/config.php');
    require_once('../functions/prefs.php');
    require_once('../functions/imap.php');
    require_once('../functions/plugin.php');
    require_once('../functions/constants.php');

    function attachment_common_parse($str, $debug) {
        global $attachment_common_types, $attachment_common_types_parsed;

        $attachment_common_types_parsed[$str] = true;
        $types = explode(', ', $str);

        foreach ($types as $val)
        {
            // Ignore the ";q=1.0" stuff
            if (strpos($val, ';') !== false)
                $val = substr($val, 0, strpos($val, ';'));

            if (! isset($attachment_common_types[$val])) {
                $attachment_common_types[$val] = true;
            }
        }
    }


    /* Before starting the session, the base URI must be known. Assuming */
    /* that this file is in the src/ subdirectory (or something).        */
    ereg ("(^.*/)[^/]+/[^/]+$", $PHP_SELF, $regs);
    $base_uri = $regs[1];

    header('Pragma: no-cache');
    $location = get_location();

    session_set_cookie_params (0, $base_uri);
    session_start();

    session_unregister ('user_is_logged_in');
    session_register ('base_uri');

    if (! isset($squirrelmail_language) ||
          $squirrelmail_language == '' ) {
        $squirrelmail_language = $squirrelmail_default_language;
    }
    set_up_language($squirrelmail_language, true);
    /* Refresh the language cookie. */
    setcookie('squirrelmail_language', $squirrelmail_language, time()+2592000,$base_uri);

    if (!isset($login_username)) {
        echo "<HTML><BODY BGCOLOR=\"#ffffff\">\n" .
             "<BR>&nbsp;<BR>\n" .
             "<CENTER>\n" .
             '<B>' . _("You must be logged in to access this page.") . "</B><BR>" .
             '<A HREF="../src/login.php">'  . _("Go to the login page") . "</A>\n" .
             "</CENTER>\n" .
             "</BODY></HTML>\n";
        exit;
    }

    if (!session_is_registered('user_is_logged_in')) {
        do_hook ('login_before');

        $onetimepad = OneTimePadCreate(strlen($secretkey));
        $key = OneTimePadEncrypt($secretkey, $onetimepad);
        session_register('onetimepad');

        /* Verify that username and password are correct. */
        if ($force_username_lowercase) {
            $login_username = strtolower($login_username);
        }
        
        $imapConnection = sqimap_login($login_username, $key, $imapServerAddress, $imapPort, 0);
        if (!$imapConnection) {
            echo "<html><body bgcolor=\"#ffffff\">\n".
                 "<br> <br>\n".
                 "<center>\n".
                 '<b>' . _("There was an error contacting the mail server.") . "</b><br>\n".
                 _("Contact your administrator for help.") . "\n".
                 "</center>\n".
                 "</body></html>\n";
            exit;
        } else {
            $delimiter = sqimap_get_delimiter ($imapConnection);
        }
        sqimap_logout($imapConnection);
        session_register('delimiter');

        $username = $login_username;
        session_register ('username');
        setcookie('key', $key, 0, $base_uri);
        do_hook ('login_verified');
        setPref( $data_dir, $username, 'counter',
            getPref( $data_dir, $username, 'counter', 0 ) + 1 );
    }

    /* Set the login variables. */
    $user_is_logged_in = true;
    $just_logged_in = true;

    /* And register with them with the session. */
    session_register ('user_is_logged_in');
    session_register ('just_logged_in');

    /* parse the accepted content-types of the client */
    $attachment_common_types = array();
    $attachment_common_types_parsed = array();
    session_register('attachment_common_types');
    session_register('attachment_common_types_parsed');
  
    $debug = false;
    if (isset($HTTP_SERVER_VARS['HTTP_ACCEPT']) &&
        !isset($attachment_common_types_parsed[$HTTP_SERVER_VARS['HTTP_ACCEPT']]))
        attachment_common_parse($HTTP_SERVER_VARS['HTTP_ACCEPT'], $debug);
    if (isset($HTTP_ACCEPT) &&
        !isset($attachment_common_types_parsed[$HTTP_ACCEPT]))
        attachment_common_parse($HTTP_ACCEPT, $debug);


    /* Complete autodetection of Javascript. */
    checkForPrefs($data_dir, $username);
    $javascript_setting = getPref($data_dir, $username, 'javascript_setting', SMPREF_JS_AUTODETECT);
    $js_autodetect_results = (isset($js_autodetect_results) ? $js_autodetect_results : SMPREF_JS_OFF);
    if ($javascript_setting == SMPREF_JS_AUTODETECT) {
        if ($js_autodetect_results == SMPREF_JS_ON) {
            setPref($data_dir, $username, 'javascript_on', SMPREF_JS_ON);
        } else {
            setPref($data_dir, $username, 'javascript_on', SMPREF_JS_OFF);
        }
    } else {
        setPref($data_dir, $username, 'javascript_on', SMPREF_JS_OFF);
    }

    /* Compute the URL to forward the user to. */
    if(isset($rcptemail)) {
        $redirect_url = 'webmail.php?right_frame=compose.php&rcptaddress=';
        $redirect_url .= urlencode($rcptemail);
    } else {
        $redirect_url = 'webmail.php';
    }

    /* Send them off to the appropriate page. */
    header("Location: $redirect_url");

?>
