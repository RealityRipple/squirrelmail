<?php
/**
 * newmail.php
 *
 * Copyright (c) 1999-2005 The SquirrelMail Project Team
 * Copyright (c) 2000 by Michael Huttinger
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Quite a hack -- but my first attempt at a plugin.  We were
 * looking for a way to play a sound when there was unseen
 * messages to look at.  Nice for users who keep the squirrel
 * mail window up for long periods of time and want to know
 * when mail arrives.
 *
 * Basically, I hacked much of left_main.php into a plugin that
 * goes through each mail folder and increments a flag if
 * there are unseen messages.  If the final count of unseen
 * folders is > 0, then we play a sound (using the HTML at the
 * far end of this script).
 *
 * This was tested with IE5.0 - but I hear Netscape works well,
 * too (with a plugin).
 *
 * @version $Id$
 * @package plugins
 * @subpackage newmail
 */

/**
 * sqm_baseuri function for setups that don't load it by default
 */
include_once(SM_PATH . 'functions/display_messages.php');

/** Load plugin functions */
include_once(SM_PATH . 'plugins/newmail/functions.php');

/**
 * Checks if mailbox contains new messages.
 *
 * @param object $imapConnection
 * @param mixed $mailbox FIXME: option is not used
 * @param string $real_box unformated mailbox name
 * @param mixed $delimeter FIXME: option is not used
 * @param string $unseen FIXME: option is not used
 * @param integer $total_new number of new messages
 * @return bool true, if there are new messages
 */
function CheckNewMailboxSound($imapConnection, $mailbox, $real_box, $delimeter, $unseen, &$total_new) {
    global $trash_folder, $sent_folder,
        $unseen_notify, $newmail_allbox,
        $newmail_recent;

    $mailboxURL = urlencode($real_box);

    // Skip folders for Sent and Trash
    if ($real_box == $sent_folder ||
        $real_box == $trash_folder) {
        return 0;
    }

    if (($unseen_notify == 2 && $real_box == 'INBOX') ||
        ($unseen_notify == 3 && ($newmail_allbox == 'on' ||
                                 $real_box == 'INBOX'))) {
        $status = sqimap_status_messages( $imapConnection, $real_box);
        if($newmail_recent == 'on') {
            $total_new += $status['RECENT'];
        } else {
            $total_new += $status['UNSEEN'];
        }
        if ($total_new) {
            return 1;
        }
    }
    return 0;
}

/**
 * Init newmail plugin
 */
function squirrelmail_plugin_init_newmail() {
    global $squirrelmail_plugin_hooks;

    $squirrelmail_plugin_hooks['left_main_before']['newmail'] = 'newmail_plugin';
    $squirrelmail_plugin_hooks['optpage_register_block']['newmail'] = 'newmail_optpage_register_block';
    $squirrelmail_plugin_hooks['options_save']['newmail'] = 'newmail_sav';
    $squirrelmail_plugin_hooks['loading_prefs']['newmail'] = 'newmail_pref';
    $squirrelmail_plugin_hooks['optpage_set_loadinfo']['newmail'] = 'newmail_set_loadinfo';
}

/**
 * Register newmail option block
 */
function newmail_optpage_register_block() {
    // Gets added to the user's OPTIONS page.
    global $optpage_blocks;

    if ( checkForJavascript() ) {
        /* Register Squirrelspell with the $optionpages array. */
        $optpage_blocks[] = array(
            'name' => _("NewMail Options"),
            'url'  => SM_PATH . 'plugins/newmail/newmail_opt.php',
            'desc' => _("This configures settings for playing sounds and/or showing popup windows when new mail arrives."),
            'js'   => TRUE
            );
    }
}

/**
 * Save newmail plugin settings
 */
function newmail_sav() {
    global $data_dir, $username, $_FILES;

    if ( sqgetGlobalVar('submit_newmail', $submit, SQ_POST) ) {
        $media_enable = '';
        $media_popup = '';
        $media_allbox = '';
        $media_recent = '';
        $media_changetitle = '';
        $media_sel = '';

        sqgetGlobalVar('media_enable',      $media_enable,      SQ_POST);
        sqgetGlobalVar('media_popup',       $media_popup,       SQ_POST);
        sqgetGlobalVar('media_allbox',      $media_allbox,      SQ_POST);
        sqgetGlobalVar('media_recent',      $media_recent,      SQ_POST);
        sqgetGlobalVar('media_changetitle', $media_changetitle, SQ_POST);

        setPref($data_dir,$username,'newmail_enable',$media_enable);
        setPref($data_dir,$username,'newmail_popup', $media_popup);
        setPref($data_dir,$username,'newmail_allbox',$media_allbox);
        setPref($data_dir,$username,'newmail_recent',$media_recent);
        setPref($data_dir,$username,'newmail_changetitle',$media_changetitle);

        if( sqgetGlobalVar('media_sel', $media_sel, SQ_POST) &&
            $media_sel == '(none)' ) {
            removePref($data_dir,$username,'newmail_media');
        } else {
            setPref($data_dir,$username,'newmail_media',$media_sel);
        }

        // process uploaded file
        if (isset($_FILES['media_file']['tmp_name']) && $_FILES['media_file']['tmp_name']!='') {
            // set temp file and get media file name
            $newmail_tempmedia=getHashedDir($username, $data_dir) . "/$username.tempsound";
            $newmail_mediafile=getHashedFile($username, $data_dir, $username . '.sound');
            if (move_uploaded_file($_FILES['media_file']['tmp_name'], $newmail_tempmedia)) {
                // new media file is in $newmail_tempmedia
                if (file_exists($newmail_mediafile)) unlink($newmail_mediafile);
                if (! rename($newmail_tempmedia,$newmail_mediafile)) {
                    // remove (userfile), if file rename fails
                    removePref($data_dir,$username,'newmail_media');
                } else {
                    // store media type
                    if (isset($_FILES['media_file']['type']) && isset($_FILES['media_file']['name'])) {
                        setPref($data_dir,$username,'newmail_userfile_type',
			    newmail_get_mediatype($_FILES['media_file']['type'],$_FILES['media_file']['name']));
                    } else {
                        removePref($data_dir,$username,'newmail_userfile_type');
                    }
                    // store file name
                    if (isset($_FILES['media_file']['name'])) {
                        setPref($data_dir,$username,'newmail_userfile_name',basename($_FILES['media_file']['name']));
                    } else {
                        setPref($data_dir,$username,'newmail_userfile_name','mediafile.unknown');
                    }

                }
            }
        }
    }
}

