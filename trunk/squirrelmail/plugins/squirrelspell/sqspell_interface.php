<?php

/**
 * sqspell_interface.php
 * ----------------------
 * Main wrapper for the pop-up.
 *
 * Copyright (c) 1999-2002 The SquirrelMail development team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This is a main wrapper for the pop-up window interface of
 * SquirrelSpell.    
 *
 * $Id$
 *
 * @author Konstantin Riabitsev <icon@duke.edu> ($Author$)
 * @version $Date$
 */

/**    	
 * Set up a couple of non-negotiable constants and
 * defaults. Don't change these, * the setuppable stuff is in
 * sqspell_config.php
 */
$SQSPELL_DIR='squirrelspell';
$SQSPELL_CRYPTO=FALSE;
    
/**
 * Load the stuff needed from squirrelmail
 */

chdir('..');
define('SM_PATH','../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'src/load_prefs.php');
require_once("$SQSPELL_DIR/sqspell_config.php");
require_once("$SQSPELL_DIR/sqspell_functions.php");
    
/**
 * $MOD is the name of the module to invoke.
 * If $MOD is undefined, use "init", else check for security
 * breaches.
 */
if (!isset($MOD) || !$MOD){
  $MOD='init';
} else {
  sqspell_ckMOD($MOD);
}

/**
 * Include the module.
 */
require_once("$SQSPELL_DIR/modules/$MOD.mod");
?>
