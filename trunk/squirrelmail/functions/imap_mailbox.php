<?php

/**
 * imap_mailbox.php
 *
 * This implements all functions that manipulate mailboxes
 *
 * @copyright &copy; 1999-2005 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage imap
 */

/** @ignore */
if (! defined('SM_PATH')) define('SM_PATH','../');

/** UTF7 support */
require_once(SM_PATH . 'functions/imap_utf7_local.php');

global $boxesnew;

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
        $unseen = false, $total = false;

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
            if (ereg("^(\\* [A-Z]+.*)\\{[0-9]+\\}([ \n\r\t]*)$",
                 $ary[$i], $regs)) {
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
 * Since 1.2.5 function includes special_mailbox hook.<br>
 * Since 1.4.3 hook supports more than one plugin.
 * @param string $box mailbox name
 * @return boolean
 * @since 1.2.3
 */
function isSpecialMailbox( $box ) {
    $ret = ( (strtolower($box) == 'inbox') ||
             isTrashMailbox($box) || isSentMailbox($box) || isDraftMailbox($box) );

    if ( !$ret ) {
        $ret = boolean_hook_function('special_mailbox',$box,1);
    }
    return $ret;
}

/**
 * Detects if mailbox is a Trash folder or subfolder of Trash
 * @param string $box mailbox name
 * @return bool whether this is a Trash folder
 * @since 1.4.0
 */
function isTrashMailbox ($box) {
    global $trash_folder, $move_to_trash;
    return $move_to_trash && $trash_folder &&
           ( $box == $trash_folder || isBoxBelow($box, $trash_folder) );
}

/**
 * Detects if mailbox is a Sent folder or subfolder of Sent
 * @param string $box mailbox name
 * @return bool whether this is a Sent folder
 * @since 1.4.0
 */
function isSentMailbox($box) {
   global $sent_folder, $move_to_sent;
   return $move_to_sent && $sent_folder &&
          ( $box == $sent_folder || isBoxBelow($box, $sent_folder) );
}

/**
 * Detects if mailbox is a Drafts folder or subfolder of Drafts
 * @param string $box mailbox name
 * @return bool whether this is a Draft folder
 * @since 1.4.0
 */
function isDraftMailbox($box) {
   global $draft_folder, $save_as_draft;
   return $save_as_draft &&
          ( $box == $draft_folder || isBoxBelow($box, $draft_folder) );
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
 * @param stream $imap_stream imap connection resource
 * @param string $mailbox mailbox name
 * @return boolean
 * @since 1.0 or older
 */
function sqimap_mailbox_exists ($imap_stream, $mailbox) {
    if (!isset($mailbox) || empty($mailbox)) {
        return false;
    }
    $mbx = sqimap_run_command($imap_stream, 'LIST "" ' . sqimap_encode_mailbox_name($mailbox),
                              true, $response, $message);
    return isset($mbx[0]);
}

/**
 * Selects a mailbox
 * Before 1.3.0 used more arguments and returned data depended on those argumements.
 * @param stream $imap_stream imap connection resource
 * @param string $mailbox mailbox name
 * @return array results of select command (on success - permanentflags, flags and rights)
 * @since 1.0 or older
 */
function sqimap_mailbox_select ($imap_stream, $mailbox) {
    if ($mailbox == 'None') {
        return;
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
        $mailbox .= $delimiter;
    }

    $read_ary = sqimap_run_command($imap_stream, 'CREATE ' .
                                   sqimap_encode_mailbox_name($mailbox),
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
            do_hook_function('rename_or_delete_folder', $args = array($mailbox, 'delete', ''));
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
        do_hook_function('rename_or_delete_folder',$args = array($old_name, 'rename', $new_name));
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
                do_hook_function('rename_or_delete_folder',
                                 $args = array($box[$p], 'rename', $new_sub));
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
 * @param $line
 * @param $line_lsub
 * @return array
 * @since 1.0 or older
 * @todo document id and flags keys in boxes array and function arguments.
 */
function sqimap_mailbox_parse ($line, $line_lsub) {
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
        $mailbox  = /*trim(*/$line_lsub[$g]/*)*/;
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
        if (isset($line[$g])) {
            ereg("\(([^)]*)\)",$line[$g],$regs);
            // FIXME Flags do contain the \ character. \NoSelect \NoInferiors
            // and $MDNSent <= last one doesn't have the \
            // It's better to follow RFC3501 instead of using our own naming.
            $flags = trim(strtolower(str_replace('\\', '',$regs[1])));
            if ($flags) {
                $boxesall[$g]['flags'] = explode(' ', $flags);
            }
        }
    }
    return $boxesall;
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
    global $username, $data_dir;
    $mbox_options = '';
    if ( $use_long_format ) {
        $shorten_box_names = 0;
    } else {
        $shorten_box_names = getPref($data_dir, $username, 'mailbox_select_style', SMPREF_OFF);
    }

    if ($boxes == 0) {
        $boxes = sqimap_mailbox_list($imap_stream);
    }

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
                  case 2:   /* delimited, style = 2 */
                    $box2 = str_replace('&amp;nbsp;&amp;nbsp;', '.&nbsp;', htmlspecialchars($boxes_part['formatted']));
                    break;
                  case 1:   /* indent, style = 1 */
                    $box2 = str_replace('&amp;nbsp;&amp;nbsp;', '&nbsp;&nbsp;', htmlspecialchars($boxes_part['formatted']));
                    break;
                  default:  /* default, long names, style = 0 */
                    $box2 = str_replace(' ', '&nbsp;', htmlspecialchars(imap_utf7_decode_local($boxes_part['unformatted-disp'])));
                    break;
                }
            }
            if ($show_selected != 0 && in_array($lowerbox, $show_selected) ) {
                $mbox_options .= '<option value="' . htmlspecialchars($box) .'" selected="selected">'.$box2.'</option>' . "\n";
            } else {
                $mbox_options .= '<option value="' . htmlspecialchars($box) .'">'.$box2.'</option>' . "\n";
            }
        }
    }
    return $mbox_options;
}

