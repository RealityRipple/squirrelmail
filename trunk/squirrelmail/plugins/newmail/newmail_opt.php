<?php

/**
 * newmails_opt.php - options page
 *
 * Displays all options relating to new mail sounds
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage newmail
 */

/**
 * Path for SquirrelMail required files.
 * @ignore
 */
require('../../include/init.php');

/**
 * Make sure plugin is activated!
 */
global $plugins;
if (!in_array('newmail', $plugins))
   exit;

/** Plugin functions (also loads plugin's config) */
include_once(SM_PATH . 'plugins/newmail/functions.php');

include_once(SM_PATH . 'functions/forms.php');

displayPageHeader($color);

//FIXME: Remove all HTML from core - put this all into a template file
echo html_tag( 'table', '', 'center', $color[0], 'width="95%" cellpadding="1" cellspacing="0" border="0"' ) . "\n" .
        html_tag( 'tr' ) . "\n" .
            html_tag( 'td', '', 'center' ) .
                '<b>' . _("Options") . ' - ' . _("New Mail Notification") . "</b><br />\n" .
                html_tag( 'table', '', '', '', 'width="100%" cellpadding="5" cellspacing="0" border="0"' ) . "\n" .
                    html_tag( 'tr' ) . "\n" .
                        html_tag( 'td', '', 'left', $color[4] ) . "<br />\n";

echo html_tag( 'p', sprintf(_("Based on the Folder Preferences option %s, you can be notified when new messages arrive in your account."), '&quot;' . _("Enable Unread Message Notification") . '&quot;')) . "\n" .
     html_tag( 'p',
        sprintf(_("Selecting the %s option will enable the showing of a popup window when unseen mail is in one of your folders (requires JavaScript)."), '&quot;'._("Show popup window on new mail").'&quot;')
     ) . "\n" .
     html_tag( 'p',
        sprintf(_("Use the %s option to only check for messages that are recent. Recent messages are those that have just recently showed up and have not been \"viewed\" or checked yet. This can prevent being continuously annoyed by sounds or popups for unseen mail."), '&quot;'._("Count only messages that are RECENT").'&quot;')
     ) . "\n" .
     html_tag( 'p',
        sprintf(_("Selecting the %s option will change the browser title bar to let you know when you have new mail (requires JavaScript and may only work in some browsers). This will always tell you if you have new mail, even if you have %s enabled."), '&quot;'._("Change title on supported browsers").'&quot;', '&quot;'._("Count only messages that are RECENT").'&quot;')
     ) . "\n";
if ($newmail_allowsound) {
    echo html_tag( 'p',
            sprintf(_("Select %s to turn on playing a media file when unseen mail is in your folders. When enabled, you can specify the media file to play in the provided file box."), '&quot;'._("Enable Media Playing").'&quot;')
         ) . "\n" .
         html_tag( 'p',
            sprintf(_("Select from the list of %s the media file to play when new mail arrives. If no file is specified, %s, no sound will be used."), '&quot;'._("Select server file").'&quot;', '&quot;'._("(none)").'&quot;')
         ) . "\n";
}

echo '</td></tr>' .
        html_tag( 'tr' ) .
            html_tag( 'td', '', 'center', $color[4] ) . "\n" . '<hr style="width: 25%; height: 1px;" />' . "\n";

echo '<form action="'.sqm_baseuri().'src/options.php" method="post" enctype="multipart/form-data">' . "\n" .
        '<input type="hidden" name="smtoken" value="' . sm_generate_security_token() . '">' . "\n" .
        html_tag( 'table', '', '', '', 'width="100%" cellpadding="5" cellspacing="0" border="0"' ) . "\n";

/* newmail_unseen_notify */
$newmail_unseen_opts = array( 0 => _("Follow folder preferences"),
                              SMPREF_UNSEEN_INBOX => _("INBOX"),
                              SMPREF_UNSEEN_ALL => _("All folders"),
                              SMPREF_UNSEEN_SPECIAL => _("Special folders"),
                              SMPREF_UNSEEN_NORMAL => _("Regular folders"));
