<?php

/**
 * mailbox_display.php
 *
 * Copyright (c) 1999-2004 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This contains functions that display mailbox information, such as the
 * table row that has sender, date, subject, etc...
 *
 * @version $Id$
 * @package squirrelmail
 */

/** The standard includes.. */
require_once(SM_PATH . 'functions/strings.php');
require_once(SM_PATH . 'functions/html.php');
require_once(SM_PATH . 'class/html.class.php');
require_once(SM_PATH . 'functions/imap_mailbox.php');
require_once(SM_PATH . 'functions/imap_messages.php');
require_once(SM_PATH . 'functions/imap_asearch.php');
require_once(SM_PATH . 'functions/mime.php');
require_once(SM_PATH . 'functions/forms.php');

/**
 * default value for page_selector_max
 */
define('PG_SEL_MAX', 10);

/**
 * The number of pages to cache msg headers
 */
define('SQM_MAX_PAGES_IN_CACHE',5);

/**
 * Sort constants used for sorting of messages
 */
define('SQSORT_NONE',0);
define('SQSORT_DATE_ASC',1);
define('SQSORT_DATE_DEC',2);
define('SQSORT_FROM_ASC',3);
define('SQSORT_FROM_DEC',4);
define('SQSORT_SUBJ_ASC',5);
define('SQSORT_SUBJ_DEC',6);
define('SQSORT_SIZE_ASC',7);
define('SQSORT_SIZE_DEC',8);
define('SQSORT_TO_ASC',9);
define('SQSORT_TO_DEC',10);
define('SQSORT_CC_ASC',11);
define('SQSORT_CC_DEC',12);
define('SQSORT_INT_DATE_ASC',13);
define('SQSORT_INT_DATE_DEC',14);

define('SQSORT_THREAD',32);


define('MBX_PREF_SORT',0);
define('MBX_PREF_LIMIT',1);
define('MBX_PREF_AUTO_EXPUNGE',2);
define('MBX_PREF_INTERNALDATE',3);
define('SQM_MAX_MBX_IN_CACHE',3);
// define('MBX_PREF_FUTURE',unique integer key);

/**
 * @param mixed $start UNDOCUMENTED
 */
function elapsed($start) {

    $end = microtime();
    list($start2, $start1) = explode(" ", $start);
    list($end2, $end1) = explode(" ", $end);
    $diff1 = $end1 - $start1;
    $diff2 = $end2 - $start2;
    if( $diff2 < 0 ){
        $diff1 -= 1;
        $diff2 += 1.0;
    }
    return $diff2 + $diff1;
}

/**
 * Displays message header row in messages list
 *
 * @param  array $aMsg contains all message related parameters
 * @return void
 */

function printMessageInfo($aMsg) {
    // FIX ME, remove these globals as well by adding an array as argument for the user settings
    // specificly meant for header display
    global $checkall,
        $color,
        $default_use_priority,
        $message_highlight_list,
        $index_order,
        $truncate_sender,           /* number of characters for From/To field (<= 0 for unchanged) */
        $email_address,
        $show_recipient_instead,    /* show recipient name instead of default identity */
        $use_icons,                 /* indicates to use icons or text markers */
        $icon_theme;                /* icons theming */

    $color_string = $color[4];

    // initialisation:
    $mailbox     = $aMsg['MAILBOX'];
    $msg         = $aMsg['HEADER'];
    $t           =  $aMsg['INDX'];
    $start_msg   = $aMsg['PAGEOFFSET'];
    $last        = $aMsg['LAST'];
    if (isset($aMsg['SEARCH']) && count($aMsg['SEARCH']) >1 ) {
        $where   = $aMsg['SEARCH'][0];
        $what    = $aMsg['SEARCH'][1];
    } else {
        $where = false;
        $what = false;
    }
    $iIndent  = $aMsg['INDENT'];

    $sSubject = (isset($msg['SUBJECT']) && $msg['SUBJECT'] != '') ? $msg['SUBJECT'] : _("(no subject)");
    $sFrom    = (isset($msg['FROM'])) ? $msg['FROM'] : _("Unknown sender");
    $sTo      = (isset($msg['TO'])) ? $msg['TO'] : _("Unknown recipient");
    $sCc      = (isset($msg['CC'])) ? $msg['CC'] : '';
    $aFlags   = (isset($msg['FLAGS'])) ? $msg['FLAGS'] : array();
    $iPrio    = (isset($msg['PRIORITY'])) ? $msg['PRIORITY'] : 3;
    $iSize    = (isset($msg['SIZE'])) ? $msg['SIZE'] : 0;
    $sType0   = (isset($msg['TYPE0'])) ? $msg['TYPE0'] : 'text';
    $sType1   = (isset($msg['TYPE1'])) ? $msg['TYPE1'] : 'plain';
    if (isset($msg['INTERNALDATE'])) {
       $sDate = getDateString(getTimeStamp(explode(' ',$msg['INTERNALDATE'])));
    } else {
       $sDate = (isset($msg['DATE'])) ? getDateString(getTimeStamp(explode(' ',$msg['DATE']))) : '';
    }
    $iId      = (isset($msg['UID'])) ? $msg['UID'] : false;

    if (!$iId) {
        return;
    }

    if ($GLOBALS['alt_index_colors']) {
        if (!($t % 2)) {
            if (!isset($color[12])) {
                $color[12] = '#EAEAEA';
            }
            $color_string = $color[12];
        }
    }

    $urlMailbox = urlencode($mailbox);

    // FIXME, foldertype should be set in right_main.php
    // in other words, handle as sent is obsoleted from now.
    // We replace that by providing an array to aMailbox with the to shown headers
    // that way we are free to show the user different layouts for different folders
    $bSentFolder = handleAsSent($mailbox);
    if ((!$bSentFolder) && ($show_recipient_instead)) {
        // If the From address is the same as $email_address, then handle as Sent
        $from_array = parseAddress($sFrom, 1);
        if (!isset($email_address)) {
            global $datadir, $username;
            $email_address = getPref($datadir, $username, 'email_address');
        }
        $bHandleAsSent = ((isset($from_array[0][0])) && ($from_array[0][0] == $email_address));
    } else {
        $bHandleAsSent = $bSentFolder;
    }
    // If this is a Sent message, display To address instead of From
    if ($bHandleAsSent) {
        $sFrom = $sTo;
    }
    // Passing 1 below results in only 1 address being parsed, thus defeating the following code
    $sFrom = parseAddress($sFrom/*,1*/);

    /*
        * This is done in case you're looking into Sent folders,
        * because you can have multiple receivers.
        */
    $senderNames = $sFrom;
    $senderName  = '';
    $senderAddress = '';
    if (sizeof($senderNames)){
        foreach ($senderNames as $senderNames_part) {
            if ($senderName != '') {
                $senderName .= ', ';
                $senderAddress .= ', ';
            }
            $sender_address_part = htmlspecialchars($senderNames_part[0]);
            $sender_name_part = str_replace('&nbsp;',' ', decodeHeader($senderNames_part[1]));
            if ($sender_name_part) {
                $senderName .= $sender_name_part;
                $senderAddress .= $sender_name_part . ' <' . $sender_address_part . '>';
            } else {
                $senderName .= $sender_address_part;
                $senderAddress .= $sender_address_part;
            }
        }
    }
    // If Sent, prefix with To: but only if not Sent folder
    if ($bHandleAsSent ^ $bSentFolder) {
        $senderName = _("To") . ': ' . $senderName;
        $senderAddress = _("To") . ': ' . $senderAddress;
    }

    // this is a column property which can apply to multiple columns. Do not use vars for one column
    // only. instead we should use something like this:
    // 1ed column $aMailbox['columns']['SUBJECT'] value: aray with properties ...
    // 2ed column $aMailbox['columns']['FROM'] value: aray with properties ...
    //            NB in case of the sentfolder this could be the TO field
    // properties array example:
    //      'truncate' => length (0 is no truncate)
    //      'prefix    => if (x in b then do that )
    if ($truncate_sender > 0) {
        $senderName = truncateWithEntities($senderName, $truncate_sender);
    }

    $flag = $flag_end = $bold = $bold_end = $fontstr = $fontstr_end = $italic = $italic_end = '';
    $bold = '<b>';
    $bold_end = '</b>';

    foreach ($aFlags as $sFlag => $value) {
        switch ($sFlag) {
          case '\\flagged':
              if ($value) {
                  $flag = "<font color=\"$color[2]\">";
                  $flag_end = '</font>';
              }
              break;
          case '\\seen':
              if ($value) {
                  $bold = '';
                  $bold_end = '';
              }
              break;
          case '\\deleted':
              if ($value) {
                  $fontstr = "<font color=\"$color[9]\">";
                  $fontstr_end = '</font>';
              }
              break;
        }
    }
    if ($bHandleAsSent) {
        $italic = '<i>';
        $italic_end = '</i>';
    }

    if ($where && $what) {
        $searchstr = '&amp;where='.$where.'&amp;what='.$what;
    } else {
        $searchstr = '';
    }
    /*
     *  Message highlight code
     */
    $matches = array('TO' => 'sTo', 'CC' => 'sCc', 'FROM' => 'sFrom', 'SUBJECT' => 'sSubject');
    if (is_array($message_highlight_list) && count($message_highlight_list)) {
        $sTo = parseAddress($sTo);
        $sCc = parseAddress($sCc);
        foreach ($message_highlight_list as $message_highlight_list_part) {
            if (trim($message_highlight_list_part['value']) != '') {
                $high_val   = strtolower($message_highlight_list_part['value']);
                $match_type = strtoupper($message_highlight_list_part['match_type']);
                if($match_type == 'TO_CC') {
                    $match = array('TO', 'CC');
                } else {
                    $match = array($match_type);
                }
                foreach($match as $match_type) {
                    switch($match_type) {
                        case('TO'):
                        case('CC'):
                        case('FROM'):
                            foreach ($$matches[$match_type] as $address) {
                                $address[0] = decodeHeader($address[0], true, false);
                                $address[1] = decodeHeader($address[1], true, false);
                                if (strstr('^^' . strtolower($address[0]), $high_val) ||
                                    strstr('^^' . strtolower($address[1]), $high_val)) {
                                    $hlt_color = $message_highlight_list_part['color'];
                                    break 4;
                                }
                            }
                            break;
                        default:
                            $headertest = strtolower(decodeHeader($$matches[$match_type], true, false));
                            if (strstr('^^' . $headertest, $high_val)) {
                                $hlt_color = $message_highlight_list_part['color'];
                                break 3;
                            }
                            break;
                    }
                }
            }
        }
    } /* end Message highlight code */

    if (!isset($hlt_color)) {
        $hlt_color = $color_string;
    }
    $col = 0;
    $sSubject = str_replace('&nbsp;', ' ', decodeHeader($sSubject));
    $subject = processSubject($sSubject, $iIndent);

    echo html_tag( 'tr','','','','valign="top"') . "\n";

    if (sizeof($index_order)) {
        foreach ($index_order as $index_order_part) {
            switch ($index_order_part) {
            case 1: /* checkbox */
                echo html_tag( 'td',
                    addCheckBox("msg[$t]", $checkall, $iId),
                            'center',
                            $hlt_color );
                break;
            case 2: /* from */
                if ($senderAddress != $senderName) {
                    $senderAddress = strtr($senderAddress, array_flip(get_html_translation_table(HTML_SPECIALCHARS)));
                    $title = ' title="' . str_replace('"', "''", $senderAddress) . '"';
                } else {
                    $title = '';
                }
                echo html_tag( 'td',
                            $italic . $bold . $flag . $fontstr . $senderName .
                            $fontstr_end . $flag_end . $bold_end . $italic_end,
                            'left',
                            $hlt_color, $title );
                break;
            case 3: /* date */
                if ($sDate == '') {
                    $sDate = _("Unknown date");
                }
                echo html_tag( 'td',
                            $bold . $flag . $fontstr . $sDate .
                            $fontstr_end . $flag_end . $bold_end,
                            'center',
                            $hlt_color,
                            'nowrap' );
                break;
            case 4: /* subject */
                $td_str = $bold;
                if ($iIndent) {
                    $td_str .= str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;",$iIndent);
                }
                $td_str .= '<a href="read_body.php?mailbox='.$urlMailbox
                        .  '&amp;passed_id='. $iId
                        .  '&amp;startMessage='.$start_msg.$searchstr.'"';
                $td_str .= ' ' .concat_hook_function('subject_link', array($start_msg, $searchstr));
                if ($subject != $sSubject) {
                    $title = get_html_translation_table(HTML_SPECIALCHARS);
                    $title = array_flip($title);
                    $title = strtr($sSubject, $title);
                    $title = str_replace('"', "''", $title);
                    $td_str .= " title=\"$title\"";
                }
                $td_str .= ">$flag$subject$flag_end</a>$bold_end";
                echo html_tag( 'td', $td_str, 'left', $hlt_color );
                break;
            case 5: /* flags */

                // icon message markers
                //
                if ($use_icons && $icon_theme != 'none') {
                    $td_str = "<b><small>";
                    if (isset($aFlags['\\flagged']) && $aFlags['\\flagged'] == true) {
                        $td_str .= '<img src="' . SM_PATH . 'images/themes/' . $icon_theme . '/flagged.png" border="0" height="10" width="10" /> ';
                    }
                    if ($default_use_priority) {
                        if ( ($iPrio == 1) || ($iPrio == 2) ) {
                            $td_str .= '<img src="' . SM_PATH . 'images/themes/' . $icon_theme . '/prio_high.png" border="0" height="10" width="5" /> ';
                        }
                        else if ($iPrio == 5) {
                            $td_str .= '<img src="' . SM_PATH . 'images/themes/' . $icon_theme . '/prio_low.png" border="0" height="10" width="5" /> ';
                        }
                        else {
                            $td_str .= '<img src="' . SM_PATH . 'images/themes/' . $icon_theme . '/transparent.png" border="0" width="5" /> ';
                        }
                    }
                    if ($sType1 == 'mixed') {
                        $td_str .= '<img src="' . SM_PATH . 'images/themes/' . $icon_theme . '/attach.png" border="0" height="10" width="6" />';
                    } else {
                        $td_str .= '<img src="' . SM_PATH . 'images/themes/' . $icon_theme . '/transparent.png" border="0" width="6" />';
                    }

                    $msg_icon = '';
                    if (!isset($aFlags['\\seen']) || ($aFlags['\\seen']) == false) {
                        $msg_alt = '(' . _("New") . ')';
                        $msg_title = '(' . _("New") . ')';
                        $msg_icon .= SM_PATH . 'images/themes/' . $icon_theme . '/msg_new';
                    } else {
                        $msg_alt = '(' . _("Read") . ')';
                        $msg_title = '(' . _("Read") . ')';
                        $msg_icon .= SM_PATH . 'images/themes/' . $icon_theme . '/msg_read';
                    }
                    if (isset($aFlags['\\deleted']) && ($aFlags['\\deleted']) == true) {
                        $msg_icon .= '_deleted';
                    }
                    if (isset($aFlags['\\answered']) && ($aFlags['\\answered']) == true) {
                        $msg_alt = '(' . _("Answered") . ')';
                        $msg_title = '(' . _("Answered") . ')';
                        $msg_icon .= '_reply';
                    }
                    $td_str .= '<img src="' . $msg_icon . '.png" border="0" alt="'. $msg_alt . '" title="' . $msg_title . '" height="12" width="18" />';
                    $td_str .= '</small></b>';
                    echo html_tag( 'td',
                                $td_str,
                                'right',
                                $hlt_color,
                                'nowrap' );
                }

                // plain text message markers
                //
                else {
                    $stuff = false;
                    $td_str = "<b><small>";
                    if (isset($aFlags['\\answered']) && $aFlags['\\answered'] == true) {
                        $td_str .= _("A");
                        $stuff = true;
                    }
                    if ($sType1 == 'mixed') {
                        $td_str .= '+';
                        $stuff = true;
                    }
                    if ($default_use_priority) {
                        if ( ($iPrio == 1) || ($iPrio == 2) ) {
                            $td_str .= "<font color=\"$color[1]\">!</font>";
                            $stuff = true;
                        }
                        if ($iPrio == 5) {
                            $td_str .= "<font color=\"$color[8]\">?</font>";
                            $stuff = true;
                        }
                    }
                    if (isset($aFlags['\\deleted']) && $aFlags['\\deleted'] == true) {
                        $td_str .= "<font color=\"$color[1]\">D</font>";
                        $stuff = true;
                    }
                    if (!$stuff) {
                        $td_str .= '&nbsp;';
                    }
                    $td_str .= '</small></b>';
                    echo html_tag( 'td',
                                $td_str,
                                'center',
                                $hlt_color,
                                'nowrap' );
                }
                break;
            case 6: /* size */
                echo html_tag( 'td',
                            $bold . $fontstr . show_readable_size($iSize) .
                            $fontstr_end . $bold_end,
                            'right',
                            $hlt_color );
                break;
            }
            ++$col;
        }
    }
    /* html for separationlines between rows */
    if ($last) {
        echo '</tr>'."\n";
    } else {
        echo '</tr>' . "\n" . '<tr><td colspan="' . $col . '" bgcolor="' .
            $color[0] . '" height="1"></td></tr>' . "\n";
    }
}


