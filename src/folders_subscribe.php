<?php
   /**
    **  folders_subscribe.php
    **
    **  Copyright (c) 1999-2000 The SquirrelMail development team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    **  Subscribe and unsubcribe form folders. 
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
   if (!isset($display_messages_php))
      include("../functions/display_messages.php");

   include("../src/load_prefs.php");

   $imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);
   $dm = sqimap_get_delimiter($imapConnection);

   $location = get_location();
   if ($method == "sub") {
      for ($i=0; $i < count($mailbox); $i++) {
         $mailbox[$i] = trim($mailbox[$i]);
         sqimap_subscribe ($imapConnection, $mailbox[$i]);
         header("Location: $location/folders.php?success=subscribe");
      }
   } else {
      for ($i=0; $i < count($mailbox); $i++) {
         $mailbox[$i] = trim($mailbox[$i]);
         sqimap_unsubscribe ($imapConnection, $mailbox[$i]);
         header("Location: $location/folders.php?success=unsubscribe");
      }
   }
   sqimap_logout($imapConnection);

   /*
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
   echo _("Click here");
   echo "</A> ";
   echo _("to continue.");
   echo "</CENTER>";
   echo "</BODY></HTML>";
   */
?>

