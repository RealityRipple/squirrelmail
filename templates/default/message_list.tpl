<?php

/**
 * message_list.tpl
 *
 * Template for viewing a messages list
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage templates
 */

/** add required includes */
include_once(SM_PATH . 'templates/util_message_list.php');

/* retrieve the template vars */
extract($t);

do_hook('mailbox_index_before');

/**
 * Calculate string "Viewing message x to y (z total)"
 */
$msg_cnt_str = '';
if ($pageOffset < $end_msg) {
    $msg_cnt_str = sprintf(_("Viewing Messages: %s to %s (%s total)"),
                    '<b>'.$pageOffset.'</b>', '<b>'.$end_msg.'</b>', $iNumberOfMessages);
} else if ($pageOffset == $end_msg) {
    $msg_cnt_str = sprintf(_("Viewing Message: %s (%s total)"), '<b>'.$pageOffset.'</b>', $iNumberOfMessages);
}



if (!($sort & SQSORT_THREAD) && $enablesort) {
    $aSortSupported = array(SQM_COL_SUBJ =>     array(SQSORT_SUBJ_ASC     , SQSORT_SUBJ_DESC),
                            SQM_COL_DATE =>     array(SQSORT_DATE_DESC    , SQSORT_DATE_ASC),
                            SQM_COL_INT_DATE => array(SQSORT_INT_DATE_DESC, SQSORT_INT_DATE_ASC),
                            SQM_COL_FROM =>     array(SQSORT_FROM_ASC     , SQSORT_FROM_DESC),
                            SQM_COL_TO =>       array(SQSORT_TO_ASC       , SQSORT_TO_DESC),
                            SQM_COL_CC =>       array(SQSORT_CC_ASC       , SQSORT_CC_DESC),
                            SQM_COL_SIZE =>     array(SQSORT_SIZE_ASC     , SQSORT_SIZE_DESC));
} else {
    $aSortSupported = array();
}

// figure out which columns should serve as labels for checkbox:
// we try to grab the two columns before and after the checkbox,
// except the subject column, since it is the link that opens
// the message view
//
// if $javascript_on is set, then the highlighting code takes
// care of this; just skip it
//
$show_label_columns = array();
$index_order_part = array();
if (!($javascript_on && $fancy_index_highlite)) {
    $get_next_two = 0;
    $last_order_part = 0;
    $last_last_order_part = 0;
    foreach ($aOrder as $index_order_part) {
        if ($index_order_part == SQM_COL_CHECK) {
            $get_next_two = 1;
            if ($last_last_order_part != SQM_COL_SUBJ)
                $show_label_columns[] = $last_last_order_part;
            if ($last_order_part != SQM_COL_SUBJ)
                $show_label_columns[] = $last_order_part;

        } else if ($get_next_two > 0 && $get_next_two < 3 && $index_order_part != SQM_COL_SUBJ) {
            $show_label_columns[] = $index_order_part;
            $get_next_two++;
        }
        $last_last_order_part = $last_order_part;
        $last_order_part = $index_order_part;
    }
}

/**
 * Check usage of images for attachments, flags and priority
 */
$bIcons = ($use_icons && $icon_theme) ? true : false;

/**
 * Location of icon images
 */
if ($bIcons) {
    $sImageLocation = SM_PATH . 'images/themes/' . $icon_theme . '/';
}

// set this to an empty string to turn off extra
// highlighting of checked rows
//
//$clickedColor = '';
$clickedColor = (empty($color[16])) ? $color[2] : $color[16];

?>
<form id="<?php echo $form_id;?>" name="<?php echo $form_name;?>" method="post" action="<?php echo $php_self;?>">
<table border="0" width="100%" cellpadding="0" cellspacing="0">
  <tr>
    <td>
    <table width="100%" cellpadding="1"  cellspacing="0" style="border: 1px solid <?php echo $color[0]; ?>;">
      <tr>
        <td>
          <table bgcolor="<?php echo $color[4]; ?>" border="0" width="100%" cellpadding="1"  cellspacing="0">
            <tr>
              <td align="<?php echo $align['left']; ?>">
                <small>
<!-- paginator and thread link string -->
                  <?php
                      /**
                       * because the template is included in the display function we refer to $oTemplate with $this
                       */
                      $paginator_str = $this->fetch('paginator.tpl');
                      echo $paginator_str . $thread_link_str ."\n"; ?>
