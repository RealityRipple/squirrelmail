<?php

/**
 * search.php
 *
 * Copyright (c) 1999-2003 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * IMAP search page
 *
 * $Id$
 * @package squirrelmail
 * @link ftp://ftp.rfc-editor.org/in-notes/rfc3501.txt
 * @author Alex Lemaresquier - Brainstorm - alex at brainstorm.fr
 *
 * Subfolder search idea from Patch #806075 by Thomas Pohl xraven at users.sourceforge.net. Thanks Thomas!
 */

/** Path for SquirrelMail required files. */
define('SM_PATH','../');

/** SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'functions/strings.php');
require_once(SM_PATH . 'functions/imap_asearch.php');
require_once(SM_PATH . 'functions/imap_mailbox.php');
require_once(SM_PATH . 'functions/imap_messages.php');
require_once(SM_PATH . 'functions/mime.php');
require_once(SM_PATH . 'functions/mailbox_display.php');	//getButton()...

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
 * @param string $mailbox mailbox name
 * @return string mailbox name ready to display
 */
function imap_get_mailbox_display($mailbox)
{
	if (strtoupper($mailbox) == 'INBOX')
		return _("INBOX");
	return imap_utf7_decode_local($mailbox);
}

/**
 * @param string $mailbox mailbox name
 * @return string mailbox name ready to display
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
function asearch_get_title_display($color, $txt)
{
	return '<b><big>' . $txt . '</big></b>';
}

/**
 * @param array $color color array
 * @param string $txt text to display
 * @return string error text ready to display
 */
function asearch_get_error_display($color, $txt)
{
	return '<font color="' . $color[2] . '">' . '<b><big>' . $txt . '</big></b></font>';
}

/**
 * @param array $input_array array to serialize
 * @return string a string containing a byte-stream representation of value that can be stored anywhere
 */
function asearch_serialize($input_array)
{
	return serialize($input_array);
}

/**
 * @param string $input_string string to unserialize
 * @return array
 */
function asearch_unserialize($input_string)
{
	return unserialize($input_string);
}

/**
 * @param string $data_dir prefs data dir or dsn
 * @param string $username the username
 * @param string $key the pref key
 * @param integer $index the pref key index
 * @param string $default default value
 * @return string pref value
 */
function asearch_getPref($data_dir, $username, $key, $index, $default = '')
{
	return getPref($data_dir, $username, $key . $index, $default);
}

/**
 * @param string $data_dir prefs data dir or dsn
 * @param string $username the username
 * @param string $key the pref key
 * @param integer $index the pref key index
 * @param string $value pref value to set
 * @return bool status
 */
function asearch_setPref($data_dir, $username, $key, $index, $value)
{
	return setPref($data_dir, $username, $key . $index, $value);
}

/**
 * @param string $data_dir prefs data dir or dsn
 * @param string $username the username
 * @param string $key the pref key
 * @param integer $index the pref key index
 * @return bool status
 */
function asearch_removePref($data_dir, $username, $key, $index)
{
	return removePref($data_dir, $username, $key . $index);
}

/** Sanity checks, done before running the imap command and before push_recent */
function asearch_check_query($where_array, $what_array, $exclude_array)
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

/** Read the recent searches */
function asearch_read_recent($data_dir, $username)
{
	global $recent_prefkeys;

	$recent_array = array();
	$recent_max = getPref($data_dir, $username, 'search_memory', 0);
	for ($recent_num = 0; $recent_num < $recent_max; $recent_num++) {
		foreach ($recent_prefkeys as $prefkey) {
			$pref = asearch_getPref($data_dir, $username, $prefkey, $recent_num);
/*			if (!empty($pref))*/
				$recent_array[$prefkey][$recent_num] = $pref;
		}
		if (empty($recent_array[$recent_prefkeys[0]][$recent_num])) {
			foreach ($recent_prefkeys as $key) {
				array_pop($recent_array[$key]);
			}
			break;
		}
	}
	return $recent_array;
}

