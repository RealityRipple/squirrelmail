<?php
/**
 * left_main.tpl
 *
 * Basic template to the left main window.  The following variables are 
 * avilable in this template:
 *      $clock           - formatted string containing last refresh
 *      $mailbox_listing - string containing HTML to display default mailbox tree
 *      $location_of_bar - string "left" or "right" indicating where the frame
 *                         is located.  Currently only used in
 *                         left_main_advanced.tpl
 *      $left_size       - width of left column in pixels.  Currently only used
 *                         in left_main_advanced.tpl
 *      $imapConnection  - IMAP connection handle.  Needed to allow plugins to 
 *                         read the mailbox.
 *      $icon_theme_path - Path to the desired icon theme.  If no icon theme has
 *                         been chosen, this will be the template directory.  If
 *                         the user has disabled icons, this will be NULL.
 *
 *      $unread_notification_enabled - Boolean TRUE if the user wants to see unread 
 *                             message count on mailboxes
 *      $unread_notification_cummulative - Boolean TRUE if the user has enabled
 *                             cummulative message counts.
 *      $unread_notification_allFolders - Boolean TRUE if the user wants to see
 *                             unread message count on ALL folders or just the
 *                             mailbox.
 *      $unread_notification_displayTotal - Boolean TRUE if the user wants to
 *                             see the total number of messages in addition to
 *                             the unread message count.
 *      $collapsable_folders_enabled - Boolean TRUE if the user has enabled collapsable
 *                             folders.
 *      $use_special_folder_color - Boolean TRUE if the use has chosen to tag
 *                             "Special" folders in a different color.
 *      $message_recycling_enabled - Boolean TRUE if messages that get deleted go to
 *                             the Trash folder.  FALSE if they are permanently
 *                             deleted.
 *      $trash_folder_name   - Name of the Trash folder.
 * 
 *      $mailboxes       - Associative array of current mailbox structure.
 *                         Provided so template authors know what they have to
 *                         work with when building a custom mailbox tree.
 *                         Array contains the following elements:
 *          $a['MailboxName']   = String containing the name of the mailbox
 *          $a['MailboxFullName'] = String containing full IMAP name of mailbox
 *          $a['MessageCount']  = integer of all messages in the mailbox
 *          $a['UnreadCount']   = integer of unseen message in the mailbox
 *          $a['ViewLink']      = array containing elements needed to view the
 *                                mailbox.  Elements are:
 *                                  'Target' = target frame for link
 *                                  'URL'    = target URL for link
 *          $a['IsRecent']      = boolean TRUE if the mailbox is tagged "recent"
 *          $a['IsSpecial']     = boolean TRUE if the mailbox is tagged "special"
 *          $a['IsRoot']        = boolean TRUE if the mailbox is the root mailbox
 *          $a['IsNoSelect']    = boolean TRUE if the mailbox is tagged "noselect"
 *          $a['IsCollapsed']   = boolean TRUE if the mailbox is currently collapsed
 *          $a['CollapseLink']  = array containg elements needed to expand/collapse
 *                                the mailbox.  Elements are:
 *                                  'Target' = target frame for link
 *                                  'URL'    = target URL for link
 *                                  'Icon'   = the icon to use, based on user prefs
 *          $a['ChildBoxes']    = array containing this same data structure for
 *                                each child folder/mailbox of the current
 *                                mailbox. 
 *          $a['CummulativeMessageCount']   = integer of total messages in all
 *                                            folders in this mailbox, exlcuding
 *                                            trash folders.
 *          $a['CummulativeUnreadCount']    = integer of total unseen messages
 *                                            in all folders in this mailbox,
 *                                            excluding trash folders.
 *  
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage templates
 */

// include required files
include_once(SM_PATH . 'templates/util_global.php');

/*
 * Recursively parse the mailbox structure to build the navigation tree.
 * 
 * @since 1.5.2
 */
