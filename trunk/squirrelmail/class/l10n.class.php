<?php
/**
 * l10n.class
 *
 * Copyright (c) 2003-2004 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This contains internal squirrelmail functions needed to handle
 * translations when php gettext extension is missing or some functions
 * are not available.
 *
 * @version $Id$
 * @package squirrelmail
 * @subpackage i18n
 */

/** Load all php-gettext classes */
include_once(SM_PATH . 'class/l10n/streams.class.php');
include_once(SM_PATH . 'class/l10n/gettext.class.php');
?>