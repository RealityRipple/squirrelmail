<?php

/**
 * errors.php
 *
 * @copyright &copy; 2005-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 */

/** init error array */
$aError = array();

define('SQM_ERROR_IMAP',1);
define('SQM_ERROR_FS',2);
define('SQM_ERROR_SMTP',4);
define('SQM_ERROR_LDAP',8);
define('SQM_ERROR_DB',16);
define('SQM_ERROR_PLUGIN',32);
// define('SQM_ERROR_X',64);  future error category



$aErrors['SQM_IMAP_NO_THREAD'] = array(
    'level'    => E_USER_ERROR,
    'category' => SQM_ERROR_IMAP,
    'message'  => _("Thread sorting is not supported by your IMAP server.") . "\n" .
                  _("Please contact your system administrator and report this error."),
    'link'     => '',
    'tip'      => _("Run \"configure\", choose option 4 (General options) and set option 10 (Disable server thread sort) to true).")
);

$aErrors['SQM_IMAP_NO_SORT'] = array(
    'level'    => E_USER_ERROR,
    'category' => SQM_ERROR_IMAP,
    'message'  => _( "Server-side sorting is not supported by your IMAP server.") . "\n" .
                  _("Please contact your system administrator and report this error."),
    'link'     => '',
    'tip'      => _("Run \"configure\", choose option 4 (General options) and set option 11 (Disable server-side sorting) to true.")
);

$aErrors['SQM_IMAP_BADCHARSET'] = array(
    'level'    => E_USER_NOTICE,
    'category' => SQM_ERROR_IMAP,
    'message'  => _( "Your used charset is not supported by your IMAP server.") . "\n" .
                  _("Please contact your system administrator and report this error."),
    'link'     => '',
    'tip'      => _("Run \"configure\", choose option 4 (General options) and set option 12 (Allow server charset search) to false) or choose option 10 (Language settings) and set option 2 (Default charset) to a charset supported by your IMAP server.")
);

$aErrors['SQM_IMAP_APPEND_QUOTA_ERROR'] = array(
    'level'    => E_USER_NOTICE,
    'category' => SQM_ERROR_IMAP,
    'message'  => _( "Out of quota error."),
    'link'     => '',
    'tip'      => _("Remove unneccessary messages from your folder. Start with your Trash folder.")
);

$aErrors['SQM_IMAP_APPEND_ERROR'] = array(
    'level'    => E_USER_NOTICE,
    'category' => SQM_ERROR_IMAP,
    'message'  => _( "An error occured when SquirrelMail appended a message to the mailbox as listed in this message."),
    'link'     => ''
);

$aErrors['SQM_IMAP_ERROR'] = array(
    'level'    => E_USER_ERROR,
    'category' => SQM_ERROR_IMAP,
    'message'  => _( "Your IMAP server returned an error.") . "\n" .
                  _("Please contact your system administrator and report this error."),
    'link'     => ''
);

$aErrors['SQM_IMAP_BYE'] = array(
    'level'    => E_USER_ERROR,
    'category' => SQM_ERROR_IMAP,
    'message'  => _( "IMAP server closed the connection.") . "\n" .
                  _("Please contact your system administrator and report this error."),
    'link'     => ''
);

//$aError['SQM_FS'] // Filesystem related errors

?>