function setUserPref($username, $pref, $value) {
    global $data_dir;
    setPref($data_dir,$username,$pref,$value);
}

/**
 * Selects a mailbox for header retrieval.
 * Cache control for message headers is embedded.
 *
 * @param resource $imapConnection imap socket handle
 * @param string   $mailbox mailbox to select and retrieve message headers from
 * @param array    $aConfig array with system config settings and incoming vars
 * @param array    $aProps mailbox specific properties
 * @return array   $aMailbox mailbox array with all relevant information
 * @author Marc Groot Koerkamp
 */
function sqm_api_mailbox_select($imapConnection,$mailbox,$aConfig,$aProps) {
    /**
     * NB: retrieve this from the session before accessing this function
     * and make sure you write it back at the end of the script after
     * the aMailbox var is added so that the headers are added to the cache
     */
    global $mailbox_cache;
    /**
     * In case the properties arrays are empty set the defaults.
     */
    $aDefaultMbxPref = array ();
//                          MBX_PREF_SORT => 0,
//                          MBX_PREF_LIMIT => 15,
//                          MBX_PREF_AUTO_EXPUNGE => 0,
//                          MBX_PREF_INTERNALDATE => 0
//                           );
    /* array_merge doesn't work with integers as keys */
//    foreach ($aDefaultMbxPref as $key => $value) {
//        if (!isset($aProps[$key])) {
//            $aProps[$key] = $value;
//        }
//    }
    $aDefaultConfigProps = array(
//                'allow_thread_sort' => 0,
                'allow_server_sort' => sqimap_capability($imapConnection,'SORT'),
//                'charset'           => 'US-ASCII',
                'user'              => false, /* no pref storage if false */
                'setindex'          => 0,
//                'search'            => 'ALL',
                'max_cache_size'    => SQM_MAX_MBX_IN_CACHE
                );

    $aConfig = array_merge($aDefaultConfigProps,$aConfig);
    $iSetIndx = $aConfig['setindex'];

    $aMbxResponse = sqimap_mailbox_select($imapConnection, $mailbox);

    if ($mailbox_cache) {
        if (isset($mailbox_cache[$mailbox])) {
            $aCachedMailbox = $mailbox_cache[$mailbox];
        } else {
            $aCachedMailbox = false;
        }
            /* cleanup cache */
        if (count($mailbox_cache) > $aConfig['max_cache_size'] -1) {
            $aTime = array();
            foreach($mailbox_cache as $cachedmailbox => $aVal) {
                $aTime[$aVal['TIMESTAMP']] = $cachedmailbox;
            }
            if (ksort($aTime,SORT_NUMERIC)) {
                for ($i=0,$iCnt=count($mailbox_cache);$i<($iCnt-$aConfig['max_cache_size']);++$i) {
                    $sOldestMbx = array_shift($aTime);
                    /**
                     * Remove only the UIDSET and MSG_HEADERS from cache because those can
                     * contain large amounts of data.
                     */
                    if (isset($mailbox_cache[$sOldestMbx]['UIDSET'])) {
                        $mailbox_cache[$sOldestMbx]['UIDSET']= false;
                    }
                    if (isset($mailbox_cache[$sOldestMbx]['MSG_HEADERS'])) {
                        $mailbox_cache[$sOldestMbx]['MSG_HEADERS'] = false;
                    }
                }
            }
        }

    } else {
        $aCachedMailbox = false;
    }

    /**
     * Deal with imap servers that do not return the required UIDNEXT or
     * UIDVALIDITY response
     * from a SELECT call (since rfc 3501 it's required).
     */
    if (!isset($aMbxResponse['UIDNEXT']) || !isset($aMbxResponse['UIDVALIDITY'])) {
        $aStatus = sqimap_status_messages($imapConnection,$mailbox,
                                        array('UIDNEXT','UIDVALIDITY'));
        $aMbxResponse['UIDNEXT'] = $aStatus['UIDNEXT'];
        $aMbxResponse['UIDVALIDTY'] = $aStatus['UIDVALIDITY'];
    }

    $aMailbox['UIDSET'][$iSetIndx] = false;
    $aMailbox['ID'] = false;
    $aMailbox['SETINDEX'] = $iSetIndx;

    if ($aCachedMailbox) {
        /**
         * Validate integrity of cached data
         */
        if ($aCachedMailbox['EXISTS'] == $aMbxResponse['EXISTS'] &&
            $aMbxResponse['EXISTS'] &&
            $aCachedMailbox['UIDVALIDITY'] == $aMbxResponse['UIDVALIDITY'] &&
            $aCachedMailbox['UIDNEXT']  == $aMbxResponse['UIDNEXT'] &&
            isset($aCachedMailbox['SEARCH'][$iSetIndx]) &&
            (!isset($aConfig['search']) || /* always set search from the searchpage */
             $aCachedMailbox['SEARCH'][$iSetIndx] == $aConfig['search'])) {
            if (isset($aCachedMailbox['MSG_HEADERS'])) {
                $aMailbox['MSG_HEADERS'] = $aCachedMailbox['MSG_HEADERS'];
            }
            $aMailbox['ID'] =  $aCachedMailbox['ID'];
            if (isset($aCachedMailbox['UIDSET'][$iSetIndx]) && $aCachedMailbox['UIDSET'][$iSetIndx]) {
                if (isset($aProps[MBX_PREF_SORT]) &&  $aProps[MBX_PREF_SORT] != $aCachedMailbox['SORT'] ) {
                    $newsort = $aProps[MBX_PREF_SORT];
                    $oldsort = $aCachedMailbox['SORT'];
                    /**
                     * If it concerns a reverse sort we do not need to invalidate
                     * the cached sorted UIDSET, a reverse is sufficient.
                     */
                    if ((($newsort % 2) && ($newsort + 1 == $oldsort)) ||
                        (!($newsort % 2) && ($newsort - 1 == $oldsort))) {
                        $aMailbox['UIDSET'][$iSetIndx] = array_reverse($aCachedMailbox['UIDSET'][$iSetIndx]);
                    } else {
                        $server_sort_array = false;
                        $aMailbox['MSG_HEADERS'] = false;
                        $aMailbox['ID'] = false;
                    }
                    // store the new sort value in the mailbox pref
                    if ($aConfig['user']) {
                        // FIXME, in ideal situation, we write back the
                        // prefs at the end of the script
                        setUserPref($aConfig['user'],"pref_$mailbox",serialize($aProps));
                    }
                } else {
                    $aMailbox['UIDSET'][$iSetIndx] = $aCachedMailbox['UIDSET'][$iSetIndx];
                }
            }
        }
    }
    /**
     * Restore the offset in the paginator if no new offset is provided.
     */
    if (isset($aMailbox['UIDSET'][$iSetIndx]) && !isset($aConfig['offset']) && $aCachedMailbox['OFFSET']) {
        $aMailbox['OFFSET'] =  $aCachedMailbox['OFFSET'];
        $aMailbox['PAGEOFFSET'] =  $aCachedMailbox['PAGEOFFSET'];
    } else {
        $aMailbox['OFFSET'] = (isset($aConfig['offset']) && $aConfig['offset']) ? $aConfig['offset'] -1 : 0;
        $aMailbox['PAGEOFFSET'] = (isset($aConfig['offset']) && $aConfig['offset']) ? $aConfig['offset'] : 1;
    }

    /**
     * Restore the showall value no new showall value is provided.
     */
    if (isset($aMailbox['UIDSET'][$iSetIndx]) && !isset($aConfig['showall']) &&
        isset($aCachedMailbox['SHOWALL'][$iSetIndx]) && $aCachedMailbox['SHOWALL'][$iSetIndx]) {
        $aMailbox['SHOWALL'][$iSetIndx] =  $aCachedMailbox['SHOWALL'][$iSetIndx];
    } else {
        $aMailbox['SHOWALL'][$iSetIndx] = (isset($aConfig['showall']) && $aConfig['showall']) ? 1 : 0;
    }

    if (!isset($aProps[MBX_PREF_SORT]) && isset($aCachedMailbox['SORT'])) {
        $aMailbox['SORT'] = $aCachedMailbox['SORT'];
    } else {
        $aMailbox['SORT'] =  (isset($aProps[MBX_PREF_SORT])) ? $aProps[MBX_PREF_SORT] : 0;
    }

    if (!isset($aProps[MBX_PREF_LIMIT]) && isset($aCachedMailbox['LIMIT'])) {
        $aMailbox['LIMIT'] = $aCachedMailbox['LIMIT'];
    } else {
        $aMailbox['LIMIT'] =  (isset($aProps[MBX_PREF_LIMIT])) ? $aProps[MBX_PREF_LIMIT] : 15;
    }

    if (!isset($aProps[MBX_PREF_INTERNALDATE]) && isset($aCachedMailbox['INTERNALDATE'])) {
        $aMailbox['INTERNALDATE'] = $aCachedMailbox['INTERNALDATE'];
    } else {
        $aMailbox['INTERNALDATE'] =  (isset($aProps[MBX_PREF_INTERNALDATE])) ? $aProps[MBX_PREF_INTERNALDATE] : false;
    }

    if (!isset($aProps[MBX_PREF_AUTO_EXPUNGE]) && isset($aCachedMailbox['AUTO_EXPUNGE'])) {
        $aMailbox['AUTO_EXPUNGE'] = $aCachedMailbox['AUTO_EXPUNGE'];
    } else {
        $aMailbox['AUTO_EXPUNGE'] =  (isset($aProps[MBX_PREF_AUTO_EXPUNGE])) ? $aProps[MBX_PREF_AUTO_EXPUNGE] : false;
    }

    if (!isset($aConfig['allow_thread_sort']) && isset($aCachedMailbox['ALLOW_THREAD'])) {
        $aMailbox['ALLOW_THREAD'] = $aCachedMailbox['ALLOW_THREAD'];
    } else {
        $aMailbox['ALLOW_THREAD'] =  (isset($aConfig['allow_thread_sort'])) ? $aConfig['allow_thread_sort'] : false;
    }

    if (!isset($aConfig['search']) && isset($aCachedMailbox['SEARCH'][$iSetIndx])) {
        $aMailbox['SEARCH'][$iSetIndx] = $aCachedMailbox['SEARCH'][$iSetIndx];
    } else {
        $aMailbox['SEARCH'][$iSetIndx] =  (isset($aConfig['search'])) ? $aConfig['search'] : 'ALL';
    }

    if (!isset($aConfig['charset']) && isset($aCachedMailbox['CHARSET'][$iSetIndx])) {
        $aMailbox['CHARSET'][$iSetIndx] = $aCachedMailbox['CHARSET'][$iSetIndx];
    } else {
        $aMailbox['CHARSET'][$iSetIndx] =  (isset($aConfig['charset'])) ? $aConfig['charset'] : 'US-ASCII';
    }

    $aMailbox['NAME'] = $mailbox;
    $aMailbox['EXISTS'] = $aMbxResponse['EXISTS'];
    $aMailbox['SEEN'] = (isset($aMbxResponse['SEEN'])) ? $aMbxResponse['SEEN'] : $aMbxResponse['EXISTS'];
    $aMailbox['RECENT'] = (isset($aMbxResponse['RECENT'])) ? $aMbxResponse['RECENT'] : 0;
    $aMailbox['UIDVALIDITY'] = $aMbxResponse['UIDVALIDITY'];
    $aMailbox['UIDNEXT'] = $aMbxResponse['UIDNEXT'];
    $aMailbox['PERMANENTFLAGS'] = $aMbxResponse['PERMANENTFLAGS'];
    $aMailbox['RIGHTS'] = $aMbxResponse['RIGHTS'];



    /* decide if we are thread sorting or not */
    if (!$aMailbox['ALLOW_THREAD']) {
        if ($aMailbox['SORT'] & SQSORT_THREAD) {
            $aMailbox['SORT'] -= SQSORT_THREAD;
        }
    }
    if ($aMailbox['SORT'] & SQSORT_THREAD) {
        $aMailbox['SORT_METHOD'] = 'THREAD';
        $aMailbox['THREAD_INDENT'] = $aCachedMailbox['THREAD_INDENT'];
    } else if (isset($aConfig['allow_server_sort']) && $aConfig['allow_server_sort']) {
        $aMailbox['SORT_METHOD'] = 'SERVER';
        $aMailbox['THREAD_INDENT'] = false;
    } else {
        $aMailbox['SORT_METHOD'] = 'SQUIRREL';
        $aMailbox['THREAD_INDENT'] = false;
    }

    /* set a timestamp for cachecontrol */
    $aMailbox['TIMESTAMP'] = time();
    return $aMailbox;
}