/** Get the saved searches */
function asearch_read_saved($data_dir, $username)
{
	global $saved_prefkeys;

	$saved_array = array();
	$saved_key = $saved_prefkeys[0];
	for ($saved_count = 0; ; $saved_count++) {
		$pref = asearch_getPref($data_dir, $username, $saved_key, $saved_count);
		if (empty($pref))
			break;
	}
	for ($saved_num = 0; $saved_num < $saved_count; $saved_num++) {
		foreach ($saved_prefkeys as $key) {
			$saved_array[$key][$saved_num] = asearch_getPref($data_dir, $username, $key, $saved_num);
		}
	}
	return $saved_array;
}

/** Save a recent search */
function asearch_save_recent($data_dir, $username, $recent_index)
{
	global $recent_prefkeys, $saved_prefkeys;

	$saved_array = asearch_read_saved($data_dir, $username);
	$saved_index = count($saved_array[$saved_prefkeys[0]]);
	$recent_array = asearch_read_recent($data_dir, $username);
	$n = 0;
	foreach ($recent_prefkeys as $key) {
		$recent_slice = array_slice($recent_array[$key], $recent_index, 1);
		if (!empty($recent_slice[0]))
			asearch_setPref($data_dir, $username, $saved_prefkeys[$n], $saved_index, $recent_slice[0]);
		else
			asearch_removePref($data_dir, $username, $saved_prefkeys[$n], $saved_index);
		$n++;
	}
}

/** Write a recent search */
function asearch_write_recent($data_dir, $username, $recent_array)
{
	global $recent_prefkeys;

	$recent_max = getPref($data_dir, $username, 'search_memory', 0);
	$recent_count = min($recent_max, count($recent_array[$recent_prefkeys[0]]));
	for ($recent_num=0; $recent_num < $recent_count; $recent_num++) {
		foreach ($recent_prefkeys as $key) {
			asearch_setPref($data_dir, $username, $key, $recent_num, $recent_array[$key][$recent_num]);
		}
	}
	for (; $recent_num < $recent_max; $recent_num++) {
		foreach ($recent_prefkeys as $key) {
			asearch_removePref($data_dir, $username, $key, $recent_num);
		}
	}
}

/** Forget a recent search  */
function asearch_forget_recent($data_dir, $username, $forget_index)
{
	global $recent_prefkeys;

	$recent_array = asearch_read_recent($data_dir, $username);
	foreach ($recent_prefkeys as $key) {
		array_splice($recent_array[$key], $forget_index, 1);
	}
	asearch_write_recent($data_dir, $username, $recent_array);
}

/** Find a recent search */
function asearch_find_recent($recent_array, $mailbox_array, $biop_array, $unop_array, $where_array, $what_array, $exclude_array, $sub_array)
{
	global $recent_prefkeys;

	$mailbox_string = asearch_serialize($mailbox_array);
	$biop_string = asearch_serialize($biop_array);
	$unop_string = asearch_serialize($unop_array);
	$where_string = asearch_serialize($where_array);
	$what_string = asearch_serialize($what_array);
	$exclude_string = asearch_serialize($exclude_array);
	$sub_string = asearch_serialize($sub_array);
	$recent_count = count($recent_array[$recent_prefkeys[0]]);
	for ($recent_num=0; $recent_num<$recent_count; $recent_num++) {
		if (isset($recent_array[$recent_prefkeys[0]][$recent_num])) {
			if (
					$mailbox_string == $recent_array['asearch_recent_mailbox'][$recent_num] &&
					$biop_string == $recent_array['asearch_recent_biop'][$recent_num] &&
					$unop_string == $recent_array['asearch_recent_unop'][$recent_num] &&
					$where_string == $recent_array['asearch_recent_where'][$recent_num] &&
					$what_string == $recent_array['asearch_recent_what'][$recent_num] &&
					$exclude_string == $recent_array['asearch_recent_exclude'][$recent_num] &&
					$sub_string == $recent_array['asearch_recent_sub'][$recent_num]
					)
				return $recent_num;
		}
	}
	return -1;
}

