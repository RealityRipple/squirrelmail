<?php

/**
 * search.php
 *
 * Copyright (c) 1999-2004 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * IMAP search page
 *
 * Subfolder search idea from Patch #806075 by Thomas Pohl xraven at users.sourceforge.net. Thanks Thomas!
 *
 * @version $Id$
 * @package squirrelmail
 * @link http://www.ietf.org/rfc/rfc3501.txt
 * @author Alex Lemaresquier - Brainstorm - alex at brainstorm.fr
 */

/**
 * Path for SquirrelMail required files.
 * @ignore
 */
define('SM_PATH','../');

/** SquirrelMail required files.
 */
require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'functions/strings.php');
require_once(SM_PATH . 'functions/imap_asearch.php');
require_once(SM_PATH . 'functions/imap_mailbox.php');
require_once(SM_PATH . 'functions/imap_messages.php');
require_once(SM_PATH . 'functions/mime.php');
require_once(SM_PATH . 'functions/mailbox_display.php');	//getButton()...

/** Prefs array ordinals. Must match $recent_prefkeys and $saved_prefkeys
 */
define('ASEARCH_WHERE', 0);
define('ASEARCH_MAILBOX', 1);
define('ASEARCH_WHAT', 2);
define('ASEARCH_UNOP', 3);
define('ASEARCH_BIOP', 4);
define('ASEARCH_EXCLUDE', 5);
define('ASEARCH_SUB', 6);
define('ASEARCH_MAX', 7);

/** Name of session var
 */
define('ASEARCH_CRITERIA', 'criteria');

/** Builds a href with params
 * @param string $params optional parameters to GET
 */
function asearch_get_href($params = '')
{
	$href = 'search.php';
	if ($params != '')
		$href .= '?' . $params;
	return $href;
}

/** Builds a [link]
 * @param string $href (reference)
 * @param string $text
 * @param string $title
 */
function asearch_get_link(&$href, $text, $title = '')
{
	if ($title != '')
		$title = ' title="' . $title . '"';
	return '<a href="' . $href . '"' . $title . '>' . $text . '</a>';
}

/** Builds a toggle [link]
 * @param integer $value
 * @param string $action
 * @param array $text_array
 * @param array $title_array
 */
function asearch_get_toggle_link($value, $action, $text_array, $title_array = array())
{
	return asearch_get_link(asearch_get_href($action . '=' . (int)$value), $text_array[$value], asearch_nz($title_array[$value]));
}

/**
 * @param string $a
 * @param string $b
 * @return bool strcoll()-like result
 */
function asearch_unhtml_strcoll($a, $b)
{
	return strcoll(asearch_unhtmlentities($a), asearch_unhtmlentities($b));
}

/**
 * @param string $mailbox mailbox name utf7 encoded inc. special case INBOX
 * @return string mailbox name ready to display (utf7 decoded or localized INBOX)
 */
function imap_get_mailbox_display($mailbox)
{
	if (strtoupper($mailbox) == 'INBOX')
		return _("INBOX");
	return imap_utf7_decode_local($mailbox);
}

/**
 * @param string $mailbox mailbox name or special case 'All Folders'
 * @return string mailbox name ready to display (utf7 decoded or localized 'All Folders')
 */
function asearch_get_mailbox_display($mailbox)
{
	if ($mailbox == 'All Folders')
		return _("All Folders");
	return imap_get_mailbox_display($mailbox);
}

/**
 * @param array $color color array
 * @param string $txt text to display
 * @return string title ready to display
 */
function asearch_get_title_display(&$color, $txt)
{
	return '<b><big>' . $txt . '</big></b>';
}

/**
 * @param array $color color array
 * @param string $txt text to display
 * @return string error text ready to display
 */
function asearch_get_error_display(&$color, $txt)
{
	return '<font color="' . $color[2] . '">' . '<b><big>' . $txt . '</big></b></font>';
}

/**
 * @param array $input_array array to serialize
 * @return string a string containing a byte-stream representation of value that can be stored anywhere
 */
function asearch_serialize(&$input_array)
{
	global $search_advanced;
	if ($search_advanced)
		return serialize($input_array);
	return $input_array[0];
}

/**
 * @param string $input_string string to unserialize
 * @return array
 */
function asearch_unserialize($input_string)
{
	global $search_advanced;
	if ($search_advanced)
		return unserialize($input_string);
	return array($input_string);
}

/**
 * @param string $key the pref key
 * @param integer $index the pref key index
 * @param string $default default value
 * @return string pref value
 */
function asearch_getPref(&$key, $index, $default = '')
{
	global $data_dir, $username, $search_advanced;
	return getPref($data_dir, $username, $key . ($index + !$search_advanced), $default);
}

/**
 * @param string $key the pref key
 * @param integer $index the pref key index
 * @param string $value pref value to set
 * @return bool status
 */
function asearch_setPref(&$key, $index, $value)
{
	global $data_dir, $username, $search_advanced;
	return setPref($data_dir, $username, $key . ($index + !$search_advanced), $value);
}

/**
 * @param string $key the pref key
 * @param integer $index the pref key index
 * @return bool status
 */
function asearch_removePref(&$key, $index)
{
	global $data_dir, $username, $search_advanced;
	return removePref($data_dir, $username, $key . ($index + !$search_advanced));
}

/** Sanity checks, done before running the imap command and before calling push_recent
 */
function asearch_check_query(&$where_array, &$what_array, &$exclude_array)
{
	global $imap_asearch_opcodes;

	if (empty($where_array))
		return _("Please enter something to search for");
	if (count($exclude_array) == count($where_array))
		return _("There must be at least one criteria to search for");
	for ($crit_num = 0; $crit_num < count($where_array); $crit_num++) {
		$where = $where_array[$crit_num];
		$what = $what_array[$crit_num];
		if (!(($what == '') ^ ($imap_asearch_opcodes[$where] != '')))
			return _("Error in criteria argument");
	}
	return '';
}

/** Read the recent searches from the prefs
 */
function asearch_read_recent()
{
	global $recent_prefkeys, $search_memory;

	$recent_array = array();
	$recent_num = 0;
	for ($pref_num = 0; $pref_num < $search_memory; $pref_num++) {
		foreach ($recent_prefkeys as $prefkey) {
			$pref = asearch_getPref($prefkey, $pref_num);
/*			if (!empty($pref))*/
				$recent_array[$prefkey][$recent_num] = $pref;
		}
		if (empty($recent_array[$recent_prefkeys[0]][$recent_num])) {
			foreach ($recent_prefkeys as $key) {
				array_pop($recent_array[$key]);
			}
//			break;	//Disabled to support old search code broken prefs
		}
		else
			$recent_num++;
	}
	return $recent_array;
}