/**
 * Does the $srt $_GET var to field mapping
 *
 * @param int $srt Field to sort on
 * @param bool $bServerSort Server sorting is true
 * @return string $sSortField Field to sort on
 */
function getSortField($sort,$bServerSort) {
    switch($sort) {
        case SQSORT_NONE:
            $sSortField = 'UID';
            break;
        case SQSORT_DATE_ASC:
        case SQSORT_DATE_DEC:
            $sSortField = 'DATE';
            break;
        case SQSORT_FROM_ASC:
        case SQSORT_FROM_DEC:
            $sSortField = 'FROM';
            break;
        case SQSORT_SUBJ_ASC:
        case SQSORT_SUBJ_DEC:
            $sSortField = 'SUBJECT';
            break;
        case SQSORT_SIZE_ASC:
        case SQSORT_SIZE_DEC:
            $sSortField = ($bServerSort) ? 'SIZE' : 'RFC822.SIZE';
            break;
        case SQSORT_TO_ASC:
        case SQSORT_TO_DEC:
            $sSortField = 'TO';
            break;
        case SQSORT_CC_ASC:
        case SQSORT_CC_DEC:
            $sSortField = 'CC';
            break;
        case SQSORT_INT_DATE_ASC:
        case SQSORT_INT_DATE_DEC:
            $sSortField = ($bServerSort) ? 'ARRIVAL' : 'INTERNALDATE';
            break;
        case SQSORT_THREAD:
            break;
        default: $sSortField = 'UID';
            break;

    }
    return $sSortField;
}

