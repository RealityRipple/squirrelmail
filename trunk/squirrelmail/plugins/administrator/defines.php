<?PHP

/**
 * defines.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Philippe Mingo
 *
 * $Id$
 */


/* Define constants for the various option types. */
define('SMOPT_TYPE_UNDEFINED', -1);
define('SMOPT_TYPE_STRING', 0);
define('SMOPT_TYPE_STRLIST', 1);
define('SMOPT_TYPE_TEXTAREA', 2);
define('SMOPT_TYPE_INTEGER', 3);
define('SMOPT_TYPE_FLOAT', 4);
define('SMOPT_TYPE_BOOLEAN', 5);
define('SMOPT_TYPE_HIDDEN', 6);
define('SMOPT_TYPE_COMMENT', 7);
define('SMOPT_TYPE_NUMLIST', 8);
define('SMOPT_TYPE_TITLE', 128);

/* Define constants for the options refresh levels. */
define('SMOPT_REFRESH_NONE', 0);
define('SMOPT_REFRESH_FOLDERLIST', 1);
define('SMOPT_REFRESH_ALL', 2);

/* Define constants for the options size. */
define('SMOPT_SIZE_TINY', 0);
define('SMOPT_SIZE_SMALL', 1);
define('SMOPT_SIZE_MEDIUM', 2);
define('SMOPT_SIZE_LARGE', 3);
define('SMOPT_SIZE_HUGE', 4);

define('SMOPT_SAVE_DEFAULT', 'save_option');
define('SMOPT_SAVE_NOOP', 'save_option_noop');

global $languages;

$language_values = array( );
foreach ($languages as $lang_key => $lang_attributes) {
    if (isset($lang_attributes['NAME'])) {
        $language_values[$lang_key] = $lang_attributes['NAME'];
    }
}
asort( $language_values );
$language_values = array_merge(array('' => _("Default")), $language_values);
$left_size_values = array();
for ($lsv = 100; $lsv <= 300; $lsv += 10) {
    $left_size_values[$lsv] = "$lsv " . _("pixels");
}

