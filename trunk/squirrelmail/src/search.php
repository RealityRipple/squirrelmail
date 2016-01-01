<?php

/**
 * search.php
 *
 * IMAP search page
 *
 * Subfolder search idea from Patch #806075 by Thomas Pohl xraven at users.sourceforge.net. Thanks Thomas!
 *
 * @author Alex Lemaresquier - Brainstorm <alex at brainstorm.fr>
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage search
 * @link http://www.ietf.org/rfc/rfc3501.txt
 * @todo explain why references are used in function calls
 */

/** This is the search page */
define('PAGE_NAME', 'search');

/**
 * Include the SquirrelMail initialization file.
 */
require('../include/init.php');

/** SquirrelMail required files.
 */
require_once(SM_PATH . 'functions/imap_asearch.php');
require_once(SM_PATH . 'functions/imap_messages.php');
require_once(SM_PATH . 'functions/imap_general.php');
require_once(SM_PATH . 'functions/mime.php');
require_once(SM_PATH . 'functions/mailbox_display.php'); //sqm_api_mailbox_select
require_once(SM_PATH . 'functions/forms.php');
require_once(SM_PATH . 'functions/date.php');
require_once(SM_PATH . 'functions/compose.php');

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


/**
 * Array sort callback used to sort $imap_asearch_options
 * @param string $a
 * @param string $b
 * @return bool strcoll()-like result
 * @since 1.5.0
 * @private
 */
function asearch_unhtml_strcoll($a, $b)
{
    // FIXME: Translation policy says "no html entities in translations"
    return strcoll(asearch_unhtmlentities($a), asearch_unhtmlentities($b));
}

/**
 * @param string $mailbox mailbox name or special case 'All Folders'
 * @return string mailbox name ready to display (utf7 decoded or localized 'All Folders')
 * @since 1.5.0
 * @private
 */
function asearch_get_mailbox_display($mailbox)
{
    if ($mailbox == 'All Folders') {
        return _("All Folders");
    } elseif (strtoupper($mailbox) == 'INBOX') {
        return _("INBOX");
    }
    return imap_utf7_decode_local($mailbox);
}

/**
 * @param array $input_array array to serialize
 * @return string a string containing a byte-stream representation of value that can be stored anywhere
 * @since 1.5.0
 * @private
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
 * @since 1.5.0
 * @private
 */
function asearch_unserialize($input_string)
{
    global $search_advanced;
    if ($search_advanced)
        return unserialize($input_string);
    return array($input_string);
}

/**
 * Gets user's advanced search preferences
 *
 * Arguments are different in 1.5.0.
 * @param string $key the pref key
 * @param integer $index the pref key index
 * @param string $default default value
 * @return string pref value
 * @since 1.5.0
 * @private
 */
function asearch_getPref(&$key, $index, $default = '')
{
    global $data_dir, $username, $search_advanced;
    return getPref($data_dir, $username, $key . ($index + !$search_advanced), $default);
}

/**
 * Sets user's advanced search preferences
 *
 * Arguments are different in 1.5.0.
 * @param string $key the pref key
 * @param integer $index the pref key index
 * @param string $value pref value to set
 * @return bool status
 * @since 1.5.0
 * @private
 */
function asearch_setPref(&$key, $index, $value)
{
    global $data_dir, $username, $search_advanced;
    return setPref($data_dir, $username, $key . ($index + !$search_advanced), $value);
}

/**
 * Deletes user's advanced search preferences
 *
 * Arguments are different in 1.5.0.
 * @param string $key the pref key
 * @param integer $index the pref key index
 * @return bool status
 * @since 1.5.0
 * @private
 */
function asearch_removePref(&$key, $index)
{
    global $data_dir, $username, $search_advanced;
    return removePref($data_dir, $username, $key . ($index + !$search_advanced));
}

/**
 * Sanity checks, done before running the imap command and before calling push_recent
 * @param array $where_array search location data
 * @param array $what_array search criteria data
 * @param array $exclude_array excluded criteria data
 * @return string error message or empty string
 * @since 1.5.0
 * @private
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

/**
 * Read the recent searches from the prefs
 *
 * Function arguments are different in 1.5.0
 * @return array recent searches
 * @since 1.5.0
 * @private
 */
function asearch_read_recent()
{
    global $recent_prefkeys, $search_memory;

    $recent_array = array();
    $recent_num = 0;
    for ($pref_num = 0; $pref_num < $search_memory; $pref_num++) {
        foreach ($recent_prefkeys as $prefkey) {
            $pref = asearch_getPref($prefkey, $pref_num);
/*            if (!empty($pref))*/
                $recent_array[$prefkey][$recent_num] = $pref;
        }
        if (empty($recent_array[$recent_prefkeys[0]][$recent_num])) {
            foreach ($recent_prefkeys as $key) {
                array_pop($recent_array[$key]);
            }
//            break; //Disabled to support old search code broken prefs
        }
        else
            $recent_num++;
    }
    return $recent_array;
}

/**
 * Read the saved searches from the prefs
 *
 * Function arguments are different in 1.5.0
 * @return array saved searches
 * @since 1.5.0
 * @private
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

/**
 * Save a recent search to the prefs
 *
 * Function arguments are different in 1.5.0
 * @param integer $recent_index
 * @since 1.5.0
 * @private
 */
