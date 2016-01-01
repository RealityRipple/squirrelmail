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
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage mail_fetch
 */


/** pop3 class */
include_once (SM_PATH . 'plugins/mail_fetch/constants.php');
include_once (SM_PATH . 'plugins/mail_fetch/class.mail_fetch.php');

/** declare plugin globals */
global $mail_fetch_allow_unsubscribed, $mail_fetch_allowable_ports,
       $mail_fetch_block_server_pattern;

/**
  * Add link to menu at top of content pane
  *
  * @return void
  *
  */
function mail_fetch_link_do() {

    global $oTemplate, $nbsp;
    $output = makeInternalLink('plugins/mail_fetch/fetch.php', _("Fetch"), '')
            . $nbsp . $nbsp;
    return array('menuline' => $output);

}

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
 * Internal function used to fetch pop3 mails on login
 * @since 1.5.1
 * @private
 */
function mail_fetch_login_function() {
    include_once (SM_PATH . 'functions/imap_general.php');

    global $username, $data_dir, $imapServerAddress, $imapPort, $imap_stream_options;

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
        if( $mailfetch_cypher == 'on' ) {
            $mailfetch_pass_[$i_loop] = decrypt( $mailfetch_pass_[$i_loop] );
        }

        if( $mailfetch_pass_[$i_loop] <> '' &&          // Empty passwords no allowed
                ( ( $mailfetch_login_[$i_loop] == 'on' &&  $mailfetch_newlog == 'on' ) || $mailfetch_fref_[$i_loop] == 'on' ) ) {

            // What the heck
            $mailfetch_server_[$i_loop] = getPref($data_dir, $username, "mailfetch_server_$i_loop");
            $mailfetch_port_[$i_loop] = getPref($data_dir, $username , "mailfetch_port_$i_loop");
            $mailfetch_alias_[$i_loop] = getPref($data_dir, $username, "mailfetch_alias_$i_loop");
            $mailfetch_user_[$i_loop] = getPref($data_dir, $username, "mailfetch_user_$i_loop");
            $mailfetch_lmos_[$i_loop] = getPref($data_dir, $username, "mailfetch_lmos_$i_loop");
            $mailfetch_uidl_[$i_loop] = getPref($data_dir, $username, "mailfetch_uidl_$i_loop");
            $mailfetch_subfolder_[$i_loop] = getPref($data_dir, $username, "mailfetch_subfolder_$i_loop");
            $mailfetch_auth_[$i_loop] = getPref($data_dir, $username, "mailfetch_auth_$i_loop",MAIL_FETCH_AUTH_USER);
            $mailfetch_type_[$i_loop] = getPref($data_dir, $username, "mailfetch_type_$i_loop",MAIL_FETCH_USE_PLAIN);

            $mailfetch_server=$mailfetch_server_[$i_loop];
            $mailfetch_port=$mailfetch_port_[$i_loop];
            $mailfetch_user=$mailfetch_user_[$i_loop];
            $mailfetch_alias=$mailfetch_alias_[$i_loop];
            $mailfetch_pass=$mailfetch_pass_[$i_loop];
            $mailfetch_lmos=$mailfetch_lmos_[$i_loop];
            $mailfetch_login=$mailfetch_login_[$i_loop];
            $mailfetch_uidl=$mailfetch_uidl_[$i_loop];
            $mailfetch_subfolder=$mailfetch_subfolder_[$i_loop];
            $mailfetch_auth=$mailfetch_auth_[$i_loop];
            $mailfetch_type=$mailfetch_type_[$i_loop];
            // end of what the heck


            // $outMsg .= "$mailfetch_alias checked<br />";

            // $outMsg .= "$mailfetch_alias_[$i_loop]<br />";

            // FIXME: duplicate code with different output destination.

            $pop3 = new mail_fetch(array('host'    => $mailfetch_server,
                                         'port'    => $mailfetch_port,
                                         'auth'    => $mailfetch_auth,
                                         'tls'     => $mailfetch_type,
                                         'timeout' => 60));

            if (!empty($pop3->error)) {
                $outMsg .= _("Warning:") . ' ' . $pop3->error;
                continue;
            }

            $imap_stream = sqimap_login($username, false, $imapServerAddress, $imapPort, 10, $imap_stream_options);

            /* log into pop server*/
            if (! $pop3->login($mailfetch_user, $mailfetch_pass)) {
                $outMsg .= _("Login Failed:") . ' ' . $pop3->error;
                continue;
            }

            $aMsgStat = $pop3->command_stat();
            if (is_bool($aMsgStat)) {
                $outMsg .= _("Can't get mailbox status:") . ' ' . sm_encode_html_special_chars($pop3->error);
                continue;
            }

            $Count = $aMsgStat['count'];

            $i = 1;

            if ($Count>0) {
                // If we leave messages on server, try using UIDL
                if ($mailfetch_lmos == 'on') {
                    $msglist = $pop3->command_uidl();
                    if (is_bool($msglist)) {
                        $outMsg .= _("Server does not support UIDL.") . ' '.sm_encode_html_special_chars($pop3->error);
                        // User asked to leave messages on server, but we can't do that.
                        $pop3->command_quit();
                        continue;
                        // $mailfetch_lmos = 'off';
                    } else {
                        // calculate number of new messages
                        for ($j = 1; $j <= sizeof($msglist); $j++) {
                            // do strict comparison ('1111.10' should not be equal to '1111.100')
                            if ($msglist[$j] === $mailfetch_uidl) {
                                $i = $j+1;
                                break;
                            }
                        }
                    }
                }
                // fetch list of messages with LIST
                // we can use else control, but we can also set $mailfetch_lmos 
                // to off if server does not support UIDL.
                if ($mailfetch_lmos != 'on') {
                    $msglist = $pop3->command_list();
                }
            }

            if ($Count < $i) {
                $pop3->command_quit();
                continue;
            }
            if ($Count == 0) {
                $pop3->command_quit();
                continue;
            }

            for (; $i <= $Count; $i++) {
                if (!ini_get('safe_mode'))
                    set_time_limit(20); // 20 seconds per message max
                $Message = $pop3->command_retr($i);

                if (is_bool($Message)) {
                    $outMsg .= _("Warning:") . ' ' . sm_encode_html_special_chars($pop3->error);
                    continue;
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

                    // Check results of append command
                    $response=(implode('',$response));
                    $message=(implode('',$message));
                    if ($response != 'OK') {
                        $outMsg .= _("Error Appending Message!")." ".sm_encode_html_special_chars($message);

                        if ($mailfetch_lmos == 'on') {
                            setPref($data_dir,$username,"mailfetch_uidl_$i_loop", $msglist[$i-1]);
                        }
                        // Destroy msg list in order to prevent UIDL update
                        $msglist = false;
                        // if append fails, don't download other messages
                        break;
                    }

                    if ($mailfetch_lmos != 'on') {
                        $pop3->command_dele($i);
                    }
                } else {
                    echo "$Line";
                    $outMsg .= _("Error Appending Message!");
                }
            }

            $pop3->command_quit();
            sqimap_logout($imap_stream);
            if ($mailfetch_lmos == 'on' && is_array($msglist)) {
                setPref($data_dir,$username,"mailfetch_uidl_$i_loop", array_pop($msglist));
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
    global $data_dir, $username;

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
 * hex2bin - convert a hexadecimal string into binary
 * Exists since PHP 5.4.
 */
if ( ! function_exists('hex2bin') ) {
    function hex2bin( $data ) {

        /* Original code by josh@superfork.com */

        $len = strlen($data);
        $newdata = '';
        for( $i=0; $i < $len; $i += 2 ) {
            $newdata .= pack( "C", hexdec( substr( $data, $i, 2) ) );
        }
        return $newdata;
    }
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

/**
  * Validate a requested POP3 port number
  *
  * Allowable port numbers are configured in config.php
  * (see config_example.php for an example and more
  * rules about how the list of allowable port numbers
  * can be specified)
  *
  * @param int $requested_port The port number given by the user
  *
  * @return string An error string is returned if the port
  *                number is not allowable, otherwise an
  *                empty string is returned.
  *
  */
function validate_mail_fetch_port_number($requested_port) {
    global $mail_fetch_allowable_ports;
    if (empty($mail_fetch_allowable_ports))
        $mail_fetch_allowable_ports = array(110, 995);

    if (in_array('ALL', $mail_fetch_allowable_ports))
        return '';

    if (!in_array($requested_port, $mail_fetch_allowable_ports)) {
        sq_change_text_domain('mail_fetch');
        $error = _("Sorry, that port number is not allowed");
        sq_change_text_domain('squirrelmail');
        return $error;
    }

    return '';
}

/**
  * Validate a requested POP3 server address
  *
  * Blocked server addresses are configured in config.php
  * (see config_example.php for more details)
  *
  * @param int $requested_address The server address given by the user
  *
  * @return string An error string is returned if the server
  *                address is not allowable, otherwise an
  *                empty string is returned.
  *
  */
function validate_mail_fetch_server_address($requested_address) {
    global $mail_fetch_block_server_pattern;
    if (empty($mail_fetch_block_server_pattern))
        $mail_fetch_block_server_pattern = '/(^10\.)|(^192\.)|(^127\.)|(^localhost)/';

    if ($mail_fetch_block_server_pattern == 'UNRESTRICTED')
        return '';

    if (preg_match($mail_fetch_block_server_pattern, $requested_address)) {
        sq_change_text_domain('mail_fetch');
        $error = _("Sorry, that server address is not allowed");
        sq_change_text_domain('squirrelmail');
        return $error;
    }

    return '';
}

