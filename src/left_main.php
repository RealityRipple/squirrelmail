<?php

/**
 * left_main.php
 *
 * This is the code for the left bar. The left bar shows the folders
 * available, and has cookie information.
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 */

/**
 * Path for SquirrelMail required files.
 * @ignore
 */
define('SM_PATH','../');

/* SquirrelMail required files. */
include_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'functions/imap.php');
require_once(SM_PATH . 'functions/plugin.php');
require_once(SM_PATH . 'functions/page_header.php');
require_once(SM_PATH . 'functions/html.php');
require_once(SM_PATH . 'functions/date.php');

/** add required includes */
include_once(SM_PATH . 'templates/util_global.php');
include_once(SM_PATH . 'templates/util_left_main.php');

/* These constants are used for folder stuff. */
define('SM_BOX_UNCOLLAPSED', 0);
define('SM_BOX_COLLAPSED',   1);

/* --------------------- FUNCTIONS ------------------------- */



/**
 * Recursive functions to output a tree of folders.
 * They are called on a list of boxes and iterates over that tree.
 *
 * NOTE: These functions are deprecated and replaced with templates in 1.5.2.
 *       They remain until the advanced tree tempalte is completed also,
 *       at which point both functions below will be removed
 * 
 * @since 1.3.0
 * @deprecated
 */
function ListBoxes ($boxes, $j=0) {
    return '';
}