/** Read the saved searches from the prefs
 */
function asearch_read_saved()
{
	global $saved_prefkeys;

	$saved_array = array();
	$saved_key = $saved_prefkeys[0];
	for ($saved_count = 0; ; $saved_count++) {
		$pref = asearch_getPref($saved_key, $saved_count);
		if (empty($pref))
			break;
	}
	for ($saved_num = 0; $saved_num < $saved_count; $saved_num++) {
		foreach ($saved_prefkeys as $key) {
			$saved_array[$key][$saved_num] = asearch_getPref($key, $saved_num);
		}
	}
	return $saved_array;
}

/** Save a recent search to the prefs
 */
function asearch_save_recent($recent_index)
{
	global $recent_prefkeys, $saved_prefkeys;

	$saved_array = asearch_read_saved();
	$saved_index = count($saved_array[$saved_prefkeys[0]]);
	$recent_array = asearch_read_recent();
	$n = 0;
	foreach ($recent_prefkeys as $key) {
		$recent_slice = array_slice($recent_array[$key], $recent_index, 1);
		if (!empty($recent_slice[0]))
			asearch_setPref($saved_prefkeys[$n], $saved_index, $recent_slice[0]);
		else
			asearch_removePref($saved_prefkeys[$n], $saved_index);
		$n++;
	}
}

/** Write a recent search to prefs
 */
function asearch_write_recent(&$recent_array)
{
	global $recent_prefkeys, $search_memory;

	$recent_count = min($search_memory, count($recent_array[$recent_prefkeys[0]]));
	for ($recent_num = 0; $recent_num < $recent_count; $recent_num++) {
		foreach ($recent_prefkeys as $key) {
			asearch_setPref($key, $recent_num, $recent_array[$key][$recent_num]);
		}
	}
	for (; $recent_num < $search_memory; $recent_num++) {
		foreach ($recent_prefkeys as $key) {
			asearch_removePref($key, $recent_num);
		}
	}
}

/** Remove a recent search from prefs
 */
function asearch_forget_recent($forget_index)
{
	global $recent_prefkeys;

	$recent_array = asearch_read_recent();
	foreach ($recent_prefkeys as $key) {
		array_splice($recent_array[$key], $forget_index, 1);
	}
	asearch_write_recent($recent_array);
}

/** Find a recent search in the prefs (used to avoid saving duplicates)
 */
function asearch_find_recent(&$recent_array, &$mailbox_array, &$biop_array, &$unop_array, &$where_array, &$what_array, &$exclude_array, &$sub_array)
{
	global $recent_prefkeys, $search_advanced;

	$where_string = asearch_serialize($where_array);
	$mailbox_string = asearch_serialize($mailbox_array);
	$what_string = asearch_serialize($what_array);
	$unop_string = asearch_serialize($unop_array);
	if ($search_advanced) {
		$biop_string = asearch_serialize($biop_array);
		$exclude_string = asearch_serialize($exclude_array);
		$sub_string = asearch_serialize($sub_array);
	}
	$recent_count = count($recent_array[$recent_prefkeys[ASEARCH_WHERE]]);
	for ($recent_num = 0; $recent_num < $recent_count; $recent_num++) {
		if (isset($recent_array[$recent_prefkeys[ASEARCH_WHERE]][$recent_num])) {
			if (
					$where_string == $recent_array[$recent_prefkeys[ASEARCH_WHERE]][$recent_num] &&
					$mailbox_string == $recent_array[$recent_prefkeys[ASEARCH_MAILBOX]][$recent_num] &&
					$what_string == $recent_array[$recent_prefkeys[ASEARCH_WHAT]][$recent_num] &&
					$unop_string == $recent_array[$recent_prefkeys[ASEARCH_UNOP]][$recent_num] &&
					((!$search_advanced) ||
						($biop_string == $recent_array[$recent_prefkeys[ASEARCH_BIOP]][$recent_num] &&
						$exclude_string == $recent_array[$recent_prefkeys[ASEARCH_EXCLUDE]][$recent_num] &&
						$sub_string == $recent_array[$recent_prefkeys[ASEARCH_SUB]][$recent_num]))
					)
				return $recent_num;
		}
	}
	return -1;
}

/** Push a recent search into the prefs
 */
function asearch_push_recent(&$mailbox_array, &$biop_array, &$unop_array, &$where_array, &$what_array, &$exclude_array, &$sub_array)
{
	global $recent_prefkeys, $search_memory;

	$criteria = array($mailbox_array, $biop_array, $unop_array, $where_array, $what_array, $exclude_array, $sub_array);
	sqsession_register($criteria, ASEARCH_CRITERIA);
	if ($search_memory > 0) {
		$recent_array = asearch_read_recent();
		$recent_found = asearch_find_recent($recent_array, $mailbox_array, $biop_array, $unop_array, $where_array, $what_array, $exclude_array, $sub_array);
		if ($recent_found >= 0) {	// Remove identical recent
			foreach ($recent_prefkeys as $key) {
				array_splice($recent_array[$key], $recent_found, 1);
			}
		}
		$input = array($where_array, $mailbox_array, $what_array, $unop_array, $biop_array, $exclude_array, $sub_array);
		$i = 0;
		foreach ($recent_prefkeys as $key) {
			array_unshift($recent_array[$key], asearch_serialize($input[$i]));
			$i++;
		}
		asearch_write_recent($recent_array);
	}
}

/** Edit a recent search
 * @global array mailbox_array searched mailboxes
 */
function asearch_edit_recent($index)
{
	global $recent_prefkeys, $search_advanced;
	global $where_array, $mailbox_array, $what_array, $unop_array;
	global $biop_array, $exclude_array, $sub_array;

	$where_array = asearch_unserialize(asearch_getPref($recent_prefkeys[ASEARCH_WHERE], $index));
	$mailbox_array = asearch_unserialize(asearch_getPref($recent_prefkeys[ASEARCH_MAILBOX], $index));
	$what_array = asearch_unserialize(asearch_getPref($recent_prefkeys[ASEARCH_WHAT], $index));
	$unop_array = asearch_unserialize(asearch_getPref($recent_prefkeys[ASEARCH_UNOP], $index));
	if ($search_advanced) {
		$biop_array = asearch_unserialize(asearch_getPref($recent_prefkeys[ASEARCH_BIOP], $index));
		$exclude_array = asearch_unserialize(asearch_getPref($recent_prefkeys[ASEARCH_EXCLUDE], $index));
		$sub_array = asearch_unserialize(asearch_getPref($recent_prefkeys[ASEARCH_SUB], $index));
	}
}

/** Get last search criteria from session or prefs
 */
