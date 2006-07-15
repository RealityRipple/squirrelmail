<?php

/**
 * mail_fetch/functions.php
 *
 * Functions for the mail_fetch plugin.
 *
 * Original code from LexZEUS <lexzeus@mifinca.com>
 * and josh@superfork.com (extracted from php manual)
 * Adapted for MailFetch by Philippe Mingo <mingo@rotedic.com>
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage mail_fetch
 */


/** pop3 class */
include_once (SM_PATH . 'plugins/mail_fetch/class.POP3.php');

/** declare plugin globals */
global $mail_fetch_allow_unsubscribed;

/**
 * Controls use of unsubscribed folders in plugin
 * @global boolean $mail_fetch_allow_unsubscribed
 * @since 1.5.1 and 1.4.5
 */
$mail_fetch_allow_unsubscribed = false;

/** load site config */
if (file_exists(SM_PATH . 'config/mail_fetch_config.php')) {
    include_once(SM_PATH . 'config/mail_fetch_config.php');
} elseif (file_exists(SM_PATH . 'plugins/mail_fetch/config.php')) {
    include_once(SM_PATH . 'plugins/mail_fetch/config.php');
}

// hooked functions

/**
 * internal function used to load user's preferences
 * @since 1.5.1
 * @private
 */
function  mail_fetch_load_pref_function() {
    global $data_dir;
    global $mailfetch_server_number;
    global $mailfetch_cypher, $mailfetch_port_;
    global $mailfetch_server_,$mailfetch_alias_,$mailfetch_user_,$mailfetch_pass_;
    global $mailfetch_lmos_, $mailfetch_uidl_, $mailfetch_login_, $mailfetch_fref_;
    global $PHP_SELF;

    sqgetGlobalVar('username', $username, SQ_SESSION);

    if( stristr( $PHP_SELF, 'mail_fetch' ) ) {
        $mailfetch_server_number = getPref($data_dir, $username, 'mailfetch_server_number', 0);
        $mailfetch_cypher = getPref($data_dir, $username, 'mailfetch_cypher', 'on' );
        if ($mailfetch_server_number<1) $mailfetch_server_number=0;
        for ($i=0;$i<$mailfetch_server_number;$i++) {
            $mailfetch_server_[$i] = getPref($data_dir, $username, "mailfetch_server_$i");
            $mailfetch_port_[$i] = getPref($data_dir, $username, "mailfetch_port_$i");
            $mailfetch_alias_[$i]  = getPref($data_dir, $username, "mailfetch_alias_$i");
            $mailfetch_user_[$i]   = getPref($data_dir, $username, "mailfetch_user_$i");
            $mailfetch_pass_[$i]   = getPref($data_dir, $username, "mailfetch_pass_$i");
            $mailfetch_lmos_[$i]   = getPref($data_dir, $username, "mailfetch_lmos_$i");
            $mailfetch_login_[$i]  = getPref($data_dir, $username, "mailfetch_login_$i");
            $mailfetch_fref_[$i]   = getPref($data_dir, $username, "mailfetch_fref_$i");
            $mailfetch_uidl_[$i]   = getPref($data_dir, $username, "mailfetch_uidl_$i");
            if( $mailfetch_cypher   == 'on' ) $mailfetch_pass_[$i] =    decrypt( $mailfetch_pass_[$i] );
        }
    }
}

/**
 * Internal function used to fetch pop3 mails on login
 * @since 1.5.1
 * @private
 */
