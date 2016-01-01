<?php

/**
 * message_list_util.php
 *
 * Helper functions for message list templates.
 *
 * The following functions are utility functions for templates. Do not
 * echo output in these functions.
 *
 * @copyright 2005-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 */


/**
 * @param array $aOrder
 * @return array
 */
function calcMessageListColumnWidth($aOrder) {
    /**
     * Width of the displayed columns
     */
    $aWidthTpl = array(
        SQM_COL_CHECK => 1,
        SQM_COL_FROM =>  25,
        SQM_COL_DATE => 15,
        SQM_COL_SUBJ  => 100,
        SQM_COL_FLAGS => 2,
        SQM_COL_SIZE  => 5,
        SQM_COL_PRIO => 1,
        SQM_COL_ATTACHMENT => 1,
        SQM_COL_INT_DATE => 15,
        SQM_COL_TO => 25,
        SQM_COL_CC => 25,
        SQM_COL_BCC => 25
    );

    /**
     * Calculate the width of the subject column based on the
     * widths of the other columns
     */
    if (isset($aOrder[SQM_COL_SUBJ])) {
        foreach($aOrder as $iCol) {
            if ($iCol != SQM_COL_SUBJ) {
                $aWidthTpl[SQM_COL_SUBJ] -= $aWidthTpl[$iCol];
            }
        }
    }
    $aWidth = array();
    foreach($aOrder as $iCol) {
        $aWidth[$iCol] = $aWidthTpl[$iCol];
    }

    $iCheckTotalWidth = $iTotalWidth = 0;
    foreach($aOrder as $iCol) { $iTotalWidth += $aWidth[$iCol];}

    $iTotalWidth = ($iTotalWidth) ? $iTotalWidth : 100; // divide by zero check. shouldn't be needed

    // correct the width to 100%
    foreach($aOrder as $iCol) {
        $aWidth[$iCol] = round( (100 / $iTotalWidth) * $aWidth[$iCol] , 0);
        $iCheckTotalWidth += $aWidth[$iCol];
    }
    if ($iCheckTotalWidth > 100) { // correction needed
       $iCol = array_search(max($aWidth),$aWidth);
       $aWidth[$iCol] -= $iCheckTotalWidth-100;
    }
    return $aWidth;
}


/**
 * Function to retrieve correct icon based on provided message flags.  This is 
 * a merge/replacement for getFlagIcon() and getFlagText() functions.
 * 
 * @param array $aFlags associative array with seen,deleted,anwered and flag keys.
 * @param string $icon_theme_path path to user's currently selected icon theme.
 * @return string $icon full HTML img tag or text icon, depending on of user prefs
 * @author Steve Brown
 */
