<?php

/**
 * folders_rename_getname.php
 *
 * Copyright (c) 1999-2004 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Gets folder names and enables renaming
 * Called from folders.php
 *
 * $Id$
 * @package squirrelmail
 */

/** Path for SquirrelMail required files. */
define('SM_PATH','../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'functions/global.php');
require_once(SM_PATH . 'functions/imap_mailbox.php');
require_once(SM_PATH . 'functions/html.php');
require_once(SM_PATH . 'functions/display_messages.php');
require_once(SM_PATH . 'functions/forms.php');

/* get globals we may need */
sqgetGlobalVar('key',       $key,           SQ_COOKIE);
sqgetGlobalVar('username',  $username,      SQ_SESSION);
sqgetGlobalVar('onetimepad',$onetimepad,    SQ_SESSION);
sqgetGlobalVar('delimiter', $delimiter,     SQ_SESSION);
sqgetGlobalVar('old',       $old,           SQ_POST);
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
	    addForm('folders_rename_do.php').
     _("New name:").
     '<br><b>' . htmlspecialchars($old_parent) . ' ' . htmlspecialchars($delimiter) . '</b>' .
     addInput('new_name', $old_name, 25) . '<BR>' . "\n";
if ( $isfolder ) {
    echo addHidden('isfolder', 'true');
}
echo addHidden('orig', $old).
     addHidden('old_name', $old_name).
     '<INPUT TYPE=SUBMIT VALUE="'._("Submit")."\">\n".
     '</FORM><BR></td></tr></table>';

?>