function asearch_save_recent($recent_index)
{
    global $recent_prefkeys, $saved_prefkeys;

    $saved_array = asearch_read_saved();
    if (isset($saved_array[$saved_prefkeys[0]])) {
        $saved_index = count($saved_array[$saved_prefkeys[0]]);
    } else {
        $saved_index = 0;
    }
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

/**
 * Write a recent search to prefs
 *
 * Function arguments are different in 1.5.0
 * @param array $recent_array
 * @since 1.5.0
 * @private
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

/**
 * Remove a recent search from prefs
 *
 * Function arguments are different in 1.5.0
 * @param integer $forget_index removed search number
 * @since 1.5.0
 * @private
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

/**
 * Find a recent search in the prefs (used to avoid saving duplicates)
 * @param array $recent_array
 * @param array $mailbox_array
 * @param array $biop_array
 * @param array $unop_array
 * @param array $where_array
 * @param array $what_array
 * @param array $exclude_array
 * @param array $sub_array
 * @return integer
 * @since 1.5.0
 * @private
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

/**
 * Push a recent search into the prefs
 * @param array $recent_array
 * @param array $mailbox_array
 * @param array $biop_array
 * @param array $unop_array
 * @param array $where_array
 * @param array $what_array
 * @param array $exclude_array
 * @param array $sub_array
 * @since 1.5.0
 * @private
 */
function asearch_push_recent(&$mailbox_array, &$biop_array, &$unop_array, &$where_array, &$what_array, &$exclude_array, &$sub_array)
{
    global $recent_prefkeys, $search_memory;
    //global $what; // Hack to access issued search from read_body.php
    $what = 1;
    /**
     * Update search history and store it in the session so we can retrieve the
     * issued search when returning from an external page like read_body.php
     */
    $criteria[$what] = array($mailbox_array, $biop_array, $unop_array, $where_array, $what_array, $exclude_array, $sub_array);
    sqsession_register($criteria, ASEARCH_CRITERIA);
    if ($search_memory > 0) {
        $recent_array = asearch_read_recent();
        $recent_found = asearch_find_recent($recent_array, $mailbox_array, $biop_array, $unop_array, $where_array, $what_array, $exclude_array, $sub_array);
        if ($recent_found >= 0) { // Remove identical recent
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

/**
 * Edit a recent search
 *
 * Function arguments are different in 1.5.0
 * @global array mailbox_array searched mailboxes
 * @param mixed $index
 * @since 1.5.0
 * @private
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

/**
 * Get last search criteria from session or prefs
 *
 * Function arguments are different in 1.5.0
 * FIXME, try to avoid globals
 * @param mixed $index
 * @since 1.5.0
 * @private
 */
function asearch_edit_last($index) {
    if (sqGetGlobalVar(ASEARCH_CRITERIA, $criteria, SQ_SESSION)) {
        global $where_array, $mailbox_array, $what_array, $unop_array;
        global $biop_array, $exclude_array, $sub_array;
        $mailbox_array = $criteria[$index][0];
        $biop_array = $criteria[$index][1];
        $unop_array = $criteria[$index][2];
        $where_array = $criteria[$index][3];
        $what_array = $criteria[$index][4];
        $exclude_array = $criteria[$index][5];
        $sub_array = $criteria[$index][6];
        unset($criteria[$index]);
        //sqsession_unregister(ASEARCH_CRITERIA);
    } else {
        global $search_memory;
        if ($search_memory > 0) {
            asearch_edit_recent(0);
        }
    }
}

/**
 * Edit a saved search
 *
 * Function arguments are different in 1.5.0
 * @param mixed $index
 * @since 1.5.0
 * @private
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

/**
 * Write a saved search to the prefs
 *
 * Function arguments are different in 1.5.0
 * @param array $saved_array
 * @since 1.5.0
 * @private
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

/**
 * Delete a saved search from the prefs
 *
 * Function arguments are different in 1.5.0
 * @param integer $saved_index
 * @since 1.5.0
 * @private
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
 * @param string $what date string
 * @return string locally formatted date or error text
 * @since 1.5.0
 * @private
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

/**
 * Translate the query to rough natural display
 * @param array $color
 * @param array $mailbox_array
 * @param array $biop_array
 * @param array $unop_array
 * @param array $where_array
 * @param array $what_array
 * @param array $exclude_array
 * @param array $sub_array
 * @return string rough natural query ready to display
 * @since 1.5.0
 * @private
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
                $mailbox_display = ' <span class="mailbox">' . sm_encode_html_special_chars(asearch_get_mailbox_display($cur_mailbox)) . '</span>';
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
            if ($what_type) { /* Check opcode parameter */
                if ($what == '')
                    $what_display = ' <span class="error">' . _("(Missing argument)") .'</span>';
                else {
                    if ($what_type == 'adate')
                        $what_display = asearch_get_date_display($what);
                    else
                        $what_display = sm_encode_html_special_chars($what);
                    $what_display = ' <span class="value">' . $what_display . '</span>';
                }
            }
            else {
                if ($what)
                    $what_display = ' <span class="error">' . _("(Spurious argument)") .'</span>';
                else
                    $what_display = '';
            }
            if ($mailbox_display != '')
                $query_display .= ' <span class="operator">' . $biop_display . '</span>' . $mailbox_display . ' <span class="conditions">' . $where_display . '</span>' . $what_display;
            else
                $query_display .= ' <span class="operator">' . $biop_display . '</span> <span class="conditions">' . $where_display . '</span>' . $what_display;
        }
    }
    return $query_display;
}

