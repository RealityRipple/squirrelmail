<?php

/**
 * folders.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Handles all interaction between the user and the other folder
 * scripts which do most of the work. Also handles the Special
 * Folders.
 *
 * $Id$
 */

require_once('../src/validate.php');
require_once('../functions/imap.php');
require_once('../functions/array.php');
require_once('../functions/plugin.php');

displayPageHeader($color, 'None');

?>

<br>
<table bgcolor="<?php echo $color[0] ?>" width="95%" align="center" cellpadding="2" cellspacing="0" border="0">
<tr><td align="center">

    <b><?php echo _("Folders"); ?></b>

    <table width="100%" border="0" cellpadding="5" cellspacing="0">
    <tr><td bgcolor="<?php echo $color[4] ?>" align="center">

<?php

if ((isset($success) && $success) ||
    (isset($sent_create) && $sent_create == 'true') ||
    (isset($trash_create) && $trash_create == 'true')) {
    echo "<table width=\"100%\" align=center cellpadding=4 cellspacing=0 border=0>\n" .
         "   <tr><td align=center>\n";
    if ($success == "subscribe") {
        echo "<b>" . _("Subscribed successfully!") . "</b><br>";
    } else if ($success == "unsubscribe") {
        echo "<b>" . _("Unsubscribed successfully!") . "</b><br>";
    } else if ($success == "delete") {
        echo "<b>" . _("Deleted folder successfully!") . "</b><br>";
    } else if ($success == "create") {
        echo "<b>" . _("Created folder successfully!") . "</b><br>";
    } else if ($success == "rename") {
        echo "<b>" . _("Renamed successfully!") . "</b><br>";
    }

    echo "   <a href=\"../src/left_main.php\" target=left>" . _("refresh folder list") . "</a>".
         "   </td></tr>\n";
         "</table><br>\n";
} else {
    echo "<br>";
}
$imapConnection = sqimap_login ($username, $key, $imapServerAddress, $imapPort, 0);
$boxes = sqimap_mailbox_list($imapConnection);

/** CREATING FOLDERS **/
echo "<TABLE WIDTH=\"70%\" COLS=1 ALIGN=CENTER cellpadding=4 cellspacing=0 border=0>\n".
     "<TR><TD BGCOLOR=\"$color[9]\" ALIGN=CENTER><B>".
     _("Create Folder").
     "</B></TD></TR>".
     "<TR><TD BGCOLOR=\"$color[0]\" ALIGN=CENTER>".
     "<FORM NAME=cf ACTION=\"folders_create.php\" METHOD=\"POST\">\n".
     "<INPUT TYPE=TEXT SIZE=25 NAME=folder_name><BR>\n".
     _("as a subfolder of").
     "<BR>".
     "<TT><SELECT NAME=subfolder>\n";
if ($default_sub_of_inbox == false) {
    echo '<OPTION SELECTED VALUE="">[ '._("None")." ]\n";
} else {
    echo '<OPTION VALUE="">[ '._("None")." ]\n";
}

for ($i = 0; $i < count($boxes); $i++) {
    if (!in_array('noinferiors', $boxes[$i]['flags'])) {
        if ((strtolower($boxes[$i]['unformatted']) == 'inbox') &&
            $default_sub_of_inbox) {
            $box = $boxes[$i]['unformatted'];
            $box2 = str_replace(' ', '&nbsp;', $boxes[$i]['unformatted-disp']);
            echo "<OPTION SELECTED VALUE=\"$box\">$box2</option>\n";
        } else {
            $box = $boxes[$i]['unformatted'];
            $box2 = str_replace(' ', '&nbsp;', $boxes[$i]['unformatted-disp']);
            if (strtolower($imap_server_type) != 'courier' ||
                  strtolower($box) != "inbox.trash")
                echo "<OPTION VALUE=\"$box\">$box2</option>\n";
        }
    }
}
echo "</SELECT></TT>\n";
if ($show_contain_subfolders_option) {
    echo "<br><INPUT TYPE=CHECKBOX NAME=\"contain_subs\"> &nbsp;";
    echo _("Let this folder contain subfolders");
    echo "<BR>";
}
echo "<INPUT TYPE=SUBMIT VALUE=\""._("Create")."\">\n";
echo "</FORM></TD></TR>\n";

