<?php

/**
 * delete_message.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Deletes a meesage from the IMAP server 
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
require_once('../functions/display_messages.php');
require_once('../functions/imap.php');

   $imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);

   sqimap_mailbox_select($imapConnection, $mailbox);
   
   sqimap_messages_delete($imapConnection, $message, $message, $mailbox);
   if ($auto_expunge)
      sqimap_mailbox_expunge($imapConnection, $mailbox, true);

   $location = get_location();
   if (isset($where) && isset($what))
      header ("Location: $location/search.php?where=".urlencode($where)."&what=".urlencode($what)."&mailbox=".urlencode($mailbox));
   else   
      header ("Location: $location/right_main.php?sort=$sort&startMessage=$startMessage&mailbox=".urlencode($mailbox));

   sqimap_logout($imapConnection);
?>
