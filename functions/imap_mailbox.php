<?php

/**
 * imap_mailbox.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This impliments all functions that manipulate mailboxes
 *
 * $Id$
 */

/*
    Defines Special Mail Boxes
*/

function isSpecialMailbox( $box ) {

    global $trash_folder, $sent_folder, $draft_folder,
           $move_to_trash, $move_to_sent, $save_as_draft,
           $delimiter, $folder_prefix;

    $i = strpos( $sent_folder, $delimiter, strlen( $folder_prefix ) );

    $ret = ( (strtolower($box) == 'inbox') ||
             ($box == $trash_folder &&
              $move_to_trash) ||
             (substr( $sent_folder, 0, max( $i, strlen( $box ) ) ) == $box &&
              $move_to_sent) ||
             ($box == $draft_folder &&
              $save_as_draft) );

    return( $ret );

}

/*************************
 **  Expunges a mailbox **
 *************************/
function sqimap_mailbox_expunge ($imap_stream, $mailbox,$handle_errors = TRUE)
{
    $read = sqimap_run_command($imap_stream, 'EXPUNGE',
                               $handle_errors, $response, $message);
}


/******************************************************************************
 **  Checks whether or not the specified mailbox exists 
 ******************************************************************************/
function sqimap_mailbox_exists ($imap_stream, $mailbox)
{
    if (! isset($mailbox)) {
        return false;
    }
    $mbx = sqimap_run_command($imap_stream, "LIST \"\" \"$mailbox\"",
                              TRUE, $response, $message);
    return isset($mbx[0]);
}

/******************************************************************************
 **  Selects a mailbox
 ******************************************************************************/
function sqimap_mailbox_select ($imap_stream, $mailbox,
                                $hide=TRUE, $recent=false)
{
    global $auto_expunge;
    
    if ( $mailbox == 'None' ) {
        return;
    }
    
    $read = sqimap_run_command($imap_stream, "SELECT \"$mailbox\"",
                               TRUE, $response, $message);
    if ($recent) {
        for ($i=0; $i<count($read); $i++) {
            if (strpos(strtolower($read[$i]), 'recent')) {
                $r = explode(' ', $read[$i]);
            }
        }
        return $r[1];
    }
    if ($auto_expunge) {
        $tmp = sqimap_run_command($imap_stream, "EXPUNGE",
                                  false, $a, $b);
    }
}



/******************************************************************************
 **  Creates a folder 
 ******************************************************************************/
function sqimap_mailbox_create ($imap_stream, $mailbox, $type)
{
    global $delimiter;
    if (strtolower($type) == 'noselect') {
        $mailbox = $mailbox.$delimiter;
    }
    $read_ary = sqimap_run_command($imap_stream, "CREATE \"$mailbox\"",
                                   TRUE, $response, $message);
    
    sqimap_subscribe ($imap_stream, $mailbox);
}



/******************************************************************************
 **  Subscribes to an existing folder 
 ******************************************************************************/
function sqimap_subscribe ($imap_stream, $mailbox)
{
    $read_ary = sqimap_run_command($imap_stream, " SUBSCRIBE \"$mailbox\"",
                                 TRUE, $response, $message);
}



/******************************************************************************
 **  Unsubscribes to an existing folder 
 ******************************************************************************/
function sqimap_unsubscribe ($imap_stream, $mailbox)
{
    global $imap_server_type;
    
    $read_ary = sqimap_run_command($imap_stream, " UNSUBSCRIBE \"$mailbox\"",
                                 TRUE, $response, $message);
}



/******************************************************************************
 **  This function simply deletes the given folder
 ******************************************************************************/
function sqimap_mailbox_delete ($imap_stream, $mailbox)
{
    $read_ary = sqimap_run_command($imap_stream, "DELETE \"$mailbox\"",
                                 TRUE, $response, $message);
    sqimap_unsubscribe ($imap_stream, $mailbox);
}

/***********************************************************************
 ** Determines if the user is subscribed to the folder or not
 **********************************************************************/
