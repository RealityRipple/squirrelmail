<?php

/**
 * folders.php
 *
 * Copyright (c) 1999-2005 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Handles all interaction between the user and the other folder
 * scripts which do most of the work. Also handles the Special
 * Folders.
 *
 * @version $Id$
 * @package squirrelmail
 */

/**
 * Path for SquirrelMail required files.
 * @ignore
 */
define('SM_PATH','../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'functions/imap.php');
require_once(SM_PATH . 'functions/folder_manip.php');
require_once(SM_PATH . 'functions/plugin.php');
require_once(SM_PATH . 'functions/html.php');
require_once(SM_PATH . 'functions/forms.php');

displayPageHeader($color, 'None');

/* get globals we may need */

sqgetGlobalVar('username', $username, SQ_SESSION);
sqgetGlobalVar('key', $key, SQ_COOKIE);
sqgetGlobalVar('delimiter', $delimiter, SQ_SESSION);
sqgetGlobalVar('onetimepad', $onetimepad, SQ_SESSION);

sqgetGlobalVar('smaction', $action, SQ_POST);

/* end of get globals */

echo '<br />' .
    html_tag( 'table', '', 'center', $color[0], 'width="95%" cellpadding="1" cellspacing="0" border="0"' ) .
        html_tag( 'tr' ) .
            html_tag( 'td', '', 'center' ) . '<b>' . _("Folders") . '</b>' .
                html_tag( 'table', '', 'center', '', 'width="100%" cellpadding="5" cellspacing="0" border="0"' ) .
                    html_tag( 'tr' ) .
                        html_tag( 'td', '', 'center', $color[4] );

$imapConnection = sqimap_login ($username, $key, $imapServerAddress, $imapPort, 0);

/* switch to the right function based on what the user selected */
if ( sqgetGlobalVar('smaction', $action, SQ_POST) ) {

    switch ($action)
    {
        case 'create':
            sqgetGlobalVar('folder_name',  $folder_name,  SQ_POST);
            sqgetGlobalVar('subfolder',    $subfolder,    SQ_POST);
            sqgetGlobalVar('contain_subs', $contain_subs, SQ_POST);
            folders_create($imapConnection, $delimiter, $folder_name, $subfolder, $contain_subs);
            $td_str =  _("Created folder successfully.");
            break;
        case 'rename':
            if ( sqgetGlobalVar('cancelbutton', $dummy, SQ_POST) ) {
                break;
            }
            if ( ! sqgetGlobalVar('new_name', $new_name, SQ_POST) ) {
                sqgetGlobalVar('old_name',    $old_name, SQ_POST);
                folders_rename_getname($imapConnection, $delimiter, $old_name);
            } else {
                sqgetGlobalVar('orig',        $orig,     SQ_POST);
                sqgetGlobalVar('old_name',    $old_name, SQ_POST);
                folders_rename_do($imapConnection, $delimiter, $orig, $old_name, $new_name);
                $td_str =  _("Renamed successfully.");
            }
            break;
        case 'delete':
            if ( sqgetGlobalVar('cancelbutton', $dummy, SQ_POST) ) {
                break;
            }
            sqgetGlobalVar('folder_name',  $folder_name,  SQ_POST);
            if ( sqgetGlobalVar('confirmed', $dummy, SQ_POST) ) {
                folders_delete_do($imapConnection, $delimiter, $folder_name);
                $td_str =  _("Deleted folder successfully.");
            } else {
                folders_delete_ask($imapConnection, $folder_name);
            }
            break;
        case 'subscribe':
            sqgetGlobalVar('folder_names',  $folder_names,  SQ_POST);
            folders_subscribe($imapConnection, $folder_names);
            $td_str =  _("Subscribed successfully.");
            break;
        case 'unsubscribe':
            sqgetGlobalVar('folder_names',  $folder_names,  SQ_POST);
            folders_unsubscribe($imapConnection, $folder_names);
            $td_str =  _("Unsubscribed successfully.");
            break;
        default:
            // TODO: add hook for plugin action processing.
            $td_str = '';
            break;
    }

    // if there are any messages, output them.
    if ( !empty($td_str) ) {
        echo html_tag( 'table',
                html_tag( 'tr',
                     html_tag( 'td', '<b>' . $td_str . "</b><br />\n" .
                               '<a href="../src/left_main.php" target="left">' .
                               _("refresh folder list") . '</a>' ,
                     'center' )
                ) ,
            'center', '', 'width="100%" cellpadding="4" cellspacing="0" border="0"' );
    }
}

echo "\n<br />";

$boxes = sqimap_mailbox_list($imapConnection,true);

/** CREATING FOLDERS **/
echo html_tag( 'table', '', 'center', '', 'width="70%" cellpadding="4" cellspacing="0" border="0"' ) .
            html_tag( 'tr',
                html_tag( 'td', '<b>' . _("Create Folder") . '</b>', 'center', $color[9] )
            ) .
            html_tag( 'tr' ) .
                html_tag( 'td', '', 'center', $color[0] ) .
     addForm('folders.php', 'post', 'cf').
     addHidden('smaction','create').
     addInput('folder_name', '', 25).
     "<br />\n". _("as a subfolder of"). '<br />'.
     "<tt><select name=\"subfolder\">\n";

$show_selected = array();
$skip_folders = array();
$server_type = strtolower($imap_server_type);

// Special handling for courier
if ( $server_type == 'courier' ) {
    /**
     * If we use courier, we should hide system trash folder
     * FIXME: (tokul) Who says that courier does not allow storing folders in 
     * INBOX.Trash or inbox.trash? Can't reproduce it 3.0.8. This entry is 
     * useless, because in_array() check is case sensitive and INBOX is in 
     * upper case.
     */
    array_push($skip_folders, 'inbox.trash');

    if ( $default_folder_prefix == 'INBOX.' ) {
        // We don't need INBOX, since it is top folder
        array_push($skip_folders, 'INBOX');
    }
}

if ( $default_sub_of_inbox == false ) {
    echo '<option selected="selected" value="">[ '._("None")." ]</option>\n";
} else {
    echo '<option value="">[ '._("None")." ]</option>\n";
    $show_selected = array('inbox');
}

// Call sqimap_mailbox_option_list, using existing connection to IMAP server,
// the arrays of folders to include or skip (assembled above),
// use 'noinferiors' as a mailbox filter to leave out folders that can not contain other folders.
// use the long format to show subfolders in an intelligible way if parent is missing (special folder)
echo sqimap_mailbox_option_list($imapConnection, $show_selected, $skip_folders, $boxes, 'noinferiors', true);

echo "</select></tt>\n";
if ($show_contain_subfolders_option) {
    echo '<br />'.
         addCheckBox('contain_subs', FALSE, '1') .' &nbsp;'
       . _("Let this folder contain subfolders")
       . '<br />';
}
echo "<input type=\"submit\" value=\""._("Create")."\" />\n";
echo "</form></td></tr>\n";

echo html_tag( 'tr',
            html_tag( 'td', '&nbsp;', 'left', $color[4] )
        ) ."\n";

/** count special folders **/
foreach ($boxes as $index => $aBoxData) {
    if (isSpecialMailbox($aBoxData['unformatted']) &&
        ! in_array($aBoxData['unformatted'],$skip_folders)) {
        $skip_folders[] = $aBoxData['unformatted'];
    } 
}

/**
 * Retrieve list of folders when special folders are excluded. Special folders
 * should be unavailable in rename/delete/unsubscribe. Theoretically user can
 * modify form and perform these operations with special folders, but if user 
 * manages to delete/rename/unsubscribe special folder by hacking form... 
 *
 * If script or program depends on special folder, they should not assume that
 * folder is available.
 *
 * $filtered_folders contains empty string or html formated option list.
 */
$filtered_folders = sqimap_mailbox_option_list($imapConnection, 0, $skip_folders, $boxes, NULL, true);

/** RENAMING FOLDERS **/
echo html_tag( 'tr',
            html_tag( 'td', '<b>' . _("Rename a Folder") . '</b>', 'center', $color[9] )
        ) .
        html_tag( 'tr' ) .
        html_tag( 'td', '', 'center', $color[0] );

/* show only if we have folders to rename */
if (! empty($filtered_folders)) {
    echo addForm('folders.php')
       . addHidden('smaction', 'rename')
       . "<tt><select name=\"old_name\">\n"
       . '         <option value="">[ ' . _("Select a folder") . " ]</option>\n";

    // use existing IMAP connection, we have no special values to show,
    // but we do include values to skip. Use the pre-created $boxes to save an IMAP query.
    // send NULL for the flag - ALL folders are eligible for rename!
    // use long format to make sure folder names make sense when parents may be missing.
    echo $filtered_folders;

    echo "</select></tt>\n".
         '<input type="submit" value="'.
         _("Rename").
         "\" />\n".
         "</form></td></tr>\n";
} else {
    echo _("No folders found") . '<br /><br /></td></tr>';
}

echo html_tag( 'tr',
            html_tag( 'td', '&nbsp;', 'left', $color[4] )
        ) ."\n";

/** DELETING FOLDERS **/
echo html_tag( 'tr',
            html_tag( 'td', '<b>' . _("Delete Folder") . '</b>', 'center', $color[9] )
        ) .
        html_tag( 'tr' ) .
        html_tag( 'td', '', 'center', $color[0] );

/* show only if we have folders to delete */
if (!empty($filtered_folders)) {
    echo addForm('folders.php')
       . addHidden('smaction', 'delete')
       . "<tt><select name=\"folder_name\">\n"
       . '         <option value="">[ ' . _("Select a folder") . " ]</option>\n";

    // send NULL for the flag - ALL folders are eligible for delete (except what we've got in skiplist)
    // use long format to make sure folder names make sense when parents may be missing.
    echo $filtered_folders;

    echo "</select></tt>\n"
       . '<input type="submit" value="'
       . _("Delete")
       . "\" />\n"
       . "</form></td></tr>\n";
} else {
    echo _("No folders found") . "<br /><br /></td></tr>";
}

echo html_tag( 'tr',
            html_tag( 'td', '&nbsp;', 'left', $color[4] )
        ) ."</table>\n";


if ($show_only_subscribed_folders) {
    // FIXME: fix subscription options when top folder is not subscribed and sub folder is subscribed
    // TODO: use checkboxes instead of select options.

        /** UNSUBSCRIBE FOLDERS **/
        echo html_tag( 'table', '', 'center', '', 'width="70%" cellpadding="4" cellspacing="0" border="0"' ) .
                    html_tag( 'tr',
                        html_tag( 'td', '<b>' . _("Unsubscribe") . '/' . _("Subscribe") . '</b>', 'center', $color[9], 'colspan="2"' )
                    ) .
                    html_tag( 'tr' ) .
                        html_tag( 'td', '', 'center', $color[0], 'width="50%"' );

        if (!empty($filtered_folders)) {
            echo addForm('folders.php')
               . addHidden('smaction', 'unsubscribe')
               . "<tt><select name=\"folder_names[]\" multiple=\"multiple\" size=\"8\">\n"
               . $filtered_folders
               . "</select></tt><br /><br />\n"
               . '<input type="submit" value="'
               . _("Unsubscribe")
               . "\" />\n"
               . "</form></td>\n";
        } else {
            echo _("No folders were found to unsubscribe from.") . '</td>';
        }

        /** SUBSCRIBE TO FOLDERS **/
        echo html_tag( 'td', '', 'center', $color[0], 'width="50%"' );
        if(!$no_list_for_subscribe) {
            $boxes_all = sqimap_mailbox_list_all ($imapConnection);

            $subboxes = array();
            // here we filter out all boxes we're already subscribed to,
            // so we keep only the unsubscribed ones.
            foreach ($boxes_all as $box_a) {

                $use_folder = true;
                foreach ( $boxes as $box ) {
                    if ($box_a['unformatted'] == $box['unformatted'] ||
                        $box_a['unformatted-dm'] == $folder_prefix ) {
                        $use_folder = false;
                    }
                }

                if ($use_folder == true) {
                    $box_enc  = htmlspecialchars($box_a['unformatted-dm']);
                    $box_disp = htmlspecialchars(imap_utf7_decode_local($box_a['unformatted-disp']));
                    $subboxes[$box_enc] = $box_disp;
                }
            }

            if ( count($subboxes) > 0 ) {
                echo addForm('folders.php')
                 . addHidden('smaction', 'subscribe')
                 . '<tt><select name="folder_names[]" multiple="multiple" size="8">';

                foreach($subboxes as $subbox_enc => $subbox_disp) {
                    echo '         <option value="' . $subbox_enc . '">'.$subbox_disp."</option>\n";
                }

                echo '</select></tt><br /><br />'
                 . '<input type="submit" value="'. _("Subscribe") . "\" />\n"
                 . "</form></td></tr></table><br />\n";
            } else {
                echo _("No folders were found to subscribe to.") . '</td></tr></table>';
            }
        } else {
            /* don't perform the list action -- this is much faster */
            echo addForm('folders.php')
             . addHidden('smaction', 'subscribe')
             . _("Subscribe to:") . '<br />'
             . '<tt><input type="text" name="folder_names[]" size="35" />'
             . '<input type="submit" value="'. _("Subscribe") . "\" />\n"
             . "</form></td></tr></table><br />\n";
        }
}

do_hook('folders_bottom');
sqimap_logout($imapConnection);

?>
    </td></tr>
    </table>
</td></tr>
</table>
</body></html>