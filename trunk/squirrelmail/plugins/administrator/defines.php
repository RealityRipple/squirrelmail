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

$namcfg = array( '$config_version' => array( 'name' => _("Config File Version"),
                                             'type' => 'string',
                                             'size' => 7 ),
                 '$org_logo' => array( 'name' => _("Organization Logo"),
                                       'type' => SMOPT_TYPE_STRING,
                                       'size' => 40 ),
                 '$org_name' => array( 'name' => _("Organization Name"),
                                       'type' => SMOPT_TYPE_STRING,
                                       'size' => 40 ),
                 '$org_title' => array( 'name' => _("Organization Name"),
                                        'type' => SMOPT_TYPE_STRING,
                                        'size' => 40 ),
                 '$squirrelmail_default_language' => array( 'name' => _("Default Language"),
                                                            'type' => SMOPT_TYPE_STRLIST,
                                                            'size' => 7,
                                                            'posvals' => $language_values ),
                 '$imapServerAddress' => array( 'name' => _("IMAP Server Address"),
                                                'type' => SMOPT_TYPE_STRING,
                                                'size' => 40 ),
                 '$imapPort' => array( 'name' => _("IMAP Server Port"),
                                                 'type' => SMOPT_TYPE_INTEGER ),
                 '$domain' => array( 'name' => _("Mail Domain"),
                                                'type' => SMOPT_TYPE_STRING,
                                                'size' => 40 ),
                 '$smtpServerAddress' => array( 'name' => _("SMTP Server Address"),
                                                'type' => SMOPT_TYPE_STRING,
                                                'size' => 40 ),
                 '$smtpPort' => array( 'name' => _("SMTP Server Port"),
                                                 'type' => SMOPT_TYPE_INTEGER ),
                 '$motd' => array( 'name' => _("Message of the Day"),
                                             'type' => SMOPT_TYPE_STRING,
                                             'size' => 40 ),
               );

?>