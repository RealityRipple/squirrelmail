<?php

/**
 * folders_subscribe.php
 *
 * Copyright (c) 1999-2001 The Squirrelmail Development Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Subscribe and unsubcribe form folders. 
 * Called from folders.php
 *
 * $Id$
 */

/*****************************************************************/
/*** THIS FILE NEEDS TO HAVE ITS FORMATTING FIXED!!!           ***/
/*** PLEASE DO SO AND REMOVE THIS COMMENT SECTION.             ***/
/***    + Base level indent should begin at left margin, as    ***/
/***      the require_once below looks.                        ***/
/***    + All identation should consist of four space blocks   ***/
/***    + Tab characters are evil.                             ***/
/***    + all comments should use "slash-star ... star-slash"  ***/
/***      style -- no pound characters, no slash-slash style   ***/
/***    + FLOW CONTROL STATEMENTS (if, while, etc) SHOULD      ***/
/***      ALWAYS USE { AND } CHARACTERS!!!                     ***/
/***    + Please use ' instead of ", when possible. Note "     ***/
/***      should always be used in _( ) function calls.        ***/
/*** Thank you for your help making the SM code more readable. ***/
/*****************************************************************/

require_once('../src/validate.php');
require_once('../functions/imap.php');
require_once('../functions/display_messages.php');

   $imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);

   $location = get_location();
   if ($method == 'sub') {
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
   if (!isset($mailbox)) {
         header("Location: $location/folders.php");
   }
   sqimap_logout($imapConnection);

   /*
   displayPageHeader($color, 'None');
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
