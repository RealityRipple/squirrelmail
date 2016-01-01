<?php

/**
 * sqspell_interface.php
 *
 * Main wrapper for the pop-up.
 *
 * This is a main wrapper for the pop-up window interface of
 * SquirrelSpell.
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
 * Set up a couple of non-negotiable constants and
 * defaults. Don't change these, * the setuppable stuff is in
 * sqspell_config.php
 */
$SQSPELL_DIR='plugins/squirrelspell/';
$SQSPELL_CRYPTO=FALSE;

include_once(SM_PATH . $SQSPELL_DIR . 'sqspell_functions.php');

/**
 * $MOD is the name of the module to invoke.
 * If $MOD is unspecified, assign "init" to it. Else check for
 * security breach attempts.
 */
if(! sqgetGlobalVar('MOD',$MOD,SQ_FORM)) {
    $MOD = 'init';
}
sqspell_ckMOD($MOD);

/* Load the stuff already. */
if (file_exists(SM_PATH . $SQSPELL_DIR . "modules/$MOD.mod")) {
    require_once(SM_PATH . $SQSPELL_DIR . "modules/$MOD.mod");
} else {
    error_box(_("Invalid SquirrelSpell module."));
    // display sm footer (closes html tags)
    $oTemplate->display('footer.tpl');
}
