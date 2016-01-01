<?php

/**
 * message_list.tpl
 *
 * Template for viewing a messages list
 *
 * The following variables are available in this template:
//FIXME: need to clean (and document) this list, it is just a dump of the array keys of $t
 *    $sTemplateID
 *    $icon_theme_path
 *    $javascript_on
 *    $delayed_errors
 *    $frames
 *    $lang
 *    $page_title
 *    $header_tags
 *    $plugin_output
 *    $header_sent
 *    $body_tag_js
 *    $shortBoxName
 *    $provider_link
 *    $frame_top
 *    $urlMailbox
 *    $startMessage
 *    $hide_sm_attributions
 *    $uri
 *    $text
 *    $onclick
 *    $class
 *    $id
 *    $target
 *    $color
 *    $form_name
 *    $form_id
 *    $page_selector
 *    $page_selector_max
 *    $messagesPerPage
 *    $showall
 *    $end_msg
 *    $align
 *    $iNumberOfMessages
 *    $aOrder
 *    $aFormElements
 *    $sort
 *    $pageOffset
 *    $baseurl
 *    $aMessages
 *    $trash_folder
 *    $sent_folder
 *    $draft_folder
 *    $thread_link_uri
 *    $thread_name
 *    $php_self
 *    $mailbox
 *    $enablesort
 *    $icon_theme
 *    $use_icons
 *    $alt_index_colors
 *    $fancy_index_highlite
 *    $aSortSupported
 *    $show_label_columns
 *    $compact_paginator
 *    $aErrors
 *    $checkall
 *    $preselected
 *    $show_personal_names boolean When turned on, all email
 *                                 address fields should display
 *                                 the personal name and use the
 *                                 email address as a tool tip;
 *                                 When turned off, this logic
 *                                 should be inverted
 *    $accesskey_mailbox_toggle_selected The access key to use for the toggle all checkbox
 *    $accesskey_mailbox_thread The access key to use for the Thread/Unthread links
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage templates
 */


/** add required includes */
include_once(SM_PATH . 'functions/template/message_list_util.php');


/* retrieve the template vars */
extract($t);


if (!empty($plugin_output['mailbox_index_before'])) echo $plugin_output['mailbox_index_before'];


/**
 * Calculate string "Viewing message x to y (z total)"
 */
$msg_cnt_str = '';
if ($pageOffset < $end_msg) {
    $msg_cnt_str = sprintf(_("Viewing Messages: %s to %s (%s total)"),
                           '<em>' . $pageOffset . '</em>',
                           '<em>' . $end_msg . '</em>',
                           $iNumberOfMessages);
} else if ($pageOffset == $end_msg) {
    $msg_cnt_str = sprintf(_("Viewing Message: %s (%s total)"),
                           '<em>' . $pageOffset . '</em>',
                           $iNumberOfMessages);
}


/**
 * All icon functionality is now handled through $icon_theme_path.
 * $icon_theme_path will contain the path to the user-selected theme.  If it is
 * NULL, the user and/or admin have turned off icons.
 */


?>
<div id="message_list">
<form id="<?php echo $form_name;?>" name="<?php echo $form_name;?>" method="post" action="<?php echo $php_self;?>">
<input type="hidden" name="smtoken" value="<?php echo sm_generate_security_token(); ?>" />
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
                       * The following line gets the output from a separate 
                       * template altogether (called "paginator.tpl").
                       * $this is the Template class object.
                       */
                      $paginator_str = $this->fetch('paginator.tpl');
                      echo $paginator_str . '<small>[<a href="' . $thread_link_uri
                                          . ($accesskey_mailbox_thread != 'NONE'
                                          ? '" accesskey="' . $accesskey_mailbox_thread . '">'
                                          : '">')
                                          . $thread_name . '</a>]</small>&nbsp;&nbsp;';
                      if (!empty($plugin_output['mailbox_paginator_after'])) echo $plugin_output['mailbox_paginator_after'];
                  ?>
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
                  <?php
                     /**
                       * The following line gets the output from a separate
                       * template altogether (called "message_list_controls.tpl").
                       * $this is the Template class object.
                       */
                      $message_list_controls = $this->fetch('message_list_controls.tpl');
                      echo $message_list_controls ."\n"; ?>
        </td>
      </tr>
<!-- end message list form control -->
<?php
    } // if (count($aFormElements))
?>
    </table>