function get_sorted_msgs_list($imapConnection,&$aMailbox,&$error) {
    $iSetIndx = (isset($aMailbox['SETINDEX'])) ? $aMailbox['SETINDEX'] : 0;
    $bDirection = ($aMailbox['SORT'] % 2);
    $error = false;
    if (!$aMailbox['SEARCH'][$iSetIndx]) {
        $aMailbox['SEARCH'][$iSetIndx] = 'ALL';
    }
    switch ($aMailbox['SORT_METHOD']) {
      case 'THREAD':
        $aRes = get_thread_sort($imapConnection,$aMailbox['SEARCH'][$iSetIndx]);
        if ($aRes === false) {
            $error = '<b><small><center><font color=red>' .
                _("Thread sorting is not supported by your IMAP server.") . '<br />' .
                _("Please contact your system administrator and report this error.") .
                '</center></small></b>';
            $aMailbox['SORT'] -= SQSORT_THREAD;
        } else {
            $aMailbox['UIDSET'][$iSetIndx] = $aRes[0];
            $aMailbox['THREAD_INDENT'][$iSetIndx] = $aRes[1];
        }
        break;
      case 'SERVER':
        $sSortField = getSortField($aMailbox['SORT'],true);
        $id = sqimap_get_sort_order($imapConnection, $sSortField, $bDirection, $aMailbox['SEARCH'][$iSetIndx]);
        if ($id === false) {
            $error =  '<b><small><center><font color=red>' .
                _("Server-side sorting is not supported by your IMAP server.") . '<br />' .
                _("Please contact your system administrator and report this error.") .
                '</center></small></b>';
        } else {
            $aMailbox['UIDSET'][$iSetIndx] = $id;
        }
        break;
      default:
        $id = NULL;
        if ($aMailbox['SEARCH'][$iSetIndx] != 'ALL') {
            $id = sqimap_run_search($imapConnection, $aMailbox['SEARCH'][$iSetIndx], $aMailbox['CHARSET'][$iSetIndx]);
        }
        $sSortField = getSortField($aMailbox['SORT'],false);
        $aMailbox['UIDSET'][$iSetIndx] = get_squirrel_sort($imapConnection, $sSortField, $bDirection, $id);
        break;
    }
    return $error;
}




function fetchMessageHeaders($imapConnection, &$aMailbox) {

    /**
     * Retrieve the UIDSET.
     * Setindex is used to be able to store multiple uid sets. That will make it
     * possible to display the mailbox multiple times in different sort order
     * or to store serach results separate from normal mailbox view.
     */
    $iSetIndx =  (isset($aMailbox['SETINDEX'])) ? $aMailbox['SETINDEX'] : 0;

    $iLimit = ($aMailbox['SHOWALL'][$iSetIndx]) ? $aMailbox['EXISTS'] : $aMailbox['LIMIT'];
    /**
     * Adjust the start_msg
     */
    $start_msg = $aMailbox['PAGEOFFSET'];
    if($aMailbox['PAGEOFFSET'] > $aMailbox['EXISTS']) {
        $start_msg -= $aMailbox['LIMIT'];
        if($start_msg < 1) {
            $start_msg = 1;
        }
    }


    if (is_array($aMailbox['UIDSET'])) {
        $aUid =& $aMailbox['UIDSET'][$iSetIndx];
    } else {
        $aUid = false;
    }

    // initialize the fields we want to retrieve:
    $aHeaderFields = array('Date', 'To', 'Cc', 'From', 'Subject', 'X-Priority', 'Content-Type');
    $aFetchItems = array('FLAGS', 'RFC822.SIZE');

    // Are we sorting on internaldate then retrieve the internaldate value as well
    if ($aMailbox['INTERNALDATE']) {
        $aFetchItems[] = 'INTERNALDATE';
    }


    /**
     * A uidset with sorted uid's is available. We can use the cache
     */
    if (($aMailbox['SORT'] != SQSORT_NONE || $aMailbox['SEARCH'][$iSetIndx] != 'ALL') &&
         isset($aUid) && $aUid ) {

        // limit the cache to SQM_MAX_PAGES_IN_CACHE
        if (!$aMailbox['SHOWALL'][$iSetIndx]) {
            $iMaxMsgs = $iLimit * SQM_MAX_PAGES_IN_CACHE;
            $iCacheSize = count($aMailbox['MSG_HEADERS']);
            if ($iCacheSize > $iMaxMsgs) {
                $iReduce = $iCacheSize - $iMaxMsgs;
                foreach ($aMailbox['MSG_HEADERS'] as $iUid => $value) {
                    if ($iReduce) {
                        unset($aMailbox['MSG_HEADERS'][$iUid]);
                    } else {
                        break;
                    }
                    --$iReduce;
                }
            }
        }

        $id_slice = array_slice($aUid,$start_msg-1,$iLimit);
        /* do some funky cache checks */
        $aUidCached = array_keys($aMailbox['MSG_HEADERS']);
        $aUidNotCached = array_values(array_diff($id_slice,$aUidCached));
        /**
         * $aUidNotCached contains an array with UID's which need to be fetched to
         * complete the needed message headers.
         */
        if (count($aUidNotCached)) {
            $aMsgs = sqimap_get_small_header_list($imapConnection,$aUidNotCached,
                                                    $aHeaderFields,$aFetchItems);
            // append the msgs to the existend headers
            $aMailbox['MSG_HEADERS'] += $aMsgs;
        }

    } else {
        /**
         * Initialize the sorted UID list and fetch the visible message headers
         */
        if ($aMailbox['SORT'] != SQSORT_NONE || $aMailbox['SEARCH'][$iSetIndx] != 'ALL') {//  || $aMailbox['SORT_METHOD'] & SQSORT_THREAD 'THREAD') {

            $error = false;
            if ($aMailbox['SEARCH'][$iSetIndx] && $aMailbox['SORT'] == 0) {
                $aUid = sqimap_run_search($imapConnection, $aMailbox['SEARCH'][$iSetIndx], $aMailbox['CHARSET'][$iSetIndx]);
            } else {
                $error = get_sorted_msgs_list($imapConnection,$aMailbox,$error);
                $aUid = $aMailbox['UIDSET'][$iSetIndx];
            }
            if ($error === false) {
                $id_slice = array_slice($aUid,$aMailbox['OFFSET'], $iLimit);
                if (count($id_slice)) {
                    $aMailbox['MSG_HEADERS'] = sqimap_get_small_header_list($imapConnection,$id_slice,
                        $aHeaderFields,$aFetchItems);
                } else {
                    return false;
                }

            } else {
                // FIX ME, format message and fallback to squirrel sort
                if ($error) {
                    echo $error;
                }
            }
        } else {
            // limit the cache to SQM_MAX_PAGES_IN_CACHE
            if (!$aMailbox['SHOWALL'][$iSetIndx] && isset($aMailbox['MSG_HEADERS']) && is_array($aMailbox['MSG_HEADERS'])) {
                $iMaxMsgs = $iLimit * SQM_MAX_PAGES_IN_CACHE;
                $iCacheSize = count($aMailbox['MSG_HEADERS']);
                if ($iCacheSize > $iMaxMsgs) {
                    $iReduce = $iCacheSize - $iMaxMsgs;
                    foreach ($aMailbox['MSG_HEADERS'] as $iUid => $value) {
                        if ($iReduce) {
                            $iId = $aMailbox['MSG_HEADERS'][$iUid]['ID'];
                            unset($aMailbox['MSG_HEADERS'][$iUid]);
                            unset($aMailbox['ID'][$iId]);
                        } else {
                            break;
                        }
                        --$iReduce;
                    }
                }
            }

            /**
             * retrieve messages by sequence id's and fetch the UID to retrieve
             * the UID. for sorted lists this is not needed because a UID FETCH
             * automaticly add the UID value in fetch results
             **/
            $aFetchItems[] = 'UID';

            //create id range
            $iRangeStart = $aMailbox['EXISTS'] - $aMailbox['OFFSET'];
            $iRangeEnd   = ($iRangeStart > $iLimit) ?
                            ($iRangeStart - $iLimit+1):1;

            $id_slice = range($iRangeStart, $iRangeEnd);
            /**
             * Non sorted mailbox with cached message headers
             */
            if (isset($aMailbox['ID']) && is_array($aMailbox['ID'])) {
                // the fetched id => uid relation
                $aId = $aMailbox['ID'];
                $aIdCached = array();
                foreach ($aId as $iId => $iUid) {
                    if (isset($aMailbox['MSG_HEADERS'][$iUid])) {
                        if ($iId <= $iRangeStart && $iId >= $iRangeEnd) {
                            $aIdCached[] = $iId;
                        }
                    }
                }
                $aIdNotCached = array_diff($id_slice,$aIdCached);
            } else {
                $aIdNotCached = $id_slice;
            }

            if (count($aIdNotCached)) {
                $aMsgs = sqimap_get_small_header_list($imapConnection,$aIdNotCached,
                    $aHeaderFields,$aFetchItems);
                // append the msgs to the existend headers
                if (isset($aMailbox['MSG_HEADERS']) && is_array($aMailbox['MSG_HEADERS'])) {
                    $aMailbox['MSG_HEADERS'] += $aMsgs;
                } else {
                    $aMailbox['MSG_HEADERS'] = $aMsgs;
                }
                // update the ID array
                foreach ($aMsgs as $iUid => $aMsg) {
                    if (isset($aMsg['ID'])) {
                        $aMailbox['ID'][$aMsg['ID']] = $iUid;
                    }
                }
            }

            /**
             * In unsorted state we show newest messages first which means
             * that the UIDSET which represents the order of the messages
             * should contain a high to low ordered UID list
             */
            $aSortedUidList = array();
            foreach ($id_slice as $iId) {
                if (isset($aMailbox['ID'][$iId])) {
                    $aSortedUidList[] = $aMailbox['ID'][$iId];
                }
            }
            $aMailbox['UIDSET'][$iSetIndx] = $aSortedUidList;
            $aMailbox['OFFSET'] = 0;
        }
    }
    return true;
}

/**
 * This function loops through a group of messages in the mailbox
 * and shows them to the user.
 *
 * @param mixed $imapConnection
 * @param array $aMailbox associative array with mailbox related vars
 */
