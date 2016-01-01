<?php

/**
 * Message and Spam Filter Plugin - Filtering Functions
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage filters
 */

// TODO: This plugin has an issue that seems to corrupt folder cache for subfolders of INBOX
/**
 * do not allow to call this file directly
 */
if (isset($_SERVER['SCRIPT_FILENAME']) && $_SERVER['SCRIPT_FILENAME'] == __FILE__) {
    header("Location: ../../src/login.php");
    die();
}

/** load globals */
global $UseSeparateImapConnection,
    $AllowSpamFilters, $SpamFilters_YourHop, $SpamFilters_ShowCommercial,
    $SpamFilters_DNScache, $SpamFilters_BulkQuery, $SpamFilters_SharedCache,
    $SpamFilters_CacheTTL;

/**
 * load required functions. Plugin depends on IMAP functions and they are not
 * loaded in src/webmail.php
 */
include_once (SM_PATH . 'functions/imap.php');

/** load default config */
if (file_exists(SM_PATH . 'plugins/filters/config_default.php')) {
    include_once (SM_PATH . 'plugins/filters/config_default.php');
} else {
    // default config was removed.
    $UseSeparateImapConnection = false;
    $AllowSpamFilters = true;
    $SpamFilters_YourHop = ' ';
    $SpamFilters_ShowCommercial = false;
    $SpamFilters_DNScache = array();
    $SpamFilters_BulkQuery = '';
    $SpamFilters_SharedCache = true;
    $SpamFilters_CacheTTL = 7200;
}

if (file_exists(SM_PATH . 'config/filters_config.php')) {
    include_once (SM_PATH . 'config/filters_config.php');
} elseif (file_exists(SM_PATH . 'plugins/filters/config.php')) {
    include_once (SM_PATH . 'plugins/filters/config.php');
}

/**
 * Register option blocks
 * @access private
 */
function filters_optpage_register_block() {
    global $optpage_blocks, $AllowSpamFilters;

    $optpage_blocks[] = array(
        'name' => _("Message Filters"),
        'url'  => SM_PATH . 'plugins/filters/options.php',
        'desc' => _("Filtering enables messages with different criteria to be automatically filtered into different folders for easier organization."),
        'js'   => false
    );

    if ($AllowSpamFilters) {
        $optpage_blocks[] = array(
            'name' => _("SPAM Filters"),
            'url'  => SM_PATH . 'plugins/filters/spamoptions.php',
            'desc' => _("SPAM filters allow you to select from various DNS based blacklists to detect junk email in your INBOX and move it to another folder (like Trash)."),
            'js'   => false
        );
    }
}

/* Receive the status of the folder and do something with it */
function filters_folder_status($statusarr) {

    global $filter_inbox_count;
    if (empty($filter_inbox_count)) $filter_inbox_count=0;

    if ($statusarr['MAILBOX'] == 'INBOX')
    {
     if (!empty($statusarr['MESSAGES'])) $filter_inbox_count=$statusarr['MESSAGES'];
    }
}

/**
 * Saves the DNS Cache to disk
 * @access private
 */
function filters_SaveCache () {
    global $data_dir, $SpamFilters_DNScache;

    if (file_exists($data_dir . '/dnscache')) {
        $fp = fopen($data_dir . '/dnscache', 'r');
    } else {
        $fp = false;
    }
    if ($fp) {
        flock($fp,LOCK_EX);
    } else {
       $fp = fopen($data_dir . '/dnscache', 'w+');
       fclose($fp);
       $fp = fopen($data_dir . '/dnscache', 'r');
       flock($fp,LOCK_EX);
    }
    $fp1 = fopen($data_dir . '/dnscache', 'w+');

    foreach ($SpamFilters_DNScache as $Key=> $Value) {
       $tstr = $Key . ',' . $Value['L'] . ',' . $Value['T'] . "\n";
       fputs ($fp1, $tstr);
    }
    fclose($fp1);
    flock($fp,LOCK_UN);
    fclose($fp);
}

/**
 * Loads the DNS Cache from disk
 * @access private
 */
