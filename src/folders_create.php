<?php
   /**
    **  folders_create.php
    **
    **  Copyright (c) 1999-2000 The SquirrelMail development team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    **  Creates folders on the IMAP server. 
    **  Called from folders.php
    **
    **  $Id$
    **/

   include('../src/validate.php');
   include("../functions/page_header.php");
   include("../functions/imap.php");
   include("../functions/display_messages.php");
   include("../src/load_prefs.php");

   $imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);
   $dm = sqimap_get_delimiter($imapConnection);

   if (strpos($folder_name, "\"") || strpos($folder_name, "\\") ||
       strpos($folder_name, "'") || strpos($folder_name, "$dm")) {
		print "<html><body bgcolor=$color[4]>";
      plain_error_message(_("Illegal folder name.  Please select a different name.")."<BR><A HREF=\"../src/folders.php\">"._("Click here to go back")."</A>.", $color);
      sqimap_logout($imapConnection);
      exit;
   }

   if (isset($contain_subs) && $contain_subs == true)
      $folder_name = "$folder_name$dm";

   if ($folder_prefix && (substr($folder_prefix, -1) != $dm)) {
      $folder_prefix = $folder_prefix . $dm;
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
      sqimap_mailbox_create ($imapConnection, $subfolder.$dm.$folder_name, "");
   }

   $location = get_location();
   header ("Location: $location/folders.php?success=create");
   sqimap_logout($imapConnection);
?>

