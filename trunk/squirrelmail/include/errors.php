<?php

/**
 * errors.php
 *
 * Copyright (c) 2005 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
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
    'tip'      => _("Run \"configure\", choose option 4 (General options) and set option 10 (Allow server thread sort to false).")
);

$aErrors['SQM_IMAP_NO_SORT'] = array(
    'level'    => E_USER_ERROR,
    'category' => SQM_ERROR_IMAP,
    'message'  => _( "Server-side sorting is not supported by your IMAP server.") . "\n" .
                  _("Please contact your system administrator and report this error."),
    'link'     => '',
    'tip'      => _("Run \"configure\", choose option 4 (General options) and set option 11 (Allow server-side sorting to false).")
);

//$aError['SQM_FS'] // Filesystem related errors

?>