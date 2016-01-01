<?php

/**
 * imap_search.php
 *
 * IMAP asearch routines
 *
 * Subfolder search idea from Patch #806075 by Thomas Pohl xraven at users.sourceforge.net. Thanks Thomas!
 *
 * @author Alex Lemaresquier - Brainstorm <alex at brainstorm.fr>
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage imap
 * @see search.php
 * @link http://www.ietf.org/rfc/rfc3501.txt
 */

/** This functionality requires the IMAP and date functions
 */
//require_once(SM_PATH . 'functions/imap_general.php');
//require_once(SM_PATH . 'functions/date.php');

/** Set to TRUE to dump the IMAP dialogue
 * @global bool $imap_asearch_debug_dump
 */
$imap_asearch_debug_dump = FALSE;

/** IMAP SEARCH keys
 * @global array $imap_asearch_opcodes
 */
global $imap_asearch_opcodes;
$imap_asearch_opcodes = array(
/* <sequence-set> => 'asequence', */    // Special handling, @see sqimap_asearch_build_criteria()
/*'ALL' is binary operator */
    'ANSWERED' => '',
    'BCC' => 'astring',
    'BEFORE' => 'adate',
    'BODY' => 'astring',
    'CC' => 'astring',
    'DELETED' => '',
    'DRAFT' => '',
    'FLAGGED' => '',
    'FROM' => 'astring',
    'HEADER' => 'afield',    // Special syntax for this one, @see sqimap_asearch_build_criteria()
    'KEYWORD' => 'akeyword',
    'LARGER' => 'anum',
    'NEW' => '',
/*'NOT' is unary operator */
    'OLD' => '',
    'ON' => 'adate',
/*'OR' is binary operator */
    'RECENT' => '',
    'SEEN' => '',
    'SENTBEFORE' => 'adate',
    'SENTON' => 'adate',
    'SENTSINCE' => 'adate',
    'SINCE' => 'adate',
    'SMALLER' => 'anum',
    'SUBJECT' => 'astring',
    'TEXT' => 'astring',
    'TO' => 'astring',
    'UID' => 'asequence',
    'UNANSWERED' => '',
    'UNDELETED' => '',
    'UNDRAFT' => '',
    'UNFLAGGED' => '',
    'UNKEYWORD' => 'akeyword',
    'UNSEEN' => ''
);

/** IMAP SEARCH month names encoding
 * @global array $imap_asearch_months
 */
$imap_asearch_months = array(
    '01' => 'jan',
    '02' => 'feb',
    '03' => 'mar',
    '04' => 'apr',
    '05' => 'may',
    '06' => 'jun',
    '07' => 'jul',
    '08' => 'aug',
    '09' => 'sep',
    '10' => 'oct',
    '11' => 'nov',
    '12' => 'dec'
);

/**
 * Function to display an error related to an IMAP query.
 * We need to do our own error management since we may receive NO responses on purpose (even BAD with SORT or THREAD)
 * so we call sqimap_error_box() if the function exists (sm >= 1.5) or use our own embedded code
 * @global array imap_error_titles
 * @param string $response the imap server response code
 * @param string $query the failed query
 * @param string $message an optional error message
 * @param string $link an optional link to try again
 */
//@global array color sm colors array
function sqimap_asearch_error_box($response, $query, $message, $link = '')
{
    global $color;
    // Error message titles according to IMAP server returned code
    $imap_error_titles = array(
        'OK' => '',
        'NO' => _("ERROR: Could not complete request."),
        'BAD' => _("ERROR: Bad or malformed request."),
        'BYE' => _("ERROR: IMAP server closed the connection."),
        '' => _("ERROR: Connection dropped by IMAP server.")
    );


    if (!array_key_exists($response, $imap_error_titles))
        $title = _("ERROR: Unknown IMAP response.");
    else
        $title = $imap_error_titles[$response];
    if ($link == '')
        $message_title = _("Reason Given:");
    else
        $message_title = _("Possible reason:");
    $message_title .= ' ';
    sqimap_error_box($title, $query, $message_title, $message, $link);
}

/**
 * This is a convenient way to avoid spreading if (isset(... all over the code
 * @param mixed $var any variable (reference)
 * @param mixed $def default value to return if unset (default is zls (''), pass 0 or array() when appropriate)
 * @return mixed $def if $var is unset, otherwise $var
 */
function asearch_nz(&$var, $def = '')
{
    if (isset($var))
        return $var;
    return $def;
}

/**
 * This should give the same results as PHP 4 >= 4.3.0's html_entity_decode(),
 * except it doesn't handle hex constructs
 * @param string $string string to unhtmlentity()
 * @return string decoded string
 */