function sqimap_mailbox_is_subscribed($imap_stream, $folder)
{
    $boxes = sqimap_mailbox_list ($imap_stream);
    foreach ($boxes as $ref) {
        if ($ref['unformatted'] == $folder) {
            return TRUE;
        }
    }
    return false;
}

/*
    Renames a mailbox
*/
function sqimap_mailbox_rename( $imap_stream, $old_name, $new_name ) {

    if ( $old_name <> $new_name ) {

        global $delimiter;

        if ( substr( $old_name, -1 ) == $delimiter  ) {
            $old_name = substr( $old_name, 0, strlen( $old_name ) - 1 );
            $new_name = substr( $new_name, 0, strlen( $new_name ) - 1 );
            $postfix = $delimiter;
            $boxes = sqimap_mailbox_list($imap_stream);
        } else {
            $postfix = '';
            $boxes = FALSE;
        }

        $cmd = 'RENAME "' . quoteIMAP($old_name) . '" "' .  quoteIMAP($new_name) . '"';
        $data = sqimap_run_command($imap_stream, $cmd,
                                 TRUE, $response, $message);
        sqimap_unsubscribe($imap_stream, $old_name.$postfix);
        sqimap_subscribe($imap_stream, $new_name.$postfix);

        if ( $boxes ) {
            // Sub-unsub subfolders
            $l = strlen( $old_name ) + 1;
            $p = 'unformatted';
            foreach ( $boxes as $box ) {
                if ( substr( $box[$p], 0, $l ) == $old_name . $delimiter ) {
                    sqimap_unsubscribe($imap_stream, $box[$p]);
                    sqimap_subscribe($imap_stream,
                                     $new_name . $delimiter . substr( $box[$p], $l ) );
                }
            }
        }

    }

}

/******************************************************************************
 **  Formats a mailbox into 4 parts for the $boxes array
 **
 **  The four parts are:
 **
 **    raw            - Raw LIST/LSUB response from the IMAP server
 **    formatted      - nicely formatted folder name
 **    unformatted    - unformatted, but with delimiter at end removed
 **    unformatted-dm - folder name as it appears in raw response
 **    unformatted-disp - unformatted without $folder_prefix
 **
 ******************************************************************************/
function sqimap_mailbox_parse ($line, $line_lsub)
{
    global $folder_prefix, $delimiter;
    
    /* Process each folder line */
    for ($g=0; $g < count($line); $g++) {
        
        /* Store the raw IMAP reply */
        if (isset($line[$g])) {
            $boxes[$g]["raw"] = $line[$g];
        }
        else {
            $boxes[$g]["raw"] = "";
        }
        
        
        /* Count number of delimiters ($delimiter) in folder name */
        $mailbox = trim($line_lsub[$g]);
        $dm_count =  substr_count($mailbox, $delimiter);
        if (substr($mailbox, -1) == $delimiter) {
            /* If name ends in delimiter - decrement count by one */
            $dm_count--;  
        }
        
        /* Format folder name, but only if it's a INBOX.* or have */
        /* a parent. */
        $boxesbyname[$mailbox] = $g;
        $parentfolder = readMailboxParent($mailbox, $delimiter);
        if ( (strtolower(substr($mailbox, 0, 5)) == "inbox") ||
             (substr($mailbox, 0, strlen($folder_prefix)) == $folder_prefix) ||
             ( isset($boxesbyname[$parentfolder]) &&
               (strlen($parentfolder) > 0) ) ) {
            $indent = $dm_count - ( substr_count($folder_prefix, $delimiter));
            if ($indent > 0) {
                $boxes[$g]['formatted']  = str_repeat("&nbsp;&nbsp;", $indent);
            }
            else {
                $boxes[$g]['formatted'] = '';
            }
            $boxes[$g]['formatted'] .= readShortMailboxName($mailbox, $delimiter);
        }
        else {
            $boxes[$g]['formatted']  = $mailbox;
        }

        $boxes[$g]['unformatted-dm'] = $mailbox;
        if (substr($mailbox, -1) == $delimiter) {
            $mailbox = substr($mailbox, 0, strlen($mailbox) - 1);
        }
        $boxes[$g]['unformatted'] = $mailbox;
        if (substr($mailbox,0,strlen($folder_prefix))==$folder_prefix) {
            $mailbox = substr($mailbox, strlen($folder_prefix));
        }
        $boxes[$g]['unformatted-disp'] = $mailbox;
        $boxes[$g]['id'] = $g;

        $boxes[$g]['flags'] = array();
        if (isset($line[$g])) {
            ereg("\(([^)]*)\)",$line[$g],$regs);
            $flags = trim(strtolower(str_replace('\\', '',$regs[1])));
            if ($flags) {
                $boxes[$g]['flags'] = explode(' ', $flags);
            }
        }
    }

    return $boxes;
}

