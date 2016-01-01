<?php

/**
 * imap_mailbox.php
 *
 * This implements all functions that manipulate mailboxes
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage imap
 */

/** UTF7 support */
require_once(SM_PATH . 'functions/imap_utf7_local.php');


/**
 * Mailboxes class
 *
 * FIXME. This class should be extracted and placed in a separate file that
 * can be included before we start the session. That makes caching of the tree
 * possible. On a refresh mailboxes from left_main.php the only function that
 * should be called is the sqimap_get_status_mbx_tree. In case of subscribe
 * / rename / delete / new we have to create methods for adding/changing the
 * mailbox in the mbx_tree without the need for a refresh.
 *
 * Some code fragments are present in 1.3.0 - 1.4.4.
 * @package squirrelmail
 * @subpackage imap
 * @since 1.5.0
 */
class mailboxes {
    var $mailboxname_full = '', $mailboxname_sub= '', $is_noselect = false, $is_noinferiors = false,
        $is_special = false, $is_root = false, $is_inbox = false, $is_sent = false,
        $is_trash = false, $is_draft = false,  $mbxs = array(),
        $unseen = false, $total = false, $recent = false;

    function addMbx($mbx, $delimiter, $start, $specialfirst) {
        $ary = explode($delimiter, $mbx->mailboxname_full);
        $mbx_parent =& $this;
        for ($i = $start, $c = count($ary)-1; $i < $c; $i++) {
            $mbx_childs =& $mbx_parent->mbxs;
            $found = false;
            if ($mbx_childs) {
                foreach ($mbx_childs as $key => $parent) {
                    if ($parent->mailboxname_sub == $ary[$i]) {
                        $mbx_parent =& $mbx_parent->mbxs[$key];
                        $found = true;
                        break;
                    }
                }
            }
            if (!$found) {
                $no_select_mbx = new mailboxes();
                if (isset($mbx_parent->mailboxname_full) && $mbx_parent->mailboxname_full != '') {
                    $no_select_mbx->mailboxname_full = $mbx_parent->mailboxname_full.$delimiter.$ary[$i];
                } else {
                    $no_select_mbx->mailboxname_full = $ary[$i];
                }
                $no_select_mbx->mailboxname_sub = $ary[$i];
                $no_select_mbx->is_noselect = true;
                $mbx_parent->mbxs[] = $no_select_mbx;
                $i--;
            }
        }
        $mbx_parent->mbxs[] = $mbx;
        if ($mbx->is_special && $specialfirst) {
            usort($mbx_parent->mbxs, 'sortSpecialMbx');
        }
    }
}

/**
 * array callback used for sorting in mailboxes class
 * @param object $a
 * @param object $b
 * @return integer see php strnatcasecmp()
 * @since 1.3.0
 */
function sortSpecialMbx($a, $b) {
    if ($a->is_inbox) {
        $acmp = '0'. $a->mailboxname_full;
    } else if ($a->is_special) {
        $acmp = '1'. $a->mailboxname_full;
    } else {
        $acmp = '2' . $a->mailboxname_full;
    }
    if ($b->is_inbox) {
        $bcmp = '0'. $b->mailboxname_full;
    }else if ($b->is_special) {
        $bcmp = '1' . $b->mailboxname_full;
    } else {
        $bcmp = '2' . $b->mailboxname_full;
    }
    return strnatcasecmp($acmp, $bcmp);
}

/**
 * @param array $ary
 * @return array
 * @since 1.5.0
 */
function compact_mailboxes_response($ary) {
    /*
     * Workaround for mailboxes returned as literal
     * FIXME : Doesn't work if the mailbox name is multiple lines
     * (larger then fgets buffer)
     */
    for ($i = 0, $iCnt=count($ary); $i < $iCnt; $i++) {
        if (isset($ary[$i + 1]) && substr($ary[$i], -3) == "}\r\n") {
            if (preg_match('/^(\* [A-Z]+.*)\{[0-9]+\}([ \n\r\t]*)$/', $ary[$i], $regs)) {
                $ary[$i] = $regs[1] . '"' . addslashes(trim($ary[$i+1])) . '"' . $regs[2];
                array_splice($ary, $i+1, 2);
            }
        }
    }
    /* remove duplicates and ensure array is contiguous */
    return array_values(array_unique($ary));
}

/**
 * Extract the mailbox name from an untagged LIST (7.2.2) or LSUB (7.2.3) answer
 * (LIST|LSUB) (<Flags list>) (NIL|"<separator atom>") <mailbox name string>\r\n
 * mailbox name in quoted string MUST be unquoted and stripslashed (sm API)
 *
 * Originally stored in functions/strings.php. Since 1.2.6 stored in
 * functions/imap_mailbox.php
 * @param string $line imap LIST/LSUB response line
 * @return string mailbox name
 */
function find_mailbox_name($line) {
    if (preg_match('/^\* (?:LIST|LSUB) \([^\)]*\) (?:NIL|\"[^\"]*\") ([^\r\n]*)[\r\n]*$/i', $line, $regs)) {
        if (substr($regs[1], 0, 1) == '"')
            return stripslashes(substr($regs[1], 1, -1));
        return $regs[1];
    }
    return '';
}

/**
 * Detects if mailbox has noselect flag (can't store messages)
 * In versions older than 1.4.5 function checks only LSUB responses
 * and can produce pcre warnings.
 * @param string $lsub_line mailbox line from untagged LIST or LSUB response
 * @return bool whether this is a Noselect mailbox.
 * @since 1.3.2
 */
function check_is_noselect ($lsub_line) {
    return preg_match("/^\* (LSUB|LIST) \([^\)]*\\\\Noselect[^\)]*\)/i", $lsub_line);
}

/**
 * Detects if mailbox has noinferiors flag (can't store subfolders)
 * @param string $lsub_line mailbox line from untagged LIST or LSUB response
 * @return bool whether this is a Noinferiors mailbox.
 * @since 1.5.0
 */
function check_is_noinferiors ($lsub_line) {
    return preg_match("/^\* (LSUB|LIST) \([^\)]*\\\\Noinferiors[^\)]*\)/i", $lsub_line);
}

/**
 * Detects mailbox's parent folder
 *
 * If $haystack is a full mailbox name, and $needle is the mailbox
 * separator character, returns the second last part of the full
 * mailbox name (i.e. the mailbox's parent mailbox)
 *
 * Originally stored in functions/strings.php. Since 1.2.6 stored in
 * functions/imap_mailbox.php
 * @param string $haystack full mailbox name
 * @param string $needle delimiter
 * @return string parent mailbox
 */
function readMailboxParent($haystack, $needle) {
    if ($needle == '') {
        $ret = '';
    } else {
        $parts = explode($needle, $haystack);
        $elem = array_pop($parts);
        while ($elem == '' && count($parts)) {
            $elem = array_pop($parts);
        }
        $ret = join($needle, $parts);
    }
    return( $ret );
}

/**
 * Check if $subbox is below the specified $parentbox
 * @param string $subbox potential sub folder
 * @param string $parentbox potential parent
 * @return boolean
 * @since 1.2.3
 */
function isBoxBelow( $subbox, $parentbox ) {
    global $delimiter;
    /*
     * Eliminate the obvious mismatch, where the
     * subfolder path is shorter than that of the potential parent
     */
    if ( strlen($subbox) < strlen($parentbox) ) {
      return false;
    }
    /* check for delimiter */
    if (substr($parentbox,-1) != $delimiter) {
        $parentbox .= $delimiter;
    }

    return (substr($subbox,0,strlen($parentbox)) == $parentbox);
}