function showMessagesForMailbox($imapConnection, &$aMailbox) {
    global $color;

    // to retrieve the internaldate pref: (I know this is not the right place to do that, move up in front
    // and use a properties array as function argument to provide user preferences
    global $data_dir, $username;

    if (!fetchMessageHeaders($imapConnection, $aMailbox)) {
        return false;
    }
    $iSetIndx = $aMailbox['SETINDEX'];
    $iLimit = ($aMailbox['SHOWALL'][$iSetIndx]) ? $aMailbox['EXISTS'] : $aMailbox['LIMIT'];
    $iEnd = ($aMailbox['PAGEOFFSET'] + ($iLimit - 1) < $aMailbox['EXISTS']) ?
             $aMailbox['PAGEOFFSET'] + $iLimit - 1 : $aMailbox['EXISTS'];

    $paginator_str = get_paginator_str($aMailbox['NAME'], $aMailbox['PAGEOFFSET'],
                                    $aMailbox['EXISTS'], $aMailbox['LIMIT'], $aMailbox['SHOWALL'][$iSetIndx]);

    $msg_cnt_str = get_msgcnt_str($aMailbox['PAGEOFFSET'], $iEnd,$aMailbox['EXISTS']);

    do_hook('mailbox_index_before');
?>
<table border="0" width="100%" cellpadding="0" cellspacing="0">
<tr>
    <td>
    <?php mail_message_listing_beginning($imapConnection, $aMailbox, $msg_cnt_str, $paginator_str); ?>
    </td>
</tr>
<tr><td height="5" bgcolor="<?php echo $color[4]; ?>"></td></tr>
<tr>
    <td>
    <table width="100%" cellpadding="1" cellspacing="0" align="center" border="0" bgcolor="<?php echo $color[9]; ?>">
        <tr>
        <td>
            <table width="100%" cellpadding="1" cellspacing="0" align="center" border="0" bgcolor="<?php echo $color[5]; ?>">
            <tr>
                <td>
                <?php
                    printHeader($aMailbox);
                    displayMessageArray($imapConnection, $aMailbox);
                ?>
                </td>
            </tr>
            </table>
        </td>
        </tr>
    </table>
    <?php
        mail_message_listing_end($aMailbox['EXISTS'], $paginator_str, $msg_cnt_str);
    ?>
    </td>
</tr>
</table>
<?php

}

/**
 * Function to map an uid list with a msg header array by uid
 * The mapped headers are printed with printMessage
 * aMailbox parameters contains info about the page we are on, the
 * used search criteria, the number of messages to show
 *
 * @param resource $imapConnection socket handle to imap
 * @param array    $aMailbox array with required elements MSG_HEADERS, UIDSET, OFFSET, LIMIT
 * @return void
 **/
function displayMessageArray($imapConnection, $aMailbox) {
    $iSetIndx    = $aMailbox['SETINDEX'];
    $aId         = $aMailbox['UIDSET'][$iSetIndx];
    $aHeaders    = $aMailbox['MSG_HEADERS'];
    $iOffset     = $aMailbox['OFFSET'];
    $sort        = $aMailbox['SORT'];
    $iPageOffset = $aMailbox['PAGEOFFSET'];
    $sMailbox    = $aMailbox['NAME'];
    $sSearch     = (isset($aMailbox['SEARCH'][$aMailbox['SETINDEX']])) ? $aMailbox['SEARCH'][$aMailbox['SETINDEX']] : false;
    $aSearch     = ($sSearch) ? array('search.php',$aMailbox['SETINDEX']) : null;

    if ($aMailbox['SORT'] & SQSORT_THREAD) {
        $aIndentArray =& $aMailbox['THREAD_INDENT'][$aMailbox['SETINDEX']];
        $bThread = true;
    } else {
        $bThread = false;
    }
    /*
    * Loop through and display the info for each message.
    * ($t is used for the checkbox number)
    */
    $iEnd = ($aMailbox['SHOWALL'][$iSetIndx]) ? $aMailbox['EXISTS'] : $iOffset + $aMailbox['LIMIT'];
    for ($i=$iOffset,$t=0;$i<$iEnd;++$i) {
        if (isset($aId[$i])) {
            $bLast = ((isset($aId[$i+1]) && isset($aHeaders[$aId[$i+1]]))
                                 || ($i == $iEnd )) ? false : true;
            if ($bThread) {
               $indent = (isset($aIndentArray[$aId[$i]])) ? $aIndentArray[$aId[$i]] : 0;
            } else {
               $indent = 0;
            }
            $aMsg = array(
                      'HEADER'     => $aHeaders[$aId[$i]],
                      'INDX'       => $t,
                      'OFFSET'     => $iOffset,
                      'PAGEOFFSET' => $iPageOffset,
                      'SORT'       => $sort,
                      'SEARCH'     => $aSearch,
                      'MAILBOX'    => $sMailbox,
                      'INDENT'     => $indent,
                      'LAST'       => $bLast
                    );
             printMessageInfo($aMsg);
            ++$t;
        } else {
            break;
        }
    }
}

/**
 * Displays the standard message list header.
 *
 * To finish the table, you need to do a "</table></table>";
 *
 * @param resource $imapConnection
 * @param array    $aMailbox associative array with mailbox related information
 * @param string   $msg_cnt_str
 * @param string   $paginator Paginator string
 */
function mail_message_listing_beginning ($imapConnection,
                                         $aMailbox,
                                         $msg_cnt_str = '',
                                         $paginator = '&nbsp;'
                                        ) {
    global $color, $show_flag_buttons, $PHP_SELF;
    global $lastTargetMailbox, $boxes;

    $php_self = $PHP_SELF;

    $urlMailbox = urlencode($aMailbox['NAME']);

    if (preg_match('/^(.+)\?.+$/',$php_self,$regs)) {
        $source_url = $regs[1];
    } else {
        $source_url = $php_self;
    }

    if (!isset($msg)) {
        $msg = '';
    }

    $moveFields = addHidden('msg', $msg).
                  addHidden('mailbox', $aMailbox['NAME']).
                  addHidden('startMessage', $aMailbox['PAGEOFFSET']);

    /* build thread sorting links */
    $sort = $aMailbox['SORT'];
    if ($aMailbox['ALLOW_THREAD']) {
        if ($aMailbox['SORT'] & SQSORT_THREAD) {
            $sort -= SQSORT_THREAD;
            $thread_name = _("Unthread View");
        } else {
            $thread_name = _("Thread View");
            $sort = $aMailbox['SORT'] + SQSORT_THREAD;
        }
        $thread_link_str = '<small>[<a href="' . $source_url . '?srt='
            . $sort . '&start_messages=1'
            . '&mailbox=' . urlencode($aMailbox['NAME']) . '">' . $thread_name
            . '</a>]</small>';
    } else {
        $thread_link_str ='';
    }
    /*
    * This is the beginning of the message list table.
    * It wraps around all messages
    */
    $safe_name = preg_replace("/[^0-9A-Za-z_]/", '_', $aMailbox['NAME']);
    $form_name = "FormMsgs" . $safe_name;

    echo '<form name="' . $form_name . '" method="post" action="'.$php_self.'">' ."\n"
        . $moveFields;

    $button_str = '';
    // display flag buttons only if supported
    if ($show_flag_buttons  &&
        in_array('\\flagged',$aMailbox['PERMANENTFLAGS'], true) ) {
        $button_str .= getButton('submit', 'markUnflagged', _("Unflag"));
        $button_str .= getButton('submit', 'markFlagged',   _("Flag"));
        $button_str .= "&nbsp;\n";
    }
    if (in_array('\\seen',$aMailbox['PERMANENTFLAGS'], true)) {
        $button_str .= getButton('submit', 'markUnread', _("Unread"));
        $button_str .= getButton('submit', 'markRead',   _("Read"));
        $button_str .= "&nbsp;\n";
    }
    $button_str .= getButton('submit', 'attache',_("Forward")) .
                   "&nbsp;\n";
    if (in_array('\\deleted',$aMailbox['PERMANENTFLAGS'], true)) {
        $button_str .= getButton('submit', 'delete',_("Delete"));
        $button_str .= '<input type="checkbox" name="bypass_trash" />' . _("Bypass Trash");
        $button_str .= "&nbsp;\n";
    }
    if (!$aMailbox['AUTO_EXPUNGE'] && $aMailbox['RIGHTS'] != 'READ-ONLY') {
        $button_str .= getButton('submit', 'expungeButton',_("Expunge"))  .'&nbsp;' . _("mailbox") . "\n";
        $button_str .= '&nbsp;';
    }
?>
    <table width="100%" cellpadding="1"  cellspacing="0" style="border: 1px solid <?php echo $color[0]; ?>">
        <tr>
        <td>
            <table bgcolor="<?php echo $color[4]; ?>" border="0" width="100%" cellpadding="1"  cellspacing="0">
            <tr>
                <?php echo html_tag('td', '<small>' . $paginator . $thread_link_str . '</small>', 'left') . "\n"; ?>
                <?php echo html_tag('td', '', 'center') . "\n"; ?>
                <?php echo html_tag('td', '<small>' . $msg_cnt_str . '</small>', 'right') . "\n"; ?>
            </tr>
            </table>
        </td>
        </tr>
        <tr width="100%" cellpadding="1"  cellspacing="0" border="0" bgcolor="<?php echo $color[0]; ?>">
        <td>
            <table border="0" width="100%" cellpadding="1"  cellspacing="0">
            <tr>
                <?php echo html_tag('td', '', 'left') . "\n"; ?>
                <small>
                    <?php echo $button_str; ?>
                    <?php do_hook('mailbox_display_buttons'); ?>
                </small>
                </td>
                <?php
                if (in_array('\\deleted',$aMailbox['PERMANENTFLAGS'], true)) {
                ?>
                <?php echo html_tag('td', '', 'right'); ?>
                    <small>&nbsp;<tt>
                        <select name="targetMailbox">
                            <?php echo sqimap_mailbox_option_list($imapConnection, array(strtolower($lastTargetMailbox)), 0, $boxes); ?>
                        </select></tt>&nbsp;
                        <?php echo getButton('submit', 'moveButton',_("Move")); ?>
                    </small>
                <?php
                }
                ?>
                </td>
            </tr>
            </table>
        </td>
        </tr>
    </table>
<?php
    do_hook('mailbox_form_before');
}

/**
 * Function to add the last row in a message list, it contains the paginator and info about
 * the number of messages.
 *
 * @param integer $num_msgs number of messages in a mailbox
 * @param string  $paginator_str Paginator string  [Prev | Next]  [ 1 2 3 ... 91 92 94 ]  [Show all]
 * @param string  $msg_cnt_str   Message count string Viewing Messages: 21 to 1861 (20 total)
 */
