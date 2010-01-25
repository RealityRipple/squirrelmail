<?php

/**
 * Local config overrides.
 *
 * You can override the config.php settings here.
 * Don't do it unless you know what you're doing.
 * Use standard PHP syntax, see config.php for examples.
 *
 * @copyright 2002-2010 The SquirrelMail Project Team
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
 * hide_squirrelmail_header (must be defined as a constant:
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
 */