/**
 * Defines special mailboxes: given a mailbox name, it checks if this is a
 * "special" one: INBOX, Trash, Sent or Draft.
 *
 * Since 1.2.5 function includes special_mailbox hook.
 *
 * Since 1.4.3 hook supports more than one plugin.
 *
//FIXME: make $subfolders_of_inbox_are_special a configuration setting in conf.pl and config.php
 * Since 1.4.22/1.5.2, the administrator can add
 * $subfolders_of_inbox_are_special = TRUE;
 * to config/config_local.php and all subfolders
 * of the INBOX will be treated as special.
 *
 * @param string $box mailbox name
 * @param boolean $include_subs (since 1.5.2) if true, subfolders of system 
 *  folders are special. if false, subfolders are not special mailboxes 
 *  unless they are tagged as special in 'special_mailbox' hook.
 * @return boolean
 * @since 1.2.3
 */
function isSpecialMailbox($box,$include_subs=true) {
    global $subfolders_of_inbox_are_special;
    $ret = ( ($subfolders_of_inbox_are_special && isInboxMailbox($box,$include_subs)) ||
             (!$subfolders_of_inbox_are_special && strtolower($box) == 'inbox') ||
             isTrashMailbox($box,$include_subs) || 
             isSentMailbox($box,$include_subs) || 
             isDraftMailbox($box,$include_subs) );

    if ( !$ret ) {
        $ret = boolean_hook_function('special_mailbox', $box, 1);
    }
    return $ret;
}

/**
 * Detects if mailbox is the Inbox folder or subfolder of the Inbox
 *
 * @param string $box The mailbox name to test
 * @param boolean $include_subs If true, subfolders of system folders
 *                              are special.  If false, subfolders are
 *                              not special mailboxes.
 *
 * @return boolean Whether this is the Inbox or a child thereof.
 *
 * @since 1.4.22
 */
function isInboxMailbox($box, $include_subs=TRUE) {
   return ((strtolower($box) == 'inbox')
        || ($include_subs && isBoxBelow(strtolower($box), 'inbox')));
}

/**
 * Detects if mailbox is a Trash folder or subfolder of Trash
 * @param string $box mailbox name
 * @param boolean $include_subs (since 1.5.2) if true, subfolders of system 
 *  folders are special. if false, subfolders are not special mailboxes.
 * @return bool whether this is a Trash folder
 * @since 1.4.0
 */
function isTrashMailbox ($box,$include_subs=true) {
    global $trash_folder, $move_to_trash;
    return $move_to_trash && $trash_folder &&
           ( $box == $trash_folder || 
             ($include_subs && isBoxBelow($box, $trash_folder)) );
}

/**
 * Detects if mailbox is a Sent folder or subfolder of Sent
 * @param string $box mailbox name
 * @param boolean $include_subs (since 1.5.2) if true, subfolders of system 
 *  folders are special. if false, subfolders are not special mailboxes.
 * @return bool whether this is a Sent folder
 * @since 1.4.0
 */
function isSentMailbox($box,$include_subs=true) {
   global $sent_folder, $move_to_sent;
   return $move_to_sent && $sent_folder &&
          ( $box == $sent_folder || 
            ($include_subs && isBoxBelow($box, $sent_folder)) );
}

/**
 * Detects if mailbox is a Drafts folder or subfolder of Drafts
 * @param string $box mailbox name
 * @param boolean $include_subs (since 1.5.2) if true, subfolders of system 
 *  folders are special. if false, subfolders are not special mailboxes.
 * @return bool whether this is a Draft folder
 * @since 1.4.0
 */
function isDraftMailbox($box,$include_subs=true) {
   global $draft_folder, $save_as_draft;
   return $save_as_draft &&
          ( $box == $draft_folder || 
            ($include_subs && isBoxBelow($box, $draft_folder)) );
}

/**
 * Is the given folder "sent-like" in nature?
 *
 * The most obvious use of this is to know what folders you usually
 * want to show the To field instead of the From field on the mailbox list
 *
 * This function returns TRUE if the given folder is the sent
 * folder (or any of its subfolders) or if it is the draft
 * folder (or any of its subfolders)
 *
 * @param string $mailbox
 *
 * @return boolean See explanation above
 *
 */
function handleAsSent($mailbox) {
    global $handleAsSent_result;

    /* First check if this is the sent or draft folder. */
    $handleAsSent_result = isSentMailbox($mailbox) || isDraftMailbox($mailbox);

    /* Then check the result of the handleAsSent hook. */
    do_hook('check_handleAsSent_result', $mailbox);

    /* And return the result. */
    return $handleAsSent_result;
}

/**
 * Expunges a mailbox
 *
 * WARNING: Select mailbox before calling this function.
 *
 * permanently removes all messages that have the \Deleted flag
 * set from the selected mailbox. See EXPUNGE command chapter in
 * IMAP RFC.
 * @param stream $imap_stream imap connection resource
 * @param string $mailbox mailbox name (unused since 1.1.3).
 * @param boolean $handle_errors error handling control (displays error_box on error).
 * @param mixed $id (since 1.3.0) integer message id or array with integer ids
 * @return integer number of expunged messages
 * @since 1.0 or older
 */
function sqimap_mailbox_expunge ($imap_stream, $mailbox, $handle_errors = true, $id='') {
    if ($id) {
        if (is_array($id)) {
            $id = sqimap_message_list_squisher($id);
        }
        $id = ' '.$id;
        $uid = TRUE;
    } else {
        $uid = false;
    }
    $read = sqimap_run_command($imap_stream, 'EXPUNGE'.$id, $handle_errors,
                               $response, $message, $uid);
    $cnt = 0;

    if (is_array($read)) {
        foreach ($read as $r) {
            if (preg_match('/^\*\s[0-9]+\sEXPUNGE/AUi',$r,$regs)) {
                $cnt++;
            }
        }
    }
    return $cnt;
}

/**
 * Checks whether or not the specified mailbox exists
 *
 * @param stream $imap_stream imap connection resource
 * @param string $mailbox mailbox name
 * @param array $mailboxlist (since 1.5.1) optional array of mailboxes from
 *  sqimap_get_mailboxes() (to avoid having to talk to imap server)
 * @return boolean
 * @since 1.0 or older
 */
function sqimap_mailbox_exists ($imap_stream, $mailbox, $mailboxlist=null) {
    if (!isset($mailbox) || empty($mailbox)) {
        return false;
    }

    if (is_array($mailboxlist)) {
        // use previously retrieved mailbox list
        foreach ($mailboxlist as $mbox) {
            if ($mbox['unformatted-dm'] == $mailbox) { return true; }
        }
        return false;
    } else {
        // go to imap server
        $mbx = sqimap_run_command($imap_stream, 'LIST "" ' . sqimap_encode_mailbox_name($mailbox),
                                  true, $response, $message);
        return isset($mbx[0]);
    }
}

/**
 * Selects a mailbox
 * Before 1.3.0 used more arguments and returned data depended on those arguments.
 * @param stream $imap_stream imap connection resource
 * @param string $mailbox mailbox name
 * @return array results of select command (on success - permanentflags, flags and rights)
 * @since 1.0 or older
 */