/**
 * Sorting function used to sort mailbox names.
 *   + Original patch from dave_michmerhuizen@yahoo.com
 *   + Allows case insensitivity when sorting folders
 *   + Takes care of the delimiter being sorted to the end, causing
 *     subfolders to be listed in below folders that are prefixed
 *     with their parent folders name.
 *        For example: INBOX.foo, INBOX.foobar, and INBOX.foo.bar
 *        Without special sort function: foobar between foo and foo.bar
 *        With special sort function: foobar AFTER foo and foo.bar :)
 */
function user_strcasecmp($a, $b) {
    global $delimiter;

    /* Calculate the length of some strings. */
    $a_length = strlen($a);
    $b_length = strlen($b);
    $min_length = min($a_length, $b_length);
    $delimiter_length = strlen($delimiter);

    /* Set the initial result value. */
    $result = 0;

    /* Check the strings... */
    for ($c = 0; $c < $min_length; ++$c) {
        $a_del = substr($a, $c, $delimiter_length);
        $b_del = substr($b, $c, $delimiter_length);

        if (($a_del == $delimiter) && ($b_del == $delimiter)) {
            $result = 0;
        } else if (($a_del == $delimiter) && ($b_del != $delimiter)) {
            $result = -1;
        } else if (($a_del != $delimiter) && ($b_del == $delimiter)) {
            $result = 1;
        } else {
            $result = strcasecmp($a{$c}, $b{$c});
        }

        if ($result != 0) {
            break;
        }
    }

    /* If one string is a prefix of the other... */
    if ($result == 0) {
        if ($a_length < $b_length) {
            $result = -1;
        } else if ($a_length > $b_length) {
            $result = 1;
        }
    }

    return ($result);
}


/******************************************************************************
 **  Returns sorted mailbox lists in several different ways.
 **  See comment on sqimap_mailbox_parse() for info about the returned array.
 ******************************************************************************/
