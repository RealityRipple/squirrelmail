<?php

/**
 * Message and Spam Filter Plugin
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This plugin filters your inbox into different folders based upon given
 * criteria.  It is most useful for people who are subscibed to mailing lists
 * to help organize their messages.  The argument stands that filtering is
 * not the place of the client, which is why this has been made a plugin for
 * SquirrelMail.  You may be better off using products such as Sieve or
 * Procmail to do your filtering so it happens even when SquirrelMail isn't
 * running.
 *
 * If you need help with this, or see improvements that can be made, please
 * email me directly at the address above.  I definately welcome suggestions
 * and comments.  This plugin, as is the case with all SquirrelMail plugins,
 * is not directly supported by the developers.  Please come to me off the
 * mailing list if you have trouble with it.
 *
 * Also view plugins/README.plugins for more information.
 *
 * $Id$
 */

function filters_SaveCache () {
    global $data_dir, $SpamFilters_DNScache;

    if (file_exists($data_dir . "/dnscache")) {
        $fp = fopen($data_dir . "/dnscache", "r");
    } else {
        $fp = false;
    }
    if ($fp) {
        flock($fp,LOCK_EX);
    } else {
       $fp = fopen($data_dir . "/dnscache", "w+");
       fclose($fp);
       $fp = fopen($data_dir . "/dnscache", "r");
       flock($fp,LOCK_EX);
    }
    $fp1=fopen($data_dir . "/dnscache", "w+");

    foreach ($SpamFilters_DNScache as $Key=> $Value) {
       $tstr = $Key . ',' . $Value['L'] . ',' . $Value['T'] . "\n";
       fputs ($fp1, $tstr);
    }
    fclose($fp1);
    flock($fp,LOCK_UN);
    fclose($fp);
}


function filters_LoadCache () {
    global $data_dir, $SpamFilters_DNScache;

    if (file_exists($data_dir . "/dnscache")) {
        $SpamFilters_DNScache = array();
        if ($fp = fopen ($data_dir . "/dnscache", "r")) {
            flock($fp,LOCK_SH);
            while ($data=fgetcsv($fp,1024)) {
               if ($data[2] > time()) {
                  $SpamFilters_DNScache[$data[0]]['L'] = $data[1];
                  $SpamFilters_DNScache[$data[0]]['T'] = $data[2];
               }
            }

            flock($fp,LOCK_UN);
        }
    }
}

function filters_bulkquery($filters_spam_scan, $filters, $read) {
    global $SpamFilters_YourHop, $attachment_dir, $username,
           $SpamFilters_DNScache, $SpamFilters_BulkQuery,
           $SpamFilters_CacheTTL;

    $IPs = array();
    $i = 0;
    while ($i < count($read)) {
        // EIMS will give funky results
        $Chunks = explode(' ', $read[$i]);
        if ($Chunks[0] != '*') {
            $i ++;
            continue;
        }
        $MsgNum = $Chunks[1];

        $i ++;

        // Look through all of the Received headers for IP addresses
        // Stop when I get ")" on a line
        // Stop if I get "*" on a line (don't advance)
        // and above all, stop if $i is bigger than the total # of lines
        while (($i < count($read)) &&
                ($read[$i][0] != ')' && $read[$i][0] != '*' &&
                $read[$i][0] != "\n")) {
            // Check to see if this line is the right "Received from" line
            // to check
            if (is_int(strpos($read[$i], $SpamFilters_YourHop))) {
                $read[$i] = ereg_replace('[^0-9\.]', ' ', $read[$i]);
                $elements = explode(' ', $read[$i]);
                foreach ($elements as $value) {
                    if ($value != '' &&
                        ereg('[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}',
                            $value, $regs)) {
                        $Chunks = explode('.', $value);
                        $IP = $Chunks[3] . '.' . $Chunks[2] . '.' .
                              $Chunks[1] . '.' . $Chunks[0];
                        foreach ($filters as $key => $value) {
                            if ($filters[$key]['enabled'] &&
                                      $filters[$key]['dns']) {
                                if (strlen($SpamFilters_DNScache[$IP.'.'.$filters[$key]['dns']]) == 0) {
                                   $IPs[$IP] = true;
                                   break;
                                }
                            }
                        }
                        // If we've checked one IP and YourHop is
                        // just a space
                        if ($SpamFilters_YourHop == ' ') {
                            break;  // don't check any more
                        }
                    }
                }
            }
            $i ++;
        }
    }

    if (count($IPs) > 0) {
        $rbls = array();
        foreach ($filters as $key => $value) {
            if ($filters[$key]['enabled']) {
                if ($filters[$key]['dns']) {
                    $rbls[$filters[$key]['dns']] = true;
                }
            }
        }

        $bqfil = $attachment_dir . $username . "-bq.in";
        $fp = fopen($bqfil, "w");
        fputs ($fp, $SpamFilters_CacheTTL . "\n");
        foreach ($rbls as $key => $value) {
            fputs ($fp, "." . $key . "\n");
        }
        fputs ($fp, "----------\n");
        foreach ($IPs as $key => $value) {
            fputs ($fp, $key . "\n");
        }
        fclose ($fp);
        $bqout = array();
        exec ($SpamFilters_BulkQuery . " < " . $bqfil, $bqout);
        foreach ($bqout as $value) {
            $Chunks = explode(',', $value);
            $SpamFilters_DNScache[$Chunks[0]]['L'] = $Chunks[1];
            $SpamFilters_DNScache[$Chunks[0]]['T'] = $Chunks[2] + time();
        }
        unlink($bqfil);
    }
}


