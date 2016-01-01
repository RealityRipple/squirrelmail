<?php

/**
 * mail_fetch/fetch.php
 *
 * Fetch code.
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage mail_fetch
 */

/**
 * Include the SquirrelMail initialization file.
 */
require('../../include/init.php');

include_once(SM_PATH . 'functions/imap_general.php');
include_once(SM_PATH . 'plugins/mail_fetch/functions.php' );

// don't load this page if this plugin is not enabled
//
global $plugins;
if (!in_array('mail_fetch', $plugins)) exit;

/* globals */
sqgetGlobalVar('delimiter',  $delimiter,  SQ_SESSION);
global $imap_stream_options; // in case not defined in config
/* end globals */

/**
 * @param string $msg message
 */
function Mail_Fetch_Status($msg) {
    echo html_tag( 'table',
             html_tag( 'tr',
                 html_tag( 'td', sm_encode_html_special_chars( $msg ) , 'left' )
                 ),
             '', '', 'width="90%"' );
    flush();
}

/**
 * @return array
 */
function Mail_Fetch_Servers() {
    global $data_dir, $username;

    $mailfetch = array();
    $mailfetch['server_number'] = getPref($data_dir, $username, "mailfetch_server_number");
    if (!isset($mailfetch['server_number']) || ($mailfetch['server_number'] < 1)) {
        $mailfetch['server_number'] = 0;
    }
    $mailfetch['cypher'] = getPref($data_dir, $username, "mailfetch_cypher");

    for ($i = 0; $i < $mailfetch['server_number']; $i++) {
        $mailfetch[$i]['server'] = getPref($data_dir, $username, "mailfetch_server_$i");
        $mailfetch[$i]['port']   = getPref($data_dir, $username, "mailfetch_port_$i");
        $mailfetch[$i]['alias']  = getPref($data_dir, $username, "mailfetch_alias_$i");
        $mailfetch[$i]['user']   = getPref($data_dir, $username, "mailfetch_user_$i");
        $mailfetch[$i]['pass']   = getPref($data_dir, $username, "mailfetch_pass_$i");
        if($mailfetch['cypher'] == 'on') {
            $mailfetch[$i]['pass'] = decrypt($mailfetch[$i]['pass']);
        }
        if ($mailfetch[$i]['pass'] == '') {
            sqgetGlobalVar("pass_$i", $mailfetch[$i]['pass'], SQ_POST);
        }
        $mailfetch[$i]['lmos']   = getPref($data_dir, $username, "mailfetch_lmos_$i");
        $mailfetch[$i]['login']  = getPref($data_dir, $username, "mailfetch_login_$i");
        $mailfetch[$i]['uidl']   = getPref($data_dir, $username, "mailfetch_uidl_$i");
        $mailfetch[$i]['subfolder'] = getPref($data_dir, $username, "mailfetch_subfolder_$i");
        if($mailfetch[$i]['alias'] == '') {
            $mailfetch[$i]['alias'] == $mailfetch[$i]['server'];
        }
        // Authentication type (added in 1.5.2)
        $mailfetch[$i]['auth'] = getPref($data_dir, $username, "mailfetch_auth_$i",MAIL_FETCH_AUTH_USER);
        // Connection type (added in 1.5.2)
        $mailfetch[$i]['type'] = getPref($data_dir, $username, "mailfetch_type_$i",MAIL_FETCH_USE_PLAIN);
    }
    return $mailfetch;
}

/**
 * @param array $mailfetch
 */
function Mail_Fetch_Select_Server($mailfetch) {
    global $PHP_SELF;

    echo '<font size="-5"><br /></font>' .
        '<form action="'.$PHP_SELF.'" method="post" target="_self">' .
        html_tag( 'table', '', 'center', '', 'width="70%" cols="2"' ) .
        html_tag( 'tr' ) .
        html_tag( 'td', _("Select Server:") . ' &nbsp; &nbsp;', 'right' ) .
        html_tag( 'td', '', 'left' ) .
        '<select name="server_to_fetch" size="1">' .
        '<option value="all" selected="selected">..' . _("All") . "...\n";
    for ($i = 0;$i < $mailfetch['server_number'];$i++) {
        echo "<option value=\"$i\">" .
            sm_encode_html_special_chars($mailfetch[$i]['alias']) .
            '</option>' . "\n";
    }
    echo            '</select>' .
        '</td>' .
        '</tr>';

    //if password not set, ask for it
    for ($i = 0;$i < $mailfetch['server_number'];$i++) {
        if ($mailfetch[$i]['pass'] == '') {
            echo html_tag( 'tr',
                     html_tag( 'td', _("Password for") . ' <b>' .
                         sm_encode_html_special_chars($mailfetch[$i]['alias']) .
                         '</b>: &nbsp; &nbsp; ',
                         'right' ) .
                     html_tag( 'td', '<input type="password" name="pass_' . $i . '" />', 'left' )
                           );
        }
    }
    echo html_tag( 'tr',
             html_tag( 'td', '&nbsp;' ) .
             html_tag( 'td', '<input type="submit" name="submit_mailfetch" value="' . _("Fetch Mail"). '" />', 'left' )
                   ) .
        '</table></form>';
}