function asearch_edit_last()
{
	if (sqGetGlobalVar(ASEARCH_CRITERIA, $criteria, SQ_SESSION)) {
		global $where_array, $mailbox_array, $what_array, $unop_array;
		global $biop_array, $exclude_array, $sub_array;
		$mailbox_array = $criteria[0];
		$biop_array = $criteria[1];
		$unop_array = $criteria[2];
		$where_array = $criteria[3];
		$what_array = $criteria[4];
		$exclude_array = $criteria[5];
		$sub_array = $criteria[6];
		sqsession_unregister(ASEARCH_CRITERIA);
	}
	else {
		global $search_memory;
		if ($search_memory > 0)
			asearch_edit_recent(0);
	}
}

/** Edit a saved search
 */
function asearch_edit_saved($index)
{
	global $saved_prefkeys, $search_advanced;
	global $where_array, $mailbox_array, $what_array, $unop_array;
	global $biop_array, $exclude_array, $sub_array;

	$where_array = asearch_unserialize(asearch_getPref($saved_prefkeys[ASEARCH_WHERE], $index));
	$mailbox_array = asearch_unserialize(asearch_getPref($saved_prefkeys[ASEARCH_MAILBOX], $index));
	$what_array = asearch_unserialize(asearch_getPref($saved_prefkeys[ASEARCH_WHAT], $index));
	$unop_array = asearch_unserialize(asearch_getPref($saved_prefkeys[ASEARCH_UNOP], $index));
	if ($search_advanced) {
		$biop_array = asearch_unserialize(asearch_getPref($saved_prefkeys[ASEARCH_BIOP], $index));
		$exclude_array = asearch_unserialize(asearch_getPref($saved_prefkeys[ASEARCH_EXCLUDE], $index));
		$sub_array = asearch_unserialize(asearch_getPref($saved_prefkeys[ASEARCH_SUB], $index));
	}
}

/** Write a saved search to the prefs
 */
function asearch_write_saved(&$saved_array)
{
	global $saved_prefkeys;

	$saved_count = count($saved_array[$saved_prefkeys[0]]);
	for ($saved_num=0; $saved_num < $saved_count; $saved_num++) {
		foreach ($saved_prefkeys as $key) {
			asearch_setPref($key, $saved_num, $saved_array[$key][$saved_num]);
		}
	}
	foreach ($saved_prefkeys as $key) {
		asearch_removePref($key, $saved_count);
	}
}

/** Delete a saved search from the prefs
 */
function asearch_delete_saved($saved_index)
{
	global $saved_prefkeys;

	$saved_array = asearch_read_saved();
	$asearch_keys = $saved_prefkeys;
	foreach ($asearch_keys as $key) {
		array_splice($saved_array[$key], $saved_index, 1);
	}
	asearch_write_saved($saved_array);
}

/** Translate the input date to imap date to local date display,
 * so the user can know if the date is wrong or illegal
 * @return string locally formatted date or error text
 */
function asearch_get_date_display(&$what)
{
	$what_parts = sqimap_asearch_parse_date($what);
	if (count($what_parts) == 4) {
		if (checkdate($what_parts[2], $what_parts[1], $what_parts[3]))
			return date_intl(_("M j, Y"), mktime(0,0,0,$what_parts[2],$what_parts[1],$what_parts[3]));
			//return $what_parts[1] . ' ' . getMonthName($what_parts[2]) . ' ' . $what_parts[3];
		return _("(Illegal date)");
	}
	return _("(Wrong date)");
}

/** Translate the query to rough natural display
 * @return string rough natural query ready to display
 */
function asearch_get_query_display(&$color, &$mailbox_array, &$biop_array, &$unop_array, &$where_array, &$what_array, &$exclude_array, &$sub_array)
{
	global $imap_asearch_biops_in, $imap_asearch_biops, $imap_asearch_unops, $imap_asearch_options;
	global $imap_asearch_opcodes;

	$last_mailbox = $mailbox_array[0];
	if (empty($last_mailbox))
		$last_mailbox = 'INBOX';
	$query_display = '';
	for ($crit_num=0; $crit_num < count($where_array); $crit_num++) {
		if ((!isset($exclude_array[$crit_num])) || (!$exclude_array[$crit_num])) {
			$cur_mailbox = $mailbox_array[$crit_num];
			if (empty($cur_mailbox))
				$cur_mailbox = 'INBOX';
			$biop = asearch_nz($biop_array[$crit_num]);
			if (($query_display == '') || ($cur_mailbox != $last_mailbox)) {
				$mailbox_display = ' <b>' . asearch_get_mailbox_display($cur_mailbox) . '</b>';
				if ($query_display == '')
					$biop_display = _("In");
				else
					$biop_display = $imap_asearch_biops_in[$biop];
				$last_mailbox = $cur_mailbox;
			}
			else {
				$mailbox_display = '';
				$biop_display = $imap_asearch_biops[$biop];
			}
			$unop = $unop_array[$crit_num];
			$unop_display = $imap_asearch_unops[$unop];
			if ($unop_display != '')
				$unop_display .= ' ';
			$where = $where_array[$crit_num];
			$where_display = $unop_display . asearch_nz($imap_asearch_options[$where], $where);
			$what_type = $imap_asearch_opcodes[$where];
			$what = $what_array[$crit_num];
			if ($what_type) {	/* Check opcode parameter */
				if ($what == '')
					$what_display = ' ' . asearch_get_error_display($color, _("(Missing argument)"));
				else {
					if ($what_type == 'adate')
						$what_display = asearch_get_date_display($what);
					else
						$what_display = htmlspecialchars($what);
					$what_display = ' <b>' . $what_display . '</b>';
				}
			}
			else {
				if ($what)
					$what_display = ' ' . asearch_get_error_display($color, _("(Spurious argument)"));
				else
					$what_display = '';
			}
			if ($mailbox_display != '')
				$query_display .= ' <u><i>' . $biop_display . '</i></u>' . $mailbox_display . ' <u><i>' . $where_display . '</i></u>' . $what_display;
			else
				$query_display .= ' <u><i>' . $biop_display . ' ' . $where_display . '</i></u>' . $what_display;
		}
	}
	return $query_display;
}

/** Handle the alternate row colors
 * @return string color value
 */
function asearch_get_row_color(&$color, $row_num)
{
/*$color_string = ($row_num%2 ? $color[0] : $color[4]);*/
	$color_string = $color[4];
	if ($GLOBALS['alt_index_colors']) {
		if (($row_num % 2) == 0) {
			if (!isset($color[12]))
				$color[12] = '#EAEAEA';
			$color_string = $color[12];
		}
	}
	return $color_string;
}

/** Print a whole query array, recent or saved
 */