function filters_sqimap_read_data ($imap_stream, $pre, $handle_errors, &$response, &$message) {
    global $color, $squirrelmail_language, $imap_general_debug;

    $data = array();
    $size = 0;

    do {
        $read = fgets($imap_stream, 9096);
        if (ereg("^$pre (OK|BAD|NO)(.*)$", $read, $regs)) {
            break;  // found end of reply
        }

        // Continue if needed for this single line
        while (strpos($read, "\n") === false) {
            $read .= fgets($imap_stream, 9096);
        }

        $data[] = $read;

        if (ereg("^\\* [0-9]+ FETCH.*\\{([0-9]+)\\}", $read, $regs)) {
            $size = $regs[1];
            if ($imap_general_debug) {
                echo "<small><tt><font color=\"#CC0000\">Size is $size</font></tt></small><br>\n";
            }

            $total_size = 0;
            do {
                $read = fgets($imap_stream, 9096);
                if ($imap_general_debug) {
                    echo "<small><tt><font color=\"#CC0000\">$read</font></tt></small><br>\n";
                    flush();
                }
                $data[] = $read;
                $total_size += strlen($read);
            } while ($total_size < $size);

            $size = 0;
        }
        // For debugging purposes
        if ($imap_general_debug) {
            echo "<small><tt><font color=\"#CC0000\">$read</font></tt></small><br>\n";
            flush();
        }
    } while (true);

    $response = $regs[1];
    $message = trim($regs[2]);

    if ($imap_general_debug) {
        echo '--<br>';
    }

    if (!$handle_errors) {
        return $data;
    }

    if ($response == 'NO') {
        // ignore this error from m$ exchange, it is not fatal (aka bug)
        if (strstr($message, 'command resulted in') === false) {
            set_up_language($squirrelmail_language);
            echo "<br><b><font color=$color[2]>\n" .
                 _("ERROR : Could not complete request.") .
                 "</b><br>\n" .
                 _("Reason Given: ") .
                 $message . "</font><br>\n";
            exit;
        }
    } else if ($response == 'BAD') {
        set_up_language($squirrelmail_language);
        echo "<br><b><font color=$color[2]>\n" .
             _("ERROR : Bad or malformed request.") .
             "</b><br>\n" .
             _("Server responded: ") .
             $message . "</font><br>\n";
        exit;
    }

    return $data;
}


function start_filters() {
    global $mailbox, $username, $key, $imapServerAddress, $imapPort, $imap,
        $imap_general, $filters, $imap_stream, $imapConnection,
    $UseSeparateImapConnection, $AllowSpamFilters;

#    if ($mailbox == 'INBOX') {
        // Detect if we have already connected to IMAP or not.
        // Also check if we are forced to use a separate IMAP connection
        if ((!isset($imap_stream) && !isset($imapConnection)) ||
            $UseSeparateImapConnection) {
                $stream = sqimap_login($username, $key, $imapServerAddress,
                                    $imapPort, 10);
                $previously_connected = false;
        } elseif (isset($imapConnection)) {
            $stream = $imapConnection;
            $previously_connected = true;
        } else {
            $previously_connected = true;
            $stream = $imap_stream;
        }

        if (sqimap_get_num_messages($stream, 'INBOX') > 0) {
            // Filter spam from inbox before we sort them into folders
            if ($AllowSpamFilters) {
                spam_filters($stream);
            }

            // Sort into folders
            user_filters($stream);
        }

        if (!$previously_connected) {
            sqimap_logout($stream);
        }
#    }
}


function user_filters($imap_stream) {
    global $data_dir, $username;
    $filters = load_filters();
    if (! $filters) return;
    $filters_user_scan = getPref($data_dir, $username, 'filters_user_scan');

    sqimap_mailbox_select($imap_stream, 'INBOX');

    // For every rule
    for ($i=0; $i < count($filters); $i++) {
        // If it is the "combo" rule
        if ($filters[$i]['where'] == 'To or Cc') {
            /*
            *  If it's "TO OR CC", we have to do two searches, one for TO
            *  and the other for CC.
            */
            filter_search_and_delete($imap_stream, 'TO',
            $filters[$i]['what'], $filters[$i]['folder'], $filters_user_scan);
            filter_search_and_delete($imap_stream, 'CC',
            $filters[$i]['what'], $filters[$i]['folder'], $filters_user_scan);
        } else {
            /*
            *  If it's a normal TO, CC, SUBJECT, or FROM, then handle it
            *  normally.
            */
            filter_search_and_delete($imap_stream, $filters[$i]['where'],
            $filters[$i]['what'], $filters[$i]['folder'], $filters_user_scan);
        }
    }
    // Clean out the mailbox whether or not auto_expunge is on
    // That way it looks like it was redirected properly
    sqimap_mailbox_expunge($imap_stream, 'INBOX');
}