function asearch_unhtmlentities($string) {
    $trans_tbl = array_flip(get_html_translation_table(HTML_ENTITIES));
    for ($i=127; $i<255; $i++)    /* Add &#<dec>; entities */
        $trans_tbl['&#' . $i . ';'] = chr($i);
    return strtr($string, $trans_tbl);
/* I think the one above is quicker, though it should be benchmarked
    $string = strtr($string, array_flip(get_html_translation_table(HTML_ENTITIES)));
    return preg_replace("/&#([0-9]+);/E", "chr('\\1')", $string);
 */
}

/** Encode a string to quoted or literal as defined in rfc 3501
 *
 * -  4.3 String:
 *        A quoted string is a sequence of zero or more 7-bit characters,
 *         excluding CR and LF, with double quote (<">) characters at each end.
 * -  9. Formal Syntax:
 *        quoted-specials = DQUOTE / "\"
 * @param string $what string to encode
 * @param string $charset search charset used
 * @return string encoded string
 */
function sqimap_asearch_encode_string($what, $charset)
{
    if (strtoupper($charset) == 'ISO-2022-JP')    // This should be now handled in imap_utf7_local?
        $what = mb_convert_encoding($what, 'JIS', 'auto');
    if (preg_match('/["\\\\\r\n\x80-\xff]/', $what))
        return '{' . strlen($what) . "}\r\n" . $what;    // 4.3 literal form
    return '"' . $what . '"';    // 4.3 quoted string form
}

/**
 * Parses a user date string into an rfc 3501 date string
 * Handles space, slash, backslash, dot and comma as separators (and dash of course ;=)
 * @global array imap_asearch_months
 * @param string user date
 * @return array a preg_match-style array:
 *  - [0] = fully formatted rfc 3501 date string (<day number>-<US month TLA>-<4 digit year>)
 *  - [1] = day
 *  - [2] = month
 *  - [3] = year
 */
function sqimap_asearch_parse_date($what)
{
    global $imap_asearch_months;

    $what = trim($what);
    $what = preg_replace('/[ \/\\.,]+/', '-', $what);
    if ($what) {
        preg_match('/^([0-9]+)-+([^\-]+)-+([0-9]+)$/', $what, $what_parts);
        if (count($what_parts) == 4) {
            $what_month = strtolower(asearch_unhtmlentities($what_parts[2]));
/*                if (!in_array($what_month, $imap_asearch_months)) {*/
                foreach ($imap_asearch_months as $month_number => $month_code) {
                    if (($what_month == $month_number)
                    || ($what_month == $month_code)
                    || ($what_month == strtolower(asearch_unhtmlentities(getMonthName($month_number))))
                    || ($what_month == strtolower(asearch_unhtmlentities(getMonthAbrv($month_number))))
                    ) {
                        $what_parts[2] = $month_number;
                        $what_parts[0] = $what_parts[1] . '-' . $month_code . '-' . $what_parts[3];
                        break;
                    }
                }
/*                }*/
        }
    }
    else
        $what_parts = array();
    return $what_parts;
}

/**
 * Build one criteria sequence
 * @global array imap_asearch_opcodes
 * @param string $opcode search opcode
 * @param string $what opcode argument
 * @param string $charset search charset
 * @return string one full criteria sequence
 */
function sqimap_asearch_build_criteria($opcode, $what, $charset)
{
    global $imap_asearch_opcodes;

    $criteria = '';
    switch ($imap_asearch_opcodes[$opcode]) {
        default:
        case 'anum':
            $what = str_replace(' ', '', $what);
            $what = preg_replace('/[^0-9]+[^KMG]$/', '', strtoupper($what));
            if ($what != '') {
                switch (substr($what, -1)) {
                    case 'G':
                        $what = substr($what, 0, -1) << 30;
                    break;
                    case 'M':
                        $what = substr($what, 0, -1) << 20;
                    break;
                    case 'K':
                        $what = substr($what, 0, -1) << 10;
                    break;
                }
                $criteria = $opcode . ' ' . $what . ' ';
            }
        break;
        case '':    //aflag
            $criteria = $opcode . ' ';
        break;
        case 'afield':    /* HEADER field-name: field-body */
            preg_match('/^([^:]+):(.*)$/', $what, $what_parts);
            if (count($what_parts) == 3)
                $criteria = $opcode . ' ' .
                    sqimap_asearch_encode_string($what_parts[1], $charset) . ' ' .
                    sqimap_asearch_encode_string($what_parts[2], $charset) . ' ';
        break;
        case 'adate':
            $what_parts = sqimap_asearch_parse_date($what);
            if (isset($what_parts[0]))
                $criteria = $opcode . ' ' . $what_parts[0] . ' ';
        break;
        case 'akeyword':
        case 'astring':
            $criteria = $opcode . ' ' . sqimap_asearch_encode_string($what, $charset) . ' ';
        break;
        case 'asequence':
            $what = preg_replace('/[^0-9:()]+/', '', $what);
            if ($what != '')
                $criteria = $opcode . ' ' . $what . ' ';
        break;
    }
    return $criteria;
}

