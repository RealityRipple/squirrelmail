<HTML>
<META HTTP-EQUIV="REFRESH" CONTENT="0;URL=right_main.php">
<BODY TEXT="#000000" BGCOLOR="#FFFFFF" LINK="#0000EE" VLINK="#0000EE" ALINK="#0000EE">
<?
   include("../config/config.php");
   include("../functions/strings.php");
   include("../functions/page_header.php");
   include("../functions/mailbox.php");
   include("../functions/smtp.php");

   sendMessage($smtpServerAddress, $smtpPort, $username, $domain, $passed_to, $passed_cc, $passed_bcc, $passed_subject, $passed_body, $version);

   echo "<FONT FACE=\"Arial,Helvetica\">";
   echo "<BR><BR><BR><CENTER><B>Message Sent!</B><BR><BR>";
   echo "You will be automatically forwarded.<BR>If not, <A HREF=\"right_main.php\">click here</A>";
   echo "</CENTER></FONT>";
?>
</BODY></HTML>