echo "<tr><td bgcolor=\"$color[4]\">&nbsp;</td></tr>\n";


/** count special folders **/
$count_special_folders = 0;
$num_max = 1;
if (strtolower($imap_server_type) == "courier" || $move_to_trash) {
        $num_max++;
}
if ($move_to_sent) {
        $num_max++;
}
if ($save_as_draft) {
        $num_max++;
}
for ($p = 0; $p < count($boxes) && $count_special_folders < $num_max; $p++) {
    if (strtolower($boxes[$p]['unformatted']) == 'inbox')
        $count_special_folders++;
    else if (strtolower($imap_server_type) == 'courier' &&
            strtolower($boxes[$p]['unformatted']) == 'inbox.trash')
        $count_special_folders++;
    else if ($boxes[$p]['unformatted'] == $trash_folder && $trash_folder)
        $count_special_folders++;
    else if ($boxes[$p]['unformatted'] == $sent_folder && $sent_folder)
        $count_special_folders++;
    else if ($boxes[$p]['unformatted'] == $draft_folder && $draft_folder)
        $count_special_folders++;
}


/** RENAMING FOLDERS **/
echo "<TR><TD BGCOLOR=\"$color[9]\" ALIGN=CENTER><B>".
     _("Rename a Folder").
     "</B></TD></TR>".
     "<TR><TD BGCOLOR=\"$color[0]\" ALIGN=CENTER>";
if ($count_special_folders < count($boxes)) {
    echo "<FORM ACTION=\"folders_rename_getname.php\" METHOD=\"POST\">\n"
       . "<TT><SELECT NAME=old>\n"
       . '         <OPTION VALUE="">[ ' . _("Select a folder") . " ]</OPTION>\n";
    for ($i = 0; $i < count($boxes); $i++) {
        $use_folder = true;

        if ((strtolower($boxes[$i]['unformatted']) != 'inbox') &&
            ($boxes[$i]['unformatted'] != $trash_folder)  &&
            ($boxes[$i]['unformatted'] != $sent_folder) &&
            ($boxes[$i]['unformatted'] != $draft_folder)) {
            $box = $boxes[$i]['unformatted-dm'];

            $box2 = str_replace(' ', '&nbsp;', $boxes[$i]['unformatted-disp']);
            if (strtolower($imap_server_type) != 'courier' || strtolower($box) != 'inbox.trash') {
                echo "<OPTION VALUE=\"$box\">$box2</option>\n";
            }
        }
    }
    echo "</SELECT></TT>\n".
         "<INPUT TYPE=SUBMIT VALUE=\"".
         _("Rename").
         "\">\n".
         "</FORM></TD></TR>\n";
} else {
    echo _("No folders found") . "<br><br></td></tr>";
}
$boxes_sub = $boxes;

echo "<tr><td bgcolor=\"$color[4]\">&nbsp;</td></tr>\n";

/** DELETING FOLDERS **/
echo "<TR><TD BGCOLOR=\"$color[9]\" ALIGN=CENTER><B>";
echo _("Delete Folder");
echo "</B></TD></TR>";
echo "<TR><TD BGCOLOR=\"$color[0]\" ALIGN=CENTER>";

if ($count_special_folders < count($boxes)) {
    echo "<FORM ACTION=\"folders_delete.php\" METHOD=\"POST\">\n"
       . "<TT><SELECT NAME=mailbox>\n"
       . '         <OPTION VALUE="">[ ' . _("Select a folder") . " ]</OPTION>\n";
    for ($i = 0; $i < count($boxes); $i++) {
        $use_folder = true;
        if ((strtolower($boxes[$i]['unformatted']) != 'inbox') &&
            ($boxes[$i]['unformatted'] != $trash_folder) &&
            ($boxes[$i]['unformatted'] != $sent_folder) &&
            ($boxes[$i]['unformatted'] != $draft_folder) &&
            (!in_array('noselect', $boxes[$i]['flags'])) &&
            ((strtolower($imap_server_type) != 'courier') ||
             (strtolower($boxes[$i]['unformatted']) != 'inbox.trash'))) {
            $box = $boxes[$i]['unformatted-dm'];
            $box2 = str_replace(' ', '&nbsp;', $boxes[$i]['unformatted-disp']);
            echo "         <OPTION VALUE=\"$box\">$box2</option>\n";
        }
    }
    echo "</SELECT></TT>\n";
    echo "<INPUT TYPE=SUBMIT VALUE=\"";
    echo _("Delete");
    echo "\">\n";
    echo "</FORM></TD></TR>\n";
} else {
    echo _("No folders found") . "<br><br></td><tr>";
}
echo "<tr><td bgcolor=\"$color[4]\">&nbsp;</td></tr></table>\n";