<?php if (!empty($plugin_output['mailbox_form_before'])) echo $plugin_output['mailbox_form_before']; ?>
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
                  $checked = ($checkall ? ' checked="checked" ' : '');
                  $accesskey = ($accesskey_mailbox_toggle_selected == 'NONE' ? ''
                                : ' accesskey="' . $accesskey_mailbox_toggle_selected . '" ');
                  echo '<input type="checkbox" name="toggleAll" id="toggleAll" title="'
                     . _("Toggle All") . '" onclick="toggle_all(\''
                     . $form_name . '\', \'msg\', ' . $fancy_index_highlite
                     . '); return false;" ' . $checked . $accesskey . '/>' . "\n";
              } else {
                  $link = $baseurl 
                        . "&amp;startMessage=$pageOffset&amp;checkall=" 
                        . ($checkall ? '0' : '1');
                  echo "<a href=\"$link\">" . _("All") . '</a>';
              }
              break;
          case SQM_COL_FROM:       
              echo '<label for="toggleAll">' . _("From") . "</label>\n";
              break;
          case SQM_COL_DATE:       echo _("Date") . "\n";     break;
          case SQM_COL_SUBJ:       echo _("Subject") . "\n";  break;
          case SQM_COL_FLAGS:
                echo getIcon($icon_theme_path, 'msg_new.png', '&nbsp;', _("Message Flags")) . "\n";
                break;
          case SQM_COL_SIZE:       echo  _("Size") . "\n";    break;
          case SQM_COL_PRIO:
                echo getIcon($icon_theme_path, 'prio_high.png', '!', _("Priority")) . "\n";
                break;
          case SQM_COL_ATTACHMENT:
                echo getIcon($icon_theme_path, 'attach.png', '+', _("Attachment")) . "\n";
                break;
          case SQM_COL_INT_DATE:   echo _("Received") . "\n"; break;
          case SQM_COL_TO:
              echo '<label for="toggleAll">' . _("To") . "</label>\n";
              break;
          case SQM_COL_CC:         echo _("Cc") . "\n";       break;
          case SQM_COL_BCC:        echo _("Bcc") . "\n";      break;
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
            if (!empty($plugin_output['checkbox_javascript_onclick'])) $checkbox_javascript_onclick = $plugin_output['checkbox_javascript_onclick'];
            else $checkbox_javascript_onclick = '';

            if ($javascript_on && $fancy_index_highlite) {
                $checkbox_javascript = ' onclick="this.checked = !this.checked; ' . $checkbox_javascript_onclick . '"';
            } else if (!empty($checkbox_javascript_onclick)) {
                $checkbox_javascript = ' onclick="' . $checkbox_javascript_onclick . '"';
            } else {
                $checkbox_javascript = '';
            }


            /**
              * main message iteration loop
              */
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

    $class = (($checkall || in_array($iUid, $preselected)) && $javascript_on && $fancy_index_highlite ? 'clicked_even' : 'even');
    $non_clicked_class = 'even';

    /**
     * If alternating row colors is set, adapt the CSS class
     */
    if (isset($alt_index_colors) && $alt_index_colors) {
        if (!($i % 2)) {
            $class = (($checkall || in_array($iUid, $preselected)) && $javascript_on && $fancy_index_highlite ? 'clicked_odd' : 'odd');
            $non_clicked_class = 'odd';
        }

    }

    /**
     * Message Highlighting Functionality
     */
    if (isset($aMsg['row']['color']))
    {
    	if (($checkall || in_array($iUid, $preselected)) && $javascript_on && $fancy_index_highlite) {
//FIXME: would be best not to use $color directly here; want to move this to be a CSS style-defined value only, but the problem is that this CSS class is being defined on the fly right here
    	    $bgcolor = $color[16];
    	    $class = 'clicked_misc'.$i;
        } else {
            $bgcolor = $aMsg['row']['color'];
    	    $class = 'misc'.$i;
        }
        $non_clicked_class = 'misc'.$i;
        $non_clicked_bgcolor = $aMsg['row']['color'];
    } 
    else 
    {
        $bgcolor = '';
        $non_clicked_bgcolor = '';
    }

    $row_extra = '';

    // this stuff does the auto row highlighting on mouseover
    //
    if ($javascript_on && $fancy_index_highlite) {
        $row_extra = ' onmouseover="rowOver(\''.$form_id . '_msg' . $i.'\');" onmouseout="setPointer(this, ' . $i . ', \'out\', \'' . $non_clicked_class . '\', \'mouse_over\', \'clicked\');" onmousedown="setPointer(this, ' . $i . ', \'click\', \'' . $non_clicked_class . '\', \'mouse_over\', \'clicked\');"';
    }
    // this does the auto-checking of the checkbox no matter
    // where on the row you click
    //
    $javascript_auto_click = '';
    $row_click_extra = '';
    if (!empty($plugin_output['row_click_extra'])) $row_click_extra = $plugin_output['row_click_extra'];
    if ($javascript_on && $fancy_index_highlite) {
        // include the form_id in order to show multiple messages lists. Otherwise id isn't unique
        $javascript_auto_click = " onmousedown=\"row_click('$form_id"."_msg$i', event, '$form_name', 'msg[' + $i + ']', '$row_click_extra')\"";
    }


