<?php

   /**
    **  sqspell_options.php -- Main wrapper for the options interface.
    **
    **  Copyright (c) 1999-2001 The SquirrelMail development team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    **
    **
    **  $Id$
    **/

    /*
    ** Set a couple of constants. Don't change these, the setuppable stuff is
    ** in sqspell_config.php
    */
    $SQSPELL_DIR='squirrelspell';
    $SQSPELL_CRYPTO=FALSE;
    
    /* Load some necessary stuff. */
    chdir('..');
    require_once('../src/validate.php');
    require_once('../src/load_prefs.php');
    require_once('../functions/strings.php');
    require_once('../functions/page_header.php');
    require_once("$SQSPELL_DIR/sqspell_config.php");
    require_once("$SQSPELL_DIR/sqspell_functions.php");
    
    /* Access the module needed */
    if (!$MOD) 
        $MOD = 'options_main';
    
    /*
    ** see if someone is attempting to be nasty by trying to get out of the
    ** modules directory, although it probably wouldn't do them any good,
    ** since every module has to end with .mod.php. Still, they deserve
    ** to be warned. ;)
    */
    if (strstr($MOD, ".") || strstr($MOD, "/") || strstr($MOD, "%")){
    	echo _("SECURITY BREACH ON DECK 5! CMDR TUVOK AND SECURITY TEAM REQUESTED.");
        exit;
    }
    /* load the stuff already. */
    require_once("$SQSPELL_DIR/modules/$MOD.mod.php");
?>