function sqimap_mailbox_select ($imap_stream, $mailbox) {
    if (empty($mailbox)) {
        return;
    }

    // cleanup $mailbox in order to prevent IMAP injection attacks
    $mailbox = str_replace(array("\r","\n"), array("",""),$mailbox);

    /**
     * Default UW IMAP server configuration allows to access other files
     * on server. $imap_server_type is not checked because interface can
     * be used with 'other' or any other server type setting. $mailbox
     * variable can be modified in any script that uses variable from GET
     * or POST. This code blocks all standard SquirrelMail IMAP API requests
     * that use mailbox with full path (/etc/passwd) or with ../ characters
     * in path (../../etc/passwd)
     */
    if (strstr($mailbox, '../') || substr($mailbox, 0, 1) == '/') {
        global $oTemplate;
        error_box(sprintf(_("Invalid mailbox name: %s"),sm_encode_html_special_chars($mailbox)));
        sqimap_logout($imap_stream);
        $oTemplate->display('footer.tpl');
        die();
    }

    $read = sqimap_run_command($imap_stream, 'SELECT ' . sqimap_encode_mailbox_name($mailbox),
                               true, $response, $message);
    $result = array();
    for ($i = 0, $cnt = count($read); $i < $cnt; $i++) {
        if (preg_match('/^\*\s+OK\s\[(\w+)\s(\w+)\]/',$read[$i], $regs)) {
            $result[strtoupper($regs[1])] = $regs[2];
        } else if (preg_match('/^\*\s([0-9]+)\s(\w+)/',$read[$i], $regs)) {
            $result[strtoupper($regs[2])] = $regs[1];
        } else {
            if (preg_match("/PERMANENTFLAGS(.*)/i",$read[$i], $regs)) {
                $regs[1]=trim(preg_replace (  array ("/\(/","/\)/","/\]/") ,'', $regs[1])) ;
                $result['PERMANENTFLAGS'] = explode(' ',strtolower($regs[1]));
            } else if (preg_match("/FLAGS(.*)/i",$read[$i], $regs)) {
                $regs[1]=trim(preg_replace (  array ("/\(/","/\)/") ,'', $regs[1])) ;
                $result['FLAGS'] = explode(' ',strtolower($regs[1]));
            }
        }
    }
    if (!isset($result['PERMANENTFLAGS'])) {
        $result['PERMANENTFLAGS'] = $result['FLAGS'];
    }
    if (preg_match('/^\[(.+)\]/',$message, $regs)) {
        $result['RIGHTS']=strtoupper($regs[1]);
    }

    return $result;
}

/**
 * Creates a folder.
 *
 * Mailbox is automatically subscribed.
 *
 * Set $type to string that does not match 'noselect' (case insensitive),
 * if you don't want to prepend delimiter to mailbox name. Please note
 * that 'noinferiors' might be used someday as keyword for folders
 * that store only messages.
 * @param stream $imap_steam imap connection resource
 * @param string $mailbox mailbox name
 * @param string $type folder type.
 * @since 1.0 or older
 */
function sqimap_mailbox_create ($imap_stream, $mailbox, $type) {
    global $delimiter;
    if (strtolower($type) == 'noselect') {
        $create_mailbox = $mailbox . $delimiter;
    } else {
        $create_mailbox = $mailbox;
    }

    $read_ary = sqimap_run_command($imap_stream, 'CREATE ' .
                                   sqimap_encode_mailbox_name($create_mailbox),
                                   true, $response, $message);
    sqimap_subscribe ($imap_stream, $mailbox);
}

/**
 * Subscribes to an existing folder.
 * @param stream $imap_stream imap connection resource
 * @param string $mailbox mailbox name
 * @param boolean $debug (since 1.5.1)
 * @since 1.0 or older
 */
function sqimap_subscribe ($imap_stream, $mailbox,$debug=true) {
    $read_ary = sqimap_run_command($imap_stream, 'SUBSCRIBE ' .
                                   sqimap_encode_mailbox_name($mailbox),
                                   $debug, $response, $message);
}

/**
 * Unsubscribes from an existing folder
 * @param stream $imap_stream imap connection resource
 * @param string $mailbox mailbox name
 * @since 1.0 or older
 */
function sqimap_unsubscribe ($imap_stream, $mailbox) {
    $read_ary = sqimap_run_command($imap_stream, 'UNSUBSCRIBE ' .
                                   sqimap_encode_mailbox_name($mailbox),
                                   false, $response, $message);
}

/**
 * Deletes the given folder
 * Since 1.2.6 and 1.3.0 contains rename_or_delete_folder hook
 * @param stream $imap_stream imap connection resource
 * @param string $mailbox mailbox name
 * @since 1.0 or older
 */
function sqimap_mailbox_delete ($imap_stream, $mailbox) {
    global $data_dir, $username;
    sqimap_unsubscribe ($imap_stream, $mailbox);

    if (sqimap_mailbox_exists($imap_stream, $mailbox)) {

        $read_ary = sqimap_run_command($imap_stream, 'DELETE ' .
                                       sqimap_encode_mailbox_name($mailbox),
                                       true, $response, $message);
        if ($response !== 'OK') {
            // subscribe again
            sqimap_subscribe ($imap_stream, $mailbox);
        } else {
            $temp = array(&$mailbox, 'delete', '');
            do_hook('rename_or_delete_folder', $temp);
            removePref($data_dir, $username, "thread_$mailbox");
            removePref($data_dir, $username, "collapse_folder_$mailbox");
        }
    }
}

/**
 * Determines if the user is subscribed to the folder or not
 * @param stream $imap_stream imap connection resource
 * @param string $mailbox mailbox name
 * @return boolean
 * @since 1.2.0
 */
function sqimap_mailbox_is_subscribed($imap_stream, $folder) {
    $boxesall = sqimap_mailbox_list ($imap_stream);
    foreach ($boxesall as $ref) {
        if ($ref['unformatted'] == $folder) {
            return true;
        }
    }
    return false;
}

/**
 * Renames a mailbox.
 * Since 1.2.6 and 1.3.0 contains rename_or_delete_folder hook
 * @param stream $imap_stream imap connection resource
 * @param string $old_name mailbox name
 * @param string $new_name new mailbox name
 * @since 1.2.3
 */
function sqimap_mailbox_rename( $imap_stream, $old_name, $new_name ) {
    if ( $old_name != $new_name ) {
        global $delimiter, $imap_server_type, $data_dir, $username;
        if ( substr( $old_name, -1 ) == $delimiter  ) {
            $old_name = substr( $old_name, 0, strlen( $old_name ) - 1 );
            $new_name = substr( $new_name, 0, strlen( $new_name ) - 1 );
            $postfix = $delimiter;
        } else {
            $postfix = '';
        }

        $boxesall = sqimap_mailbox_list_all($imap_stream);
        $cmd = 'RENAME ' . sqimap_encode_mailbox_name($old_name) .
                     ' ' . sqimap_encode_mailbox_name($new_name);
        $data = sqimap_run_command($imap_stream, $cmd, true, $response, $message);
        sqimap_unsubscribe($imap_stream, $old_name.$postfix);
        $oldpref_thread = getPref($data_dir, $username, 'thread_'.$old_name.$postfix);
        $oldpref_collapse = getPref($data_dir, $username, 'collapse_folder_'.$old_name.$postfix);
        removePref($data_dir, $username, 'thread_'.$old_name.$postfix);
        removePref($data_dir, $username, 'collapse_folder_'.$old_name.$postfix);
        sqimap_subscribe($imap_stream, $new_name.$postfix);
        setPref($data_dir, $username, 'thread_'.$new_name.$postfix, $oldpref_thread);
        setPref($data_dir, $username, 'collapse_folder_'.$new_name.$postfix, $oldpref_collapse);
        $temp = array(&$old_name, 'rename', &$new_name);
        do_hook('rename_or_delete_folder', $temp);
        $l = strlen( $old_name ) + 1;
        $p = 'unformatted';

        foreach ($boxesall as $box) {
            if (substr($box[$p], 0, $l) == $old_name . $delimiter) {
                $new_sub = $new_name . $delimiter . substr($box[$p], $l);
                /* With Cyrus IMAPd >= 2.0 rename is recursive, so don't check for errors here */
                if ($imap_server_type == 'cyrus') {
                    $cmd = 'RENAME "' . $box[$p] . '" "' . $new_sub . '"';
                    $data = sqimap_run_command($imap_stream, $cmd, false,
                                               $response, $message);
                }
                $was_subscribed = sqimap_mailbox_is_subscribed($imap_stream, $box[$p]);
                if ( $was_subscribed ) {
                    sqimap_unsubscribe($imap_stream, $box[$p]);
                }
                $oldpref_thread = getPref($data_dir, $username, 'thread_'.$box[$p]);
                $oldpref_collapse = getPref($data_dir, $username, 'collapse_folder_'.$box[$p]);
                removePref($data_dir, $username, 'thread_'.$box[$p]);
                removePref($data_dir, $username, 'collapse_folder_'.$box[$p]);
                if ( $was_subscribed ) {
                    sqimap_subscribe($imap_stream, $new_sub);
                }
                setPref($data_dir, $username, 'thread_'.$new_sub, $oldpref_thread);
                setPref($data_dir, $username, 'collapse_folder_'.$new_sub, $oldpref_collapse);
                $temp = array(&$box[$p], 'rename', &$new_sub);
                do_hook('rename_or_delete_folder', $temp);
            }
        }
    }
}