function ListAdvancedBoxes ($boxes, $mbx, $j='ID.0000' ) {
    global $data_dir, $username, $color, $unseen_notify, $unseen_type, $unseen_cum,
        $move_to_trash, $trash_folder, $collapse_folders, $use_special_folder_color;

    if (empty($boxes)) {
        return;
    }

    /* use_folder_images only works if the images exist in ../images */
    $use_folder_images = true;

    $pre = '';
    $end = '';
    $collapse = false;
    $unseen_found = false;
    $unseen = 0;

    $mailbox = $boxes->mailboxname_full;
    $mailboxURL = urlencode($mailbox);

     /* get collapse information */
     if ($collapse_folders) {
          $form_entry = $j.'F';
          if (isset($mbx) && isset($mbx[$form_entry])) {
              $collapse = $mbx[$form_entry];
              setPref($data_dir, $username, 'collapse_folder_'.$boxes->mailboxname_full ,
                    $collapse ? SM_BOX_COLLAPSED : SM_BOX_UNCOLLAPSED);
          } else {
              $collapse = getPref($data_dir, $username, 'collapse_folder_' . $mailbox);
              $collapse = ($collapse == '' ? SM_BOX_UNCOLLAPSED : $collapse);
          }
          $img_src = ($collapse ? '../images/plus.png' : '../images/minus.png');
          $collapse_link = '<a href="javascript:void(0)">' .
                    " <img src=\"$img_src\" border=\"1\" id=$j onclick=\"hidechilds(this)\" style=\"cursor:hand\" /></a>";
    } else {
         $collapse_link='';
    }

    /* get unseen/total messages information */
    /* Only need to display info when option is set */
    if (isset($unseen_notify) && ($unseen_notify > 1)) {
        /* handle Cumulative Unread Message Notification */
        if ($collapse && $unseen_cum) {
            foreach ($boxes->mbxs as $cumn_box) {
                if (!empty($cumn_box->unseen)) $boxes->unseen += $cumn_box->unseen;
                if (!empty($cumn_box->total)) $boxes->total += $cumn_box->total;
            }
        }
        if (($boxes->unseen !== false) || ($boxes->total !== false)) {
            if ($boxes->unseen !== false)     $unseen = $boxes->unseen;
               /*
                * Should only display unseen info if the folder is inbox
                * or you set the option for all folders
                */
                if ((strtolower($mailbox) == 'inbox') || ($unseen_notify == 3)) {
                     $unseen_string = $unseen;

                    /* If users requests, display message count too */
                    if (isset($unseen_type) && ($unseen_type == 2) && ($boxes->total !== false)) {
                        $unseen_string .= '/' . $boxes->total;
                    }
                    if (isset($boxes->recent) && $boxes->recent > 0) {
                        $unseen_string = "<span class=\"leftrecent\">($unseen_string)</span>";
                    } else {
                        $unseen_string = "<span class=\"leftunseen\">($unseen_string)</span>";
                    }

                    /*
                     * Finally allow the script to display the values by setting a boolean.
                     * This can only occur if the unseen count is great than 0 (if you have
                     * unseen count only), or you have the message count too.
                     */
                     if (($unseen > 0) || (isset($unseen_type) && ($unseen_type ==2))) {
                         $unseen_found = true;
                     }
            }
        }
    }

    /* If there are unseen message, bold the line. */
    if ($unseen > 0) { $pre .= '<b>'; }

    /* color special boxes */
    if ($use_special_folder_color && $boxes->is_special) {
        $pre .= "<span class=\"leftspecial\">";
        $end .= '</span>';
    }

    /* If there are unseen message, close bolding. */
    if ($unseen > 0) { $end .= '</b>'; }

    /* Print unseen information. */
    if ($unseen_found) {
        $end .= "&nbsp;$unseen_string";
    }

    if (($move_to_trash) && ($mailbox == $trash_folder)) {
        $pre = "<a class=\"mbx_link\" href=\"right_main.php?PG_SHOWALL=0&amp;startMessage=1&amp;mailbox=$mailboxURL\" target=\"right\">" . $pre;
        $end .= '</a>';
        $end .= "\n<small>\n" .
                '&nbsp;&nbsp;[<a class="mbx_link" href="empty_trash.php">'._("Purge").'</a>]'.
                '</small>';
    } else {
        if (!$boxes->is_noselect) { /* \Noselect boxes can't be selected */
            $pre = "<a class=\"mbx_link\" href=\"right_main.php?PG_SHOWALL=0&amp;startMessage=1&amp;mailbox=$mailboxURL\" target=\"right\">" . $pre;
            $end .= '</a>';
        }
    }

    // let plugins fiddle with end of line
    global $imapConnection;
    $end .= concat_hook_function('left_main_after_each_folder',
        array(isset($numMessages) ? $numMessages : '',
              $boxes->mailboxname_full, $imapConnection));

    $out = '';
    if (!$boxes->is_root) {
        if ($use_folder_images) {
            if ($boxes->is_inbox) {
                $folder_img = '../images/inbox.png';
            } else if ($boxes->is_sent) {
                $folder_img = '../images/senti.png';
            } else if ($boxes->is_trash) {
                $folder_img = '../images/delitem.png';
            } else if ($boxes->is_draft) {
                $folder_img = '../images/draft.png';
            } else if ($boxes->is_noinferiors) {
                $folder_img = '../images/folder_noinf.png';
            } else {
                $folder_img = '../images/folder.png';
            }
            $folder_img = '&nbsp;<img src="'.$folder_img.'" height="15" />&nbsp;';
        } else {
            $folder_img = '';
        }
        if (!isset($boxes->mbxs[0])) {
            $out .= '   ' . html_tag( 'div',
                            $pre . $folder_img .
                                str_replace( array(' ','<','>'),
                                             array('&nbsp;','&lt;','&gt;'),
                                             $boxes->mailboxname_sub) .
                                $end,
                            'left', '', 'class="mbx_sub" id="' .$j. '"' ) . "\n";
        } else {

            $out .= '   ' . html_tag( 'div',
                            $collapse_link . $pre . $folder_img . '&nbsp;'. $boxes->mailboxname_sub . $end ,
                            'left', '', 'class="mbx_par" id="' .$j. 'P"' ) . "\n";
            $out .= '   <input type="hidden" name="mbx['.$j. 'F]" value="'.$collapse.'" id="mbx['.$j.'F]" />'."\n";
        }
    }

    $visible = ($collapse ? ' style="display:none"' : ' style="display:block"');
    if (isset($boxes->mbxs[0]) && !$boxes->is_root) /* mailbox contains childs */
        $out .= html_tag( 'div', '', 'left', '', 'class="par_area" id='.$j.'.0000 '. $visible ) . "\n";

    if ($j !='ID.0000') {
       $j = $j .'.0000';
    }
    for ($i = 0; $i <count($boxes->mbxs); $i++) {
        $j++;
        $out .= ListAdvancedBoxes($boxes->mbxs[$i],$mbx,$j);
    }
    if (isset($boxes->mbxs[0]) && !$boxes->is_root) {
        $out .= '</div>'."\n\n";
    }
    
    return $out;
}