function mail_message_listing_end($num_msgs, $paginator_str, $msg_cnt_str) {
global $color;
if ($num_msgs) {
    /* space between list and footer */
?>
<tr><td height="5" bgcolor="<?php echo $color[4]; ?>" colspan="1"></td></tr>
<tr>
    <td>
    <table width="100%" cellpadding="1"  cellspacing="0" style="border: 1px solid <?php echo $color[0]; ?>">
        <tr>
        <td>
            <table bgcolor="<?php echo $color[4]; ?>" border="0" width="100%" cellpadding="1"  cellspacing="0">
            <tr>
                    <?php echo html_tag('td', '<small>' . $paginator_str . '</small>', 'left');  ?>
                    <?php echo html_tag('td', '<small>' . $msg_cnt_str   . '</small>', 'right'); ?>
            </tr>
            </table>
        </td>
        </tr>
    </table>
    </td>
</tr>
<?php
}
    /* End of message-list table */

    do_hook('mailbox_index_after');
    echo "</form>\n";
}

/**
 * Prints the table header for the messages list view
 *
 * @param array $aMailbox
 */
function printHeader($aMailbox) {
    global $index_order, $internal_date_sort, $color;

    if ($aMailbox['SORT_METHOD'] != 'THREAD') {
        $showsort = true;
    } else {
        $showsort = false;
    }

    echo html_tag( 'tr' ,'' , 'center', $color[5] );

    /* calculate the width of the subject column based on the
     * widths of the other columns */
    $widths = array(1=>1,2=>25,3=>5,4=>0,5=>1,6=>5);
    $subjectwidth = 100;
    foreach($index_order as $item) {
        $subjectwidth -= $widths[$item];
    }

    foreach ($index_order as $item) {
        switch ($item) {
        case 1: /* checkbox */
            echo html_tag( 'td',get_selectall_link($aMailbox) , '', '', 'width="1%"' );
            break;
        case 5: /* flags */
            echo html_tag( 'td','' , '', '', 'width="1%"' );
            break;
        case 2: /* from */
            if (handleAsSent($aMailbox['NAME'])) {
                echo html_tag( 'td' ,'' , 'left', '', 'width="25%"' )
                    . '<b>' . _("To") . '</b>';
                if ($showsort) {
                    ShowSortButton($aMailbox, SQSORT_TO_ASC, SQSORT_TO_DEC);
                }
            } else {
                echo html_tag( 'td' ,'' , 'left', '', 'width="25%"' )
                    . '<b>' . _("From") . '</b>';
                if ($showsort) {
                    ShowSortButton($aMailbox, SQSORT_FROM_ASC, SQSORT_FROM_DEC);
                }
            }
            echo "</td>\n";
            break;
        case 3: /* date */
            echo html_tag( 'td' ,'' , 'left', '', 'width="5%" nowrap' )
                . '<b>' . _("Date") . '</b>';
            if ($showsort) {
                if ($internal_date_sort) {
                    ShowSortButton($aMailbox, SQSORT_INT_DATE_ASC, SQSORT_INT_DATE_DEC);
                } else {
                    ShowSortButton($aMailbox, SQSORT_DATE_ASC, SQSORT_DATE_DEC);
                }
            }
            echo "</td>\n";
            break;
        case 4: /* subject */
            echo html_tag( 'td' ,'' , 'left', '', 'width="'.$subjectwidth.'%"' )
                . '<b>' . _("Subject") . '</b>';
            if ($showsort) {
                ShowSortButton($aMailbox, SQSORT_SUBJ_ASC, SQSORT_SUBJ_DEC);
            }
            echo "</td>\n";
            break;
        case 6: /* size */
            echo html_tag( 'td', '', 'center','','width="5%" nowrap')
                . '<b>' . _("Size") . '</b>';
            if ($showsort) {
                ShowSortButton($aMailbox, SQSORT_SIZE_ASC, SQSORT_SIZE_DEC);
            }
            echo "</td>\n";
            break;
        }
    }
    echo "</tr>\n";
}


/**
 * This function shows the sort button. Isn't this a good comment?
 *
 * @param array $aMailbox
 * @param integer $Down
 * @param integer $Up
 */
function ShowSortButton($aMailbox, $Down, $Up ) {
    global $PHP_SELF;

    /* Figure out which image we want to use. */
    if ($aMailbox['SORT'] != $Up && $aMailbox['SORT'] != $Down) {
        $img = 'sort_none.png';
        $which = $Up;
    } elseif ($aMailbox['SORT'] == $Up) {
        $img = 'up_pointer.png';
        $which = $Down;
    } else {
        $img = 'down_pointer.png';
        $which = 0;
    }

    if (preg_match('/^(.+)\?.+$/',$PHP_SELF,$regs)) {
        $source_url = $regs[1];
    } else {
        $source_url = $PHP_SELF;
    }

    /* Now that we have everything figured out, show the actual button. */
    echo ' <a href="' . $source_url .'?srt=' . $which
        . '&amp;startMessage=1&amp;mailbox=' . urlencode($aMailbox['NAME'])
        . '"><img src="../images/' . $img
        . '" border="0" width="12" height="10" alt="sort" title="'
        . _("Click here to change the sorting of the message list") .'" /></a>';
}

/**
 * FIXME: Undocumented function
 *
 * @param array $aMailbox
 */
function get_selectall_link($aMailbox) {
    global $checkall, $javascript_on;
    global $PHP_SELF;

    $result = '';
    if ($javascript_on) {
        $safe_name = preg_replace("/[^0-9A-Za-z_]/", '_', $aMailbox['NAME']);
        $func_name = "CheckAll" . $safe_name;
        $form_name = "FormMsgs" . $safe_name;
        $result = '<script language="JavaScript" type="text/javascript">'
                . "\n<!-- \n"
                . "function " . $func_name . "() {\n"
                . "  for (var i = 0; i < document." . $form_name . ".elements.length; i++) {\n"
                . "    if(document." . $form_name . ".elements[i].type == 'checkbox' && "
                . "       document." . $form_name . ".elements[i].name.substring(0,3) == 'msg'){\n"
                . "      document." . $form_name . ".elements[i].checked = "
                . "        !(document." . $form_name . ".elements[i].checked);\n"
                . "    }\n"
                . "  }\n"
                . "}\n"
                . "//-->\n"
                . '</script>'
                . '<input type="checkbox" name="toggleAll" title="'._("Toggle All").'" onclick="'.$func_name.'();" />';
//                . <a href="javascript:void(0)" onClick="' . $func_name . '();">' . _("Toggle All")
//                . "</a>\n";
    } else {
        if (strpos($PHP_SELF, "?")) {
            $result .= "<a href=\"$PHP_SELF&amp;mailbox=" . urlencode($aMailbox['NAME'])
                    .  "&amp;startMessage=$aMailbox[PAGEOFFSET]&amp;srt=$aMailbox[SORT]&amp;checkall=";
        } else {
            $result .= "<a href=\"$PHP_SELF?mailbox=" . urlencode($mailbox)
                    .  "&amp;startMessage=$aMailbox[PAGEOFFSET]&amp;srt=$aMailbox[SORT]&amp;checkall=";
        }
        if (isset($checkall) && $checkall == '1') {
            $result .= '0';
        } else {
            $result .= '1';
        }

        if (isset($aMailbox['SEARCH']) && $aMailbox['SEARCH'][0]) {
            $result .= '&amp;where=' . urlencode($aMailbox['SEARCH'][0])
                    .  '&amp;what=' .  urlencode($aMailbox['SEARCH'][1]);
        }
        $result .= "\">";
        $result .= _("All");
        $result .= "</a>\n";
    }

    /* Return our final result. */
    return ($result);
}

/**
 * This function computes the "Viewing Messages..." string.
 *
 * @param integer $start_msg first message number
 * @param integer $end_msg last message number
 * @param integer $num_msgs total number of message in folder
 * @return string
 */
function get_msgcnt_str($start_msg, $end_msg, $num_msgs) {
    /* Compute the $msg_cnt_str. */
    $result = '';
    if ($start_msg < $end_msg) {
        $result = sprintf(_("Viewing Messages: %s to %s (%s total)"),
                        '<b>'.$start_msg.'</b>', '<b>'.$end_msg.'</b>', $num_msgs);
    } else if ($start_msg == $end_msg) {
        $result = sprintf(_("Viewing Message: %s (%s total)"), '<b>'.$start_msg.'</b>', $num_msgs);
    } else {
        $result = '<br />';
    }
    /* Return our result string. */
    return ($result);
}

/**
 * Generate a paginator link.
 *
 * @param mixed $box Mailbox name
 * @param mixed $start_msg Message Offset
 * @param mixed $use
 * @param string $text text used for paginator link
 * @return string
 */
function get_paginator_link($box, $start_msg, $text) {
    sqgetGlobalVar('PHP_SELF',$php_self,SQ_SERVER);
    $result = "<a href=\"$php_self?startMessage=$start_msg&amp;mailbox=$box\" "
            . ">$text</a>";

    return ($result);
}

/**
 * This function computes the paginator string.
 *
 * @param string  $box      mailbox name
 * @param integer $iOffset  offset in total number of messages
 * @param integer $iTotal   total number of messages
 * @param integer $iLimit   maximum number of messages to show on a page
 * @param bool    $bShowAll show all messages at once (non paginate mode)
 * @return string $result   paginate string with links to pages
 */