/**
 * Print a whole query array, recent or saved
 *
 * Function arguments are different in 1.5.0
 * @param array $boxes (unused)
 * @param array $query_array
 * @param mixed $query_keys
 * @param array $action_array
 * @param mixed $title
 * @param string $show_pref
 * @since 1.5.0
 * @private
 */
function asearch_print_query_array(&$boxes, &$query_array, &$query_keys, &$action_array, $title, $show_pref)
{
    global $data_dir, $username;
    global $icon_theme_path;
    global $oTemplate;

    $show_flag = getPref($data_dir, $username, $show_pref, 0) & 1;
    $a = array();
    $main_key = $query_keys[ASEARCH_WHERE];
    $query_count = count($query_array[$main_key]);
    for ($query_num = 0, $row_num = 0; $query_num < $query_count; $query_num++) {
        if (!empty($query_array[$main_key][$query_num])) {
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
            
            $a[$query_num] = $query_display;
        }
    }
            
    $oTemplate->assign('list_title', $title);
    $oTemplate->assign('show_list', $show_flag==1);
    $oTemplate->assign('is_recent_list', $title==_("Recent Searches"));
    $oTemplate->assign('expand_collapse_toggle', '../src/search.php?'.$show_pref.'='.($show_flag==1 ? 0 : 1));
    $oTemplate->assign('query_list', $a);
    
    $oTemplate->assign('save_recent', '../src/search.php?submit=save_recent&smtoken=' . sm_generate_security_token() . '&rownum=');
    $oTemplate->assign('do_recent', '../src/search.php?submit=search_recent&smtoken=' . sm_generate_security_token() . '&rownum=');
    $oTemplate->assign('forget_recent', '../src/search.php?submit=forget_recent&smtoken=' . sm_generate_security_token() . '&rownum=');
    
    $oTemplate->assign('edit_saved', '../src/search.php?submit=edit_saved&smtoken=' . sm_generate_security_token() . '&rownum=');
    $oTemplate->assign('do_saved', '../src/search.php?submit=search_saved&smtoken=' . sm_generate_security_token() . '&rownum=');
    $oTemplate->assign('delete_saved', '../src/search.php?submit=delete_saved&smtoken=' . sm_generate_security_token() . '&rownum=');
    
    $oTemplate->display('search_list.tpl');
}

/** Print the saved array
 *
 * Function arguments are different in 1.5.0
 * @param array $boxes (unused, see asearch_print_query_array())
 * @since 1.5.0
 * @private
 */
function asearch_print_saved(&$boxes)
{
    global $saved_prefkeys;

    $saved_array = asearch_read_saved();
    if (isset($saved_array[$saved_prefkeys[0]])) {
        $saved_count = count($saved_array[$saved_prefkeys[0]]);
        if ($saved_count > 0) {
            $saved_actions = array('edit_saved' => _("Edit"), 'search_saved' => _("Search"), 'delete_saved' => _("Delete"));
            asearch_print_query_array($boxes, $saved_array, $saved_prefkeys, $saved_actions, _("Saved Searches"), 'search_show_saved');
        }
    }
}

/**
 * Print the recent array
 *
 * Function arguments are different in 1.5.0
 * @param array $boxes (unused, see asearch_print_query_array())
 * @since 1.5.0
 * @private
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

/** Verify that a mailbox exists
 * @param string $mailbox
 * @param array $boxes
 * @return bool mailbox exists
 * @deprecated FIXME use standard functions
 * @since 1.5.0
 * @private
 */
function asearch_mailbox_exists($mailbox, &$boxes)
{
    foreach ($boxes as $box) {
        if ($box['unformatted'] == $mailbox)
            return TRUE;
    }
    return FALSE;
}

/** Print the advanced search form
 * @param stream $imapConnection
 * @param array $boxes
 * @param array $mailbox_array
 * @param array $biop_array
 * @param array $unop_array
 * @param array $where_array
 * @param array $what_array
 * @param array $exclude_array
 * @param array $sub_array
 * @since 1.5.0
 * @private
 */