/* -------------------- MAIN ------------------------ */

/* get globals */
sqgetGlobalVar('username', $username, SQ_SESSION);
sqgetGlobalVar('key', $key, SQ_COOKIE);
sqgetGlobalVar('delimiter', $delimiter, SQ_SESSION);
sqgetGlobalVar('onetimepad', $onetimepad, SQ_SESSION);

sqgetGlobalVar('fold', $fold, SQ_GET);
sqgetGlobalVar('unfold', $unfold, SQ_GET);

/* end globals */

// open a connection on the imap port (143)
// why hide the output?
$imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, true);

/**
 * Using stristr since very old preferences may contain "None" and "none".
 */
if (!empty($left_refresh) &&
    !stristr($left_refresh, 'none')){
    $xtra =  "\n<meta http-equiv=\"Expires\" content=\"Thu, 01 Dec 1994 16:00:00 GMT\" />\n" .
             "<meta http-equiv=\"Pragma\" content=\"no-cache\" />\n".
             "<meta http-equiv=\"REFRESH\" content=\"$left_refresh;URL=left_main.php\" />\n";
} else {
    $xtra = '';
}

/**
 * $advanced_tree and is a boolean var which is default set to default
 * SM behaviour.
 * Setting $advanced tree to true causes SM to display a experimental
 * mailbox-tree with dhtml behaviour.  
 * 
 * See templates/default/left_main_advanced.tpl
 **/

/* set this to true if you want to see a nicer mailboxtree */
if (empty($advanced_tree)) {
    $advanced_tree=false;
}

// get mailbox list and cache it
$mailboxes=sqimap_get_mailboxes($imapConnection,false,$show_only_subscribed_folders);

displayHtmlHeader( 'SquirrelMail', $xtra );

sqgetGlobalVar('auto_create_done',$auto_create_done,SQ_SESSION);
/* If requested and not yet complete, attempt to autocreate folders. */
if ($auto_create_special && !isset($auto_create_done)) {
    $autocreate = array($sent_folder, $trash_folder, $draft_folder);
    $folders_created = false;
    foreach( $autocreate as $folder ) {
        if (($folder != '') && ($folder != 'none')) {
            // use $mailboxes array for checking if mailbox exists
            if ( !sqimap_mailbox_exists($imapConnection, $folder, $mailboxes)) {
                sqimap_mailbox_create($imapConnection, $folder, '');
                $folders_created = true;
            } else {
                // check for subscription is useless and expensive, just
                // surpress the NO response. Unless we're on Mecury, which
                // will just subscribe a folder again if it's already
                // subscribed.
                if ( strtolower($imap_server_type) != 'mercury32' ||
                    !sqimap_mailbox_is_subscribed($imapConnection, $folder) ) {
                    sqimap_subscribe($imapConnection, $folder, false);
                    $folders_created = true;
                }
            }
        }
    }

    /* Let the world know that autocreation is complete! Hurrah! */
    $auto_create_done = TRUE;
    sqsession_register($auto_create_done, 'auto_create_done');
    // reload mailbox list
    if ($folders_created)
        $mailboxes=sqimap_get_mailboxes($imapConnection,true,$show_only_subscribed_folders);
}

$clock = '';
if ($date_format != 6) {
    /* First, display the clock. */
    if ($hour_format == 1) {
        $hr = 'H:i';
        if ($date_format == 4) {
            $hr .= ':s';
        }
    } else {
        if ($date_format == 4) {
            $hr = 'g:i:s a';
        } else {
            $hr = 'g:i a';
        }
    }

    switch( $date_format ) {
    case 0:
        $clk = date('Y-m-d '.$hr. ' T', time());
        break;
    case 1:
        $clk = date('m/d/y '.$hr, time());
        break;
    case 2:
        $clk = date('d/m/y '.$hr, time());
        break;
    case 4:
    case 5:
        $clk = date($hr, time());
        break;
    default:
        $clk = getDayAbrv( date( 'w', time() ) ) . date( ', ' . $hr, time() );
    }
    $clk = str_replace(' ','&nbsp;',$clk);

    $clock = '<small><span style="white-space: nowrap;">'
       . str_replace(' ', '&nbsp;', _("Last Refresh"))
       . ":</span><br /><span style=\"white-space: nowrap;\">$clk</span></small><br />\n";
}