function filters_LoadCache () {
    global $data_dir, $SpamFilters_DNScache;

    if (file_exists($data_dir . '/dnscache')) {
        $SpamFilters_DNScache = array();
        if ($fp = fopen ($data_dir . '/dnscache', 'r')) {
            flock($fp,LOCK_SH);
            while ($data = fgetcsv($fp,1024)) {
               if ($data[2] > time()) {
                  $SpamFilters_DNScache[$data[0]]['L'] = $data[1];
                  $SpamFilters_DNScache[$data[0]]['T'] = $data[2];
               }
            }
            flock($fp,LOCK_UN);
        }
    }
}

/**
 * Uses the BulkQuery executable to query all the RBLs at once
 * @param array $filters Array of SPAM Filters
 * @param array $IPs Array of IP Addresses
 * @access private
 */
function filters_bulkquery($filters, $IPs) {
    global $attachment_dir, $username,
           $SpamFilters_DNScache, $SpamFilters_BulkQuery,
           $SpamFilters_CacheTTL;

    if (count($IPs) > 0) {
        $rbls = array();
        foreach ($filters as $key => $value) {
            if ($filters[$key]['enabled']) {
                if ($filters[$key]['dns']) {
                    $rbls[$filters[$key]['dns']] = true;
                }
            }
        }

        $bqfil = $attachment_dir . $username . '-bq.in';
        $fp = fopen($bqfil, 'w');
        fputs ($fp, $SpamFilters_CacheTTL . "\n");
        foreach ($rbls as $key => $value) {
            fputs ($fp, '.' . $key . "\n");
        }
        fputs ($fp, "----------\n");
        foreach ($IPs as $key => $value) {
            fputs ($fp, $key . "\n");
        }
        fclose ($fp);
        $bqout = array();
        exec ($SpamFilters_BulkQuery . ' < ' . $bqfil, $bqout);
        foreach ($bqout as $value) {
            $Chunks = explode(',', $value);
            $SpamFilters_DNScache[$Chunks[0]]['L'] = $Chunks[1];
            $SpamFilters_DNScache[$Chunks[0]]['T'] = $Chunks[2] + time();
        }
        unlink($bqfil);
    }
}

/**
 * Starts the filtering process
 * @param array $hook_args (since 1.5.2) do hook arguments. Is used to check
 * hook name, array key = 0.
 * @access private
 */
