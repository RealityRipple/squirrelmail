<?php
/**
 * read_menubar_buttons.tpl
 *
 * Tempalte for displaying the action buttons, e.g. Reply, Reply All, Forward,
 * etc., while reading a message.  When combined with the read_menubar_nav template,
 * the entire menu bar is displayed.
 * 
 * The following variables are available in this template:
 *    $nav_on_top       - boolean TRUE if the navigation buttons are on top of the
 *                        action buttons generated here.
 *    $prev_href        - URL to move to the previous message.  Empty if not avilable.
 *    $up_href          - URL to move up in the message.  Empty if not available.
 *    $next_href    - URL to move to the next nessage.  Empty when N/A.
 *    $del_prev_href - URL to delete this message and move to the next one.  Empty if N/A.
 *    $del_next_href - URL to delete this message and move to the next one.  Empty if N/A.
 *    $view_msg_href - URL to go back to the main message.  Empty if N/A.
 *    $msg_list_href - URL to go to the message list.
 *    $search_href   - URL to go back to the serach results.  Empty if N/A.
 *    $form_extra    - Extra elements that will be added to the <form> tag verbatim
 *    $form_method   - The value of the <form>'s method attribute (optional, may be blank)
 *    $form_target   - The value of the <form>'s target attribute (optional, may be blank)
 *    $form_onsubmit - The value of the <form>'s onsubmit handler (optional, may be blank)
 *    $compose_href  - Base URL to forward, reply, etc.  Note that a specific action
 *                     must also be given by the form or in this URL.
 *    $button_onclick - Onclick event string for all buttons
 *    $forward_as_attachment_enabled - boolean TRUE if forwarding as attachments
 *                     has been enabled.
 *    $can_resume_draft - boolean TRUE if the "resume draft" is legitimate for
 *                     this message.
 *    $can_edit_as_new - boolean TRUE if the "reasume as new" action is legitimate
 *                     for this message
 *    $mailboxes     - array containing list of mailboxes available for move/copy action.
 *    $can_be_deleted - boolean TRUE if this message can be deleted.
 *    $can_be_moved  - boolean TRUE if this message can be moved.
 *    $cab_be_copied - boolean TRUE if this message can be copied to another folder.
 *    $move_delete_form_action - the value for the ACTION attribute of forms to
 *                     move, copy or delete a message
 *    $delete_form_extra - additional input elements needed by the DELETE form
 *    $move_form_extra - additional input elements needed by the MOVE form.
 *    $last_move_target - the last folder that a message was moved/copied to. 
 *    $accesskey_read_msg_reply        - The accesskey to be used for the Reply button
 *    $accesskey_read_msg_reply_all    - The accesskey to be used for the Reply All button
 *    $accesskey_read_msg_forward      - The accesskey to be used for the Forward button
 *    $accesskey_read_msg_as_attach    - The accesskey to be used for the As Attachment checkbox
 *    $accesskey_read_msg_delete       - The accesskey to be used for the Delete button
 *    $accesskey_read_msg_bypass_trash - The accesskey to be used for the Bypass Trash checkbox
 *    $accesskey_read_msg_move_to      - The accesskey to be used for the folder select list
 *    $accesskey_read_msg_move         - The accesskey to be used for the Move button
 *    $accesskey_read_msg_copy         - The accesskey to be used for the Copy button
 *    
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage templates
 */

/** add required includes **/

/** extract template variables **/
extract($t);


/*FIXME: This is a place where Marc's idea for putting all the buttons and links and other widgets into an array is sorely needed instead of hard-coding everything.  Whomever implements that, PLEASE, PLEASE look at how the preview pane plugin code is used here to change some links and buttons and make sure your implementation can support it (tip: it may or may not be OK to let a plugin do the modification of the widgets, since a template set can turn on the needed plugin, but that might not be the most clear way to solve said issue).*/

/** preview pane prep */
global $data_dir, $username, $base_uri;
$pp_refresh_message_list = getPref($data_dir, $username, 'pp_refresh_message_list', 1);
$use_previewPane = getPref($data_dir, $username, 'use_previewPane', 0);
$show_preview_pane = checkForJavascript() && $use_previewPane;
$empty_frame_uri = $base_uri . 'plugins/preview_pane/empty_frame.php';


