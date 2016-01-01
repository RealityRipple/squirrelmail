<?php

/**
 * message_list_controls.tpl
 *
 * Template for the form control widgets on the message list page
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
 *    $sm_attribute_str
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
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage templates
 */


/* retrieve the template vars */
extract($t);


    if (count($aFormElements)) {
?>
          <table class="table_empty" cellspacing="0">
            <tr>
              <td class="message_control_buttons">

<?php
        foreach ($aFormElements as $widget_name => $widget_attrs) {
            switch ($widget_attrs['type']) {
            case 'submit':
                if ($widget_name != 'moveButton' && $widget_name != 'copyButton' && $widget_name != 'delete' && $widget_name != 'undeleteButton') { // add these later in another table cell
                    echo '<input type="submit" name="' . $widget_name . '" value="' . $widget_attrs['value'] . '" class="message_control_button"';
                    if (isset($widget_attrs['accesskey'])
                      && $widget_attrs['accesskey'] != 'NONE')
                        echo ' accesskey="' . $widget_attrs['accesskey'] . '"';
                    if (!empty($widget_attrs['extra_attrs'])) {
                        foreach ($widget_attrs['extra_attrs'] as $attr => $val) {
                            echo ' ' . $attr . '="' . $val . '"';
                        }
                    }
                    echo ' />&nbsp;';
                }
                break;
            case 'checkbox':
                if ($widget_name != 'bypass_trash') {
                    echo '<input type="checkbox" name="' . $widget_name . '" id="' . $widget_name . '"';
                    if ($widget_attrs['accesskey'] != 'NONE')
                        echo ' accesskey="' . $widget_attrs['accesskey'] . '"';
                    if (!empty($widget_attrs['extra_attrs'])) {
                        foreach ($widget_attrs['extra_attrs'] as $attr => $val) {
                            echo ' ' . $attr . '="' . $val . '"';
                        }
                    }
                    echo ' /><label for="' . $widget_name . '">' . $widget_attrs['value'] . '</label>&nbsp;';
                }
                break;
            case 'hidden':
                echo '<input type="hidden" name="'.$widget_name.'" value="'. $widget_attrs['value']."\" />";
                break;
            default: break;
            }
        }
?>
              </td>
              <td class="message_control_delete">
<?php
        if (isset($aFormElements['delete'])) {
            echo '<input type="submit" name="delete" value="' . $aFormElements['delete']['value'] . '" class="message_control_button" ' . ($aFormElements['delete']['accesskey'] != 'NONE' ? 'accesskey="' . $aFormElements['delete']['accesskey'] . '" ' : '') . '/>&nbsp;';
            if (isset($aFormElements['bypass_trash'])) {
                echo '<input type="checkbox" name="bypass_trash" id="bypass_trash" ' . ($aFormElements['bypass_trash']['accesskey'] != 'NONE' ? 'accesskey="' . $aFormElements['bypass_trash']['accesskey'] . '" ' : '') . '/><label for="bypass_trash">' . $aFormElements['bypass_trash']['value'] . '</label>&nbsp;';
            }
            if (isset($aFormElements['undeleteButton'])) {
                echo '<input type="submit" name="undeleteButton" value="' . $aFormElements['undeleteButton']['value'] . '" class="message_control_button" ' . ($aFormElements['undeleteButton']['accesskey'] != 'NONE' ? 'accesskey="' . $aFormElements['undeleteButton']['accesskey'] . '" ' : '') . '/>&nbsp;';
            }
?>

              </td>

<?php
        } // if (isset($aFormElements['delete']))
        if (isset($aFormElements['moveButton']) || isset($aFormElements['copyButton'])) {
?>
              <td class="message_control_move">
                    <select name="targetMailbox"<?php if ($aFormElements['targetMailbox']['accesskey'] != 'NONE') echo ' accesskey="' . $aFormElements['targetMailbox']['accesskey'] . '"'; ?>>
                       <?php echo $aFormElements['targetMailbox']['options_list'];?>
                    </select>
<?php
            if (isset($aFormElements['moveButton'])) {
                echo '<input type="submit" name="moveButton" value="' . $aFormElements['moveButton']['value'] . '" class="message_control_button" ' . ($aFormElements['moveButton']['accesskey'] != 'NONE' ? 'accesskey="' . $aFormElements['moveButton']['accesskey'] . '" ' : '') . '/>';
            }
            if (isset($aFormElements['copyButton'])) {
                echo '<input type="submit" name="copyButton" value="' . $aFormElements['copyButton']['value'] . '" class="message_control_button" ' . ($aFormElements['copyButton']['accesskey'] != 'NONE' ? 'accesskey="' . $aFormElements['copyButton']['accesskey'] . '" ' : '') . '/>';
            }
?>

              </td>

<?php
        } // if (isset($aFormElements['moveButton']) || isset($aFormElements['copyButton']))
?>
            </tr>
          </table>
<?php 
    } // if (count($aFormElements))