function asearch_print_query_array(&$boxes, &$query_array, &$query_keys, &$action_array, $title, $show_pref)
{
	global $color;
	global $data_dir, $username;
	global $use_icons, $icon_theme;

	$show_flag = getPref($data_dir, $username, $show_pref, 0) & 1;
	$use_icons_flag = ($use_icons) && ($icon_theme != 'none');
	if ($use_icons_flag)
		$text_array = array('<img src="' . SM_PATH . 'images/minus.png" border="0" height="7" width="7" />',
			'<img src="' . SM_PATH . 'images/plus.png" border="0" height="7" width="7" />');
	else
		$text_array = array('-', '+');
	$toggle_link = asearch_get_toggle_link(!$show_flag, $show_pref, $text_array, array(_("Fold"), _("Unfold")));
	if (!$use_icons_flag)
		$toggle_link = '<small>[' . $toggle_link . ']</small>';

	echo "<br />\n";
	echo html_tag('table', '', 'center', $color[9], 'width="95%" cellpadding="1" cellspacing="1" border="0"');
	echo html_tag('tr',
		html_tag('td', $toggle_link, 'center', $color[5], 'width="5%"')
		. html_tag('td', asearch_get_title_display($color, $title), 'center', $color[5], 'colspan=4'));
	if ($show_flag) {
		$main_key = $query_keys[ASEARCH_WHERE];
		$query_count = count($query_array[$main_key]);
		for ($query_num = 0, $row_num = 0; $query_num < $query_count; $query_num++) {
			if (!empty($query_array[$main_key][$query_num])) {
				echo html_tag('tr', '', '', asearch_get_row_color($color, $row_num));

				unset($search_array);
				foreach ($query_keys as $query_key) {
					$search_array[] = asearch_unserialize($query_array[$query_key][$query_num]);
				}
				$where_array = $search_array[ASEARCH_WHERE];
				$mailbox_array = $search_array[ASEARCH_MAILBOX];
				$what_array = $search_array[ASEARCH_WHAT];
				$unop_array = $search_array[ASEARCH_UNOP];
				$biop_array = asearch_nz($search_array[ASEARCH_BIOP], array());
				$exclude_array = asearch_nz($search_array[ASEARCH_EXCLUDE], array());
				$sub_array = asearch_nz($search_array[ASEARCH_SUB], array());
				$query_display = asearch_get_query_display($color, $mailbox_array, $biop_array, $unop_array, $where_array, $what_array, $exclude_array, $sub_array);

				echo html_tag('td', $query_num + 1, 'right');
				echo html_tag('td', $query_display, 'center', '', 'width="80%"');
				foreach ($action_array as $action => $action_display) {
					echo html_tag('td', '<a href="' . asearch_get_href('submit=' . $action . '&amp;rownum=' . $query_num) . '">' . $action_display . '</a>', 'center');
				}

				echo '</tr>' . "\n";
				$row_num++;
			}
		}
	}
	echo '</table>' . "\n";
}

/** Print the saved array
 */
function asearch_print_saved(&$boxes)
{
	global $saved_prefkeys;

	$saved_array = asearch_read_saved();
	if (isset($saved_array[$saved_prefkeys[0]])) {
		$saved_count = count($saved_array[$saved_prefkeys[0]]);
		if ($saved_count > 0) {
			$saved_actions = array('edit_saved' => _("edit"), 'search_saved' => _("search"), 'delete_saved' => _("delete"));
			asearch_print_query_array($boxes, $saved_array, $saved_prefkeys, $saved_actions, _("Saved Searches"), 'search_show_saved');
		}
	}
}

/**
 * Print the recent array
 */
function asearch_print_recent(&$boxes)
{
	global $recent_prefkeys, $search_memory;

	$recent_array = asearch_read_recent();
	if (isset($recent_array[$recent_prefkeys[0]])) {
		$recent_count = count($recent_array[$recent_prefkeys[0]]);
		if (min($recent_count, $search_memory) > 0) {
			$recent_actions = array('save_recent' => _("save"), 'search_recent' => _("search"), 'forget_recent' => _("forget"));
			asearch_print_query_array($boxes, $recent_array, $recent_prefkeys, $recent_actions, _("Recent Searches"), 'search_show_recent');
		}
	}
}

/** Build an <option> statement
 */
function asearch_opt($val, $sel, $tit)
{
	return '<option value="' . $val . '"' . ($sel == $val ? ' selected' : '') . '>' . $tit . '</option>' . "\n";
}

/** Build a <select> statement from an array
 */
function asearch_opt_array($var_name, $opt_array, $cur_val)
{
	$output = '<select name="' . $var_name . '">' . "\n";
	foreach($opt_array as $val => $display)
		$output .= asearch_opt($val, $cur_val, asearch_nz($display, $val));
	$output .= '</select>' . "\n";
	return $output;
}

/** Verify that a mailbox exists
 * @return bool mailbox exists
 */
function asearch_mailbox_exists($mailbox, &$boxes)
{
	foreach ($boxes as $box) {
		if ($box['unformatted'] == $mailbox)
			return TRUE;
	}
	return FALSE;
}

/** Build the mailbox select
 */
function asearch_get_form_mailbox($imapConnection, &$boxes, $mailbox, $row_num = 0)
{
	if (($mailbox != 'All Folders') && (!asearch_mailbox_exists($mailbox, $boxes)))
		$missing = asearch_opt($mailbox, $mailbox, '[' . _("Missing") . '] ' . asearch_get_mailbox_display($mailbox));
	else
		$missing = '';
	return '<select name="mailbox[' . $row_num . ']">'
		. $missing
		. asearch_opt('All Folders', $mailbox, '[' . asearch_get_mailbox_display('All Folders') . ']')
		. sqimap_mailbox_option_list($imapConnection, array(strtolower($mailbox)), 0, $boxes, NULL)
		. '</select>';
}

/** Build the Include subfolders checkbox
 */
function asearch_get_form_sub($sub, $row_num = 0)
{
	return function_exists('addCheckBox') ? addCheckBox('sub[' . $row_num .']', $sub)
	: '<input type="checkbox" name="sub[' . $row_num .']"' . ($sub ? ' checked="checked"' : '') . ' />';
}

/** Build the 2 unop and where selects
 */
function asearch_get_form_location($unop, $where, $row_num = 0)
{
	global $imap_asearch_unops, $imap_asearch_options;

	return asearch_opt_array('unop[' . $row_num . ']', $imap_asearch_unops, $unop)
		. asearch_opt_array('where[' . $row_num . ']', $imap_asearch_options, $where);
}

/** Build the what text input
 */
