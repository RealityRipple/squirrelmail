<?
   include("../config/config.php");
   include("../functions/strings.php");
   include("../functions/page_header.php");
   include("../functions/imap.php");

   $imapConnection = loginToImapServer($username, $key, $imapServerAddress);

   if ($subfolder == "INBOX")
      fputs($imapConnection, "1 create \"user.$username.$folder_name\"\n");
   else
      fputs($imapConnection, "1 create \"user.$username.$subfolder.$folder_name\"\n");

   fputs($imapConnection, "1 logout\n");

   echo "<BR><BR><A HREF=\"folders.php\">Return</A>";
?>