echo html_tag('tr',
              html_tag('td',_("Check for new messages in:"),'right', '', 'style="white-space: nowrap;"').
              html_tag('td',addSelect('newmail_unseen_notify',$newmail_unseen_opts,$newmail_unseen_notify,true),'left')
              );

// Option: media_recent
echo html_tag( 'tr' ) .
        html_tag( 'td', '<label for="media_recent">' . _("Count only messages that are RECENT") . ':</label>', 'right', '', 'style="white-space: nowrap;"' ) .
            html_tag( 'td', '', 'left' ) .
                '<input type="checkbox" ';
if ($newmail_recent == 'on') {
    echo 'checked="checked" ';
}
echo 'name="media_recent" id="media_recent" /></td></tr>' . "\n";

// Option: media_changetitle
echo html_tag( 'tr' ) .
        html_tag( 'td', '<label for="media_changetitle">' . _("Change title on supported browsers") . ':</label>', 'right', '', 'style="white-space: nowrap;"' ) .
            html_tag( 'td', '', 'left' ) .
                '<input type="checkbox" ';
if ($newmail_changetitle == 'on') {
    echo 'checked="checked" ';
}
echo 'name="media_changetitle" id="media_changetitle" />&nbsp;<small><label for="media_changetitle">('._("requires JavaScript to work").')</label></small></td></tr>' . "\n";

// Option: media_popup
echo html_tag( 'tr' ) .
        html_tag( 'td', '<label for="media_popup">' . _("Show popup window on new mail") . ':</label>', 'right', '', 'style="white-space: nowrap;"' ) .
            html_tag( 'td', '', 'left' ) .
                '<input type="checkbox" ';
if($newmail_popup == 'on') {
    echo 'checked="checked" ';
}
echo 'name="media_popup" id="media_popup" />&nbsp;<small><label for="media_popup">('._("requires JavaScript to work").')</label></small></td></tr>' . "\n";

echo html_tag( 'tr' )
     . html_tag('td',_("Width of popup window:"),'right','', 'style="white-space: nowrap;"')
     . html_tag('td','<input type="text" name="popup_width" value="'
                . (int)$newmail_popup_width . '" size="3" maxlengh="3" />'
                . '&nbsp;<small>(' . _("If set to 0, reverts to default value") . ')</small>','left')
     . "</tr>\n";

echo html_tag( 'tr' )
     . html_tag('td',_("Height of popup window:"),'right','', 'style="white-space: nowrap;"')
     . html_tag('td','<input type="text" name="popup_height" value="'
                . (int)$newmail_popup_height . '" size="3" maxlengh="3" />'
                . '&nbsp;<small>(' . _("If set to 0, reverts to default value") . ')</small>','left')
     . "</tr>\n";