function asearch_print_form($imapConnection, &$boxes, $mailbox_array, $biop_array, $unop_array, $where_array, $what_array, $exclude_array, $sub_array)
{
    global $oTemplate, $allow_advanced_search, $search_advanced, 
           $imap_asearch_unops, $imap_asearch_biops_in, $imap_asearch_options;

    # Build the criteria array
    $c = array();
    for ($row_num = 0; $row_num < count($where_array); $row_num++) {
        $mailbox = asearch_nz($mailbox_array[$row_num]);
        $a = array();
        $a['MailboxSel'] = asearch_nz($mailbox_array[$row_num]);
        $a['LogicSel'] = strip_tags(asearch_nz($biop_array[$row_num]));
        $a['UnarySel'] = strip_tags(asearch_nz($unop_array[$row_num]));
        $a['WhereSel'] = strip_tags(asearch_nz($where_array[$row_num]));
        $a['What'] = asearch_nz($what_array[$row_num]);
        $a['Exclude'] = strip_tags(asearch_nz($exclude_array[$row_num])) == 'on';
        $a['IncludeSubfolders'] = strip_tags(asearch_nz($sub_array[$row_num])) == 'on';
        
        $c[$row_num] = $a;
    }
        
    # Build the mailbox array
    $a = array();
    if (($mailbox != 'All Folders') && (!asearch_mailbox_exists($mailbox, $boxes))) {
        $a[$mailbox] = '[' . _("Missing") . '] ' . sm_encode_html_special_chars(asearch_get_mailbox_display($mailbox));
    }
    $a['All Folders'] = '[' . asearch_get_mailbox_display('All Folders') . ']';
    $a = array_merge($a, sqimap_mailbox_option_array($imapConnection, 0, $boxes, NULL));
    
    if ($allow_advanced_search > 1) {
        $link = '../src/search.php?advanced='.($search_advanced ? 0 : 1);
        $txt = $search_advanced ? _("Standard Search") : _("Advanced search");
    } else {
        $link = NULL;
        $txt = NULL;
    }
           
    $oTemplate->assign('allow_advanced_search', $allow_advanced_search > 1);
    $oTemplate->assign('adv_toggle_text', $txt);
    $oTemplate->assign('adv_toggle_link', $link);
    
    $oTemplate->assign('mailbox_options', $a);
    $oTemplate->assign('logical_options', $imap_asearch_biops_in);
    $oTemplate->assign('unary_options', $imap_asearch_unops);
    $oTemplate->assign('where_options', $imap_asearch_options);

    $oTemplate->assign('criteria', $c);
    
    echo '<form action="../src/search.php" name="form_asearch">' . "\n"
       . addHidden('smtoken', sm_generate_security_token()) . "\n";
    $oTemplate->display('search_advanced.tpl');
    echo "</form>\n";
}

/** Print the basic search form
 * @param stream $imapConnection
 * @param array $boxes
 * @param array $mailbox_array
 * @param array $biop_array
 * @param array $unop_array
 * @param array $where_array
 * @param array $what_array
 * @param array $exclude_array
 * @param array $sub_array
 * @since 1.5.1
 * @private
 */
function asearch_print_form_basic($imapConnection, &$boxes, $mailbox_array, $biop_array, $unop_array, $where_array, $what_array, $exclude_array, $sub_array)
{
    global $allow_advanced_search, $search_advanced, $oTemplate, $imap_asearch_unops, $imap_asearch_options;

    $row_num = 0;
    $mailbox = asearch_nz($mailbox_array[$row_num]);
    $biop = strip_tags(asearch_nz($biop_array[$row_num]));
    $unop = strip_tags(asearch_nz($unop_array[$row_num]));
    $where = strip_tags(asearch_nz($where_array[$row_num]));
    $what = asearch_nz($what_array[$row_num]);
    $exclude = strip_tags(asearch_nz($exclude_array[$row_num]));
    $sub = strip_tags(asearch_nz($sub_array[$row_num]));

    # Build the mailbox array
    $a = array();
    if (($mailbox != 'All Folders') && (!asearch_mailbox_exists($mailbox, $boxes))) {
        $a[$mailbox] = '[' . _("Missing") . '] ' . sm_encode_html_special_chars(asearch_get_mailbox_display($mailbox));
    }
    $a['All Folders'] = '[' . asearch_get_mailbox_display('All Folders') . ']';
    $a = array_merge($a, sqimap_mailbox_option_array($imapConnection, 0, $boxes, NULL));
        
    if ($allow_advanced_search > 1) {
        $link = '../src/search.php?advanced='.($search_advanced ? 0 : 1);
        $txt = $search_advanced ? _("Standard Search") : _("Advanced search");
    } else {
        $link = NULL;
        $txt = NULL;
    }
           
    $oTemplate->assign('allow_advanced_search', $allow_advanced_search > 1);
    $oTemplate->assign('adv_toggle_text', $txt);
    $oTemplate->assign('adv_toggle_link', $link);

    $oTemplate->assign('mailbox_options', $a);
    $oTemplate->assign('unary_options', $imap_asearch_unops);
    $oTemplate->assign('where_options', $imap_asearch_options);
    
    $oTemplate->assign('mailbox_sel', strtolower(sm_encode_html_special_chars($mailbox)));
    $oTemplate->assign('unary_sel', $unop);
    $oTemplate->assign('where_sel', $where);
    $oTemplate->assign('what_val', $what);
        
    echo '<form action="../src/search.php" name="form_asearch">' . "\n"
       . addHidden('smtoken', sm_generate_security_token()) . "\n";
    $oTemplate->display('search.tpl');
    echo "</form>\n";
}


/**
 * @param array $boxes mailboxes array (reference)
 * @return array selectable unformatted mailboxes names
 * @since 1.5.0
 * @private
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
sqgetGlobalVar('smtoken', $submitted_token, SQ_FORM, '');
sqgetGlobalVar('delimiter', $delimiter, SQ_SESSION);

if (!sqgetGlobalVar('checkall',$checkall,SQ_GET)) {
    $checkall = false;
}

if (!sqgetGlobalVar('preselected', $preselected, SQ_GET) || !is_array($preselected)) {
    $preselected = array();
} else {
    $preselected = array_keys($preselected);
}

/**
 * Retrieve the mailbox cache from the session.
 */
sqgetGlobalVar('mailbox_cache',$mailbox_cache,SQ_SESSION);

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
 */
$allow_advanced_search = asearch_nz($allow_advanced_search, 2);

/**
 * Toggle advanced/basic search
 */
