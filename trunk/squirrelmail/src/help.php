<HTML>

<?php

   /**
    ** help.php
    **
    **  Copyright (c) 1999-2000 The SquirrelMail development team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    **  This checks if the user's preferred language has a directory and file present
    **  then loads it or english if preferred is not found.
    **
    **/

  if (!isset($config_php))
      include("../config/config.php");

/** If it was a successful login, lets load their preferences **/
   include("../src/load_prefs.php");
   echo "<BODY TEXT=\"$color[8]\" BGCOLOR=\"$color[4]\" LINK=\"$color[7]\" VLINK=\"$color[7]\" ALINK=\"$color[7]\">\n";

/** 
 ** Check to see if the help files have been translated into the users language
 ** If so, include them, if not, give them english. The po file should really have
 ** The echo line put in it.
 **/

   if (file_exists("../help/$user_language/basic.hlp")) {
	include ("../help/$user_language/basic.hlp");
   } elseif(file_exists("../help/en/basic.hlp")) {
	echo "<CENTER><B><FONT COLOR=$color[2]>";
	echo _("Your preferred language is not yet translated. English will be substituted here.");
	echo "</FONT></B></CENTER><BR>";
	include ("../help/en/basic.hlp");
   } else {
	$nohelp = true;
   }
   if (file_exists("../help/$user_language/main_folder.hlp")) {
	include ("../help/$user_language/main_folder.hlp");
   } elseif(file_exists("../help/en/main_folder.hlp")) {
	echo "<CENTER><B><FONT COLOR=$color[2]>";
	echo _("Your preferred language is not yet translated. English will be substituted here.");
	echo "</FONT></B></CENTER><BR>";
	include ("../help/en/main_folder.hlp");
   } else {
	$nohelp = true;
   }
   if (file_exists("../help/$user_language/read_mail.hlp")) {
	include ("../help/$user_language/read_mail.hlp");
   } elseif(file_exists("../help/en/read_mail.hlp")) {
	echo "<CENTER><B><FONT COLOR=$color[2]>";
	echo _("Your preferred language is not yet translated. English will be substituted here.");
	echo "</FONT></B></CENTER><BR>";
	include ("../help/en/read_mail.hlp");
   } else {
	$nohelp = true;
   }
   if (file_exists("../help/$user_language/compose.hlp")) {
	include ("../help/$user_language/compose.hlp");
   } elseif(file_exists("../help/en/compose.hlp")) {
	echo "<CENTER><B><FONT COLOR=$color[2]>Your preferred language is not yet translated. English will be substituted here.</FONT></B></CENTER><BR>";
	echo "<CENTER><B><FONT COLOR=$color[2]>";
	echo _("Your preferred language is not yet translated. English will be substituted here.");
	echo "</FONT></B></CENTER><BR>";
	include ("../help/en/compose.hlp");
   } else {
	$nohelp = true;
   }
   if (file_exists("../help/$user_language/addresses.hlp")) {
	include ("../help/$user_language/addresses.hlp");
   } elseif(file_exists("../help/en/addresses.hlp")) {
	echo "<CENTER><B><FONT COLOR=$color[2]>";
	echo _("Your preferred language is not yet translated. English will be substituted here.");
	echo "</FONT></B></CENTER><BR>";
	include ("../help/en/addresses.hlp");
   } else {
	$nohelp = true;
   }
   if (file_exists("../help/$user_language/folders.hlp")) {
	include ("../help/$user_language/folders.hlp");
   } elseif(file_exists("../help/en/folders.hlp")) {
	echo "<CENTER><B><FONT COLOR=$color[2]>";
	echo _("Your preferred language is not yet translated. English will be substituted here.");
	echo "</FONT></B></CENTER><BR>";
	include ("../help/en/folders.hlp");
   } else {
	$nohelp = true;
   }
   if (file_exists("../help/$user_language/options.hlp")) {
	include ("../help/$user_language/options.hlp");
   } elseif(file_exists("../help/en/options.hlp")) {
	echo "<CENTER><B><FONT COLOR=$color[2]>";
	echo _("Your preferred language is not yet translated. English will be substituted here.");
	echo "</FONT></B></CENTER><BR>";
	include ("../help/en/options.hlp");
   } else {
	$nohelp = true;
   }
   if (file_exists("../help/$user_language/FAQ.hlp")) {
	include ("../help/$user_language/FAQ.hlp");
   } elseif(file_exists("../help/en/FAQ.hlp")) {
	echo "<CENTER><B><FONT COLOR=$color[2]>";
	echo _("Your preferred language is not yet translated. English will be substituted here.");
	echo "</FONT></B></CENTER><BR>";
	include ("../help/en/FAQ.hlp");
   } else {
	$nohelp = true;
   }
// If any of the standard help files aren't there, tell them.

   if($nohelp) {
	echo "<BR><CENTER><B><FONT COLOR=$color[2]>",_("ERROR: Some or all of the standard English help files ar missing."), "</FONT></B></CENTER><BR>";
   }

?>
</BODY>
</HTML>
