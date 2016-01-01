<?php

/**
 * folder_list_util.php
 *
 * Provides some functions for use in left_main.php and templates.  Do not echo
 * output from these functions!
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage templates
 */
 
/**
 * Recursively iterates a mailboxes object to get the cummulative count of
 * messages for all folderes below the current mailbox.
 * 
 * @param object $boxes Object of the class mailboxes
 * @param string $type Whether to fetch unseen only or all messages
 * @author Steve Brown
 * @since 1.5.2
 */
function getMessageCount ($boxes, $type='total') {

    global $trash_folder;

    // The Trash folder isn't counted...
    if ($boxes->mailboxname_full == $trash_folder)
        return 0;
        
    $count = 0;
    if (strtolower($type) == 'unseen')
        $field = 'unseen';
    else $field = 'total';
    
    
    $count += !empty($boxes->{$field}) ? $boxes->{$field} : 0;
    for ($j = 0; $j <count($boxes->mbxs); $j++) {
        $count += getMessageCount($boxes->mbxs[$j], $type);
    }
    
    return $count;    
}

/**
 * Recursively iterates a mailboxes object to build a data structure that is
 * easy for template authors to work with.
FIXME: well.... why not document that data structure here?
 * 
 * @param object $boxes Object of the class mailboxes
 * @author Steve Brown
 * @since 1.5.2
 */
function getBoxStructure ($boxes) {
    global $data_dir, $username, $icon_theme_path;
        
    // Stop condition   
    if (empty($boxes))  {
        return array();
    }
    
    $mailbox = $boxes->mailboxname_full;
    $mailboxURL = urlencode($mailbox);
    $box = array();

    $box['MailboxFullName'] = $mailbox;
    $box['MailboxName'] = $boxes->mailboxname_sub;
    $box['MessageCount'] = !empty($boxes->total) ? $boxes->total : 0;
    $box['UnreadCount'] = !empty($boxes->unseen) ? $boxes->unseen : 0;
    
    // Needed in case user enables cummulative message counts
    $box['CummulativeMessageCount'] = getMessageCount($boxes, 'total');
    $box['CummulativeUnreadCount'] = getMessageCount($boxes, 'unseen');
    
    $box['ViewLink'] = array( 'Target' => 'right',
                              'URL'    => 'right_main.php?PG_SHOWALL=0&amp;startMessage=1&amp;mailbox='.$mailboxURL
                            );
                              
    $box['IsRecent'] = isset($boxes->recent) && $boxes->recent;
    $box['IsSpecial'] = isset($boxes->is_special) && $boxes->is_special;
    $box['IsRoot'] =  isset($boxes->is_root) && $boxes->is_root;
    $box['IsNoSelect'] = isset($boxes->is_noselect) && $boxes->is_noselect;

    $box['IsInbox'] = isset($boxes->is_inbox) && $boxes->is_inbox;
    $box['IsSent'] = isset($boxes->is_sent) && $boxes->is_sent;
    $box['IsTrash'] = isset($boxes->is_trash) && $boxes->is_trash;
    $box['IsDraft'] = isset($boxes->is_draft) && $boxes->is_draft;
    $box['IsNoInferiors'] = isset($boxes->is_noinferiors) && $boxes->is_noinferiors;

    $collapse = getPref($data_dir, $username, 'collapse_folder_' . $mailbox);
    $collapse = ($collapse == '' ? SM_BOX_UNCOLLAPSED : $collapse);
    $collapse = (int)$collapse == SM_BOX_COLLAPSED;
    $box['IsCollapsed'] = $collapse;

    /*
     * Check for an image needed here.  If the file exists in $icon_theme_path
     * assume the template provides all icons.  If not, we will use the 
     * SQM default images.  If icons have been disabled, $icon_theme_path
     * will be NULL.
     */
     
    $text_icon = $box['IsCollapsed'] ? '+' : '-';
    $icon_file = $box['IsCollapsed'] ? 'plus.png' : 'minus.png';
    $icon_alt = $box['IsCollapsed'] ? 'Expand Box' : 'Collapse Box';
    $icon = getIcon($icon_theme_path, $icon_file, $text_icon, $icon_alt);
    
    $box['CollapseLink'] = array ( 'Target' => 'left',
                                   'URL'    => 'left_main.php?'.($box['IsCollapsed'] ? 'unfold' : 'fold') .'='.$mailboxURL,
                                   'Icon'   => $icon .'&nbsp;'
                                 ); 

    $box['ChildBoxes'] = array();
    for ($i = 0; $i <count($boxes->mbxs); $i++) {
        $box['ChildBoxes'][] = getBoxStructure($boxes->mbxs[$i]);
    }
    
    // if plugins want to add some text or link after the folder name in
    // the folder list, they should add to the "ExtraOutput" array element
    // in $box (remember, it's passed through the hook by reference) -- making
    // sure to play nice with other plugins by *concatenating* to "ExtraOutput"
    // and NOT by overwriting it
    //
    // known users of this hook:
    // empty_folders
    //
    do_hook('left_main_after_each_folder', $box);

    return $box;
}
