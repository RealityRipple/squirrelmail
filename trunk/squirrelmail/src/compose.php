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

   echo "<FORM action=\"compose_send.php\" METHOD=POST>\n";
   echo "<CENTER>";
   echo "<INPUT TYPE=TEXT NAME=passed_to>";
   echo "<INPUT TYPE=TEXT NAME=passed_subject>";
   echo "<TEXTAREA NAME=passed_body ROWS=20 COLS=72 WRAP=SOFT></TEXTAREA><BR>";
   echo "<INPUT TYPE=SUBMIT VALUE=\"Send\">";
   echo "</CENTER>";
   echo "</FORM>";
?>