<?php

/**
 * newmails_opt.php
 *
 * Copyright (c) 1999-2003 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Displays all options relating to new mail sounds
 *
 * $Id$
 */

define('SM_PATH','../../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'functions/page_header.php');
require_once(SM_PATH . 'functions/display_messages.php');
require_once(SM_PATH . 'functions/imap.php');
require_once(SM_PATH . 'include/load_prefs.php');

displayPageHeader($color, 'None');

$media_enable = getPref($data_dir,$username, 'newmail_enable', 'FALSE' );
$media_popup = getPref($data_dir, $username,'newmail_popup');
$media_allbox = getPref($data_dir,$username,'newmail_allbox');
$media_recent = getPref($data_dir,$username,'newmail_recent');
$media_changetitle = getPref($data_dir,$username,'newmail_changetitle');
$media = getPref($data_dir,$username,'newmail_media', '(none)');

// Set $allowsound to false if you don't want sound files available
$allowsound = "true";

echo html_tag( 'table', '', 'center', '', 'width="95%" border="0" cellpadding="1" cellspacing="0"' ) . "\n" .
         html_tag( 'tr', "\n" .
                 html_tag( 'td', '<b>' . _("Options") . ' - ' . _("New Mail Notification") . '</b>', 'center', $color[0] )
            ) . "\n" .
             html_tag( 'tr' ) . "\n" .
                 html_tag( 'td', '', 'left' );
if ($allowsound == "true") {
     echo html_tag( 'p',
            _("Select <b>Enable Media Playing</b> to turn on playing a media file when unseen mail is in your folders. When enabled, you can specify the media file to play in the provided file box.")
          ) . "\n";
}
      echo html_tag( 'p',
             _("The <b>Check all boxes, not just INBOX</b> option will check ALL of your folders for unseen mail, not just the inbox for notification.")
           ) . "\n" .
           html_tag( 'p',
               _("Selecting the <b>Show popup</b> option will enable the showing of a popup window when unseen mail is in your folders (requires JavaScript).")
           ) . "\n" .
           html_tag( 'p',
               _("Use the <b>Check RECENT</b> to only check for messages that are recent. Recent messages are those that have just recently showed up and have not been \"viewed\" or checked yet.  This can prevent being continuously annoyed by sounds or popups for unseen mail.")
           ) . "\n" .
           html_tag( 'p',
               _("Selecting the <b>Change title</b> option will change the title in some browsers to let you know when you have new mail (requires JavaScript, and only works in IE but you won't see errors with other browsers).  This will always tell you if you have new mail, even if you have <b>Check RECENT</b> enabled.")
           );
    if ($allowsound == "true") {
        echo html_tag( 'p',
                    _("Select from the list of <b>server files</b> the media file to play when new mail arrives.  If no file is specified, \"(none)\", no sound will be used.")
               ) . "\n";
    }
    echo '<form action="'.sqm_baseuri().'src/options.php" method=post>'.
         html_tag( 'table', '', '', '', 'width="100%" cellpadding="0" cellspacing="2" border="0"' ) . "\n" .
             html_tag( 'tr' ) . "\n" .
                 html_tag( 'td', '&nbsp;', 'right', '', 'nowrap' ) . "\n";
    if ($allowsound == "true") {
                echo html_tag( 'td', '', 'left' ) . 
                     '<input type="checkbox" ';
        if ($media_enable == 'on') {
                echo 'checked ';
        }
        echo 'name="media_enable"><b> ' . _("Enable Media Playing") . '</b></td>'.
                '</tr>' . "\n";
    }  
            echo html_tag( 'tr' ) . "\n" .
                html_tag( 'td', '&nbsp;', 'right', '', 'nowrap' ) . "\n" .
                html_tag( 'td', '', 'left' ) .
                    '<input type="checkbox" ';
    if ($media_allbox == 'on') {
              echo 'checked ';
    }
    echo 'name="media_allbox"><b> ' . _("Check all boxes, not just INBOX") . '</b></td>'.
            '</tr>'.  "\n" .
            html_tag( 'tr' ) . "\n" .
                html_tag( 'td', '&nbsp;', 'right', '', 'nowrap' ) . "\n" .
                html_tag( 'td', '', 'left' ) .
                    '<input type="checkbox" ';
    if ($media_recent == 'on') {
              echo 'checked ';
    }
    echo 'name="media_recent"><b> ' . _("Count only messages that are RECENT") . '</b></td>'.
            '</tr>'. "\n" .
            html_tag( 'tr' ) . "\n" .
                html_tag( 'td', '&nbsp;', 'right', '', 'nowrap' ) . "\n" .
                html_tag( 'td', '', 'left' ) .
                    '<input type="checkbox" ';
    if ($media_changetitle == 'on') {
              echo 'checked ';
    }
    echo 'name="media_changetitle"><b> ' . _("Change title on supported browsers.") . '</b> &nbsp; (' . _("requires JavaScript to work") . ')</td>'.
            '</tr>'. "\n" .
            html_tag( 'tr' ) . "\n" .
                html_tag( 'td', '&nbsp;', 'right', '', 'nowrap' ) . "\n" .
                html_tag( 'td', '', 'left' ) .
                    '<input type="checkbox" ';
    if($media_popup == 'on') {
              echo 'checked ';
    }
    echo 'name="media_popup"><b> ' . _("Show popup window on new mail") . '</b> &nbsp; (' . _("requires JavaScript to work") . ')</td>'.
            '</tr>' . "\n";
    if ($allowsound == "true") {
            echo html_tag( 'tr' ) . "\n" .
                        html_tag( 'td', _("Select server file:"), 'right', '', 'nowrap' ) . "\n" .
                        html_tag( 'td', '', 'left' ) .
                            '<select name="media_sel">'.  "\n".
                            '<option value="(none)"';
            if ( $media == '(none)') {
                echo 'selected ';
            }
            echo '>' . _("(none)") . '</option>' .  "\n";

    // Iterate sound files for options
    $d = dir(SM_PATH . 'plugins/newmail/sounds');
    while($entry=$d->read()) {
        $fname = get_location () . '/sounds/' . $entry;
        if ($entry != '..' && $entry != '.' && $entry != 'CVS') {
            echo '<option ';
            if ($fname == $media) {
                echo 'selected ';
            }
            echo 'value="' . $fname . '">' . $entry . "</option>\n";
        }
    }
    $d->close();
    echo '</select>'.
               '<input type="submit" value=" ' . _("Try") . ' " name="test" onClick="'.
                    "window.open('testsound.php?sound='+media_sel.options[media_sel.selectedIndex].value, 'TestSound',".
                    "'width=150,height=30,scrollbars=no');".
                    'return false;'.
               '">'.
            '</td>'.
         '</tr>'.
         html_tag( 'tr', "\n" .
             html_tag( 'td', _("Current File:"), 'right', '', 'nowrap' ) .
             html_tag( 'td', '<input type="hidden" value="' . $media . '" name="media_default">' . $media . '', 'left' )
         ) . "\n";
    }
         echo html_tag( 'tr', "\n" .
                     html_tag( 'td', '&nbsp;' ) .
                     html_tag( 'td',
                         '<input type="hidden" name="optmode" value="submit">'. 
                         '<input type="submit" value="' . _("Submit") . '" name="submit_newmail">',
                     'left' )
                 ) . "\n" .
      '</table>'. "\n" .
   '</form>'. "\n" .
   '</td></tr></table>'. "\n" .
'</body></html>';

?>
