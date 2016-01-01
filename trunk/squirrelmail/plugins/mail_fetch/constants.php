<?php
/**
 * Mail fetch plugin constants
 * @copyright 2006-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage mail_fetch
 */

/** RFC 1939 USER authentication */
define('MAIL_FETCH_AUTH_USER',1);
/** RFC 1939 APOP authentication */
define('MAIL_FETCH_AUTH_APOP',2);
/** All authentication methods described in RFC 1939 */
define('MAIL_FETCH_AUTH_RFC1939',3);

/** Connection types */
define('MAIL_FETCH_USE_PLAIN',0);
define('MAIL_FETCH_USE_TLS',1);
define('MAIL_FETCH_USE_STLS',2);
