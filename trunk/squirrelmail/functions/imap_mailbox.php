<?php

/**
 * imap_mailbox.php
 *
 * Copyright (c) 1999-2003 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This impliments all functions that manipulate mailboxes
 *
 * $Id$
 */
require_once(SM_PATH . 'functions/imap_utf7_local.php');

global $boxesnew;

class mailboxes {
    var $mailboxname_full = '', $mailboxname_sub= '', $is_noselect = false, 
        $is_special = false, $is_root = false, $is_inbox = false, $is_sent = false,
        $is_trash = false, $is_draft = false,  $mbxs = array(), 
        $unseen = false, $total = false;

    function addMbx($mbx, $delimiter, $start, $specialfirst) {
        $ary = explode($delimiter, $mbx->mailboxname_full);
        $mbx_parent = &$this;
        for ($i = $start, $c = count($ary)-1; $i < $c; $i++) {
            $mbx_childs = &$mbx_parent->mbxs;
            $found = false;
            if ($mbx_childs) {
                foreach ($mbx_childs as $key => $parent) {
                    if ($parent->mailboxname_sub == $ary[$i]) {
                        $mbx_parent = &$mbx_parent->mbxs[$key];
                        $found = true;
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
    if ($acmp == $bcmp) return 0;
    return ($acmp > $bcmp) ? 1: -1;
}

function find_mailbox_name ($mailbox) {
    if (preg_match('/\*.+\"([^\r\n\"]*)\"[\s\r\n]*$/', $mailbox, $regs)) 
        return $regs[1];
    if (ereg(" *\"([^\r\n\"]*)\"[ \r\n]*$", $mailbox, $regs))
        return $regs[1];
    ereg(" *([^ \r\n\"]*)[ \r\n]*$",$mailbox,$regs);
    return $regs[1];
}

function check_is_noselect ($lsub_line) {
    return preg_match("/^\* LSUB \([^\)]*\\Noselect[^\)]*\)/i", $lsub_line);
}

/**
 * If $haystack is a full mailbox name, and $needle is the mailbox
 * separator character, returns the second last part of the full
 * mailbox name (i.e. the mailbox's parent mailbox)
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
        if (!substr($parentbox,-1) == $delimiter) {
            $parentbox.=$delimiter;
        }
        if (substr($subbox,0,strlen($parentbox)) == $parentbox) {
            return true;
        } else {
            return false;
        }
}

/* Defines special mailboxes */
function isSpecialMailbox( $box ) {
    global $trash_folder, $sent_folder, $draft_folder,
           $move_to_trash, $move_to_sent, $save_as_draft;

    $ret = ( (strtolower($box) == 'inbox') ||
             isTrashMailbox($box) || isSentMailbox($box) || isDraftMailbox($box) );

    if ( !$ret ) {
        $ret = do_hook_function( 'special_mailbox', $box );
    }
    return $ret;
}

function isTrashMailbox ($box) {
    global $trash_folder, $move_to_trash;
    return $move_to_trash && $trash_folder &&
           ( $box == $trash_folder || isBoxBelow($box, $trash_folder) );
}

function isSentMailbox($box) {
   global $sent_folder, $move_to_sent;
   return $move_to_sent && $sent_folder &&
          ( $box == $sent_folder || isBoxBelow($box, $sent_folder) );
}

function isDraftMailbox($box) {
   global $draft_folder, $save_as_draft;
   return $save_as_draft &&
          ( $box == $draft_folder || isBoxBelow($box, $draft_folder) );
}

/* Expunges a mailbox */
function sqimap_mailbox_expunge ($imap_stream, $mailbox, $handle_errors = true, $id='') {
    global $uid_support;
    if ($id) {
        if (is_array($id)) {
            $id = sqimap_message_list_squisher($id);
        }
        $id = ' '.$id;
        $uid = $uid_support;
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

/* Checks whether or not the specified mailbox exists */
function sqimap_mailbox_exists ($imap_stream, $mailbox) {
    if (!isset($mailbox)) {
        return false;
    }
    $mbx = sqimap_run_command($imap_stream, "LIST \"\" \"$mailbox\"",
                              true, $response, $message);
    return isset($mbx[0]);
}

/* Selects a mailbox */
function sqimap_mailbox_select ($imap_stream, $mailbox) {
    global $auto_expunge;

    if ($mailbox == 'None') {
        return;
    }

    $read = sqimap_run_command($imap_stream, "SELECT \"$mailbox\"",
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
                $result['PERMANENTFLAGS'] = $regs[1];
            } else if (preg_match("/FLAGS(.*)/i",$read[$i], $regs)) {
                $regs[1]=trim(preg_replace (  array ("/\(/","/\)/") ,'', $regs[1])) ;
                $result['FLAGS'] = $regs[1];
            }
        }
    }
    if (preg_match('/^\[(.+)\]/',$message, $regs)) {
        $result['RIGHTS']=$regs[1];
    }

    if ($auto_expunge) {
        $tmp = sqimap_run_command($imap_stream, 'EXPUNGE', false, $a, $b);
    }
    return $result;
}

/* Creates a folder */
function sqimap_mailbox_create ($imap_stream, $mailbox, $type) {
    global $delimiter;
    if (strtolower($type) == 'noselect') {
        $mailbox .= $delimiter;
    }

    $read_ary = sqimap_run_command($imap_stream, "CREATE \"$mailbox\"",
                                   true, $response, $message);
    sqimap_subscribe ($imap_stream, $mailbox);
}

/* Subscribes to an existing folder */
function sqimap_subscribe ($imap_stream, $mailbox) {
    $read_ary = sqimap_run_command($imap_stream, "SUBSCRIBE \"$mailbox\"",
                                   true, $response, $message);
}

/* Unsubscribes to an existing folder */
function sqimap_unsubscribe ($imap_stream, $mailbox) {
    $read_ary = sqimap_run_command($imap_stream, "UNSUBSCRIBE \"$mailbox\"",
                                   true, $response, $message);
}

/* Deletes the given folder */
function sqimap_mailbox_delete ($imap_stream, $mailbox) {
    global $data_dir, $username;
    $read_ary = sqimap_run_command($imap_stream, "DELETE \"$mailbox\"",
                                   true, $response, $message);
    sqimap_unsubscribe ($imap_stream, $mailbox);
    do_hook_function('rename_or_delete_folder', $args = array($mailbox, 'delete', ''));
    removePref($data_dir, $username, "thread_$mailbox");
}

/* Determines if the user is subscribed to the folder or not */
function sqimap_mailbox_is_subscribed($imap_stream, $folder) {
    $boxesall = sqimap_mailbox_list ($imap_stream);
    foreach ($boxesall as $ref) {
        if ($ref['unformatted'] == $folder) {
            return true;
        }
    }
    return false;
}

/* Renames a mailbox */
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

        $boxesall = sqimap_mailbox_list($imap_stream);
        $cmd = 'RENAME "' . $old_name . '" "' . $new_name . '"';
        $data = sqimap_run_command($imap_stream, $cmd, true, $response, $message);
        sqimap_unsubscribe($imap_stream, $old_name.$postfix);
        $oldpref = getPref($data_dir, $username, 'thread_'.$old_name.$postfix);
        removePref($data_dir, $username, 'thread_'.$old_name.$postfix);
        sqimap_subscribe($imap_stream, $new_name.$postfix);
        setPref($data_dir, $username, 'thread_'.$new_name.$postfix, $oldpref);
        do_hook_function('rename_or_delete_folder',$args = array($old_name, 'rename', $new_name));
        $l = strlen( $old_name ) + 1;
        $p = 'unformatted';

        foreach ($boxesall as $box) {
            if (substr($box[$p], 0, $l) == $old_name . $delimiter) {
                $new_sub = $new_name . $delimiter . substr($box[$p], $l);
                if ($imap_server_type == 'cyrus') {
                    $cmd = 'RENAME "' . $box[$p] . '" "' . $new_sub . '"';
                    $data = sqimap_run_command($imap_stream, $cmd, true,
                                               $response, $message);
                }
                sqimap_unsubscribe($imap_stream, $box[$p]);
                $oldpref = getPref($data_dir, $username, 'thread_'.$box[$p]);
                removePref($data_dir, $username, 'thread_'.$box[$p]);
                sqimap_subscribe($imap_stream, $new_sub);
                setPref($data_dir, $username, 'thread_'.$new_sub, $oldpref);
                do_hook_function('rename_or_delete_folder',
                                 $args = array($box[$p], 'rename', $new_sub));
            }
        }
    }
}

/*
 * Formats a mailbox into 4 parts for the $boxesall array
 *
 * The four parts are:
 *
 *     raw            - Raw LIST/LSUB response from the IMAP server
 *     formatted      - nicely formatted folder name
 *     unformatted    - unformatted, but with delimiter at end removed
 *     unformatted-dm - folder name as it appears in raw response
 *     unformatted-disp - unformatted without $folder_prefix
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
        $mailbox  = trim($line_lsub[$g]);
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
            $flags = trim(strtolower(str_replace('\\', '',$regs[1])));
            if ($flags) {
                $boxesall[$g]['flags'] = explode(' ', $flags);
            }
        }
    }
    return $boxesall;
}

/*
 * Sorting function used to sort mailbox names.
 *     + Original patch from dave_michmerhuizen@yahoo.com
 *     + Allows case insensitivity when sorting folders
 *     + Takes care of the delimiter being sorted to the end, causing
 *       subfolders to be listed in below folders that are prefixed
 *       with their parent folders name.
 *
 *       For example: INBOX.foo, INBOX.foobar, and INBOX.foo.bar
 *       Without special sort function: foobar between foo and foo.bar
 *       With special sort function: foobar AFTER foo and foo.bar :)
 */
function user_strcasecmp($a, $b) {
    return  strnatcasecmp($a, $b);
}

/*
 * Returns list of options (to be echoed into select statement
 * based on available mailboxes and separators
 * Caller should surround options with <SELECT..> </SELECT> and
 * any formatting.
 *   $imap_stream - $imapConnection to query for mailboxes
 *   $show_selected - array containing list of mailboxes to pre-select (0 if none)
 *   $folder_skip - array of folders to keep out of option list (compared in lower)
 *   $boxes - list of already fetched boxes (for places like folder panel, where
 *            you know these options will be shown 3 times in a row.. (most often unset).
 */
function sqimap_mailbox_option_list($imap_stream, $show_selected = 0, $folder_skip = 0, $boxes = 0 ) {
    global $username, $data_dir;
    $mbox_options = '';

    $shorten_box_names = getPref($data_dir, $username, 'mailbox_select_style', SMPREF_OFF);

    if ($boxes == 0) {
        $boxes = sqimap_mailbox_list($imap_stream);
    }
    foreach ($boxes as $boxes_part) {
        if (!in_array('noselect', $boxes_part['flags'])) {
            $box = $boxes_part['unformatted'];
            $lowerbox = strtolower($box);

            if ($folder_skip != 0 && in_array($lowerbox, $folder_skip) ) {
                continue;
            }
            if ($lowerbox == 'inbox'){
                $box2 = _("INBOX");
            } else if ( $shorten_box_names == 2 ) {  /* delimited, style = 2 */
                $box2 = str_replace('&nbsp;&nbsp;', '.&nbsp;', $boxes_part['formatted']);
            } else if ( $shorten_box_names == 1 ) {     /* indent, style = 1 */
                $box2 = $boxes_part['formatted'];
            } else  {                      /* default, long names, style = 0 */
                $box2 = str_replace(' ', '&nbsp;', imap_utf7_decode_local($boxes_part['unformatted-disp']));
            }
            if ($show_selected != 0 && in_array($lowerbox, $show_selected) ) {
                $mbox_options .= '<OPTION VALUE="'.$box.'" SELECTED>'.$box2.'</OPTION>' . "\n";
            } else {
                $mbox_options .= '<OPTION VALUE="'.$box.'">'.$box2.'</OPTION>' . "\n";
            }
        }
    }
    return $mbox_options;
}

/*
 * Returns sorted mailbox lists in several different ways. 
 * See comment on sqimap_mailbox_parse() for info about the returned array.
 */
function sqimap_mailbox_list($imap_stream) {
    global $default_folder_prefix;

    if (!isset($boxesnew)) {
        global $data_dir, $username, $list_special_folders_first,
               $folder_prefix, $trash_folder, $sent_folder, $draft_folder,
               $move_to_trash, $move_to_sent, $save_as_draft,
               $delimiter, $noselect_fix_enable;

        $inbox_in_list = false;
        $inbox_subscribed = false;

        require_once(SM_PATH . 'include/load_prefs.php');

        if ($noselect_fix_enable) {
            $lsub_args = "LSUB \"$folder_prefix\" \"*%\"";
        } else {
            $lsub_args = "LSUB \"$folder_prefix\" \"*\"";
        }
        /* LSUB array */
        $lsub_ary = sqimap_run_command ($imap_stream, $lsub_args,
                                        true, $response, $message);

        $sorted_lsub_ary = array();
        for ($i = 0, $cnt = count($lsub_ary);$i < $cnt; $i++) {
            /*
             * Workaround for mailboxes returned as literal
             * Doesn't work if the mailbox name is multiple lines 
	     * (larger then fgets buffer)
             */
            if (isset($lsub_ary[$i + 1]) && substr($lsub_ary[$i],-3) == "}\r\n") {
		if (ereg("^(\\* [A-Z]+.*)\\{[0-9]+\\}([ \n\r\t]*)$",
                     $lsub_ary[$i], $regs)) {
            	    $i++;
            	    $lsub_ary[$i] = $regs[1] . '"' . addslashes(trim($lsub_ary[$i])) . '"' . $regs[2];
		}
            }
            $temp_mailbox_name = find_mailbox_name($lsub_ary[$i]);
            $sorted_lsub_ary[] = $temp_mailbox_name;
            if (!$inbox_subscribed && strtoupper($temp_mailbox_name) == 'INBOX') {
                $inbox_subscribed = true;
            }
        }
	/* remove duplicates */
	$sorted_lsub_ary = array_unique($sorted_lsub_ary);

	/* natural sort mailboxes */
        if (isset($sorted_lsub_ary)) {
            usort($sorted_lsub_ary, 'user_strcasecmp');
        }
	/*
	 * The LSUB response doesn't provide us information about \Noselect
	 * mail boxes. The LIST response does, that's why we need to do a LIST
	 * call to retrieve the flags for the mailbox
           * Note: according RFC2060 an imap server may provide \NoSelect flags in the LSUB response.
           * in other words, we cannot rely on it.
	 */
        $sorted_list_ary = array();
        for ($i=0; $i < count($sorted_lsub_ary); $i++) {
            if (substr($sorted_lsub_ary[$i], -1) == $delimiter) {
                $mbx = substr($sorted_lsub_ary[$i], 0, strlen($sorted_lsub_ary[$i])-1);
            }
            else {
                $mbx = $sorted_lsub_ary[$i];
            }

            $read = sqimap_run_command ($imap_stream, "LIST \"\" \"$mbx\"",
                                        true, $response, $message);

            /* Another workaround for literals */

            if (isset($read[1]) && substr($read[1],-3) == "}\r\n") {
		if (ereg("^(\\* [A-Z]+.*)\\{[0-9]+\\}([ \n\r\t]*)$",
                     $read[0], $regs)) {
            	    $read[0] = $regs[1] . '"' . addslashes(trim($read[1])) . '"' . $regs[2];
		}
            }

            if (isset($read[0])) {
                $sorted_list_ary[$i] = $read[0];
            } else {
                $sorted_list_ary[$i] = '';
            }
        }

        /*
         * Just in case they're not subscribed to their inbox,
         * we'll get it for them anyway
         */
        if (!$inbox_subscribed) {
            $inbox_ary = sqimap_run_command ($imap_stream, "LIST \"\" \"INBOX\"",
                                             true, $response, $message);
            /* Another workaround for literals */
            if (isset($inbox_ary[1]) && substr($inbox_ary[0],-3) == "}\r\n") {
		if (ereg("^(\\* [A-Z]+.*)\\{[0-9]+\\}([ \n\r\t]*)$",
                     $inbox_ary[0], $regs)) {
            	    $inbox_ary[0] = $regs[1] . '"' . addslashes(trim($inbox_ary[1])) .
                                '"' . $regs[2];
		}
            }
            $sorted_list_ary[] = $inbox_ary[0];
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

        /* Rest of the folders */
        for($k = 0; $k < $cnt; $k++) {
            if (!$used[$k]) {
                $boxesnew[] = $boxesall[$k];
            }
        }
    }
    return $boxesnew;
}

/*
 *  Returns a list of all folders, subscribed or not
 */
function sqimap_mailbox_list_all($imap_stream) {
    global $list_special_folders_first, $folder_prefix, $delimiter;

    $ssid = sqimap_session_id();
    $lsid = strlen( $ssid );
    fputs ($imap_stream, $ssid . " LIST \"$folder_prefix\" *\r\n");
    $read_ary = sqimap_read_data ($imap_stream, $ssid, true, $response, $message);
    $g = 0;
    $phase = 'inbox';
    $fld_pre_length = strlen($folder_prefix);

    for ($i = 0, $cnt = count($read_ary); $i < $cnt; $i++) {
        /* Another workaround for EIMS */
        if (isset($read_ary[$i + 1]) &&
            ereg("^(\\* [A-Z]+.*)\\{[0-9]+\\}([ \n\r\t]*)$",
                 $read_ary[$i], $regs)) {
            $i ++;
            $read_ary[$i] = $regs[1] . '"' . addslashes(trim($read_ary[$i])) . '"' . $regs[2];
        }
        if (substr($read_ary[$i], 0, $lsid) != $ssid ) {
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

//            $read_mlbx = sqimap_run_command ($imap_stream, "LIST \"\" \"$mailbox\"",
//                                             true, $response, $message);

            /* Another workaround for EIMS */
//            if (isset($read_mlbx[1]) &&
//                ereg("^(\\* [A-Z]+.*)\\{[0-9]+\\}([ \n\r\t]*)$", $read_mlbx[0], $regs)) {
//                $read_mlbx[0] = $regs[1] . '"' . addslashes(trim($read_mlbx[1])) . '"' . $regs[2];
//            }
//            echo  $read_mlbx[0] .' raw 2 <br>';

            $flags = substr($read_mlbx, strpos($read_mlbx, '(')+1);
            $flags = substr($flags, 0, strpos($flags, ')'));
            $flags = str_replace('\\', '', $flags);
            $flags = trim(strtolower($flags));
            if ($flags) {
                $boxes[$g]['flags'] = explode(' ', $flags);
            } else {
                $boxes[$g]['flags'] = array();
            }
        }
        $g++;
    }
    if(is_array($boxes)) {
        sort ($boxes);
    }

    return $boxes;
}

function sqimap_mailbox_tree($imap_stream) {
    global $boxesnew, $default_folder_prefix, $unseen_notify, $unseen_type;
    if (!isset($boxesnew)) {

        global $data_dir, $username, $list_special_folders_first,
               $folder_prefix, $delimiter, $trash_folder, $move_to_trash;


        $inbox_in_list = false;
        $inbox_subscribed = false;

        require_once(SM_PATH . 'include/load_prefs.php');

        /* LSUB array */
        $lsub_ary = sqimap_run_command ($imap_stream, "LSUB \"$folder_prefix\" \"*\"",
                                        true, $response, $message);

        /*
         * Section about removing the last element was removed 
         * We don't return "* OK" anymore from sqimap_read_data
         */
        $sorted_lsub_ary = array();
        $cnt = count($lsub_ary);
        for ($i = 0; $i < $cnt; $i++) {
            /*
             * Workaround for EIMS
             * Doesn't work if the mailbox name is multiple lines
             */
            if (isset($lsub_ary[$i + 1]) &&
                ereg("^(\\* [A-Z]+.*)\\{[0-9]+\\}([ \n\r\t]*)$",
                     $lsub_ary[$i], $regs)) {
                $i++;
                $lsub_ary[$i] = $regs[1] . '"' . addslashes(trim($lsub_ary[$i])) . '"' . $regs[2];
            }

            /*
            if (preg_match("/^\*\s+LSUB\s+\((.*)\)\s+\"(.*)\"\s+\"?(.+(?=\")|.+).*$/",$lsub_ary[$i],$regs)) {
                $flag = $regs[1];
                $mbx = trim($regs[3]);
                $sorted_lsub_ary[] = array ('mbx' => $mbx, 'flag' => $flag); 
            }
            */
            $mbx = find_mailbox_name($lsub_ary[$i]);
            $noselect = check_is_noselect($lsub_ary[$i]);
            if (substr($mbx, -1) == $delimiter) {
                $mbx = substr($mbx, 0, strlen($mbx) - 1);
            }
            $sorted_lsub_ary[] = array ('mbx' => $mbx, 'noselect' => $noselect); 
        }
        array_multisort($sorted_lsub_ary, SORT_ASC, SORT_REGULAR);

        for($i = 0; $i < $cnt; $i++) {
            if ($sorted_lsub_ary[$i]['mbx'] == 'INBOX') {
                $inbox_in_list = true;
                break;
            }
        }

        /*
         * Just in case they're not subscribed to their inbox,
         * we'll get it for them anyway
         */
        if (!$inbox_in_list) {
            $inbox_ary = sqimap_run_command ($imap_stream, "LIST \"\" \"INBOX\"",
                                             true, $response, $message);
            /* Another workaround for EIMS */
            if (isset($inbox_ary[1]) &&
                ereg("^(\\* [A-Z]+.*)\\{[0-9]+\\}([ \n\r\t]*)$",
                     $inbox_ary[0], $regs)) {
                $inbox_ary[0] = $regs[1] . '"' . addslashes(trim($inbox_ary[1])) .
                                '"' . $regs[2];
            }
            $mbx = find_mailbox_name($inbox_ary[0]);
            if (substr($mbx, -1) == $delimiter) {
                $mbx = substr($mbx, 0, strlen($mbx) - 1);
            }
            if ($mbx == 'INBOX') {
                $sorted_lsub_ary[] = array ('mbx' => $mbx, 'flag' => ''); 
                sqimap_subscribe($imap_stream, 'INBOX');
                $cnt++;
            }

            /*
            if (preg_match("/^\*\s+LIST\s+\((.*)\)\s+\"(.*)\"\s+\"?(.+(?=\")|.+).*$/",$inbox_ary[0],$regs)) {
                $flag = $regs[1];
                $mbx = trim($regs[3]);
                if (substr($mbx, -1) == $delimiter) {
                    $mbx = substr($mbx, 0, strlen($mbx) - 1);
                }
                $sorted_lsub_ary[] = array ('mbx' => $mbx, 'flag' => $flag); 
            }
            */
        }
        for ($i = 0 ; $i < $cnt; $i++) {
            $mbx = $sorted_lsub_ary[$i]['mbx'];
            if (($unseen_notify == 2 && $mbx == 'INBOX') ||
                ($unseen_notify == 3) ||
                ($move_to_trash && ($mbx == $trash_folder))) {
                if($sorted_lsub_ary[$i]['noselect']) {
                    $sorted_lsub_ary[$i]['unseen'] = 0;
                } else {
                    $sorted_lsub_ary[$i]['unseen'] = 
                        sqimap_unseen_messages($imap_stream, $mbx);
                }
                if (($unseen_type == 2) ||
                    ($move_to_trash && ($mbx == $trash_folder)) ||
                    ($mbx == $trash_folder)) {
                    if($sorted_lsub_ary[$i]['noselect']) {
                        $sorted_lsub_ary[$i]['nummessages'] = 0;
                    } else {
                        $sorted_lsub_ary[$i]['nummessages'] =
                            sqimap_get_num_messages($imap_stream, $mbx);
                    }
                }
            }
        }
        $boxesnew = sqimap_fill_mailbox_tree($sorted_lsub_ary);
        return $boxesnew;
    }
}


function sqimap_fill_mailbox_tree($mbx_ary, $mbxs=false) {
    global $data_dir, $username, $list_special_folders_first,
           $folder_prefix, $trash_folder, $sent_folder, $draft_folder,
           $move_to_trash, $move_to_sent, $save_as_draft,
           $delimiter;

    $special_folders = array ('INBOX', $sent_folder, $draft_folder, $trash_folder);

    /* create virtual root node */
    $mailboxes= new mailboxes();
    $mailboxes->is_root = true;
    $trail_del = false;
    if (isset($folder_prefix) && $folder_prefix != '') {
        $start = substr_count($folder_prefix,$delimiter);
        if (strrpos($folder_prefix, $delimiter) == (strlen($folder_prefix)-1)) {
            $trail_del = true;
            $mailboxes->mailboxname_full = substr($folder_prefix,0, (strlen($folder_prefix)-1));
        } else {
            $mailboxes->mailboxname_full = $folder_prefix;
            $start++;
        }
        $mailboxes->mailboxname_sub = $mailboxes->mailboxname_full;
    } else {
        $start = 0;
    }
    $cnt =  count($mbx_ary);
    for ($i=0; $i < $cnt; $i++) {
        if ($mbx_ary[$i]['mbx'] !='' ) {
            $mbx = new mailboxes();
            $mailbox = $mbx_ary[$i]['mbx'];
            switch ($mailbox) {
                case 'INBOX':
                    $mbx->is_inbox = true;
                    $mbx->is_special = true;
                    break;
                case $trash_folder:
                    $mbx->is_trash = true;
                    $mbx->is_special = true;
                    break;
                case $sent_folder:
                    $mbx->is_sent = true;
                    $mbx->is_special = true;
                    break;
                case $draft_folder:
                    $mbx->is_draft = true;
                    $mbx->is_special = true;
                    break;
            }

            if (isset($mbx_ary[$i]['unseen'])) {
                $mbx->unseen = $mbx_ary[$i]['unseen'];
            }
            if (isset($mbx_ary[$i]['nummessages'])) {
                $mbx->total = $mbx_ary[$i]['nummessages'];
            }

            $mbx->is_noselect = $mbx_ary[$i]['noselect'];

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

    return $mailboxes;
}

?>
