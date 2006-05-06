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
include_once(SM_PATH . 'templates/util_global.php');
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
                    '<em>'.$pageOffset.'</em>', '<em>'.$end_msg.'</em>', $iNumberOfMessages);
} else if ($pageOffset == $end_msg) {
    $msg_cnt_str = sprintf(_("Viewing Message: %s (%s total)"), '<em>'.$pageOffset.'</em>', $iNumberOfMessages);
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
 * All icon functionality is now handled through $icon_theme_path.
 * $icon_theme_path will contain the path to the user-selected theme.  If it is
 * NULL, the user and/or admin have turned off icons.
*/

// set this to an empty string to turn off extra
// highlighting of checked rows
//
//$clickedColor = '';
$clickedColor = (empty($color[16])) ? $color[2] : $color[16];

?>
<div id="message_list">
<form id="<?php echo $form_name;?>" name="<?php echo $form_name;?>" method="post" action="<?php echo $php_self;?>">
<table class="table_empty" cellspacing="0">
  <tr>
   <td>
    <table class="table_standard" cellspacing="0">
      <tr>
        <td>
          <table class="table_empty" cellspacing="0">
            <tr>
              <td class="links_paginator">
<!-- paginator and thread link string -->
                  <?php
                      /**
                       * because the template is included in the display function we refer to $oTemplate with $this
                       */
                      $paginator_str = $this->fetch('paginator.tpl');
                      echo $paginator_str . $thread_link_str ."\n"; ?>
<!-- end paginator and thread link string -->
              </td>
<!-- message count string -->
              <td class="message_count"><?php echo $msg_cnt_str; ?></td>
<!-- end message count string -->
            </tr>
          </table>
        </td>
      </tr>
<?php
    if (count($aFormElements)) {
?>
<!-- start message list form control -->
      <tr class="message_list_controls">
        <td>
          <table class="table_empty" cellspacing="0">
            <tr>
              <td class="message_control_buttons">

<?php
        foreach ($aFormElements as $key => $value) {
            switch ($value[1]) {
            case 'submit':
                if ($key != 'moveButton' && $key != 'delete' && $key != 'undeleteButton') { // add move in a different table cell
?>
                  <input type="submit" name="<?php echo $key; ?>" value="<?php echo $value[0]; ?>" class="message_control_button" />&nbsp;
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
              </td>
              <td class="message_control_delete">


<?php
        if (isset($aFormElements['delete'])) {
?>
                  <input type="submit" name="delete" value="<?php echo $aFormElements['delete'][0]; ?>" class="message_control_button" />&nbsp;
 <?php
            if (isset($aFormElements['bypass_trash'])) {
?>
                  <input type="checkbox" name="bypass_trash" /><?php echo $aFormElements['bypass_trash'][0]; ?>&nbsp;
<?php
            }
            if (isset($aFormElements['undeleteButton'])) {
?>
                  <input type="submit" name="undeleteButton" value="<?php echo $aFormElements['undeleteButton'][0]; ?>" class="message_control_button" />&nbsp;
<?php
            }
?>
              </td>
<?php
        } // if (isset($aFormElements['delete']))
        if (isset($aFormElements['moveButton'])) {
?>
              <td class="message_control_move">
                    <select name="targetMailbox">
                       <?php echo $aFormElements['targetMailbox'][0];?>
                    </select>
                  <input type="submit" name="moveButton" value="<?php echo $aFormElements['moveButton'][0]; ?>" class="message_control_button" />
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
  <tr><td class="spacer"></td></tr>
  <tr>
    <td>
      <table class="table_messageListWrapper" cellspacing="0">
        <tr>
          <td>
            <table class="table_messageList" cellspacing="0">
<!-- table header start -->
<?php
/*
 * As an FYI, Firefox on Windows seems to have an issue w/ putting wierd breaks while
 * rendering this table if we use THEAD and TH tags.  No other browser or platform has
 * this issue.  We will use TR/TD w/ another CSS class to work around this.
 */
?>
              <tr class="headerRow">
<?php
    $aWidth = calcMessageListColumnWidth($aOrder);
    foreach($aOrder as $iCol) {
?>
                    <td style="width:<?php echo $aWidth[$iCol]; ?>%">
<?php
        switch ($iCol) {
          case SQM_COL_CHECK:
              if ($javascript_on) {
                  echo '<input type="checkbox" name="toggleAll" title="'._("Toggle All").'" onclick="toggle_all(\''.$form_name."',".$fancy_index_highlite.')" />'."\n";
              } else {
                  $link = $baseurl . "&amp;startMessage=$pageOffset&amp;checkall=";
                  if (sqgetGlobalVar('checkall',$checkall,SQ_GET)) {
                      $link .= ($checkall) ? '0' : '1';
                  } else {
                      $link .= '1';
                  }
                  echo "<a href=\"$link\">"._("All").'</a>';
              }
              break;
          case SQM_COL_FROM:       echo _("From")."\n";     break;
          case SQM_COL_DATE:       echo _("Date")."\n";     break;
          case SQM_COL_SUBJ:       echo _("Subject")."\n";  break;
          case SQM_COL_FLAGS:
                echo getIcon($icon_theme_path, 'msg_new.png', '&nbsp;', _("Message Flags")) . "\n";
                break;
          case SQM_COL_SIZE:       echo  _("Size")."\n";    break;
          case SQM_COL_PRIO:
                echo getIcon($icon_theme_path, 'prio_high.png', '!', _("Priority")) . "\n";
                break;
          case SQM_COL_ATTACHMENT:
                echo getIcon($icon_theme_path, 'attach.png', '+', _("Attachment")) . "\n";
                break;
          case SQM_COL_INT_DATE:   echo _("Received")."\n"; break;
          case SQM_COL_TO:         echo _("To")."\n";       break;
          case SQM_COL_CC:         echo _("Cc")."\n";       break;
          case SQM_COL_BCC:        echo _("Bcc")."\n";      break;
          default: break;
        }
        // add the sort buttons
        if (isset($aSortSupported[$iCol])) {
            if ($sort == $aSortSupported[$iCol][0]) {
                $newsort = $aSortSupported[$iCol][1];
                $img = 'up_pointer.png';
                $text_icon = '&#8679;';  // U+21E7 UPWARDS WHITE ARROW
            } else if ($sort == $aSortSupported[$iCol][1]) {
                $newsort = 0;
                $img = 'down_pointer.png';
                $text_icon = '&#8681;'; // U+21E9 DOWNWARDS WHITE ARROW
            } else {
                $newsort = $aSortSupported[$iCol][0];
                $img = 'sort_none.png';
                $text_icon = '&#9723;'; // U+25FB WHITE MEDIUM SQUARE
            }
            /* Now that we have everything figured out, show the actual button. */
            echo " <a href=\"$baseurl&amp;startMessage=1&amp;srt=$newsort\" style=\"text-decoration:none\">" .
                 getIcon($icon_theme_path, $img, $text_icon, _("Click here to change the sorting of the message list")) . "\n" .
                 '</a>';
        }
?>
                    </td>
<?php
    }
?>
              </tr>
<!-- end table header -->

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
        $sFlags = getFlagIcon($aFlags, $icon_theme_path);

        /* add the flag string to the value index */
        $aColumns[SQM_COL_FLAGS]['value'] = $sFlags;
    }
    /**
     * Check the priority column
     */
    if (isset($aColumns[SQM_COL_PRIO])) {
        $sValue = getPriorityIcon($aColumns[SQM_COL_PRIO]['value'], $icon_theme_path);
        $aColumns[SQM_COL_PRIO]['value'] = $sValue;
    }

    /**
     * Check the attachment column
     */
    if (isset($aColumns[SQM_COL_ATTACHMENT])) {
        $sValue = getAttachmentIcon($aColumns[SQM_COL_ATTACHMENT]['value'], $icon_theme_path); 
        $aColumns[SQM_COL_ATTACHMENT]['value'] = $sValue;
    }

	$class = 'even';
    /**
     * If alternating row colors is set, adapt the CSS class
     */
    if (isset($alt_index_colors) && $alt_index_colors) {
        if (!($i % 2)) {
        	$class = 'odd';
        }

    }
    if (isset($aMsg['row']['color']))
    {
    	$bgcolor = $aMsg['row']['color'];
    	$class = 'misc'.$i;
    }
    else $bgcolor = '';

    $row_extra = '';

    // this stuff does the auto row highlighting on mouseover
    //
    if ($javascript_on && $fancy_index_highlite) {
        $row_extra .= ' onmouseover="rowOver(\''.$form_id . '_msg' . $i.'\');" onmouseout="setPointer(this, ' . $i . ', \'out\', \'' . $class . '\', \'mouse_over\', \'clicked\');" onmousedown="setPointer(this, ' . $i . ', \'click\', \'' . $class . '\', \'mouse_over\', \'clicked\');"';
    }
    // this does the auto-checking of the checkbox no matter
    // where on the row you click
    //
    $javascript_auto_click = '';
    if ($javascript_on && $fancy_index_highlite) {
        // include the form_id in order to show multiple messages lists. Otherwise id isn't unique
        $javascript_auto_click = " onMouseDown=\"row_click('$form_id"."_msg$i')\"";
    }

/*
 * Message Highlighting requires a unique CSS class declaration for proper
 * mouseover functionality.  There is no harm in doing this when the mouseover
 * functionality is disabled
 */
if ($class != 'even' && $class != 'odd')
{
?>
<style type="text/css">
<!--
.table_messageList	tr.<?php echo $class; ?>	{ background:<?php echo $bgcolor; ?> }
-->
</style>
<?php
}
?>
<tr <?php echo (empty($class) ? '' : 'class="'.$class.'" ');  echo $row_extra;?>>
<?php
    // flag style mumbo jumbo
    $sPre = $sEnd = '';
    if (isset($aColumns[SQM_COL_FLAGS])) {
        if (!in_array('seen',$aFlags) || !$aFlags['seen']) {
            $sPre = '<span class="unread">'; $sEnd = '</span>';
        }
        if (in_array('deleted',$aFlags) && $aFlags['deleted']) {
            $sPre = '<span class="deleted">' . $sPre;
            $sEnd .= '</span>';
        } else {
            if (in_array('flagged',$aFlags) && $aFlags['flagged']) {
                $sPre = '<span class="flagged">' . $sPre;
                $sEnd .= '</span>';
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
            if ($javascript_on) {
                echo '<td class="col_check"'. $javascript_auto_click. '>' ?>
                <input type="checkbox" name="<?php echo "msg[$i]";?>" id="<?php echo $form_id."_msg$i";?>" value="<?php echo $iUid;?>" <?php echo $checkbox_javascript;?> /></td>
            <?php
            } else {
                echo '<td class="col_check">';
                $checked = ($checkall) ? " checked=checked " : " ";
                echo "<input type=\"checkbox\" name=\"msg[".$i."]\" id=\"".$form_id."_msg$i\" value=\"$iUid\" $checked/></td>";
            }
            break;
          case SQM_COL_SUBJ:
            $indent = $aCol['indent'];
            $sText = "    <td class=\"col_subject\" $javascript_auto_click>";
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
                            'parentNode.parentNode, ' . $i . ', \'click\', \''. $class. '\', \'mouse_over\', \'' .
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
            $sText = "    <td class=\"col_flags\" $javascript_auto_click>";
            $sText .= "$value</td>\n";
            echo $sText;
            break;
          case SQM_COL_INT_DATE:
          case SQM_COL_DATE:
            $sText = "    <td class=\"col_date\" $javascript_auto_click>";
            $sText .= $value. "</td>\n";
            echo $sText;
            break;
          default:
            $sText = "    <td class=\"col_text\" $javascript_auto_click";
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
            $sLine = "<tr><td colspan=\"$iColCnt\" class=\"spacer\"></td></tr>\n";
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
      <tr><td class="spacer"></td></tr>
      <tr>
        <td>
          <table class="table_standard" cellspacing="0">
            <tr>
              <td>
                <table class="table_empty" cellspacing="0">
                  <tr>
                    <td class="links_paginator"><?php echo $paginator_str; ?></td>
                    <td class="message_count"><?php echo $msg_cnt_str; ?></td>
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
</div>
