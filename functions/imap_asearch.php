<?php

/**
 * imap_search.php
 *
 * Copyright (c) 1999-2003 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * IMAP asearch routines
 *
 * $Id$
 * @package squirrelmail
 * @see search.php
 * @link ftp://ftp.rfc-editor.org/in-notes/rfc3501.txt
 * @author Alex Lemaresquier - Brainstorm - alex at brainstorm.fr
 *
 * Subfolder search idea from Patch #806075 by Thomas Pohl xraven at users.sourceforge.net. Thanks Thomas!
 */

/** This functionality requires the IMAP and date functions */
require_once(SM_PATH . 'functions/imap_general.php');
require_once(SM_PATH . 'functions/date.php');

/** Set to TRUE to dump the imap dialogue
 * @global bool $imap_asearch_debug_dump
 */
$imap_asearch_debug_dump = FALSE;

/** Imap SEARCH keys
 * @global array $imap_asearch_opcodes
 */
$imap_asearch_opcodes = array(
/* <message set> => 'asequence', */
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
	'HEADER' => 'afield',	/* Special syntax for this one, see below */
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

/** Imap SEARCH month names encoding
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

/** Error message titles according to imap server returned code
 * @global array $imap_error_titles
 */
$imap_error_titles = array(
	'OK' => '',
	'NO' => _("ERROR : Could not complete request."),
	'BAD' => _("ERROR : Bad or malformed request."),
	'BYE' => _("ERROR : Imap server closed the connection."),
	'' => _("ERROR : Connection dropped by imap-server.")
);

/**
 * Function to display an error related to an IMAP-query.
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
	global $imap_error_titles;

	//if (!array_key_exists($response, $imap_error_titles))	//php 4.0.6 compatibility
	if (!in_array($response, array_keys($imap_error_titles)))
		$title = _("ERROR : Unknown imap response.");
	else
		$title = $imap_error_titles[$response];
	if ($link == '')
		$message_title = _("Reason Given: ");
	else
		$message_title = _("Possible reason : ");
	if (function_exists('sqimap_error_box'))
		sqimap_error_box($title, $query, $message_title, $message, $link);
	else {	//Straight copy of 1.5 imap_general.php:sqimap_error_box(). Can be removed at a later time
		global $color;
    require_once(SM_PATH . 'functions/display_messages.php');
    $string = "<font color=$color[2]><b>\n" . $title . "</b><br>\n";
    if ($query != '')
        $string .= _("Query:") . ' ' . htmlspecialchars($query) . '<br>';
    if ($message_title != '')
        $string .= $message_title;
    if ($message != '')
        $string .= htmlspecialchars($message);
    if ($link != '')
        $string .= $link;
    $string .= "</font><br>\n";
    error_box($string,$color);
	}
}

/**
 * This is to avoid the E_NOTICE warnings signaled by marc AT squirrelmail.org. Thanks Marc!
 * @param mixed $var any variable (reference)
 * @return mixed zls ('') if $var is not defined, otherwise $var
 */
function asearch_nz(&$var)
{
	if (isset($var))
		return $var;
	return '';
}

/**
 * This should give the same results as PHP 4 >= 4.3.0's html_entity_decode(),
 * except it doesn't handle hex constructs
 * @param string $string string to unhtmlentity()
 * @return string decoded string
 */
function asearch_unhtmlentities($string) {
	$trans_tbl = array_flip(get_html_translation_table(HTML_ENTITIES));
	for ($i=127; $i<255; $i++)	/* Add &#<dec>; entities */
		$trans_tbl['&#' . $i . ';'] = chr($i);
	return strtr($string, $trans_tbl);
/* I think the one above is quicker, though it should be benchmarked
	$string = strtr($string, array_flip(get_html_translation_table(HTML_ENTITIES)));
	return preg_replace("/&#([0-9]+);/E", "chr('\\1')", $string);
*/
}

/**
 * Provide an easy way to dump the imap dialogue if $imap_asearch_debug_dump is TRUE
 * @global imap_asearch_debug_dump
 * @param string $var_name
 * @param string $var_var
 */
function s_debug_dump($var_name, $var_var)
{
	global $imap_asearch_debug_dump;
	if ($imap_asearch_debug_dump) {
		if (function_exists('sm_print_r'))	//Only exists since 1.4.2
			sm_print_r($var_name, $var_var);	//Better be the 'varargs' version ;)
		else {
			echo '<pre>';
			echo htmlentities($var_name);
			print_r($var_var);
			echo '</pre>';
		}
	}
}

/** Encode a string to quoted or literal as defined in rfc 3501
 *
 * - § 4.3 String:
 *	A quoted string is a sequence of zero or more 7-bit characters,
 *	 excluding CR and LF, with double quote (<">) characters at each end.
 * - § 9. Formal Syntax:
 *	quoted-specials = DQUOTE / "\"
 * @param string $what string to encode
 * @param string $charset search charset used
 * @return string encoded string
 */
function sqimap_asearch_encode_string($what, $charset)
{
	if (strtoupper($charset) == 'ISO-2022-JP')	// This should be now handled in imap_utf7_local?
		$what = mb_convert_encoding($what, 'JIS', 'auto');
//if (ereg("[\"\\\r\n\x80-\xff]", $what))
	if (preg_match('/["\\\\\r\n\x80-\xff]/', $what))
		return '{' . strlen($what) . "}\r\n" . $what;	// 4.3 literal form
	return '"' . $what . '"';	// 4.3 quoted string form
}

/**
 * Parses a user date string into an rfc 3501 date string
 * Handles space, slash, backslash, dot and comma as separators (and dash of course ;=)
 * @global imap_asearch_months
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
	$what = ereg_replace('[ /\\.,]+', '-', $what);
	if ($what) {
		preg_match('/^([0-9]+)-+([^\-]+)-+([0-9]+)$/', $what, $what_parts);
		if (count($what_parts) == 4) {
			$what_month = strtolower(asearch_unhtmlentities($what_parts[2]));
/*		if (!in_array($what_month, $imap_asearch_months)) {*/
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
/*		}*/
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
//			$what = str_replace(' ', '', $what);
			$what = ereg_replace('[^0-9]+', '', $what);
			if ($what != '')
				$criteria = $opcode . ' ' . $what . ' ';
		break;
		case '':	//aflag
			$criteria = $opcode . ' ';
		break;
		case 'afield':	/* HEADER field-name: field-body */
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
			$what = ereg_replace('[^0-9:\(\)]+', '', $what);
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
 * Run the imap SEARCH command as defined in rfc 3501
 * @link ftp://ftp.rfc-editor.org/in-notes/rfc3501.txt
 * @param resource $imapConnection the current imap stream
 * @param string $search_string the full search expression eg "ALL RECENT"
 * @param string $search_charset charset to use or zls ('')
 * @return array an IDs or UIDs array of matching messages or an empty array
 */
function sqimap_run_search($imapConnection, $search_string, $search_charset)
{
	global $uid_support;

	/* 6.4.4 try OPTIONAL [CHARSET] specification first */
	if ($search_charset != '')
		$query = 'SEARCH CHARSET "' . strtoupper($search_charset) . '" ALL ' . $search_string;
	else
		$query = 'SEARCH ALL ' . $search_string;
	s_debug_dump('C:', $query);
	$readin = sqimap_run_command($imapConnection, $query, false, $response, $message, $uid_support);

	/* 6.4.4 try US-ASCII charset if we tried an OPTIONAL [CHARSET] and received a tagged NO response (SHOULD be [BADCHARSET]) */
	if (($search_charset != '')  && (strtoupper($response) == 'NO')) {
		$query = 'SEARCH CHARSET US-ASCII ALL ' . $search_string;
		s_debug_dump('C:', $query);
		$readin = sqimap_run_command($imapConnection, $query, false, $response, $message, $uid_support);
	}
	if (strtoupper($response) != 'OK') {
		sqimap_asearch_error_box($response, $query, $message);
		return array();
	}

	// Keep going till we find the * SEARCH response
	foreach ($readin as $readin_part) {
		s_debug_dump('S:', $readin_part);
		if (substr($readin_part, 0, 9) == '* SEARCH ') {
			//EIMS returns multiple SEARCH responses, and this allowed according to Mark Crispin
			$messagelist = sqimap_array_merge_unique($messagelist, preg_split("/ /", substr($readin_part, 9)));
		}
	}

	if (empty($messagelist))	//Empty search response, ie '* SEARCH'
		return array();

	$cnt = count($messagelist);
	for ($q = 0; $q < $cnt; $q++)
		$id[$q] = trim($messagelist[$q]);
	return $id;
}

/**
 * Run the imap SORT command as defined in 
 * @link http://www.ietf.org/internet-drafts/draft-ietf-imapext-sort-13.txt
 * @param resource $imapConnection the current imap stream
 * @param string $search_string the full search expression as defined in rfc 3501
 * @param string $search_charset mandatory charset
 * @param string $sort_criteria the full sort criteria expression eg "SUBJECT REVERSE DATE"
 * @return array an IDs or UIDs array of matching messages or an empty array
 */
function sqimap_run_sort($imapConnection, $search_string, $search_charset, $sort_criteria)
{
	global $uid_support;

	if ($search_charset == '')
		$search_charset = 'US-ASCII';
	$query = 'SORT (' . $sort_criteria . ') "' . strtoupper($search_charset) . '" ALL ' . $search_string;
	s_debug_dump('C:', $query);
	$readin = sqimap_run_command($imapConnection, $query, false, $response, $message, $uid_support);
	s_debug_dump('S:', $response);

	/* 6.4 try US-ASCII charset if we received a tagged NO response (SHOULD be [BADCHARSET]) */
	if (($search_charset != 'US-ASCII')  && (strtoupper($response) == 'NO')) {
		s_debug_dump('S:', $readin);
		$query = 'SORT (' . $sort_criteria . ') US-ASCII ALL ' . $search_string;
		s_debug_dump('C:', $query);
		$readin = sqimap_run_command($imapConnection, $query, false, $response, $message, $uid_support);
		s_debug_dump('S:', $response);
	}

	if (strtoupper($response) != 'OK') {
		s_debug_dump('S:', $readin);
//	sqimap_asearch_error_box($response, $query, $message);
//	return array();
		return sqimap_run_search($imapConnection, $search_string, $search_charset);	// Fell back to standard search
	}

	/* Keep going till we find the * SORT response */
	foreach ($readin as $readin_part) {
		s_debug_dump('S:', $readin_part);
		if (substr($readin_part, 0, 7) == '* SORT ') {
			//SORT returns untagged responses
			$messagelist = sqimap_array_merge_unique($messagelist, preg_split("/ /", substr($readin_part, 7)));
		}
	}

	if (empty($messagelist))	//Empty search response, ie '* SORT'
		return array();

	$cnt = count($messagelist);
	for ($q = 0; $q < $cnt; $q++)
		$id[$q] = trim($messagelist[$q]);
	return $id;
}

/**
 * Run the imap THREAD command as defined in 
 * @link http://www.ietf.org/internet-drafts/draft-ietf-imapext-sort-13.txt
 * @param resource $imapConnection the current imap stream
 * @param string $search_string the full search expression as defined in rfc 3501
 * @param string $search_charset mandatory charset
 * @param string $thread_algorithm the threading algorithm "ORDEREDSUBJECT" or "REFERENCES"
 * @return array an IDs or UIDs array of matching messages or an empty array
 * @global array $thread_new will be used by thread view in mailbox_display
 * @global array $server_sort_array will be used by thread view in mailbox_display
 */
function sqimap_run_thread($imapConnection, $search_string, $search_charset, $thread_algorithm)
{
	global $thread_new, $server_sort_array;

	if (sqsession_is_registered('thread_new'))
		sqsession_unregister('thread_new');
	if (sqsession_is_registered('server_sort_array'))
		sqsession_unregister('server_sort_array');

	$thread_new = array();
	$thread_new[0] = "";

	$server_sort_array = array();

	global $uid_support;

	if ($search_charset == '')
		$search_charset = 'US-ASCII';
	$query = 'THREAD ' . $thread_algorithm . ' "' . strtoupper($search_charset) . '" ALL ' . $search_string;
	s_debug_dump('C:', $query);
	$readin = sqimap_run_command($imapConnection, $query, false, $response, $message, $uid_support);
	s_debug_dump('S:', $response);

	/* 6.4 try US-ASCII charset if we received a tagged NO response (SHOULD be [BADCHARSET]) */
	if (($search_charset != 'US-ASCII')  && (strtoupper($response) == 'NO')) {
		s_debug_dump('S:', $readin);
		$query = 'THREAD ' . $thread_algorithm . ' US-ASCII ALL ' . $search_string;
		s_debug_dump('C:', $query);
		$readin = sqimap_run_command($imapConnection, $query, false, $response, $message, $uid_support);
		s_debug_dump('S:', $response);
	}

	if (strtoupper($response) != 'OK') {
		s_debug_dump('S:', $readin);
		if (empty($response)) {	//imap server closed connection. We can't go further.
/* we should at this point:
	- warn the user that the THREAD call has failed
	- (offer him a way to) disconnect it permanently in the prefs
	- perform the regular search instead or provide a way to do it in one click
*/
			global $sort, $mailbox, $php_self;
			$message = _("The imap server failed to handle threading.");
			$unthread = _("Click here to unset thread view for this mailbox and start again.");
			if (preg_match('/^(.+)\?.+$/', $php_self, $regs))
				$source_url = $regs[1];
    	else
				$source_url = $php_self;
			$link = '<a href=' . $source_url . '?sort=' . $sort . '&start_messages=1&set_thread=0&mailbox=' . urlencode($mailbox) . '>' . $unthread . '</a>';
			sqimap_asearch_error_box($response, $query, $message, $link);
			return array();
		}
		return sqimap_run_search($imapConnection, $search_string, $search_charset);	// Fell back to standard search
	}

	/* Keep going till we find the * THREAD response */
	foreach ($readin as $readin_part) {
		s_debug_dump('S:', $readin_part);
		if (substr($readin_part, 0, 9) == '* THREAD ') {
			$thread_temp = preg_split("//", substr($readin_part, 9), -1, PREG_SPLIT_NO_EMPTY);
			break;	// Should be the last anyway
		}
	}

	if (empty($thread_temp))	//Empty search response, ie '* THREAD'
		return array();

	$char_count = count($thread_temp);
	$counter = 0;
	$k = 0;
	for ($i=0;$i<$char_count;$i++) {
        if ($thread_temp[$i] != ')' && $thread_temp[$i] != '(') {
                $thread_new[$k] = $thread_new[$k] . $thread_temp[$i];
        }
        elseif ($thread_temp[$i] == '(') {
                $thread_new[$k] .= $thread_temp[$i];
                $counter++;
        }
        elseif ($thread_temp[$i] == ')') {
                if ($counter > 1) {
                        $thread_new[$k] .= $thread_temp[$i];
                        $counter = $counter - 1;
                }
                else {
                        $thread_new[$k] .= $thread_temp[$i];
                        $k++;
                        $thread_new[$k] = "";
                        $counter = $counter - 1;
                }
        }
	}
	sqsession_register($thread_new, 'thread_new');
	$thread_new = array_reverse($thread_new);
	$thread_list = implode(" ", $thread_new);
	$thread_list = str_replace("(", " ", $thread_list);
	$thread_list = str_replace(")", " ", $thread_list);
	$thread_list = preg_split("/\s/", $thread_list, -1, PREG_SPLIT_NO_EMPTY);
	$server_sort_array = $thread_list;
	sqsession_register($server_sort_array, 'server_sort_array');
	return $thread_list;
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
 * Convert sm internal sort to imap sort taking care of:
 * - user defined date sorting (ARRIVAL vs DATE)
 * - if the searched mailbox is the sent folder then TO is being used instead of FROM
 * - reverse order by using REVERSE
 * @param string $mailbox mailbox name to sort
 * @param integer $sort_by sm sort criteria index
 * @global bool $internal_date_sort sort by arrival date instead of message date
 * @global string $sent_folder sent folder name
 * @return string imap sort criteria
 */
function sqimap_asearch_get_sort_criteria($mailbox, $sort_by)
{
	global $internal_date_sort, $sent_folder;

	$sort_opcodes = array ('DATE', 'FROM', 'SUBJECT', 'SIZE');
	if ($internal_date_sort == true)
		$sort_opcodes[0] = 'ARRIVAL';
//	if (handleAsSent($mailbox))
//	if (isSentFolder($mailbox))
	if ($mailbox == $sent_folder)
		$sort_opcodes[1] = 'TO';
	return (($sort_by % 2) ? '' : 'REVERSE ') . $sort_opcodes[($sort_by >> 1) & 3];
}

/**
 * @param string $cur_mailbox unformatted mailbox name
 * @param array $boxes_unformatted selectable mailbox unformatted names array (reference)
 * @return array sub mailboxes unformatted names
 */
function sqimap_asearch_get_sub_mailboxes($cur_mailbox, $mboxes_array)
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
 * Performs the search, given all the criteria, merging results for every mailbox
 * @param resource $imapConnection
 * @param array $mailbox_array
 * @param array $biop_array
 * @param array $unop_array
 * @param array $where_array
 * @param array $what_array
 * @param array $exclude_array
 * @param array $sub_array
 * @param array $mboxes_array selectable unformatted mailboxes names
 * @global bool $allow_server_sort comes from config.php
 * @global integer $sort sm internal sort order
 * @global bool $allow_thread_sort comes from config.php
 * @global bool $thread_sort_messages does it really need to global?
 * @global integer $sort_by_ref thread by references
 * @global string $data_dir
 * @global string $username
 * @return array $mbox_msgs array(mailbox => array(UIDs))
 */
function sqimap_asearch($imapConnection, $mailbox_array, $biop_array, $unop_array, $where_array, $what_array, $exclude_array, $sub_array, $mboxes_array)
{
	global $allow_server_sort, $sort, $allow_thread_sort, $thread_sort_messages, $sort_by_ref;
	global $data_dir, $username;

	$search_charset = sqimap_asearch_get_charset();
	$mbox_msgs = array();
	$search_string = '';
	$cur_mailbox = $mailbox_array[0];
	$cur_biop = '';	/* Start with ALL */
	/* We loop one more time than the real array count, so the last search gets fired */
	for ($cur_crit = 0; $cur_crit <= count($where_array); $cur_crit++) {
		if (empty($exclude_array[$cur_crit])) {
			$next_mailbox = $mailbox_array[$cur_crit];
			if ($next_mailbox != $cur_mailbox) {
				$search_string = trim($search_string);	/* Trim out last space */
				if ($cur_mailbox == 'All Folders')
						$search_mboxes = $mboxes_array;
				else if ((!empty($sub_array[$cur_crit - 1])) || (!in_array($cur_mailbox, $mboxes_array)))
					$search_mboxes = sqimap_asearch_get_sub_mailboxes($cur_mailbox, $mboxes_array);
				else
					$search_mboxes = array($cur_mailbox);
				foreach ($search_mboxes as $cur_mailbox) {
					s_debug_dump('C:SELECT:', $cur_mailbox);
					sqimap_mailbox_select($imapConnection, $cur_mailbox);
					$thread_sort_messages = $allow_thread_sort && getPref($data_dir, $username, 'thread_' . $cur_mailbox);
					if ($thread_sort_messages) {
						if ($sort_by_ref == 1)
							$thread_algorithm = 'REFERENCES';
						else
							$thread_algorithm = 'ORDEREDSUBJECT';
						$found_msgs = sqimap_run_thread($imapConnection, $search_string, $search_charset, $thread_algorithm);
					}
					else
					if (($allow_server_sort) && ($sort < 6)) {
						$sort_criteria = sqimap_asearch_get_sort_criteria($cur_mailbox, $sort);
						$found_msgs = sqimap_run_sort($imapConnection, $search_string, $search_charset, $sort_criteria);
					}
					else
						$found_msgs = sqimap_run_search($imapConnection, $search_string, $search_charset);
					if (isset($mbox_msgs[$cur_mailbox])) {
						if ($cur_biop == 'OR')	/* Merge with previous results */
							$mbox_msgs[$cur_mailbox] = sqimap_array_merge_unique($mbox_msgs[$cur_mailbox], $found_msgs);
						else	/* Intersect previous results */
							$mbox_msgs[$cur_mailbox] = array_values(array_intersect($found_msgs, $mbox_msgs[$cur_mailbox]));
					}
					else /* No previous results */
						$mbox_msgs[$cur_mailbox] = $found_msgs;
					if (empty($mbox_msgs[$cur_mailbox]))	/* Can happen with intersect, and we need at the end a contiguous array */
						unset($mbox_msgs[$cur_mailbox]);
				}
				$cur_mailbox = $next_mailbox;
				$search_string = '';
			}
			if (isset($where_array[$cur_crit])) {
				$criteria = sqimap_asearch_build_criteria($where_array[$cur_crit], $what_array[$cur_crit], $search_charset);
				if (!empty($criteria)) {
					$unop = $unop_array[$cur_crit];
					if (!empty($unop))
						$criteria = $unop . ' ' . $criteria;
					/* We need to infix the next non-excluded criteria's biop if it's the same mailbox */
					$next_biop = '';
					for ($next_crit = $cur_crit+1; $next_crit <= count($where_array); $next_crit++) {
						if (empty($exclude_array[$next_crit])) {
							if (asearch_nz($mailbox_array[$next_crit]) == $cur_mailbox)
								$next_biop = asearch_nz($biop_array[$next_crit]);
							break;
						}
					}
					if ($next_biop == 'OR')
						$criteria = $next_biop . ' ' . $criteria;
					$search_string .= $criteria;
					$cur_biop = asearch_nz($biop_array[$cur_crit]);
				}
			}
		}
	}
	return $mbox_msgs;
}

?>
