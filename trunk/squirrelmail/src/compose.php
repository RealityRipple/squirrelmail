<?
   include("../config/config.php");
   include("../functions/strings.php");
   include("../functions/page_header.php");
   include("../functions/imap.php");
   include("../functions/mailbox.php");
   include("../functions/date.php");

   echo "<HTML><BODY TEXT=\"#000000\" BGCOLOR=\"#FFFFFF\" LINK=\"#0000EE\" VLINK=\"#0000EE\" ALINK=\"#0000EE\">\n";
   $imapConnection = loginToImapServer($username, $key, $imapServerAddress);
   displayPageHeader($mailbox);

   echo "<FORM action=\"mailto:luke@usa.om.org\" METHOD=POST>\n";
   echo "<TEXTAREA NAME=body ROWS=20 COLS=82></TEXTAREA>";
   echo "<INPUT TYPE=SUBMIT VALUE=\"Send\">";
   echo "</FORM>";
?>