$mailfetch = Mail_Fetch_Servers();
displayPageHeader($color);

echo '<br />';

echo html_tag( 'table',
         html_tag( 'tr',
             html_tag( 'td', '<b>' . _("Remote POP server Fetching Mail") . '</b>', 'center', $color[0] )
                   ) ,
               'center', '', 'width="95%" cols="1"' );


/* there are no servers defined yet... */
if($mailfetch['server_number'] == 0) {
//FIXME: do not echo directly to browser -- use templates only
    echo '<p>' . _("No POP3 servers configured yet.") . '</p>';
    echo makeInternalLink('plugins/mail_fetch/options.php',
                        _("Click here to go to the options page.") );
    $oTemplate->display('footer.tpl');
    exit();
}

// get $server_to_fetch from globals, if not set display a choice to the user
if (! sqgetGlobalVar('server_to_fetch', $server_to_fetch, SQ_POST) ) {
    Mail_Fetch_Select_Server($mailfetch);
    $oTemplate->display('footer.tpl');
    exit();
}

if ( $server_to_fetch == 'all' ) {
    $i_start = 0;
    $i_stop  = $mailfetch['server_number'];
} else {
    $i_start = $server_to_fetch;
    $i_stop  = $i_start+1;
}

for ($i_loop=$i_start;$i_loop<$i_stop;$i_loop++) {
    $mailfetch_server = $mailfetch[$i_loop]['server'];
    $mailfetch_port   = $mailfetch[$i_loop]['port'];
    $mailfetch_user   = $mailfetch[$i_loop]['user'];
    $mailfetch_pass   = $mailfetch[$i_loop]['pass'];
    $mailfetch_lmos   = $mailfetch[$i_loop]['lmos'];
    $mailfetch_login  = $mailfetch[$i_loop]['login'];
    $mailfetch_uidl   = $mailfetch[$i_loop]['uidl'];
    $mailfetch_subfolder = $mailfetch[$i_loop]['subfolder'];
    $mailfetch_auth = $mailfetch[$i_loop]['auth'];
    $mailfetch_type = $mailfetch[$i_loop]['type'];

    echo '<br />' .
        html_tag( 'table',
            html_tag( 'tr',
                html_tag( 'td', '<b>' .
                    sprintf(_("Fetching from %s"),
                        sm_encode_html_special_chars($mailfetch[$i_loop]['alias'])) .
                    '</b>',
                'center' ) ,
            '', $color[9] ) ,
        '', '', 'width="90%"' );

    flush();

    $pop3 = new mail_fetch(array('host'    => $mailfetch_server,
                                 'port'    => $mailfetch_port,
                                 'auth'    => $mailfetch_auth,
                                 'tls'     => $mailfetch_type,
                                 'timeout' => 60));

    if (!empty($pop3->error)) {
        Mail_Fetch_Status($pop3->error);
        continue;
    }

    Mail_Fetch_Status(_("Opening IMAP server"));
    $imap_stream = sqimap_login($username, false, $imapServerAddress, $imapPort, 10, $imap_stream_options);

    // check if destination folder is not set, is not subscribed and is not \noselect folder
    if($mailfetch_subfolder == '' ||
       ! mail_fetch_check_folder($imap_stream,$mailfetch_subfolder)) {
        $mailfetch_subfolder = 'INBOX';
    }

    Mail_Fetch_Status(_("Opening POP server"));

    /* log into pop server*/
    if (! $pop3->login($mailfetch_user, $mailfetch_pass)) {
        Mail_Fetch_Status(_("Login Failed:") . ' ' . sm_encode_html_special_chars($pop3->error));
        continue;
    }

    $aMsgStat = $pop3->command_stat();
    if (is_bool($aMsgStat)) {
        Mail_Fetch_Status(_("Can't get mailbox status:") . ' ' . sm_encode_html_special_chars($pop3->error) );
        continue;
    }

    $Count = $aMsgStat['count'];

    $i = 1;

    if ($Count>0) {
        // If we leave messages on server, try using UIDL
        if ($mailfetch_lmos == 'on') {
            Mail_Fetch_Status(_("Fetching UIDL..."));
            $msglist = $pop3->command_uidl();
            if (is_bool($msglist)) {
                Mail_Fetch_Status(_("Server does not support UIDL.") . ' '.sm_encode_html_special_chars($pop3->error));
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
            Mail_Fetch_Status(_("Fetching list of messages..."));
            $msglist = $pop3->command_list();
        }
    }

    if ($Count < $i) {
        Mail_Fetch_Status(_("Login OK: No new messages"));
        $pop3->command_quit();
        continue;
    }
    if ($Count == 0) {
        Mail_Fetch_Status(_("Login OK: Inbox EMPTY"));
        $pop3->command_quit();
        continue;
    } else {
        $newmsgcount = $Count - $i + 1;
        Mail_Fetch_Status(sprintf(ngettext("Login OK: Inbox contains %s message",
                                           "Login OK: Inbox contains %s messages",$newmsgcount), $newmsgcount));
    }

    if ($mailfetch_lmos == 'on') {
        Mail_Fetch_Status(_("Leaving messages on server..."));
    } else {
        Mail_Fetch_Status(_("Deleting messages from server..."));
    }

    for (; $i <= $Count; $i++) {
        Mail_Fetch_Status(sprintf(_("Fetching message %s."), $i));

        if (!ini_get('safe_mode'))
            set_time_limit(20); // 20 seconds per message max

        $Message = $pop3->command_retr($i);

        if (is_bool($Message)) {
            Mail_Fetch_Status(sm_encode_html_special_chars($pop3->error));
            continue;
        }

        fputs($imap_stream, "A3$i APPEND \"$mailfetch_subfolder\" {" . strlen($Message) . "}\r\n");
        $Line = fgets($imap_stream, 1024);
        if (substr($Line, 0, 1) == '+') {
            fputs($imap_stream, $Message);
            fputs($imap_stream, "\r\n");
            sqimap_read_data($imap_stream, "A3$i", false, $response, $message);
            $response=(implode('',$response));
            $message=(implode('',$message));
            if ($response != 'OK') {
                Mail_Fetch_Status(_("Error Appending Message!")." ".sm_encode_html_special_chars($message) );
                Mail_Fetch_Status(_("Closing POP"));
                $pop3->command_quit();
                Mail_Fetch_Status(_("Logging out from IMAP"));
                sqimap_logout($imap_stream);

                if ($mailfetch_lmos == 'on') {
                    Mail_Fetch_Status(_("Saving UIDL"));
                    setPref($data_dir,$username,"mailfetch_uidl_$i_loop", $msglist[$i-1]);
                }
                exit;
            } else {
                Mail_Fetch_Status(_("Message appended to mailbox"));
            }

            if ($mailfetch_lmos != 'on') {
                if( $pop3->command_dele($i) ) {
                    Mail_Fetch_Status(sprintf(_("Message %d deleted from remote server!"), $i));
                } else {
                    Mail_Fetch_Status(_("Delete failed:") . sm_encode_html_special_chars($pop3->error) );
                }
            }
        } else {
            echo $Line;
            Mail_Fetch_Status(_("Error Appending Message!"));
            Mail_Fetch_Status(_("Closing POP"));
            $pop3->command_quit();
            Mail_Fetch_Status(_("Logging out from IMAP"));
            sqimap_logout($imap_stream);

            // not gurantee corect!
            if ($mailfetch_lmos == 'on') {
                Mail_Fetch_Status(_("Saving UIDL"));
                setPref($data_dir,$username,"mailfetch_uidl_$i_loop", $msglist[$i-1]);
            }
            exit;
        }
    }

    Mail_Fetch_Status(_("Closing POP"));
    $pop3->command_quit();
    Mail_Fetch_Status(_("Logging out from IMAP"));
    sqimap_logout($imap_stream);
    if ($mailfetch_lmos == 'on' && is_array($msglist)) {
        Mail_Fetch_Status(_("Saving UIDL"));
        setPref($data_dir,$username,"mailfetch_uidl_$i_loop", array_pop($msglist));
    }

    Mail_Fetch_Status(_("Done"));
}

$oTemplate->display('footer.tpl');