if ($newmail_allowsound) {
// Option: media_enable
    echo html_tag( 'tr' ) .
            html_tag( 'td', '<label for="media_enable">' . _("Enable Media Playing") . ':</label>', 'right', '', 'style="white-space: nowrap;"' ) .
                html_tag( 'td', '', 'left' ) .
                    '<input type="checkbox" ';
    if ($newmail_media_enable == 'on') {
        echo 'checked="checked" ';
    }
    echo 'name="media_enable" id="media_enable" /></td></tr>' . "\n";

// Option: media_sel
    echo html_tag( 'tr' ) .
        html_tag( 'td', _("Select server file").':', 'right', '', 'style="white-space: nowrap;"' ) .
            html_tag( 'td', '', 'left' ) .
                '<select name="media_sel">' . "\n" .
                    '<option value="(none)"';
    if ( $newmail_media == '(none)') {
        echo 'selected="selected" ';
    }
    echo '>' . _("(none)") . '</option>' .  "\n";
    // Iterate sound files for options
    $d = dir(SM_PATH . 'plugins/newmail/sounds');
    if ($d) {
        while($entry=$d->read()) {
            // $fname = get_location () . '/sounds/' . $entry;
            if ($entry != '..' && $entry != '.' && $entry != 'CVS' && $entry != 'index.php') {
                echo '<option ';
                if ($entry == $newmail_media) {
                    echo 'selected="selected" ';
                }
                echo 'value="' . sm_encode_html_special_chars($entry) . '">' .
                    sm_encode_html_special_chars($entry) . "</option>\n";
            }
        }
        $d->close();
    }
    // display media selection
    foreach($newmail_mmedia as $newmail_mm_name => $newmail_mm_data) {
        echo '<option ';
        if ($newmail_media=='mmedia_' . $newmail_mm_name) {
            echo 'selected="selected" ';
        }
        echo 'value="mmedia_' . $newmail_mm_name . '">'
            .sm_encode_html_special_chars($newmail_mm_name) . "</option>\n";
    }

    if($newmail_uploadsounds) {
        // display local file option
        echo '<option ';
        if ($newmail_media=='(userfile)') {
            echo 'selected="selected" ';
        }
        echo 'value="(userfile)">'.
            _("uploaded media file") . "</option>\n";
        // end of local file option
    }

    // Set media file name
    if ($newmail_media == '(none)') {
        $media_output = _("none");
    } elseif ($newmail_media == '(userfile)') {
        $media_output = basename($newmail_userfile_name);
    } elseif (preg_match("/^mmedia_+/",$newmail_media)) {
        $media_output = preg_replace("/^mmedia_/",'',$newmail_media);
    } else {
        $media_output = basename($newmail_media);
    }

    echo '</select>'.
        '<input type="submit" value="' . _("Try") . '" name="test" onclick="' .
            "window.open('testsound.php?sound='+media_sel.options[media_sel.selectedIndex].value, 'TestSound'," .
            "'width=150,height=30,scrollbars=no');" .
            'return false;' .
            '" /></td></tr>';
    if ($newmail_uploadsounds) {
        // upload form
        echo  html_tag('tr')
            . html_tag('td',_("Upload Media File:"),'right','','style="white-space: nowrap;"')
            . html_tag('td','<input type="file" size="40" name="media_file" />')
            . "</tr>\n";
        // display currently uploaded file information
        echo  html_tag('tr')
            . html_tag('td',_("Uploaded Media File:"),'right','','style="white-space: nowrap;"')
            . html_tag('td',($newmail_userfile_name!='' ? sm_encode_html_special_chars($newmail_userfile_name) : _("unavailable")))
            ."</tr>\n";

        if ($newmail_userfile_name!='') {
            echo '<tr>'
                .'<td colspan="2" align="center">'
                .sprintf(_("Media file %s will be removed, if you upload other media file."),basename($newmail_userfile_name))
                .'</td></tr>';
        }
    }
    echo html_tag( 'tr', "\n" .
                html_tag( 'td', _("Current File:"), 'right', '', 'style="white-space: nowrap;"' ) .
                    html_tag( 'td', '<input type="hidden" value="' .
                        sm_encode_html_special_chars($newmail_media) . '" name="media_default" />' .
                        sm_encode_html_special_chars($media_output) . '', 'left' )
             ) . "\n";
}
echo html_tag( 'tr', "\n" .
    html_tag( 'td', '&nbsp;' ) .
        html_tag( 'td',
            '<input type="hidden" name="optmode" value="submit" />' .
            '<input type="hidden" name="optpage" value="newmail" />' .
        	'<input type="hidden" name="smtoken" value="' . sm_generate_security_token() . '" />' .
            '<input type="submit" value="' . _("Submit") . '" name="submit_newmail" />',
        'left' )
     ) . "\n";
?>
</table></form></td></tr></table></td></tr></table></body></html>