function start_filters($hook_args) {
    global $imapServerAddress, $imapPort, $imap_stream_options, $imap_stream,
           $imapConnection, $UseSeparateImapConnection, $AllowSpamFilters,
           $filter_inbox_count, $username;

    // if there were filtering errors previously during
    // this login session, we won't try again
    //
    // (errors that this plugin was able to catch or a "NO"
    // response/failure from IMAP found in the current session,
    // which could have resulted from an attempted filter copy
    // (over quota), in which case execution halts before this
    // plugin can catch the problem -- note, however, that any
    // other IMAP "NO" failure (caused by unrelated actions) at
    // any time during the current session will cause this plugin
    // to effectively shut down)
    //
    sqgetGlobalVar('filters_error', $filters_error, SQ_SESSION, FALSE);
    sqgetGlobalVar('IMAP_FATAL_ERROR_TYPE', $imap_fatal_error, SQ_SESSION, '');
    if ($filters_error || $imap_fatal_error == 'NO')
        return;

    /**
     * check hook that calls filtering. If filters are called by right_main_after_header,
     * do filtering only when we are in INBOX folder.
     */
    if ($hook_args[0]=='right_main_after_header' &&
        (sqgetGlobalVar('mailbox',$mailbox,SQ_FORM) && $mailbox!='INBOX')) {
        return;
    }

    $filters = load_filters();

    // No point running spam filters if there aren't any to run //
    if ($AllowSpamFilters) {
        $spamfilters = load_spam_filters();

        $AllowSpamFilters = false;
        foreach($spamfilters as $value) {
            if ($value['enabled'] == SMPREF_ON) {
                $AllowSpamFilters = true;
                break;
            }
        }
    }

    // No user filters, and no spam filters, no need to continue //
    if (!$AllowSpamFilters && empty($filters)) {
        return;
    }


    // Detect if we have already connected to IMAP or not.
    // Also check if we are forced to use a separate IMAP connection
    if ((!isset($imap_stream) && !isset($imapConnection)) ||
        $UseSeparateImapConnection ) {
            $stream = sqimap_login($username, false, $imapServerAddress,
                                $imapPort, 10, $imap_stream_options);
            $previously_connected = false;
    } else if (isset($imapConnection)) {
        $stream = $imapConnection;
        $previously_connected = true;
    } else {
        $previously_connected = true;
        $stream = $imap_stream;
    }

    if (!isset($filter_inbox_count)) {
        $aStatus = sqimap_status_messages ($stream, 'INBOX', array('MESSAGES'));
        if (!empty($aStatus['MESSAGES'])) {
            $filter_inbox_count=$aStatus['MESSAGES'];
        } else {
            $filter_inbox_count=0;
        }
    }

    if ($filter_inbox_count > 0) {
        sqimap_mailbox_select($stream, 'INBOX');
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
}

/**
 * Does the loop through each filter
 * @param stream imap_stream the stream to read from
 * @access private
 */
function user_filters($imap_stream) {
    global $data_dir, $username;
    $filters = load_filters();
    if (! $filters) return;
    $filters_user_scan = getPref($data_dir, $username, 'filters_user_scan');

    $expunge = false;
    // For every rule
    for ($i=0, $num = count($filters); $i < $num; $i++) {
        // If it is the "combo" rule
        if ($filters[$i]['where'] == 'To or Cc') {
            /*
            *  If it's "TO OR CC", we have to do two searches, one for TO
            *  and the other for CC.
            */
            $expunge = filter_search_and_delete($imap_stream, 'TO',
                  $filters[$i]['what'], $filters[$i]['folder'], $filters_user_scan, $expunge);
            $expunge = filter_search_and_delete($imap_stream, 'CC',
                  $filters[$i]['what'], $filters[$i]['folder'], $filters_user_scan, $expunge);
        } else if ($filters[$i]['where'] == 'Header and Body') {
            $expunge = filter_search_and_delete($imap_stream, 'TEXT',
                  $filters[$i]['what'], $filters[$i]['folder'], $filters_user_scan, $expunge);
        } else if ($filters[$i]['where'] == 'Message Body') {
            $expunge = filter_search_and_delete($imap_stream, 'BODY',
                  $filters[$i]['what'], $filters[$i]['folder'], $filters_user_scan, $expunge);
        } else {
            /*
            *  If it's a normal TO, CC, SUBJECT, or FROM, then handle it
            *  normally.
            */
            $expunge = filter_search_and_delete($imap_stream, $filters[$i]['where'],
                 $filters[$i]['what'], $filters[$i]['folder'], $filters_user_scan, $expunge);
        }
    }
    // Clean out the mailbox whether or not auto_expunge is on
    // That way it looks like it was redirected properly
    if ($expunge) {
        sqimap_mailbox_expunge($imap_stream, 'INBOX');
    }
}

/**
 * Creates and runs the IMAP command to filter messages
 * @param string $imap_stream TODO: Document this parameter
 * @param string $where Which part of the message to search (TO, CC, SUBJECT, etc...)
 * @param string $what String to search for
 * @param string $where_to Folder it will move to
 * @param string $user_scan Whether to search all or just unseen
 * @param string $should_expunge
 * @access private
 */
function filter_search_and_delete($imap_stream, $where, $what, $where_to, $user_scan,
                                  $should_expunge) {
    global $languages, $squirrelmail_language, $allow_charset_search, $imap_server_type;

    //TODO: make use of new mailbox cache. See mailbox_display.phpinfo

    if (strtolower($where_to) == 'inbox') {
        return array();
    }

    if ($user_scan == 'new') {
        $category = 'UNSEEN';
    } else {
        $category = 'ALL';
    }
    $category .= ' UNDELETED';

    if ($allow_charset_search &&
        isset($languages[$squirrelmail_language]['CHARSET']) &&
        $languages[$squirrelmail_language]['CHARSET']) {
        $search_str = 'SEARCH CHARSET '
                    . strtoupper($languages[$squirrelmail_language]['CHARSET'])
                    . ' ' . $category;
    } else {
        $search_str = 'SEARCH CHARSET US-ASCII ' . $category;
    }
    if ($where == 'Header') {
        $what  = explode(':', $what);
        $where = strtoupper($where);
        $where = trim($where . ' ' . $what[0]);
        $what  = addslashes(trim($what[1]));
    }

    // see comments in squirrelmail sqimap_search function
    if ($imap_server_type == 'macosx' || $imap_server_type == 'hmailserver') {
        $search_str .= ' ' . $where . ' ' . $what;
        /* read data back from IMAP */
        $read = sqimap_run_command($imap_stream, $search_str, true, $response, $message, TRUE);
    } else {
        $search_str .= ' ' . $where . ' {' . strlen($what) . "}";
        $sid = sqimap_session_id(true);
        fputs ($imap_stream, $sid . ' ' . $search_str . "\r\n");
        $read2 = sqimap_fgets($imap_stream);
        # server should respond with Ready for argument, then we will send search text
        #echo "RR2 $read2<br>";
        fputs ($imap_stream, "$what\r\n");
        #echo "SS $what<br>";
        $read2 = sqimap_fgets($imap_stream);
        #echo "RR2 $read2<br>";
        $read[]=$read2;
        $read3 = sqimap_fgets($imap_stream);
        #echo "RR3 $read3<br>";
        list($rtag,$response,$message)=explode(' ',$read3,3);
##        $read2 = sqimap_retrieve_imap_response($imap_stream, $sid, true,
##              $response, $message, $search_str, false, true, false);
        #echo "RR2 $read2 / RESPONSE $response<br>";
    }

    if (isset($read[0])) {
        $ids = array();
        for ($i = 0, $iCnt = count($read); $i < $iCnt; ++$i) {
            if (preg_match("/^\* SEARCH (.+)$/", $read[$i], $regs)) {
                $ids += explode(' ', trim($regs[1]));
            }
        }
        if ($response == 'OK' && count($ids)) {
            if (sqimap_mailbox_exists($imap_stream, $where_to)) {
                if (!sqimap_msgs_list_move ($imap_stream, $ids, $where_to, false)) {
                    // if errors occurred, don't try to filter again during this session
                    sqsession_register(TRUE, 'filters_error');
                    global $color;
                    error_box(_("A problem occurred filtering messages. Check filter settings and account quota if applicable. Filtering is disabled for the remainder of this login session."), $color);
                }

                // expunge even in the case of errors, in case some
                // messages were filtered before the error happened
                $should_expunge = true;
            }
        } elseif ($response != 'OK') {
            $query = $search_str . "\r\n".$what ."\r\n";
            if ($response == 'NO') {
                if (strpos($message,'BADCHARSET') !== false ||
                    strpos($message,'character') !== false) {
                    sqm_trigger_imap_error('SQM_IMAP_BADCHARSET',$query, $response, $message);
                } else {
                    sqm_trigger_imap_error('SQM_IMAP_ERROR',$query, $response, $message);
                }
            } else {
                sqm_trigger_imap_error('SQM_IMAP_ERROR',$query, $response, $message);
            }
        }
    }
    return $should_expunge;
}

/**
 * Loops through all the Received Headers to find IP Addresses
 * @param stream imap_stream the stream to read from
 * @access private
 */
function spam_filters($imap_stream) {
    global $data_dir, $username;
    global $SpamFilters_YourHop;
    global $SpamFilters_DNScache;
    global $SpamFilters_SharedCache;
    global $SpamFilters_BulkQuery;
    global $SpamFilters_CacheTTL;

    $filters_spam_scan = getPref($data_dir, $username, 'filters_spam_scan');
    $filters_spam_folder = getPref($data_dir, $username, 'filters_spam_folder');
    $filters = load_spam_filters();

    if ($SpamFilters_SharedCache) {
       filters_LoadCache();
    }

    $run = false;

    foreach ($filters as $Value) {
        if ($Value['enabled']) {
            $run = true;
            break;
        }
    }

    // short-circuit
    if (!$run) {
        return;
    }

    // Ask for a big list of all "Received" headers in the inbox with
    // flags for each message.  Kinda big.

    if ($filters_spam_scan == 'new') {
        $search_array = array();
        $read = sqimap_run_command($imap_stream, 'SEARCH UNSEEN', true, $response, $message, TRUE);
        if (isset($read[0])) {
            for ($i = 0, $iCnt = count($read); $i < $iCnt; ++$i) {
                if (preg_match("/^\* SEARCH (.+)$/", $read[$i], $regs)) {
                    $search_array = explode(' ', trim($regs[1]));
                    break;
                }
            }
        }
    }
    if ($filters_spam_scan == 'new' && count($search_array)) {
        $headers = sqimap_get_small_header_list ($imap_stream, $search_array, array('Received'),array());
    } else if ($filters_spam_scan != 'new') {
        $headers = sqimap_get_small_header_list ($imap_stream, null , array('Received'),array());
    } else {
        return;
    }
    if (!count($headers)) {
        return;
    }
    $bulkquery = (strlen($SpamFilters_BulkQuery) > 0 ? true : false);
    $IPs = array();
    $aSpamIds = array();
    foreach ($headers as $id => $aValue) {
        if (isset($aValue['UID'])) {
            $MsgNum = $aValue['UID'];
        } else {
            $MsgNum = $id;
        }
        // Look through all of the Received headers for IP addresses
        if (isset($aValue['RECEIVED'])) {
            foreach ($aValue['RECEIVED'] as $received) {
                // Check to see if this line is the right "Received from" line
                // to check

                // $aValue['Received'] is an array with all the received lines.
                // We should check them from bottom to top and only check the first 2.
                // Currently we check only the header where $SpamFilters_YourHop in occures

                if (is_int(strpos($received, $SpamFilters_YourHop))) {
                    if (preg_match('/([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})/',$received,$aMatch)) {
                        $isspam = false;
                        if (filters_spam_check_site($aMatch[1],$aMatch[2],$aMatch[3],$aMatch[4],$filters)) {
                            $aSpamIds[] = $MsgNum;
                            $isspam = true;
                        }

                        if ($bulkquery) {
                            array_shift($aMatch);
                            $IP = explode('.', $aMatch);
                            foreach ($filters as $key => $value) {
                                if ($filters[$key]['enabled'] && $filters[$key]['dns']) {
                                    if (strlen($SpamFilters_DNScache[$IP.'.'.$filters[$key]['dns']]) == 0) {
                                        $IPs[$IP] = true;
                                        break;
                                    }
                                }
                            }
                        }
                        // If we've checked one IP and YourHop is
                        // just a space
                        if ($SpamFilters_YourHop == ' ' || $isspam) {
                            break;  // don't check any more
                        }
                    }
                }
            }
        }
    }
    // Lookie!  It's spam!  Yum!
    if (count($aSpamIds) && sqimap_mailbox_exists($imap_stream, $filters_spam_folder)) {
        if (!sqimap_msgs_list_move($imap_stream, $aSpamIds, $filters_spam_folder)) {
           // if errors occurred, don't try to filter again during this session
           sqsession_register(TRUE, 'filters_error');
           global $color;
           error_box(_("A problem occurred filtering messages. Check filter settings and account quota if applicable. Filtering is disabled for the remainder of this login session."), $color);
        }

        // expunge even in the case of errors, in case some
        // messages were filtered before the error happened
        sqimap_mailbox_expunge($imap_stream, 'INBOX');
    }

    if ($bulkquery && count($IPs)) {
        filters_bulkquery($filters, $IPs);
    }

    if ($SpamFilters_SharedCache) {
       filters_SaveCache();
    } else {
       sqsession_register($SpamFilters_DNScache, 'SpamFilters_DNScache');
    }
}

/**
 * Does the loop through each enabled filter for the specified IP address.
 * IP format:  $a.$b.$c.$d
 * @param int $a First subset of IP
 * @param int $b Second subset of IP
 * @param int $c Third subset of IP
 * @param int $d Forth subset of IP
 * @param array $filters The Spam Filters
 * @return boolean Whether the IP is Spam
 * @access private
 */
function filters_spam_check_site($a, $b, $c, $d, &$filters) {
    global $SpamFilters_DNScache, $SpamFilters_CacheTTL;
    foreach ($filters as $key => $value) {
        if ($filters[$key]['enabled']) {
            if ($filters[$key]['dns']) {

                /**
                 * RFC allows . on end of hostname to force domain lookup to
                 * not use search domain from resolv.conf, i.e. to ensure
                 * search domain isn't used if no hostname is found
                 */
                $filter_revip = $d . '.' . $c . '.' . $b . '.' . $a . '.' .
                                $filters[$key]['dns'] . '.';

                if(!isset($SpamFilters_DNScache[$filter_revip]['L']))
                        $SpamFilters_DNScache[$filter_revip]['L'] = '';

                if(!isset($SpamFilters_DNScache[$filter_revip]['T']))
                        $SpamFilters_DNScache[$filter_revip]['T'] = '';

                if (strlen($SpamFilters_DNScache[$filter_revip]['L']) == 0) {
                    $SpamFilters_DNScache[$filter_revip]['L'] =
                                    gethostbyname($filter_revip);
                    $SpamFilters_DNScache[$filter_revip]['T'] =
                                       time() + $SpamFilters_CacheTTL;
                }

                /**
                 * gethostbyname returns ip if resolved, or returns original
                 * host supplied to function if there is no resolution
                 */
                if ($SpamFilters_DNScache[$filter_revip]['L'] != $filter_revip) {
                    return 1;
                }
            }
        }
    }
    return 0;
}

/**
 * Loads the filters from the user preferences
 * @return array All the user filters
 * @access private
 */
function load_filters() {
    global $data_dir, $username;

    $filters = array();
    for ($i = 0; $fltr = getPref($data_dir, $username, 'filter' . $i); $i++) {
        $ary = explode(',', $fltr);
        $filters[$i]['where'] = $ary[0];
        $filters[$i]['what'] = str_replace('###COMMA###', ',', $ary[1]);
        $filters[$i]['folder'] = $ary[2];
    }
    return $filters;
}

/**
 * Loads the Spam Filters and checks the preferences for the enabled status
 * @return array All the spam filters
 * @access private
 */
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
            _("COMMERCIAL - Servers that are configured (or misconfigured) to allow spam to be relayed through their system will be banned with this. Another good one to use.");

        $filters['MAPS DUL']['prefname'] = 'filters_spam_maps_dul';
        $filters['MAPS DUL']['name'] = 'MAPS Dial-Up List';
        $filters['MAPS DUL']['link'] = 'http://www.mail-abuse.org/dul/';
        $filters['MAPS DUL']['dns'] = 'dialups.mail-abuse.org';
        $filters['MAPS DUL']['result'] = '127.0.0.3';
        $filters['MAPS DUL']['comment'] =
            _("COMMERCIAL - Dial-up users are often filtered out since they should use their ISP's mail servers to send mail. Spammers typically get a dial-up account and send spam directly from there.");

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
    $filters['SPAMhaus']['result'] = '127.0.0.2';
    $filters['SPAMhaus']['comment'] =
        _("FREE - SPAMhaus - A list of well-known SPAM sources.");

    $filters['SPAMcop']['prefname'] = 'filters_spam_spamcop';
    $filters['SPAMcop']['name'] = 'SPAM Cop Lists';
    $filters['SPAMcop']['link'] = 'http://spamcop.net/bl.shtml';
    $filters['SPAMcop']['dns'] = 'bl.spamcop.net';
    $filters['SPAMcop']['result'] = '127.0.0.2';
    $filters['SPAMcop']['comment'] =
        _("FREE, for now - SpamCop - An interesting solution that lists servers that have a very high spam to legit email ratio (85 percent or more).");

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

    $filters['ahbl.org Open Relays']['prefname'] = 'filters_spam_2mb_or';
    $filters['ahbl.org Open Relays']['name'] = 'ahbl.org Open Relays List';
    $filters['ahbl.org Open Relays']['link'] = 'http://www.ahbl.org/';
    $filters['ahbl.org Open Relays']['dns'] = 'dnsbl.ahbl.org';
    $filters['ahbl.org Open Relays']['result'] = '127.0.0.2';
    $filters['ahbl.org Open Relays']['comment'] =
        _("FREE - ahbl.org Open Relays - Another list of Open Relays.");

    $filters['ahbl.org SPAM Source']['prefname'] = 'filters_spam_2mb_ss';
    $filters['ahbl.org SPAM Source']['name'] = 'ahbl.org SPAM Source List';
    $filters['ahbl.org SPAM Source']['link'] = 'http://www.ahbl.org/';
    $filters['ahbl.org SPAM Source']['dns'] = 'dnsbl.ahbl.org';
    $filters['ahbl.org SPAM Source']['result'] = '127.0.0.4';
    $filters['ahbl.org SPAM Source']['comment'] =
        _("FREE - ahbl.org SPAM Source - List of Direct SPAM Sources.");

    $filters['ahbl.org SPAM ISPs']['prefname'] = 'filters_spam_2mb_isp';
    $filters['ahbl.org SPAM ISPs']['name'] = 'ahbl.org SPAM-friendly ISP List';
    $filters['ahbl.org SPAM ISPs']['link'] = 'http://www.ahbl.org/';
    $filters['ahbl.org SPAM ISPs']['dns'] = 'dnsbl.ahbl.org';
    $filters['ahbl.org SPAM ISPs']['result'] = '127.0.0.7';
    $filters['ahbl.org SPAM ISPs']['comment'] =
        _("FREE - ahbl.org SPAM ISPs - List of SPAM-friendly ISPs.");

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
        $filters[$Key]['enabled'] = (bool)getPref($data_dir, $username, $filters[$Key]['prefname']);
    }

    return $filters;
}

