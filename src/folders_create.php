<HTML>
<META HTTP-EQUIV="REFRESH" CONTENT="0;URL=webmail.php?right_frame=folders.php" TARGET=_top>
<BODY TEXT="#000000" BGCOLOR="#FFFFFF" LINK="#0000EE" VLINK="#0000EE" ALINK="#0000EE">
<?
   include("../config/config.php");
   include("../functions/strings.php");
   include("../functions/page_header.php");
   include("../functions/imap.php");

   $imapConnection = loginToImapServer($username, $key, $imapServerAddress);
   fputs($imapConnection, "1 create \"$subfolder.$folder_name\"\n");
   fputs($imapConnection, "1 logout\n");

   echo "<CENTER><BR><BR>You will be automatically forwarded.<BR>If not, <A HREF=\"webmail.php?right_frame=folders.php\" TARGET=_top>click here</A></CENTER>";
?>
</BODY></HTML>

