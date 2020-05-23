<?php

/**
 * Local config overrides.
 *
 * You can override the config.php settings here.
 * Don't do it unless you know what you're doing.
 * Use standard PHP syntax, see config.php for examples.
 *
 * @copyright 2002-2020 The SquirrelMail Project Team
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
 *
 * $use_expiring_security_tokens (boolean) allows you to
 * make SquirrelMail use short-lived anti-CSRF security
 * tokens that expire as desired (not recommended, can
 * cause user-facing issues when tokens expire unexpectedly).
 *
 * $max_token_age_days (integer) allows you to indicate how
 * long a token should be valid for (in days) (only relevant
 * when $use_expiring_security_tokens is enabled).
 *
 * $do_not_use_single_token (boolean) allows you to force
 * SquirrelMail to generate a new token every time one is
 * requested (which may increase obscurity through token
 * randomness at the cost of some performance).  Otherwise,
 * only one token will be generated per user which will
 * change only after it expires or is used outside of the
 * validity period specified when calling
 * sm_validate_security_token() (only relevant when
 * $use_expiring_security_tokens is enabled).
 * 
 * $head_tag_extra can be used to add custom tags inside
 * the <head> section of *ALL* pages.  The string
 * "###SM BASEURI###" will be replaced with the base URI
 * for this SquirrelMail installation.  This may be used,
 * for example, to add custom favicon tags.  If this
 * setting is empty here, SquirrelMail will add a favicon
 * tag by default.  If you want to retain the default favicon
 * while using this setting, you must include the following
 * as part of this setting:
 * $head_tag_extra = '<link rel="shortcut icon" href="###SM BASEURI###favicon.ico" />...<YOUR CONTENT HERE>...';
 *
 * $imap_id_command_args (array) causes the IMAP ID
 * command (RFC 2971) to be sent after every login,
 * identifying the client to the server.  Each key in this
 * array is an attibute to be sent in the ID command to
 * the server.  Values will be sent as-is except if the
 * value is "###REMOTE ADDRESS###" (without quotes) in
 * which case the current user's real IP address will be
 * substituted.  If "###X-FORWARDED-FOR###" is used and a
 * "X-FORWARDED-FOR" header is present in the client request,
 * the contents of that header are used (careful, this can
 * be forged).  If "###X-FORWARDED-FOR OR REMOTE ADDRESS###"
 * is used, then the "X-FORWARDED-FOR" header is used if it
 * is present in the request, otherwise, the client's
 * connecting IP address is used.  The following attributes
 * will always be added unless they are specifically
 * overridden with a blank value:
 *    name, vendor, support-url, version
 * A parsed representation of server's response is made
 * available to plugins as both a global and session variable
 * named "imap_server_id_response" (a simple key/value array)
 * unless response parsing is turned off by way of setting a
 * variable in this file named
 * $do_not_parse_imap_id_command_response to TRUE, in which
 * case, the stored response will be the unparsed IMAP response.
 * $imap_id_command_args = array('remote-host' => '###REMOTE ADDRESS###');
 * $do_not_parse_imap_id_command_response = FALSE;
 *
 * $remove_rcdata_rawtext_tags_and_content
 * When displaying HTML-format email message content, a small
 * number of HTML tags are parsed differently (RCDATA, RAWTEXT
 * content), but can also be removed entirely (with their contents)
 * if desired (in most cases, should be a safe thing with minimal
 * impact).  This would be done as a fallback security measure and
 * can be enabled by adding this here:
 * $remove_rcdata_rawtext_tags_and_content = TRUE; 
 *
 * $php_self_pattern
 * $php_self_replacement
 * These may be used to modify the value of the global $PHP_SELF
 * variable used throughout the SquirrelMail code (though that
 * variable is used less frequently in version 1.5.x). The
 * pattern should be a full regular expression including the
 * delimiters. This may be helpful when the web server sees
 * traffic from a proxy so the normal $PHP_SELF does not resolve
 * to what it should be for the real client.
 *
 * $upload_filesize_divisor allows the administrator to specify
 * the divisor used when converting the size of an uploaded file
 * as given by PHP's filesize() and converted to human-digestable
 * form.  By default, 1000 is used, but 1024 may be necessary in
 * some environments.
 * $upload_filesize_divisor = 1024;
 *
 */
