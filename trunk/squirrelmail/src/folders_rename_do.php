<?php

   /**
    **  folders_rename_do.php
    **
    **  Copyright (c) 1999-2001 The SquirrelMail development team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    **  Does the actual renaming of files on the IMAP server. 
    **  Called from the folders.php
    **
    **  $Id$
    **/

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
