<?php
   /**
    **  folders_rename_do.php
    **
    **  Copyright (c) 1999-2000 The SquirrelMail development team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    **  Does the actual renaming of files on the IMAP server. 
    **  Called from the folders.php
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
   if (!isset($display_messages_php))
      include("../functions/display_messages.php");

   include("../src/load_prefs.php");

   $imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);
   $dm = sqimap_get_delimiter($imapConnection);

   if ($method == "sub") {
      $mailbox = trim($mailbox);
      sqimap_subscribe ($imapConnection, $mailbox);
   } else {
      sqimap_unsubscribe ($imapConnection, $mailbox);
   }

   displayPageHeader($color, "None");
   echo "<BR><BR><BR><CENTER><B>";
   if ($method == "sub") {
      echo _("Subscribed Successfully!");
      echo "</B><BR><BR>";
      echo _("You have been successfully subscribed.");
   } else {
      echo _("Unsubscribed Successfully!");
      echo "</B><BR><BR>";
      echo _("You have been successfully unsubscribed.");
   }
   echo "<BR><A HREF=\"webmail.php?right_frame=folders.php\" TARGET=_top>";
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

   if (strpos($orig, $dm))
      $old_dir = substr($orig, 0, strrpos($orig, $dm));
   else
      $old_dir = "";

   if ($old_dir != "")
      $newone = "$old_dir$dm$new_name";
   else
      $newone = "$new_name";

   $orig = stripslashes($orig);
   $newone = stripslashes($newone);

   fputs ($imapConnection, ". RENAME \"$orig\" \"$newone\"\n");
   $data = sqimap_read_data($imapConnection, ".", true, $a, $b);

   // Renaming a folder doesn't renames the folder but leaves you unsubscribed
   //    at least on Cyrus IMAP servers.
   if ($isfolder) {
      $newone = $newone.$dm;
      $orig = $orig.$dm;
   }   

   sqimap_unsubscribe($imapConnection, $orig);
   sqimap_subscribe($imapConnection, $newone);

   /** Log out this session **/
   sqimap_logout($imapConnection);

   displayPageHeader($color, "None");
   echo "<BR><BR><BR><CENTER><B>";
   echo _("Folder Renamed!");
   echo "</B><BR><BR>";
   echo _("The folder has been successfully renamed.");
   echo "<BR><A HREF=\"webmail.php?right_frame=folders.php\" TARGET=_top>";
   echo _("Click here");
   echo "</A> ";
   echo _("to continue.");
   echo "</CENTER>";
   
   echo "</BODY></HTML>"; 
?>
