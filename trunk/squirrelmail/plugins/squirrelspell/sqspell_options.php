<?php
/**
 * sqspell_options.php
 *
 * Main wrapper for the options interface.
 *
 * Copyright (c) 1999-2005 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * @author Konstantin Riabitsev <icon@duke.edu>
 * @version $Id$
 * @package plugins
 * @subpackage squirrelspell
 */

/**
 * Set a couple of constants and defaults. Don't change these,
 * the configurable stuff is in sqspell_config.php
 */
$SQSPELL_DIR='plugins/squirrelspell/';
$SQSPELL_CRYPTO=FALSE;

/**
 * Load some necessary stuff from SquirrelMail.
 * @ignore
 */
define('SM_PATH','../../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');
include_once(SM_PATH . 'functions/display_messages.php');
include_once(SM_PATH . $SQSPELL_DIR . 'sqspell_config.php');
include_once(SM_PATH . $SQSPELL_DIR . 'sqspell_functions.php');

/**
 * $MOD is the name of the module to invoke.
 * If $MOD is unspecified, assign "options_main" to it. Else check for
 * security breach attempts.
 */
if(! sqgetGlobalVar('MOD',$MOD,SQ_FORM)) {
    $MOD = 'options_main';
}
sqspell_ckMOD($MOD);

/* Load the stuff already. */
if (file_exists(SM_PATH . $SQSPELL_DIR . "modules/$MOD.mod")) {
    require_once(SM_PATH . $SQSPELL_DIR . "modules/$MOD.mod");
} else {
    error_box(_("Invalid SquirrelSpell module."),$color);
    echo '</body></html>';
}
?>