function get_paginator_str($box, $iOffset, $iTotal, $iLimit, $bShowAll) {
    global $username, $data_dir;
    sqgetGlobalVar('PHP_SELF',$php_self,SQ_SERVER);

    /* Initialize paginator string chunks. */
    $prv_str = '';
    $nxt_str = '';
    $pg_str  = '';
    $all_str = '';

    $box = urlencode($box);

    /* Create simple strings that will be creating the paginator. */
    $spc = '&nbsp;';     /* This will be used as a space. */
    $sep = '|';          /* This will be used as a seperator. */

    /* Get some paginator preference values. */
    $pg_sel = getPref($data_dir, $username, 'page_selector', SMPREF_ON);
    $pg_max = getPref($data_dir, $username, 'page_selector_max', PG_SEL_MAX);

    /* Make sure that our start message number is not too big. */
    $iOffset = min($iOffset, $iTotal);

    /* Compute the starting message of the previous and next page group. */
    $next_grp = $iOffset + $iLimit;
    $prev_grp = $iOffset - $iLimit;

    if (!$bShowAll) {
        /* Compute the basic previous and next strings. */
        if (($next_grp <= $iTotal) && ($prev_grp >= 0)) {
            $prv_str = get_paginator_link($box, $prev_grp, _("Previous"));
            $nxt_str = get_paginator_link($box, $next_grp, _("Next"));
        } else if (($next_grp > $iTotal) && ($prev_grp >= 0)) {
            $prv_str = get_paginator_link($box, $prev_grp, _("Previous"));
            $nxt_str = _("Next");
        } else if (($next_grp <= $iTotal) && ($prev_grp < 0)) {
            $prv_str = _("Previous");
            $nxt_str = get_paginator_link($box, $next_grp, _("Next"));
        }

        /* Page selector block. Following code computes page links. */
        if ($iLimit != 0 && $pg_sel && ($iTotal > $iLimit)) {
            /* Most importantly, what is the current page!!! */
            $cur_pg = intval($iOffset / $iLimit) + 1;

            /* Compute total # of pages and # of paginator page links. */
            $tot_pgs = ceil($iTotal / $iLimit);  /* Total number of Pages */
            $vis_pgs = min($pg_max, $tot_pgs - 1);   /* Visible Pages    */

            /* Compute the size of the four quarters of the page links. */

            /* If we can, just show all the pages. */
            if (($tot_pgs - 1) <= $pg_max) {
                $q1_pgs = $cur_pg - 1;
                $q2_pgs = $q3_pgs = 0;
                $q4_pgs = $tot_pgs - $cur_pg;

            /* Otherwise, compute some magic to choose the four quarters. */
            } else {
                /*
                * Compute the magic base values. Added together,
                * these values will always equal to the $pag_pgs.
                * NOTE: These are DEFAULT values and do not take
                * the current page into account. That is below.
                */
                $q1_pgs = floor($vis_pgs/4);
                $q2_pgs = round($vis_pgs/4, 0);
                $q3_pgs = ceil($vis_pgs/4);
                $q4_pgs = round(($vis_pgs - $q2_pgs)/3, 0);

                /* Adjust if the first quarter contains the current page. */
                if (($cur_pg - $q1_pgs) < 1) {
                    $extra_pgs = ($q1_pgs - ($cur_pg - 1)) + $q2_pgs;
                    $q1_pgs = $cur_pg - 1;
                    $q2_pgs = 0;
                    $q3_pgs += ceil($extra_pgs / 2);
                    $q4_pgs += floor($extra_pgs / 2);

                /* Adjust if the first and second quarters intersect. */
                } else if (($cur_pg - $q2_pgs - ceil($q2_pgs/3)) <= $q1_pgs) {
                    $extra_pgs = $q2_pgs;
                    $extra_pgs -= ceil(($cur_pg - $q1_pgs - 1) * 3/4);
                    $q2_pgs = ceil(($cur_pg - $q1_pgs - 1) * 3/4);
                    $q3_pgs += ceil($extra_pgs / 2);
                    $q4_pgs += floor($extra_pgs / 2);

                /* Adjust if the fourth quarter contains the current page. */
                } else if (($cur_pg + $q4_pgs) >= $tot_pgs) {
                    $extra_pgs = ($q4_pgs - ($tot_pgs - $cur_pg)) + $q3_pgs;
                    $q3_pgs = 0;
                    $q4_pgs = $tot_pgs - $cur_pg;
                    $q1_pgs += floor($extra_pgs / 2);
                    $q2_pgs += ceil($extra_pgs / 2);

                /* Adjust if the third and fourth quarter intersect. */
                } else if (($cur_pg + $q3_pgs + 1) >= ($tot_pgs - $q4_pgs + 1)) {
                    $extra_pgs = $q3_pgs;
                    $extra_pgs -= ceil(($tot_pgs - $cur_pg - $q4_pgs) * 3/4);
                    $q3_pgs = ceil(($tot_pgs - $cur_pg - $q4_pgs) * 3/4);
                    $q1_pgs += floor($extra_pgs / 2);
                    $q2_pgs += ceil($extra_pgs / 2);
                }
            }

            /*
            * I am leaving this debug code here, commented out, because
            * it is a really nice way to see what the above code is doing.
            * echo "qts =  $q1_pgs/$q2_pgs/$q3_pgs/$q4_pgs = "
            *    . ($q1_pgs + $q2_pgs + $q3_pgs + $q4_pgs) . '<br />';
            */

            /* Print out the page links from the compute page quarters. */

            /* Start with the first quarter. */
            if (($q1_pgs == 0) && ($cur_pg > 1)) {
                $pg_str .= "...$spc";
            } else {
                for ($pg = 1; $pg <= $q1_pgs; ++$pg) {
                    $start = (($pg-1) * $iLimit) + 1;
                    $pg_str .= get_paginator_link($box, $start, $pg) . $spc;
                }
                if ($cur_pg - $q2_pgs - $q1_pgs > 1) {
                    $pg_str .= "...$spc";
                }
            }

            /* Continue with the second quarter. */
            for ($pg = $cur_pg - $q2_pgs; $pg < $cur_pg; ++$pg) {
                $start = (($pg-1) * $iLimit) + 1;
                $pg_str .= get_paginator_link($box, $start, $pg) . $spc;
            }

            /* Now print the current page. */
            $pg_str .= $cur_pg . $spc;

            /* Next comes the third quarter. */
            for ($pg = $cur_pg + 1; $pg <= $cur_pg + $q3_pgs; ++$pg) {
                $start = (($pg-1) * $iLimit) + 1;
                $pg_str .= get_paginator_link($box, $start, $pg) . $spc;
            }

            /* And last, print the forth quarter page links. */
            if (($q4_pgs == 0) && ($cur_pg < $tot_pgs)) {
                $pg_str .= "...$spc";
            } else {
                if (($tot_pgs - $q4_pgs) > ($cur_pg + $q3_pgs)) {
                    $pg_str .= "...$spc";
                }
                for ($pg = $tot_pgs - $q4_pgs + 1; $pg <= $tot_pgs; ++$pg) {
                    $start = (($pg-1) * $iLimit) + 1;
                    $pg_str .= get_paginator_link($box, $start,$pg) . $spc;
                }
            }
        }
    } else {
        $pg_str = "<a href=\"$php_self?showall=0"
                . "&amp;startMessage=1&amp;mailbox=$box\" "
                . ">" ._("Paginate") . '</a>';
    }

    /* Put all the pieces of the paginator string together. */
    /**
     * Hairy code... But let's leave it like it is since I am not certain
     * a different approach would be any easier to read. ;)
     */
    $result = '';
    if ( $prv_str || $nxt_str ) {

        /* Compute the 'show all' string. */
        $all_str = "<a href=\"$php_self?showall=1"
                . "&amp;startMessage=1&amp;mailbox=$box\" "
                . ">" . _("Show All") . '</a>';
        $result .= '[';
        $result .= ($prv_str != '' ? $prv_str . $spc . $sep . $spc : '');
        $result .= ($nxt_str != '' ? $nxt_str : '');
        $result .= ']' . $spc ;

    }

    $result .= ($pg_str  != '' ? $spc . '['.$spc.$pg_str.']' .  $spc : '');
    $result .= ($all_str != '' ? $spc . '['.$all_str.']' . $spc . $spc : '');

    /* If the resulting string is blank, return a non-breaking space. */
    if ($result == '') {
        $result = '&nbsp;';
    }

    /* Return our final magical paginator string. */
    return ($result);
}

/**
 * FIXME: Undocumented function
 */
function truncateWithEntities($subject, $trim_at)
{
    $ent_strlen = strlen($subject);
    if (($trim_at <= 0) || ($ent_strlen <= $trim_at))
        return $subject;

    global $languages, $squirrelmail_language;

    /*
     * see if this is entities-encoded string
     * If so, Iterate through the whole string, find out
     * the real number of characters, and if more
     * than $trim_at, substr with an updated trim value.
     */
    $trim_val = $trim_at;
    $ent_offset = 0;
    $ent_loc = 0;
    while ( $ent_loc < $trim_val && (($ent_loc = strpos($subject, '&', $ent_offset)) !== false) &&
            (($ent_loc_end = strpos($subject, ';', $ent_loc+3)) !== false) ) {
        $trim_val += ($ent_loc_end-$ent_loc);
        $ent_offset  = $ent_loc_end+1;
    }
    if (($trim_val > $trim_at) && ($ent_strlen > $trim_val) && (strpos($subject,';',$trim_val) < ($trim_val + 6))) {
        $i = strpos($subject,';',$trim_val);
        if ($i) {
            $trim_val = strpos($subject,';',$trim_val);
        }
    }
    // only print '...' when we're actually dropping part of the subject
    if ($ent_strlen <= $trim_val)
        return $subject;

    if (isset($languages[$squirrelmail_language]['XTRA_CODE']) &&
        function_exists($languages[$squirrelmail_language]['XTRA_CODE'] . '_strimwidth')) {
        return call_user_func($languages[$squirrelmail_language]['XTRA_CODE'] . '_strimwidth', $subject, $trim_val);
    }

    return substr_replace($subject, '...', $trim_val);
}

/**
 * FIXME: Undocumented function
 */
function processSubject($subject, $threadlevel = 0) {
    /* Shouldn't ever happen -- caught too many times in the IMAP functions */
    if ($subject == '') {
        return _("(no subject)");
    }

    global $truncate_subject;     /* number of characters for Subject field (<= 0 for unchanged) */
    $trim_at = $truncate_subject;

    /* if this is threaded, subtract two chars per indentlevel */
    if (($threadlevel > 0) && ($threadlevel <= 10))
        $trim_at -= (2*$threadlevel);

    return truncateWithEntities($subject, $trim_at);
}


/**
 * Creates button
 *
 * @deprecated see form functions available in 1.5.1 and 1.4.3.
 * @param string $type
 * @param string $name
 * @param string $value
 * @param string $js
 * @param bool $enabled
 */
function getButton($type, $name, $value, $js = '', $enabled = TRUE) {
    $disabled = ( $enabled ? '' : 'disabled ' );
    $js = ( $js ? $js.' ' : '' );
    return '<input '.$disabled.$js.
            'type="'.$type.
            '" name="'.$name.
            '" value="'.$value .
            '" style="padding: 0px; margin: 0px" />';
}

/**
 * Puts string into cell, aligns it and adds <small> tag
 *
 * @param string $string string
 * @param string $align alignment
 */
