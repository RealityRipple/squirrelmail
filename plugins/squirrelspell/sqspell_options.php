<?php
/**
 * sqspell_options.php 
 *
 * Main wrapper for the options interface.
 *
 * Copyright (c) 1999-2002 The SquirrelMail development team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * $Id$
 *
 * @author Konstantin Riabitsev <icon@duke.edu> ($Author$)
 * @version $Date$
 */

/**
 * Set a couple of constants and defaults. Don't change these, 
 * the configurable stuff is in sqspell_config.php
 */
$SQSPELL_DIR='plugins/squirrelspell/';
$SQSPELL_CRYPTO=FALSE;

/**
 * Load some necessary stuff from squirrelmail. 
 */
chdir('..');
define('SM_PATH','../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'include/load_prefs.php');
require_once(SM_PATH . 'functions/strings.php');
require_once(SM_PATH . 'functions/page_header.php');
require_once(SM_PATH . $SQSPELL_DIR . 'sqspell_config.php');
require_once(SM_PATH . $SQSPELL_DIR . 'sqspell_functions.php');

/**
 * $MOD is the name of the module to invoke.
 * If $MOD is unspecified, assign "init" to it. Else check for
 * security breach attempts.
 */
if (!isset($MOD) || !$MOD){
  $MOD = 'options_main';
} else {
  sqspell_ckMOD($MOD);
}

/* Load the stuff already. */
require_once(SM_PATH . $SQSPELL_DIR . "modules/$MOD.mod");

?>