/**
 * Removes a User filter
 * @param int $id ID of the filter to remove
 * @access private
 */
function remove_filter ($id) {
    global $data_dir, $username;

    while ($nextFilter = getPref($data_dir, $username, 'filter' . ($id + 1))) {
        setPref($data_dir, $username, 'filter' . $id, $nextFilter);
        $id ++;
    }

    removePref($data_dir, $username, 'filter' . $id);
}

/**
 * Swaps two filters
 * @param int $id1 ID of first filter to swap
 * @param int $id2 ID of second filter to swap
 * @access private
 */
function filter_swap($id1, $id2) {
    global $data_dir, $username;

    $FirstFilter = getPref($data_dir, $username, 'filter' . $id1);
    $SecondFilter = getPref($data_dir, $username, 'filter' . $id2);

    if ($FirstFilter && $SecondFilter) {
        setPref($data_dir, $username, 'filter' . $id2, $FirstFilter);
        setPref($data_dir, $username, 'filter' . $id1, $SecondFilter);
    }
}

/**
 * This updates the filter rules when renaming or deleting folders
 * @param array $args
 * @access private
 */
function update_for_folder ($args) {
    $old_folder = $args[0];
    $new_folder = $args[2];
    $action = $args[1];
    global $data_dir, $username;
    $filters = array();
    $filters = load_filters();
    $filter_count = count($filters);
    $p = 0;
    for ($i = 0; $i < $filter_count; $i++) {
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

/**
 * Display formated error message
 * @param string $string text message
 * @return string html formated text message
 * @access private
 */
function do_error($string) {
    global $color;
    echo "<p align=\"center\"><font color=\"$color[2]\">";
    echo $string;
    echo "</font></p>\n";
}