/** Begin template **/
if ($nav_on_top) {
    $table_class = 'bottom';
    $plugin_hook = 'read_body_menu_buttons_top';
} else {
    $table_class = 'top';
    $plugin_hook = 'read_body_menu_buttons_bottom';
}
?>
<div class="readMenuBar">
<table class="<?php echo $table_class; ?>" cellspacing="0">
 <tr class="buttons">
  <td class="buttons">
   <form name="composeForm" action="<?php
                  echo $compose_href . '" '
                     . (!empty($form_method) ? 'method="' . $form_method . '" ' : '')
                     . (!empty($form_target) ? 'target="' . $form_target . '" ' : '')
                     . (!empty($form_onsubmit) ? 'onsubmit="' . $form_onsubmit . '" ' : '')
                     . $form_extra; ?> >
   <small>
    <?php
        if ($can_resume_draft) {
            ?>
    <input type="submit" name="smaction_draft" value="<?php echo _("Resume Draft"); ?>" onclick="<?php echo $button_onclick; ?>" />&nbsp;
            <?php
        } elseif ($can_edit_as_new) {
            ?>
    <input type="submit" name="smaction_edit_new" value="<?php echo _("Edit Message as New"); ?>" onclick="<?php echo $button_onclick; ?>" />&nbsp;
            <?php
        }
    ?>
    <input type="submit" name="smaction_reply" <?php if ($accesskey_read_msg_reply != 'NONE') echo 'accesskey="' . $accesskey_read_msg_reply . '" '; ?>value="<?php echo _("Reply"); ?>" onclick="<?php echo $button_onclick; ?>" />&nbsp;
    <input type="submit" name="smaction_reply_all" <?php if ($accesskey_read_msg_reply_all != 'NONE') echo 'accesskey="' . $accesskey_read_msg_reply_all . '" '; ?>value="<?php echo _("Reply All"); ?>" onclick="<?php echo $button_onclick; ?>" />
    &nbsp;&nbsp;|&nbsp;&nbsp;
    <input type="submit" name="smaction_forward" <?php if ($accesskey_read_msg_forward != 'NONE') echo 'accesskey="' . $accesskey_read_msg_forward . '" '; ?>value="<?php echo _("Forward"); ?>" onclick="<?php echo $button_onclick; ?>" />
    <?php
    if ($forward_as_attachment_enabled) {
        ?>
    <input type="checkbox" name="smaction_attache" id="smaction_attache" <?php if ($accesskey_read_msg_as_attach != 'NONE') echo 'accesskey="' . $accesskey_read_msg_as_attach . '" '; ?>/>
    <label for="smaction_attache"><?php echo _("As Attachment"); ?></label>
        <?php
    }
    ?>
   </small>
   </form>
    &nbsp;&nbsp;|&nbsp;&nbsp;
    <?php
    if ($can_be_deleted) {
        ?>
    <form name="deleteMessageForm" action="<?php echo $move_delete_form_action; ?>" method="post">
     <input type="hidden" name="smtoken" value="<?php echo sm_generate_security_token(); ?>" />
     <?php echo $delete_form_extra; ?>
     <small>
     <input type="submit" name="delete" <?php if ($accesskey_read_msg_delete != 'NONE') echo 'accesskey="' . $accesskey_read_msg_delete . '" '; ?>value="<?php 

echo _("Delete") .'"';

/** if preview pane turned on with "always refresh message list",
    refresh message list frame too, but only if we are in the bottom frame! */
if ($show_preview_pane && $pp_refresh_message_list)
   echo ' onclick="if (self.name == \'bottom\') { refresh_message_list(); } "';

echo ' />'; ?>

     <input type="checkbox" name="bypass_trash" id="bypass_trash" <?php if ($accesskey_read_msg_bypass_trash != 'NONE') echo 'accesskey="' . $accesskey_read_msg_bypass_trash . '" '; ?>/><label for="bypass_trash"><?php echo _("Bypass Trash"); ?></label>
     </small>
    </form>
        <?php
    }
    ?>
   <?php if(!empty($plugin_output[$plugin_hook])) echo $plugin_output[$plugin_hook]; ?>
  </td>
  <td class="move">
   <?php
    if ($can_be_moved) {
        ?>
    <form name="moveMessageForm" action="<?php echo $move_delete_form_action; ?>" method="post">
     <input type="hidden" name="smtoken" value="<?php echo sm_generate_security_token(); ?>" />
     <?php echo $move_form_extra; ?>
     <small>
     <?php echo _("Move To"); ?>:
     <select <?php if ($accesskey_read_msg_move_to != 'NONE') echo 'accesskey="' . $accesskey_read_msg_move_to . '" '; ?>name="targetMailbox">
     <?php
        foreach ($mailboxes as $value=>$option) {
            echo '<option value="'. $value .'"' . ($value==$last_move_target ? ' selected="selected"' : '').'>' . $option .'</option>'."\n";
        }
     ?>
     </select>
     <input type="submit" name="moveButton" <?php if ($accesskey_read_msg_move != 'NONE') echo 'accesskey="' . $accesskey_read_msg_move . '" '; ?>value="<?php 

echo _("Move") . '"'; 

/** if preview pane turned on with "always refresh message list",
    refresh message list frame too, but only if we are in the bottom frame! */
if ($show_preview_pane && $pp_refresh_message_list)
   echo ' onclick="if (self.name == \'bottom\') { refresh_message_list(); } "';

echo ' />';


        if ($can_be_copied) {
            ?>
     <input type="submit" name="copyButton" <?php if ($accesskey_read_msg_copy != 'NONE') echo 'accesskey="' . $accesskey_read_msg_copy . '" '; ?>value="<?php echo _("Copy"); ?>" />
            <?php
        }
     ?>
     </small>
    </form>
        <?php
    }
   ?>
  </td>
 </tr>
</table>
</div>
