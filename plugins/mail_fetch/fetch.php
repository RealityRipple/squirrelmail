<?php

/**
 * mail_fetch/fetch.php
 *
 * Copyright (c) 1999-2003 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Fetch code.
 *
 * $Id$
 */

define('SM_PATH','../../');

require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'functions/page_header.php');
require_once(SM_PATH . 'functions/imap.php');
require_once(SM_PATH . 'include/load_prefs.php');
require_once(SM_PATH . 'plugins/mail_fetch/class.POP3.php');
require_once(SM_PATH . 'plugins/mail_fetch/functions.php' );
require_once(SM_PATH . 'functions/html.php' );

/* globals */ 
sqgetGlobalVar('username',   $username,   SQ_SESSION);
sqgetGlobalVar('key',        $key,        SQ_COOKIE);
sqgetGlobalVar('onetimepad', $onetimepad, SQ_SESSION);
sqgetGlobalVar('delimiter',  $delimiter,  SQ_SESSION);
/* end globals */

    function Mail_Fetch_Status($msg) {
        echo html_tag( 'table',
                   html_tag( 'tr',
                       html_tag( 'td', htmlspecialchars( $msg ) , 'left' )
                   ),
                 '', '', 'width="90%"' );
        flush();
    }

    function Mail_Fetch_Servers() {
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
        }
        return $mailfetch;
    }

    function Mail_Fetch_Select_Server($mailfetch) {
        echo '<font size=-5><br></font>' .
             "<form action=\"$PHP_SELF\" method=\"post\" target=\"_self\">" .
             html_tag( 'table', '', 'center', '', 'width="70%" cols="2"' ) .
                 html_tag( 'tr' ) .
                     html_tag( 'td', _("Select Server:") . ' &nbsp; &nbsp;', 'right' ) .
                     html_tag( 'td', '', 'left' ) .
                         '<select name="server_to_fetch" size="1">' .
                         '<option value="all" selected>..' . _("All") . "...\n";
        for ($i = 0;$i < $mailfetch['server_number'];$i++) {
             echo "<option value=\"$i\">" .
                 htmlspecialchars($mailfetch[$i]['alias']) .
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
                                  htmlspecialchars($mailfetch[$i]['alias']) .
                                  '</b>: &nbsp; &nbsp; ',
                              'right' ) .
                              html_tag( 'td', '<input type="password" name="pass_' . $i . '">', 'left' )
                          );
             }
        }
        echo html_tag( 'tr',
                   html_tag( 'td', '&nbsp;' ) .
                   html_tag( 'td', '<input type=submit name=submit_mailfetch value="' . _("Fetch Mail"). '">', 'left' )
               ) .
             '</table></form>';
    }

    $mailfetch = Mail_Fetch_Servers();
    displayPageHeader($color, 'None');

    echo '<br><center>';

    echo html_tag( 'table',
               html_tag( 'tr',
                   html_tag( 'td', '<b>' . _("Remote POP server Fetching Mail") . '</b>', 'center', $color[0] )
               ) ,
           'center', '', 'width="95%" cols="1"' );

    if (!isset( $server_to_fetch ) ) {
        Mail_Fetch_Select_Server($mailfetch);
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
        if($mailfetch_subfolder == '') {
            $mailfetch_subfolder == 'INBOX';
        }

        $pop3 = new POP3($mailfetch_server, 60);

        echo '<br>' .
        html_tag( 'table',
            html_tag( 'tr',
                html_tag( 'td', '<b>' . _("Fetching from ") . 
                    htmlspecialchars($mailfetch[$i_loop]['alias']) . 
                    '</b>',
                'center' ) ,
            '', $color[9] ) ,
        '', '', 'width="90%"' );

        flush();

        if (!$pop3->connect($mailfetch_server,$mailfetch_port)) {
            Mail_Fetch_Status(_("Oops, ") . $pop3->ERROR );
            continue;
        }

        Mail_Fetch_Status(_("Opening IMAP server"));
        $imap_stream = sqimap_login($username, $key, $imapServerAddress, $imapPort, 10);

        Mail_Fetch_Status(_("Opening POP server"));
        $Count = $pop3->login($mailfetch_user, $mailfetch_pass);
        if (($Count == false || $Count == -1) && $pop3->ERROR != '') {
            Mail_Fetch_Status(_("Login Failed:") . ' ' . $pop3->ERROR );
            continue;
        }

        //   register_shutdown_function($pop3->quit());

        $msglist = $pop3->uidl();

        $i = 1;
        for ($j = 1; $j < sizeof($msglist); $j++) {
           if ($msglist[$j] == $mailfetch_uidl) {
                $i = $j+1;
                break;
           }
        }

        if ($Count < $i) {
            Mail_Fetch_Status(_("Login OK: No new messages"));
            $pop3->quit();
            continue;
        }
        if ($Count == 0) {
            Mail_Fetch_Status(_("Login OK: Inbox EMPTY"));
            $pop3->quit();
            continue;
        } else {
            $newmsgcount = $Count - $i + 1;
            Mail_Fetch_Status(_("Login OK: Inbox contains [") . $newmsgcount . _("] messages"));
        }

        Mail_Fetch_Status(_("Fetching UIDL..."));
        // Faster to get them all at once
        $mailfetch_uidl = $pop3->uidl();

        if (! is_array($mailfetch_uidl) && $mailfetch_lmos == 'on')
            Mail_Fetch_Status(_("Server does not support UIDL."));

        if ($mailfetch_lmos == 'on') {
            Mail_Fetch_Status(_("Leaving Mail on Server..."));
        } else {
            Mail_Fetch_Status(_("Deleting messages from server..."));
        }

        for (; $i <= $Count; $i++) {
            Mail_Fetch_Status(_("Fetching message ") . "$i" );
            set_time_limit(20); // 20 seconds per message max
            $Message = '';
            $MessArray = $pop3->get($i);

            while ( (!$MessArray) or (gettype($MessArray) != "array")) {
                 Mail_Fetch_Status(_("Oops, ") . $pop3->ERROR);
                 // re-connect pop3
                 Mail_Fetch_Status(_("Server error...Disconnect"));
                 $pop3->quit();
                 Mail_Fetch_Status(_("Reconnect from dead connection"));
                 if (!$pop3->connect($mailfetch_server)) {
                     Mail_Fetch_Status(_("Oops, ") . $pop3->ERROR );
                     Mail_Fetch_Status(_("Saving UIDL"));
                     setPref($data_dir,$username,"mailfetch_uidl_$i_loop", $mailfetch_uidl[$i-1]);

                     continue;
                 }
                 $Count = $pop3->login($mailfetch_user, $mailfetch_pass);
                 if (($Count == false || $Count == -1) && $pop3->ERROR != '') {
                     Mail_Fetch_Status(_("Login Failed:") . ' ' . $pop3->ERROR );
                     Mail_Fetch_Status(_("Saving UIDL"));
                     setPref($data_dir,$username,"mailfetch_uidl_$i_loop", $mailfetch_uidl[$i-1]);

                     continue;
                 }
                 Mail_Fetch_Status(_("Refetching message ") . "$i" );
                 $MessArray = $pop3->get($i);

            } // end while

            while (list($lineNum, $line) = each ($MessArray)) {
                 $Message .= $line;
            }

            fputs($imap_stream, "A3$i APPEND \"$mailfetch_subfolder\" {" . strlen($Message) . "}\r\n");
            $Line = fgets($imap_stream, 1024);
            if (substr($Line, 0, 1) == '+') {
                fputs($imap_stream, $Message);
                fputs($imap_stream, "\r\n");
                sqimap_read_data($imap_stream, "A3$i", false, $response, $message);
                if ($response != 'OK') {
                    Mail_Fetch_Status(_("Error Appending Message!")." ".$message );
                    Mail_Fetch_Status(_("Closing POP"));
                    $pop3->quit();
                    Mail_Fetch_Status(_("Logging out from IMAP"));
                    sqimap_logout($imap_stream);

                    Mail_Fetch_Status(_("Saving UIDL"));
                    setPref($data_dir,$username,"mailfetch_uidl_$i_loop", $mailfetch_uidl[$i-1]);
                    exit;
                } else {
                    Mail_Fetch_Status(_("Message appended to mailbox"));
                }

                if ($mailfetch_lmos != 'on') {
                   if( $pop3->delete($i) ) {
                        Mail_Fetch_Status(_("Message ") . $i . _(" deleted from Remote Server!"));
                   } else {
                        Mail_Fetch_Status(_("Delete failed:") . $pop3->ERROR );
                   }
                }
            } else {
                echo $Line;
                Mail_Fetch_Status(_("Error Appending Message!"));
                Mail_Fetch_Status(_("Closing POP"));
                $pop3->quit();
                Mail_Fetch_Status(_("Logging out from IMAP"));
                sqimap_logout($imap_stream);

                // not gurantee corect!
                Mail_Fetch_Status(_("Saving UIDL"));
                setPref($data_dir,$username,"mailfetch_uidl_$i_loop", $mailfetch_uidl[$i-1]);
                exit;
            }
        }

        Mail_Fetch_Status(_("Closing POP"));
        $pop3->quit();
        Mail_Fetch_Status(_("Logging out from IMAP"));
        sqimap_logout($imap_stream);
        if (is_array($mailfetch_uidl)) {
            Mail_Fetch_Status(_("Saving UIDL"));
            setPref($data_dir,$username,"mailfetch_uidl_$i_loop", array_pop($mailfetch_uidl));
        }

        Mail_Fetch_Status(_("Done"));

   }

?>
</center>
</body>
</html>
