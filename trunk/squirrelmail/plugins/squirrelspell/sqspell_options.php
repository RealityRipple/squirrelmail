<?php

/**
 * sqspell_options.php
 *
 * Main wrapper for the options interface.
 *
 * @author Konstantin Riabitsev <icon at duke.edu>
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage squirrelspell
 */

/**
 * Include the SquirrelMail initialization file.
 */
require('../../include/init.php');

/**
 * Set a couple of constants and defaults. Don't change these,
 * the configurable stuff is in sqspell_config.php
 * @todo do we really need $SQSPELL_DIR var?
 */
$SQSPELL_DIR='plugins/squirrelspell/';
$SQSPELL_CRYPTO=FALSE;



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
    error_box(_("Invalid SquirrelSpell module."));
    // display footer (closes html tags)
    $oTemplate->display('footer.tpl');
}