/**
 * Formats a mailbox into parts for the $boxesall array
 *
 * The parts are:
 * <ul>
 *   <li>raw            - Raw LIST/LSUB response from the IMAP server
 *   <li>formatted      - nicely formatted folder name
 *   <li>unformatted    - unformatted, but with delimiter at end removed
 *   <li>unformatted-dm - folder name as it appears in raw response
 *   <li>unformatted-disp - unformatted without $folder_prefix
 *   <li>id             - TODO: document me
 *   <li>flags          - TODO: document me
 * </ul>
 * Before 1.2.0 used third argument for delimiter.
 *
 * Before 1.5.1 used second argument for lsub line. Argument was removed in order to use
 * find_mailbox_name() on the raw input. Since 1.5.1 includes RFC3501 names in flags
 * array (for example, "\NoSelect" in addition to "noselect")
 * @param array $line
 * @return array
 * @since 1.0 or older
 * @todo document id and flags keys in boxes array and function arguments.
 */
function sqimap_mailbox_parse ($line) {
    global $folder_prefix, $delimiter;

    /* Process each folder line */
    for ($g = 0, $cnt = count($line); $g < $cnt; ++$g) {
        /* Store the raw IMAP reply */
        if (isset($line[$g])) {
            $boxesall[$g]['raw'] = $line[$g];
        } else {
            $boxesall[$g]['raw'] = '';
        }

        /* Count number of delimiters ($delimiter) in folder name */
        $mailbox = find_mailbox_name($line[$g]);
        $dm_count = substr_count($mailbox, $delimiter);
        if (substr($mailbox, -1) == $delimiter) {
            /* If name ends in delimiter, decrement count by one */
            $dm_count--;
        }

        /* Format folder name, but only if it's a INBOX.* or has a parent. */
        $boxesallbyname[$mailbox] = $g;
        $parentfolder = readMailboxParent($mailbox, $delimiter);
        if ( (strtolower(substr($mailbox, 0, 5)) == "inbox") ||
             (substr($mailbox, 0, strlen($folder_prefix)) == $folder_prefix) ||
             (isset($boxesallbyname[$parentfolder]) &&
              (strlen($parentfolder) > 0) ) ) {
            $indent = $dm_count - (substr_count($folder_prefix, $delimiter));
            if ($indent > 0) {
                $boxesall[$g]['formatted'] = str_repeat('&nbsp;&nbsp;', $indent);
            } else {
                $boxesall[$g]['formatted'] = '';
            }
            $boxesall[$g]['formatted'] .= imap_utf7_decode_local(readShortMailboxName($mailbox, $delimiter));
        } else {
            $boxesall[$g]['formatted']  = imap_utf7_decode_local($mailbox);
        }

        $boxesall[$g]['unformatted-dm'] = $mailbox;
        if (substr($mailbox, -1) == $delimiter) {
            $mailbox = substr($mailbox, 0, strlen($mailbox) - 1);
        }
        $boxesall[$g]['unformatted'] = $mailbox;
        if (substr($mailbox,0,strlen($folder_prefix))==$folder_prefix) {
            $mailbox = substr($mailbox, strlen($folder_prefix));
        }
        $boxesall[$g]['unformatted-disp'] = $mailbox;
        $boxesall[$g]['id'] = $g;

        $boxesall[$g]['flags'] = array();
        if (isset($line[$g]) && preg_match('/\(([^)]*)\)/',$line[$g],$regs) ) {
            /**
             * Since 1.5.1 flags are stored with RFC3501 naming
             * and also the old way for backwards compatibility
             * so for example "\NoSelect" and "noselect"
             */
            $flags = trim($regs[1]);
            if ($flags) {
                $flagsarr = explode(' ',$flags);
                $flagsarrnew=$flagsarr;
                // add old type
                foreach ($flagsarr as $flag) {
                    $flagsarrnew[]=strtolower(str_replace('\\', '',$flag));
                }
                $boxesall[$g]['flags']=$flagsarrnew;
            }
        }
    }
    return $boxesall;
}

/**
 * Returns an array of mailboxes available.  Separated from sqimap_mailbox_option_list()
 * below for template development.
 * 
 * @author Steve Brown
 * @since 1.5.2
 */
