<?php

   /**
    **  mail_fetch/fetch.php
    **
    **  Copyright (c) 1999-2002 The SquirrelMail Project Team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    **  Fetch code.
    **
    **  $Id$
    **/

    chdir('..');
    require_once('../src/validate.php');
    require_once('../functions/page_header.php');
    require_once('../functions/imap.php');
    require_once('../src/load_prefs.php');
    require_once('../plugins/mail_fetch/class.POP3.php');
    require_once('../functions/i18n.php');
    require_once( '../plugins/mail_fetch/functions.php' );
    require_once( '../functions/html.php' );


    function Mail_Fetch_Status($msg) {
        echo html_tag( 'table',
                   html_tag( 'tr',
                       html_tag( 'td', htmlspecialchars( $msg ) , 'left' )
                   ),
                 '', '', 'width="90%"' );
        flush();
    }

    displayPageHeader($color, 'None');

    $mailfetch_server_number = getPref($data_dir, $username, "mailfetch_server_number");
    if (!isset($mailfetch_server_number)) $mailfetch_server_number=0;
    $mailfetch_cypher = getPref($data_dir, $username, "mailfetch_cypher");
    if ($mailfetch_server_number<1) $mailfetch_server_number=0;
    for ($i=0;$i<$mailfetch_server_number;$i++) {
        $mailfetch_server_[$i] = getPref($data_dir, $username, "mailfetch_server_$i");
        $mailfetch_alias_[$i] = getPref($data_dir, $username, "mailfetch_alias_$i");
        $mailfetch_user_[$i] = getPref($data_dir, $username, "mailfetch_user_$i");
        $mailfetch_pass_[$i] = getPref($data_dir, $username, "mailfetch_pass_$i");
        $mailfetch_lmos_[$i] = getPref($data_dir, $username, "mailfetch_lmos_$i");
        $mailfetch_login_[$i] = getPref($data_dir, $username, "mailfetch_login_$i");
        $mailfetch_uidl_[$i] = getPref($data_dir, $username, "mailfetch_uidl_$i");
        $mailfetch_subfolder_[$i] = getPref($data_dir, $username, "mailfetch_subfolder_$i");
        if( $mailfetch_cypher == 'on' ) {
            $mailfetch_pass_[$i] = decrypt( $mailfetch_pass_[$i] );
        }
    }
    
    echo '<br><center>';
    
    echo html_tag( 'table',
               html_tag( 'tr',
                   html_tag( 'td', '<b>' . _("Remote POP server Fetching Mail") . '</b>', 'center', $color[0] )
               ) ,
           'center', '', 'width="95%" cols="1"' );
    
    if (!isset( $server_to_fetch ) ) {
        
        echo '<font size=-5><br></font>' .
             "<form action=\"$PHP_SELF\" method=\"post\" target=\"_self\">" .
             html_tag( 'table', '', 'center', '', 'width="70%" cols="2"' ) .
                 html_tag( 'tr' ) .
                     html_tag( 'td', _("Select Server:") . ' &nbsp; &nbsp;', 'right' ) .
                     html_tag( 'td', '', 'left' ) .
                         '<select name="server_to_fetch" size="1">' .
                         '<option value="all" selected>..' . _("All") . "...\n";
        for ($i=0;$i<$mailfetch_server_number;$i++) {
             echo "<option value=\"$i\">" .
                  (($mailfetch_alias_[$i]=='')?$mailfetch_server_[$i]:$mailfetch_alias_[$i]) .
                  '</option>' . "\n";
        } 
        echo            '</select>' .
                    '</td>' .
                '</tr>';
        
        //if password not set, ask for it
        for ($i=0;$i<$mailfetch_server_number;$i++) {
             if ($mailfetch_pass_[$i]=='') {
                  echo html_tag( 'tr',
                              html_tag( 'td', _("Password for") . ' <b>' .
                                  (($mailfetch_alias_[$i]=='')?$mailfetch_server_[$i]:$mailfetch_alias_[$i]) .
                                  '</b>: &nbsp; &nbsp; ',
                              'right' ) .
                              html_tag( 'td', '<input type="password" name="pass_' . $i , '">', 'left' )
                          );
             }
        }
        echo html_tag( 'tr',
                   html_tag( 'td', '&nbsp;' ) .
                   html_tag( 'td', '<input type=submit name=submit_mailfetch value="' . _("Fetch Mail"). '">', 'left' )
               );

             '</table></form>';
        exit();
    }

    if ( $server_to_fetch == 'all' ) {
        $i_start = 0;
        $i_stop = $mailfetch_server_number;
    } else {
        $i_start = $server_to_fetch;
        $i_stop = $i_start+1;
    }
    
    for ($i_loop=$i_start;$i_loop<$i_stop;$i_loop++) {
        $mailfetch_server=$mailfetch_server_[$i_loop];
        $mailfetch_user=$mailfetch_user_[$i_loop];
        if ($mailfetch_pass_[$i_loop]=="") {
            $tmp="pass_$i_loop";
            $mailfetch_pass=$$tmp;
        } else {
            $mailfetch_pass=$mailfetch_pass_[$i_loop];
        }
        $mailfetch_lmos=$mailfetch_lmos_[$i_loop];
        $mailfetch_login=$mailfetch_login_[$i_loop];
        $mailfetch_uidl=$mailfetch_uidl_[$i_loop];
        $mailfetch_subfolder=$mailfetch_subfolder_[$i_loop];
        
        
        $pop3 = new POP3($mailfetch_server, 60);
        
        echo '<br>' .
        html_tag( 'table',
            html_tag( 'tr',
                html_tag( 'td', '<b>' . _("Fetching from ") . 
                    (($mailfetch_alias_[$i_loop] == '')?$mailfetch_server:$mailfetch_alias_[$i_loop]) . 
                    '</b>',
                'center' ) ,
            '', $color[9] ) ,
        '', '', 'width="90%"' );
          
        flush();
        
        if (!$pop3->connect($mailfetch_server)) {
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
           if ($msglist["$j"] == $mailfetch_uidl) {
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
            $Message = "";
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
            
            if ($mailfetch_subfolder=="") {
                fputs($imap_stream, "A3$i APPEND INBOX {" . (strlen($Message) - 1) . "}\r\n");
            } else {
                fputs($imap_stream, "A3$i APPEND \"$mailfetch_subfolder\" {" . (strlen($Message) - 1) . "}\r\n");
            }
            $Line = fgets($imap_stream, 1024);
            if (substr($Line, 0, 1) == '+') {
                fputs($imap_stream, $Message);
                sqimap_read_data($imap_stream, "A3$i", false, $response, $message);
                if ( $response <> 'OK' ) {
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
                echo "$Line";
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
