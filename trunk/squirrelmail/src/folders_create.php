<?php

/**
 * folders_create.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Creates folders on the IMAP server.
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
   global $delimiter;

   if (strpos($folder_name, "\"") || strpos($folder_name, "\\") ||
       strpos($folder_name, "'") || strpos($folder_name, "$delimiter")) {
      echo "<html><body bgcolor=$color[4]>";
      plain_error_message(_("Illegal folder name.  Please select a different name.")."<BR><A HREF=\"../src/folders.php\">"._("Click here to go back")."</A>.", $color);
      sqimap_logout($imapConnection);
      exit;
   }

   if (isset($contain_subs) && $contain_subs == true)
      $folder_name = "$folder_name$delimiter";

   if ($folder_prefix && (substr($folder_prefix, -1) != $delimiter)) {
      $folder_prefix = $folder_prefix . $delimiter;
   }
   if ($folder_prefix && (substr($subfolder, 0, strlen($folder_prefix)) != $folder_prefix)){
      $subfolder_orig = $subfolder;
      $subfolder = $folder_prefix . $subfolder;
   } else {
      $subfolder_orig = $subfolder;
   }

   if (trim($subfolder_orig) == '') {
      sqimap_mailbox_create ($imapConnection, $folder_prefix.$folder_name, "");
   } else {
      sqimap_mailbox_create ($imapConnection, $subfolder.$delimiter.$folder_name, "");
   }

   $location = get_location();
   header ("Location: $location/folders.php?success=create");
   sqimap_logout($imapConnection);
?>
