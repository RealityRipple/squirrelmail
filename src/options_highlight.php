<?php

/**
 * options_highlight.php
 *
 * Displays message highlighting options
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage prefs
 */

/** This is the options_highlight page */
define('PAGE_NAME', 'options_highlight');

/**
 * Include the SquirrelMail initialization file.
 */
require('../include/init.php');

// include_once(SM_PATH . 'functions/imap.php');
require_once(SM_PATH . 'functions/forms.php');

/* get globals */
sqGetGlobalVar('action', $action);
sqGetGlobalVar('theid', $theid);
sqGetGlobalVar('identname', $identname);
sqGetGlobalVar('newcolor_choose', $newcolor_choose);
sqGetGlobalVar('newcolor_input', $newcolor_input);
sqGetGlobalVar('color_type', $color_type);
sqGetGlobalVar('match_type', $match_type);
sqGetGlobalVar('value', $value);
sqgetGlobalVar('smtoken', $submitted_token, SQ_FORM, '');

/* end of get globals */

function oh_opt( $val, $sel, $tit ) {
    echo "<option value=\"$val\"";
    if ( $sel )
        echo ' selected="selected"';
    echo  ">$tit</option>\n";
}

if (! isset($action)) {
    $action = '';
}
if (! isset($message_highlight_list)) {
    $message_highlight_list = array();
}

if (isset($theid) && ($action == 'delete') ||
                     ($action == 'up')     ||
                     ($action == 'down')) {

    // security check
    sm_validate_security_token($submitted_token, -1, TRUE);

    $new_rules = array();
    switch($action) {
        case('delete'):
            foreach($message_highlight_list as $rid => $rule) {
                 if($rid != $theid) {
                     $new_rules[] = $rule;
                 }
            }
            break;
        case('down'):
            $theid++;
        case('up'):
            foreach($message_highlight_list as $rid => $rule) {
                if($rid == $theid) {
                    $temp_rule         = $new_rules[$rid-1];
                    $new_rules[$rid-1] = $rule;
                    $new_rules[$rid]   = $temp_rule;
                } else {
                    $new_rules[$rid]   = $rule;
                }
            }
            break;
        default:
            $new_rules = $message_highlight_list;
            break;
    }
    $message_highlight_list = $new_rules;

    setPref($data_dir, $username, 'hililist', serialize($message_highlight_list));

    header( 'Location: ' .get_location(). '/options_highlight.php' );
    exit;
} else if ($action == 'save') {

    // security check
    sm_validate_security_token($submitted_token, -1, TRUE);

    if ($color_type == 1) $newcolor = $newcolor_choose;
    elseif ($color_type == 2) $newcolor = $newcolor_input;
    else $newcolor = $color_type;

    $newcolor = str_replace('#', '', $newcolor);
    $newcolor = str_replace('"', '', $newcolor);
    $newcolor = str_replace('\'', '', $newcolor);
    $value = str_replace(',', ' ', $value);

    if(isset($theid)) {
        $message_highlight_list[$theid] =
            array( 'name' => $identname, 'color' => $newcolor,
                   'value' => $value, 'match_type' => $match_type );
    } else {
        $message_highlight_list[] =
            array( 'name' => $identname, 'color' => $newcolor,
                   'value' => $value, 'match_type' => $match_type );
    }

    setPref($data_dir, $username, 'hililist', serialize($message_highlight_list));
}
displayPageHeader($color);

/**
 * Display the current rule list
 */
$rules = array();
foreach($message_highlight_list as $index=>$rule) {
    $a = array();
    
    $a['Name'] = sm_encode_html_special_chars($rule['name']);
    $a['Color'] = $rule['color'];
    $a['MatchField'] = '';
    $a['MatchValue'] = sm_encode_html_special_chars($rule['value']);
    switch ($rule['match_type']) {
            case 'from' :
                $a['MatchField'] = _("From");
                break;
            case 'to' :
                $a['MatchField'] = _("To");
                break;
            case 'cc' :
                $a['MatchField'] = _("Cc");
                break;
            case 'to_cc' :
                $a['MatchField'] = _("To or Cc");
                break;
            case 'subject' :
                $a['MatchField'] = _("subject");
                break;
    }
    
    $rules[$index] = $a;
}

$oTemplate->assign('current_rules', $rules);

$token = sm_generate_security_token();