function sqimap_mailbox_option_array($imap_stream, $folder_skip = 0, $boxes = 0,
                                    $flag = 'noselect', $use_long_format = false ) {
    global $username, $data_dir, $translate_special_folders, $sent_folder, 
        $trash_folder, $draft_folder;

    $delimiter = sqimap_get_delimiter($imap_stream);

    $mbox_options = '';
    if ( $use_long_format ) {
        $shorten_box_names = 0;
    } else {
        $shorten_box_names = getPref($data_dir, $username, 'mailbox_select_style', SMPREF_MAILBOX_SELECT_INDENTED);
    }

    if ($boxes == 0) {
        $boxes = sqimap_mailbox_list($imap_stream);
    }

    $a = array();
    foreach ($boxes as $boxes_part) {
        if ($flag == NULL || (is_array($boxes_part['flags'])
                      && !in_array($flag, $boxes_part['flags']))) {
            $box = $boxes_part['unformatted'];

            if ($folder_skip != 0 && in_array($box, $folder_skip) ) {
                continue;
            }
            $lowerbox = strtolower($box);
            // mailboxes are casesensitive => inbox.sent != inbox.Sent
            // nevermind, to many dependencies this should be fixed!

            if (strtolower($box) == 'inbox') { // inbox is special and not casesensitive
                $box2 = _("INBOX");
            } else {
                switch ($shorten_box_names)
                {
                  case SMPREF_MAILBOX_SELECT_DELIMITED:
                      if ($translate_special_folders && $boxes_part['unformatted-dm']==$sent_folder) {
                          /*
                           * calculate pad level from number of delimiters. do it inside if control in order 
                           * to reduce number of calculations. Other folders don't need it.
                           */
                          $pad = str_pad('',7 * (count(explode($delimiter,$boxes_part['unformatted-dm']))-1),'.&nbsp;');
                          // i18n: Name of Sent folder
                          $box2 = $pad . _("Sent");
                      } elseif ($translate_special_folders && $boxes_part['unformatted-dm']==$trash_folder) {
                          $pad = str_pad('',7 * (count(explode($delimiter,$boxes_part['unformatted-dm']))-1),'.&nbsp;');
                          // i18n: Name of Trash folder
                          $box2 = $pad . _("Trash");
                      } elseif ($translate_special_folders && $boxes_part['unformatted-dm']==$draft_folder) {
                          $pad = str_pad('',7 * (count(explode($delimiter,$boxes_part['unformatted-dm']))-1),'.&nbsp;');
                          // i18n: Name of Drafts folder
                          $box2 = $pad . _("Drafts");
                      } else {
                          $box2 = str_replace('&amp;nbsp;&amp;nbsp;', '.&nbsp;', sm_encode_html_special_chars($boxes_part['formatted']));
                      }
                    break;
                  case SMPREF_MAILBOX_SELECT_INDENTED:
                      if ($translate_special_folders && $boxes_part['unformatted-dm']==$sent_folder) {
                          $pad = str_pad('',12 * (count(explode($delimiter,$boxes_part['unformatted-dm']))-1),'&nbsp;&nbsp;');
                          $box2 = $pad . _("Sent");
                      } elseif ($translate_special_folders && $boxes_part['unformatted-dm']==$trash_folder) {
                          $pad = str_pad('',12 * (count(explode($delimiter,$boxes_part['unformatted-dm']))-1),'&nbsp;&nbsp;');
                          $box2 = $pad . _("Trash");
                      } elseif ($translate_special_folders && $boxes_part['unformatted-dm']==$draft_folder) {
                          $pad = str_pad('',12 * (count(explode($delimiter,$boxes_part['unformatted-dm']))-1),'&nbsp;&nbsp;');
                          $box2 = $pad . _("Drafts");
                      } else {
                          $box2 = str_replace('&amp;nbsp;&amp;nbsp;', '&nbsp;&nbsp;', sm_encode_html_special_chars($boxes_part['formatted']));
                      }
                    break;
                  default:  /* default, long names, style = 0 */
                    $box2 = str_replace(' ', '&nbsp;', sm_encode_html_special_chars(imap_utf7_decode_local($boxes_part['unformatted-disp'])));
                    break;
                }
            }
            
            $a[sm_encode_html_special_chars($box)] = $box2;
        }
    }
    
    return $a;
}

/**
 * Returns list of options (to be echoed into select statement
 * based on available mailboxes and separators
 * Caller should surround options with <select ...> </select> and
 * any formatting.
 * @param stream $imap_stream imap connection resource to query for mailboxes
 * @param array $show_selected array containing list of mailboxes to pre-select (0 if none)
 * @param array $folder_skip array of folders to keep out of option list (compared in lower)
 * @param $boxes list of already fetched boxes (for places like folder panel, where
 *            you know these options will be shown 3 times in a row.. (most often unset).
 * @param string $flag (since 1.4.1) flag to check for in mailbox flags, used to filter out mailboxes.
 *           'noselect' by default to remove unselectable mailboxes.
 *           'noinferiors' used to filter out folders that can not contain subfolders.
 *           NULL to avoid flag check entirely.
 *           NOTE: noselect and noiferiors are used internally. The IMAP representation is
 *                 \NoSelect and \NoInferiors
 * @param boolean $use_long_format (since 1.4.1) override folder display preference and always show full folder name.
 * @return string html formated mailbox selection options
 * @since 1.3.2
 */
function sqimap_mailbox_option_list($imap_stream, $show_selected = 0, $folder_skip = 0, $boxes = 0,
                                    $flag = 'noselect', $use_long_format = false ) {
    global $username, $data_dir, $translate_special_folders, $sent_folder, 
        $trash_folder, $draft_folder;

    $boxes = sqimap_mailbox_option_array($imap_stream, $folder_skip, $boxes, $flag, $use_long_format);
    
    $str = '';
    foreach ($boxes as $value=>$option) {
        $lowerbox = strtolower(sm_encode_html_special_chars($value));
        $sel = false;
        if ($show_selected != 0) {
            reset($show_selected);
            while (!$sel && (list($x, $val) = each($show_selected))) {
                if (strtolower($value) == strtolower(sm_encode_html_special_chars($val))) {
                    $sel = true;
                }
            }
        }
        
        $str .= '<option value="'. $value .'"'. ($sel ? ' selected="selected"' : '').'>'. $option ."</option>\n";
    }
    
    return $str;
}

/**
 * Returns sorted mailbox lists in several different ways.
 *
 * Since 1.5.1 most of the functionality has been moved to new function sqimap_get_mailboxes
 *
 * See comment on sqimap_mailbox_parse() for info about the returned array.
 * @param resource $imap_stream imap connection resource
 * @param boolean $force force update of mailbox listing. available since 1.4.2 and 1.5.0
 * @return array list of mailboxes
 * @since 1.0 or older
 */
function sqimap_mailbox_list($imap_stream, $force=false) {
    global $boxesnew,$show_only_subscribed_folders;
    if (!sqgetGlobalVar('boxesnew',$boxesnew,SQ_SESSION) || $force) {
        $boxesnew=sqimap_get_mailboxes($imap_stream,$force,$show_only_subscribed_folders);
    }
    return $boxesnew;
}

/**
 * Returns a list of all folders, subscribed or not
 *
 * Since 1.5.1 code moved to sqimap_get_mailboxes()
 *
 * @param stream $imap_stream imap connection resource
 * @return array see sqimap_mailbox_parse()
 * @since 1.0 or older
 */
function sqimap_mailbox_list_all($imap_stream) {
    global $show_only_subscribed_folders;
    // fourth argument prevents registration of retrieved list of mailboxes in session
    $boxes=sqimap_get_mailboxes($imap_stream,true,false,false);
    return $boxes;
}


/**
 * Gets the list of mailboxes for sqimap_maolbox_tree and sqimap_mailbox_list
 *
 * This is because both of those functions had duplicated logic, but with slightly different
 * implementations. This will make both use the same implementation, which should make it
 * easier to maintain and easier to modify in the future
 * @param stream $imap_stream imap connection resource
 * @param bool $force force a reload and ignore cache
 * @param bool $show_only_subscribed controls listing of visible or all folders
 * @param bool $session_register controls registration of retrieved data in session.
 * @return object boxesnew - array of mailboxes and their attributes
 * @since 1.5.1
 */
