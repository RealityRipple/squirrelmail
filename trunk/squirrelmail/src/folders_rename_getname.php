<?php
   /**
    **  folders_rename_getname.php
    **
    **  Copyright (c) 1999-2000 The SquirrelMail development team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    **  Gets folder names and enables renaming
    **  Called from folders.php
    **
    **  $Id$
    **/

   session_start();

   if (!isset($config_php))
      include("../config/config.php");
   if (!isset($strings_php))
      include("../functions/strings.php");
   if (!isset($page_header_php))
      include("../functions/page_header.php");
   if (!isset($imap_php))
      include("../functions/imap.php");

   include("../src/load_prefs.php");

   $imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);

   $dm = sqimap_get_delimiter($imapConnection);
   if (substr($old, strlen($old) - strlen($dm)) == $dm) {
      $isfolder = true;
      $old = substr($old, 0, strlen($old) - 1);
   }
   
   if (strpos($old, $dm)) {
      $old_name = substr($old, strrpos($old, $dm)+1, strlen($old));
      $old_parent = substr($old, 0, strrpos($old, $dm));
   } else {
      $old_name = $old;
      $old_parent = "";
   }

   $old_name = sqStripSlashes($old_name);

   displayPageHeader($color, "None");
   echo "<br><TABLE align=center border=0 WIDTH=95% COLS=1>";
   echo "<TR><TD BGCOLOR=\"$color[0]\" ALIGN=CENTER><B>";
   echo _("Rename a folder");
   echo "</B></TD></TR>";
   echo "<TR><TD BGCOLOR=\"$color[4]\" ALIGN=CENTER>";
   echo "<FORM ACTION=\"folders_rename_do.php\" METHOD=\"POST\">\n";
   echo _("New name:");
   echo "<br><B>$old_parent . </B><INPUT TYPE=TEXT SIZE=25 NAME=new_name VALUE=\"$old_name\"><BR>\n";
   if ($isfolder)
      echo "<INPUT TYPE=HIDDEN NAME=isfolder VALUE=\"true\">";
   printf("<INPUT TYPE=HIDDEN NAME=orig VALUE=\"%s\">\n", $old);
   printf("<INPUT TYPE=HIDDEN NAME=old_name VALUE=\"%s\">\n", $old_name);
   echo "<INPUT TYPE=SUBMIT VALUE=\""._("Submit")."\">\n";
   echo "</FORM><BR></TD></TR>";
   echo "</TABLE>";

   /** Log out this session **/
   sqimap_logout($imapConnection);
?>


