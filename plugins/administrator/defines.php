<?php

/**
 * Administrator plugin - Option definitions
 *
 * @author Philippe Mingo
 * @copyright 1999-2025 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage administrator
 */

/** Define constants for the various option types. */
if (!defined('SMOPT_TYPE_UNDEFINED'))
 define('SMOPT_TYPE_UNDEFINED', -1);
if (!defined('SMOPT_TYPE_STRING'))
 define('SMOPT_TYPE_STRING', 0);
if (!defined('SMOPT_TYPE_STRLIST'))
 define('SMOPT_TYPE_STRLIST', 1);
if (!defined('SMOPT_TYPE_TEXTAREA'))
 define('SMOPT_TYPE_TEXTAREA', 2);
if (!defined('SMOPT_TYPE_INTEGER'))
 define('SMOPT_TYPE_INTEGER', 3);
if (!defined('SMOPT_TYPE_FLOAT'))
 define('SMOPT_TYPE_FLOAT', 4);
if (!defined('SMOPT_TYPE_BOOLEAN'))
 define('SMOPT_TYPE_BOOLEAN', 5);
if (!defined('SMOPT_TYPE_HIDDEN'))
 define('SMOPT_TYPE_HIDDEN', 6);
if (!defined('SMOPT_TYPE_COMMENT'))
 define('SMOPT_TYPE_COMMENT', 7);
if (!defined('SMOPT_TYPE_NUMLIST'))
 define('SMOPT_TYPE_NUMLIST', 8);
if (!defined('SMOPT_TYPE_TITLE'))
 define('SMOPT_TYPE_TITLE', 9);
if (!defined('SMOPT_TYPE_THEME'))
 define('SMOPT_TYPE_THEME', 10);
if (!defined('SMOPT_TYPE_PLUGINS'))
 define('SMOPT_TYPE_PLUGINS', 11);
if (!defined('SMOPT_TYPE_LDAP'))
 define('SMOPT_TYPE_LDAP', 12);
if (!defined('SMOPT_TYPE_CUSTOM'))
 define('SMOPT_TYPE_CUSTOM', 13);
if (!defined('SMOPT_TYPE_EXTERNAL'))
 define('SMOPT_TYPE_EXTERNAL', 32);
if (!defined('SMOPT_TYPE_PATH'))
 define('SMOPT_TYPE_PATH',33);

/**
 * Returns reformated aTemplateSet array data for option selection
 * @return array template selection options
 * @since 1.5.1
 */