function sqimap_get_mailboxes($imap_stream,$force=false,$show_only_subscribed=true,$session_register=true) {
    global    $show_only_subscribed_folders,$noselect_fix_enable,$folder_prefix,
            $list_special_folders_first,$imap_server_type;
    $inbox_subscribed = false;
    $listsubscribed = sqimap_capability($imap_stream,'LIST-SUBSCRIBED');

    if ($show_only_subscribed) { $show_only_subscribed=$show_only_subscribed_folders; }

    //require_once(SM_PATH . 'include/load_prefs.php');

    /**
     * There are three main listing commands we can use in IMAP:
     * LSUB        shows just the list of subscribed folders
     *            may include flags, but these are not necessarily accurate or authoratative
     *            \NoSelect has special meaning: the folder does not exist -OR- it means this
     *            folder is not subscribed but children may be
     *            [RFC-2060]
     * LIST        this shows every mailbox on the system
     *            flags are always included and are accurate and authoratative
     *            \NoSelect means folder should not be selected
     *            [RFC-2060]
     * LIST (SUBSCRIBED)    implemented with LIST-SUBSCRIBED extension
     *            this is like list but returns only subscribed folders
     *            flag meanings are like LIST, not LSUB
     *            \NonExistent means mailbox doesn't exist
     *            \PlaceHolder means parent is not valid (selectable), but one or more children are
     *            \NoSelect indeed means that the folder should not be selected
     *            IMAPEXT-LIST-EXTENSIONS-04 August 2003 B. Leiba
     */
    if (!$show_only_subscribed) {
        $lsub = 'LIST';
        $sub_cache_name='list_cache';
    }  elseif ($listsubscribed) {
        $lsub = 'LIST (SUBSCRIBED)';
        $sub_cache_name='listsub_cache';
    } else {
        $lsub = 'LSUB';
        $sub_cache_name='lsub_cache';
    }

    // Some IMAP servers allow subfolders to exist even if the parent folders do not
    // This fixes some problems with the folder list when this is the case, causing the
    // NoSelect folders to be displayed
    if ($noselect_fix_enable) {
        $lsub_args = "$lsub \"$folder_prefix\" \"*%\"";
        $list_args = "LIST \"$folder_prefix\" \"*%\"";
    } else {
        $lsub_args = "$lsub \"$folder_prefix\" \"*\"";
        $list_args = "LIST \"$folder_prefix\" \"*\"";
    }

    // get subscribed mailbox list from cache (session)
    // if not there, then get it from the imap server and store in cache

    if (!$force) {
        sqgetGlobalVar($sub_cache_name,$lsub_cache,SQ_SESSION);
    }

    $lsub_assoc_ary=array();
    if (!empty($lsub_cache)) {
        $lsub_assoc_ary=$lsub_cache;
    } else {
        $lsub_ary = sqimap_run_command ($imap_stream, $lsub_args, true, $response, $message);
        $lsub_ary = compact_mailboxes_response($lsub_ary);
        if (!empty($lsub_ary)) {
            foreach ($lsub_ary as $rawline) {
                $temp_mailbox_name=find_mailbox_name($rawline);
                $lsub_assoc_ary[$temp_mailbox_name]=$rawline;
            }
            unset($lsub_ary);
            sqsession_register($lsub_assoc_ary,$sub_cache_name);
        }
    }

    // Now to get the mailbox flags
    // The LSUB response may return \NoSelect flags, etc. but it is optional
    // according to RFC3501, and even when returned it may not be accurate
    // or authoratative. LIST will always return accurate results.
    if (($lsub == 'LIST') || ($lsub == 'LIST (SUBSCRIBED)')) {
        // we've already done a LIST or LIST (SUBSCRIBED)
        // and NOT a LSUB, so no need to do it again
        $list_assoc_ary  = $lsub_assoc_ary;
    } else {
        // we did a LSUB so now we need to do a LIST
        // first see if it is in cache
        $list_cache_name='list_cache';
        if (!$force) {
            sqgetGlobalVar($list_cache_name,$list_cache,SQ_SESSION);
        }

        if (!empty($list_cache)) {
            $list_assoc_ary=$list_cache;
            // we could store this in list_cache_name but not necessary
        } else {
            // not in cache so we need to go get it from the imap server
            $list_assoc_ary = array();
            $list_ary = sqimap_run_command($imap_stream, $list_args,
                                           true, $response, $message);
            $list_ary = compact_mailboxes_response($list_ary);
            if (!empty($list_ary)) {
                foreach ($list_ary as $rawline) {
                    $temp_mailbox_name=find_mailbox_name($rawline);
                    $list_assoc_ary[$temp_mailbox_name]=$rawline;
                }
                unset($list_ary);
                sqsession_register($list_assoc_ary,$list_cache_name);
            }
        }
    }

    // If they aren't subscribed to the inbox, then add it anyway (if its in LIST)
    $inbox_subscribed=false;
    if (!empty($lsub_assoc_ary)) {
        foreach ($lsub_assoc_ary as $temp_mailbox_name=>$rawline) {
            if (strtoupper($temp_mailbox_name) == 'INBOX') {
                $inbox_subscribed=true;
            }
        }
    }
    if (!$inbox_subscribed)  {
        if (!empty($list_assoc_ary)) {
            foreach ($list_assoc_ary as $temp_mailbox_name=>$rawline) {
                if (strtoupper($temp_mailbox_name) == 'INBOX') {
                    $lsub_assoc_ary[$temp_mailbox_name]=$rawline;
                }
            }
        }
    }

    // Now we have the raw output, we need to create an array of mailbox names we will return
    if (!$show_only_subscribed) {
        $final_folders_assoc_ary=$list_assoc_ary;
    } else {
        /**
         * only show subscribed folders
         * we need to merge the folders here... we can't trust the flags, etc. from the lsub_assoc_array
         * so we use the lsub_assoc_array as the list of folders and the values come from list_assoc_array
         */
        if (!empty($lsub_assoc_ary)) {
            foreach ($lsub_assoc_ary as $temp_mailbox_name=>$rawline) {
                if (!empty($list_assoc_ary[$temp_mailbox_name])) {
                    $final_folders_assoc_ary[$temp_mailbox_name]=$list_assoc_ary[$temp_mailbox_name];
                }
            }
        }
    }


    // Now produce a flat, sorted list
    if (!empty($final_folders_assoc_ary)) {
        uksort($final_folders_assoc_ary,'strnatcasecmp');
        foreach ($final_folders_assoc_ary as $temp_mailbox_name=>$rawline) {
            $final_folders_ary[]=$rawline;
        }
    }

    // this will put it into an array we can use later
    // containing:
    // raw    - Raw LIST/LSUB response from the IMAP server
    // formatted - formatted folder name
    // unformatted - unformatted, but with the delimiter at the end removed
    // unformated-dm - folder name as it appears in raw response
    // unformatted-disp - unformatted without $folder_prefix
    // id - the array element number (0, 1, 2, etc.)
    // flags - mailbox flags
    if (!empty($final_folders_ary)) {
        $boxesall = sqimap_mailbox_parse($final_folders_ary);
    } else {
        // they have no mailboxes
        $boxesall=array();
    }

    /* Now, lets sort for special folders */
    $boxesnew = $used = array();

    /* Find INBOX */
    $cnt = count($boxesall);
    $used = array_pad($used,$cnt,false);
    $has_inbox = false;
    for($k = 0; $k < $cnt; ++$k) {
        if (strtoupper($boxesall[$k]['unformatted']) == 'INBOX') {
            $boxesnew[] = $boxesall[$k];
            $used[$k] = true;
            $has_inbox = true;
            break;
        }
    }

    if ($has_inbox == false) {
        // do a list request for inbox because we should always show
        // inbox even if the user isn't subscribed to it.
        $inbox_ary = sqimap_run_command($imap_stream, 'LIST "" "INBOX"',
                                        true, $response, $message);
        $inbox_ary = compact_mailboxes_response($inbox_ary);
        if (count($inbox_ary)) {
            $inbox_entry = sqimap_mailbox_parse($inbox_ary);
            // add it on top of the list
            if (!empty($boxesnew)) {
                array_unshift($boxesnew,$inbox_entry[0]);
            } else {
                $boxesnew[]=$inbox_entry[0];
            }
            /* array_unshift($used,true); */
        }
    }

    /* List special folders and their subfolders, if requested. */
    if ($list_special_folders_first) {
        for($k = 0; $k < $cnt; ++$k) {
            if (!$used[$k] && isSpecialMailbox($boxesall[$k]['unformatted'])) {
                $boxesnew[] = $boxesall[$k];
                $used[$k]   = true;
            }
        }
    }

    /* Find INBOX's children */
    for($k = 0; $k < $cnt; ++$k) {
        $isboxbelow=isBoxBelow(strtoupper($boxesall[$k]['unformatted']),'INBOX');
        if (strtoupper($boxesall[$k]['unformatted']) == 'INBOX') {
            $is_inbox=1;
        } else {
            $is_inbox=0;
        }

        if (!$used[$k] && $isboxbelow && $is_inbox) {
            $boxesnew[] = $boxesall[$k];
            $used[$k] = true;
        }
    }

    /* Rest of the folders */
    for($k = 0; $k < $cnt; $k++) {
        if (!$used[$k]) {
            $boxesnew[] = $boxesall[$k];
        }
    }
    /**
     * Don't register boxes in session, if $session_register is set to false
     * Prevents registration of sqimap_mailbox_list_all() results.
     */
    if ($session_register) sqsession_register($boxesnew,'boxesnew');
    return $boxesnew;
}