function buildMailboxTree ($box, $settings, $indent_factor=0) {
    // stop condition
    if (empty($box)) {
        return '';
    }

#    echo '<b>'.$box['MailboxName'].':</b> '.dump_array($box).'<hr>';
    $pre = '<span style="white-space: nowrap;">';
    $end = '';
    $indent = str_repeat('&nbsp;&nbsp;',$indent_factor);

    // Get unseeen/total message info if needed
    $unseen_str = '';
    if ($settings['unreadNotificationEnabled'])   {
        // We only display the unread count if we on the Inbox or we are told
        // to display it on all folders. 
        if ( $settings['unreadNotificationAllFolders'] || 
             (!$settings['unreadNotifictionAllFolders'] && strtolower($box['MailboxFullName'])=='inbox') 
           )  {
            $unseen_str = $settings['unreadNotificationCummulative'] ? 
                            $box['CummulativeUnreadCount'] : 
                            $box['UnreadCount'];
            
            // Add the total messages if desired
            if ($settings['unreadNotificationDisplayTotal'])    {
                $unseen_str .= '/' . ($settings['unreadNotificationCummulative'] ?
                                        $box['CummulativeMessageCount'] :
                                        $box['MessageCount']);
            }
            
            $unseen_str = '<span class="'. 
                          ($box['IsRecent'] ? 'leftrecent' : 'leftunseen') .
                          '">' . $unseen_str .
                          '</span>';
        }
    }

    /*
     * If the box has any children, and collapsable folders have been enabled
     * we need to output the expand/collapse link.
     */
    if (sizeof($box['ChildBoxes'])>0 && $settings['collapsableFoldersEnabled'])    {
        $link = $indent . 
                '<a href="'.$box['CollapseLink']['URL'].'" ' .
                'target="'.$box['CollapseLink']['Target'].'" ' .
                'style="text-decoration:none" ' .
#                'alt="'.$box['CollapseLink']['Alt'].'" ' .
#                'title="'.$box['CollapseLink']['Alt'].'">' .
                '>' .   
                $box['CollapseLink']['Icon'] . 
                '</a>';   
        $pre .= $link;   
    } else { 
        $pre .= $indent . '&nbsp;&nbsp;';
    }
      
    /*
     * The Trash folder should only be displayed if message recycling has
     * been enabled, i.e. when deleted is a message moved to the trash or
     * deleted forever?
     */
    $view_link = '<a href="'.$box['ViewLink']['URL'].'" ' .
                 'target="'.$box['ViewLink']['Target'].'" ' .
                 'style="text-decoration:none">';
     
    if ($settings['messageRecyclingEnabled'] && $box['MailboxFullName'] == $settings['trashFolderName']) {
        $pre .= $view_link;

        // Boxes with unread messages should be emphasized
        if ($box['UnreadCount'] > 0) {
            $pre .= '<em>';
            $end .= '</em>';
        }
        $end .= '</a>';
        
        // Print unread info
        if ($box['UnreadCount'] > 0) {
            if (!empty($unseen_str)) {
                $end .= '&nbsp;<small>('.$unseen_str.')</small>';
            }
            $end .= "\n<small>" .
                    '&nbsp;&nbsp;[<a href="empty_trash.php">'. _("Purge").'</a>]' .
                    '</small>';
        }
    } else {
        // Add a few other things for all other folders...
        if (!$box['IsNoSelect']) {
            $pre .= $view_link;
    
            // Boxes with unread messages should be emphasized
            if ($box['UnreadCount'] > 0) {
                $pre .= '<em>';
                $end .= '</em>';
            }
            $end .= '</a>';
        }
        
        // Display unread info...
        if (!empty($unseen_str)) {
            $end .= '&nbsp;<small>('.$unseen_str.')</small>';
        }
    }
     
    $span = '';
    $spanend = '';
    if ($settings['useSpecialFolderColor'] && $box['IsSpecial']) {
        $span = '<span class="leftspecial">';
        $spanend = '</span>';
    } elseif ( $box['IsNoSelect'] ) {
        $span = '<span class="leftnoselect">';
        $spanend = '</span>';
    }

    // let plugins fiddle with end of line
    $end .= concat_hook_function('left_main_after_each_folder',
            array(isset($numMessages) ? $numMessages : '',
            $box['MailboxFullName'], $settings['imapConnection']));

    $end .= '</span>';

    $out = '';
    if (!$box['IsRoot']) {
        $out = $span . $pre .
               str_replace(
                    array(' ','<','>'),
                    array('&nbsp;','&lt;','&gt;'),
                    $box['MailboxName']) .
               $end . $spanend . '<br />' . "\n";
        $indent_factor++;
    }

    if (!$box['IsCollapsed'] || $box['IsRoot']) {
        for ($i = 0; $i<sizeof($box['ChildBoxes']); $i++) {
            $out .= buildMailboxTree($box['ChildBoxes'][$i], $settings, $indent_factor);
        }
    }
    
    return $out;
}

// Retrieve the template vars
extract($t);
  
/*
 * Build an array to pass user prefs to the function that builds the tree in
 * order to avoid using globals, which are dirty, filthy things in templates. :)
 */         
$settings = array();
$settings['imapConnection'] = $imapConnection;
$settings['iconThemePath'] = $icon_theme_path;
$settings['unreadNotificationEnabled'] = $unread_notification_enabled;
$settings['unreadNotificationAllFolders'] = $unread_notification_allFolders;
$settings['unreadNotificationDisplayTotal'] = $unread_notification_displayTotal;
$settings['unreadNotificationCummulative'] = $unread_notification_cummulative;
$settings['useSpecialFolderColor'] = $use_special_folder_color;
$settings['messageRecyclingEnabled'] = $message_recycling_enabled;
$settings['trashFolderName'] = $trash_folder_name;
$settings['collapsableFoldersEnabled'] = $collapsable_folders_enabled;

?>
<body class="sqm_leftMain">
<div class="sqm_leftMain">
<?php do_hook('left_main_before'); ?>
<table class="sqm_wrapperTable" cellspacing="0">
 <tr>
  <td>
   <table cellspacing="0">
    <tr>
     <td style="text-align:center">
      <span class="sqm_folderHeader"><?php echo _("Folders"); ?></span><br />
      <span class="sqm_clock"><?php echo $clock; ?></span>
      <span class="sqm_refreshButton"><small>[<a href="../src/left_main.php" target="left"><?php echo _("Check Mail"); ?></a>]</small></span>
     </td>
    </tr>
   </table>
   <br />
   <?php echo buildMailboxTree($mailboxes, $settings); ?>
  </tr>
 </td>
</table>
<?php do_hook('left_main_after'); ?>
</div>