function sqimap_mailbox_list ($imap_stream) {
    global $data_dir, $username, $list_special_folders_first,
           $folder_prefix, $trash_folder, $sent_folder, $draft_folder,
           $move_to_trash, $move_to_sent, $save_as_draft,
           $delimiter;

    $inbox_in_list = false;
    $inbox_subscribed = false;

    require_once('../src/load_prefs.php');
    require_once('../functions/array.php');

    /** LSUB array **/
    $lsub_ary = sqimap_run_command ($imap_stream, "LSUB \"$folder_prefix\" \"*\"",
                                  TRUE, $response, $message);

    /* Section about removing the last element was removed */
    /* We don't return "* OK" anymore from sqimap_read_data */

    $sorted_lsub_ary = array();
    for ($i=0;$i < count($lsub_ary); $i++) {
        /* Workaround for EIMS */
        /* Doesn't work if the mailbox name is multiple lines */
        if (isset($lsub_ary[$i + 1]) &&
            ereg("^(\\* [A-Z]+.*)\\{[0-9]+\\}([ \n\r\t]*)$",
                 $lsub_ary[$i], $regs)) {
            $i ++;
            $lsub_ary[$i] = $regs[1] . '"' . addslashes(trim($lsub_ary[$i])) .
                '"' . $regs[2];
        }
        $temp_mailbox_name = find_mailbox_name($lsub_ary[$i]);
        $sorted_lsub_ary[] = $temp_mailbox_name;
        if (strtoupper($temp_mailbox_name) == 'INBOX') {
            $inbox_subscribed = TRUE;
        }
    }
    $new_ary = array();
    for ($i=0; $i < count($sorted_lsub_ary); $i++) {
        if (!in_array($sorted_lsub_ary[$i], $new_ary)) {
            $new_ary[] = $sorted_lsub_ary[$i];
        }
    }
    $sorted_lsub_ary = $new_ary;
    if (isset($sorted_lsub_ary)) {
        usort($sorted_lsub_ary, 'user_strcasecmp');
    }

    /** LIST array **/
    $sorted_list_ary = array();
    for ($i=0; $i < count($sorted_lsub_ary); $i++) {
        if (substr($sorted_lsub_ary[$i], -1) == $delimiter) {
            $mbx = substr($sorted_lsub_ary[$i], 0, strlen($sorted_lsub_ary[$i])-1);
        }
        else {
            $mbx = $sorted_lsub_ary[$i];
        }

        $read = sqimap_run_command ($imap_stream, "LIST \"\" \"$mbx\"",
                                  TRUE, $response, $message);
        /* Another workaround for EIMS */
        if (isset($read[1]) &&
            ereg("^(\\* [A-Z]+.*)\\{[0-9]+\\}([ \n\r\t]*)$",
                 $read[0], $regs)) {
            $read[0] = $regs[1] . '"' . addslashes(trim($read[1])) .
                       '"' . $regs[2];
        }

        if (isset($sorted_list_ary[$i])) {
            $sorted_list_ary[$i] = '';
        }

        if (isset($read[0])) {
            $sorted_list_ary[$i] = $read[0];
        }
        else {
            $sorted_list_ary[$i] = '';
        }

        if (isset($sorted_list_ary[$i]) &&
            strtoupper(find_mailbox_name($sorted_list_ary[$i])) == 'INBOX') {
            $inbox_in_list = TRUE;
        }
    }

    /**
     * Just in case they're not subscribed to their inbox,
     * we'll get it for them anyway
     */
    if ($inbox_subscribed == false || $inbox_in_list == false) {
        $inbox_ary = sqimap_run_command ($imap_stream, "LIST \"\" \"INBOX\"",
                                       TRUE, $response, $message);
        /* Another workaround for EIMS */
        if (isset($inbox_ary[1]) &&
            ereg("^(\\* [A-Z]+.*)\\{[0-9]+\\}([ \n\r\t]*)$",
                 $inbox_ary[0], $regs)) {
            $inbox_ary[0] = $regs[1] . '"' . addslashes(trim($inbox_ary[1])) .
                '"' . $regs[2];
        }

        $sorted_list_ary[] = $inbox_ary[0];
        $sorted_lsub_ary[] = find_mailbox_name($inbox_ary[0]);
    }

    $boxes = sqimap_mailbox_parse ($sorted_list_ary, $sorted_lsub_ary);

    /** Now, lets sort for special folders **/
    $boxesnew = $used = array();

    /* Find INBOX */
    foreach ( $boxes as $k => $box ) {
        if ( strtolower($box['unformatted']) == 'inbox') {
            $boxesnew[] = $box;
            $used[$k] = TRUE;
        } else {
            $used[$k] = FALSE;
        }
    }

    /* List special folders and their subfolders, if requested. */
    if ($list_special_folders_first == TRUE) {

        foreach ( $boxes as $k => $box ) {
            if ( !$used[$k] &&
                 isSpecialMailbox( $box['unformatted'] ) ) {
                $boxesnew[] = $box;
                $used[$k] = TRUE;
            }
        }

    }

    /* Rest of the folders */
    foreach ( $boxes as $k => $box ) {
        if ( !$used[$k] ) {
            $boxesnew[] = $box;
        }
    }

    return( $boxesnew );
}

