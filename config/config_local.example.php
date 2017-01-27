<?php

/**
 * Local config overrides.
 *
 * You can override the config.php settings here.
 * Don't do it unless you know what you're doing.
 * Use standard PHP syntax, see config.php for examples.
 *
 * @copyright 2002-2017 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage config
 */


/**
 * What follows are notes about "hidden" settings that
 * are not defined in config.php and are only meant to
 * be optionally defined by administrators who need to
 * suit specific (unusual) setups.  This file, of course,
 * is not limited to setting these values - you can still
 * specify overrides for anything in config.php.
 *
 * $custom_session_handlers (array) allows the definition
 * of custom PHP session handlers.  This feature is well
 * documented in the code in include/init.php
 *
 * $hide_squirrelmail_header (must be defined as a constant:
 * define('hide_squirrelmail_header', 1);
 * This allows the administrator to force SquirrelMail never
 * to add its own Received headers with user information in
 * them.  This is VERY DANGEROUS and is HIGHLY DISCOURAGED
 *
 * $show_timezone_name allows (boolean) the addition of the
 * timezone name to the Date header in outgoing messages.
 * Turning this on violates RFC 822 syntax and can result in
 * more serious problems (unencoded 8 bit characters in headers)
 * on some systems.
 *
 * $force_crlf_default (string) Can be used to force CRLF or LF
 * line endings in decoded message parts.  In some environments
 * this allows attachments to be downloaded with operating-system
 * friendly line endings.  This setting may be overridden by
 * certain plugins or on systems running PHP versions less than
 * 4.3.0.  Set to 'CRLF' or 'LF' or, to force line endings to be
 * unmolested, set to some other string, such as 'NOCHANGE'
 *
 * $subfolders_of_inbox_are_special (boolean) can be set to TRUE
 * if any subfolders of the INBOX should be treated as "special"
 * (those that are displayed in a different color than other
 * "normal" mailboxes).
 *
 * $hash_dirs_use_md5 (boolean) If set to TRUE, forces the
 * hashed preferences directory calculation to use MD5 instead
 * of CRC32.
 *
 * $hash_dirs_strip_domain (boolean) If set to TRUE, and if
 * usernames are in full email address format, the domain
 * part (beginning with "@") will be stripped before
 * calculating the CRC or MD5.
 *
 * $smtp_stream_options allows more control over the SSL context
 * used when connecting to the SMTP server over SSL/TLS.  See:
 * http://www.php.net/manual/context.php and in particular
 * http://php.net/manual/context.ssl.php
 * For example, you can specify a CA file that corresponds
 * to your server's certificate and make sure that the
 * server's certificate is validated when connecting:
 * $smtp_stream_options = array(
 *     'ssl' => array(
 *         'cafile' => '/etc/pki/tls/certs/ca-bundle.crt',
 *         'verify_peer' => true,
 *         'verify_depth' => 3,
 *     ),
 * );
 *
 * $imap_stream_options allows more control over the SSL
 * context used when connecting to the IMAP server over
 * SSL/TLS.  See: http://www.php.net/manual/context.php
 * and in particular http://php.net/manual/context.ssl.php
 * For example, you can specify a CA file that corresponds
 * to your server's certificate and make sure that the
 * server's certificate is validated when connecting:
 * $imap_stream_options = array(
 *     'ssl' => array(
 *         'cafile' => '/etc/pki/tls/certs/ca-bundle.crt',
 *         'verify_peer' => true,
 *         'verify_depth' => 3,
 *     ),
 * );
 *
 * $disable_pdo (boolean) tells SquirrelMail not to use
 * PDO to access the user preferences and address book
 * databases as it normally would.  When this is set to
 * TRUE, Pear DB will be used instead, but this is not
 * recommended.
 *
 * $pdo_show_sql_errors (boolean) causes the actual
 * database error to be displayed when one is encountered.
 * When set to FALSE, generic errors are displayed,
 * preventing internal database information from being
 * exposed.  This should be set to TRUE only for debugging
 * purposes.
 *
 * $pdo_identifier_quote_char (string) allows you to
 * override the character used for quoting table and field
 * names in database queries.  Set this to the desired
 * Quote character, for example:
 * $pdo_identifier_quote_char = '"';
 * Or you can tell SquirrelMail not to quote identifiers
 * at all by setting this to "none".  When this setting
 * is empty or not found, SquirrelMail will attempt to
 * quote table and field names with what it thinks is
 * the appropriate quote character for the database type
 * being used (backtick for MySQL (and thus MariaDB),
 * double quotes for all others).
 */