function filter_search_and_delete($imap, $where, $what, $where_to, $user_scan) {
    global $languages, $squirrelmail_language;
    if ($user_scan == 'new') {
        $category = 'UNSEEN';
    } else {
        $category = 'ALL';
    }

    if (isset($languages[$squirrelmail_language]['CHARSET']) &&
        $languages[$squirrelmail_language]['CHARSET']) {
        $search_str = "SEARCH CHARSET "
            . strtoupper($languages[$squirrelmail_language]['CHARSET']) 
            . ' ' . $category . ' ';
    } else {
        $search_str = 'SEARCH CHARSET US-ASCII ' . $category . ' ';
    }
    if ($where == "Header") {
       $what = explode(':', $what);
       $where = trim($where . ' ' . $what[0]);
       $what = addslashes(trim($what[1]));
    }
    $search_str .= $where . ' {' . strlen($what) . "}\r\n" . $what . "\r\n";
    
    fputs ($imap, "a001 $search_str");
    $read = filters_sqimap_read_data ($imap, 'a001', true, $response, $message);

    // This may have problems with EIMS due to it being goofy

    for ($r=0; $r < count($read) &&
                substr($read[$r], 0, 8) != '* SEARCH'; $r++) {}
    if ($response == 'OK') {
        $ids = explode(' ', $read[$r]);
        if (sqimap_mailbox_exists($imap, $where_to)) {
            for ($j=2; $j < count($ids); $j++) {
                $id = trim($ids[$j]);
                sqimap_messages_copy ($imap, $id, $id, $where_to);
                sqimap_messages_flag ($imap, $id, $id, 'Deleted');
            }
        }
    }
}

// These are the spam filters
function spam_filters($imap_stream) {
    global $data_dir, $username;
    global $SpamFilters_YourHop;
    global $SpamFilters_DNScache;
    global $SpamFilters_SharedCache;
    global $SpamFilters_BulkQuery;

    $filters_spam_scan = getPref($data_dir, $username, 'filters_spam_scan');
    $filters_spam_folder = getPref($data_dir, $username, 'filters_spam_folder');
    $filters = load_spam_filters();

    if ($SpamFilters_SharedCache) {
       filters_LoadCache();
    }

    $run = 0;

    foreach ($filters as $Key=> $Value) {
        if ($Value['enabled']) {
            $run ++;
        }
    }

    // short-circuit
    if ($run == 0) {
        return;
    }

    sqimap_mailbox_select($imap_stream, 'INBOX');

    // Ask for a big list of all "Received" headers in the inbox with
    // flags for each message.  Kinda big.
    //fputs($imap_stream, 'A3999 FETCH 1:* (FLAGS BODY.PEEK[HEADER.FIELDS ' .
    //    "(RECEIVED)])\r\n");
    $sid = sqimap_session_id();
    if ($filters_spam_scan != 'new') {
        fputs($imap_stream, $sid.' FETCH 1:* (FLAGS BODY.PEEK[HEADER.FIELDS ' .
            "(RECEIVED)])\r\n");
    } else {
        fputs ($imap_stream, $sid.' SEARCH UNSEEN' . "\r\n");
        $read = filters_sqimap_read_data ($imap_stream, $sid, true,
                                          $response, $message);
        $sid = sqimap_session_id();
        if ($response != 'OK' || trim($read[0]) == '* SEARCH') {
            fputs($imap_stream,
                  $sid.' FETCH 1:* (FLAGS BODY.PEEK[HEADER.FIELDS ' .
                     "(RECEIVED)])\r\n");
        } else {
	    $read[0] = trim($read[0]);
            $i = 0;
            $imap_query = $sid.' FETCH ';
            $Chunks = explode(' ', $read[0]);
            for ($i=2; $i < (count($Chunks)-1) ; $i++) {
                $imap_query .= $Chunks[$i].',';
            }
            $imap_query .= $Chunks[count($Chunks)-1];
            $imap_query .= ' (FLAGS BODY.PEEK[HEADER.FIELDS ';
            $imap_query .= "(RECEIVED)])\r\n";
            fputs($imap_stream, $imap_query);
        }
    }
    
    $read = filters_sqimap_read_data ($imap_stream, $sid, true,
                                    $response, $message);
    if ($response != 'OK') {
        return;
    }

    if (strlen($SpamFilters_BulkQuery) > 0) {
       filters_bulkquery($filters_spam_scan, $filters, $read);
    }

    $i = 0;
    while ($i < count($read)) {
        // EIMS will give funky results
        $Chunks = explode(' ', $read[$i]);
        if ($Chunks[0] != '*') {
            $i ++;
            continue;
        }
        $MsgNum = $Chunks[1];

        $IPs = array();
        $i ++;
        $IsSpam = 0;

        // Look through all of the Received headers for IP addresses
        // Stop when I get ")" on a line
        // Stop if I get "*" on a line (don't advance)
        // and above all, stop if $i is bigger than the total # of lines
        while (($i < count($read)) &&
                ($read[$i][0] != ')' && $read[$i][0] != '*' &&
                $read[$i][0] != "\n") && (! $IsSpam)) {
            // Check to see if this line is the right "Received from" line
            // to check
            if (is_int(strpos($read[$i], $SpamFilters_YourHop))) {

                // short-circuit and skip work if we don't scan this one
                $read[$i] = ereg_replace('[^0-9\.]', ' ', $read[$i]);
                $elements = explode(' ', $read[$i]);
                foreach ($elements as $value) {
                    if ($value != '' &&
                        ereg('[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}',
                            $value, $regs)) {
                        $Chunks = explode('.', $value);
                        if (filters_spam_check_site($Chunks[0],
                                $Chunks[1], $Chunks[2], $Chunks[3],
                                $filters)) {
                            $IsSpam ++;
                            break;  // no sense in checking more IPs
                        }
                        // If we've checked one IP and YourHop is
                        // just a space
                        if ($SpamFilters_YourHop == ' ') {
                            break;  // don't check any more
                        }
                    }
                }
            }
            $i ++;
        }

        // Lookie!  It's spam!  Yum!
        if ($IsSpam) {
            if (sqimap_mailbox_exists($imap_stream, $filters_spam_folder)) {
                sqimap_messages_copy ($imap_stream, $MsgNum, $MsgNum,
                                    $filters_spam_folder);
                sqimap_messages_flag ($imap_stream, $MsgNum, $MsgNum,
                                    'Deleted');
            }
        } else {
        }
    }

    sqimap_mailbox_expunge($imap_stream, 'INBOX');

    if ($SpamFilters_SharedCache) {
       filters_SaveCache();
    } else {
       session_register('SpamFilters_DNScache');
    }
}