function mail_fetch_login_function() {
    //include_once (SM_PATH . 'include/validate.php');
    include_once (SM_PATH . 'functions/imap_general.php');

    global $data_dir, $imapServerAddress, $imapPort;

    sqgetGlobalVar('username', $username, SQ_SESSION);
    sqgetGlobalVar('key',      $key,      SQ_COOKIE);

    $mailfetch_newlog = getPref($data_dir, $username, 'mailfetch_newlog');

    $outMsg = '';

    $mailfetch_server_number = getPref($data_dir, $username, 'mailfetch_server_number');
    if (!isset($mailfetch_server_number)) $mailfetch_server_number=0;
    $mailfetch_cypher = getPref($data_dir, $username, 'mailfetch_cypher');
    if ($mailfetch_server_number<1) $mailfetch_server_number=0;

    for ($i_loop=0;$i_loop<$mailfetch_server_number;$i_loop++) {

        $mailfetch_login_[$i_loop] = getPref($data_dir, $username, "mailfetch_login_$i_loop");
        $mailfetch_fref_[$i_loop] = getPref($data_dir, $username, "mailfetch_fref_$i_loop");
        $mailfetch_pass_[$i_loop] = getPref($data_dir, $username, "mailfetch_pass_$i_loop");
        if( $mailfetch_cypher == 'on' )
            $mailfetch_pass_[$i_loop] = decrypt( $mailfetch_pass_[$i_loop] );

        if( $mailfetch_pass_[$i_loop] <> '' &&          // Empty passwords no allowed
                ( ( $mailfetch_login_[$i_loop] == 'on' &&  $mailfetch_newlog == 'on' ) || $mailfetch_fref_[$i_loop] == 'on' ) ) {

            $mailfetch_server_[$i_loop] = getPref($data_dir, $username, "mailfetch_server_$i_loop");
            $mailfetch_port_[$i_loop] = getPref($data_dir, $username , "mailfetch_port_$i_loop");
            $mailfetch_alias_[$i_loop] = getPref($data_dir, $username, "mailfetch_alias_$i_loop");
            $mailfetch_user_[$i_loop] = getPref($data_dir, $username, "mailfetch_user_$i_loop");
            $mailfetch_lmos_[$i_loop] = getPref($data_dir, $username, "mailfetch_lmos_$i_loop");
            $mailfetch_uidl_[$i_loop] = getPref($data_dir, $username, "mailfetch_uidl_$i_loop");
            $mailfetch_subfolder_[$i_loop] = getPref($data_dir, $username, "mailfetch_subfolder_$i_loop");

            $mailfetch_server=$mailfetch_server_[$i_loop];
            $mailfetch_port=$mailfetch_port_[$i_loop];
            $mailfetch_user=$mailfetch_user_[$i_loop];
            $mailfetch_alias=$mailfetch_alias_[$i_loop];
            $mailfetch_pass=$mailfetch_pass_[$i_loop];
            $mailfetch_lmos=$mailfetch_lmos_[$i_loop];
            $mailfetch_login=$mailfetch_login_[$i_loop];
            $mailfetch_uidl=$mailfetch_uidl_[$i_loop];
            $mailfetch_subfolder=$mailfetch_subfolder_[$i_loop];

            // $outMsg .= "$mailfetch_alias checked<br />";

            // $outMsg .= "$mailfetch_alias_[$i_loop]<br />";

            $pop3 = new POP3($mailfetch_server, 60);

            if (!$pop3->connect($mailfetch_server,$mailfetch_port)) {
                $outMsg .= _("Warning:") . ' ' . $pop3->ERROR;
                continue;
            }

            $imap_stream = sqimap_login($username, $key, $imapServerAddress, $imapPort, 10);

            $Count = $pop3->login($mailfetch_user, $mailfetch_pass);
            if (($Count == false || $Count == -1) && $pop3->ERROR != '') {
                $outMsg .= _("Login Failed:") . ' ' . $pop3->ERROR;
                continue;
            }

            //   register_shutdown_function($pop3->quit());

            $msglist = $pop3->uidl();

            $i = 1;
            for ($j = 1; $j < sizeof($msglist); $j++) {
                if ($msglist["$j"] == $mailfetch_uidl) {
                    $i = $j+1;
                    break;
                }
            }

            if ($Count < $i) {
                $pop3->quit();
                continue;
            }
            if ($Count == 0) {
                $pop3->quit();
                continue;
            }

            // Faster to get them all at once
            $mailfetch_uidl = $pop3->uidl();

            if (! is_array($mailfetch_uidl) && $mailfetch_lmos == 'on')
                $outMsg .= _("Server does not support UIDL.");

            for (; $i <= $Count; $i++) {
                if (!ini_get('safe_mode'))
                    set_time_limit(20); // 20 seconds per message max
                $Message = "";
                $MessArray = $pop3->get($i);

                if ( (!$MessArray) or (gettype($MessArray) != "array")) {
                    $outMsg .= _("Warning:") . ' ' . $pop3->ERROR;
                    continue 2;
                }

                while (list($lineNum, $line) = each ($MessArray)) {
                    $Message .= $line;
                }

                // check if mail folder is not null and subscribed (There is possible issue with /noselect mail folders)
                if ($mailfetch_subfolder=='' ||
                    ! mail_fetch_check_folder($imap_stream,$mailfetch_subfolder)) {
                    fputs($imap_stream, "A3$i APPEND INBOX {" . strlen($Message) . "}\r\n");
                } else {
                    fputs($imap_stream, "A3$i APPEND $mailfetch_subfolder {" . strlen($Message) . "}\r\n");
                }
                $Line = fgets($imap_stream, 1024);
                if (substr($Line, 0, 1) == '+') {
                    fputs($imap_stream, $Message);
                    fputs($imap_stream, "\r\n");
                    sqimap_read_data($imap_stream, "A3$i", false, $response, $message);

                    if ($mailfetch_lmos != 'on') {
                        $pop3->delete($i);
                    }
                } else {
                    echo "$Line";
                    $outMsg .= _("Error Appending Message!");
                }
            }

            $pop3->quit();
            sqimap_logout($imap_stream);
            if (is_array($mailfetch_uidl)) {
                setPref($data_dir,$username,"mailfetch_uidl_$i_loop", array_pop($mailfetch_uidl));
            }
        }
    }

    if( trim( $outMsg ) <> '' ) {
        echo '<br /><font size="1">' . _("Mail Fetch Result:") . "<br />$outMsg</font>";
    }
    if( $mailfetch_newlog == 'on' ) {
        setPref($data_dir, $username, 'mailfetch_newlog', 'off');
    }
}

/**
 * Internal function used to detect new logins
 */
