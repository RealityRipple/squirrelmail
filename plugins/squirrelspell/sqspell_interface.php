<?php

/**
   SQSPELL_INTERFACE.PHP
   ----------------------
   This is a main wrapper for the pop-up window interface of
   SquirrelSpell.
  								**/
	
// Set up a couple of non-negotiable constants. Don't change these,
// the setuppable stuff is in sqspell_config.php
$SQSPELL_DIR="squirrelspell";
$SQSPELL_CRYPTO=false;

// Load the necessary stuff.
chdir("..");
include("../src/validate.php");
include("../src/load_prefs.php");
include ("$SQSPELL_DIR/sqspell_config.php");
require ("$SQSPELL_DIR/sqspell_functions.php");

// Now load the necessary module from the modules dir.
//
if (!$MOD) $MOD="init";

// see if someone is attempting to be nasty by trying to get out of the
// modules directory, although it probably wouldn't do them any good,
// since every module has to end with .mod.php. Still, they deserve
// to be warned. ;)
if (strstr($MOD, ".") || strstr($MOD, "/") || strstr($MOD, "%")){ 
	echo "SECURITY BREACH ON DECK 5! CMDR TUVOK AND SECURITY TEAM REQUESTED.";
        exit;
}
// fetch the module now.
include ("$SQSPELL_DIR/modules/$MOD.mod.php");
?>