if (sqgetGlobalVar('advanced', $search_advanced, SQ_GET)) {
    setPref($data_dir, $username, 'search_advanced', $search_advanced & 1);
}
/** If 1, show advanced search interface
 * Default from allow_advanced_search pref
 * @global integer $search_advanced
 */
if ($allow_advanced_search > 1) {
    $search_advanced = getPref($data_dir, $username, 'search_advanced', 0);
} else {
    $search_advanced = $allow_advanced_search;
}
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
} else {
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
$searchpressed = false;
//FIXME: Why is there so much access to $_GET in this file?  What's wrong with sqGetGlobalVar?
if (isset($_GET['submit'])) {
    $submit = strip_tags($_GET['submit']);
}

/** Searched mailboxes
 * @global array $mailbox_array
 */
/* when using compact paginator, mailbox might be indicated in $startMessage, so look for it now ($startMessage is then processed farther below) */
$mailbox = '';
$startMessage = '';
if (sqGetGlobalVarMultiple('startMessage', $temp, 'paginator_submit', SQ_FORM)) {
    if (strstr($temp, '_')) list($startMessage, $mailbox) = explode('_', $temp);
    else $startMessage = $temp;
}
if (empty($mailbox)) sqGetGlobalVar('mailbox', $mailbox, SQ_GET, '');
if (!empty($mailbox)) {
    $mailbox_array = $mailbox;
    $targetmailbox = $mailbox;
    if (!is_array($mailbox_array)) {
        $mailbox_array = array($mailbox_array);
    }
} else {
    $mailbox_array = array();
    $targetmailbox = array();
}
$aMailboxGlobalPref = array(
                       MBX_PREF_SORT         => 0,
                       MBX_PREF_LIMIT        => (int)  $show_num,
                       MBX_PREF_AUTO_EXPUNGE => (bool) $auto_expunge,
                       MBX_PREF_INTERNALDATE => (bool) getPref($data_dir, $username, 'internal_date_sort')
                    // MBX_PREF_FUTURE       => (var)  $future
                     );

/**
 * system wide admin settings and incoming vars.
 */
$aConfig = array(
//                'allow_thread_sort' => $allow_thread_sort,
//                'allow_server_sort' => $allow_server_sort,
                'user'              => $username,
                'setindex'          => 1
                );

/** Binary operators
 * @global array $biop_array
 */
//FIXME: Why is there so much access to $_GET in this file?  What's wrong with sqGetGlobalVar?
if (isset($_GET['biop'])) {
    $biop_array = $_GET['biop'];
    if (!is_array($biop_array))
        $biop_array = array($biop_array);
} else {
    $biop_array = array();
}
/** Unary operators
 * @global array $unop_array
 */
//FIXME: Why is there so much access to $_GET in this file?  What's wrong with sqGetGlobalVar?
if (isset($_GET['unop'])) {
    $unop_array = $_GET['unop'];
    if (!is_array($unop_array))
        $unop_array = array($unop_array);
} else {
    $unop_array = array();
}
/** Where to search
 * @global array $where_array
 */
//FIXME: Why is there so much access to $_GET in this file?  What's wrong with sqGetGlobalVar?
if (isset($_GET['where'])) {
    $where_array = $_GET['where'];
    if (!is_array($where_array)) {
        $where_array = array($where_array);
    }
} else {
    $where_array = array();
}
/** What to search
 * @global array $what_array
 */
//FIXME: Why is there so much access to $_GET in this file?  What's wrong with sqGetGlobalVar?
if (isset($_GET['what'])) {
    $what_array = $_GET['what'];
    if (!is_array($what_array)) {
        $what_array = array($what_array);
    }
} else {
    $what_array = array();
}
/** Whether to exclude this criteria from search
 * @global array $exclude_array
 */
//FIXME: Why is there so much access to $_GET in this file?  What's wrong with sqGetGlobalVar?
if (isset($_GET['exclude'])) {
    $exclude_array = $_GET['exclude'];
} else {
    $exclude_array = array();
}
/** Search within subfolders
 * @global array $sub_array
 */
//FIXME: Why is there so much access to $_GET in this file?  What's wrong with sqGetGlobalVar?
if (isset($_GET['sub'])) {
    $sub_array = $_GET['sub'];
} else {
    $sub_array = array();
}
/** Row number used by recent and saved stuff
 */
//FIXME: Why is there so much access to $_GET in this file?  What's wrong with sqGetGlobalVar?
if (isset($_GET['rownum'])) {
    $submit_rownum = strip_tags($_GET['rownum']);
}
/** Change global sort
 */
if (sqgetGlobalVar('srt', $temp, SQ_GET)) {
    $srt = (int) $temp;
    asearch_edit_last(1);
//    asearch_push_recent($mailbox_array, $biop_array, $unop_array, $where_array, $what_array, $exclude_array, $sub_array);
}
/* already retrieved startMessage above */
if (!empty($startMessage)) {
    $startMessage = (int) $startMessage;
    asearch_edit_last(1);
//    asearch_push_recent($mailbox_array, $biop_array, $unop_array, $where_array, $what_array, $exclude_array, $sub_array);
}

if ( sqgetGlobalVar('showall', $temp, SQ_GET) ) {
    $showall = (int) $temp;
    asearch_edit_last(1);
//    asearch_push_recent($mailbox_array, $biop_array, $unop_array, $where_array, $what_array, $exclude_array, $sub_array);
}

if ( sqgetGlobalVar('account', $temp,  SQ_GET) ) {
    $iAccount = (int) $temp;
} else {
    $iAccount = 0;
}

/**
 * Incoming submit buttons from the message list with search results
 */
if (sqgetGlobalVar('moveButton',      $moveButton,      SQ_POST) ||
    sqgetGlobalVar('expungeButton',   $expungeButton,   SQ_POST) ||
    sqgetGlobalVar('delete',          $markDelete,      SQ_POST) ||
    sqgetGlobalVar('undeleteButton',  $undeleteButton,  SQ_POST) ||
    sqgetGlobalVar('markRead',        $markRead,        SQ_POST) ||
    sqgetGlobalVar('markUnread',      $markUnread,      SQ_POST) ||
    sqgetGlobalVar('markFlagged',     $markFlagged,     SQ_POST) ||
    sqgetGlobalVar('markUnflagged',   $markUnflagged,   SQ_POST) ||
    sqgetGlobalVar('attache',         $attache,         SQ_POST)) {
    asearch_edit_last(1);
    $submit = '';
    asearch_push_recent($mailbox_array, $biop_array, $unop_array, $where_array, $what_array, $exclude_array, $sub_array);
}



/** Toggle show/hide saved searches
 */
if (sqgetGlobalVar('search_show_saved', $search_show_saved, SQ_GET)) {
    setPref($data_dir, $username, 'search_show_saved', $search_show_saved & 1);
}
/** Toggle show/hide recent searches
 */
if (sqgetGlobalVar('search_show_recent', $search_show_recent, SQ_GET)) {
    setPref($data_dir, $username, 'search_show_recent', $search_show_recent & 1);
}
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
    /* Hack needed to handle coming back from read_body et als */
    if (count($where_array) != count($unop_array)) {
        /**
         * Hack to use already existen where and what vars.
         * where now contains the initiator page of the messagelist
         * and in this case 'search'. what contains an index to access
         * the search history
         */

        sqgetGlobalVar('what',$what,SQ_GET);
        asearch_edit_last($what);
    }
}

