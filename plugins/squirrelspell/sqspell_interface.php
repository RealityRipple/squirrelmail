<?php

   /**
    **  sqspell_interface.php -- Main wrapper for the pop-up.
    **
    **  Copyright (c) 1999-2001 The SquirrelMail development team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    **   This is a main wrapper for the pop-up window interface of
    **   SquirrelSpell.    
    **
    **  $Id$
    **/

    /*    	
    ** Set up a couple of non-negotiable constants. Don't change these,
    ** the setuppable stuff is in sqspell_config.php
    */
    $SQSPELL_DIR='squirrelspell';
    $SQSPELL_CRYPTO=FALSE;
    
    /* Load the necessary stuff. */
    chdir('..');
    require_once('../src/validate.php');
    require_once('../src/load_prefs.php');
    require_once("$SQSPELL_DIR/sqspell_config.php");
    require_once("$SQSPELL_DIR/sqspell_functions.php");
    
    /*
    ** Now load the necessary module from the modules dir.
    **
    */
    if (!$MOD) 
        $MOD='init';
    
    /*
    ** see if someone is attempting to be nasty by trying to get out of the
    ** modules directory, although it probably wouldn't do them any good,
    ** since every module has to end with .mod.php. Still, they deserve
    ** to be warned. ;)
    */
    if (strstr($MOD, '.') || strstr($MOD, '/') || strstr($MOD, '%')){ 
    	echo _("SECURITY BREACH ON DECK 5! CMDR TUVOK AND SECURITY TEAM REQUESTED.");
        exit;
    }
    /* fetch the module now. */
    require_once("$SQSPELL_DIR/modules/$MOD.mod.php");
?>