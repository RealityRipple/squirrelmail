<?php
/**
 * read_menubar_nav.tpl
 *
 * Template to generate the nav buttons while reading a message, e.g. "Previous",
 * "Next", "Delete & Previous", etc.  When used in conjunction with the
 * read_menubar_nav tempalte, the entire menubar is generated.
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
 *    $form_extra    - Extra elements required by the forms to delete, move or copy
 *    $compose_href  - Base URL to forward, reply, etc.  Note that a specific action
 *                     must also be given by the form or in this URL.
 *    $on_click      - Onclick event string for all buttons
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
 *     *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage templates
 */

/** add required includes **/

/** extract template variables **/
extract($t);

/*FIXME: This is a place where Marc's idea for putting all the buttons and links and other widgets into an array is sorely needed instead of hard-coding everything.  Whomever implements that, PLEASE, PLEASE look at how the preview pane plugin code is used in this same template file for the *default_advanced* set to change some links and buttons and make sure your implementation can support it (tip: it may or may not be OK to let a plugin do the modification of the widgets, since a template set can turn on the needed plugin, but that might not be the most clear way to solve said issue).*/

/** Begin template **/

if ($nav_on_top) {
    $table_class = 'top';
    $plugin_hook = 'read_body_menu_nav_top';
} else {
    $table_class = 'bottom';
    $plugin_hook = 'read_body_menu_nav_bottom';
}
?>
<div class="readMenuBar">
<table cellspacing="0" class="<?php echo $table_class; ?>">
 <tr class="nav">
  <td class="nav">
   <small>
   [
   <?php
    if (empty($prev_href)) {
        echo _("Previous");
    } else {
        ?>
   <a href="<?php echo $prev_href; ?>"><?php echo _("Previous"); ?></a>
        <?php
    }
   ?> | 
   <?php
    if (empty($up_href)) {
        # Do nothing
    } else {
        ?>
   <a href="<?php echo $up_href; ?>"><?php echo _("Up"); ?></a> |
        <?php
    }

    if (empty($next_href)) {
        echo _("Next");
    } else {
        ?>
   <a href="<?php echo $next_href; ?>"><?php echo _("Next"); ?></a>
        <?php
    }
   ?>
   ]
   &nbsp;&nbsp;&nbsp;&nbsp;
   <?php
    if (!empty($del_prev_href) || !empty($del_next_href)) {
        ?>
        [
        <?php        
        if (empty($del_prev_href)) {
            echo _("Delete &amp; Previous");
        } else {
            ?>
   <a href="<?php echo $del_prev_href; ?>"><?php echo _("Delete &amp; Previous"); ?></a>
            <?php
        }
        ?>
        | 
        <?php
        if (empty($del_next_href)) {
            echo _("Delete &amp; Next");
        } else {
            ?>
   <a href="<?php echo $del_next_href; ?>"><?php echo _("Delete &amp; Next"); ?></a>
            <?php
        }
        ?>
        ]
   &nbsp;&nbsp;&nbsp;&nbsp;
        <?php
    }
   
    if (!empty($view_msg_href)) {
        ?>
   [ <a href="<?php echo $view_msg_href; ?>"><?php echo _("View Message"); ?></a> ]
   &nbsp;&nbsp;&nbsp;&nbsp;
        <?php
    }
   ?>
   [ <a href="<?php echo $message_list_href; ?>"><?php echo _("Message List"); ?></a>
   <?php
    if (!empty($search_href)) {
        ?>
   | <a href="<?php echo $search_href; ?>"><?php echo _("Search Results"); ?></a>
        <?php
    }
   ?>
   ]
   </small>
   <?php if(!empty($plugin_output[$plugin_hook])) echo $plugin_output[$plugin_hook]; ?>
  </td>
 </tr>
</table>
</div>
