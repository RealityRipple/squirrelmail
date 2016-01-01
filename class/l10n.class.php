<?php

/**
 * l10n.class
 *
 * This contains internal SquirrelMail functions needed to handle
 * translations when php gettext extension is missing or some functions
 * are not available.
 *
 * @copyright 2003-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage i18n
 */

//FIXME is SM_PATH ever not defined here?  defined() calls are CPU intensive enough that we should remove this if it is not really needed
/** @ignore */
if (! defined('SM_PATH')) define('SM_PATH','../');

/** Load all php-gettext classes */
include_once(SM_PATH . 'class/l10n/streams.class.php');
include_once(SM_PATH . 'class/l10n/gettext.class.php');