if ( $collapse_folders ) {
    /* If directed, collapse or uncollapse a folder. */
    if (isset($fold)) {
        setPref($data_dir, $username, 'collapse_folder_' . $fold, SM_BOX_COLLAPSED);
    } else if (isset($unfold)) {
        setPref($data_dir, $username, 'collapse_folder_' . $unfold, SM_BOX_UNCOLLAPSED);
    }
}

/* Get unseen/total display prefs */
$unseen_type = getPref( $data_dir , $username , 'unseen_type' );
$unseen_notify = getPref( $data_dir , $username , 'unseen_notify' );

if (empty($unseen_type)) {
    if (!empty($default_unseen_type)) {
        $unseen_type = $default_unseen_type;
    } else {
        $unseen_type = 1;
    }
}

if (empty($unseen_notify)) {
    if (!empty($default_unseen_notify)) {
        $unseen_notify = $default_unseen_notify;
    } else {
        $unseen_notify = 0;
    }
}

/**
 * pass $mailboxes now instead of $imapconnection - sqimap_get_mailboxes() has been separated from
 * sqimap_mailbox_tree() so that the cached mailbox list can be used elsewhere in left_main and beyond
 */
$boxes = sqimap_mailbox_tree($imapConnection,$mailboxes,$show_only_subscribed_folders);

$mailbox_listing = '';
if (isset($advanced_tree) && $advanced_tree) {
    $mailbox_listing = '<form name="collapse" action="left_main.php" method="post" ' .
         'enctype="multipart/form-data">'."\n";
    $mailbox_listing .= '<button type="submit" class="button" onmouseover="buttonover(this,true)" onmouseout="buttonover(this,false)" onmousedown="buttonclick(this,true)" onmouseup="buttonclick(this,false)">'. _("Save folder tree") .'</button><br /><br />';
    $mailbox_listing .= '<div id="mailboxes" class="mailboxes">'."\n\n";
    sqgetGlobalVar('mbx', $mbx, SQ_POST);
    if (!isset($mbx)) $mbx=NULL;
    $mailbox_listing .=ListAdvancedBoxes($boxes, $mbx);
    $mailbox_listing .= '</div>';
    $mailbox_listing .= '</form>'."\n";
} else {
    $mailbox_listing = ListBoxes($boxes);
}

$mailbox_structure = getBoxStructure($boxes);

$oTemplate->assign('clock', $clock);
$oTemplate->assign('mailbox_listing', $mailbox_listing);
$oTemplate->assign('location_of_bar', $location_of_bar);
$oTemplate->assign('left_size', $left_size);

$oTemplate->assign('mailboxes', $mailbox_structure);
$oTemplate->assign('imapConnection', $imapConnection);

$oTemplate->assign('unread_notification_enabled', $unseen_notify!=1);
$oTemplate->assign('unread_notification_cummulative', $unseen_cum==1);
$oTemplate->assign('unread_notification_allFolders', $unseen_notify == 3);
$oTemplate->assign('unread_notification_displayTotal', $unseen_type == 2);
$oTemplate->assign('collapsable_folders_enabled', $collapse_folders==1);
$oTemplate->assign('icon_theme_path', $icon_theme_path);
$oTemplate->assign('use_special_folder_color', $use_special_folder_color);
$oTemplate->assign('message_recycling_enabled', $move_to_trash);

if (isset($advanced_tree) && $advanced_tree)    {
    $oTemplate->display('left_main_advanced.tpl');
}   else    { 
    $oTemplate->display('left_main.tpl');
}

sqimap_logout($imapConnection);
$oTemplate->display('footer.tpl');
?>