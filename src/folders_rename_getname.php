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

   require_once('../src/validate.php');
   require_once('../functions/imap.php');

   $imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);

   global $delimiter;
   if (substr($old, strlen($old) - strlen($delimiter)) == $delimiter) {
      $isfolder = true;
      $old = substr($old, 0, strlen($old) - 1);
   }
   
   if (strpos($old, $delimiter)) {
      $old_name = substr($old, strrpos($old, $delimiter)+1, strlen($old));
      $old_parent = substr($old, 0, strrpos($old, $delimiter));
   } else {
      $old_name = $old;
      $old_parent = "";
   }

   displayPageHeader($color, "None");
   echo "<br><TABLE align=center border=0 WIDTH=95% COLS=1>";
   echo "<TR><TD BGCOLOR=\"$color[0]\" ALIGN=CENTER><B>";
   echo _("Rename a folder");
   echo "</B></TD></TR>";
   echo "<TR><TD BGCOLOR=\"$color[4]\" ALIGN=CENTER>";
   echo "<FORM ACTION=\"folders_rename_do.php\" METHOD=\"POST\">\n";
   echo _("New name:");
   echo "<br><B>$old_parent . </B><INPUT TYPE=TEXT SIZE=25 NAME=new_name VALUE=\"$old_name\"><BR>\n";
   if (isset($isfolder))
      echo "<INPUT TYPE=HIDDEN NAME=isfolder VALUE=\"true\">";
   printf("<INPUT TYPE=HIDDEN NAME=orig VALUE=\"%s\">\n", $old);
   printf("<INPUT TYPE=HIDDEN NAME=old_name VALUE=\"%s\">\n", $old_name);
   echo "<INPUT TYPE=SUBMIT VALUE=\""._("Submit")."\">\n";
   echo "</FORM><BR></TD></TR>";
   echo "</TABLE>";

   /** Log out this session **/
   sqimap_logout($imapConnection);
?>
