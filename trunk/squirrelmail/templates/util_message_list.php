<?php

/**
 * Template logic
 *
 * The following functions are utility functions for this template. Do not
 * echo output in those functions.
 *
 * @copyright &copy; 2005-2006 The SquirrelMail Project Team
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
        SQM_COL_DATE => 10,
        SQM_COL_SUBJ  => 100,
        SQM_COL_FLAGS => 2,
        SQM_COL_SIZE  => 5,
        SQM_COL_PRIO => 1,
        SQM_COL_ATTACHMENT => 1,
        SQM_COL_INT_DATE => 10,
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
 * Function to retrieve the correct flag icon belonging to the set of
 * provided flags
 *
 * @param array $aFlags associative array with seen,deleted,anwered and flag keys.
 * @param string $sImageLocation directory location of flagicons
 * @return string $sFlags string with the correct img html tag
 * @author Marc Groot Koerkamp
 */
function getFlagIcon($aFlags, $sImageLocation) {
    $sFlags = '';

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
     */

    /**
     * Use static vars to avoid initialisation of the array on each displayed row
     */
    static $aFlagImages, $aFlagValues;
    if (!isset($aFlagImages)) {
        $aFlagImages = array(
                            array('msg_new.png','('._("New").')'),
                            array('msg_read.png','('._("Read").')'),
                            array('msg_new_deleted.png','('._("Deleted").')'),
                            array('msg_read_deleted.png','('._("Deleted").')'),
                            array('msg_new_reply.png','('._("Answered").')'),
                            array('msg_read_reply.png','('._("Answered").')'),
                            array('msg_read_deleted_reply.png','('._("Answered").')'),
                            array('flagged.png', '('._("Flagged").')'),
                            array('flagged.png', '('._("Flagged").')'),
                            array('flagged.png', '('._("Flagged").')'),
                            array('flagged.png', '('._("Flagged").')'),
                            array('flagged.png', '('._("Flagged").')'),
                            array('flagged.png', '('._("Flagged").')'),
                            array('flagged.png', '('._("Flagged").')'),
                            array('flagged.png', '('._("Flagged").')'),
                            array('flagged.png', '('._("Flagged").')')
                            ); // as you see the list is not completed yet.
        $aFlagValues = array('seen'     => 1,
                             'deleted'  => 2,
                             'answered' => 4,
                             'flagged'  => 8,
                             'draft'    => 16);
    }

    /**
     * The flags entry contain all items displayed in the flag column.
     */
    $iFlagIndx = 0;
    foreach ($aFlags as $flag => $flagvalue) {
        /* FIX ME, we should use separate templates for icons */
         switch ($flag) {
            case 'deleted':
            case 'answered':
            case 'seen':
            case 'flagged': if ($flagvalue) $iFlagIndx+=$aFlagValues[$flag]; break;
            default: break;
        }
    }
    if (isset($aFlagImages[$iFlagIndx])) {
        $aFlagEntry = $aFlagImages[$iFlagIndx];
    } else {
        $aFlagEntry = end($aFlagImages);
    }

    $sFlags = '<img src="' . $sImageLocation . $aFlagEntry[0].'"'.
              ' border="0" alt="'.$aFlagEntry[1].'" title="'. $aFlagEntry[1] .'" height="12" width="18" />' ;
    if (!$sFlags) { $sFlags = '&nbsp;'; }
    return $sFlags;
}

/**
 * Function to retrieve the correct flag text belonging to the set of
 * provided flags
 *
 * @param array $aFlags associative array with seen,deleted,anwered and flag keys.
 * @return string $sFlags string with the correct flag text
 * @author Marc Groot Koerkamp
 */
function getFlagText($aFlags) {
    $sFlags = '';

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
     */
    /**
     * Use static vars to avoid initialisation of the array on each displayed row
     */
    static $aFlagText, $aFlagValues;
    if (!isset($aFlagText)) {
        $aFlagText = array(
                            array('&nbsp;', '('._("New").')'),
                            array('&nbsp;', '('._("Read").')'),
                            array(_("D")  , '('._("Deleted").')'),
                            array(_("D")  , '('._("Deleted").')'),
                            array(_("A")  , '('._("Answered").')'),
                            array(_("A")  , '('._("Answered").')'),
                            array(_("D")  , '('._("Answered").')'),
                            array(_("F")  , '('._("Flagged").')'),
                            array(_("F")  , '('._("Flagged").')'),
                            array(_("F")  , '('._("Flagged").')'),
                            array(_("F")  , '('._("Flagged").')'),
                            array(_("F")  , '('._("Flagged").')'),
                            array(_("F")  , '('._("Flagged").')'),
                            array(_("F")  , '('._("Flagged").')'),
                            array(_("F")  , '('._("Flagged").')'),
                            array(_("F")  , '('._("Flagged").')')
                            ); // as you see the list is not completed yet.
        $aFlagValues = array('seen'     => 1,
                             'deleted'  => 2,
                             'answered' => 4,
                             'flagged'  => 8,
                             'draft'    => 16);
    }

    /**
     * The flags entry contain all items displayed in the flag column.
     */
    $iFlagIndx = 0;
    foreach ($aFlags as $flag => $flagvalue) {
        /* FIX ME, we should use separate templates for icons */
        switch ($flag) {
            case 'deleted':
            case 'answered':
            case 'seen':
            case 'flagged': if ($flagvalue) $iFlagIndx+=$aFlagValues[$flag]; break;
            default: break;
        }
    }
    if (isset($aFlagText[$iFlagIndx])) {
        $sFlags = $aFlagText[$iFlagIndx][0];
    } else {
        $aLast = end($aFlagText);
        $sFlags = $aLast[0];
    }
    if (!$sFlags) { $sFlags = '&nbsp;'; }
    return $sFlags;
}
?>