/*
 *  Returns a list of all folders, subscribed or not
 */
function sqimap_mailbox_list_all ($imap_stream)
{
    global $list_special_folders_first, $folder_prefix;
    global $delimiter;

    if (!function_exists('ary_sort')) {
        include_once('../functions/array.php');
    }

    $ssid = sqimap_session_id();
    $lsid = strlen( $ssid );
    fputs ($imap_stream, $ssid . " LIST \"$folder_prefix\" *\r\n");
    $read_ary = sqimap_read_data ($imap_stream, $ssid, TRUE, $response, $message);
    $g = 0;
    $phase = 'inbox';

    for ($i = 0; $i < count($read_ary); $i++) {
        /* Another workaround for EIMS */
        if (isset($read_ary[$i + 1]) &&
            ereg("^(\\* [A-Z]+.*)\\{[0-9]+\\}([ \n\r\t]*)$",
                 $read_ary[$i], $regs)) {
            $i ++;
            $read_ary[$i] = $regs[1] . '"' .
                            addslashes(trim($read_ary[$i])) .
                            '"' . $regs[2];
        }
        if (substr($read_ary[$i], 0, $lsid) != $ssid ) {

            /* Store the raw IMAP reply */
            $boxes[$g]["raw"] = $read_ary[$i];

            /* Count number of delimiters ($delimiter) in folder name */
            $mailbox = find_mailbox_name($read_ary[$i]);
            $dm_count =  substr_count($mailbox, $delimiter);
            if (substr($mailbox, -1) == $delimiter) {
                /* If name ends in delimiter - decrement count by one */
                $dm_count--;
            }

            /* Format folder name, but only if it's a INBOX.* or have */
            /* a parent. */
            $boxesbyname[$mailbox] = $g;
            $parentfolder = readMailboxParent($mailbox, $delimiter);
            if((eregi('^inbox'.quotemeta($delimiter), $mailbox)) ||
               (ereg('^'.$folder_prefix, $mailbox)) ||
               ( isset($boxesbyname[$parentfolder]) && (strlen($parentfolder) > 0) ) ) {
                if ($dm_count) {
                    $boxes[$g]['formatted']  = str_repeat('&nbsp;&nbsp;', $dm_count);
                }
                else {
                    $boxes[$g]['formatted'] = '';
                }
                $boxes[$g]['formatted'] .= readShortMailboxName($mailbox, $delimiter);
            }
            else {
                $boxes[$g]['formatted']  = $mailbox;
            }

            $boxes[$g]["unformatted-dm"] = $mailbox;
            if (substr($mailbox, -1) == $delimiter) {
                $mailbox = substr($mailbox, 0, strlen($mailbox) - 1);
            }
            $boxes[$g]['unformatted'] = $mailbox;
            $boxes[$g]['unformatted-disp'] =
                ereg_replace('^' . $folder_prefix, '', $mailbox);
            $boxes[$g]['id'] = $g;

            /** Now lets get the flags for this mailbox **/
            $read_mlbx = sqimap_run_command ($imap_stream, "LIST \"\" \"$mailbox\"",
                                           TRUE, $response, $message);

            /* Another workaround for EIMS */
            if (isset($read_mlbx[1]) &&
                ereg("^(\\* [A-Z]+.*)\\{[0-9]+\\}([ \n\r\t]*)$",
                     $read_mlbx[0], $regs)) {
                $read_mlbx[0] = $regs[1] . '"' .
                                addslashes(trim($read_mlbx[1])) .
                                '"' . $regs[2];
            }

            $flags = substr($read_mlbx[0], strpos($read_mlbx[0], '(')+1);
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
        $boxes = ary_sort ($boxes, 'unformatted', 1);
    }

    return $boxes;
}

?>