/** Push a recent search */
function asearch_push_recent($data_dir, $username, $mailbox_array, $biop_array, $unop_array, $where_array, $what_array, $exclude_array, $sub_array)
{
	global $recent_prefkeys;

	$recent_max = getPref($data_dir, $username, 'search_memory', 0);
	if ($recent_max > 0) {
		$recent_array = asearch_read_recent($data_dir, $username);
		$recent_found = asearch_find_recent($recent_array, $mailbox_array, $biop_array, $unop_array, $where_array, $what_array, $exclude_array, $sub_array);
		if ($recent_found >= 0) {	// Remove identical recent
			foreach ($recent_prefkeys as $key) {
				array_splice($recent_array[$key], $recent_found, 1);
			}
		}
		$input = array($where_array, $mailbox_array, $what_array, $biop_array, $unop_array, $exclude_array, $sub_array);
		$i = 0;
		foreach ($recent_prefkeys as $key) {
			array_unshift($recent_array[$key], asearch_serialize($input[$i]));
			$i++;
		}
		asearch_write_recent($data_dir, $username, $recent_array);
	}
}

/**
 * Edit a recent search
 * @global array mailbox_array searched mailboxes
 */
function asearch_edit_recent($data_dir, $username, $index)
{
	global $mailbox_array, $biop_array, $unop_array, $where_array, $what_array, $exclude_array, $sub_array;

	$mailbox_array = asearch_unserialize(asearch_getPref($data_dir, $username, 'asearch_recent_mailbox', $index));
	$biop_array = asearch_unserialize(asearch_getPref($data_dir, $username, 'asearch_recent_biop', $index));
	$unop_array = asearch_unserialize(asearch_getPref($data_dir, $username, 'asearch_recent_unop', $index));
	$where_array = asearch_unserialize(asearch_getPref($data_dir, $username, 'asearch_recent_where', $index));
	$what_array = asearch_unserialize(asearch_getPref($data_dir, $username, 'asearch_recent_what', $index));
	$exclude_array = asearch_unserialize(asearch_getPref($data_dir, $username, 'asearch_recent_exclude', $index));
	$sub_array = asearch_unserialize(asearch_getPref($data_dir, $username, 'asearch_recent_sub', $index));
}

/** Edit the last recent search if the prefs permit it */
function asearch_edit_last($data_dir, $username)
{
	if (getPref($data_dir, $username, 'search_memory', 0) > 0)
		asearch_edit_recent($data_dir, $username, 0);
}

/** Edit a saved search */
function asearch_edit_saved($data_dir, $username, $index)
{
	global $mailbox_array, $biop_array, $unop_array, $where_array, $what_array, $exclude_array, $sub_array;

	$mailbox_array = asearch_unserialize(asearch_getPref($data_dir, $username, 'asearch_saved_mailbox', $index));
	$biop_array = asearch_unserialize(asearch_getPref($data_dir, $username, 'asearch_saved_biop', $index));
	$unop_array = asearch_unserialize(asearch_getPref($data_dir, $username, 'asearch_saved_unop', $index));
	$where_array = asearch_unserialize(asearch_getPref($data_dir, $username, 'asearch_saved_where', $index));
	$what_array = asearch_unserialize(asearch_getPref($data_dir, $username, 'asearch_saved_what', $index));
	$exclude_array = asearch_unserialize(asearch_getPref($data_dir, $username, 'asearch_saved_exclude', $index));
	$sub_array = asearch_unserialize(asearch_getPref($data_dir, $username, 'asearch_saved_sub', $index));
}