$oTemplate->assign('add_rule', 'options_highlight.php?action=add');
$oTemplate->assign('edit_rule', 'options_highlight.php?action=edit&amp;theid=');
$oTemplate->assign('delete_rule', 'options_highlight.php?action=delete&amp;smtoken=' . $token . '&amp;theid=');
$oTemplate->assign('move_up', 'options_highlight.php?action=up&amp;smtoken=' . $token . '&amp;theid=');
$oTemplate->assign('move_down', 'options_highlight.php?action=down&amp;smtoken=' . $token . '&amp;theid=');

$oTemplate->display('options_highlight_list.tpl');

/**
 * Optionally, display the add/edit dialog
 */
if ($action == 'edit' || $action == 'add') {

    $color_list[0] = '4444aa';
    $color_list[1] = '44aa44';
    $color_list[2] = 'aaaa44';
    $color_list[3] = '44aaaa';
    $color_list[4] = 'aa44aa';
    $color_list[5] = 'aaaaff';
    $color_list[6] = 'aaffaa';
    $color_list[7] = 'ffffaa';
    $color_list[8] = 'aaffff';
    $color_list[9] = 'ffaaff';
    $color_list[10] = 'aaaaaa';
    $color_list[11] = 'bfbfbf';
    $color_list[12] = 'dfdfdf';
    $color_list[13] = 'ffffff';

    # helpful color chart from http://www.visibone.com/colorlab/big.html
    $new_color_list["0,0"] = 'cccccc';
    $new_color_list["0,1"] = '999999';
    $new_color_list["0,2"] = '666666';
    $new_color_list["0,3"] = '333333';
    $new_color_list["0,4"] = '000000';

    # red
    $new_color_list["1,0"] = 'ff0000';
    $new_color_list["1,1"] = 'cc0000';
    $new_color_list["1,2"] = '990000';
    $new_color_list["1,3"] = '660000';
    $new_color_list["1,4"] = '330000';

    $new_color_list["2,0"] = 'ffcccc';
    $new_color_list["2,1"] = 'cc9999';
    $new_color_list["2,2"] = '996666';
    $new_color_list["2,3"] = '663333';
    $new_color_list["2,4"] = '330000';

    $new_color_list["3,0"] = 'ffcccc';
    $new_color_list["3,1"] = 'ff9999';
    $new_color_list["3,2"] = 'ff6666';
    $new_color_list["3,3"] = 'ff3333';
    $new_color_list["3,4"] = 'ff0000';

    # green
    $new_color_list["4,0"] = '00ff00';
    $new_color_list["4,1"] = '00cc00';
    $new_color_list["4,2"] = '009900';
    $new_color_list["4,3"] = '006600';
    $new_color_list["4,4"] = '003300';

    $new_color_list["5,0"] = 'ccffcc';
    $new_color_list["5,1"] = '99cc99';
    $new_color_list["5,2"] = '669966';
    $new_color_list["5,3"] = '336633';
    $new_color_list["5,4"] = '003300';

    $new_color_list["6,0"] = 'ccffcc';
    $new_color_list["6,1"] = '99ff99';
    $new_color_list["6,2"] = '66ff66';
    $new_color_list["6,3"] = '33ff33';
    $new_color_list["6,4"] = '00ff00';

    # blue
    $new_color_list["7,0"] = '0000ff';
    $new_color_list["7,1"] = '0000cc';
    $new_color_list["7,2"] = '000099';
    $new_color_list["7,3"] = '000066';
    $new_color_list["7,4"] = '000033';

    $new_color_list["8,0"] = 'ccccff';
    $new_color_list["8,1"] = '9999cc';
    $new_color_list["8,2"] = '666699';
    $new_color_list["8,3"] = '333366';
    $new_color_list["8,4"] = '000033';

    $new_color_list["9,0"] = 'ccccff';
    $new_color_list["9,1"] = '9999ff';
    $new_color_list["9,2"] = '6666ff';
    $new_color_list["9,3"] = '3333ff';
    $new_color_list["9,4"] = '0000ff';

    # yellow
    $new_color_list["10,0"] = 'ffff00';
    $new_color_list["10,1"] = 'cccc00';
    $new_color_list["10,2"] = '999900';
    $new_color_list["10,3"] = '666600';
    $new_color_list["10,4"] = '333300';

    $new_color_list["11,0"] = 'ffffcc';
    $new_color_list["11,1"] = 'cccc99';
    $new_color_list["11,2"] = '999966';
    $new_color_list["11,3"] = '666633';
    $new_color_list["11,4"] = '333300';

    $new_color_list["12,0"] = 'ffffcc';
    $new_color_list["12,1"] = 'ffff99';
    $new_color_list["12,2"] = 'ffff66';
    $new_color_list["12,3"] = 'ffff33';
    $new_color_list["12,4"] = 'ffff00';

    # cyan
    $new_color_list["13,0"] = '00ffff';
    $new_color_list["13,1"] = '00cccc';
    $new_color_list["13,2"] = '009999';
    $new_color_list["13,3"] = '006666';
    $new_color_list["13,4"] = '003333';

    $new_color_list["14,0"] = 'ccffff';
    $new_color_list["14,1"] = '99cccc';
    $new_color_list["14,2"] = '669999';
    $new_color_list["14,3"] = '336666';
    $new_color_list["14,4"] = '003333';

    $new_color_list["15,0"] = 'ccffff';
    $new_color_list["15,1"] = '99ffff';
    $new_color_list["15,2"] = '66ffff';
    $new_color_list["15,3"] = '33ffff';
    $new_color_list["15,4"] = '00ffff';

    # magenta
    $new_color_list["16,0"] = 'ff00ff';
    $new_color_list["16,1"] = 'cc00cc';
    $new_color_list["16,2"] = '990099';
    $new_color_list["16,3"] = '660066';
    $new_color_list["16,4"] = '330033';

    $new_color_list["17,0"] = 'ffccff';
    $new_color_list["17,1"] = 'cc99cc';
    $new_color_list["17,2"] = '996699';
    $new_color_list["17,3"] = '663366';
    $new_color_list["17,4"] = '330033';

    $new_color_list["18,0"] = 'ffccff';
    $new_color_list["18,1"] = 'ff99ff';
    $new_color_list["18,2"] = 'ff66ff';
    $new_color_list["18,3"] = 'ff33ff';
    $new_color_list["18,4"] = 'ff00ff';

    $selected_input = FALSE;
    $selected_choose = FALSE;
    $selected_predefined = FALSE;

    $name = $action=='edit' && isset($theid) && isset($message_highlight_list[$theid]['name']) ? $message_highlight_list[$theid]['name'] : '';
    $field = $action=='edit' && isset($theid) && isset($message_highlight_list[$theid]['match_type']) ? $message_highlight_list[$theid]['match_type'] : '';
    $value = $action=='edit' && isset($theid) && isset($message_highlight_list[$theid]['value']) ? $message_highlight_list[$theid]['value'] : '';
    $color = $action=='edit' && isset($theid) && isset($message_highlight_list[$theid]['color']) ? $message_highlight_list[$theid]['color'] : '';
    
    if ($action == 'edit' && isset($theid) && isset($message_highlight_list[$theid]['color'])) {
        for ($i=0; $i < 14; $i++) {
            if ($color_list[$i] == $message_highlight_list[$theid]['color']) {
                $selected_choose = TRUE;
                continue;
            }
        }
    }

    $pre_defined_color = 0;
    for($x = 0; $x < 5; $x++) {
        for($y = 0; $y < 19; $y++) {
            $gridindex = "$y,$x";
            $gridcolor = $new_color_list[$gridindex];
            if ($gridcolor == $color) {
                $pre_defined_color = 1;
                break;
            }
        }
    }

    if (isset($theid) && !isset($message_highlight_list[$theid]['color']))
        $selected_choose = TRUE;
    else if ($pre_defined_color)
        $selected_predefined = TRUE;
    else if ($selected_choose == '')
        $selected_input = TRUE;
        
    $oTemplate->assign('rule_name', $name);
    $oTemplate->assign('rule_value', $value);
    $oTemplate->assign('rule_field', $field);
    $oTemplate->assign('rule_color', $color);
    $oTemplate->assign('color_radio', ($selected_choose ? 1 : ($selected_input ? 2 : 0)));
    $oTemplate->assign('color_input', ($selected_input ? $color : ''));
    
    echo addForm('options_highlight.php', 'post', 'f', '', '', array(), TRUE).
         addHidden('action', 'save');
    if($action == 'edit') {
        echo addHidden('theid', (isset($theid)?$theid:''));
    }
    
    $oTemplate->display('options_highlight_addedit.tpl');
    
    echo "</form>\n";
}
do_hook('options_highlight_bottom', $null);

$oTemplate->display('footer.tpl');
