<?php

/**
 * folders_rename_getname.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Gets folder names and enables renaming
 * Called from folders.php
 *
 * $Id$
 */

/* Path for SquirrelMail required files. */
define('SM_PATH','../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'functions/imap_mailbox.php');
require_once(SM_PATH . 'functions/html.php');
require_once(SM_PATH . 'functions/display_messages.php');

/* get globals we may need */

$username = $_SESSION['username'];
$key = $_COOKIE['key'];
$delimiter = $_SESSION['delimiter'];
$onetimepad = $_SESSION['onetimepad'];

$old = $_POST['old'];
    
/* end of get globals */

if ($old == '') {
    displayPageHeader($color, 'None');

    plain_error_message(_("You have not selected a folder to rename. Please do so.").
        '<BR><A HREF="../src/folders.php">'._("Click here to go back").'</A>.', $color);
    exit;
}

if (substr($old, strlen($old) - strlen($delimiter)) == $delimiter) {
    $isfolder = TRUE;
    $old = substr($old, 0, strlen($old) - 1);
} else {
    $isfolder = FALSE;
}

$old = imap_utf7_decode_local($old);

if (strpos($old, $delimiter)) {
    $old_name = substr($old, strrpos($old, $delimiter)+1, strlen($old));
    $old_parent = substr($old, 0, strrpos($old, $delimiter));
} else {
    $old_name = $old;
    $old_parent = '';
}


displayPageHeader($color, 'None');
echo '<br>' .
    html_tag( 'table', '', 'center', '', 'width="95%" border="0"' ) .
        html_tag( 'tr',
            html_tag( 'td', '<b>' . _("Rename a folder") . '</b>', 'center', $color[0] )
        ) .
        html_tag( 'tr' ) .
            html_tag( 'td', '', 'center', $color[4] ) .
            '<FORM ACTION="folders_rename_do.php" METHOD="POST">'.
     _("New name:").
     "<br><B>$old_parent $delimiter </B><INPUT TYPE=TEXT SIZE=25 NAME=new_name VALUE=\"$old_name\"><BR>\n";
if ( $isfolder ) {
    echo '<INPUT TYPE=HIDDEN NAME="isfolder" VALUE="true">';
}
printf("<INPUT TYPE=HIDDEN NAME=\"orig\" VALUE=\"%s\">\n", $old);
printf("<INPUT TYPE=HIDDEN NAME=\"old_name\" VALUE=\"%s\">\n", $old_name);
echo '<INPUT TYPE=SUBMIT VALUE="'._("Submit")."\">\n".
     '</FORM><BR></td></tr></table>';

?>