$defcfg = array( '$config_version' => array( 'name' => _("Config File Version"),
                                             'type' => SMOPT_TYPE_COMMENT,
                                             'size' => 7 ),
                 /* --------------------------------------------------------*/
                 'Group1' => array( 'name' => _("Organization Preferences"),
                                    'type' => SMOPT_TYPE_TITLE ),
                 '$org_name' => array( 'name' => _("Organization Name"),
                                       'type' => SMOPT_TYPE_STRING,
                                       'size' => 40 ),
                 '$org_logo' => array( 'name' => _("Organization Logo"),
                                       'type' => SMOPT_TYPE_STRING,
                                       'size' => 40 ),
                 '$org_title' => array( 'name' => _("Organization Title"),
                                        'type' => SMOPT_TYPE_STRING,
                                        'size' => 40 ),
                 '$signout_page' => array( 'name' => _("Signout Page"),
                                           'type' => SMOPT_TYPE_STRING,
                                           'size' => 40 ),
                 '$squirrelmail_default_language' => array( 'name' => _("Default Language"),
                                                            'type' => SMOPT_TYPE_STRLIST,
                                                            'size' => 7,
                                                            'posvals' => $language_values ),
                 '$frame_top' => array( 'name' => _("Top Frame"),
                                        'type' => SMOPT_TYPE_STRING,
                                        'size' => 40 ),
                 /* --------------------------------------------------------*/
                 'Group2' => array( 'name' => _("Server Settings"),
                                    'type' => SMOPT_TYPE_TITLE ),
                 '$domain' => array( 'name' => _("Mail Domain"),
                                                'type' => SMOPT_TYPE_STRING,
                                                'size' => 40 ),
                 '$imapServerAddress' => array( 'name' => _("IMAP Server Address"),
                                                'type' => SMOPT_TYPE_STRING,
                                                'size' => 40 ),
                 '$imapPort' => array( 'name' => _("IMAP Server Port"),
                                                 'type' => SMOPT_TYPE_INTEGER ),
                 '$imap_server_type' => array( 'name' => _("IMAP Server Type"),
                                               'type' => SMOPT_TYPE_STRLIST,
                                               'posvals' => array( 'cyrus' => _("Cyrus IMAP server"),
                                                                   'uw' => _("University of Washington's IMAP server"),
                                                                   'exchange' => _("Microsoft Exchange IMAP server"),
                                                                   'courier' => _("Courier IMAP server"),
                                                                   'other' => _("Not one of the above servers") ) ),
                 '$optional_delimiter' => array( 'name' => _("IMAP Folder Delimiter"),
                                                 'type' => SMOPT_TYPE_STRING,
                                                 'size' => 2 ),
                 '$useSendmail' => array( 'name' => _("Use Sendmail"),
                                          'type' => SMOPT_TYPE_BOOLEAN ),
                 '$sendmail_path' => array( 'name' => _("Sendmail Path"),
                                            'type' => SMOPT_TYPE_STRING,
                                            'size' => 40 ),
                 '$smtpServerAddress' => array( 'name' => _("SMTP Server Address"),
                                                'type' => SMOPT_TYPE_STRING,
                                                'size' => 40 ),
                 '$smtpPort' => array( 'name' => _("SMTP Server Port"),
                                                 'type' => SMOPT_TYPE_INTEGER ),
                 '$use_authenticated_smtp' => array( 'name' => _("Authenticated SMTP"),
                                                     'type' => SMOPT_TYPE_BOOLEAN ),
                 '$invert_time' => array( 'name' => _("Invert Time"),
                                          'type' => SMOPT_TYPE_BOOLEAN ),
                 /* --------------------------------------------------------*/
                 'Group3' => array( 'name' => _("Folders Defaults"),
                                    'type' => SMOPT_TYPE_TITLE ),
                 '$default_folder_prefix' => array( 'name' => _("Default Folder Prefix"),
                                                    'type' => SMOPT_TYPE_STRING,
                                                    'size' => 40 ),
                 '$show_prefix_option' => array( 'name' => _("Show Folder Prefix Option"),
                                                 'type' => SMOPT_TYPE_BOOLEAN ),
                 '$trash_folder' => array( 'name' => _("Trash Folder"),
                                           'type' => SMOPT_TYPE_STRING,
                                           'size' => 40 ),
                 '$sent_folder' => array( 'name' => _("Sent Folder"),
                                          'type' => SMOPT_TYPE_STRING,
                                          'size' => 40 ),
                 '$draft_folder' => array( 'name' => _("Draft Folder"),
                                           'type' => SMOPT_TYPE_STRING,
                                           'size' => 40 ),
                 '$default_move_to_trash' => array( 'name' => _("By default, move to trash"),
                                                    'type' => SMOPT_TYPE_BOOLEAN ),
                 '$default_move_to_sent' => array( 'name' => _("By default, move to sent"),
                                                   'type' => SMOPT_TYPE_BOOLEAN ),
                 '$default_save_as_draft' => array( 'name' => _("By default, save as draft"),
                                                   'type' => SMOPT_TYPE_BOOLEAN ),
                 '$list_special_folders_first' => array( 'name' => _("List Special Folders First"),
                                                         'type' => SMOPT_TYPE_BOOLEAN ),
                 '$use_special_folder_color' => array( 'name' => _("Show Special Folders Color"),
                                                       'type' => SMOPT_TYPE_BOOLEAN ),
                 '$auto_expunge' => array( 'name' => _("Auto Expunge"),
                                           'type' => SMOPT_TYPE_BOOLEAN ),
                 '$default_sub_of_inbox' => array( 'name' => _("Default Sub. of INBOX"),
                                                   'type' => SMOPT_TYPE_BOOLEAN ),
                 '$show_contain_subfolders_option' => array( 'name' => _("Show 'Contain Sub.' Option"),
                                                             'type' => SMOPT_TYPE_BOOLEAN ),
                 '$default_unseen_notify' => array( 'name' => _("Default Unseen Notify"),
                                                    'type' => SMOPT_TYPE_INTEGER ),
                 '$default_unseen_type'  => array( 'name' => _("Default Unseen Type"),
                                                   'type' => SMOPT_TYPE_INTEGER ),
                 '$auto_create_special' => array( 'name' => _("Auto Create Special Folders"),
                                                  'type' => SMOPT_TYPE_BOOLEAN ),
                 /* --------------------------------------------------------*/
                 'Group4' => array( 'name' => _("General Options"),
                                    'type' => SMOPT_TYPE_TITLE ),
                 '$default_charset' => array( 'name' => _("Default Charset"),
                                              'type' => SMOPT_TYPE_STRING,
                                              'size' => 10 ),
                 '$data_dir' => array( 'name' => _("Data Directory"),
                                       'type' => SMOPT_TYPE_STRING,
                                       'size' => 40 ),
                 '$attachment_dir' => array( 'name' => _("Temp Directory"),
                                             'type' => SMOPT_TYPE_STRING,
                                             'size' => 40 ),
                 '$dir_hash_level' => array( 'name' => _("Hash Level"),
                                             'type' => SMOPT_TYPE_NUMLIST,
                                             'posvals' => array( 0 => _("Hash Disabled"),
                                                                 1 => _("Low"),
                                                                 2 => _("Moderate"),
                                                                 3 => _("Medium"),
                                                                 4 => _("High") ) ),
                 '$default_left_size' => array( 'name' => _("Hash Level"),
                                                'type' => SMOPT_TYPE_NUMLIST,
                                                'posvals' => $left_size_values ),
                 '$force_username_lowercase' => array( 'name' => _("Usernames in Lowercase"),
                                                       'type' => SMOPT_TYPE_BOOLEAN ),
                 '$default_use_priority'  => array( 'name' => _("Allow use of priority"),
                                                    'type' => SMOPT_TYPE_BOOLEAN ),
                 '$hide_sm_attributions' => array( 'name' => _("Hide SM attributions"),
                                                   'type' => SMOPT_TYPE_BOOLEAN ),
                 /* --------------------------------------------------------*/
                 'Group5' => array( 'name' => _("Themes"),
                                    'type' => SMOPT_TYPE_TITLE )
               );


$defcfg['$motd'] = array( 'name' => _("Message of the Day"),
                          'type' => SMOPT_TYPE_STRING,
                          'size' => 40 );

?>