/** Write a saved searches */
function asearch_write_saved($data_dir, $username, $saved_array)
{
	global $saved_prefkeys;

	$saved_count = count($saved_array[$saved_prefkeys[0]]);
	for ($saved_num=0; $saved_num < $saved_count; $saved_num++) {
		foreach ($saved_prefkeys as $key) {
			asearch_setPref($data_dir, $username, $key, $saved_num, $saved_array[$key][$saved_num]);
		}
	}
	foreach ($saved_prefkeys as $key) {
		asearch_removePref($data_dir, $username, $key, $saved_count);
	}
}

/** Delete a saved search  */
function asearch_delete_saved($data_dir, $username, $saved_index)
{
	global $saved_prefkeys;

	$saved_array = asearch_read_saved($data_dir, $username);
	$asearch_keys = $saved_prefkeys;
	foreach ($asearch_keys as $key) {
		array_splice($saved_array[$key], $saved_index, 1);
	}
	asearch_write_saved($data_dir, $username, $saved_array);
}

/** Translate the input date to imap date to local date display, so the user can know if the date is wrong or illegal */
function asearch_get_date_display($what)
{
	$what_parts = sqimap_asearch_parse_date($what);
	if (count($what_parts) == 4) {
		if (checkdate($what_parts[2], $what_parts[1], $what_parts[3])) {
			$what_display = date_intl(_("M j, Y"), mktime(0,0,0,$what_parts[2],$what_parts[1],$what_parts[3]));
			/*$what_display = $what_parts[1] . ' ' . getMonthName($what_parts[2]) . ' ' . $what_parts[3];*/
		}
		else
			$what_display = _("(Illegal date)");
	}
	else
		$what_display = _("(Wrong date)");
	return $what_display;
}

/** Translate the query to rough natural display */
function asearch_get_query_display($color, $mailbox_array, $biop_array, $unop_array, $where_array, $what_array, $exclude_array, $sub_array)
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
				$mailbox_display = ' <B>' . asearch_get_mailbox_display($cur_mailbox) . '</B>';
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
			$biop_display = ' <U><I>' . $biop_display . '</I></U>';
			$unop = $unop_array[$crit_num];
			$unop_display = $imap_asearch_unops[$unop];
			$where = $where_array[$crit_num];
			$where_display = $imap_asearch_options[$where];
			if ($unop_display != '')
				$where_display = ' <U><I>' . $unop_display . ' ' . $where_display . '</I></U>';
			else
				$where_display = ' <U><I>' . $where_display . '</I></U>';
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
					$what_display = ' <B>' . $what_display . '</B>';
				}
			}
			else {
				if ($what)
					$what_display = ' ' . asearch_get_error_display($color, _("(Spurious argument)"));
				else
					$what_display = '';
			}
			$query_display .= ' ' . $biop_display . $mailbox_display . $where_display . $what_display;
		}
	}
	return $query_display;
}

/** Handle the alternate row colors */
function asearch_get_row_color($color, $row_num)
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

/** Print a whole query array, recent or saved */
function asearch_print_query_array($query_array, $query_keys, $action_array, $title)
{
	global $color;

	echo "<br>\n";
	echo html_tag('table', '', 'center', $color[9], 'width="95%" cellpadding="1" cellspacing="1" border="0"');
	echo html_tag('tr', html_tag('td', asearch_get_title_display($color, $title), 'center', $color[5], 'colspan=5'));
	$main_key = $query_keys[0];
	$query_count = count($query_array[$main_key]);
	for ($query_num=0, $row_num=0; $query_num<$query_count; $query_num++) {
		if (!empty($query_array[$main_key][$query_num])) {
			echo html_tag('tr', '', '', asearch_get_row_color($color, $row_num));

			unset($search_array);
			foreach ($query_keys as $query_key) {
				$search_array[] = asearch_unserialize($query_array[$query_key][$query_num]);
			}
			$mailbox_array = $search_array[1];
			$biop_array = $search_array[3];
			$unop_array = $search_array[4];
			$where_array = $search_array[0];
			$what_array = $search_array[2];
			$exclude_array = $search_array[5];
			$sub_array = $search_array[6];
			$query_display = asearch_get_query_display($color, $mailbox_array, $biop_array, $unop_array, $where_array, $what_array, $exclude_array, $sub_array);

			echo html_tag('td', $query_num+1, 'right');
			echo html_tag('td', $query_display, 'center', '', 'width="80%"');
			foreach ($action_array as $action => $action_display) {
				echo html_tag('td', '<a href=search.php?submit=' . $action . '&amp;rownum=' . $query_num . '>' . $action_display . '</a>', 'center');
			}

			echo '</tr>' . "\n";
			$row_num++;
		}
	}
	echo '</table>' . "\n";
}