/**
 * Fills mailbox object
 *
 * this is passed the mailbox array by left_main.php
 * who has previously obtained it from sqimap_get_mailboxes
 * that way, the raw mailbox list is available in left_main to other
 * things besides just sqimap_mailbox_tree
 * imap_stream is just used now to get status info
 *
 * most of the functionality is moved to sqimap_get_mailboxes
 * also takes care of TODO items:
 * caching mailbox tree
 * config setting for UW imap section (not needed now)
 *
 * Some code fragments are present in 1.3.0 - 1.4.4.
 * @param stream $imap_stream imap connection resource
 * @param array $lsub_ary output array from sqimap_get_mailboxes (contains mailboxes and flags)
 * @return object see mailboxes class.
 * @since 1.5.0
 */
function sqimap_mailbox_tree($imap_stream,$lsub_ary) {

    $sorted_lsub_ary = array();
    $cnt = count($lsub_ary);
    for ($i = 0; $i < $cnt; $i++) {
        $mbx=$lsub_ary[$i]['unformatted'];
        $flags=$lsub_ary[$i]['flags'];

        $noinferiors=0;
        if (in_array('\Noinferiors',$flags)) { $noinferiors=1; }
        if (in_array('\NoInferiors',$flags)) { $noinferiors=1; }
        if (in_array('\HasNoChildren',$flags)) { $noinferiors=1; }

        $noselect=0;
        if (in_array('\NoSelect',$flags)) { $noselect=1; }
        /**
         * LIST (SUBSCRIBED) has two new flags, \NonExistent which means the mailbox is subscribed to
         * but doesn't exist, and \PlaceHolder which is similar (but not the same) as \NoSelect
         * For right now, we'll treat these the same as \NoSelect and this behavior can be changed
         * later if needed
         */
        if (in_array('\NonExistent',$flags)) { $noselect=1; }
        if (in_array('\PlaceHolder',$flags)) { $noselect=1; }
        $sorted_lsub_ary[] = array ('mbx' => $mbx, 'noselect' => $noselect, 'noinferiors' => $noinferiors);
    }

    $sorted_lsub_ary = array_values($sorted_lsub_ary);
    usort($sorted_lsub_ary, 'mbxSort');
    $boxestree = sqimap_fill_mailbox_tree($sorted_lsub_ary,false,$imap_stream);
    return $boxestree;
}

/**
 * Callback function used for sorting mailboxes in sqimap_mailbox_tree
 * @param string $a
 * @param string $b
 * @return integer see php strnatcasecmp()
 * @since 1.5.1
 */
function mbxSort($a, $b) {
    return strnatcasecmp($a['mbx'], $b['mbx']);
}

/**
 * Fills mailbox object
 *
 * Some code fragments are present in 1.3.0 - 1.4.4.
 * @param array $mbx_ary
 * @param $mbxs
 * @param stream $imap_stream imap connection resource
 * @return object see mailboxes class
 * @since 1.5.0
 */
function sqimap_fill_mailbox_tree($mbx_ary, $mbxs=false,$imap_stream) {
    global $data_dir, $username, $list_special_folders_first,
           $folder_prefix, $trash_folder, $sent_folder, $draft_folder,
           $move_to_trash, $move_to_sent, $save_as_draft,
           $delimiter, $imap_server_type;

    // $special_folders = array ('INBOX', $sent_folder, $draft_folder, $trash_folder);

    /* create virtual root node */
    $mailboxes= new mailboxes();
    $mailboxes->is_root = true;
    $trail_del = false;
    $start = 0;

    if (isset($folder_prefix) && ($folder_prefix != '')) {
        $start = substr_count($folder_prefix,$delimiter);
        if (strrpos($folder_prefix, $delimiter) == (strlen($folder_prefix)-1)) {
            $mailboxes->mailboxname_full = substr($folder_prefix,0, (strlen($folder_prefix)-1));
        } else {
            $mailboxes->mailboxname_full = $folder_prefix;
            $start++;
        }
        $mailboxes->mailboxname_sub = $mailboxes->mailboxname_full;
    } else {
        $start = 0;
    }

    $cnt = count($mbx_ary);
    for ($i=0; $i < $cnt; $i++) {
        if ($mbx_ary[$i]['mbx'] !='' ) {
            $mbx = new mailboxes();
            $mailbox = $mbx_ary[$i]['mbx'];

            /*
             * Set the is_special flag if it concerned a special mailbox.
             * Used for displaying the special folders on top in the mailbox
             * tree displaying code.
             */
            $mbx->is_special |= ($mbx->is_inbox = (strtoupper($mailbox) == 'INBOX'));
            $mbx->is_special |= ($mbx->is_trash = isTrashMailbox($mailbox));
            $mbx->is_special |= ($mbx->is_sent = isSentMailbox($mailbox));
            $mbx->is_special |= ($mbx->is_draft = isDraftMailbox($mailbox));

            if (!$mbx->is_special)
                $mbx->is_special = boolean_hook_function('special_mailbox', $mailbox, 1);

            if (isset($mbx_ary[$i]['unseen'])) {
                $mbx->unseen = $mbx_ary[$i]['unseen'];
            }
            if (isset($mbx_ary[$i]['nummessages'])) {
                $mbx->total = $mbx_ary[$i]['nummessages'];
            }

            $mbx->is_noselect = $mbx_ary[$i]['noselect'];
            $mbx->is_noinferiors = $mbx_ary[$i]['noinferiors'];

            $r_del_pos = strrpos($mbx_ary[$i]['mbx'], $delimiter);
            if ($r_del_pos) {
                $mbx->mailboxname_sub = substr($mbx_ary[$i]['mbx'],$r_del_pos+1);
            } else {   /* mailbox is root folder */
                $mbx->mailboxname_sub = $mbx_ary[$i]['mbx'];
            }
            $mbx->mailboxname_full = $mbx_ary[$i]['mbx'];

            $mailboxes->addMbx($mbx, $delimiter, $start, $list_special_folders_first);
        }
    }
    sqimap_utf7_decode_mbx_tree($mailboxes);
    sqimap_get_status_mbx_tree($imap_stream,$mailboxes);
    return $mailboxes;
}

/**
 * @param object $mbx_tree
 * @since 1.5.0
 */