/**
 * Another way to do array_values(array_unique(array_merge($to, $from)));
 * @param array $to to array (reference)
 * @param array $from from array
 * @return array uniquely merged array
 */
function sqimap_array_merge_unique(&$to, $from)
{
    if (empty($to))
        return $from;
    $count = count($from);
    for ($i = 0; $i < $count; $i++) {
        if (!in_array($from[$i], $to))
            $to[] = $from[$i];
    }
    return $to;
}

/**
 * Run the IMAP SEARCH command as defined in rfc 3501
 * @link http://www.ietf.org/rfc/rfc3501.txt
 * @param resource $imapConnection the current imap stream
 * @param string $search_string the full search expression eg "ALL RECENT"
 * @param string $search_charset charset to use or zls ('')
 * @return array an IDs or UIDs array of matching messages or an empty array
 * @since 1.5.0
 */
function sqimap_run_search($imapConnection, $search_string, $search_charset)
{
    //For some reason, this seems to happen and forbids searching servers not allowing OPTIONAL [CHARSET]
    if (strtoupper($search_charset) == 'US-ASCII')
        $search_charset = '';
    /* 6.4.4 try OPTIONAL [CHARSET] specification first */
    if ($search_charset != '')
        $query = 'SEARCH CHARSET "' . strtoupper($search_charset) . '" ' . $search_string;
    else
        $query = 'SEARCH ' . $search_string;
    $readin = sqimap_run_command_list($imapConnection, $query, false, $response, $message, TRUE);

    /* 6.4.4 try US-ASCII charset if we tried an OPTIONAL [CHARSET] and received a tagged NO response (SHOULD be [BADCHARSET]) */
    if (($search_charset != '')  && (strtoupper($response) == 'NO')) {
        $query = 'SEARCH CHARSET US-ASCII ' . $search_string;
        $readin = sqimap_run_command_list($imapConnection, $query, false, $response, $message, TRUE);
    }
    if (strtoupper($response) != 'OK') {
        sqimap_asearch_error_box($response, $query, $message);
        return array();
    }
    $messagelist = parseUidList($readin,'SEARCH');

    if (empty($messagelist))    //Empty search response, ie '* SEARCH'
        return array();

    $cnt = count($messagelist);
    for ($q = 0; $q < $cnt; $q++)
        $id[$q] = trim($messagelist[$q]);
    return $id;
}

/**
 * @global bool allow_charset_search user setting
 * @global array languages sm languages array
 * @global string squirrelmail_language user language setting
 * @return string the user defined charset if $allow_charset_search is TRUE else zls ('')
 */
function sqimap_asearch_get_charset()
{
    global $allow_charset_search, $languages, $squirrelmail_language;

    if ($allow_charset_search)
        return $languages[$squirrelmail_language]['CHARSET'];
    return '';
}

/**
 * Convert SquirrelMail internal sort to IMAP sort taking care of:
 * - user defined date sorting (ARRIVAL vs DATE)
 * - if the searched mailbox is the sent folder then TO is being used instead of FROM
 * - reverse order by using REVERSE
 * @param string $mailbox mailbox name to sort
 * @param integer $sort_by sm sort criteria index
 * @global bool internal_date_sort sort by arrival date instead of message date
 * @global string sent_folder sent folder name
 * @return string imap sort criteria
 */
function sqimap_asearch_get_sort_criteria($mailbox, $sort_by)
{
    global $internal_date_sort, $sent_folder;

    $sort_opcodes = array ('DATE', 'FROM', 'SUBJECT', 'SIZE');
    if ($internal_date_sort == true)
        $sort_opcodes[0] = 'ARRIVAL';
// FIXME: Why are these commented out?  I have no idea what this code does, but both of these functions sound more robust than the simple string check that's being used now.  Someone who understands this code should either fix this or remove these lines completely or document why they are here commented out
//        if (handleAsSent($mailbox))
//        if (isSentFolder($mailbox))
    if ($mailbox == $sent_folder)
        $sort_opcodes[1] = 'TO';
    return (($sort_by % 2) ? '' : 'REVERSE ') . $sort_opcodes[($sort_by >> 1) & 3];
}

/**
 * @param string $cur_mailbox unformatted mailbox name
 * @param array $boxes_unformatted selectable mailbox unformatted names array (reference)
 * @return array sub mailboxes unformatted names
 */
function sqimap_asearch_get_sub_mailboxes($cur_mailbox, &$mboxes_array)
{
    $sub_mboxes_array = array();
    $boxcount = count($mboxes_array);
    for ($boxnum=0; $boxnum < $boxcount; $boxnum++) {
        if (isBoxBelow($mboxes_array[$boxnum], $cur_mailbox))
            $sub_mboxes_array[] = $mboxes_array[$boxnum];
    }
    return $sub_mboxes_array;
}