/** Print the saved array */
function asearch_print_saved($data_dir, $username)
{
	global $saved_prefkeys;

	$saved_array = asearch_read_saved($data_dir, $username);
	if (isset($saved_array[$saved_prefkeys[0]])) {
		$saved_count = count($saved_array[$saved_prefkeys[0]]);
		if ($saved_count > 0) {
			$saved_actions = array('edit_saved' => _("edit"), 'search_saved' => _("search"), 'delete_saved' => _("delete"));
			asearch_print_query_array($saved_array, $saved_prefkeys, $saved_actions, _("Saved Searches"));
		}
	}
}

/** Print the recent array */
function asearch_print_recent($data_dir, $username)
{
	global $recent_prefkeys;

	$recent_array = asearch_read_recent($data_dir, $username);
	if (isset($recent_array[$recent_prefkeys[0]])) {
		$recent_count = count($recent_array[$recent_prefkeys[0]]);
		$recent_max = min($recent_count, getPref($data_dir, $username, 'search_memory', 0));
		if ($recent_max > 0) {
			$recent_actions = array('save_recent' => _("save"), 'search_recent' => _("search"), 'forget_recent' => _("forget"));
			asearch_print_query_array($recent_array, $recent_prefkeys, $recent_actions, _("Recent Searches"));
		}
	}
}

/** Build an <option> statement */
function asearch_opt($val, $sel, $tit)
{
    return '<option value="' . $val . '"' . ($sel == $val ? ' selected' : '') . '>' . $tit . '</option>' . "\n";
}

/** Build a <select> statement from an array */
function asearch_opt_array($var_name, $opt_array, $cur_val)
{
	$output = '<select name="' . $var_name . '">' . "\n";
	foreach($opt_array as $val => $display)
		$output .= asearch_opt($val, $cur_val, $display);
	$output .= '</select>' . "\n";
	return $output;
}

/** Verify that a mailbox exists */
function asearch_mailbox_exists($mailbox, $boxes)
{
	foreach ($boxes as $box) {
		if ($box['unformatted'] == $mailbox)
			return TRUE;
	}
	return FALSE;
}

/** Print one form row */
function asearch_print_form_row($imapConnection, $boxes, $mailbox, $biop, $unop, $where, $what, $exclude, $sub, $row_num)
{
	global $imap_asearch_biops_in, $imap_asearch_unops, $imap_asearch_options;
	global $color;

	echo html_tag('tr', '', '', $color[4]);

	echo html_tag('td', '', 'center');
/* Binary operator */
	if ($row_num)
		echo asearch_opt_array('biop[' . $row_num . ']', $imap_asearch_biops_in, $biop);
	else
		echo /*'<input type="hidden" name="biop[0]" value="">' .*/ '<b>' . _("In") . '</b>';
	echo "</td>\n";

	echo html_tag('td', '', 'center');
/* Mailbox list */
	echo '<select name="mailbox[' . $row_num . ']">';
		if (($mailbox != 'All Folders') && (!asearch_mailbox_exists($mailbox, $boxes)))
			echo asearch_opt($mailbox, $mailbox, '[' . _("Missing") . '] ' . asearch_get_mailbox_display($mailbox));
		echo asearch_opt('All Folders', $mailbox, '[' . asearch_get_mailbox_display('All Folders') . ']');
		echo sqimap_mailbox_option_list($imapConnection, array(strtolower($mailbox)), 0, $boxes, NULL);
	echo '</select>' . "\n";

/* Include Subfolders */
	echo _("and&nbsp;subfolders:") . '<input type=checkbox name="sub[' . $row_num .']"' . ($sub ? ' CHECKED' : '') . '></td>' . "\n";

/* Unary operator and Search location */
	echo html_tag('td',
		asearch_opt_array('unop[' . $row_num . ']', $imap_asearch_unops, $unop)
		. asearch_opt_array('where[' . $row_num . ']', $imap_asearch_options, $where),
		'center');

/* Text input */
	$what_disp = htmlspecialchars($what);
	echo html_tag('td', '<input type="text" size="35" name="what[' . $row_num . ']" value="' . $what_disp . '">', 'center') . "\n";

/* Exclude criteria */
	echo html_tag('td',
		_("Exclude Criteria:") . '<input type=checkbox name="exclude[' . $row_num .']"' . ($exclude ? ' CHECKED' : '') . '>', 'center', '') . "\n";

	echo "</tr>\n";
}

