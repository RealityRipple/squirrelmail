<?php

/**
 * imap_search.php
 *
 * Copyright (c) 1999-2003 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * IMAP asearch routines
 * Alex Lemaresquier - Brainstorm - alex at brainstorm.fr
 * See README file for infos.
 *
 */

require_once(SM_PATH . 'functions/imap.php');
require_once(SM_PATH . 'functions/date.php');

/* Set to TRUE to dump the imap dialogue */
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

/* This is to avoid the E_NOTICE warnings signaled by marc AT squirrelmail.org. Thanks Marc! */
function asearch_nz(&$var)
{
	if (isset($var))
		return $var;
	return '';
}

/* This should give the same results as PHP 4 >= 4.3.0's html_entity_decode() */
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

function s_dump($var_name, $var_var, $compact = FALSE)
{
	if (!$compact)
		echo '<PRE>';
	echo htmlentities($var_name) . '=';
	print_r($var_var);
	if ($compact)
		echo "<BR>\n";
	else
		echo "</PRE>\n";
}

function s_debug_dump($var_name, $var_var, $compact = FALSE)
{
	global $imap_asearch_debug_dump;
	if ($imap_asearch_debug_dump)
		s_dump($var_name, $var_var, $compact);
}

function sqimap_asearch_encode_string($what, $search_charset)
{
	if (strtoupper($search_charset) == 'ISO-2022-JP')
		$what = mb_convert_encoding($what, 'JIS', 'auto');
	if (strpos($what,'"') > -1)
		return '{' . strlen($what) . "}\r\n" . $what;	/* 4.3 literal form */
	return '"' . $what . '"';	/* 4.3 quoted string form */
}

/*
 Parses a user date string into an rfc2060 date string (<day number>-<US month TLA>-<4 digit year>)
 Returns a preg_match-style array: [0]: fully formatted date, [1]: day, [2]: month, [3]: year
 Handles space, slash, dot and comma as separators (and dash of course ;=)
*/
function sqimap_asearch_parse_date($what)
{
	global $imap_asearch_months;

	$what = trim($what);
	$what = ereg_replace('[ :,:/:.]+', '-', $what);
	if ($what) {
		preg_match('/^([0-9]+)-([^\-]+)-([0-9]+)$/', $what, $what_parts);
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

function sqimap_asearch_build_criteria($opcode, $what, $search_charset)
{
	global $imap_asearch_opcodes;

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
					sqimap_asearch_encode_string($what_parts[1], $search_charset) . ' ' .
					sqimap_asearch_encode_string($what_parts[2], $search_charset) . ' ';
		break;
		case 'adate':
			$what_parts = sqimap_asearch_parse_date($what);
			if ($what_parts[0] != '')
				$criteria = $opcode . ' ' . $what_parts[0] . ' ';
		break;
		case 'akeyword':
		case 'astring':
			$criteria = $opcode . ' ' . sqimap_asearch_encode_string($what, $search_charset) . ' ';
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
	global $allow_charset_search, $uid_support;

	/* 6.4.4 try OPTIONAL [CHARSET] specification first */
	if ($allow_charset_search && (!empty($search_charset)))
		$ss = 'SEARCH CHARSET ' . strtoupper($search_charset) . ' ALL ' . $search_string;
	else
		$ss = 'SEARCH ALL ' . $search_string;
	s_debug_dump('O', $ss);

	/* read data back from IMAP */
	$readin = sqimap_run_command($imapConnection, $ss, false, $result, $message, $uid_support);

	/* 6.4.4 try US-ASCII charset if we receive a tagged NO response */
	if (!empty($charset)  && strtolower($result) == 'no') {
		$ss = 'SEARCH CHARSET "US-ASCII" ALL ' . $search_string;
		s_debug_dump('O', $ss);
		$readin = sqimap_run_command ($imapConnection, $ss, true, $result, $message);	/* no $uid_support? */
	}

	unset($messagelist);

	/* Keep going till we find the SEARCH response */
	foreach ($readin as $readin_part) {
		s_debug_dump('I', $readin_part);
		/* Check to see if a SEARCH response was received */
		if (substr($readin_part, 0, 9) == '* SEARCH ') {
			$messagelist = preg_split("/ /", substr($readin_part, 9));
		} else if (isset($errors)) {
			$errors = $errors . $readin_part;
		} else {
			$errors = $readin_part;
		}
	}

	/* If nothing is found * SEARCH should be the first error else echo errors */
	if (isset($errors)) {
		if (strstr($errors,'* SEARCH'))
			return array();
		echo '<!-- ' . htmlspecialchars($errors) . ' -->';
	}

	if (empty($messagelist))
		return array();

	$cnt = count($messagelist);
	for ($q = 0; $q < $cnt; $q++)
		$id[$q] = trim($messagelist[$q]);
	return $id;
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
	global $languages, $squirrelmail_language;

/* ??? what are those for ?? */
/*	$pos = $search_position;*/

	$mbox_msgs = array();
	$search_charset = $languages[$squirrelmail_language]['CHARSET'];
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
					sqimap_mailbox_select($imapConnection, $cur_mailbox);
					s_debug_dump('SELECT',$cur_mailbox);
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