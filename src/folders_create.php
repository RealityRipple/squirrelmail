<HTML>
<META HTTP-EQUIV="REFRESH" CONTENT="0;URL=webmail.php?right_frame=folders.php" TARGET=_top>
<?
   include("../config/config.php");
   include("../functions/strings.php");
   include("../functions/page_header.php");
   include("../functions/imap.php");

   echo "<BODY TEXT=\"$color[8]\" BGCOLOR=\"$color[4]\" LINK=\"$color[7]\" VLINK=\"$color[7]\" ALINK=\"$color[7]\">\n";
   $imapConnection = loginToImapServer($username, $key, $imapServerAddress);
   fputs($imapConnection, "1 create \"$subfolder.$folder_name\"\n");
   fputs($imapConnection, "1 logout\n");

   echo "<CENTER><BR><BR>You will be automatically forwarded.<BR>If not, <A HREF=\"webmail.php?right_frame=folders.php\" TARGET=_top>click here</A></CENTER>";
?>
</BODY></HTML>

