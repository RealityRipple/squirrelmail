<?php
/**
 * left_main.tpl
 *
 * Displays an experimental mailbox-tree with dhtml behaviour.
 * Advanced tree makes uses dTree JavaScript package by Geir Landrö heavily.
 * See http://www.destroydrop.com/javascripts/tree/
 *  
 * It only works on browsers which supports css and javascript.
 * 
 * The following variables are avilable in this template:
 *      $clock           - formatted string containing last refresh
 *      $settings        - Array containing user perferences needed by this
 *                         template.  Indexes are as follows:
 *          $settings['iconThemePath'] - Path to the desired icon theme.  If
 *                         the user has disabled icons, this will be NULL.
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
 * @param array $box Array containing mailbox data
 * @param array $settings Array containing perferences, etc, passed to template
 * @param string $icon_theme_path
 * @param integer $indent_factor Counter used to control indent spacing
 * @since 1.5.2
 * @author Steve Brown
 */
function buildMailboxTree ($box, $settings, $icon_theme_path, $parent_node=-1) {
    static $counter;
    
    // stop condition
    if (empty($box)) {
        return '';
    }
    
    $out = '';
    if ($box['IsRoot']) { 
        // Determine the path to the correct images
        $out .= 'mailboxes = new dTree("mailboxes", "'.$icon_theme_path.'");'."\n";
        $out .= 'mailboxes.config.inOrder = true;'."\n";
        $counter = -1;
    } else {
        $counter++;
        $name = $box['MailboxName'];
        $pre = '<span style="white-space: nowrap;">';
        $end = '';

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

        /**
         * Add folder icon.
         */
        $img = '';
        $img_open = '';
        switch (true) {
            case $box['IsInbox']:
                $img = 'base.png';
                $img_open = 'base.png';
                break; 
            case $box['IsTrash']:
                $img = 'trash.png';
                $img_open = 'trash.png';
                break;
            case $box['IsNoSelect']: 
            case $box['IsNoInferiors']:
                $img = 'page.png';
                $img_open = 'page.png';
                break;
            default:
                $img = 'folder.png';
                $img_open = 'folderopen.png'; 
                break;
        }
        
        $display_folder = true;
        if (!$settings['messageRecyclingEnabled'] && $box['IsTrash']) {
            $display_folder = false;
        }
        
        if($settings['messageRecyclingEnabled'] && $box['IsTrash']) {
            // Boxes with unread messages should be emphasized
            if ($box['UnreadCount'] > 0) {
                $pre .= '<em>';
                $end .= '</em>';
            }            

            // Print unread info
            if ($box['UnreadCount'] > 0) {
                if (!empty($unseen_str)) {
                    $end .= '&nbsp;<small>('.$unseen_str.')</small>';
                }
            }
        } else {
            // Add a few other things for all other folders...
            if (!$box['IsNoSelect']) {
                // Boxes with unread messages should be emphasized
                if ($box['UnreadCount'] > 0) {
                    $pre .= '<em>';
                    $end .= '</em>';
                }
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
        
        $name = str_replace(
                    array(' ','<','>'),
                    array('&nbsp;','&lt;','&gt;'),
                    $box['MailboxName']);
        $title = $name;
                             
        if ($box['IsNoSelect']) {
            $url = '';
            $target = '';
        } else {
            $url = $box['ViewLink']['URL'];
            $target = $box['ViewLink']['Target'];
            $name = $span . $pre . $name . $end . $spanend;
        }
        
        if ($display_folder) {

            if ($box['IsInbox']) {
                global $accesskey_folders_inbox;
                $accesskey = $accesskey_folders_inbox;
            }
            else $accesskey = '';

            $out .= 'mailboxes.add('.$counter.', '.$parent_node.', ' .
                                       '"'.addslashes($name).'", "'.$url.'", "'.$title.'", ' .
                                       '"'.$target.'", ' .
                                       '"'.getIconPath($icon_theme_path, $img).'", ' .
                                       '"'.getIconPath($icon_theme_path, $img_open).'", ' .
                                       '"'.$accesskey.'"' .
                                       ');'."\n";
        }
    }
    
    $parent_node = $counter;
    for ($i = 0; $i<sizeof($box['ChildBoxes']); $i++) {
        $out .= buildMailboxTree($box['ChildBoxes'][$i], $settings, $icon_theme_path, $parent_node);
    }

    if ($box['IsRoot']) {
        $out .= 'document.write(mailboxes);'."\n";
    }
    
    return $out;
//FIXME: somewhere above, need to insert the left_main_after_each_folder hook, or if no plugin hooks allowed in templates, at least the output from that hook (but I think it might be impossible not to have the hook here in this fxn
}

/* retrieve the template vars */
extract($t);

?>
<body class="sqm_leftMain">
<script type="text/javascript">
<!--
/**
 * Advanced tree makes uses dTree JavaScript package by Geir Landrö heavily.
 * See http://www.destroydrop.com/javascripts/tree/
 *
 * |---------------------------------------------------|
 * | dTree 2.05 | www.destroydrop.com/javascript/tree/ |
 * |---------------------------------------------------|
 * | Copyright (c) 2002-2003 Geir Landrö               |
 * |                                                   |
 * | This script can be used freely as long as all     |
 * | copyright messages are intact.                    |
 * |                                                   |
 * | Updated: 17.04.2003                               |
 * |---------------------------------------------------|
 **/
//-->
</script>
<div class="sqm_leftMain">
<?php if (!empty($plugin_output['left_main_before'])) echo $plugin_output['left_main_before']; ?>
<div class="dtree">
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
  </td>
 </tr>
</table>
<p>
<a href="javascript:mailboxes.openAll()"><?php echo _("Open All") ?></a>
&nbsp;&nbsp;|&nbsp;&nbsp;
<a href="javascript:mailboxes.closeAll()"><?php echo _("Close All") ?></a>
<?php
if ($settings['messageRecyclingEnabled']) {
    echo '<br />';
    echo '<a href="empty_trash.php?smtoken=' . sm_generate_security_token() . '"';
    if ($accesskey_folders_purge_trash != 'NONE')
        echo ' accesskey="' . $accesskey_folders_purge_trash . '"';
    echo '>' . _("Purge Trash") . '</a>';
}
?>
</p>
<script type="text/javascript">
<!--
<?php echo buildMailboxTree($mailboxes, $settings, $icon_theme_path); ?>
-->
</script>
</div>
<?php if (!empty($plugin_output['left_main_after'])) echo $plugin_output['left_main_after']; ?>
</div>