/** UNSUBSCRIBE FOLDERS **/
echo "<TABLE WIDTH=\"70%\" COLS=2 ALIGN=CENTER cellpadding=4 cellspacing=0 border=0>\n";
echo "<TR><TD BGCOLOR=\"$color[9]\" ALIGN=CENTER colspan=2><B>";
echo _("Unsubscribe") . "/" . _("Subscribe");
echo "</B></TD></TR>\n";
echo "<TR><TD BGCOLOR=\"$color[0]\" width=\"50%\" ALIGN=CENTER>\n";
if ($count_special_folders < count($boxes)) {
    echo "<FORM ACTION=\"folders_subscribe.php?method=unsub\" METHOD=\"POST\">\n";
    echo "<TT><SELECT NAME=\"mailbox[]\" multiple size=8>\n";
    for ($i = 0; $i < count($boxes); $i++) {
        $use_folder = true;
        if ((strtolower($boxes[$i]["unformatted"]) != "inbox") &&
            ($boxes[$i]["unformatted"] != $trash_folder) &&
            ($boxes[$i]["unformatted"] != $sent_folder) &&
            ($boxes[$i]["unformatted"] != $draft_folder)) {
            $box = $boxes[$i]["unformatted-dm"];
            $box2 = str_replace(' ', '&nbsp;', $boxes[$i]["unformatted-disp"]);
            echo "         <OPTION VALUE=\"$box\">$box2\n";
        }
    }
    echo "</SELECT></TT><br><br>\n";
    echo "<INPUT TYPE=SUBMIT VALUE=\"";
    echo _("Unsubscribe");
    echo "\">\n";
    echo "</FORM></TD>\n";
} else {
    echo _("No folders were found to unsubscribe from!") . "</td>";
}
$boxes_sub = $boxes;

/** SUBSCRIBE TO FOLDERS **/
echo "<TD BGCOLOR=\"$color[0]\" width=\"50%\" ALIGN=CENTER>";
$imap_stream = sqimap_login ($username, $key, $imapServerAddress, $imapPort, 1);
$boxes_all = sqimap_mailbox_list_all ($imap_stream);

$box = "";
$box2 = "";
for ($i = 0, $q = 0; $i < count($boxes_all); $i++) {
    $use_folder = true;
    for ($p = 0; $p < count ($boxes); $p++) {
        if ($boxes_all[$i]["unformatted"] == $boxes[$p]["unformatted"]) {
            $use_folder = false;
            continue;
        } else if ($boxes_all[$i]["unformatted-dm"] == $folder_prefix) {
            $use_folder = false;
        }
    }
    if ($use_folder == true) {
        $box[$q] = $boxes_all[$i]["unformatted-dm"];
        $box2[$q] = $boxes_all[$i]["unformatted-disp"];
        $q++;
    }
}
sqimap_logout($imap_stream);

if ($box && $box2) {
    echo "<FORM ACTION=\"folders_subscribe.php?method=sub\" METHOD=\"POST\">\n";
    echo "<tt><select name=\"mailbox[]\" multiple size=8>";

    for ($q = 0; $q < count($box); $q++) {      
       echo "         <OPTION VALUE=\"$box[$q]\">".$box2[$q]."\n";
    }      
    echo "</select></tt><br><br>";
    echo "<INPUT TYPE=SUBMIT VALUE=\"". _("Subscribe") . "\">\n";
    echo "</FORM></TD></TR></TABLE><BR>\n";
} else {
    echo _("No folders were found to subscribe to!") . "</td></tr></table>";
}

do_hook("folders_bottom");
?>


    </td></tr>
    </table>

</td></tr>
</table>

<?php
   sqimap_logout($imapConnection);
?>

</body></html>
