<?
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

   if (strpos($folder_name, "\"") || strpos($folder_name, ".") ||
       strpos($folder_name, "/") || strpos($folder_name, "\\") ||
       strpos($folder_name, "'") || strpos($folder_name, "$dm")) {
      plain_error_message(_("Illegal folder name.  Please select a different name.")."<BR><A HREF=\"../src/folders.php\">"._("Click here to go back")."</A>.", $color);
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
   }

   if (trim($subfolder_orig) == "[ None ]") {
      sqimap_mailbox_create ($imapConnection, $folder_prefix.$folder_name, "");
   } else {
      sqimap_mailbox_create ($imapConnection, $subfolder.$dm.$folder_name, "");
   }
   fputs($imapConnection, "1 logout\n");

//   if ($auto_forward == true) {
//      header ("Location: webmail.php?right_frame=folders.php");
//   } else {
      echo "<html><BODY TEXT=\"$color[8]\" BGCOLOR=\"$color[4]\" LINK=\"$color[7]\" VLINK=\"$color[7]\" ALINK=\"$color[7]\">\n";
      displayPageHeader($color, "None");
      echo "<BR><BR><BR><CENTER><B>";
      echo _("Folder Created!");
      echo "</B><BR><BR>";
      echo _("The folder has been successfully created.");
      echo "<BR><A HREF=\"webmail.php?right_frame=folders.php\" TARGET=_top>";
      echo _("Click here");
      echo "</A> ";
      echo _("to continue.");
      echo "</CENTER>";
      echo "</BODY></HTML>";
//   }
?>