function asearch_get_form_what($what, $row_num = 0)
{
	return function_exists('addInput') ? addInput('what[' . $row_num . ']', $what, '35')
	: '<input type="text" size="35" name="what[' . $row_num . ']" value="' . htmlspecialchars($what) . '" />';
}

/** Build the Exclude criteria checkbox
 */
function asearch_get_form_exclude($exclude, $row_num = 0)
{
	return function_exists('addCheckBox') ? addCheckBox('exclude['.$row_num.']', $exclude)
	: '<input type="checkbox" name="exclude[' . $row_num .']"' . ($exclude ? ' checked="checked"' : '') . ' />';
}

/** Print one advanced form row
 */
function asearch_print_form_row($imapConnection, &$boxes, $mailbox, $biop, $unop, $where, $what, $exclude, $sub, $row_num)
{
	global $imap_asearch_biops_in;
	global $color;

	echo html_tag('tr', '', '', $color[4]);

//Binary operator
	echo html_tag('td', $row_num ?
			asearch_opt_array('biop[' . $row_num . ']', $imap_asearch_biops_in, $biop)
			: '<b>' . _("In") . '</b>', 'center')	. "\n";

//Mailbox list and Include Subfolders
	echo html_tag('td',
			asearch_get_form_mailbox($imapConnection, $boxes, $mailbox, $row_num)
		. _("and&nbsp;subfolders:") . asearch_get_form_sub($sub, $row_num), 'center') . "\n";

//Unary operator and Search location
	echo html_tag('td', asearch_get_form_location($unop, $where, $row_num), 'center') . "\n";

//Text input
	echo html_tag('td', asearch_get_form_what($what, $row_num), 'center') . "\n";

//Exclude criteria
	echo html_tag('td', _("Exclude Criteria:") . asearch_get_form_exclude($exclude, $row_num), 'center') . "\n";

	echo "</tr>\n";
}

/** Print the advanced search form
 */
function asearch_print_form($imapConnection, &$boxes, $mailbox_array, $biop_array, $unop_array, $where_array, $what_array, $exclude_array, $sub_array)
{
	global $search_button_html, $add_criteria_button_html, $del_excluded_button_html, $del_all_button_html;
	global $color;

//Search Form
	echo "<br />\n";
	echo '<form action="' . asearch_get_href() . '" name="form_asearch">' . "\n";

	echo html_tag('table', '', 'center', $color[9], 'width="100%" cellpadding="1" cellspacing="1" border="0"');
	echo html_tag('tr', html_tag('td', asearch_get_title_display($color, _("Search Criteria")), 'center', $color[5], 'colspan=5'));
	$row_count = count($where_array);
	for ($row_num = 0; $row_num < $row_count; $row_num++) {
		$mailbox = asearch_nz($mailbox_array[$row_num]);
		$biop = strip_tags(asearch_nz($biop_array[$row_num]));
		$unop = strip_tags(asearch_nz($unop_array[$row_num]));
		$where = strip_tags(asearch_nz($where_array[$row_num]));
		$what = asearch_nz($what_array[$row_num]);
		$exclude = strip_tags(asearch_nz($exclude_array[$row_num]));
		$sub = strip_tags(asearch_nz($sub_array[$row_num]));
		asearch_print_form_row($imapConnection, $boxes, $mailbox, $biop, $unop, $where, $what, $exclude, $sub, $row_num);
	}
	echo '</table>' . "\n";

//Submit buttons
	echo html_tag('table', '', 'center', $color[9], 'width="100%" cellpadding="1" cellspacing="0" border="0"');
	echo html_tag('tr',
				html_tag('td', getButton('SUBMIT', 'submit', $search_button_html), 'center') . "\n"
			. html_tag('td', getButton('SUBMIT', 'submit', $add_criteria_button_html), 'center') . "\n"
			. html_tag('td', getButton('SUBMIT', 'submit', $del_all_button_html), 'center') . "\n"
			. html_tag('td', getButton('SUBMIT', 'submit', $del_excluded_button_html), 'center') . "\n"
			);
	echo '</table>' . "\n";
	echo '</form>' . "\n";
}

/** Print one basic form row
 */
function asearch_print_form_row_basic($imapConnection, &$boxes, $mailbox, $biop, $unop, $where, $what, $exclude, $sub, $row_num)
{
	global $search_button_html;
	global $color;

	echo html_tag('tr', '', '', $color[4]);

//Mailbox list
	echo html_tag('td', '<b>' . _("In") . '</b> ' . asearch_get_form_mailbox($imapConnection, $boxes, $mailbox), 'center') . "\n";

//Unary operator and Search location
	echo html_tag('td', asearch_get_form_location($unop, $where), 'center') . "\n";

//Text input
	echo html_tag('td', asearch_get_form_what($what), 'center') . "\n";

//Submit button
	echo html_tag('td', getButton('SUBMIT', 'submit', $search_button_html), 'center') . "\n";

	echo "</tr>\n";
}

/** Print the basic search form
 */
function asearch_print_form_basic($imapConnection, &$boxes, $mailbox_array, $biop_array, $unop_array, $where_array, $what_array, $exclude_array, $sub_array)
{
	global $color;

//Search Form
	echo "<br />\n";
	echo '<form action="' . asearch_get_href() . '" name="form_asearch">' . "\n";

	echo html_tag('table', '', 'center', $color[9], 'width="100%" cellpadding="1" cellspacing="1" border="0"');
	//echo html_tag('tr', html_tag('td', asearch_get_title_display($color, _("Search Criteria")), 'center', $color[5], 'colspan=4'));
	$row_count = count($where_array);
	for ($row_num = 0; $row_num < $row_count; $row_num++) {
		$mailbox = asearch_nz($mailbox_array[$row_num]);
		$biop = strip_tags(asearch_nz($biop_array[$row_num]));
		$unop = strip_tags(asearch_nz($unop_array[$row_num]));
		$where = strip_tags(asearch_nz($where_array[$row_num]));
		$what = asearch_nz($what_array[$row_num]);
		$exclude = strip_tags(asearch_nz($exclude_array[$row_num]));
		$sub = strip_tags(asearch_nz($sub_array[$row_num]));
		asearch_print_form_row_basic($imapConnection, $boxes, $mailbox, $biop, $unop, $where, $what, $exclude, $sub, $row_num);
	}
	echo '</table>' . "\n";
	echo '</form>' . "\n";
}

/** Print the $msgs messages from $mailbox mailbox
 */
