<?php

    require_once( '../plugins/mail_fetch/functions.php' );

    function squirrelmail_plugin_init_mail_fetch() {
        global $squirrelmail_plugin_hooks;
        global $mailbox, $imap_stream, $imapConnection;

        $squirrelmail_plugin_hooks["menuline"]["mail_fetch"] = 'mail_fetch_link';
        $squirrelmail_plugin_hooks["options_save"]["mail_fetch"] = "mail_fetch_save_pref";
        $squirrelmail_plugin_hooks["loading_prefs"]["mail_fetch"] = "mail_fetch_load_pref";
        $squirrelmail_plugin_hooks["login_verified"]["mail_fetch"] = "mail_fetch_setnew";
        $squirrelmail_plugin_hooks["left_main_before"]["mail_fetch"] = "mail_fetch_login";
        $squirrelmail_plugin_hooks['optpage_register_block']['mail_fetch'] = 'mailfetch_optpage_register_block';

    }

    function mail_fetch_link() {

        displayInternalLink('plugins/mail_fetch/fetch.php', _("Fetch"), '');
        echo '&nbsp;&nbsp;';

    }

    function mail_fetch_load_pref() {

        global $username,$data_dir;
        global $mailfetch_server_number;
        global $mailfetch_cypher;
        global $mailfetch_server_,$mailfetch_alias_,$mailfetch_user_,$mailfetch_pass_;
        global $mailfetch_lmos_, $mailfetch_uidl_, $mailfetch_login_, $mailfetch_fref_;
        global $PHP_SELF;

        if( stristr( $PHP_SELF, 'mail_fetch'    ) ) {
            $mailfetch_server_number = getPref($data_dir, $username,    "mailfetch_server_number");
            if (!isset($mailfetch_server_number)) $mailfetch_server_number=0;
            $mailfetch_cypher = getPref($data_dir, $username, "mailfetch_cypher");
            if ($mailfetch_server_number<1) $mailfetch_server_number=0;
            for ($i=0;$i<$mailfetch_server_number;$i++) {
                $mailfetch_server_[$i] = getPref($data_dir, $username, "mailfetch_server_$i");
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

    function mail_fetch_save_pref() {
        global $username,$data_dir;
        global $mf_server, $mf_user, $mf_pass, $mf_lmos, $mf_alias;
        global $mf_cypher;
        global $mf_uidl;
        global $mf_login, $mf_fref, $submit_mailfetch;
        global $mf_action, $mf_sn, $mf_subfolder;

        if (isset($submit_mailfetch))   {
            if ($mf_action=="add") {
                //add new server
                if ($mf_sn<1) $mf_sn=0;
                if (!isset($mf_server)) return;
                setPref($data_dir,$username,"mailfetch_server_$mf_sn", (isset($mf_server)?$mf_server:""));
                setPref($data_dir,$username,"mailfetch_alias_$mf_sn", (isset($mf_alias)?$mf_alias:""));
                setPref($data_dir,$username,"mailfetch_user_$mf_sn",(isset($mf_user)?$mf_user:""));
                setPref($data_dir,$username,"mailfetch_pass_$mf_sn",(isset($mf_pass)?encrypt( $mf_pass )    :""));
                if( $mf_cypher <> 'on' ) SetPref($data_dir,$username,"mailfetch_cypher",    'on');
                setPref($data_dir,$username,"mailfetch_lmos_$mf_sn",(isset($mf_lmos)?$mf_lmos:""));
                setPref($data_dir,$username,"mailfetch_login_$mf_sn",(isset($mf_login)?$mf_login:""));
                setPref($data_dir,$username,"mailfetch_fref_$mf_sn",(isset($mf_fref)?$mf_fref:""));
                setPref($data_dir,$username,"mailfetch_subfolder_$mf_sn",(isset($mf_subfolder)?$mf_subfolder:""));
                $mf_sn++;
                setPref($data_dir,$username,"mailfetch_server_number", $mf_sn);
            } elseif ($mf_action=="confirm_modify") {
                //modify    a server
                if (!isset($mf_server)) return;
                setPref($data_dir,$username,"mailfetch_server_$mf_sn", (isset($mf_server)?$mf_server:""));
                setPref($data_dir,$username,"mailfetch_alias_$mf_sn", (isset($mf_alias)?$mf_alias:""));
                setPref($data_dir,$username,"mailfetch_user_$mf_sn",(isset($mf_user)?$mf_user:""));
                setPref($data_dir,$username,"mailfetch_pass_$mf_sn",(isset($mf_pass)?encrypt( $mf_pass )    :""));
                if( $mf_cypher <> 'on' ) setPref($data_dir,$username,"mailfetch_cypher", 'on');
                setPref($data_dir,$username,"mailfetch_lmos_$mf_sn",(isset($mf_lmos)?$mf_lmos:""));
                setPref($data_dir,$username,"mailfetch_login_$mf_sn",(isset($mf_login)?$mf_login:""));
                setPref($data_dir,$username,"mailfetch_fref_$mf_sn",(isset($mf_fref)?$mf_fref:""));
                setPref($data_dir,$username,"mailfetch_subfolder_$mf_sn",(isset($mf_subfolder)?$mf_subfolder:""));
            } elseif ($mf_action=="confirm_delete") {
                //delete    a server
                $mailfetch_server_number    = getPref($data_dir, $username, "mailfetch_server_number");
                if ($mf_sn+1==$mailfetch_server_number) {
                    //is the last server, whe can only decrase $mailfetch_server_number
                    $mailfetch_server_number--;
                    setPref($data_dir,$username,"mailfetch_server_number", $mailfetch_server_number);
                } else {
                    //if not the last, all the sequel server come up one step
                    //then whe decrase $mailfetch_server_number
                    $mailfetch_server_number--;
                    for ($i=$mf_sn;$i<$mailfetch_server_number;$i++) {
                        $tmp=$i+1;
                        setPref($data_dir,$username,"mailfetch_server_$mf_sn", getPref($data_dir, $username, "mailfetch_server_$tmp"));
                        setPref($data_dir,$username,"mailfetch_alias_$mf_sn", getPref($data_dir, $username, "mailfetch_alias_$tmp"));
                        setPref($data_dir,$username,"mailfetch_user_$mf_sn", getPref($data_dir, $username, "mailfetch_user_$tmp"));
                        setPref($data_dir,$username,"mailfetch_pass_$mf_sn",(isset($mf_pass)?encrypt( $mf_pass ) :""));
                        // if( $mf_cypher <> 'on' ) setPref($data_dir,$username,"mailfetch_cypher", 'on');
                        setPref($data_dir,$username,"mailfetch_lmos_$mf_sn", getPref($data_dir, $username, "mailfetch_lmos_$tmp"));
                        setPref($data_dir,$username,"mailfetch_login_$mf_sn", getPref($data_dir, $username, "mailfetch_login_$tmp"));
                        setPref($data_dir,$username,"mailfetch_fref_$mf_sn", getPref($data_dir, $username, "mailfetch_fref_$tmp"));
                        setPref($data_dir,$username,"mailfetch_subfolder_$mf_sn", getPref($data_dir, $username, "mailfetch_subfolder_$tmp"));
                    }
                    setPref($data_dir,$username,"mailfetch_server_number", $mailfetch_server_number);
                }
            }
        }
    }

    function mail_fetch_login() {

        require_once ('../src/validate.php');
        require_once ('../functions/imap.php');
        require_once ('../plugins/mail_fetch/class.POP3.php');
        require_once ('../plugins/mail_fetch/functions.php');
        require_once('../functions/i18n.php');

        global $username, $data_dir, $key,$imapServerAddress,$imapPort;

        $mailfetch_newlog = getPref($data_dir, $username, 'mailfetch_newlog');

        $outMsg = '';

        $mailfetch_server_number = getPref($data_dir, $username, "mailfetch_server_number");
        if (!isset($mailfetch_server_number)) $mailfetch_server_number=0;
        $mailfetch_cypher = getPref($data_dir, $username, "mailfetch_cypher");
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
                $mailfetch_alias_[$i_loop] = getPref($data_dir, $username, "mailfetch_alias_$i_loop");
                $mailfetch_user_[$i_loop] = getPref($data_dir, $username, "mailfetch_user_$i_loop");
                $mailfetch_lmos_[$i_loop] = getPref($data_dir, $username, "mailfetch_lmos_$i_loop");
                $mailfetch_uidl_[$i_loop] = getPref($data_dir, $username, "mailfetch_uidl_$i_loop");
                $mailfetch_subfolder_[$i_loop] = getPref($data_dir, $username, "mailfetch_subfolder_$i_loop");

                $mailfetch_server=$mailfetch_server_[$i_loop];
                $mailfetch_user=$mailfetch_user_[$i_loop];
                $mailfetch_alias=$mailfetch_alias_[$i_loop];
                $mailfetch_pass=$mailfetch_pass_[$i_loop];
                $mailfetch_lmos=$mailfetch_lmos_[$i_loop];
                $mailfetch_login=$mailfetch_login_[$i_loop];
                $mailfetch_uidl=$mailfetch_uidl_[$i_loop];
                $mailfetch_subfolder=$mailfetch_subfolder_[$i_loop];

                // $outMsg .= "$mailfetch_alias checked<br>";

                // $outMsg .= "$mailfetch_alias_[$i_loop]<br>";

                $pop3 = new POP3($mailfetch_server, 60);

                if (!$pop3->connect($mailfetch_server)) {
                    $outMsg .= _("Warning, ") . $pop3->ERROR;
                    continue;
                }

                $imap_stream = sqimap_login($username, $key, $imapServerAddress, $imapPort, 10);

                $Count = $pop3->login($mailfetch_user, $mailfetch_pass);
                if (($Count == false || $Count == -1) && $pop3->ERROR != '') {
                    $outMsg .= _("Login Failed:") . $pop3->ERROR;
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
                } else {
                    $newmsgcount = $Count - $i + 1;
                }

                // Faster to get them all at once
                $mailfetch_uidl = $pop3->uidl();

                if (! is_array($mailfetch_uidl) && $mailfetch_lmos == 'on')
                    $outMsg .= _("Server does not support UIDL.");

                for (; $i <= $Count; $i++) {
                    set_time_limit(20); // 20 seconds per message max
                    $Message = "";
                    $MessArray = $pop3->get($i);

                    if ( (!$MessArray) or (gettype($MessArray) != "array")) {
                        $outMsg .= _("Warning, ") . $pop3->ERROR;
                        continue 2;
                    }

                    while (list($lineNum, $line) = each ($MessArray)) {
                        $Message .= $line;
                    }

                    if ($mailfetch_subfolder=="") {
                        fputs($imap_stream, "A3$i APPEND INBOX {" . (strlen($Message) - 1) . "}\r\n");
                    } else {
                        fputs($imap_stream, "A3$i APPEND $mailfetch_subfolder {" . (strlen($Message) - 1) . "}\r\n");
                    }
                    $Line = fgets($imap_stream, 1024);
                    if (substr($Line, 0, 1) == '+') {
                        fputs($imap_stream, $Message);
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

        if( trim( $outMsg ) <> '' )
            echo '<br><font size=1>' . _("Mail Fetch Result:") . "<br>$outMsg</font>";

        if( $mailfetch_newlog == 'on' )
            setPref($data_dir,$username,"mailfetch_newlog", 'off');

    }

    function mail_fetch_setnew()    {

        global $data_dir,$username;
        // require_once ('../src/load_prefs.php');
        // require_once ('../src/validate.php');
        require_once('../functions/prefs.php');

        if( $username <> '' ) {
            // Creates the pref file if it does not exist.
        checkForPrefs( $data_dir, $username );
            setPref( $data_dir, $username, 'mailfetch_newlog', 'on' );
        }

    }

    function mailfetch_optpage_register_block() {
      global $optpage_blocks;

      $optpage_blocks[] = array(
         'name' => _("Simple POP3 Fetch Mail"),
         'url'  => '../plugins/mail_fetch/options.php',
         'desc' => _("This configures settings for downloading email from a pop3 mailbox to your account on this server."),
         'js'   => false
      );
   }

?>