<?PHP

/**
 * defines.php
 *
 * Copyright (c) 1999-2003 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Philippe Mingo
 *
 * $Id$
 */

require_once( SM_PATH . 'functions/constants.php' );

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
define('SMOPT_TYPE_TITLE', 9);
define('SMOPT_TYPE_THEME', 10);
define('SMOPT_TYPE_PLUGINS', 11);
define('SMOPT_TYPE_LDAP', 12);
define('SMOPT_TYPE_EXTERNAL', 32);
define('SMOPT_TYPE_PATH',33);

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
                 'SM_ver' => array( 'name' => _("Squirrelmail Version"),
                                    'type' => SMOPT_TYPE_EXTERNAL,
                                    'value' => "$version" ),
                 'PHP_ver' => array( 'name' => _("PHP Version"),
                                     'type' => SMOPT_TYPE_EXTERNAL,
                                     'value' => phpversion() ),
                 /* --------------------------------------------------------*/
                 'Group1' => array( 'name' => _("Organization Preferences"),
                                    'type' => SMOPT_TYPE_TITLE ),
                 '$org_name' => array( 'name' => _("Organization Name"),
                                       'type' => SMOPT_TYPE_STRING,
                                       'size' => 40 ),
                 '$org_logo' => array( 'name' => _("Organization Logo"),
                                       'type' => SMOPT_TYPE_PATH,
                                       'size' => 40,
				       'default' => '../images/sm_logo.png'),
                 '$org_logo_width' => array( 'name'    => _("Organization Logo Width"),
                                             'type'    => SMOPT_TYPE_INTEGER,
                                             'size'    => 5,
                                             'default' => 0),
                 '$org_logo_height' => array( 'name'    => _("Organization Logo Height"),
                                              'type'    => SMOPT_TYPE_INTEGER,
                                              'size'    => 5,
                                              'default' => 0),
                 '$org_title' => array( 'name' => _("Organization Title"),
                                        'type' => SMOPT_TYPE_STRING,
                                        'size' => 40 ),
                 '$signout_page' => array( 'name' => _("Signout Page"),
                                           'type' => SMOPT_TYPE_PATH,
                                           'size' => 40 ),
                 '$squirrelmail_default_language' => array( 'name' => _("Default Language"),
                                                            'type' => SMOPT_TYPE_STRLIST,
                                                            'size' => 7,
                                                            'posvals' => $language_values ),
                 '$frame_top' => array( 'name' => _("Top Frame"),
                                        'type' => SMOPT_TYPE_STRING,
                                        'size' => 40,
                                        'default' => '_top' ),
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
                                                 'comment' => _("Use \"detect\" to auto-detect."),
                                                 'size' => 10,
                                                 'default' => 'detect' ),
				 '$use_imap_tls' => array( 'name' => "Use TLS for IMAP connections",
				 						   'type' => SMOPT_TYPE_BOOLEAN,
										   'comment' => "Requires PHP 4.3.x! Experimental.",
										   'default' => false ),
				 '$imap_auth_mech' => array( 'name' => "IMAP Authentication Mech",
				 							 'type' => SMOPT_TYPE_STRLIST,
											 'posvals' => array('plain' => 'Plaintext',
											 				    'cram-md5' => 'CRAM-MD5',
																'digest-md5' => 'DIGEST-MD5') ),
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
				 '$use_smtp_tls' => array( 'name' => "Use TLS for SMTP connections",
				 						   'type' => SMOPT_TYPE_BOOLEAN,
										   'comment' => "Requires PHP 4.3.x! Experimental.",
										   'default' => false ),
				 '$smtp_auth_mech' => array( 'name' => "SMTP Authentication Mech",
				 							 'type' => SMOPT_TYPE_STRLIST,
											 'posvals' => array('none' => 'No SMTP auth',
											                    'plain' => 'Plaintext',
																'cram-md5' => 'CRAM-MD5',
																'digest-md5' => 'DIGEST-MD5'),
											 'default' => 'none'),
				 '$pop_before_smtp' => array( 'name' => "POP3 before SMTP?",
				 							  'type' => SMOPT_TYPE_BOOLEAN,
											  'default' => false ),
                 '$invert_time' => array( 'name' => _("Invert Time"),
                                          'type' => SMOPT_TYPE_BOOLEAN ),
                 '$default_use_mdn' => array( 'name' => _("Use Confirmation Flags"),
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
                                                    'type' => SMOPT_TYPE_NUMLIST,
                                                    'posvals' => array( SMPREF_UNSEEN_NONE  => _("No Notification"),
                                                                        SMPREF_UNSEEN_INBOX => _("Only INBOX"),
                                                                        SMPREF_UNSEEN_ALL   => _("All Folders")) ),
                 '$default_unseen_type'  => array( 'name' => _("Default Unseen Type"),
                                                   'type' => SMOPT_TYPE_NUMLIST ,
                                                   'posvals' => array( SMPREF_UNSEEN_ONLY  => _("Only Unseen"),
                                                                       SMPREF_UNSEEN_TOTAL => _("Unseen and Total") ) ),
                 '$auto_create_special' => array( 'name' => _("Auto Create Special Folders"),
                                                  'type' => SMOPT_TYPE_BOOLEAN ),
                 '$default_use_javascript_addr_book' => array( 'name' => _("Default Javascript Adrressbook"),
                                                  'type' => SMOPT_TYPE_BOOLEAN ),
                 '$delete_folder' => array( 'name' => _("Auto delete folders"),
                                            'type' => SMOPT_TYPE_BOOLEAN ),
                 /* --------------------------------------------------------*/
                 'Group4' => array( 'name' => _("General Options"),
                                    'type' => SMOPT_TYPE_TITLE ),
                 '$default_charset' => array( 'name' => _("Default Charset"),
                                              'type' => SMOPT_TYPE_STRLIST,
                                              'posvals' => array( 'iso-8859-1' => 'iso-8859-1',
                                                                  'iso-8859-2' => 'iso-8859-2',
                                                                  'iso-8859-7' => 'iso-8859-7',
                                                                  'iso-8859-15' => 'iso-8859-15',
                                                                  'iso-8859-15' => 'iso-8859-15',
                                                                  'ns_4551_1' => 'ns_4551_1',
                                                                  'koi8-r' => 'koi8-r',
                                                                  'euc-KR' => 'euc-KR',
                                                                  'windows-1251' => 'windows-1251',
                                                                  'ISO-2022-JP' => 'ISO-2022-JP' ) ),
                 '$data_dir' => array( 'name' => _("Data Directory"),
                                       'type' => SMOPT_TYPE_PATH,
                                       'size' => 40 ),
                 '$attachment_dir' => array( 'name' => _("Temp Directory"),
                                             'type' => SMOPT_TYPE_PATH,
                                             'size' => 40 ),
                 '$dir_hash_level' => array( 'name' => _("Hash Level"),
                                             'type' => SMOPT_TYPE_NUMLIST,
                                             'posvals' => array( 0 => _("Hash Disabled"),
                                                                 1 => _("Low"),
                                                                 2 => _("Moderate"),
                                                                 3 => _("Medium"),
                                                                 4 => _("High") ) ),
                 '$default_left_size' => array( 'name' => _("Default Left Size"),
                                                'type' => SMOPT_TYPE_NUMLIST,
                                                'posvals' => $left_size_values ),
                 '$force_username_lowercase' => array( 'name' => _("Usernames in Lowercase"),
                                                       'type' => SMOPT_TYPE_BOOLEAN ),
                 '$default_use_priority'  => array( 'name' => _("Allow use of priority"),
                                                    'type' => SMOPT_TYPE_BOOLEAN ),
                 '$hide_sm_attributions' => array( 'name' => _("Hide SM attributions"),
                                                   'type' => SMOPT_TYPE_BOOLEAN ),
                 'default_use_mdn' => array( 'name' => _("Enable use of delivery receipts"),
                                             'type' => SMOPT_TYPE_BOOLEAN ),
                 '$edit_identity' => array( 'name' => _("Allow editing of identities"),
                                            'type' => SMOPT_TYPE_BOOLEAN ),
                 '$edit_name' => array( 'name' => _("Allow editing of full name"),
                                        'type' => SMOPT_TYPE_BOOLEAN ),
				 '$allow_server_sort' => array( 'name' => "Use server-side sorting",
				 							    'type' => SMOPT_TYPE_BOOLEAN,
												'default' => false ),
				 '$allow_thread_sort' => array( 'name' => "Use server-side thread sorting",
				 								'type' => SMOPT_TYPE_BOOLEAN,
												'default' => false ),
				 '$allow_charset_search' => array( 'name' => "Allow server charset search",
				 								   'type' => SMOPT_TYPE_BOOLEAN,
												   'default' => false ),
				 '$uid_support' => array( 'name' => "UID support",
				 						  'type' => SMOPT_TYPE_BOOLEAN,
										  'default' => false ),
				 '$session_name' => array( 'name' => "PHP session name",
				 						   'type' => SMOPT_TYPE_HIDDEN ),
                 /* --------------------------------------------------------*/
                 'Group5' => array( 'name' => _("Message of the Day"),
                                    'type' => SMOPT_TYPE_TITLE ),
                 '$motd' => array( 'name' => _("Message of the Day"),
                                   'type' => SMOPT_TYPE_TEXTAREA,
                                   'size' => 40 ),
                 /* --------------------------------------------------------*/
                 'Group6' => array( 'name' => _("Database"),
                                    'type' => SMOPT_TYPE_TITLE ),
                 '$addrbook_dsn' => array( 'name' => _("Address book DSN"),
                                           'type' => SMOPT_TYPE_STRING,
                                           'size' => 40 ),
                 '$addrbook_table' => array( 'name' => _("Address book table"),
                                             'type' => SMOPT_TYPE_STRING,
                                             'size' => 40,
                                             'default' => 'address' ),
                 '$prefs_dsn' => array( 'name' => _("Preferences DSN"),
                                        'type' => SMOPT_TYPE_STRING,
                                        'size' => 40 ),
                 '$prefs_table' => array( 'name' => _("Preferences table"),
                                          'type' => SMOPT_TYPE_STRING,
                                          'size' => 40,
                                          'default' => 'userprefs' ),
                 '$prefs_user_field' => array('name' => _("Preferences username field"),
                                              'type' => SMOPT_TYPE_STRING,
                                              'size' => 40,
                                              'default' => 'user' ),
                 '$prefs_key_field' => array('name' => _("Preferences key field"),
                                             'type' => SMOPT_TYPE_STRING,
                                             'size' => 40,
                                             'default' => 'prefkey' ),
                 '$prefs_val_field' => array('name' => _("Preferences value field"),
                                             'type' => SMOPT_TYPE_STRING,
                                             'size' => 40,
                                             'default' => 'prefval' ),
                 /* --------------------------------------------------------*/
                 'Group7' => array( 'name' => _("Themes"),
                                    'type' => SMOPT_TYPE_TITLE ),
                 '$theme_css' => array( 'name' => _("Style Sheet URL (css)"),
                                        'type' => SMOPT_TYPE_PATH,
                                        'size' => 40 ),
                 /* --------------------------------------------------------*/
                 '$config_use_color' => array(  'name' => '',
                                                'type' => SMOPT_TYPE_HIDDEN )
                 /* --------------------------------------------------------*/

               );

?>