function asearch_print_mailbox_msgs($imapConnection, $mbxresponse, $mailbox, $id, $cnt, $sort, $color, $where, $what)
{
	if ($cnt > 0) {
		global $allow_server_sort, $allow_thread_sort, $thread_sort_messages;
        $msgs = sqimap_get_small_header_list ($imapConnection, $id, count($id));
		$thread_sort_messages = 0;
		if ($allow_thread_sort) {
			global $data_dir, $username;
			$thread_sort_messages = getPref($data_dir, $username, 'thread_' . $mailbox);
			//$msort = $msgs;
			//$real_sort = 6;
		}
		elseif ($allow_server_sort) {
			//$msort = $msgs;
			//$real_sort = 6;
		}
		else {
			//$msort = calc_msort($msgs, $sort);
			//$real_sort = $sort;
		}

		$mailbox_display = asearch_get_mailbox_display($mailbox);
		$mailbox_title = '<b><big>' . _("Folder:") . ' '. $mailbox_display . '&nbsp;</big></b>';
		$devel = check_sm_version(1, 5, 0);
		if (!$devel) {
			echo html_tag('div', $mailbox_title, 'center') . "\n";
			$mailbox_title = get_selectall_link(1, $real_sort);
		}
		$msg_cnt_str = get_msgcnt_str(1, $cnt, $cnt);

		echo '<table border="0" width="100%" cellpadding="0" cellspacing="0">';

		echo '<tr><td>';
		if ($devel)
			mail_message_listing_beginning($imapConnection, $mbxresponse, $mailbox, $sort, $msg_cnt_str, $mailbox_title, 1, 1);
		else
			mail_message_listing_beginning($imapConnection, $mailbox, $sort, $msg_cnt_str, $mailbox_title, 1, 1);
		echo '</td></tr>';

		echo '<tr><td HEIGHT="5" BGCOLOR="'.$color[4].'"></td></tr>';

		echo '<tr><td>';
		echo '    <table width="100%" cellpadding="1" cellspacing="0" align="center"'.' border="0" bgcolor="'.$color[9].'">';
		echo '     <tr><td>';

		echo '       <table width="100%" cellpadding="1" cellspacing="0" align="center" border="0" bgcolor="'.$color[5].'">';
		echo '        <tr><td>';
		printHeader($mailbox, $sort, $color, !$thread_sort_messages);
		displayMessageArray($imapConnection, $cnt, 1, $id, $msgs, $mailbox, $sort, $cnt, $where, $what);
		echo '        </td></tr>';
		echo '       </table>';
		echo '     </td></tr>';
		echo '    </table>';
		mail_message_listing_end($cnt, '', $msg_cnt_str, $color);
		echo '</td></tr>';

		echo '</table>';
	}
}

/**
 * @param array $boxes mailboxes array (reference)
 * @return array selectable unformatted mailboxes names
 */
function sqimap_asearch_get_selectable_unformatted_mailboxes(&$boxes)
{
	$mboxes_array = array();
	$boxcount = count($boxes);
	for ($boxnum = 0; $boxnum < $boxcount; $boxnum++) {
		if (!in_array('noselect', $boxes[$boxnum]['flags']))
			$mboxes_array[] = $boxes[$boxnum]['unformatted'];
	}
	return $mboxes_array;
}

/* ------------------------ main ------------------------ */
/* get globals we will need */
sqgetGlobalVar('username', $username, SQ_SESSION);
sqgetGlobalVar('key', $key, SQ_COOKIE);
sqgetGlobalVar('delimiter', $delimiter, SQ_SESSION);
sqgetGlobalVar('onetimepad', $onetimepad, SQ_SESSION);

if ( sqgetGlobalVar('checkall', $temp, SQ_GET) ) {
    $checkall = (int) $temp;
}


$search_button_html = _("Search");
$search_button_text = asearch_unhtmlentities($search_button_html);
$add_criteria_button_html = _("Add New Criteria");
$add_criteria_button_text = asearch_unhtmlentities($add_criteria_button_html);
$del_excluded_button_html = _("Remove Excluded Criteria");
$del_excluded_button_text = asearch_unhtmlentities($del_excluded_button_html);
$del_all_button_html = _("Remove All Criteria");
$del_all_button_text = asearch_unhtmlentities($del_all_button_html);

/** Maximum number of recent searches to handle
 * Default 0
 * @global integer $search_memory
 */
$search_memory = getPref($data_dir, $username, 'search_memory', 0);

/** Advanced search control
 * - 0 => allow basic interface only
 * - 1 => allow advanced interface only
 * - 2 => allow both
 * Default 2
 * @global integer $allow_advanced_search
 */
$allow_advanced_search = asearch_nz($allow_advanced_search, 2);

/**
 * Toggle advanced/basic search
 */
if (sqgetGlobalVar('advanced', $search_advanced, SQ_GET))
	setPref($data_dir, $username, 'search_advanced', $search_advanced & 1);

/** If 1, show advanced search interface
 * Default from allow_advanced_search pref
 * @global integer $search_advanced
 */
if ($allow_advanced_search > 1)
	$search_advanced = getPref($data_dir, $username, 'search_advanced', 0);
else
	$search_advanced = $allow_advanced_search;

if ($search_advanced) {
/** Set recent prefkeys according to $search_advanced
 * @global array $recent_prefkeys
 */
	$recent_prefkeys = array('asearch_recent_where', 'asearch_recent_mailbox', 'asearch_recent_what', 'asearch_recent_unop', 'asearch_recent_biop', 'asearch_recent_exclude', 'asearch_recent_sub');

/** Set saved prefkeys according to $search_advanced
 * @global array $saved_prefkeys
 */
	$saved_prefkeys = array('asearch_saved_where', 'asearch_saved_mailbox', 'asearch_saved_what', 'asearch_saved_unop', 'asearch_saved_biop', 'asearch_saved_exclude', 'asearch_saved_sub');

/*$asearch_prefkeys = array('where', 'mailbox', 'what', 'biop', 'unop', 'exclude', 'sub');*/
}
else {
	$recent_prefkeys = array('search_where', 'search_folder', 'search_what', 'search_unop');
	$saved_prefkeys = array('saved_where', 'saved_folder', 'saved_what', 'saved_unop');
}

/** How we did enter the form
 * - unset : Enter key, or called from outside (eg read_body)
 * - $search_button_text : Search button
 * - 'Search_no_update' : Search but don't update recent
 * - 'Search_last' : Same as no_update but reload and search last
 * - 'Search_silent' : Same as no_update but only display results
 * - $add_criteria_button_text : Add New Criteria button
 * - $del_excluded_button_text : Remove Excluded Criteria button
 * - $del_all_button_text : Remove All Criteria button
 * - 'save_recent'
 * - 'search_recent'
 * - 'forget_recent'
 * - 'edit_saved'
 * - 'search_saved'
 * - 'delete_saved'
 * @global string $submit
 */
if (isset($_GET['submit']))
	$submit = strip_tags($_GET['submit']);

/** Searched mailboxes
 * @global array $mailbox_array
 */