// Does the loop through each enabled filter for the specified IP address.
// IP format:  $a.$b.$c.$d
function filters_spam_check_site($a, $b, $c, $d, &$filters) {
    global $SpamFilters_DNScache, $SpamFilters_CacheTTL;
    foreach ($filters as $key => $value) {
        if ($filters[$key]['enabled']) {
            if ($filters[$key]['dns']) {
                $filter_revip = $d . '.' . $c . '.' . $b . '.' . $a . '.' .
                                $filters[$key]['dns'];
                if (strlen($SpamFilters_DNScache[$filter_revip]['L']) == 0) {
                    $SpamFilters_DNScache[$filter_revip]['L'] =
                                    gethostbyname($filter_revip);
                    $SpamFilters_DNScache[$filter_revip]['T'] =
                                       time() + $SpamFilters_CacheTTL;
                }
                if ($SpamFilters_DNScache[$filter_revip]['L'] ==
                    $filters[$key]['result']) {
                    return 1;
                }
            }
        }
    }
    return 0;
}

function load_filters() {
    global $data_dir, $username;

    $filters = array();
    for ($i=0; $fltr = getPref($data_dir, $username, 'filter' . $i); $i++) {
        $ary = explode(',', $fltr);
        $filters[$i]['where'] = $ary[0];
        $filters[$i]['what'] = $ary[1];
        $filters[$i]['folder'] = $ary[2];
    }
    return $filters;
}