function adm_template_options() {
    global $aTemplateSet;
    $ret = array();
    foreach ($aTemplateSet as $iTemplateID => $aTemplate) {
        $ret[$aTemplate['ID']] = $aTemplate['NAME'];
    }
    return $ret;
}

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
                 'SM_ver' => array( 'name' => _("SquirrelMail Version"),
                                    'type' => SMOPT_TYPE_EXTERNAL,
                                    'value' => SM_VERSION ),
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
                                             'type'    => SMOPT_TYPE_STRING,
                                             'size'    => 5,
                                             'default' => 0),
                 '$org_logo_height' => array( 'name'    => _("Organization Logo Height"),
                                              'type'    => SMOPT_TYPE_STRING,
                                              'size'    => 5,
                                              'default' => 0),
                 '$org_title' => array( 'name' => _("Organization Title"),
                                        'type' => SMOPT_TYPE_STRING,
                                        'size' => 40 ),
                 '$signout_page' => array( 'name' => _("Signout Page"),
                                           'type' => SMOPT_TYPE_PATH,
                                           'size' => 40 ),
                 '$provider_uri' => array( 'name' => _("Provider Link URI"),
                                           'type' => SMOPT_TYPE_STRING ),
                 '$provider_name' => array( 'name' => _("Provider Name"),
                                            'type' => SMOPT_TYPE_STRING ),
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
                                                                   'macosx' => _("Mac OS X Mailserver"),
                                                                   'hmailserver' => _("hMailServer IMAP server"),
                                                                   'mercury32' => _("Mercury/32 IMAP server"),
                                                                   'bincimap' => _("Binc IMAP server"),
                                                                   'dovecot' => _("Dovecot IMAP server"),
                                                                   'other' => _("Not one of the above servers") ) ),
                 '$optional_delimiter' => array( 'name' => _("IMAP Folder Delimiter"),
                                                 'type' => SMOPT_TYPE_STRING,
                                                 'comment' => _("Use &quot;detect&quot; to auto-detect."),
                                                 'size' => 10,
                                                 'default' => 'detect' ),
                 '$use_imap_tls' => array( 'name' => _("IMAP Connection Security"),
                                           'type' => SMOPT_TYPE_NUMLIST,
                                           'posvals' => array( 0 => _("Plain text connection"),
                                                               1 => _("Secure IMAP (TLS) connection"),
                                                               2 => _("IMAP STARTTLS connection")),
                                           'comment' => _("Requires higher PHP version and special functions. See SquirrelMail documentation."),
                                           'default' => 0 ),
                 '$imap_auth_mech' => array( 'name' => _("IMAP Authentication Type"),
                                             'type' => SMOPT_TYPE_STRLIST,
                                             'posvals' => array('login' => _("IMAP login"),
                                                                'cram-md5' => 'CRAM-MD5',
                                                                'digest-md5' => 'DIGEST-MD5'),
                                             'default' => 'login' ),
                 '$useSendmail' => array( 'name' => _("Use Sendmail Binary"),
                                          'type' => SMOPT_TYPE_BOOLEAN,
                                          'comment' => _("Choose &quot;no&quot; for SMTP") ),
                 '$sendmail_path' => array( 'name' => _("Sendmail Path"),
                                            'type' => SMOPT_TYPE_STRING,
                                            'size' => 40 ),
                 '$sendmail_args' => array( 'name' => _("Sendmail Arguments"),
                                            'type' => SMOPT_TYPE_STRING,
                                            'size' => 40 ),
                 '$smtpServerAddress' => array( 'name' => _("SMTP Server Address"),
                                                'type' => SMOPT_TYPE_STRING,
                                                'size' => 40 ),
                 '$smtpPort' => array( 'name' => _("SMTP Server Port"),
                                       'type' => SMOPT_TYPE_INTEGER ),
                 '$use_smtp_tls' => array( 'name' => _("SMTP Connection Security"),
                                           'type' => SMOPT_TYPE_NUMLIST,
                                           'posvals' => array( 0 => _("Plain text connection"),
                                                               1 => _("Secure SMTP (TLS) connection"),
                                                               2 => _("SMTP STARTTLS connection")),
                                           'comment' => _("Requires higher PHP version and special functions. See SquirrelMail documentation."),
                                           'default' => 0 ),
                 '$smtp_auth_mech' => array( 'name' => _("SMTP Authentication Type"),
                                             'type' => SMOPT_TYPE_STRLIST,
                                             'posvals' => array('none' => _("No SMTP auth"),
                                                                'login' => _("Login (plain text)"),
                                                                'cram-md5' => 'CRAM-MD5',
                                                                'digest-md5' => 'DIGEST-MD5'),
                                             'default' => 'none'),
                 '$smtp_sitewide_user' => array( 'name' => _("Custom SMTP AUTH username"),
                                                 'type' => SMOPT_TYPE_STRING,
                                                 'size' => 40 ),
                 '$smtp_sitewide_pass' => array( 'name' => _("Custom SMTP AUTH password"),
                                                 'type' => SMOPT_TYPE_STRING,
                                                 'size' => 40 ),
                 '$pop_before_smtp' => array( 'name' => _("POP3 Before SMTP?"),
                                              'type' => SMOPT_TYPE_BOOLEAN,
                                              'default' => false ),
                 '$encode_header_key' => array( 'name' => _("Header Encryption Key"),
                                                'type' => SMOPT_TYPE_STRING ),
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
                 '$delete_folder' => array( 'name' => _("Auto delete folders"),
                                            'type' => SMOPT_TYPE_BOOLEAN ),
                 '$noselect_fix_enable' => array( 'name' => _("Enable /NoSelect folder fix"),
                                                  'type' => SMOPT_TYPE_BOOLEAN,
                                                  'default' => false),
                 /* --------------------------------------------------------*/
                 'Group4' => array( 'name' => _("General Options"),
                                    'type' => SMOPT_TYPE_TITLE ),
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
                 '$default_use_mdn' => array( 'name' => _("Enable use of delivery receipts"),
                                             'type' => SMOPT_TYPE_BOOLEAN ),
                 '$edit_identity' => array( 'name' => _("Allow editing of identities"),
                                            'type' => SMOPT_TYPE_BOOLEAN ),
                 '$edit_name' => array( 'name' => _("Allow editing of full name"),
                                            'type' => SMOPT_TYPE_BOOLEAN ),
                 '$edit_reply_to' => array( 'name' => _("Allow editing of reply-to address"),
                                        'type' => SMOPT_TYPE_BOOLEAN ),
                 '$hide_auth_header' => array( 'name' => _("Remove username from headers"),
                                               'comment' => _("Used only when identities can't be modified"),
                                               'type' => SMOPT_TYPE_BOOLEAN ),
                 '$disable_server_sort' => array( 'name' => _("Disable server-side sorting"),
                                                'type' => SMOPT_TYPE_BOOLEAN,
                                                'default' => false ),
                 '$disable_thread_sort' => array( 'name' => _("Disable server-side thread sorting"),
                                                'type' => SMOPT_TYPE_BOOLEAN,
                                                'default' => false ),
                 '$allow_charset_search' => array( 'name' => _("Allow server charset search"),
                                                   'type' => SMOPT_TYPE_BOOLEAN,
                                                   'default' => false ),
                 '$allow_advanced_search' => array( 'name' => _("Search functions"),
                                                    'type' => SMOPT_TYPE_NUMLIST,
                                                    'posvals' => array( 0 => _("Only basic search"),
                                                                        1 => _("Only advanced search"),
                                                                        2 => _("Both search functions") ),
                                                    'default' => 0 ),
                 '$session_name' => array( 'name' => _("PHP session name"),
                                           'type' => SMOPT_TYPE_HIDDEN ),
                 '$time_zone_type' => array( 'name' => _("Time Zone Configuration"),
                                             'type' => SMOPT_TYPE_NUMLIST,
                                             'posvals' => array( 0 => _("Standard GNU C time zones"),
                                                                 1 => _("Strict time zones"),
                                                                 2 => _("Custom GNU C time zones"),
                                                                 3 => _("Custom strict time zones")),
                                             'default' => 0 ),
                 '$config_location_base' => array( 'name' => _("Location base"),
                                                   'type' => SMOPT_TYPE_STRING,
                                                   'size' => 40,
                                                   'default' => '' ),
                 '$use_transparent_security_image' => array( 'name' => _("Use transparent security image"),
                                          'type' => SMOPT_TYPE_BOOLEAN,
                                          'default' => true ),
                 '$display_imap_login_error' => array( 'name' => _("Show login error message directly from IMAP server instead of generic one"),
                                          'type' => SMOPT_TYPE_BOOLEAN,
                                          'default' => false ),
                 /* --------------------------------------------------------*/
                 'Group5' => array( 'name' => _("Message of the Day"),
                                    'type' => SMOPT_TYPE_TITLE ),
                 '$motd' => array( 'name' => _("Message of the Day"),
                                   'type' => SMOPT_TYPE_TEXTAREA,
                                   'size' => 40 ),
                 /* ---- Database settings ---- */
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
                 '$prefs_user_size' => array( 'name' => _("Size of username field"),
                                              'type' => SMOPT_TYPE_INTEGER ),
                 '$prefs_key_field' => array('name' => _("Preferences key field"),
                                             'type' => SMOPT_TYPE_STRING,
                                             'size' => 40,
                                             'default' => 'prefkey' ),
                 '$prefs_key_size' => array( 'name' => _("Size of key field"),
                                             'type' => SMOPT_TYPE_INTEGER ),
                 '$prefs_val_field' => array('name' => _("Preferences value field"),
                                             'type' => SMOPT_TYPE_STRING,
                                             'size' => 40,
                                             'default' => 'prefval' ),
                 '$prefs_val_size' => array( 'name' => _("Size of value field"),
                                             'type' => SMOPT_TYPE_INTEGER ),
                 '$addrbook_global_dsn' => array( 'name' => _("Global address book DSN"),
                                           'type' => SMOPT_TYPE_STRING,
                                           'size' => 40 ),
                 '$addrbook_global_table' => array( 'name' => _("Global address book table"),
                                             'type' => SMOPT_TYPE_STRING,
                                             'size' => 40,
                                             'default' => 'global_abook' ),
                 '$addrbook_global_writeable' => array( 'name' => _("Allow writing into global address book"),
                                            'type' => SMOPT_TYPE_BOOLEAN ),
                 '$addrbook_global_listing' => array( 'name' => _("Allow listing of global address book"),
                                            'type' => SMOPT_TYPE_BOOLEAN ),
                 /* ---- Language settings ---- */
                 'Group9' => array( 'name' => _("Language settings"),
                                    'type' => SMOPT_TYPE_TITLE ),
                 '$squirrelmail_default_language' => array( 'name' => _("Default Language"),
                                                            'type' => SMOPT_TYPE_STRLIST,
                                                            'size' => 7,
                                                            'posvals' => $language_values ),
                 '$default_charset' => array( 'name' => _("Default Charset"),
                                              'type' => SMOPT_TYPE_STRLIST,
                                              'posvals' => array( 'iso-8859-1' => 'iso-8859-1',
                                                                  'iso-8859-2' => 'iso-8859-2',
                                                                  'iso-8859-7' => 'iso-8859-7',
                                                                  'iso-8859-9' => 'iso-8859-9',
                                                                  'iso-8859-15' => 'iso-8859-15',
                                                                  'utf-8' => 'utf-8',
                                                                  'koi8-r' => 'koi8-r',
                                                                  'euc-kr' => 'euc-kr',
                                                                  'big5' => 'big5',
                                                                  'gb2312' => 'gb2312',
                                                                  'tis-620' => 'tis-620',
                                                                  'windows-1251' => 'windows-1251',
                                                                  'windows-1255' => 'windows-1255',
                                                                  'windows-1256' => 'windows-1256',
                                                                  'iso-2022-jp' => 'iso-2022-jp' ) ),
                 '$show_alternative_names'  => array( 'name' => _("Show alternative language names"),
                                                      'type' => SMOPT_TYPE_BOOLEAN ),
                 '$aggressive_decoding'  => array( 'name' => _("Enable aggressive decoding"),
                                                 'type' => SMOPT_TYPE_BOOLEAN ),
                 '$lossy_encoding'  => array( 'name' => _("Enable lossy encoding"),
                                                 'type' => SMOPT_TYPE_BOOLEAN ),
                 /* ---- Tweaks ---- */
                 'Group10' => array( 'name' => _("Tweaks"),
                                     'type' => SMOPT_TYPE_TITLE ),
                 '$use_icons'  => array( 'name' => _("Use icons"),
                                         'type' => SMOPT_TYPE_BOOLEAN ),
                 '$use_iframe' => array( 'name' => _("Use inline frames with HTML mails"),
                                         'type' => SMOPT_TYPE_BOOLEAN ),
                 '$use_php_recode'  => array( 'name' => _("Use PHP recode functions"),
                                              'type' => SMOPT_TYPE_BOOLEAN ),
                 '$use_php_iconv'  => array( 'name' => _("Use PHP iconv functions"),
                                             'type' => SMOPT_TYPE_BOOLEAN ),
                 '$allow_remote_configtest' => array( 'name' => _("Allow remote configuration test"),
                                                      'type' => SMOPT_TYPE_BOOLEAN ),
                 /* ---- Settings of address books ---- */
                 'Group11' => array( 'name' => _("Address Books"),
                                     'type' => SMOPT_TYPE_TITLE ),
                 '$default_use_javascript_addr_book' => array( 'name' => _("Default Javascript Addressbook"),
                                                  'type' => SMOPT_TYPE_BOOLEAN ),
                 '$abook_global_file'           => array( 'name' => _("Global address book file"),
                                                          'type' => SMOPT_TYPE_STRING ),
                 '$abook_global_file_writeable' => array( 'name' => _("Allow writing into global address book file"),
                                                          'type' => SMOPT_TYPE_BOOLEAN ),
                 '$abook_global_file_listing'   => array( 'name' => _("Allow listing of global address book"),
                                                          'type' => SMOPT_TYPE_BOOLEAN ),
                 '$abook_file_line_length' => array( 'name' => _("Address book file line length"),
                                                     'type' => SMOPT_TYPE_INTEGER ),
                 /* --------------------------------------------------------*/
                 'Group7' => array( 'name' => _("Templates"),
                                    'type' => SMOPT_TYPE_TITLE ),
                 '$theme_css' => array( 'name' => _("Style Sheet URL (css)"),
                                        'type' => SMOPT_TYPE_PATH,
                                        'size' => 40 ),
                 '$default_fontsize' => array( 'name' => _("Default font size"),
                                               'type' => SMOPT_TYPE_STRING,
                                               'default' => ''),
                 '$default_fontset' => array( 'name' => _("Default font set"),
                                              'type' => SMOPT_TYPE_STRLIST,
                                              'posvals' => $fontsets),
                 '$templateset_default' => array( 'name' => _("Default template"),
                                                  'type' => SMOPT_TYPE_STRLIST,
                                                  'posvals' => adm_template_options()),
                 '$templateset_fallback' => array( 'name' => _("Fallback template"),
                                                  'type' => SMOPT_TYPE_STRLIST,
                                                  'posvals' => adm_template_options()),
                 '$theme_default' => array( 'name' => _("Default theme"),
                                            'type' => SMOPT_TYPE_INTEGER,
                                            'default' => 0,
                                            'comment' => _("Use index number of theme") ),
                 /* --------------------------------------------------------*/
                 '$config_use_color' => array( 'name' => '',
                                               'type' => SMOPT_TYPE_HIDDEN ),
                 '$no_list_for_subscribe' => array( 'name' => '',
                                                    'type' => SMOPT_TYPE_HIDDEN ),
                 /* --------------------------------------------------------*/

               );

$HASHs = hash_algos();
if (check_php_version(7,2)) {
    $HMACs = hash_hmac_algos();
    $HASHs = array_values(array_intersect($HASHs, $HMACs));
}
foreach($HASHs as $hash) {
    $hash = str_replace('sha', 'sha-', $hash);
    $hash = str_replace('sha-3-', 'sha3-', $hash);
    $hash = str_replace('ripemd', 'ripemd-', $hash);
    $hash = str_replace('tiger', 'tiger-', $hash);
    $hash = str_replace('haval', 'haval-', $hash);
    $hash = str_replace('snefru256', 'snefru-256', $hash);
    $key = 'scram-'.strtolower($hash);
    $value = 'SCRAM-'.strtoupper($hash);
    $defcfg['$imap_auth_mech']['posvals'][$key] = $value;
    $defcfg['$smtp_auth_mech']['posvals'][$key] = $value;
}