if (isset($_GET['mailbox'])) {
	$mailbox_array = $_GET['mailbox'];
	if (!is_array($mailbox_array))
		$mailbox_array = array($mailbox_array);
}
else
	$mailbox_array = array();

/** Binary operators
 * @global array $biop_array
 */
if (isset($_GET['biop'])) {
	$biop_array = $_GET['biop'];
	if (!is_array($biop_array))
		$biop_array = array($biop_array);
}
else
	$biop_array = array();

/** Unary operators
 * @global array $unop_array
 */
if (isset($_GET['unop'])) {
	$unop_array = $_GET['unop'];
	if (!is_array($unop_array))
		$unop_array = array($unop_array);
}
else
	$unop_array = array();

/** Where to search
 * @global array $where_array
 */
if (isset($_GET['where'])) {
	$where_array = $_GET['where'];
	if (!is_array($where_array))
		$where_array = array($where_array);
}
else
	$where_array = array();

/** What to search
 * @global array $what_array
 */
if (isset($_GET['what'])) {
	$what_array = $_GET['what'];
	if (!is_array($what_array))
		$what_array = array($what_array);
}
else
	$what_array = array();

/** Whether to exclude this criteria from search
 * @global array $exclude_array
 */
if (isset($_GET['exclude']))
	$exclude_array = $_GET['exclude'];
else
	$exclude_array = array();

/** Search within subfolders
 * @global array $sub_array
 */
if (isset($_GET['sub']))
	$sub_array = $_GET['sub'];
else
	$sub_array = array();

/** Row number used by recent and saved stuff
 */
if (isset($_GET['rownum']))
    $submit_rownum = strip_tags($_GET['rownum']);

/** Change global sort
 */
if (sqgetGlobalVar('newsort', $newsort, SQ_GET)) {
	setPref($data_dir, $username, 'sort', $newsort);
	$sort = $newsort;
	sqsession_register($sort, 'sort');
	asearch_edit_last();
}

/** Toggle mailbox threading
 */
if (sqgetGlobalVar('set_thread', $set_thread, SQ_GET)) {
	setPref($data_dir, $username, 'thread_' . $mailbox_array[0], $set_thread & 1);
	asearch_edit_last();
}

/** Toggle show/hide saved searches
 */
if (sqgetGlobalVar('search_show_saved', $search_show_saved, SQ_GET))
	setPref($data_dir, $username, 'search_show_saved', $search_show_saved & 1);

/** Toggle show/hide recent searches
 */
if (sqgetGlobalVar('search_show_recent', $search_show_recent, SQ_GET))
	setPref($data_dir, $username, 'search_show_recent', $search_show_recent & 1);

// end of get globals

/** If TRUE, do not show search interface
 * Default FALSE
 * @global bool $search_silent
 */
$search_silent = FALSE;

/*  See how the page was called and fire off correct function  */
if ((empty($submit)) && (!empty($where_array))) {
	/* This happens when the Enter key is used or called from outside */
	$submit = $search_button_text;
	if (count($where_array) != count($unop_array))	/* Hack needed to handle coming back from read_body et als */
		asearch_edit_last();
}

if (!isset($submit)) {
	$submit = '';
}
else {
	switch ($submit) {
		case $search_button_text:
			if (asearch_check_query($where_array, $what_array, $exclude_array) == '')
				asearch_push_recent($mailbox_array, $biop_array, $unop_array, $where_array, $what_array, $exclude_array, $sub_array);
		break;
		case 'Search_silent':
			$search_silent = TRUE;
		/*nobreak;*/
		case 'Search_no_update':
			$submit = $search_button_text;
		break;
		case $del_excluded_button_text:
			$delarray = array_keys($exclude_array);
			while (!empty($delarray)) {
				$delrow = array_pop($delarray);
				array_splice($mailbox_array, $delrow, 1);
				array_splice($biop_array, $delrow, 1);
				array_splice($unop_array, $delrow, 1);
				array_splice($where_array, $delrow, 1);
				array_splice($what_array, $delrow, 1);
/*			array_splice($exclude_array, $delrow, 1);*/	/* There is still some php magic that eludes me */
				array_splice($sub_array, $delrow, 1);
			}
			$exclude_array = array();
		break;
		case $del_all_button_text:
			$mailbox_array = array();
			$biop_array = array();
			$unop_array = array();
			$where_array = array();
			$what_array = array();
			$exclude_array = array();
			$sub_array = array();
		break;
		case 'save_recent':
			asearch_save_recent($submit_rownum);
		break;
		case 'search_recent':
			$submit = $search_button_text;
			asearch_edit_recent($submit_rownum);
			asearch_push_recent($mailbox_array, $biop_array, $unop_array, $where_array, $what_array, $exclude_array, $sub_array);
		break;
		case 'edit_recent':	/* no link to do this, yet */
			asearch_edit_recent($submit_rownum);
		break;
		case 'forget_recent':
			asearch_forget_recent($submit_rownum);
		break;
		case 'search_saved':
			$submit = $search_button_text;
			asearch_edit_saved($submit_rownum);
			asearch_push_recent($mailbox_array, $biop_array, $unop_array, $where_array, $what_array, $exclude_array, $sub_array);
		break;
		case 'edit_saved':
			asearch_edit_saved($submit_rownum);
		break;
		case 'delete_saved':
			asearch_delete_saved($submit_rownum);
		break;
	}
}

//Texts in both basic and advanced form
$imap_asearch_unops = array(
	'' => '',
	'NOT' => _("Not")
);

if ($search_advanced) {
	//Texts in advanced form only
	$imap_asearch_options = array(
		//<message set>,
		//'ALL' is binary operator
		'ANSWERED' => _("Answered"),
		'BCC' => _("Bcc"),
		'BEFORE' => _("Before"),
		'BODY' => _("Message Body"),
		'CC' => _("CC"),
		'DELETED' => _("Deleted"),
		'DRAFT' => _("Draft"),
		'FLAGGED' => _("Flagged"),
		'FROM' => _("Sent By"),
		'HEADER' => _("Header Field"),
		'KEYWORD' => _("Keyword"),
		'LARGER' => _("Larger Than"),
		'NEW' => _("New"),
		//'NOT' is unary operator
		'OLD' => _("Old"),
		'ON' => _("On"),
		//'OR' is binary operator
		'RECENT' => _("Recent"),
		'SEEN' => _("Seen"),
		'SENTBEFORE' => _("Sent Before"),
		'SENTON' => _("Sent On"),
		'SENTSINCE' => _("Sent Since"),
		'SINCE' => _("Since"),
		'SMALLER' => _("Smaller Than"),
		'SUBJECT' => _("Subject Contains"),
		'TEXT' => _("Header and Body"),
		'TO' => _("Sent To"),
		//'UID' => 'anum',
/*	'UNANSWERED' => '',
		'UNDELETED' => '',
		'UNDRAFT' => '',
		'UNFLAGGED' => '',
		'UNKEYWORD' => _("Unkeyword"),
		'UNSEEN' => _("Unseen"),*/
	);

	$imap_asearch_biops_in = array(
		'ALL' => _("And In"),
		'OR' => _("Or In")
	);

	$imap_asearch_biops = array(
		'ALL' => _("And"),
		'OR' => _("Or")
	);
}
else {
	//Texts in basic form only
	$imap_asearch_options = array(
		'BCC' => _("Bcc"),
		'BODY' => _("Body"),
		'CC' => _("Cc"),
		'FROM' => _("From"),
		'SUBJECT' => _("Subject"),
		'TEXT' => _("Everywhere"),
		'TO' => _("To"),
	);
}