function load_spam_filters() {
    global $data_dir, $username, $SpamFilters_ShowCommercial;

    if ($SpamFilters_ShowCommercial) {
        $filters['MAPS RBL']['prefname'] = 'filters_spam_maps_rbl';
        $filters['MAPS RBL']['name'] = 'MAPS Realtime Blackhole List';
        $filters['MAPS RBL']['link'] = 'http://www.mail-abuse.org/rbl/';
        $filters['MAPS RBL']['dns'] = 'blackholes.mail-abuse.org';
        $filters['MAPS RBL']['result'] = '127.0.0.2';
        $filters['MAPS RBL']['comment'] =
            _("COMMERCIAL - This list contains servers that are verified spam senders. It is a pretty reliable list to scan spam from.");

        $filters['MAPS RSS']['prefname'] = 'filters_spam_maps_rss';
        $filters['MAPS RSS']['name'] = 'MAPS Relay Spam Stopper';
        $filters['MAPS RSS']['link'] = 'http://www.mail-abuse.org/rss/';
        $filters['MAPS RSS']['dns'] = 'relays.mail-abuse.org';
        $filters['MAPS RSS']['result'] = '127.0.0.2';
        $filters['MAPS RSS']['comment'] =
            _("COMMERCIAL - Servers that are configured (or misconfigured) to allow spam to be relayed through their system will be banned with this.  Another good one to use.");

        $filters['MAPS DUL']['prefname'] = 'filters_spam_maps_dul';
        $filters['MAPS DUL']['name'] = 'MAPS Dial-Up List';
        $filters['MAPS DUL']['link'] = 'http://www.mail-abuse.org/dul/';
        $filters['MAPS DUL']['dns'] = 'dialups.mail-abuse.org';
        $filters['MAPS DUL']['result'] = '127.0.0.3';
        $filters['MAPS DUL']['comment'] =
            _("COMMERCIAL - Dial-up users are often filtered out since they should use their ISP's mail servers to send mail.  Spammers typically get a dial-up account and send spam directly from there.");

        $filters['MAPS RBLplus-RBL']['prefname'] = 'filters_spam_maps_rblplus_rbl';
        $filters['MAPS RBLplus-RBL']['name'] = 'MAPS RBL+ RBL List';
        $filters['MAPS RBLplus-RBL']['link'] = 'http://www.mail-abuse.org/';
        $filters['MAPS RBLplus-RBL']['dns'] = 'rbl-plus.mail-abuse.org';
        $filters['MAPS RBLplus-RBL']['result'] = '127.0.0.2';
        $filters['MAPS RBLplus-RBL']['comment'] =
            _("COMMERCIAL - RBL+ Blackhole entries.");

        $filters['MAPS RBLplus-RSS']['prefname'] = 'filters_spam_maps_rblplus_rss';
        $filters['MAPS RBLplus-RSS']['name'] = 'MAPS RBL+ List RSS entries';
        $filters['MAPS RBLplus-RSS']['link'] = 'http://www.mail-abuse.org/';
        $filters['MAPS RBLplus-RSS']['dns'] = 'rbl-plus.mail-abuse.org';
        $filters['MAPS RBLplus-RSS']['result'] = '127.0.0.2';
        $filters['MAPS RBLplus-RSS']['comment'] =
            _("COMMERCIAL - RBL+ OpenRelay entries.");

        $filters['MAPS RBLplus-DUL']['prefname'] = 'filters_spam_maps_rblplus_dul';
        $filters['MAPS RBLplus-DUL']['name'] = 'MAPS RBL+ List DUL entries';
        $filters['MAPS RBLplus-DUL']['link'] = 'http://www.mail-abuse.org/';
        $filters['MAPS RBLplus-DUL']['dns'] = 'rbl-plus.mail-abuse.org';
        $filters['MAPS RBLplus-DUL']['result'] = '127.0.0.3';
        $filters['MAPS RBLplus-DUL']['comment'] =
            _("COMMERCIAL - RBL+ Dial-up entries.");
    }

    $filters['Osirusoft Relays']['prefname'] = 'filters_spam_maps_osirusoft_relay';
    $filters['Osirusoft Relays']['name'] = 'Osirusoft Relay List';
    $filters['Osirusoft Relays']['link'] = 'http://relays.osirusoft.com/';
    $filters['Osirusoft Relays']['dns'] = 'relays.osirusoft.com';
    $filters['Osirusoft Relays']['result'] = '127.0.0.2';
    $filters['Osirusoft Relays']['comment'] =
        _("FREE - Osirusoft Relays - Osirusofts list of verified open relays. Seems to include servers used by abuse@uunet.net auto-replies too.");

    $filters['Osirusoft DUL']['prefname'] = 'filters_spam_maps_osirusoft_dul';
    $filters['Osirusoft DUL']['name'] = 'Osirusoft Dialup List';
    $filters['Osirusoft DUL']['link'] = 'http://relays.osirusoft.com/';
    $filters['Osirusoft DUL']['dns'] = 'relays.osirusoft.com';
    $filters['Osirusoft DUL']['result'] = '127.0.0.3';
    $filters['Osirusoft DUL']['comment'] =
        _("FREE - Osirusoft Dialups - Osirusofts Dialup Spam Source list.");

    $filters['Osirusoft Spam Source']['prefname'] = 'filters_spam_maps_osirusoft_rc';
    $filters['Osirusoft Spam Source']['name'] = 'Osirusoft Confirmed Spam Source List';
    $filters['Osirusoft Spam Source']['link'] = 'http://relays.osirusoft.com/';
    $filters['Osirusoft Spam Source']['dns'] = 'relays.osirusoft.com';
    $filters['Osirusoft Spam Source']['result'] = '127.0.0.4';
    $filters['Osirusoft Spam Source']['comment'] =
        _("FREE - Osirusoft Confirmed Spam Source - Sites that continually spam and have been manually added after multiple nominations. Use with caution. Seems to catch abuse auto-replies from some ISPs.");

    $filters['Osirusoft Smart Host']['prefname'] = 'filters_spam_maps_osirusoft_sh';
    $filters['Osirusoft Smart Host']['name'] = 'Osirusoft Smart Host List';
    $filters['Osirusoft Smart Host']['link'] = 'http://relays.osirusoft.com/';
    $filters['Osirusoft Smart Host']['dns'] = 'relays.osirusoft.com';
    $filters['Osirusoft Smart Host']['result'] = '127.0.0.5';
    $filters['Osirusoft Smart Host']['comment'] =
        _("FREE - Osirusoft Smart Hosts - List of hosts that are secure but relay for other mail servers that are not secure.");

    $filters['Osirusoft SPAMware']['prefname'] = 'filters_spam_maps_osirusoft_ss';
    $filters['Osirusoft SPAMware']['name'] = 'Osirusoft Spamware Developers List';
    $filters['Osirusoft SPAMware']['link'] = 'http://relays.osirusoft.com/';
    $filters['Osirusoft SPAMware']['dns'] = 'relays.osirusoft.com';
    $filters['Osirusoft SPAMware']['result'] = '127.0.0.6';
    $filters['Osirusoft SPAMware']['comment'] =
        _("FREE - Osirusoft Spamware Developers - It is believed that these are IP ranges of companies that are known to produce spam software. Seems to catch abuse auto-replies from some ISPs.");

    $filters['Osirusoft Unc. OptIn']['prefname'] = 'filters_spam_maps_osirusoft_sl';
    $filters['Osirusoft Unc. OptIn']['name'] = 'Osirusoft Unconfirmed OptIn Server List';
    $filters['Osirusoft Unc. OptIn']['link'] = 'http://relays.osirusoft.com/';
    $filters['Osirusoft Unc. OptIn']['dns'] = 'relays.osirusoft.com';
    $filters['Osirusoft Unc. OptIn']['result'] = '127.0.0.7';
    $filters['Osirusoft Unc. OptIn']['comment'] =
        _("FREE - Osirusoft Unconfirmed OptIn Servers - List of listservers that opt users in without confirmation.");

    $filters['Osirusoft Insecure Formmail']['prefname'] = 'filters_spam_maps_osirusoft_fm';
    $filters['Osirusoft Insecure Formmail']['name'] = 'Osirusoft Insecure formmail.cvi Script List';
    $filters['Osirusoft Insecure Formmail']['link'] = 'http://relays.osirusoft.com/';
    $filters['Osirusoft Insecure Formmail']['dns'] = 'relays.osirusoft.com';
    $filters['Osirusoft Insecure Formmail']['result'] = '127.0.0.8';
    $filters['Osirusoft Insecure Formmail']['comment'] =
        _("FREE - Osirusoft Insecure formmail.cgi scripts - List of insecure formmail.cgi scripts. (planned).");

    $filters['Osirusoft Open Proxy']['prefname'] = 'filters_spam_maps_osirusoft_op';
    $filters['Osirusoft Open Proxy']['name'] = 'Osirusoft Open Proxy Server List';
    $filters['Osirusoft Open Proxy']['link'] = 'http://relays.osirusoft.com/';
    $filters['Osirusoft Open Proxy']['dns'] = 'relays.osirusoft.com';
    $filters['Osirusoft Open Proxy']['result'] = '127.0.0.9';
    $filters['Osirusoft Open Proxy']['comment'] =
        _("FREE - Osirusoft Open Proxy Servers - List of Open Proxy Servers.");

    $filters['ORDB']['prefname'] = 'filters_spam_ordb';
    $filters['ORDB']['name'] = 'Open Relay Database List';
    $filters['ORDB']['link'] = 'http://www.ordb.org/';
    $filters['ORDB']['dns'] = 'relays.ordb.org';
    $filters['ORDB']['result'] = '127.0.0.2';
    $filters['ORDB']['comment'] =
        _("FREE - ORDB was born when ORBS went off the air. It seems to have fewer false positives than ORBS did though.");

    $filters['FiveTen Direct']['prefname'] = 'filters_spam_fiveten_src';
    $filters['FiveTen Direct']['name'] = 'Five-Ten-sg.com Direct SPAM Sources';
    $filters['FiveTen Direct']['link'] = 'http://www.five-ten-sg.com/blackhole.php';
    $filters['FiveTen Direct']['dns'] = 'blackholes.five-ten-sg.com';
    $filters['FiveTen Direct']['result'] = '127.0.0.2';
    $filters['FiveTen Direct']['comment'] =
        _("FREE - Five-Ten-sg.com - Direct SPAM sources.");

    $filters['FiveTen DUL']['prefname'] = 'filters_spam_fiveten_dul';
    $filters['FiveTen DUL']['name'] = 'Five-Ten-sg.com DUL Lists';
    $filters['FiveTen DUL']['link'] = 'http://www.five-ten-sg.com/blackhole.php';
    $filters['FiveTen DUL']['dns'] = 'blackholes.five-ten-sg.com';
    $filters['FiveTen DUL']['result'] = '127.0.0.3';
    $filters['FiveTen DUL']['comment'] =
        _("FREE - Five-Ten-sg.com - Dial-up lists - includes some DSL IPs.");

    $filters['FiveTen Unc. OptIn']['prefname'] = 'filters_spam_fiveten_oi';
    $filters['FiveTen Unc. OptIn']['name'] = 'Five-Ten-sg.com Unconfirmed OptIn Lists';
    $filters['FiveTen Unc. OptIn']['link'] = 'http://www.five-ten-sg.com/blackhole.php';
    $filters['FiveTen Unc. OptIn']['dns'] = 'blackholes.five-ten-sg.com';
    $filters['FiveTen Unc. OptIn']['result'] = '127.0.0.4';
    $filters['FiveTen Unc. OptIn']['comment'] =
        _("FREE - Five-Ten-sg.com - Bulk mailers that do not use confirmed opt-in.");

    $filters['FiveTen Others']['prefname'] = 'filters_spam_fiveten_oth';
    $filters['FiveTen Others']['name'] = 'Five-Ten-sg.com Other Misc. Servers';
    $filters['FiveTen Others']['link'] = 'http://www.five-ten-sg.com/blackhole.php';
    $filters['FiveTen Others']['dns'] = 'blackholes.five-ten-sg.com';
    $filters['FiveTen Others']['result'] = '127.0.0.5';
    $filters['FiveTen Others']['comment'] =
        _("FREE - Five-Ten-sg.com - Other misc. servers.");

    $filters['FiveTen Single Stage']['prefname'] = 'filters_spam_fiveten_ss';
    $filters['FiveTen Single Stage']['name'] = 'Five-Ten-sg.com Single Stage Servers';
    $filters['FiveTen Single Stage']['link'] = 'http://www.five-ten-sg.com/blackhole.php';
    $filters['FiveTen Single Stage']['dns'] = 'blackholes.five-ten-sg.com';
    $filters['FiveTen Single Stage']['result'] = '127.0.0.6';
    $filters['FiveTen Single Stage']['comment'] =
        _("FREE - Five-Ten-sg.com - Single Stage servers.");

    $filters['FiveTen SPAM Support']['prefname'] = 'filters_spam_fiveten_supp';
    $filters['FiveTen SPAM Support']['name'] = 'Five-Ten-sg.com SPAM Support Servers';
    $filters['FiveTen SPAM Support']['link'] = 'http://www.five-ten-sg.com/blackhole.php';
    $filters['FiveTen SPAM Support']['dns'] = 'blackholes.five-ten-sg.com';
    $filters['FiveTen SPAM Support']['result'] = '127.0.0.7';
    $filters['FiveTen SPAM Support']['comment'] =
        _("FREE - Five-Ten-sg.com - SPAM Support servers.");

    $filters['FiveTen Web forms']['prefname'] = 'filters_spam_fiveten_wf';
    $filters['FiveTen Web forms']['name'] = 'Five-Ten-sg.com Web Form IPs';
    $filters['FiveTen Web forms']['link'] = 'http://www.five-ten-sg.com/blackhole.php';
    $filters['FiveTen Web forms']['dns'] = 'blackholes.five-ten-sg.com';
    $filters['FiveTen Web forms']['result'] = '127.0.0.8';
    $filters['FiveTen Web forms']['comment'] =
        _("FREE - Five-Ten-sg.com - Web Form IPs.");

    $filters['Dorkslayers']['prefname'] = 'filters_spam_dorks';
    $filters['Dorkslayers']['name'] = 'Dorkslayers Lists';
    $filters['Dorkslayers']['link'] = 'http://www.dorkslayers.com';
    $filters['Dorkslayers']['dns'] = 'orbs.dorkslayers.com';
    $filters['Dorkslayers']['result'] = '127.0.0.2';
    $filters['Dorkslayers']['comment'] =
        _("FREE - Dorkslayers appears to include only really bad open relays outside the US to avoid being sued. Interestingly enough, their website recommends you NOT use their service.");

    $filters['SPAMhaus']['prefname'] = 'filters_spam_spamhaus';
    $filters['SPAMhaus']['name'] = 'SPAMhaus Lists';
    $filters['SPAMhaus']['link'] = 'http://www.spamhaus.org';
    $filters['SPAMhaus']['dns'] = 'sbl.spamhaus.org';
    $filters['SPAMhaus']['result'] = '127.0.0.6';
    $filters['SPAMhaus']['comment'] =
        _("FREE - SPAMhaus - A list of well-known SPAM sources.");

    $filters['SPAMcop']['prefname'] = 'filters_spam_spamcop';
    $filters['SPAMcop']['name'] = 'SPAM Cop Lists';
    $filters['SPAMcop']['link'] = 'http://spamcop.net/bl.shtml';
    $filters['SPAMcop']['dns'] = 'bl.spamcop.net';
    $filters['SPAMcop']['result'] = '127.0.0.2';
    $filters['SPAMcop']['comment'] =
        _("FREE, for now - SPAMCOP - An interesting solution that lists servers that have a very high spam to legit email ratio (85% or more).");

    $filters['dev.null.dk']['prefname'] = 'filters_spam_devnull';
    $filters['dev.null.dk']['name'] = 'dev.null.dk Lists';
    $filters['dev.null.dk']['link'] = 'http://dev.null.dk/';
    $filters['dev.null.dk']['dns'] = 'dev.null.dk';
    $filters['dev.null.dk']['result'] = '127.0.0.2';
    $filters['dev.null.dk']['comment'] =
        _("FREE - dev.null.dk - I don't have any detailed info on this list.");

    $filters['visi.com']['prefname'] = 'filters_spam_visi';
    $filters['visi.com']['name'] = 'visi.com Relay Stop List';
    $filters['visi.com']['link'] = 'http://relays.visi.com';
    $filters['visi.com']['dns'] = 'relays.visi.com';
    $filters['visi.com']['result'] = '127.0.0.2';
    $filters['visi.com']['comment'] =
        _("FREE - visi.com - Relay Stop List. Very conservative OpenRelay List.");

    $filters['2mbit.com Open Relays']['prefname'] = 'filters_spam_2mb_or';
    $filters['2mbit.com Open Relays']['name'] = '2mbit.com Open Relays List';
    $filters['2mbit.com Open Relays']['link'] = 'http://www.2mbit.com/sbl.php';
    $filters['2mbit.com Open Relays']['dns'] = 'blackholes.2mbit.com';
    $filters['2mbit.com Open Relays']['result'] = '127.0.0.2';
    $filters['2mbit.com Open Relays']['comment'] =
        _("FREE - 2mbit.com Open Relays - Another list of Open Relays.");

    $filters['2mbit.com SPAM Source']['prefname'] = 'filters_spam_2mb_ss';
    $filters['2mbit.com SPAM Source']['name'] = '2mbit.com SPAM Source List';
    $filters['2mbit.com SPAM Source']['link'] = 'http://www.2mbit.com/sbl.php';
    $filters['2mbit.com SPAM Source']['dns'] = 'blackholes.2mbit.com';
    $filters['2mbit.com SPAM Source']['result'] = '127.0.0.4';
    $filters['2mbit.com SPAM Source']['comment'] =
        _("FREE - 2mbit.com SPAM Source - List of Direct SPAM Sources.");

    $filters['2mbit.com SPAM ISPs']['prefname'] = 'filters_spam_2mb_isp';
    $filters['2mbit.com SPAM ISPs']['name'] = '2mbit.com SPAM-friendly ISP List';
    $filters['2mbit.com SPAM ISPs']['link'] = 'http://www.2mbit.com/sbl.php';
    $filters['2mbit.com SPAM ISPs']['dns'] = 'blackholes.2mbit.com';
    $filters['2mbit.com SPAM ISPs']['result'] = '127.0.0.10';
    $filters['2mbit.com SPAM ISPs']['comment'] =
        _("FREE - 2mbit.com SPAM ISPs - List of SPAM-friendly ISPs.");

    $filters['Leadmon DUL']['prefname'] = 'filters_spam_lm_dul';
    $filters['Leadmon DUL']['name'] = 'Leadmon.net DUL List';
    $filters['Leadmon DUL']['link'] = 'http://www.leadmon.net/spamguard/';
    $filters['Leadmon DUL']['dns'] = 'spamguard.leadmon.net';
    $filters['Leadmon DUL']['result'] = '127.0.0.2';
    $filters['Leadmon DUL']['comment'] =
        _("FREE - Leadmon DUL - Another list of Dial-up or otherwise dynamically assigned IPs.");

    $filters['Leadmon SPAM Source']['prefname'] = 'filters_spam_lm_ss';
    $filters['Leadmon SPAM Source']['name'] = 'Leadmon.net SPAM Source List';
    $filters['Leadmon SPAM Source']['link'] = 'http://www.leadmon.net/spamguard/';
    $filters['Leadmon SPAM Source']['dns'] = 'spamguard.leadmon.net';
    $filters['Leadmon SPAM Source']['result'] = '127.0.0.3';
    $filters['Leadmon SPAM Source']['comment'] =
        _("FREE - Leadmon SPAM Source - List of IPs Leadmon.net has received SPAM directly from.");

    $filters['Leadmon Bulk Mailers']['prefname'] = 'filters_spam_lm_bm';
    $filters['Leadmon Bulk Mailers']['name'] = 'Leadmon.net Bulk Mailers List';
    $filters['Leadmon Bulk Mailers']['link'] = 'http://www.leadmon.net/spamguard/';
    $filters['Leadmon Bulk Mailers']['dns'] = 'spamguard.leadmon.net';
    $filters['Leadmon Bulk Mailers']['result'] = '127.0.0.4';
    $filters['Leadmon Bulk Mailers']['comment'] =
        _("FREE - Leadmon Bulk Mailers - Bulk mailers that do not require confirmed opt-in or that have allowed known spammers to become clients and abuse their services.");

    $filters['Leadmon Open Relays']['prefname'] = 'filters_spam_lm_or';
    $filters['Leadmon Open Relays']['name'] = 'Leadmon.net Open Relays List';
    $filters['Leadmon Open Relays']['link'] = 'http://www.leadmon.net/spamguard/';
    $filters['Leadmon Open Relays']['dns'] = 'spamguard.leadmon.net';
    $filters['Leadmon Open Relays']['result'] = '127.0.0.5';
    $filters['Leadmon Open Relays']['comment'] =
        _("FREE - Leadmon Open Relays - Single Stage Open Relays that are not listed on other active RBLs.");

    $filters['Leadmon Multi-stage']['prefname'] = 'filters_spam_lm_ms';
    $filters['Leadmon Multi-stage']['name'] = 'Leadmon.net Multi-Stage Relay List';
    $filters['Leadmon Multi-stage']['link'] = 'http://www.leadmon.net/spamguard/';
    $filters['Leadmon Multi-stage']['dns'] = 'spamguard.leadmon.net';
    $filters['Leadmon Multi-stage']['result'] = '127.0.0.6';
    $filters['Leadmon Multi-stage']['comment'] =
        _("FREE - Leadmon Multi-stage - Multi-Stage Open Relays that are not listed on other active RBLs and that have sent SPAM to Leadmon.net.");

    $filters['Leadmon SpamBlock']['prefname'] = 'filters_spam_lm_sb';
    $filters['Leadmon SpamBlock']['name'] = 'Leadmon.net SpamBlock Sites List';
    $filters['Leadmon SpamBlock']['link'] = 'http://www.leadmon.net/spamguard/';
    $filters['Leadmon SpamBlock']['dns'] = 'spamguard.leadmon.net';
    $filters['Leadmon SpamBlock']['result'] = '127.0.0.7';
    $filters['Leadmon SpamBlock']['comment'] =
        _("FREE - Leadmon SpamBlock - Sites on this listing have sent Leadmon.net direct SPAM from IPs in netblocks where the entire block has no DNS mappings. It's a list of BLOCKS of IPs being used by people who have SPAMmed Leadmon.net.");

    $filters['NJABL Open Relays']['prefname'] = 'filters_spam_njabl_or';
    $filters['NJABL Open Relays']['name'] = 'NJABL Open Relay/Direct Spam Source List';
    $filters['NJABL Open Relays']['link'] = 'http://www.njabl.org/';
    $filters['NJABL Open Relays']['dns'] = 'dnsbl.njabl.org';
    $filters['NJABL Open Relays']['result'] = '127.0.0.2';
    $filters['NJABL Open Relays']['comment'] =
        _("FREE, for now - Not Just Another Blacklist - Both Open Relays and Direct SPAM Sources.");

    $filters['NJABL DUL']['prefname'] = 'filters_spam_njabl_dul';
    $filters['NJABL DUL']['name'] = 'NJABL Dial-ups List';
    $filters['NJABL DUL']['link'] = 'http://www.njabl.org/';
    $filters['NJABL DUL']['dns'] = 'dnsbl.njabl.org';
    $filters['NJABL DUL']['result'] = '127.0.0.3';
    $filters['NJABL DUL']['comment'] =
        _("FREE, for now - Not Just Another Blacklist - Dial-up IPs.");

    foreach ($filters as $Key => $Value) {
        $filters[$Key]['enabled'] = getPref($data_dir, $username,
            $filters[$Key]['prefname']);
    }

    return $filters;
}