/**
 * Returns sorted mailbox lists in several different ways.
 * See comment on sqimap_mailbox_parse() for info about the returned array.
 * @param resource $imap_stream imap connection resource
 * @param boolean $force force update of mailbox listing. available since 1.4.2 and 1.5.0
 * @return array list of mailboxes
 * @since 1.0 or older
 */
function sqimap_mailbox_list($imap_stream, $force=false) {
    if (!sqgetGlobalVar('boxesnew',$boxesnew,SQ_SESSION) || $force) {
        global $data_dir, $username, $list_special_folders_first,
               $folder_prefix, $trash_folder, $sent_folder, $draft_folder,
               $move_to_trash, $move_to_sent, $save_as_draft,
               $delimiter, $noselect_fix_enable, $imap_server_type,
               $show_only_subscribed_folders;
        $inbox_subscribed = false;
        $listsubscribed = sqimap_capability($imap_stream,'LIST-SUBSCRIBED');

        require_once(SM_PATH . 'include/load_prefs.php');

        if (!$show_only_subscribed_folders) {
            $lsub = 'LIST';
        } elseif ($listsubscribed) {
            $lsub = 'LIST (SUBSCRIBED)';
        } else {
            $lsub = 'LSUB';
        }

        if ($noselect_fix_enable) {
            $lsub_args = "$lsub \"$folder_prefix\" \"*%\"";
        } else {
            $lsub_args = "$lsub \"$folder_prefix\" \"*\"";
        }
        /* LSUB array */
        $lsub_ary = sqimap_run_command ($imap_stream, $lsub_args,
                                        true, $response, $message);
        $lsub_ary = compact_mailboxes_response($lsub_ary);

        $sorted_lsub_ary = array();
        for ($i = 0, $cnt = count($lsub_ary);$i < $cnt; $i++) {

            $temp_mailbox_name = find_mailbox_name($lsub_ary[$i]);
            $sorted_lsub_ary[] = $temp_mailbox_name;
            if (!$inbox_subscribed && strtoupper($temp_mailbox_name) == 'INBOX') {
                $inbox_subscribed = true;
            }
        }

        /* natural sort mailboxes */
        if (isset($sorted_lsub_ary)) {
            usort($sorted_lsub_ary, 'strnatcasecmp');
        }
        /*
         * The LSUB response doesn't provide us information about \Noselect
         * mail boxes. The LIST response does, that's why we need to do a LIST
         * call to retrieve the flags for the mailbox
           * Note: according RFC2060 an imap server may provide \NoSelect flags in the LSUB response.
           * in other words, we cannot rely on it.
         */
        $sorted_list_ary = array();
 //       if (!$listsubscribed) {
          for ($i=0; $i < count($sorted_lsub_ary); $i++) {
            if (substr($sorted_lsub_ary[$i], -1) == $delimiter) {
                $mbx = substr($sorted_lsub_ary[$i], 0, strlen($sorted_lsub_ary[$i])-1);
            }
            else {
                $mbx = $sorted_lsub_ary[$i];
            }

            $read = sqimap_run_command ($imap_stream, 'LIST "" ' . sqimap_encode_mailbox_name($mbx),
                                        true, $response, $message);

            $read = compact_mailboxes_response($read);

            if (isset($read[0])) {
                $sorted_list_ary[$i] = $read[0];
            } else {
                $sorted_list_ary[$i] = '';
            }
          }
 //       }
        /*
         * Just in case they're not subscribed to their inbox,
         * we'll get it for them anyway
         */
        if (!$inbox_subscribed) {
            $inbox_ary = sqimap_run_command ($imap_stream, 'LIST "" "INBOX"',
                                             true, $response, $message);
            $sorted_list_ary[] = implode('',compact_mailboxes_response($inbox_ary));
            $sorted_lsub_ary[] = find_mailbox_name($inbox_ary[0]);
        }

        $boxesall = sqimap_mailbox_parse ($sorted_list_ary, $sorted_lsub_ary);

        /* Now, lets sort for special folders */
        $boxesnew = $used = array();

        /* Find INBOX */
        $cnt = count($boxesall);
        $used = array_pad($used,$cnt,false);
        for($k = 0; $k < $cnt; ++$k) {
            if (strtolower($boxesall[$k]['unformatted']) == 'inbox') {
                $boxesnew[] = $boxesall[$k];
                $used[$k] = true;
                break;
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
            if (!$used[$k] && isBoxBelow(strtolower($boxesall[$k]['unformatted']), 'inbox') &&
            strtolower($boxesall[$k]['unformatted']) != 'inbox') {
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
        sqsession_register($boxesnew,'boxesnew');
    }
    return $boxesnew;
}

/**
 * Returns a list of all folders, subscribed or not
 * @param stream $imap_stream imap connection resource
 * @return array see sqimap_mailbox_parse()
 * @since 1.0 or older
 */
function sqimap_mailbox_list_all($imap_stream) {
    global $list_special_folders_first, $folder_prefix, $delimiter;

    $read_ary = sqimap_run_command($imap_stream,"LIST \"$folder_prefix\" *",true,$response, $message,false);
    $read_ary = compact_mailboxes_response($read_ary);

    $g = 0;
    $fld_pre_length = strlen($folder_prefix);
    for ($i = 0, $cnt = count($read_ary); $i < $cnt; $i++) {
        /* Store the raw IMAP reply */
        $boxes[$g]['raw'] = $read_ary[$i];

        /* Count number of delimiters ($delimiter) in folder name */
        $mailbox = find_mailbox_name($read_ary[$i]);
        $dm_count =  substr_count($mailbox, $delimiter);
        if (substr($mailbox, -1) == $delimiter) {
            /* If name ends in delimiter - decrement count by one */
            $dm_count--;
        }

        /* Format folder name, but only if it's a INBOX.* or has a parent. */
        $boxesallbyname[$mailbox] = $g;
        $parentfolder = readMailboxParent($mailbox, $delimiter);
        if((eregi('^inbox'.quotemeta($delimiter), $mailbox)) ||
           (ereg('^'.$folder_prefix, $mailbox)) ||
           ( isset($boxesallbyname[$parentfolder]) && (strlen($parentfolder) > 0) ) ) {
            if ($dm_count) {
                $boxes[$g]['formatted']  = str_repeat('&nbsp;&nbsp;', $dm_count);
            } else {
                $boxes[$g]['formatted'] = '';
            }
            $boxes[$g]['formatted'] .= imap_utf7_decode_local(readShortMailboxName($mailbox, $delimiter));
        } else {
            $boxes[$g]['formatted']  = imap_utf7_decode_local($mailbox);
        }

        $boxes[$g]['unformatted-dm'] = $mailbox;
        if (substr($mailbox, -1) == $delimiter) {
            $mailbox = substr($mailbox, 0, strlen($mailbox) - 1);
        }
        $boxes[$g]['unformatted'] = $mailbox;
        $boxes[$g]['unformatted-disp'] = substr($mailbox,$fld_pre_length);

        $boxes[$g]['id'] = $g;

        /* Now lets get the flags for this mailbox */
        $read_mlbx = $read_ary[$i];
        $flags = substr($read_mlbx, strpos($read_mlbx, '(')+1);
        $flags = substr($flags, 0, strpos($flags, ')'));
        $flags = str_replace('\\', '', $flags);
        $flags = trim(strtolower($flags));
        if ($flags) {
            $boxes[$g]['flags'] = explode(' ', $flags);
        } else {
            $boxes[$g]['flags'] = array();
        }
        $g++;
    }
    if(is_array($boxes)) {
        sort ($boxes);
    }

    return $boxes;
}

/**
 * Fills mailbox object
 *
 * Some code fragments are present in 1.3.0 - 1.4.4.
 * @param stream $imap_stream imap connection resource
 * @return object see mailboxes class.
 * @since 1.5.0
 */
function sqimap_mailbox_tree($imap_stream) {
    global $default_folder_prefix, $data_dir, $username, $list_special_folders_first,
        $folder_prefix, $delimiter, $trash_folder, $move_to_trash,
        $imap_server_type, $show_only_subscribed_folders;

    // TODO: implement mailbox tree caching. maybe store object in session?

    $noselect = false;
    $noinferiors = false;

    require_once(SM_PATH . 'include/load_prefs.php');

    if ($show_only_subscribed_folders) {
        $lsub_cmd = 'LSUB';
    } else {
        $lsub_cmd = 'LIST';
    }

    /* LSUB array */
    $lsub_ary = sqimap_run_command ($imap_stream, "$lsub_cmd \"$folder_prefix\" \"*\"",
                                    true, $response, $message);
    $lsub_ary = compact_mailboxes_response($lsub_ary);

    /* Check to see if we have an INBOX */
    $has_inbox = false;

    for ($i = 0, $cnt = count($lsub_ary); $i < $cnt; $i++) {
        if (preg_match("/^\*\s+$lsub_cmd.*\s\"?INBOX\"?\s*$/i",$lsub_ary[$i])) {
            $lsub_ary[$i] = strtoupper($lsub_ary[$i]);
            // in case of an unsubscribed inbox an imap server can
            // return the inbox in the lsub results with a \NoSelect
            // flag.
            if (!preg_match("/\*\s+$lsub_cmd\s+\(.*\\\\NoSelect.*\).*/i",$lsub_ary[$i])) {
                $has_inbox = true;
            } else {
                // remove the result and request it again  with a list
                // response at a later stage.
                unset($lsub_ary[$i]);
                // re-index the array otherwise the addition of the LIST
                // response will fail in PHP 4.1.2 and probably other older versions
                $lsub_ary = array_values($lsub_ary);
            }
            break;
        }
    }

    if ($has_inbox == false) {
        // do a list request for inbox because we should always show
        // inbox even if the user isn't subscribed to it.
        $inbox_ary = sqimap_run_command ($imap_stream, 'LIST "" "INBOX"',
                                         true, $response, $message);
        $inbox_ary = compact_mailboxes_response($inbox_ary);
        if (count($inbox_ary)) {
            $lsub_ary[] = $inbox_ary[0];
        }
    }

    /*
     * Section about removing the last element was removed
     * We don't return "* OK" anymore from sqimap_read_data
     */

    $sorted_lsub_ary = array();
    $cnt = count($lsub_ary);
    for ($i = 0; $i < $cnt; $i++) {
        $mbx = find_mailbox_name($lsub_ary[$i]);

        // only do the noselect test if !uw, is checked later. FIX ME see conf.pl setting
        if ($imap_server_type != "uw") {
            $noselect = check_is_noselect($lsub_ary[$i]);
            $noinferiors = check_is_noinferiors($lsub_ary[$i]);
        }
        if (substr($mbx, -1) == $delimiter) {
            $mbx = substr($mbx, 0, strlen($mbx) - 1);
        }
        $sorted_lsub_ary[] = array ('mbx' => $mbx, 'noselect' => $noselect, 'noinferiors' => $noinferiors);
    }
    // FIX ME this requires a config setting inside conf.pl instead of checking on server type
    if ($imap_server_type == "uw") {
        $aQuery = array();
        $aTag = array();
        // prepare an array with queries
        foreach ($sorted_lsub_ary as $aMbx) {
            $mbx = stripslashes($aMbx['mbx']);
            sqimap_prepare_pipelined_query('LIST "" ' . sqimap_encode_mailbox_name($mbx), $tag, $aQuery, false);
            $aTag[$tag] = $mbx;
        }
        $sorted_lsub_ary = array();
        // execute all the queries at once
        $aResponse = sqimap_run_pipelined_command ($imap_stream, $aQuery, false, $aServerResponse, $aServerMessage);
        foreach($aTag as $tag => $mbx) {
            if ($aServerResponse[$tag] == 'OK') {
                $sResponse = implode('', $aResponse[$tag]);
                $noselect = check_is_noselect($sResponse);
                $noinferiors = check_is_noinferiors($sResponse);
                $sorted_lsub_ary[] = array ('mbx' => $mbx, 'noselect' => $noselect, 'noinferiors' => $noinferiors);
            }
        }
        $cnt = count($sorted_lsub_ary);
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
   if (strtoupper($mbx_tree->mailboxname_full) == 'INBOX')
       $mbx_tree->mailboxname_sub = _("INBOX");
   else
       $mbx_tree->mailboxname_sub = imap_utf7_decode_local($mbx_tree->mailboxname_sub);
   if ($mbx_tree->mbxs) {
      $iCnt = count($mbx_tree->mbxs);
      for ($i=0;$i<$iCnt;++$i) {
          $mbxs_tree->mbxs[$i] = sqimap_utf7_decode_mbx_tree($mbx_tree->mbxs[$i]);
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
                   $query = 'STATUS ' . sqimap_encode_mailbox_name($mbx) . ' (MESSAGES UNSEEN)';
                } else {
                   $query = 'STATUS ' . sqimap_encode_mailbox_name($mbx) . ' (UNSEEN)';
                }
                sqimap_prepare_pipelined_query($query,$tag,$aQuery,false);
            } else {
                $oMbx->unseen = $oMbx->total = false;
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
}

?>