<HTML>
<?
   include("../config/config.php");
   include("../functions/strings.php");
   include("../functions/page_header.php");
   include("../functions/imap.php");
   include("../functions/display_messages.php");

   include("../src/load_prefs.php");

   echo "<BODY TEXT=\"$color[8]\" BGCOLOR=\"$color[4]\" LINK=\"$color[7]\" VLINK=\"$color[7]\" ALINK=\"$color[7]\">\n";
   displayPageHeader($color, "None");

   $imapConnection = loginToImapServer($username, $key, $imapServerAddress);
   $dm = findMailboxDelimeter($imapConnection);

   if (strpos($folder_name, "\"") || strpos($folder_name, ".") ||
       strpos($folder_name, "/") || strpos($folder_name, "\\") ||
       strpos($folder_name, "'") || strpos($folder_name, "$dm")) {
      plain_error_message(_("Illegal folder name.  Please select a different name.<BR><A HREF=\"../src/folders.php\">Click here to go back</A>."), $color);
      exit;
   }

   if ($contain_subs == true)
      $folder_name = "$folder_name$dm";

   if (trim($subfolder) == "[ None ]") {
      createFolder($imapConnection, "$folder_name");
   } else {
      createFolder($imapConnection, "$subfolder$dm$folder_name");
   }
   fputs($imapConnection, "1 logout\n");

   echo "<BR><BR><A HREF=\"webmail.php?right_frame=folders.php\" TARGET=_top>";
   echo _("Return");
   echo "</A>";
?>
</BODY></HTML>

