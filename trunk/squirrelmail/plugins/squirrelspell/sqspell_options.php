<?php
/**
   SQSPELL_OPTIONS.PHP
   --------------------
   Main wrapper for the options interface.
   								**/
// Set a couple of constants. Don't change these, the setuppable stuff is
// in sqspell_config.php
$SQSPELL_DIR="squirrelspell";
$SQSPELL_CRYPTO=false;

// Load some necessary stuff.
chdir("..");
include("../src/validate.php");
include("../src/load_prefs.php");
include("../functions/strings.php");
include("../functions/page_header.php");
include ("$SQSPELL_DIR/sqspell_config.php");
require ("$SQSPELL_DIR/sqspell_functions.php");

// Access the module needed
//
if (!$MOD) $MOD="options_main";

// see if someone is attempting to be nasty by trying to get out of the
// modules directory, although it probably wouldn't do them any good,
// since every module has to end with .mod.php. Still, they deserve
// to be warned. ;)
if (strstr($MOD, ".") || strstr($MOD, "/") || strstr($MOD, "%")){
	echo "SECURITY BREACH ON DECK 5! CMDR TUVOK AND SECURITY TEAM REQUESTED.";
        exit;
}
// load the stuff already.
include ("$SQSPELL_DIR/modules/$MOD.mod.php");
?>
