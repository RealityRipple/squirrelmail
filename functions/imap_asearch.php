<?php

/**
 * imap_search.php
 *
 * Copyright (c) 1999-2003 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * IMAP asearch routines
 * @author Alex Lemaresquier - Brainstorm - alex at brainstorm.fr
 * See README file for infos.
 * @package squirrelmail
 *
 */

/** This functionality requires the IMAP and date functions */
require_once(SM_PATH . 'functions/imap_general.php');
require_once(SM_PATH . 'functions/date.php');

/** Set to TRUE to dump the imap dialogue */
$imap_asearch_debug_dump = FALSE;

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

$imap_error_titles = array(
	'OK' => '',
	'NO' => _("ERROR : Could not complete request."),
	'BAD' => _("ERROR : Bad or malformed request."),
	'BYE' => _("ERROR : Imap server closed the connection.")
);

// why can't this just use sqimap_error_box() ?
function sqimap_asearch_error_box($response, $query, $message)
{
	global $imap_error_titles;

	//if (!array_key_exists($response, $imap_error_titles))	//php 4.0.6 compatibility
	if (!in_array($response, array_keys($imap_error_titles)))
		$title = _("ERROR : Unknown imap response.");
	else
		$title = $imap_error_titles[$response];
	$message_title = _("Reason Given: ");
	if (function_exists('sqimap_error_box'))
		sqimap_error_box($title, $query, $message_title, $message);
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
    $string .= "</font><br>\n";
    error_box($string,$color);
	}
}

/**
 * This is to avoid the E_NOTICE warnings signaled by marc AT squirrelmail.org. Thanks Marc!
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

/*
4.3 String:
	A quoted string is a sequence of zero or more 7-bit characters,
	 excluding CR and LF, with double quote (<">) characters at each end.
9. Formal Syntax:
	quoted-specials = DQUOTE / "\"
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
 * Parses a user date string into an rfc2060 date string
 * (<day number>-<US month TLA>-<4 digit year>).
 * Returns a preg_match-style array: [0]: fully formatted date,
 * [1]: day, [2]: month, [3]: year
 * Handles space, slash, backslash, dot and comma as separators (and dash of course ;=)
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

function sqimap_asearch_build_criteria($opcode, $what, $charset)
{
	global $imap_asearch_opcodes;

	$criteria = '';
	switch ($imap_asearch_opcodes[$opcode]) {
		default:
		case 'anum':
/*			$what = str_replace(' ', '', $what);*/
			$what = ereg_replace('[^0-9]+', '', $what);
			if ($what != '')
				$criteria = $opcode . ' ' . $what . ' ';
		break;
		case '':	/* aflag */
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

	unset($messagelist);

	/* Keep going till we find the SEARCH response */
	foreach ($readin as $readin_part) {
		s_debug_dump('S:', $readin_part);
		/* Check to see if a SEARCH response was received */
		if (substr($readin_part, 0, 9) == '* SEARCH ') {
			$messagelist = preg_split("/ /", substr($readin_part, 9));
			break;	// Should be the last anyway
		}
/*	else {
			if (isset($errors))
				$errors = $errors . $readin_part;
			else
				$errors = $readin_part;
		}*/
	}

	/* If nothing is found * SEARCH should be the first error else echo errors */
/*if (isset($errors)) {
		if (strstr($errors,'* SEARCH'))
			return array();
		echo '<!-- ' . htmlspecialchars($errors) . ' -->';
	}*/

	if (empty($messagelist))	//Empty search response, ie '* SEARCH'
		return array();

	$cnt = count($messagelist);
	for ($q = 0; $q < $cnt; $q++)
		$id[$q] = trim($messagelist[$q]);
	return $id;
}

function sqimap_asearch_get_charset()
{
	global $allow_charset_search, $languages, $squirrelmail_language;

	if ($allow_charset_search)
		return $languages[$squirrelmail_language]['CHARSET'];
	return '';
}

/* replaces $mbox_msgs[$search_mailbox] = array_values(array_unique(array_merge($mbox_msgs[$search_mailbox], sqimap_run_search($imapConnection, $search_string, $search_charset))));*/
function sqimap_array_merge_unique($to, $from)
{
	if (empty($to))
		return $from;
	for ($i=0; $i<count($from); $i++) {
		if (!in_array($from[$i], $to))
			$to[] = $from[$i];
	}
	return $to;
}

function sqimap_asearch($imapConnection, $mailbox_array, $biop_array, $unop_array, $where_array, $what_array, $exclude_array, $mboxes_array)
{
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
				if (($cur_mailbox == 'All Folders') && (!empty($mboxes_array)))
					$search_mboxes = $mboxes_array;
				else
					$search_mboxes = array($cur_mailbox);
				foreach ($search_mboxes as $cur_mailbox) {
					s_debug_dump('C:SELECT:', $cur_mailbox);
					sqimap_mailbox_select($imapConnection, $cur_mailbox);
					if (isset($mbox_msgs[$cur_mailbox])) {
						if ($cur_biop == 'OR')	/* Merge with previous results */
							$mbox_msgs[$cur_mailbox] = sqimap_array_merge_unique($mbox_msgs[$cur_mailbox], sqimap_run_search($imapConnection, $search_string, $search_charset));
						else	/* Intersect previous results */
							$mbox_msgs[$cur_mailbox] = array_values(array_intersect(sqimap_run_search($imapConnection, $search_string, $search_charset), $mbox_msgs[$cur_mailbox]));
					}
					else /* No previous results */
						$mbox_msgs[$cur_mailbox] = sqimap_run_search($imapConnection, $search_string, $search_charset);
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
