<HTML>
<?
   include("../config/config.php");
   include("../functions/strings.php");
   include("../functions/page_header.php");
   include("../functions/imap.php");

   echo "<BODY TEXT=\"$color[8]\" BGCOLOR=\"$color[4]\" LINK=\"$color[7]\" VLINK=\"$color[7]\" ALINK=\"$color[7]\">\n";
   $imapConnection = loginToImapServer($username, $key, $imapServerAddress);
   $dm = findMailboxDelimeter($imapConnection);
   if (trim($subfolder) == "[ None ]") {
      createFolder($imapConnection, "$folder_name");
   } else {
      createFolder($imapConnection, "$subfolder$dm$folder_name");
   }
   fputs($imapConnection, "1 logout\n");

   echo "<BR><BR><A HREF=\"webmail.php?right_frame=folders.php\" TARGET=_top>Return</A>";
?>
</BODY></HTML>

