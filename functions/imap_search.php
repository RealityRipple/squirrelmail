<?php

/**
 * imap_search.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * IMAP search routines
 *
 * $Id$
 */

require_once('../functions/imap.php');
require_once('../functions/date.php');
require_once('../functions/array.php');
require_once('../functions/mailbox_display.php');
require_once('../functions/mime.php');

function sqimap_search($imapConnection, $search_where, $search_what, $mailbox,
                       $color, $search_position = '', $search_all, $count_all) {

    global $message_highlight_list, $squirrelmail_language, $languages,
           $index_order, $pos, $allow_charset_search, $uid_support;

    $pos = $search_position;

    $urlMailbox = urlencode($mailbox);

    /* construct the search query, taking multiple search terms into account */
    $multi_search = array();
    $search_what  = trim($search_what);
    $search_what  = ereg_replace('[ ]{2,}', ' ', $search_what);
    $multi_search = explode(' ', $search_what);
    $search_string = '';
    foreach ($multi_search as $multi_search_part) {
        $search_string .= $search_where . ' {' . strlen($multi_search_part)
            . "}\r\n" . $multi_search_part . ' ';
    }

    $search_string = trim($search_string);

    /* now use $search_string in the imap search */
    if ($allow_charset_search && isset($languages[$squirrelmail_language]['CHARSET']) &&
        $languages[$squirrelmail_language]['CHARSET']) {
        $ss = "SEARCH CHARSET "
            . strtoupper($languages[$squirrelmail_language]['CHARSET']) 
            . " ALL $search_string";
    } else {
        $ss = "SEARCH ALL $search_string";
    }

    /* read data back from IMAP */
    $readin = sqimap_run_command($imapConnection, $ss, false, $result, $message, $uid_support);

    /* try US-ASCII charset if search fails */
    if (isset($languages[$squirrelmail_language]['CHARSET']) 
        && strtolower($result) == 'no') {
        $ss = "SEARCH CHARSET \"US-ASCII\" ALL $search_string";
        $readin = sqimap_run_command ($imapConnection, $ss, true, 
                                      $result, $message);
    }

    unset($messagelist);

    /* Keep going till we find the SEARCH response */
    foreach ($readin as $readin_part) {
        /* Check to see if a SEARCH response was received */
        if (substr($readin_part, 0, 9) == '* SEARCH ') {
            $messagelist = preg_split("/ /", substr($readin_part, 9));
        } else if (isset($errors)) {
            $errors = $errors.$readin_part;
        } else {
            $errors = $readin_part;
        }
    }

    /* If nothing is found * SEARCH should be the first error else echo errors */
    if (isset($errors)) {
        if (strstr($errors,'* SEARCH')) {
            if ($search_all != 'all') {
                echo '<br><CENTER>' . _("No Messages Found") . '</CENTER>';
            }
            return;
        }
        echo "<!-- $errors -->";
    }


    global $sent_folder;

    $cnt = count($messagelist);
    for ($q = 0; $q < $cnt; $q++) {
        $id[$q] = trim($messagelist[$q]);
    }
    $issent = ($mailbox == $sent_folder);

    $msgs = fillMessageArray($imapConnection,$id,$issent,$cnt);

    return $msgs;
}



?>