/**
 * Create the search query strings for all given criteria and merge results for every mailbox
 * @param resource $imapConnection
 * @param array $mailbox_array (reference)
 * @param array $biop_array (reference)
 * @param array $unop_array (reference)
 * @param array $where_array (reference)
 * @param array $what_array (reference)
 * @param array $exclude_array (reference)
 * @param array $sub_array (reference)
 * @param array $mboxes_array selectable unformatted mailboxes names (reference)
 * @return array array(mailbox => array(UIDs))
 */
function sqimap_asearch($imapConnection, &$mailbox_array, &$biop_array, &$unop_array, &$where_array, &$what_array, &$exclude_array, &$sub_array, &$mboxes_array)
{

    $search_charset = sqimap_asearch_get_charset();
    $mbox_search = array();
    $search_string = '';
    $cur_mailbox = $mailbox_array[0];
    $cur_biop = '';    /* Start with ALL */
    /* We loop one more time than the real array count, so the last search gets fired */
    for ($cur_crit=0,$iCnt=count($where_array); $cur_crit <= $iCnt; ++$cur_crit) {
        if (empty($exclude_array[$cur_crit])) {
            $next_mailbox = (isset($mailbox_array[$cur_crit])) ? $mailbox_array[$cur_crit] : false;
            if ($next_mailbox != $cur_mailbox) {
                $search_string = trim($search_string);    /* Trim out last space */
                if ($cur_mailbox == 'All Folders')
                    $search_mboxes = $mboxes_array;
                else if ((!empty($sub_array[$cur_crit - 1])) || (!in_array($cur_mailbox, $mboxes_array)))
                    $search_mboxes = sqimap_asearch_get_sub_mailboxes($cur_mailbox, $mboxes_array);
                else
                    $search_mboxes = array($cur_mailbox);
                foreach ($search_mboxes as $cur_mailbox) {
                    if (isset($mbox_search[$cur_mailbox])) {
                        $mbox_search[$cur_mailbox]['search'] .= ' ' . $search_string;
                    } else {
                        $mbox_search[$cur_mailbox]['search'] = $search_string;
                    }
                    $mbox_search[$cur_mailbox]['charset'] = $search_charset;
                }
                $cur_mailbox = $next_mailbox;
                $search_string = '';
            }
            if (isset($where_array[$cur_crit]) && empty($exclude_array[$cur_crit])) {
                $aCriteria = array();
                for ($crit = $cur_crit; $crit < count($where_array); $crit++) {
                    $criteria = trim(sqimap_asearch_build_criteria($where_array[$crit], $what_array[$crit], $search_charset));
                    if (!empty($criteria) && empty($exclude_array[$crit])) {
                        if (asearch_nz($mailbox_array[$crit]) == $cur_mailbox) {
                            $unop = $unop_array[$crit];
                            if (!empty($unop)) {
                                $criteria = $unop . ' ' . $criteria;
                            }
                            $aCriteria[] = array($biop_array[$crit], $criteria);
                        }
                    }
                    // unset something
                    $exclude_array[$crit] = true;
                }
                $aSearch = array();
                for($i=0,$iCnt=count($aCriteria);$i<$iCnt;++$i) {
                    $cur_biop = $aCriteria[$i][0];
                    $next_biop = (isset($aCriteria[$i+1][0])) ? $aCriteria[$i+1][0] : false;
                    if ($next_biop != $cur_biop && $next_biop == 'OR') {
                        $aSearch[] = 'OR '.$aCriteria[$i][1];
                    } else if ($cur_biop != 'OR') {
                        $aSearch[] = 'ALL '.$aCriteria[$i][1];
                    } else { // OR only supports 2 search keys so we need to create a parenthesized list
                        $prev_biop = (isset($aCriteria[$i-1][0])) ? $aCriteria[$i-1][0] : false;
                        if ($prev_biop == $cur_biop) {
                            $last = $aSearch[$i-1];
                            if (!substr($last,-1) == ')') {
                                $aSearch[$i-1] = "(OR $last";
                                $aSearch[] = $aCriteria[$i][1].')';
                            } else {
                                $sEnd = '';
                                while ($last && substr($last,-1) == ')') {
                                    $last = substr($last,0,-1);
                                    $sEnd .= ')';
                                }
                                $aSearch[$i-1] = "(OR $last";
                                $aSearch[] = $aCriteria[$i][1].$sEnd.')';
                            }
                        } else {
                            $aSearch[] = $aCriteria[$i][1];
                        }
                    }
                }
                $search_string .= implode(' ',$aSearch);
            }
        }
    }
    return ($mbox_search);
}
