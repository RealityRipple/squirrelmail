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

$language_values = array();
foreach ($languages as $lang_key => $lang_attributes) {
    if (isset($lang_attributes['NAME'])) {
        $language_values[$lang_key] = $lang_attributes['NAME'];
    }
}
asort( $language_values );

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
                                               'size' => 7,
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
                 '$motd' => array( 'name' => _("Message of the Day"),
                                             'type' => SMOPT_TYPE_STRING,
                                             'size' => 40 ),

               );

?>