/** Print the search form */
function asearch_print_form($imapConnection, $boxes, $mailbox_array, $biop_array, $unop_array, $where_array, $what_array, $exclude_array, $sub_array)
{
	global $search_button_html, $add_criteria_button_html, $del_excluded_button_html, $del_all_button_html;
	global $color;

	/* Search Form */
	echo "<br>\n";
	echo '<form action="search.php" name="form_asearch">' . "\n";

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

/* Submit buttons */
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

/** Print the $msgs messages from $mailbox mailbox */
function asearch_print_mailbox_msgs($imapConnection, $mailbox, $msgs, $cnt, $sort, $color, $where, $what)
{
	if ($cnt > 0) {
		global $allow_server_sort, $allow_thread_sort, $thread_sort_messages;

		$thread_sort_messages = 0;
		if ($allow_thread_sort) {
			global $data_dir, $username;
			$thread_sort_messages = getPref($data_dir, $username, 'thread_' . $mailbox);
			$msort = $msgs;
			$real_sort = 6;
		}
		elseif ($allow_server_sort) {
			$msort = $msgs;
			$real_sort = 6;
		}
		else {
			$msort = calc_msort($msgs, $sort);
			$real_sort = $sort;
		}
		$mailbox_display = asearch_get_mailbox_display($mailbox);
		echo html_tag('div', '<b><big>' . _("Folder:") . ' '. $mailbox_display . '</big></b>','center') . "\n";

		$msg_cnt_str = get_msgcnt_str(1, $cnt, $cnt);
		$toggle_all = get_selectall_link(1, $real_sort);

		echo '<table border="0" width="100%" cellpadding="0" cellspacing="0">';

		echo '<tr><td>';
		mail_message_listing_beginning($imapConnection, $mailbox, $real_sort, $msg_cnt_str, $toggle_all, 1);
		echo '</td></tr>';

		echo '<tr><td HEIGHT="5" BGCOLOR="'.$color[4].'"></td></tr>';  

		echo '<tr><td>';
		echo '    <table width="100%" cellpadding="1" cellspacing="0" align="center"'.' border="0" bgcolor="'.$color[9].'">';
		echo '     <tr><td>';

		echo '       <table width="100%" cellpadding="1" cellspacing="0" align="center" border="0" bgcolor="'.$color[5].'">';
		echo '        <tr><td>';
		printHeader($mailbox, $sort, $color, !$thread_sort_messages);
		displayMessageArray($imapConnection, $cnt, 1, $msort, $mailbox, $real_sort, $color, $cnt, $where, $what);
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
	for ($boxnum=0; $boxnum<$boxcount; $boxnum++) {
		if (!in_array('noselect', $boxes[$boxnum]['flags']))
			$mboxes_array[] = $boxes[$boxnum]['unformatted'];
	}
	return $mboxes_array;
}

/* ------------------------ main ------------------------ */
/* get globals we may need */
sqgetGlobalVar('username', $username, SQ_SESSION);
sqgetGlobalVar('key', $key, SQ_COOKIE);
sqgetGlobalVar('delimiter', $delimiter, SQ_SESSION);	/* we really need this? */
sqgetGlobalVar('onetimepad', $onetimepad, SQ_SESSION);	/* do we really need this? */

$recent_prefkeys = array('asearch_recent_where', 'asearch_recent_mailbox', 'asearch_recent_what', 'asearch_recent_biop', 'asearch_recent_unop', 'asearch_recent_exclude', 'asearch_recent_sub');
$saved_prefkeys = array('asearch_saved_where', 'asearch_saved_mailbox', 'asearch_saved_what', 'asearch_saved_biop', 'asearch_saved_unop', 'asearch_saved_exclude', 'asearch_saved_sub');
/*$asearch_keys = array('where', 'mailbox', 'what', 'biop', 'unop', 'exclude', 'sub');*/

$search_button_html = _("Search");
$search_button_text = asearch_unhtmlentities($search_button_html);
$add_criteria_button_html = _("Add New Criteria");
$add_criteria_button_text = asearch_unhtmlentities($add_criteria_button_html);
$del_excluded_button_html = _("Remove Excluded Criteria");
$del_excluded_button_text = asearch_unhtmlentities($del_excluded_button_html);
$del_all_button_html = _("Remove All Criteria");
$del_all_button_text = asearch_unhtmlentities($del_all_button_html);

$imap_asearch_options = array(
/* <message set>, */
/*'ALL' is binary operator */
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
/*'NOT' is unary operator */
	'OLD' => _("Old"),
	'ON' => _("On"),
/*'OR' is binary operator */
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
/*'UID' => 'anum',*/
/*'UNANSWERED' => '',
	'UNDELETED' => '',
	'UNDRAFT' => '',
	'UNFLAGGED' => '',
	'UNKEYWORD' => _("Unkeyword"),
	'UNSEEN' => _("Unseen"),*/
);
uasort($imap_asearch_options, 'asearch_unhtml_strcoll');

$imap_asearch_unops = array(
	'' => '',
	'NOT' => _("Not")
);

$imap_asearch_biops_in = array(
	'ALL' => _("And In"),
	'OR' => _("Or In")
);

$imap_asearch_biops = array(
	'ALL' => _("And"),
	'OR' => _("Or")
);

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
	asearch_edit_last($data_dir, $username);
}

/** Change mailbox threading
 */
if (sqgetGlobalVar('set_thread', $set_thread, SQ_GET)) {
	setPref($data_dir, $username, 'thread_' . $mailbox_array[0], ($set_thread == 1) ? 1 : 0 );
	asearch_edit_last($data_dir, $username);
}

/* end of get globals */

/** If TRUE, do not show search interface
 * @global bool $search_silent
 */
$search_silent = FALSE;	/* Default is normal behaviour */

/*  See how the page was called and fire off correct function  */
if ((!isset($submit) || empty($submit)) && !empty($where_array)) {
	/* This happens when the Enter key is used or called from outside */
	$submit = $search_button_text;
	if (count($where_array) != count($unop_array))	/* Hack needed to handle coming back from read_body et als */
		asearch_edit_last($data_dir, $username);
}

if (!isset($submit)) {
	$submit = '';
}
else {
	switch ($submit) {
		case $search_button_text:
			if (asearch_check_query($where_array, $what_array, $exclude_array) == '')
				asearch_push_recent($data_dir, $username, $mailbox_array, $biop_array, $unop_array, $where_array, $what_array, $exclude_array, $sub_array);
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
			asearch_save_recent($data_dir, $username, $submit_rownum);
		break;
		case 'search_recent':
			$submit = $search_button_text;
			asearch_edit_recent($data_dir, $username, $submit_rownum);
			asearch_push_recent($data_dir, $username, $mailbox_array, $biop_array, $unop_array, $where_array, $what_array, $exclude_array, $sub_array);
		break;
		case 'edit_recent':	/* no link to do this, yet */
			asearch_edit_recent($data_dir, $username, $submit_rownum);
		break;
		case 'forget_recent':
			asearch_forget_recent($data_dir, $username, $submit_rownum);
		break;
		case 'search_saved':
			$submit = $search_button_text;
			asearch_edit_saved($data_dir, $username, $submit_rownum);
			asearch_push_recent($data_dir, $username, $mailbox_array, $biop_array, $unop_array, $where_array, $what_array, $exclude_array, $sub_array);
		break;
		case 'edit_saved':
			asearch_edit_saved($data_dir, $username, $submit_rownum);
		break;
		case 'delete_saved':
			asearch_delete_saved($data_dir, $username, $submit_rownum);
		break;
	}
}

/* open IMAP connection */
$imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);
/* get mailbox names once here */
$boxes = sqimap_mailbox_list($imapConnection);
/* ensure we have a valid default mailbox name */
$mailbox = asearch_nz($mailbox_array[0]);
if (($mailbox == '') || ($mailbox == 'None'))	//Workaround for sm quirk IMHO (what if I really have a mailbox called None?)
	$mailbox = $boxes[0]['unformatted'];	//Usually INBOX ;)