<!-- end paginator and thread link string -->
                </small>
              </td>
<!-- message count string -->
              <td align="right"><small><?php echo $msg_cnt_str; ?></small></td>
<!-- end message count string -->
            </tr>
          </table>
        </td>
      </tr>
<?php
    if (count($aFormElements)) {
?>
<!-- start message list form control -->
      <tr bgcolor="<?php echo $color[0]; ?>">
        <td>
          <table border="0" width="100%" cellpadding="1"  cellspacing="0">
            <tr>
              <td align="<?php echo $align['left']; ?>">
                <small>

<?php
        foreach ($aFormElements as $key => $value) {
            switch ($value[1]) {
            case 'submit':
                if ($key != 'moveButton' && $key != 'delete' && $key != 'undeleteButton') { // add move in a different table cell
?>
                  <input type="submit" name="<?php echo $key; ?>" value="<?php echo $value[0]; ?>" style="padding: 0px; margin: 0px;" />&nbsp;
<?php
                }
                break;
            case 'checkbox':
                if ($key != 'bypass_trash') {
?>
                  <input type="checkbox" name="<?php echo $key; ?>" /><?php echo $value[0]; ?>&nbsp;
<?php
                }
                break;
            case 'hidden':
                 echo '<input type="hidden" name="'.$key.'" value="'. $value[0]."\">\n";
                 break;
            default: break;
            }
        }
?>
                </small>
              </td>
              <td align="<?php echo $align['right']; ?>">


<?php
        if (isset($aFormElements['delete'])) {
?>
              <td align="<?php echo $align['right']; ?>">
                <small>
                  <input type="submit" name="delete" value="<?php echo $aFormElements['delete'][0]; ?>" style="padding: 0px; margin: 0px;" />&nbsp;
 <?php
            if (isset($aFormElements['bypass_trash'])) {
?>
                  <input type="checkbox" name="bypass_trash" /><?php echo $aFormElements['bypass_trash'][0]; ?>&nbsp;
<?php
            }
            if (isset($aFormElements['undeleteButton'])) {
?>
                  <input type="submit" name="undeleteButton" value="<?php echo $aFormElements['undeleteButton'][0]; ?>" style="padding: 0px; margin: 0px;" />&nbsp;
<?php
            }
?>
               </small>
              </td>
<?php
        } // if (isset($aFormElements['delete']))
        if (isset($aFormElements['moveButton'])) {
?>
              <td align="<?php echo $align['right']; ?>">
                <small>&nbsp;
                  <tt>
                    <select name="targetMailbox">
                       <?php echo $aFormElements['targetMailbox'][0];?>
                    </select>
                  </tt>
                  <input type="submit" name="moveButton" value="<?php echo $aFormElements['moveButton'][0]; ?>" style="padding: 0px; margin: 0px;" />
                </small>
              </td>

<?php
        } // if (isset($aFormElements['move']))
?>
            </tr>
          </table>
        </td>
      </tr>
<!-- end message list form control -->
<?php
    } // if (count($aFormElements))
?>
    </table>
<?php
    do_hook('mailbox_form_before');
?>
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
<!-- table header start -->
                  <tr>
