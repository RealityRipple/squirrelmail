<?php
   /**
    **  folders_create.php
    **
    **  Copyright (c) 1999-2000 The SquirrelMail development team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    **  Creates folders on the IMAP server. 
    **  Called from folders.php
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

   if (strpos($folder_name, "\"") || strpos($folder_name, "\\") ||
       strpos($folder_name, "'") || strpos($folder_name, "$dm")) {
		print "<html><body bgcolor=$color[4]>";
      plain_error_message(_("Illegal folder name.  Please select a different name.")."<BR><A HREF=\"../src/folders.php\">"._("Click here to go back")."</A>.", $color);
      sqimap_logout($imapConnection);
      exit;
   }

   if ($contain_subs == true)
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

   if ((trim($subfolder_orig) == "[ None ]") || (trim(sqStripSlashes($subfolder_orig)) == "[ None ]")) {
      sqimap_mailbox_create ($imapConnection, $folder_prefix.$folder_name, "");
   } else {
      sqimap_mailbox_create ($imapConnection, $subfolder.$dm.$folder_name, "");
   }
   fputs($imapConnection, "1 logout\n");

   $location = get_location();
   header ("Location: $location/folders.php?success=create");
   sqimap_logout($imapConnection);
?>

