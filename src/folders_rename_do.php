<?php

/**
 * folders_rename_do.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Does the actual renaming of files on the IMAP server. 
 * Called from the folders.php
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

   if($old_name == $new_name) {
      $location = get_location();
      header ("Location: $location/folders.php");
      exit;
   }

   $imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);
   global $delimiter;

   if (strpos($orig, $delimiter))
      $old_dir = substr($orig, 0, strrpos($orig, $delimiter));
   else
      $old_dir = "";

   if ($old_dir != "")
      $newone = "$old_dir$delimiter$new_name";
   else
      $newone = "$new_name";

   $cmd = sqimap_session_id() . " RENAME \"" . quoteIMAP($orig) . "\" \"" .
      quoteIMAP($newone) . "\"\r\n";
   fputs ($imapConnection, $cmd);
   $data = sqimap_read_data($imapConnection, sqimap_session_id(), true, $a, $b);

   // Renaming a folder doesn't renames the folder but leaves you unsubscribed
   //    at least on Cyrus IMAP servers.
   if (isset($isfolder)) {
      $newone = $newone.$delimiter;
      $orig = $orig.$delimiter;
   }   
   sqimap_unsubscribe($imapConnection, $orig);
   sqimap_subscribe($imapConnection, $newone);

   fputs ($imapConnection, sqimap_session_id() . " LIST \"\" \"" . quoteIMAP($newone) .
      "*\"\r\n");
   $data = sqimap_read_data($imapConnection, sqimap_session_id(), true, $a, $b);
   for ($i=0; $i < count($data); $i++)
   {
      $name = find_mailbox_name($data[$i]);

      if ($name != $newone) // don't try to resubscribe when renaming ab to abc
      {
        sqimap_unsubscribe($imapConnection, $name);
        $name = substr($name, strlen($orig));
        $name = $newone . $name;
        sqimap_subscribe($imapConnection, $name);
      }
   }

   /** Log out this session **/
   sqimap_logout($imapConnection);
   $location = get_location();
   header ("Location: $location/folders.php?success=rename");
?>