<?php
    $aWidth = calcMessageListColumnWidth($aOrder);
    foreach($aOrder as $iCol) {

?>
                    <td align="<?php echo $align['left']; ?>" width="<?php echo $aWidth[$iCol]; ?>%" style="white-space: nowrap;">
                        <b>
<?php
        switch ($iCol) {
          case SQM_COL_CHECK:
              if ($javascript_on) {
                  echo '<input type="checkbox" name="toggleAll" title="'._("Toggle All").'" onclick="toggle_all(\''.$form_id."',".$fancy_index_highlite.",'".$clickedColor.'\');" />';
              } else {
                  $link = $baseurl . "&amp;startMessage=$pageOffset&amp;&amp;checkall=";
                  if (sqgetGlobalVar('checkall',$checkall,SQ_GET)) {
                      $link .= ($checkall) ? '0' : '1';
                  } else {
                      $link .= '1';
                  }
                  echo "<a href=\"$link\">"._("All").'</a>';
              }
              break;
          case SQM_COL_FROM:       echo _("From");     break;
          case SQM_COL_DATE:       echo _("Date");     break;
          case SQM_COL_SUBJ:       echo _("Subject");  break;
          case SQM_COL_FLAGS:
               if ($bIcons) {
                  echo '<img src="' . $sImageLocation. 'msg_new.png" border="0" height="12" width="18" alt="!" title="'. _("Message Flags") . '" />';
               } else {
                  echo  '&nbsp;';
               }
               break;
          case SQM_COL_SIZE:       echo  _("Size");    break;
          case SQM_COL_PRIO:
               if ($bIcons) {
                  echo '<img src="' . $sImageLocation. 'prio_high.png" border="0" height="10" width="5" alt="!" title="'. _("Priority") . '" />';
               } else {
                  echo  '!';
               }
               break;
          case SQM_COL_ATTACHMENT:
               if ($bIcons) {
                  echo '<img src="' . $sImageLocation. 'attach.png" border="0" height="10" width="6" alt="+" title="' . _("Attachment") . '"/>';
               } else {
                  echo  '+';
               }
               break;
          case SQM_COL_INT_DATE:   echo _("Received"); break;
          case SQM_COL_TO:         echo _("To");       break;
          case SQM_COL_CC:         echo _("Cc");       break;
          case SQM_COL_BCC:        echo _("Bcc");      break;
          default: break;
        }
        // add the sort buttons
        if (isset($aSortSupported[$iCol])) {
            if ($sort == $aSortSupported[$iCol][0]) {
               $newsort = $aSortSupported[$iCol][1];
               $img = 'up_pointer.png';
            } else if ($sort == $aSortSupported[$iCol][1]) {
               $newsort = 0;
               $img = 'down_pointer.png';
            } else {
               $newsort = $aSortSupported[$iCol][0];
               $img = 'sort_none.png';
            }
            /* Now that we have everything figured out, show the actual button. */
            echo " <a href=\"$baseurl&amp;startMessage=1&amp;srt=$newsort\">";
            echo '<img src="../images/' . $img
                . '" border="0" width="12" height="10" alt="sort" title="'
                . _("Click here to change the sorting of the message list") .'" /></a>';
        }
?>
                      </b>
                    </td>
<?php
    }
?>
                  </tr>