function getSmallStringCell($string, $align) {
    return html_tag('td',
                    '<small>' . $string . ':&nbsp; </small>',
                    $align,
                    '',
                    'nowrap' );
}

/**
 * This should go in imap_mailbox.php
 * @param string $mailbox
 */
function handleAsSent($mailbox) {
    global $handleAsSent_result;

    /* First check if this is the sent or draft folder. */
    $handleAsSent_result = isSentMailbox($mailbox) || isDraftMailbox($mailbox);

    /* Then check the result of the handleAsSent hook. */
    do_hook('check_handleAsSent_result', $mailbox);

    /* And return the result. */
    return $handleAsSent_result;
}

/**
 * Process messages list form and handle the cache gracefully. If $sButton and
 * $aUid are provided as argument then you can fake a message list submit and
 * use it i.e. in read_body.php for del move next and update the cache
 *
 * @param  resource $imapConnection imap connection
 * @param  array $aMailbox (reference) cached mailbox
 * @param  string $sButton fake a submit button
 * @param  array  $aUid    fake the $msg array
 * @return string $sError error string in case of an error
 * @author Marc Groot Koerkamp
 */
function handleMessageListForm($imapConnection,&$aMailbox,$sButton='',$aUid = array()) {

    /* incoming formdata */
    $sButton = (sqgetGlobalVar('moveButton',      $sTmp, SQ_POST)) ? 'move'         : $sButton;
    $sButton = (sqgetGlobalVar('expungeButton',   $sTmp, SQ_POST)) ? 'expunge'      : $sButton;
    $sButton = (sqgetGlobalVar('attache',         $sTmp, SQ_POST)) ? 'attache'      : $sButton;
    $sButton = (sqgetGlobalVar('delete',          $sTmp, SQ_POST)) ? 'setDeleted'   : $sButton;
    $sButton = (sqgetGlobalVar('undeleteButton',  $sTmp, SQ_POST)) ? 'setDeleted'   : $sButton;
    $sButton = (sqgetGlobalVar('markRead',        $sTmp, SQ_POST)) ? 'setSeen'      : $sButton;
    $sButton = (sqgetGlobalVar('markUnread',      $sTmp, SQ_POST)) ? 'unsetSeen'    : $sButton;
    $sButton = (sqgetGlobalVar('markFlagged',     $sTmp, SQ_POST)) ? 'setFlagged'   : $sButton;
    $sButton = (sqgetGlobalVar('markUnflagged',   $sTmp, SQ_POST)) ? 'unsetFlagged' : $sButton;
    sqgetGlobalVar('targetMailbox', $targetMailbox,   SQ_POST);
    sqgetGlobalVar('bypass_trash',  $bypass_trash,    SQ_POST);
    sqgetGlobalVar('msg',           $msg,             SQ_POST);

    $sError = '';
    $mailbox = $aMailbox['NAME'];

    /* retrieve the check boxes */
    $aUid = (isset($msg) && is_array($msg)) ? array_values($msg) : $aUid;

    if (count($aUid) && $sButton != 'expunge') {
        $aUpdatedMsgs = false;
        $bExpunge = false;
        switch ($sButton) {
          case 'setDeleted':
            // check if id exists in case we come from read_body
            if (count($aUid) == 1 && is_array($aMailbox['UIDSET'][$aMailbox['SETINDEX']]) &&
                !in_array($aUid[0],$aMailbox['UIDSET'][$aMailbox['SETINDEX']])) {
                break;
            }
            $aUpdatedMsgs = sqimap_msgs_list_delete($imapConnection, $mailbox, $aUid,$bypass_trash);
            $bExpunge = true;
            break;
          case 'unsetDeleted':
          case 'setSeen':
          case 'unsetSeen':
          case 'setFlagged':
          case 'unsetFlagged':
            // get flag
            $sFlag = (substr($sButton,0,3) == 'set') ? '\\'.substr($sButton,3) : '\\'.substr($sButton,5);
            $bSet  = (substr($sButton,0,3) == 'set') ? true : false;
            $aUpdatedMsgs = sqimap_toggle_flag($imapConnection, $aUid, $sFlag, $bSet, true);
            break;
          case 'move':
            $aUpdatedMsgs = sqimap_msgs_list_move($imapConnection,$aUid,$targetMailbox);
            sqsession_register($targetMailbox,'lastTargetMailbox');
            $bExpunge = true;
            break;
          case 'attache':
            $aMsgHeaders = array();
            foreach ($aUid as $iUid) {
                $aMsgHeaders[$iUid] = $aMailbox['MSG_HEADERS'][$iUid];
            }
            if (count($aMsgHeaders)) {
                $composesession = attachSelectedMessages($imapConnection,$aMsgHeaders);
                // dirty hack, add info to $aMailbox
                $aMailbox['FORWARD_SESSION'] = $composesession;
            }
            break;
          default:
            // Hook for plugin buttons
            do_hook_function('mailbox_display_button_action', $aUid);
            break;
        }
        /**
         * Updates messages is an array containing the result of the untagged
         * fetch responses send by the imap server due to a flag change. That
         * response is parsed in a array with msg arrays by the parseFetch function
         */
        if ($aUpdatedMsgs) {
            // Update the message headers cache
            $aDeleted = array();
            foreach ($aUpdatedMsgs as $iUid => $aMsg) {
                if (isset($aMsg['FLAGS'])) {
                    /**
                     * Only update the cached headers if the header is
                     * cached.
                     */
                    if (isset($aMailbox['MSG_HEADERS'][$iUid])) {
                        $aMailbox['MSG_HEADERS'][$iUid]['FLAGS'] = $aMsg['FLAGS'];
                    }
                    /**
                     * Count the messages with the \Delete flag set so we can determine
                     * if the number of expunged messages equals the number of flagged
                     * messages for deletion.
                     */
                    if (isset($aMsg['FLAGS']['\\deleted']) && $aMsg['FLAGS']['\\deleted']) {
                        $aDeleted[] = $iUid;
                    }
                }
            }
            if ($bExpunge && $aMailbox['AUTO_EXPUNGE'] &&
                $iExpungedMessages = sqimap_mailbox_expunge($imapConnection, $aMailbox['NAME'], true))
                {
                if (count($aDeleted) != $iExpungedMessages) {
                    // there are more messages deleted permanently then we expected
                    // invalidate the cache
                    $aMailbox['UIDSET'][$aMailbox['SETINDEX']] = false;
                    $aMailbox['MSG_HEADERS'] = false;
                } else {
                    // remove expunged messages from cache
                    $aUidSet = $aMailbox['UIDSET'][$aMailbox['SETINDEX']];
                    if (is_array($aUidSet)) {
                        // create a UID => array index temp array
                        $aUidSetDummy = array_flip($aUidSet);
                        foreach ($aDeleted as $iUid) {
                            // get the id as well in case of SQM_SORT_NONE
                            if ($aMailbox['SORT'] == SQSORT_NONE) {
                                $aMailbox['ID'] = false;
                                //$iId = $aMailbox['MSG_HEADERS'][$iUid]['ID'];
                                //unset($aMailbox['ID'][$iId]);
                            }
                            // unset the UID and message header
                            unset($aUidSetDummy[$iUid]);
                            unset($aMailbox['MSG_HEADERS'][$iUid]);
                        }
                        $aMailbox['UIDSET'][$aMailbox['SETINDEX']] = array_keys($aUidSetDummy);
                    }
                }
                // update EXISTS info
                if ($iExpungedMessages) {
                    $aMailbox['EXISTS'] -= (int) $iExpungedMessages;
                }
                // Change the startMessage number if the mailbox was changed
                if (($aMailbox['PAGEOFFSET']+$iExpungedMessages-1) >= $aMailbox['EXISTS']) {
                    $aMailbox['PAGEOFFSET'] = ($aMailbox['PAGEOFFSET'] > $aMailbox['LIMIT']) ?
                        $aMailbox['PAGEOFFSET'] - $aMailbox['LIMIT'] : 1;
                }
            }
        }
    } else {
        if ($sButton == 'expunge') {
            /**
             * on expunge we do not know which messages will be deleted
             * so it's useless to try to sync the cache

             * Close the mailbox so we do not need to parse the untagged expunge
             * responses which do not contain uid info.
             * NB: Closing a mailbox is faster then expunge because the imap
             * server does not need to generate the untagged expunge responses
             */
            sqimap_run_command($imapConnection,'CLOSE',false,$result,$message);
            $aMbxResponse = sqimap_mailbox_select($imapConnection,$aMailbox['NAME']);
            // update the $aMailbox array
            $aMailbox['EXISTS'] = $aMbxResponse['EXISTS'];
            $aMailbox['UIDSET'] = false;
        } else {
            if ($sButton) {
                $sError = _("No messages were selected.");
            }
        }
    }
    return $sError;
}

function attachSelectedMessages($imapConnection,$aMsgHeaders) {
    global $username, $attachment_dir,
           $data_dir, $composesession,
           $compose_messages;

    if (!isset($compose_messages)) {
        $compose_messages = array();
        sqsession_register($compose_messages,'compose_messages');
    }

    if (!$composesession) {
        $composesession = 1;
        sqsession_register($composesession,'composesession');
    } else {
        $composesession++;
        sqsession_register($composesession,'composesession');
    }

    $hashed_attachment_dir = getHashedDir($username, $attachment_dir);

    $composeMessage = new Message();
    $rfc822_header = new Rfc822Header();
    $composeMessage->rfc822_header = $rfc822_header;
    $composeMessage->reply_rfc822_header = '';

    foreach($aMsgHeaders as $iUid => $aMsgHeader) {
        /**
         * Retrieve the full message
         */
        $body_a = sqimap_run_command($imapConnection, "FETCH $iUid RFC822", true, $response, $readmessage, TRUE);

        if ($response == 'OK') {
            $subject = (isset($aMsgHeader['SUBJECT'])) ? $aMsgHeader['SUBJECT'] : $iUid;

            array_shift($body_a);
            array_pop($body_a);
            $body = implode('', $body_a);
            $body .= "\r\n";

            $localfilename = GenerateRandomString(32, 'FILE', 7);
            $full_localfilename = "$hashed_attachment_dir/$localfilename";

            $fp = fopen( $full_localfilename, 'wb');
            fwrite ($fp, $body);
            fclose($fp);
            $composeMessage->initAttachment('message/rfc822',$subject.'.msg',
                 $full_localfilename);
        }
    }

    $compose_messages[$composesession] = $composeMessage;
    sqsession_register($compose_messages,'compose_messages');
    return $composesession;
}

// vim: et ts=4
?>