/*
 * Message Highlighting requires a unique CSS class declaration for proper
 * mouseover functionality.  There is no harm in doing this when the mouseover
 * functionality is disabled
 */
if ($class != 'even' && $class != 'odd' 
 && $class != 'clicked_even' && $class != 'clicked_odd')
{
?>
<style type="text/css">
<!--
.table_messageList	tr.<?php echo $class; ?>	{ background:<?php echo $bgcolor; ?> }
-->
</style>
<?php
}
if ($non_clicked_class != 'even' && $non_clicked_class != 'odd' 
 && $non_clicked_class != 'clicked_even' && $non_clicked_class != 'clicked_odd')
{
?>
<style type="text/css">
<!--
.table_messageList	tr.<?php echo $non_clicked_class; ?>	{ background:<?php echo $non_clicked_bgcolor; ?> }
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
        $value      = (isset($aCol['value']))      ? $aCol['value']      : '';
        $target     = (isset($aCol['target']))     ? $aCol['target']     : '';
        if (!$show_personal_names
         && !empty($title)
         && ($iCol == SQM_COL_FROM
          || $iCol == SQM_COL_TO
          || $iCol == SQM_COL_CC
          || $iCol == SQM_COL_BCC)) {
            // swap title and value
            $tmp = $title;
            $title = $value;
            $value = $tmp;
        }
        if ($iCol !== SQM_COL_CHECK) {
            $value = $sLabelStart.$sPre.$value.$sEnd.$sLabelEnd;
        }


        switch ($iCol) {
          case SQM_COL_CHECK:
            $checked = (($checkall || in_array($iUid, $preselected)) ? ' checked="checked" ' : '');
            if ($javascript_on) {
                echo '<td class="col_check"'. $javascript_auto_click. '>' ?>
                <input type="checkbox" name="<?php echo "msg[$i]";?>" id="<?php echo $form_id."_msg$i";?>" value="<?php echo $iUid;?>" <?php echo $checkbox_javascript . $checked;?> /></td>
            <?php
            } else {
                echo '<td class="col_check">';
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
                  $sText .= " onmousedown=\"row_click('$form_id"."_msg$i', event, '$form_name', 'msg[' + $i + ']', '$row_click_extra'); setPointer(this." . (empty($bold) ? '' : 'parentNode.') .
                            'parentNode.parentNode, ' . $i . ', \'click\', \''. $non_clicked_class. '\', \'mouse_over\', \'clicked\');"';
            }
            $sText .= ">"
                   . $value . '</a>';
            if ($align['left'] == 'right') {
                $sText .= str_repeat('&nbsp;&nbsp;',$indent);
            }
            echo $sText."</td>\n";
            break;
          case SQM_COL_SIZE:
          case SQM_COL_FLAGS:
            $sText = "    <td class=\"col_flags\" $javascript_auto_click>"
                   . "$value</td>\n";
            echo $sText;
            break;
          case SQM_COL_INT_DATE:
          case SQM_COL_DATE:
            $sText = "    <td class=\"col_date\" $javascript_auto_click";
            if ($title) {$sText .= " title=\"$title\"";}
            $sText .= ">" . $value. "</td>\n";
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

            echo '</tr>';
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
                    <td class="links_paginator"><?php 
                     /**
                       * The following line gets the output from a separate 
                       * template altogether (called "paginator.tpl").
                       * $this is the Template class object.
                       */
                      $paginator_str = $this->fetch('paginator.tpl');
                      echo $paginator_str; 
                      if (!empty($plugin_output['mailbox_paginator_after'])) echo $plugin_output['mailbox_paginator_after'];
                    ?></td>
                    <td class="message_count"><?php echo $msg_cnt_str; ?></td>
                  </tr>
                </table>
              </td>
            </tr>
          </table>
        </td>
      </tr>
      <tr>
        <td align="right">
<?php if (!empty($plugin_output['mailbox_index_after'])) echo $plugin_output['mailbox_index_after']; ?>
        </td>
      </tr>
    </table>
</form>
</div>

<?php if (!$hide_sm_attributions): ?>
<p class="sqm_squirrelcopyright">&copy; <?php echo SM_COPYRIGHT ?> The SquirrelMail Project Team - <a href="about.php">About SquirrelMail</a></p>
<?php endif; ?>