function remove_filter ($id) {
    global $data_dir, $username;

    while ($nextFilter = getPref($data_dir, $username, 'filter' .
        ($id + 1))) {
        setPref($data_dir, $username, 'filter' . $id, $nextFilter);
        $id ++;
    }

    removePref($data_dir, $username, 'filter' . $id);
}

function filter_swap($id1, $id2) {
    global $data_dir, $username;

    $FirstFilter = getPref($data_dir, $username, 'filter' . $id1);
    $SecondFilter = getPref($data_dir, $username, 'filter' . $id2);

    if ($FirstFilter && $SecondFilter) {
        setPref($data_dir, $username, 'filter' . $id2, $FirstFilter);
        setPref($data_dir, $username, 'filter' . $id1, $SecondFilter);
    }
}

/* This update the filter rules when
   renaming or deleting folders */
function update_for_folder ($args) {
    $old_folder = $args[0];
        $new_folder = $args[2];
        $action = $args[1];
    global $plugins, $data_dir, $username;
    $filters = array();
    $filters = load_filters();
    $filter_count = count($filters);
    $p = 0;
    for ($i=0;$i<$filter_count;$i++) {
        if (!empty($filters)) {
            if ($old_folder == $filters[$i]['folder']) {
                if ($action == 'rename') {
                    $filters[$i]['folder'] = $new_folder;
                    setPref($data_dir, $username, 'filter'.$i,
                    $filters[$i]['where'].','.$filters[$i]['what'].','.$new_folder);
                }
                elseif ($action == 'delete') {
                    remove_filter($p);
                    $p = $p-1;
                }
            }
        $p++;
        }
    }
}
?>