<!-- Message headers start -->
<?php
            $i = 0;
            $iColCnt = count($aOrder);
            $sLine = '';

            // this stuff does the auto row highlighting on mouseover
            //
            if ($javascript_on && $fancy_index_highlite) {

                $mouseoverColor = $color[5];

                // set this to an empty string to turn off extra
                // highlighting of checked rows
                //
                //$clickedColor = '';
                $clickedColor = (!empty($color[16])) ? $color[16] : $color[2];

                $checkbox_javascript = ' onclick="this.checked = !this.checked;"';
            } else {
                $checkbox_javascript = '';
            }
            foreach ($aMessages as $iUid => $aMsg) {
                echo $sLine;

/**
* Display message header row in messages list
*
*/

    $aColumns = $aMsg['columns'];


    /**
     * Check the flags and set a class var.
     */
    if (isset($aColumns[SQM_COL_FLAGS])) {
        $aFlags = $aColumns[SQM_COL_FLAGS]['value'];
        if ($bIcons) {

            $sFlags = getFlagIcon($aFlags, $sImageLocation);
        } else {
            $sFlags = getFlagText($aFlags);
        }
        /* add the flag string to the value index */
        $aColumns[SQM_COL_FLAGS]['value'] = $sFlags;
    }
    /**
     * Check the priority column
     */
    if (isset($aColumns[SQM_COL_PRIO])) {
        /* FIX ME, we should use separate templates for icons */
        if ($bIcons) {
            $sValue = '<img src="' . $sImageLocation;
            switch ($aColumns[SQM_COL_PRIO]['value']) {
                case 1:
                case 2:  $sValue .= 'prio_high.png" border="0" height="10" width="5" alt="" /> ' ; break;
                case 5:  $sValue .= 'prio_low.png" border="0" height="10" width="5" alt="" /> '  ; break;
                default: $sValue .= 'transparent.png" border="0" width="5" alt="" /> '           ; break;
            }
        } else {
            $sValue = '';
            switch ($aColumns[SQM_COL_PRIO]['value']) {
                case 1:
                case 2: $sValue .= "<font color=\"$color[1]\">!</font>"; break;
        // use downwards arrow for low priority emails
                case 5: $sValue .= "<font color=\"$color[8]\">&#8595;</font>"; break;
                default: break;
            }
        }
        $aColumns[SQM_COL_PRIO]['value'] = $sValue;
    }

    /**
     * Check the attachment column
     */
    if (isset($aColumns[SQM_COL_ATTACHMENT])) {
        /* FIX ME, we should use separate templates for icons */
        if ($bIcons) {
            $sValue = '<img src="' . $sImageLocation;
            $sValue .= ($aColumns[SQM_COL_ATTACHMENT]['value'])
                    ? 'attach.png" border="0" height="10" width="6" alt=""/>'
                    : 'transparent.png" border="0" width="6" alt="" />';
        } else {
            $sValue = ($aColumns[SQM_COL_ATTACHMENT]['value']) ? '+' : '';
        }
        $aColumns[SQM_COL_ATTACHMENT]['value'] = $sValue;
    }


    $bgcolor = $color[4];

    /**
     * If alternating row colors is set, adapt the bgcolor
     */
    if (isset($alt_index_colors) && $alt_index_colors) {
        if (!($i % 2)) {
            if (!isset($color[12])) {
                $color[12] = '#EAEAEA';
            }
            $bgcolor = $color[12];
        }

    }
    $bgcolor = (isset($aMsg['row']['color'])) ? $aMsg['row']['color']: $bgcolor;
    $class = 'msg_row';

    $row_extra = '';

    // this stuff does the auto row highlighting on mouseover
    //
    if ($javascript_on && $fancy_index_highlite) {
        $row_extra .= ' onmouseover="rowOver(\''.$form_id . "_msg$i','". $mouseoverColor . '\', \'' . $clickedColor . '\');" onmouseout="setPointer(this, ' . $i . ', \'out\', \'' . $bgcolor . '\', \'' . $mouseoverColor . '\', \'' . $clickedColor . '\');" onmousedown="setPointer(this, ' . $i . ', \'click\', \'' . $bgcolor . '\', \'' . $mouseoverColor . '\', \'' . $clickedColor . '\');"';
    }
    // this does the auto-checking of the checkbox no matter
    // where on the row you click
    //
    $javascript_auto_click = '';
    if ($javascript_on && $fancy_index_highlite) {
        // include the form_id in order to show multiple messages lists. Otherwise id isn't unique
        $javascript_auto_click = " onMouseDown=\"row_click('$form_id"."_msg$i')\"";
    }

?>
<tr class="<?php echo $class;?>" valign="top" bgcolor="<?php echo $bgcolor; ?>"<?php echo $row_extra;?>>
<?php
    // flag style mumbo jumbo
    $sPre = $sEnd = '';
    if (isset($aColumns[SQM_COL_FLAGS])) {
        if (!in_array('seen',$aFlags)) {
            $sPre = '<b>'; $sEnd = '</b>';
        }
        if (in_array('deleted',$aFlags) && $aFlags['deleted']) {
            $sPre = "<font color=\"$color[9]\">" . $sPre;
            $sEnd .= '</font>';
        } else {
            if (in_array('flagged',$aFlags) && $aFlags['flagged']) {
                $sPre = "<font color=\"$color[2]\">" . $sPre;
                $sEnd .= '</font>';
            }
        }
    }
    /**
     * Because the order of the columns and which columns to show is a user preference
     * we have to do some php coding to display the columns in the right order
     */
    foreach ($aOrder as $iCol) {
        if (in_array($iCol, $show_label_columns)) {
            $sLabelStart = '<label for="'.$form_id."_msg$i\">";
            $sLabelEnd = '</label>';
        } else {
            $sLabelStart = '';
            $sLabelEnd = '';
        }
        $aCol       = (isset($aColumns[$iCol]))    ? $aColumns[$iCol]    : array();
        $title      = (isset($aCol['title']))      ? $aCol['title']      : '';
        $link       = (isset($aCol['link']))       ? $aCol['link']       : '';
        $link_extra = (isset($aCol['link_extra'])) ? $aCol['link_extra'] : '';
        $onclick    = (isset($aCol['onclick']))    ? $aCol['onclick']    : '';
        $link       = (isset($aCol['link']))       ? $aCol['link']       : '';
        $value      = (isset($aCol['value']))      ? $aCol['value']      : '';
        $target     = (isset($aCol['target']))     ? $aCol['target']     : '';
        if ($iCol !== SQM_COL_CHECK) {
            $value = $sLabelStart.$sPre.$value.$sEnd.$sLabelEnd;
        }


        switch ($iCol) {
          case SQM_COL_CHECK:
            echo '<td align="' .$align['left'] .'"'. $javascript_auto_click. ' bgcolor="'.$bgcolor.'" style="white-space: nowrap;">' ?>
            <input type="checkbox" name="<?php echo "msg[$i]";?>" id="<?php echo $form_id."_msg$i";?>" value="<?php echo $iUid;?>" <?php echo $checkbox_javascript;?> /></td>
            <?php
            break;
          case SQM_COL_SUBJ:
            $indent = $aCol['indent'];
            $sText = "    <td class=\"col_subject\" align=\"$align[left]\" $javascript_auto_click bgcolor=\"$bgcolor\">";
            if ($align['left'] == 'left') {
                $sText .= str_repeat('&nbsp;&nbsp;',$indent);
            }
            $sText .= "<a href=\"$link\"";
            if ($target)     { $sText .= " target=\"$target\"";   }
            if ($title)      { $sText .= " title=\"$title\"";     }
            if ($onclick)    { $sText .= " onclick=\"$onclick\""; }
            if ($link_extra) { $sText .= " $link_extra";          }
            if ($javascript_on && $fancy_index_highlite) {
                  $sText .= " onmousedown=\"row_click('$form_id"."_msg$i'); setPointer(this." . (empty($bold) ? '' : 'parentNode.') .
                            'parentNode.parentNode, ' . $i . ', \'click\', \'' . $bgcolor . '\', \'' . $mouseoverColor . '\', \'' .
                             $clickedColor .'\');"';
            }
            $sText .= ">";
            $sText .= $value . '</a>';
            if ($align['left'] == 'right') {
                $sText .= str_repeat('&nbsp;&nbsp;',$indent);
            }
            echo $sText."</td>\n";
            break;
          case SQM_COL_SIZE:
          case SQM_COL_FLAGS:
            $sText = "    <td class=\"col_flags\" align=\"$align[left]\" $javascript_auto_click bgcolor=\"$bgcolor\" style=\"white-space: nowrap;\">";
            $sText .= "<small>$value</small></td>\n";
            echo $sText;
            break;
          case SQM_COL_INT_DATE:
          case SQM_COL_DATE:
            $sText = "    <td class=\"col_date\" align=\"center\" $javascript_auto_click  bgcolor=\"$bgcolor\" style=\"white-space: nowrap;\">";
            $sText .= $value. "</td>\n";
            echo $sText;
            break;
          default:
            $sText = "    <td class=\"col_text\" align=\"$align[left]\" style=\"white-space: nowrap;\" $javascript_auto_click bgcolor=\"$bgcolor\"";
            if ($link) {
                $sText .= "><a href=\"$link\"";
                if ($target) { $sText .= " target=\"$target\"";}
                if ($title)  { $sText .= " title=\"$title\""  ;}
                $sText .= ">";
            } else {
                if ($title) {$sText .= " title=\"$title\"";}
                $sText .= ">";
            }
            $sText .= $value;
            if ($link) { $sText .= '</a>';}
            echo $sText."</td>\n";
            break;
        }
    }
?>
                  </tr>
<?php
            $sLine = "<tr><td colspan=\"$iColCnt\" height=\"1\" bgcolor=\"$color[0]\"></td></tr>";
            ++$i;

/*
 * End displaying row part
 */
        }

?>
<!-- Message headers end -->
                </table>
              </td>
            </tr>
          </table>
        </td>
      </tr>
      <tr><td height="5" bgcolor="<?php echo $color[4]; ?>" colspan="1"></td></tr>
      <tr>
        <td>
          <table width="100%" cellpadding="1"  cellspacing="0" style="border: 1px solid <?php echo $color[0]; ?>;">
            <tr>
              <td>
                <table bgcolor="<?php echo $color[4]; ?>" border="0" width="100%" cellpadding="1"  cellspacing="0">
                  <tr>
                    <td align="left"><small><?php echo $paginator_str; ?></small></td>
                    <td align="right"><small><?php echo $msg_cnt_str; ?></small></td>
                  </tr>
                </table>
              </td>
            </tr>
          </table>
        </td>
      </tr>
      <tr>
        <td>
        <?php do_hook('mailbox_index_after');?>
        </td>
      </tr>
    </table>
</form>