/**
 * Load newmail plugin settings
 */
function newmail_pref() {
    global $username,$data_dir;
    global $newmail_media,$newmail_enable,$newmail_popup,$newmail_allbox;
    global $newmail_recent, $newmail_changetitle;
    global $newmail_userfile_type;

    $newmail_recent = getPref($data_dir,$username,'newmail_recent');
    $newmail_enable = getPref($data_dir,$username,'newmail_enable');
    $newmail_media = getPref($data_dir, $username, 'newmail_media', '(none)');
    $newmail_popup = getPref($data_dir, $username, 'newmail_popup');
    $newmail_allbox = getPref($data_dir, $username, 'newmail_allbox');
    $newmail_changetitle = getPref($data_dir, $username, 'newmail_changetitle');

    $newmail_userfile_type = getPref($data_dir, $username, 'newmail_userfile_type');
}

/**
 * Set loadinfo data
 *
 * Used by option page when saving settings.
 */
function newmail_set_loadinfo() {
    global $optpage, $optpage_name;
    if ($optpage=='newmail') {
        $optpage_name=_("NewMail Options");
    }
}

/**
 * Insert needed data in left_main
 */
function newmail_plugin() {
    global $username, $newmail_media, $newmail_enable, $newmail_popup,
        $newmail_recent, $newmail_changetitle, $imapConnection, $PHP_SELF;
    global $newmail_mmedia;
    global $newmail_userfile_type;

    if ($newmail_enable == 'on' ||
        $newmail_popup == 'on' ||
        $newmail_changetitle) {

        // open a connection on the imap port (143)

        $boxes = sqimap_mailbox_list($imapConnection);
        $delimeter = sqimap_get_delimiter($imapConnection);

        $status = 0;
        $totalNew = 0;

        for ($i = 0;$i < count($boxes); $i++) {

            $mailbox = $boxes[$i]['formatted'];

            if (! isset($boxes[$i]['unseen'])) {
                $boxes[$i]['unseen'] = '';
            }
            if ($boxes[$i]['flags']) {
                $noselect = false;
                for ($h = 0; $h < count($boxes[$i]['flags']); $h++) {
                    if (strtolower($boxes[$i]["flags"][$h]) == 'noselect') {
                        $noselect = TRUE;
                    }
                }
                if (! $noselect) {
                    $status += CheckNewMailboxSound($imapConnection,
                                                    $mailbox,
                                                    $boxes[$i]['unformatted'],
                                                    $delimeter,
                                                    $boxes[$i]['unseen'],
                                                    $totalNew);
                }
            } else {
                $status += CheckNewMailboxSound($imapConnection,
                                                $mailbox,
                                                $boxes[$i]['unformatted'],
                                                $delimeter,
                                                $boxes[$i]['unseen'],
                                                $totalNew);
            }
        }

        // sqimap_logout($imapConnection);

        // If we found unseen messages, then we
        // will play the sound as follows:

        if ($newmail_changetitle) {
            echo "<script language=\"javascript\">\n" .
                "function ChangeTitleLoad() {\n";
            echo 'window.parent.document.title = "' .
                sprintf(ngettext("%s New Message","%s New Messages",$totalNew), $totalNew) .
                "\";\n";
            echo    "if (BeforeChangeTitle != null)\n".
                "BeforeChangeTitle();\n".
                "}\n".
                "BeforeChangeTitle = window.onload;\n".
                "window.onload = ChangeTitleLoad;\n".
                "</script>\n";
        }

        // create media output if there are new email messages
        if ($totalNew > 0 && $newmail_enable == 'on' && $newmail_media != '' ) {
            echo newmail_create_media_tags($newmail_media);
        }

        if ($totalNew > 0 && $newmail_popup == 'on') {
            echo "<script language=\"JavaScript\">\n".
                "<!--\n".
                "function PopupScriptLoad() {\n".
                'window.open("'.sqm_baseuri().'plugins/newmail/newmail.php?numnew='.$totalNew.
                '", "SMPopup",'.
                "\"width=200,height=130,scrollbars=no\");\n".
                "if (BeforePopupScript != null)\n".
                "BeforePopupScript();\n".
                "}\n".
                "BeforePopupScript = window.onload;\n".
                "window.onload = PopupScriptLoad;\n".
                // Idea by:  Nic Wolfe (Nic@TimelapseProductions.com)
                // Web URL:  http://fineline.xs.mw
                // More code from Tyler Akins
                "// End -->\n".
                "</script>\n";
        }
    }
}
?>