<?
   include("../config/config.php");
   include("../functions/strings.php");
   include("../functions/page_header.php");
   include("../functions/imap.php");

   $imapConnection = loginToImapServer($username, $key, $imapServerAddress);
   fputs($imapConnection, "1 create \"$subfolder.$folder_name\"\n");
   fputs($imapConnection, "1 logout\n");

   echo "<BR><BR><A HREF=\"webmail.php?right_frame=folders.php\" TARGET=_top>Return</A>";
?>