if (!isset($submit)) {
    $submit = '';
} else {

    // first validate security token
    sm_validate_security_token($submitted_token, -1, TRUE);

    switch ($submit) {
      case $search_button_text:
        if (asearch_check_query($where_array, $what_array, $exclude_array) == '') {
            asearch_push_recent($mailbox_array, $biop_array, $unop_array, $where_array, $what_array, $exclude_array, $sub_array);
        }
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
            /* array_splice($exclude_array, $delrow, 1);*/ /* There is still some php magic that eludes me */
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
      case 'edit_recent': /* no link to do this, yet */
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
        'CC' => _("Cc"),
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
/*        'UNANSWERED' => '',
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
} else {
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
global $imap_stream_options; // in case not defined in config
$imapConnection = sqimap_login($username, false, $imapServerAddress, $imapPort, 0, $imap_stream_options);


/* get mailboxes once here */
$boxes = sqimap_mailbox_list($imapConnection);
/* ensure we have a valid default mailbox name */
$mailbox = asearch_nz($mailbox_array[0]);
if ($mailbox == '')
    $mailbox = $boxes[0]['unformatted']; //Usually INBOX ;)


/**
* Handle form actions like flag / unflag, seen / unseen, delete
*/
if (sqgetGlobalVar('mailbox',$postMailbox,SQ_POST)) {
    if ($postMailbox) {
        /**
        * system wide admin settings and incoming vars.
        */
        $aConfig = array(
                        'user'              => $username,
                        );
        $aConfig['setindex'] = 1; // $what $where = 'search'
        /**
         * Set the max cache size to the number of mailboxes to avoid cache cleanups
         * when searching all mailboxes
         */
        $aConfig['max_cache_size'] = count($boxes) +1;

        $aMailbox = sqm_api_mailbox_select($imapConnection, $iAccount, $postMailbox,$aConfig,array());
        $sError = handleMessageListForm($imapConnection,$aMailbox);
        /* add the mailbox to the cache */
        $mailbox_cache[$iAccount.'_'.$aMailbox['NAME']] = $aMailbox;

        if ($sError) {
            $note = $sError;
        }
    }
}

if (isset($aMailbox['FORWARD_SESSION'])) {
    /* add the mailbox to the cache */
    $mailbox_cache[$iAccount.'_'.$aMailbox['NAME']] = $aMailbox;
    sqsession_register($mailbox_cache,'mailbox_cache');

    if ($compose_new_win) {
        // write the session in order to make sure that the compose window has
        // access to the composemessages array which is stored in the session
        session_write_close();
        // restart the session. Do not use sqsession_is_active because the session_id
        // isn't empty after a session_write_close
        sqsession_start();

        if (!preg_match("/^[0-9]{3,4}$/", $compose_width)) {
            $compose_width = '640';
        }
        if (!preg_match("/^[0-9]{3,4}$/", $compose_height)) {
            $compose_height = '550';
        }
        // do not use &amp;, it will break the query string and $session will not be detected!!!
        $comp_uri = $base_uri . 'src/compose.php?mailbox='. urlencode($mailbox)
                  . '&session='.$aMailbox['FORWARD_SESSION']['SESSION_NUMBER']
                  . '&smaction=forward_as_attachment'
                  . '&fwduid=' . implode('_', $aMailbox['FORWARD_SESSION']['UIDS']);
        displayPageHeader($color, $mailbox, "comp_in_new('$comp_uri', $compose_width, $compose_height);", false);
    } else {
        // save mailboxstate
        sqsession_register($aMailbox,'aLastSelectedMailbox');
        session_write_close();
        // we have to redirect to the compose page
        $location = $base_uri . 'src/compose.php?mailbox='. urlencode($mailbox)
                  . '&session='.$aMailbox['FORWARD_SESSION']['SESSION_NUMBER']
                  . '&smaction=forward_as_attachment'
                  . '&fwduid=' . implode('_', $aMailbox['FORWARD_SESSION']['UIDS']);
        header("Location: $location");
        exit;
    }
} else {
    displayPageHeader($color, $mailbox);
//    $compose_uri = $base_uri.'src/compose.php?newmessage=1';
}

if (isset($note)) {
    $oTemplate->assign('note', $note);
    $oTemplate->display('note.tpl');
}

do_hook('search_before_form', $null);

if (!$search_silent) {
    asearch_print_saved($boxes);
    asearch_print_recent($boxes);
    if (empty($where_array)) {
        global $sent_folder;

        $mailbox_array[0] = $mailbox;
        $biop_array[0] = '';
        $unop_array[0] = '';
        if ($mailbox == $sent_folder) {
            $where_array[0] = 'TO';
        } else {
            $where_array[0] = 'FROM';
        }
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
    } else {
        asearch_print_form_basic($imapConnection, $boxes, $mailbox_array, $biop_array, $unop_array, $where_array, $what_array, $exclude_array, $sub_array);
    }
}

do_hook('search_after_form', $null);

if ($submit == $search_button_text) {
    $msgsfound = false;

    $err = asearch_check_query($where_array, $what_array, $exclude_array);

    $oTemplate->assign('query_has_error', $err!='');
    $oTemplate->assign('query_error', $err=='' ? NULL : $err);
    $oTemplate->assign('query', asearch_get_query_display($color, $mailbox_array, $biop_array, $unop_array, $where_array, $what_array, $exclude_array, $sub_array));
    
    $oTemplate->display('search_result_top.tpl');
    
    flush();
    $iMsgCnt = 0;
    if ($err == '') {
        $mboxes_array = sqimap_asearch_get_selectable_unformatted_mailboxes($boxes);
        /**
         * Retrieve the search queries
         */
        $mboxes_mailbox = sqimap_asearch($imapConnection, $mailbox_array, $biop_array, $unop_array, $where_array, $what_array, $exclude_array, $sub_array, $mboxes_array);
        foreach($mboxes_mailbox as $mbx => $search) {

            /**
            * until there is no per mailbox option screen to set prefs we override
            * the mailboxprefs by the default ones
            */

            $aMailboxPrefSer=getPref($data_dir, $username,'pref_'.$iAccount.'_'.$mbx);
            if ($aMailboxPrefSer) {
                $aMailboxPref = unserialize($aMailboxPrefSer);
                $aMailboxPref[MBX_PREF_COLUMNS] = $index_order;
            } else {
                setUserPref($username,'pref_'.$iAccount.'_'.$mbx,serialize($default_mailbox_pref));
                $aMailboxPref = $default_mailbox_pref;
            }
            if (isset($srt) && $targetmailbox == $mbx) {
                $aMailboxPref[MBX_PREF_SORT] = (int) $srt;
            }

            $trash_folder = (isset($trash_folder)) ? $trash_folder : false;
            $sent_folder = (isset($sent_folder)) ? $sent_folder : false;
            $draft_folder = (isset($draft_folder)) ? $draft_folder : false;


            /**
            * until there is no per mailbox option screen to set prefs we override
            * the mailboxprefs by the default ones
            */
            $aMailboxPref[MBX_PREF_LIMIT] = (int)  $show_num;
            $aMailboxPref[MBX_PREF_AUTO_EXPUNGE] = (bool) $auto_expunge;
            $aMailboxPref[MBX_PREF_INTERNALDATE] = (bool) getPref($data_dir, $username, 'internal_date_sort');
            $aMailboxPref[MBX_PREF_COLUMNS] = $index_order;

            /**
            * Replace From => To  in case it concerns a draft or sent folder
            */
            if (($mbx == $sent_folder || $mbx == $draft_folder) &&
                !in_array(SQM_COL_TO,$aMailboxPref[MBX_PREF_COLUMNS])) {
                $aNewOrder = array(); // nice var name ;)
                foreach($aMailboxPref[MBX_PREF_COLUMNS] as $iCol) {
                    if ($iCol == SQM_COL_FROM) {
                        $iCol = SQM_COL_TO;
                    }
                    $aNewOrder[] = $iCol;
                }
                $aMailboxPref[MBX_PREF_COLUMNS] = $aNewOrder;
                setUserPref($username,'pref_'.$iAccount.'_'.$mbx,serialize($aMailboxPref));
            }

            $aConfig['search'] = $search['search'];
            $aConfig['charset'] = $search['charset'];

            /**
             * Set the max cache size to the number of mailboxes to avoid cache cleanups
             * when searching all mailboxes
             */
            $aConfig['max_cache_size'] = count($mboxes_mailbox) +1;
            if (isset($startMessage) && $targetmailbox == $mbx) {
                $aConfig['offset'] = $startMessage;
            } else {
                $aConfig['offset'] = 0;
            }
            if (isset($showall) && $targetmailbox == $mbx) {
                $aConfig['showall'] = $showall;
            } else {
                if (isset($aConfig['showall'])) {
                    unset($aConfig['showall']);
                }
                $showall = false;
            }

            /**
            * Set the config options for the messages list
            */
            $aColumns = array();
            foreach ($aMailboxPref[MBX_PREF_COLUMNS] as $iCol) {
                $aColumns[$iCol] = array();
                switch ($iCol) {
                    case SQM_COL_SUBJ:
                        if ($truncate_subject) {
                            $aColumns[$iCol]['truncate'] = $truncate_subject;
                        }
                        break;
                    case SQM_COL_FROM:
                    case SQM_COL_TO:
                    case SQM_COL_CC:
                    case SQM_COL_BCC:
                        if ($truncate_sender) {
                            $aColumns[$iCol]['truncate'] = $truncate_sender;
                        }
                        break;
                }
            }


            $aProps = array(
                'columns' => $aColumns,
                'config'  => array('alt_index_colors'      => $alt_index_colors,
                                    'highlight_list'        => $message_highlight_list,
                                    'fancy_index_highlite'  => $fancy_index_highlite,
                                    'show_flag_buttons'     => (isset($show_flag_buttons)) ? $show_flag_buttons : true,
                                    'lastTargetMailbox'     => (isset($lastTargetMailbox)) ? $lastTargetMailbox : '',
                                    'trash_folder'          => $trash_folder,
                                    'sent_folder'           => $sent_folder,
                                    'draft_folder'          => $draft_folder,
                                    'enablesort'            => true,
                                    'color'                 => $color
                            ),
                'mailbox' => $mbx,
                'account' => (isset($iAccount)) ? $iAccount : 0,
                'module' => 'read_body',
                'email'  => false);


            $aMailbox = sqm_api_mailbox_select($imapConnection, $iAccount, $mbx,$aConfig,$aMailboxPref);

            $iError = 0;
            $aTemplate = showMessagesForMailbox($imapConnection, $aMailbox,$aProps, $iError);

            // in th future we can make use of multiple message sets, now set it to 1 for search.
            $iSetIndex = 1;
            if (isset($aMailbox['UIDSET'][$iSetIndex])) {
                $iMsgCnt += count($aMailbox['UIDSET'][$iSetIndex]);
            }
            if ($iError) {
                // error handling
            } else {
                /**
                * In the future, move this the the initialisation area
                */
                sqgetGlobalVar('align',$align,SQ_SESSION);

                /**
                 * TODO: Clean up handling of message list once the template is cleaned up.
                 */
                if ($aMailbox['EXISTS'] > 0) {
                    if ($iError) {
                       // TODO: Implement an error handler in the search page.
                       echo "ERROR occured, errorhandler will be implemented very soon";
                    } else {
                        foreach ($aTemplate as $k => $v) {
                            $oTemplate->assign($k, $v);
                        }

                        $mailbox_display = asearch_get_mailbox_display($aMailbox['NAME']);
                        if (strtoupper($mbx) == 'INBOX') {
                            $mailbox_display = _("INBOX");
                        } else {
                            $mailbox_display = imap_utf7_decode_local($mbx);
                        }

                        $oTemplate->assign('mailbox_name', sm_encode_html_special_chars($mailbox_display));
                        $oTemplate->display('search_result_mailbox.tpl');

                        $oTemplate->assign('page_selector',  $page_selector);
                        $oTemplate->assign('page_selector_max', $page_selector_max);
                        $oTemplate->assign('compact_paginator', $compact_paginator);
                        $oTemplate->assign('javascript_on', checkForJavascript());
                        $oTemplate->assign('base_uri', sqm_baseuri());
                        $oTemplate->assign('enablesort', (isset($aProps['config']['enablesort'])) ? $aProps['config']['enablesort'] : false);
                        $oTemplate->assign('icon_theme_path', $icon_theme_path);
                        $oTemplate->assign('use_icons', (isset($use_icons)) ? $use_icons : false);
                        $oTemplate->assign('aOrder', array_keys($aColumns));
                        $oTemplate->assign('alt_index_colors', isset($alt_index_colors) ? $alt_index_colors: false);
                        $oTemplate->assign('color', $color);
                        $oTemplate->assign('align', $align);
                        $oTemplate->assign('checkall', $checkall);
                        $oTemplate->assign('preselected', $preselected);

                        global $show_personal_names;
                        $oTemplate->assign('show_personal_names', $show_personal_names);

                        global $accesskey_mailbox_toggle_selected, $accesskey_mailbox_thread;
                        $oTemplate->assign('accesskey_mailbox_toggle_selected', $accesskey_mailbox_toggle_selected);
                        $oTemplate->assign('accesskey_mailbox_thread', $accesskey_mailbox_thread);

                        $oTemplate->display('message_list.tpl');
                    }
                }
            }

            /* add the mailbox to the cache */
            $mailbox_cache[$iAccount.'_'.$aMailbox['NAME']] = $aMailbox;

        }
    }
    if(!$iMsgCnt) {
        $oTemplate->display('search_result_empty.tpl');
    }
}

do_hook('search_bottom', $null);
sqimap_logout($imapConnection);

$oTemplate->display('footer.tpl');
sqsession_register($mailbox_cache,'mailbox_cache');