uasort($imap_asearch_options, 'asearch_unhtml_strcoll');

/* open IMAP connection */
$imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);
/* get mailboxes once here */
$boxes = sqimap_mailbox_list($imapConnection);
/* ensure we have a valid default mailbox name */
$mailbox = asearch_nz($mailbox_array[0]);
if (($mailbox == '') || ($mailbox == 'None'))	//Workaround for sm quirk IMHO (what if I really have a mailbox called None?)
	$mailbox = $boxes[0]['unformatted'];	//Usually INBOX ;)

if (isset($composenew) && $composenew) {
	$comp_uri = "../src/compose.php?mailbox=" . urlencode($mailbox)
		. "&amp;session=$composesession&amp;attachedmessages=true&amp";
	displayPageHeader($color, $mailbox, "comp_in_new('$comp_uri');", false);
}
else
	displayPageHeader($color, $mailbox);

do_hook('search_before_form');

if (!$search_silent) {
	//Add a link to the other search mode if allowed
	if ($allow_advanced_search > 1)
		$toggle_link = ' - <small>['
			. asearch_get_toggle_link(!$search_advanced, 'advanced', array(_("Standard search"), _("Advanced search")))
			. ']</small>';
	else
		$toggle_link = '';

	echo html_tag('table',
				html_tag('tr', "\n"
				. html_tag('td', asearch_get_title_display($color, _("Search")) . $toggle_link, 'center', $color[0])
					) ,
				'', '', 'width="100%"') . "\n";
	asearch_print_saved($boxes);
	asearch_print_recent($boxes);
	if (empty($where_array)) {
		global $sent_folder;

		$mailbox_array[0] = $mailbox;
		$biop_array[0] = '';
		$unop_array[0] = '';
		if ($mailbox == $sent_folder)
			$where_array[0] = 'TO';
		else
			$where_array[0] = 'FROM';
		$what_array[0] = '';
		$exclude_array[0] = '';
		$sub_array[0] = '';
	}
	//Display advanced or basic form
	if ($search_advanced) {
		if ($submit == $add_criteria_button_text) {
			$last_index = max(count($where_array) - 1, 0);
			$mailbox_array[] = asearch_nz($mailbox_array[$last_index]);
			$biop_array[] = asearch_nz($biop_array[$last_index]);
			$unop_array[] = asearch_nz($unop_array[$last_index]);
			$where_array[] = asearch_nz($where_array[$last_index]);
			$what_array[] = asearch_nz($what_array[$last_index]);
			$exclude_array[] = asearch_nz($exclude_array[$last_index]);
			$sub_array[] = asearch_nz($sub_array[$last_index]);
		}
		asearch_print_form($imapConnection, $boxes, $mailbox_array, $biop_array, $unop_array, $where_array, $what_array, $exclude_array, $sub_array);
	}
	else
		asearch_print_form_basic($imapConnection, $boxes, $mailbox_array, $biop_array, $unop_array, $where_array, $what_array, $exclude_array, $sub_array);
}

/*********************************************************************
 * Check to see if we can use cache or not. Currently the only time  *
 * when you will not use it is when a link on the left hand frame is *
 * used. Also check to make sure we actually have the array in the   *
 * registered session data.  :)                                      *
 *********************************************************************/
if (!isset($use_mailbox_cache))
    $use_mailbox_cache = 0;

do_hook('search_after_form');

if ($submit == $search_button_text) {
	echo html_tag('table', '', 'center', $color[9], 'width="100%" cellpadding="1" cellspacing="0" border="0"');
	echo html_tag('tr', html_tag('td', asearch_get_title_display($color, _("Search Results")), 'center', $color[5]));
	echo html_tag('tr', html_tag('td', asearch_get_query_display($color, $mailbox_array, $biop_array, $unop_array, $where_array, $what_array, $exclude_array, $sub_array), 'center', $color[4]));
	echo '</table>' . "\n";

	flush();

	$query_error = asearch_check_query($where_array, $what_array, $exclude_array);
	if ($query_error != '')
		echo '<br />' . html_tag('div', asearch_get_error_display($color, $query_error), 'center') . "\n";
	else {
		// Disable thread sort for now if there is more than one mailbox or at least one 'All Folders'
		global $allow_thread_sort;
		$old_allow_thread_sort = $allow_thread_sort;
		$allow_thread_sort = ((count(array_unique($mailbox_array)) <= 1) && (!in_array('All Folders', $mailbox_array)));

		$mboxes_array = sqimap_asearch_get_selectable_unformatted_mailboxes($boxes);
		$mboxes_msgs = sqimap_asearch($imapConnection, $mailbox_array, $biop_array, $unop_array, $where_array, $what_array, $exclude_array, $sub_array, $mboxes_array);
		if (empty($mboxes_msgs))
			echo '<br />' . html_tag('div', asearch_get_error_display($color, _("No Messages Found")), 'center') . "\n";
		else {
			foreach($mboxes_msgs as $mailbox => $msgs) {
				echo '<br />';
				$mbxresponse = sqimap_mailbox_select($imapConnection, $mailbox);
				//$msgs = sqimap_get_small_header_list ($imapConnection, $msgs, count($msgs));
/* For now just keep the first criteria to make the regular search happy if the user tries to come back to search */
/*			$where = asearch_serialize($where_array);
				$what = asearch_serialize($what_array);*/
				$where = $where_array[0];
				$what = $what_array[0];
				asearch_print_mailbox_msgs($imapConnection, $mbxresponse, $mailbox, $msgs, count($msgs), $sort, $color, urlencode($where), urlencode($what));
			}
		}

		$allow_thread_sort = $old_allow_thread_sort;	// Restore thread sort
	}
}

do_hook('search_bottom');
sqimap_logout($imapConnection);
echo '</body></html>';

?>