<HTML>
<FONT FACE="Arial,Helvetica">

<?php

/**
 ** help.php
 **
 ** This checks if the user's preferred language has a directory and file present
 ** then loads it or english if preferred is not found.
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
   } else {
	echo "<CENTER><B><FONT COLOR=$color[2]>Your preferred language is not yet translated. English will be substituted here.</FONT></B></CENTER><BR>";
	include ("../help/en/basic.hlp");
   }
   if (file_exists("../help/$user_language/main_folder.hlp")) {
	include ("../help/$user_language/main_folder.hlp");
   } else {
	echo "<CENTER><B><FONT COLOR=$color[2]>Your preferred language is not yet translated. English will be substituted here.</FONT></B></CENTER><BR>";
	include ("../help/en/main_folder.hlp");
   }
   if (file_exists("../help/$user_language/read_mail.hlp")) {
	include ("../help/$user_language/read_mail.hlp");
   } else {
	echo "<CENTER><B><FONT COLOR=$color[2]>Your preferred language is not yet translated. English will be substituted here.</FONT></B></CENTER><BR>";
	include ("../help/en/read_mail.hlp");
   }
   if (file_exists("../help/$user_language/compose.hlp")) {
	include ("../help/$user_language/compose.hlp");
   } else {
	echo "<CENTER><B><FONT COLOR=$color[2]>Your preferred language is not yet translated. English will be substituted here.</FONT></B></CENTER><BR>";
	include ("../help/en/compose.hlp");
   }
   if (file_exists("../help/$user_language/addresses.hlp")) {
	include ("../help/$user_language/addresses.hlp");
   } else {
	echo "<CENTER><B><FONT COLOR=$color[2]>Your preferred language is not yet translated. English will be substituted here.</FONT></B></CENTER><BR>";
	include ("../help/en/addresses.hlp");
   }
   if (file_exists("../help/$user_language/folders.hlp")) {
	include ("../help/$user_language/folders.hlp");
   } else {
	echo "<CENTER><B><FONT COLOR=$color[2]>Your preferred language is not yet translated. English will be substituted here.</FONT></B></CENTER><BR>";
	include ("../help/en/folders.hlp");
   }
   if (file_exists("../help/$user_language/options.hlp")) {
	include ("../help/$user_language/options.hlp");
   } else {
	echo "<CENTER><B><FONT COLOR=$color[2]>Your preferred language is not yet translated. English will be substituted here.</FONT></B></CENTER><BR>";
	include ("../help/en/options.hlp");
   }
   if (file_exists("../help/$user_language/FAQ.hlp")) {
	include ("../help/$user_language/FAQ.hlp");
   } else {
	echo "<CENTER><B><FONT COLOR=$color[2]>Your preferred language is not yet translated. English will be substituted here.</FONT></B></CENTER><BR>";
	include ("../help/en/FAQ.hlp");
   }

?>
</FONT>
</BODY>
</HTML>