function getFlagIcon ($aFlags, $icon_theme_path) {
    /**
     * 0  = unseen
     * 1  = seen
     * 2  = deleted
     * 3  = deleted seen
     * 4  = answered
     * 5  = answered seen
     * 6  = answered deleted
     * 7  = answered deleted seen
     * 8  = flagged
     * 9  = flagged seen
     * 10 = flagged deleted
     * 11 = flagged deleted seen
     * 12 = flagged answered
     * 13 = flagged aswered seen
     * 14 = flagged answered deleted
     * 15 = flagged anserwed deleted seen
     * ...
     * 32 = forwarded
     * 33 = forwarded seen
     * 34 = forwarded deleted
     * 35 = forwarded deleted seen
     * ...
     * 41 = flagged forwarded seen
     * 42 = flagged forwarded deleted
     * 43 = flagged forwarded deleted seen
     */

    /**
     * Use static vars to avoid initialisation of the array on each displayed row
     */
    global $nbsp;
    static $flag_icons, $flag_values;
    if (!isset($flag_icons)) {
        // This is by no means complete...
        $flag_icons = array (  
        //      Image icon name                  Text Icon   Alt/Title Text
        //      ---------------                  ---------   --------------
        array ('msg_new.png',                    $nbsp,      '('._("New").')') ,
        array ('msg_read.png',                   $nbsp,      '('._("Read").')'),
        // i18n: "D" is short for "Deleted". Make sure that two icon strings aren't translated to the same character (only in 1.5).
        array ('msg_new_deleted.png',            _("D"),     '('._("Deleted").')'),
        array ('msg_read_deleted.png',           _("D"),     '('._("Deleted").')'),
        // i18n: "A" is short for "Answered". Make sure that two icon strings aren't translated to the same character (only in 1.5).
        array ('msg_new_reply.png',              _("A"),     '('._("Answered").')'),
        array ('msg_read_reply.png',             _("A"),     '('._("Answered").')'),
        array ('msg_new_deleted_reply.png',      _("D"),     '('._("Answered").')'),
        array ('msg_read_deleted_reply.png',     _("D"),     '('._("Answered").')'),
        // i18n: "F" is short for "Flagged". Make sure that two icon strings aren't translated to the same character (only in 1.5).
        array ('flagged.png',                    _("F"),     '('._("Flagged").')'),
        array ('flagged.png',                    _("F"),     '('._("Flagged").')'),
        array ('flagged.png',                    _("F"),     '('._("Flagged").')'),
        array ('flagged.png',                    _("F"),     '('._("Flagged").')'),
        array ('flagged.png',                    _("F"),     '('._("Flagged").')'),
        array ('flagged.png',                    _("F"),     '('._("Flagged").')'),
        array ('flagged.png',                    _("F"),     '('._("Flagged").')'),
        array ('flagged.png',                    _("F"),     '('._("Flagged").')'),
        FALSE,
        FALSE,
        FALSE,
        FALSE,
        FALSE,
        FALSE,
        FALSE,
        FALSE,
        FALSE,
        FALSE,
        FALSE,
        FALSE,
        FALSE,
        FALSE,
        FALSE,
        FALSE,
        // i18n: "O" is short for "Forwarded". Make sure that two icon strings aren't translated to the same character (only in 1.5).
        array ('msg_new_forwarded.png',          _("O"),     '('._("Forwarded").')'),
        array ('msg_read_forwarded.png',         _("O"),     '('._("Forwarded").')'),
        array ('msg_new_deleted_forwarded.png',  _("D"),     '('._("Forwarded").')'),
        array ('msg_read_deleted_forwarded.png', _("D"),     '('._("Forwarded").')'),
        FALSE,
        FALSE,
        FALSE,
        FALSE,
        FALSE,
        array ('flagged.png',                    _("F"),     '('._("Flagged").')'),
        array ('flagged.png',                    _("F"),     '('._("Flagged").')'),
        array ('flagged.png',                    _("F"),     '('._("Flagged").')'),
        );
        
        $flag_values = array('seen'      => 1,
                             'deleted'   => 2,
                             'answered'  => 4,
                             'flagged'   => 8,
                             'draft'     => 16,
                             'forwarded' => 32);
    }

    /**
     * The flags entry contain all items displayed in the flag column.
     */
    $icon = '';

    $index = 0;
    foreach ($aFlags as $flag => $flagvalue) {
         switch ($flag) {
            case 'deleted':
            case 'answered':
            case 'forwarded':
            case 'seen':
            case 'flagged': if ($flagvalue) $index += $flag_values[$flag]; break;
            default: break;
        }
    }
    
    if (!empty($flag_icons[$index])) {
        $data = $flag_icons[$index];
    } else {
//FIXME: previously this default was set to the last value of the $flag_icons array (when it was index 15 - flagged anserwed deleted seen) but I don't understand why... am changing it to flagged (index 15 just shows (only) the flag icon anyway)
        $data = $flag_icons[8]; // default to just flagged
    }

    $icon = getIcon($icon_theme_path, $data[0], $data[1], $data[2]);
    return $icon;
}


/**
 * Function to retrieve correct priority icon based on user prefs
 * 
 * @param integer $priority priority value of message
 * @param string $icon_theme_path path to user's currently selected icon theme.
 * @return string $icon full HTML img tag or text icon, depending on of user prefs
 * @author Steve Brown
 */
function getPriorityIcon ($priority, $icon_theme_path) {
    $icon = '';

    switch ($priority) {
        case 1:
        case 2:
            $icon = getIcon($icon_theme_path, 'prio_high.png', create_span('!', 'high_priority'), _("High priority"));
            break;
        case 5:
            $icon = getIcon($icon_theme_path, 'prio_low.png', create_span('&#8595;', 'low_priority'), _("Low priority"));
            break;
        default:
            $icon = getIcon($icon_theme_path, 'transparent.png', '', _("Normal priority"), 5);
            break;
    }

    return $icon;
}


/**
 * Function to retrieve correct attchment icon based on user prefs
 * 
 * @param boolean $attach TRUE if the message has an attachment
 * @param string $icon_theme_path path to user's currently selected icon theme.
 * @return string $icon full HTML img tag or text icon, depending on of user prefs
 * @author Steve Brown
 */
function getAttachmentIcon ($attach, $icon_theme_path) {
    $icon = '';
    
    $icon_file = $attach ? 'attach.png' : 'transparent.png';
    $alt_text = $attach ? _("Attachment") : _("No attachment");
    $text = $attach ? '+' : '';
    $icon = getIcon($icon_theme_path, $icon_file, $text, $alt_text);

    return $icon;
}