function mail_fetch_setnew_function() {
    global $data_dir;

    sqgetGlobalVar('username', $username, SQ_SESSION);
    setPref( $data_dir, $username, 'mailfetch_newlog', 'on' );
}

/**
 * Internal function used to register option block
 * @since 1.5.1
 * @private
 */
function mailfetch_optpage_register_block_function() {
    global $optpage_blocks;

    $optpage_blocks[] = array(
            'name' => _("POP3 Fetch Mail"),
            'url'  => '../plugins/mail_fetch/options.php',
            'desc' => _("This configures settings for downloading email from a POP3 mailbox to your account on this server."),
            'js'   => false
            );
}

/**
 * Internal function used to update mail_fetch settings
 * when folders are renamed or deleted.
 * @since 1.5.1
 * @private
 */
function mail_fetch_folderact_function($args) {
    global $username, $data_dir;

    if (empty($args) || !is_array($args)) {
        return;
    }

    /* Should be 3 ars, 1: old folder, 2: action, 3: new folder */
    if (count($args) != 3) {
        return;
    }

    list($old_folder, $action, $new_folder) = $args;

    $mailfetch_server_number = getPref($data_dir, $username, 'mailfetch_server_number');

    for ($i = 0; $i < $mailfetch_server_number; $i++) {
        $mailfetch_subfolder = getPref($data_dir, $username, 'mailfetch_subfolder_' . $i);

        if ($mailfetch_subfolder != $old_folder) {
            continue;
        }

        if ($action == 'delete') {
            setPref($data_dir, $username, 'mailfetch_subfolder_' . $i, 'INBOX');
        } elseif ($action == 'rename') {
            setPref($data_dir, $username, 'mailfetch_subfolder_' . $i, $new_folder);
        }
    }
}
// end of hooked functions

/**
 * hex2bin - document me
 */
function hex2bin( $data ) {

    /* Original code by josh@superfork.com */

    $len = strlen($data);
    $newdata = '';
    for( $i=0; $i < $len; $i += 2 ) {
        $newdata .= pack( "C", hexdec( substr( $data, $i, 2) ) );
    }
    return $newdata;
}

function mf_keyED( $txt ) {

    global $MF_TIT;

    if( !isset( $MF_TIT ) ) {
        $MF_TIT = "MailFetch Secure for SquirrelMail 1.x";
    }

    $encrypt_key = md5( $MF_TIT );
    $ctr = 0;
    $tmp = "";
    for( $i = 0; $i < strlen( $txt ); $i++ ) {
        if( $ctr == strlen( $encrypt_key ) ) $ctr=0;
        $tmp.= substr( $txt, $i, 1 ) ^ substr( $encrypt_key, $ctr, 1 );
        $ctr++;
    }
    return $tmp;
}

function encrypt( $txt ) {

    srand( (double) microtime() * 1000000 );
    $encrypt_key = md5( rand( 0, 32000 ) );
    $ctr = 0;
    $tmp = "";
    for( $i = 0; $i < strlen( $txt ); $i++ ) {
        if ($ctr==strlen($encrypt_key)) $ctr=0;
        $tmp.= substr($encrypt_key,$ctr,1) .
            (substr($txt,$i,1) ^ substr($encrypt_key,$ctr,1));
        $ctr++;
    }
    return bin2hex( mf_keyED( $tmp ) );

}

function decrypt( $txt ) {

    $txt = mf_keyED( hex2bin( $txt ) );
    $tmp = '';
    for ( $i=0; $i < strlen( $txt ); $i++ ) {
        $md5 = substr( $txt, $i, 1 );
        $i++;
        $tmp.= ( substr( $txt, $i, 1 ) ^ $md5 );
    }
    return $tmp;
}

/**
 * check mail folder
 * @param stream $imap_stream imap connection resource
 * @param string $imap_folder imap folder name
 * @return boolean true, when folder can be used to store messages.
 * @since 1.5.1 and 1.4.5
 */
function mail_fetch_check_folder($imap_stream,$imap_folder) {
    global $mail_fetch_allow_unsubscribed;

    // check if folder is subscribed or only exists.
    if (sqimap_mailbox_is_subscribed($imap_stream,$imap_folder)) {
        $ret = true;
    } elseif ($mail_fetch_allow_unsubscribed && sqimap_mailbox_exists($imap_stream,$imap_folder)) {
        $ret = true;
    } else {
        $ret = false;
    }

    // make sure that folder can store messages
    if ($ret && mail_fetch_check_noselect($imap_stream,$imap_folder)) {
        $ret = false;
    }

    return $ret;
}

/**
 * Checks if folder is noselect (can't store messages)
 *
 * Function does not check if folder subscribed.
 * @param stream $imap_stream imap connection resource
 * @param string $imap_folder imap folder name
 * @return boolean true, when folder has noselect flag. false in any other case.
 * @since 1.5.1 and 1.4.5
 */
function mail_fetch_check_noselect($imap_stream,$imap_folder) {
    $boxes=sqimap_mailbox_list($imap_stream);
    foreach($boxes as $box) {
        if ($box['unformatted']==$imap_folder) {
            return (bool) check_is_noselect($box['raw']);
        }
    }
    return false;
}