if (isset($composenew) && $composenew) {
	$comp_uri = "../src/compose.php?mailbox=" . urlencode($mailbox) .
		"&amp;session=$composesession&amp;attachedmessages=true&amp";
	displayPageHeader($color, $mailbox, "comp_in_new('$comp_uri');", false);
}
else
	displayPageHeader($color, $mailbox);

do_hook('search_before_form');

if (!$search_silent) {
	echo html_tag('table',
				html_tag('tr', "\n" .
					html_tag('td', asearch_get_title_display($color, _("Search")), 'center', $color[0])
					) ,
				'', '', 'width="100%"') . "\n";
	asearch_print_saved($data_dir, $username);
	asearch_print_recent($data_dir, $username);
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
	echo '</table><br>' . "\n";

	$query_error = asearch_check_query($where_array, $what_array, $exclude_array);
	if ($query_error != '')
		echo '<br>' . html_tag('div', asearch_get_error_display($color, $query_error), 'center') . "\n";
	else {

		// Disable thread sort for now if there is more than one mailbox or at least one 'All Folders'
		global $allow_thread_sort;
		$old_allow_thread_sort = $allow_thread_sort;
		$allow_thread_sort = ((count(array_unique($mailbox_array)) <= 1) && (!in_array('All Folders', $mailbox_array)));

		$mboxes_array = sqimap_asearch_get_selectable_unformatted_mailboxes($boxes);
		$mboxes_msgs = sqimap_asearch($imapConnection, $mailbox_array, $biop_array, $unop_array, $where_array, $what_array, $exclude_array, $sub_array, $mboxes_array);
		if (empty($mboxes_msgs))
			echo '<br>' . html_tag('div', asearch_get_error_display($color, _("No Messages Found")), 'center') . "\n";
		else {
			foreach($mboxes_msgs as $mailbox => $msgs) {
					sqimap_mailbox_select($imapConnection, $mailbox);
					$msgs = fillMessageArray($imapConnection, $msgs, count($msgs));
/* For now just keep the first criteria to make the regular search happy if the user tries to come back to search */
/*				$where = asearch_serialize($where_array);
					$what = asearch_serialize($what_array);*/
					$where = $where_array[0];
					$what = $what_array[0];
					asearch_print_mailbox_msgs($imapConnection, $mailbox, $msgs, count($msgs), $sort, $color, urlencode($where), urlencode($what));
			}
		}

		$allow_thread_sort = $old_allow_thread_sort;	// Restore thread sort
	}
}

do_hook('search_bottom');
sqimap_logout($imapConnection);
noframes_bottom();

?>