function sqimap_utf7_decode_mbx_tree(&$mbx_tree) {
    global $draft_folder, $sent_folder, $trash_folder, $translate_special_folders;

    /* decode folder name and set mailboxname_sub */
    if ($translate_special_folders && strtoupper($mbx_tree->mailboxname_full) == 'INBOX') {
        $mbx_tree->mailboxname_sub = _("INBOX");
    } elseif ($translate_special_folders && $mbx_tree->mailboxname_full == $draft_folder) {
        $mbx_tree->mailboxname_sub = _("Drafts");
    } elseif ($translate_special_folders && $mbx_tree->mailboxname_full == $sent_folder) {
        $mbx_tree->mailboxname_sub = _("Sent");
    } elseif ($translate_special_folders && $mbx_tree->mailboxname_full == $trash_folder) {
        $mbx_tree->mailboxname_sub = _("Trash");
    } else {
        $mbx_tree->mailboxname_sub = imap_utf7_decode_local($mbx_tree->mailboxname_sub);
    }

    if ($mbx_tree->mbxs) {
        $iCnt = count($mbx_tree->mbxs);
        for ($i=0;$i<$iCnt;++$i) {
            sqimap_utf7_decode_mbx_tree($mbx_tree->mbxs[$i]);
        }
    }
}

/**
 * @param object $mbx_tree
 * @param array $aMbxs
 * @since 1.5.0
 */
function sqimap_tree_to_ref_array(&$mbx_tree,&$aMbxs) {
   if ($mbx_tree)
   $aMbxs[] =& $mbx_tree;
   if ($mbx_tree->mbxs) {
      $iCnt = count($mbx_tree->mbxs);
      for ($i=0;$i<$iCnt;++$i) {
         sqimap_tree_to_ref_array($mbx_tree->mbxs[$i],$aMbxs);
      }
   }
}

/**
 * @param stream $imap_stream imap connection resource
 * @param object $mbx_tree
 * @since since 1.5.0
 */
function sqimap_get_status_mbx_tree($imap_stream,&$mbx_tree) {
    global $unseen_notify, $unseen_type, $trash_folder,$move_to_trash;
    $aMbxs = $aQuery = array();
    sqimap_tree_to_ref_array($mbx_tree,$aMbxs);
    // remove the root node
    array_shift($aMbxs);

    if($unseen_notify == 3) {
        $cnt = count($aMbxs);
        for($i=0;$i<$cnt;++$i) {
            $oMbx =& $aMbxs[$i];
            if (!$oMbx->is_noselect) {
                $mbx = $oMbx->mailboxname_full;
                if ($unseen_type == 2 ||
                   ($move_to_trash && $oMbx->mailboxname_full == $trash_folder)) {
                   $query = 'STATUS ' . sqimap_encode_mailbox_name($mbx) . ' (MESSAGES UNSEEN RECENT)';
                } else {
                   $query = 'STATUS ' . sqimap_encode_mailbox_name($mbx) . ' (UNSEEN RECENT)';
                }
                sqimap_prepare_pipelined_query($query,$tag,$aQuery,false);
            } else {
                $oMbx->unseen = $oMbx->total = $oMbx->recent = false;
                $tag = false;
            }
            $oMbx->tag = $tag;
            $aMbxs[$i] =& $oMbx;
        }
        // execute all the queries at once
        $aResponse = sqimap_run_pipelined_command ($imap_stream, $aQuery, false, $aServerResponse, $aServerMessage);
        $cnt = count($aMbxs);
        for($i=0;$i<$cnt;++$i) {
            $oMbx =& $aMbxs[$i];
            $tag = $oMbx->tag;
            if ($tag && $aServerResponse[$tag] == 'OK') {
                $sResponse = implode('', $aResponse[$tag]);
                if (preg_match('/UNSEEN\s+([0-9]+)/i', $sResponse, $regs)) {
                    $oMbx->unseen = $regs[1];
                }
                if (preg_match('/MESSAGES\s+([0-9]+)/i', $sResponse, $regs)) {
                    $oMbx->total = $regs[1];
                }
                if (preg_match('/RECENT\s+([0-9]+)/i', $sResponse, $regs)) {
                    $oMbx->recent = $regs[1];
                }

           }
           unset($oMbx->tag);
        }
    } else if ($unseen_notify == 2) { // INBOX only
        $cnt = count($aMbxs);
        for($i=0;$i<$cnt;++$i) {
            $oMbx =& $aMbxs[$i];
            if (strtoupper($oMbx->mailboxname_full) == 'INBOX' ||
               ($move_to_trash && $oMbx->mailboxname_full == $trash_folder)) {
                 if ($unseen_type == 2 ||
                   ($oMbx->mailboxname_full == $trash_folder && $move_to_trash)) {
                    $aStatus = sqimap_status_messages($imap_stream,$oMbx->mailboxname_full);
                    $oMbx->unseen = $aStatus['UNSEEN'];
                    $oMbx->total  = $aStatus['MESSAGES'];
                    $oMbx->recent = $aStatus['RECENT'];
                } else {
                    $oMbx->unseen = sqimap_unseen_messages($imap_stream,$oMbx->mailboxname_full);
                }
                $aMbxs[$i] =& $oMbx;
                if (!$move_to_trash && $trash_folder) {
                    break;
                } else {
                   // trash comes after INBOX
                   if ($oMbx->mailboxname_full == $trash_folder) {
                      break;
                   }
                }
            }
        }
    }

    $cnt = count($aMbxs);
    for($i=0;$i<$cnt;++$i) {
         $oMbx =& $aMbxs[$i];
         unset($hook_status);
         if (!empty($oMbx->unseen)) { $hook_status['UNSEEN']=$oMbx->unseen; }
         if (!empty($oMbx->total)) { $hook_status['MESSAGES']=$oMbx->total; }
         if (!empty($oMbx->recent)) { $hook_status['RECENT']=$oMbx->recent; }
         if (!empty($hook_status))
         {
              $hook_status['MAILBOX']=$oMbx->mailboxname_full;
              $hook_status['CALLER']='sqimap_get_status_mbx_tree'; // helps w/ debugging
              do_hook('folder_status', $hook_status);
         }
    }
}

/**
 * Checks if folder is noselect (can't store messages)
 *
 * Function does not check if folder subscribed.
 * @param stream $oImapStream imap connection resource
 * @param string $sImapFolder imap folder name
 * @param object $oBoxes mailboxes class object.
 * @return boolean true, when folder has noselect flag. false in any other case.
 * @since 1.5.1
 */
function sqimap_mailbox_is_noselect($oImapStream,$sImapFolder,&$oBoxes) {
    // build mailbox object if it is not available
    if (! is_object($oBoxes)) $oBoxes=sqimap_mailbox_list($oImapStream);
    foreach($oBoxes as $box) {
        if ($box['unformatted']==$sImapFolder) {
            return (bool) check_is_noselect($box['raw']);
        }
    }
    return false;
}

/**
 * Checks if folder is noinferiors (can't store other folders)
 *
 * Function does not check if folder subscribed.
 * @param stream $oImapStream imap connection resource
 * @param string $sImapFolder imap folder name
 * @param object $oBoxes mailboxes class object.
 * @return boolean true, when folder has noinferiors flag. false in any other case.
 * @since 1.5.1
 */
function sqimap_mailbox_is_noinferiors($oImapStream,$sImapFolder,&$oBoxes) {
    // build mailbox object if it is not available
    if (! is_object($oBoxes)) $oBoxes=sqimap_mailbox_list($oImapStream);
    foreach($oBoxes as $box) {
        if ($box['unformatted']==$sImapFolder) {
            return (bool) check_is_noinferiors($box['raw']);
        }
    }
    return false;
}
