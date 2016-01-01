<?php
/**
 * left_main.tpl
 *
 * Basic template to the left main window.  
 * 
 * The following variables are avilable in this template:
 *      $clock           - formatted string containing last refresh
 *      $settings        - Array containing user perferences needed by this
 *                         template.  Indexes are as follows:
 *          $settings['templateID'] - contains the ID of the current
 *                         template set.  This may be needed by third
 *                         party packages that don't integrate easily.
 *          $settings['unreadNotificationEnabled'] - Boolean TRUE if the user
 *                         wants to see unread message count on mailboxes
 *          $settings['unreadNotificationCummulative'] - Boolean TRUE if the
 *                         user has enabled cummulative message counts.
 *          $settings['unreadNotificationAllFolders'] - Boolean TRUE if the
 *                         user wants to see unread message count on ALL
 *                         folders or just the Inbox.
 *          $settings['unreadNotificationDisplayTotal'] - Boolean TRUE if the
 *                         user wants to see the total number of messages in
 *                         addition to the unread message count.
 *          $settings['collapsableFoldersEnabled'] - Boolean TRUE if the user
 *                         has enabled collapsable folders.
 *          $settings['useSpecialFolderColor'] - Boolean TRUE if the use has
 *                         chosen to tag "Special" folders in a different color
 *          $settings['messageRecyclingEnabled'] - Boolean TRUE if messages
 *                         that get deleted go to the Trash folder.  FALSE if
 *                          they are permanently deleted.
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
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage templates
 * @author Steve Brown
 */


/*
 * Recursively parse the mailbox structure to build the navigation tree.
 *
 * @since 1.5.2
 */
function buildMailboxTree ($box, $settings, $icon_theme_path, $indent_factor=0) {
    // stop condition
    if (empty($box)) {
        return '';
    }

    $pre = '<span style="white-space: nowrap;">';
    $end = '';
    $indent = str_repeat('&nbsp;&nbsp;',$indent_factor);

    // Get unseeen/total message info if needed
    $unseen_str = '';
    if ($settings['unreadNotificationEnabled'])   {
        // We only display the unread count if we on the Inbox or we are told
        // to display it on all folders AND there is more than 1 unread message
        if ( $settings['unreadNotificationAllFolders'] ||
             (!$settings['unreadNotificationAllFolders'] && strtolower($box['MailboxFullName'])=='inbox')
           )  {
            $unseen = $settings['unreadNotificationCummulative'] ?
                            $box['CummulativeUnreadCount'] :
                            $box['UnreadCount'];
            
            if (!$box['IsNoSelect'] && ($unseen > 0 || $settings['unreadNotificationDisplayTotal'])) {
                $unseen_str = $unseen;
    
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
                '>' .
                $box['CollapseLink']['Icon'] .
                '</a>';
        $pre .= $link;
    } else {
        $pre .= $indent . '&nbsp;&nbsp;';
    }

    /**
     * Add folder icon.  Template authors may choose to display a different
     * image based on whatever logic they see fit here.
     */
    $folder_icon = '';
    if (!is_null($icon_theme_path)) {
        switch (true) {
            case $box['IsInbox']:
                $folder_icon = getIcon($icon_theme_path, 'inbox.png', '', $box['MailboxName']);
                break; 
            case $box['IsSent']:
                $folder_icon = getIcon($icon_theme_path, 'senti.png', '', $box['MailboxName']);
                break; 
            case $box['IsTrash']:
                $folder_icon = getIcon($icon_theme_path, 'delitem.png', '', $box['MailboxName']);
                break; 
            case $box['IsDraft']:
                $folder_icon = getIcon($icon_theme_path, 'draft.png', '', $box['MailboxName']);
                break; 
            case $box['IsNoInferiors']:
                $folder_icon = getIcon($icon_theme_path, 'folder_noinf.png', '', $box['MailboxName']);
                break;
            default: 
                $folder_icon = getIcon($icon_theme_path, 'folder.png', '', $box['MailboxName']);
                break;
        }
        $folder_icon .= '&nbsp;';
    }
    $pre .= $folder_icon;

    // calculate if access key is needed
    //
    if ($box['IsInbox']) {
        global $accesskey_folders_inbox;
        $accesskey = $accesskey_folders_inbox;
    }
    else $accesskey = '';
    
    /*
     * The Trash folder should only be displayed if message recycling has
     * been enabled, i.e. when deleted is a message moved to the trash or
     * deleted forever?
     */
    $view_link = '<a href="'.$box['ViewLink']['URL'].'" ' .
                 ($accesskey == '' ? '' : 'accesskey="' . $accesskey . '" ') .
                 'target="'.$box['ViewLink']['Target'].'" ' .
                 'style="text-decoration:none">';

    if ($settings['messageRecyclingEnabled'] && $box['IsTrash']) {
        $pre .= $view_link;

        // Boxes with unread messages should be emphasized
        if ($box['UnreadCount'] > 0) {
            $pre .= '<em>';
            $end .= '</em>';
        }
        $end .= '</a>';

        // Print unread info
        if ($box['MessageCount'] > 0 || count($box['ChildBoxes'])) {
            if (!empty($unseen_str)) {
                $end .= '&nbsp;<small>('.$unseen_str.')</small>';
            }
            $end .= "\n<small>" .
                    '&nbsp;&nbsp;[<a href="empty_trash.php?smtoken=' . sm_generate_security_token() . '">'. _("Purge").'</a>]' .
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

    // Add any extra output that may have been added by plugins, etc
    //
    if (!empty($box['ExtraOutput']))
        $end .= $box['ExtraOutput'];

    $span = '';
    $spanend = '';
    if ($settings['useSpecialFolderColor'] && $box['IsSpecial']) {
        $span = '<span class="leftspecial">';
        $spanend = '</span>';
    } elseif ( $box['IsNoSelect'] ) {
        $span = '<span class="leftnoselect">';
        $spanend = '</span>';
    }

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
            $out .= buildMailboxTree($box['ChildBoxes'][$i], $settings, $icon_theme_path, $indent_factor);
        }
    }

    return $out;
}

// Retrieve the template vars
extract($t);

?>
<body class="sqm_leftMain">
<div class="sqm_leftMain">
<?php if (!empty($plugin_output['left_main_before'])) echo $plugin_output['left_main_before']; ?>
<table class="sqm_wrapperTable" cellspacing="0">
 <tr>
  <td>
   <table cellspacing="0">
    <tr>
     <td style="text-align:center">
      <span class="sqm_folderHeader"><?php echo _("Folders"); ?></span><br />
      <span class="sqm_clock"><?php echo $clock; ?></span>
      <span class="sqm_refreshButton"><small>[<a href="../src/left_main.php" <?php if ($accesskey_folders_refresh != 'NONE') echo 'accesskey="' . $accesskey_folders_refresh . '" '; ?>target="left"><?php echo _("Check Mail"); ?></a>]</small></span>
     </td>
    </tr>
   </table>
   <br />
   <?php echo buildMailboxTree($mailboxes, $settings, $icon_theme_path); ?>
  </td>
 </tr>
</table>
<?php if (!empty($plugin_output['left_main_after'])) echo $plugin_output['left_main_after']; ?>
</div>
