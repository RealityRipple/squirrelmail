<?
   include("../config/config.php");
   include("../functions/strings.php");
   include("../functions/page_header.php");
   include("../functions/mailbox.php");
   include("../functions/smtp.php");

   sendMessage($passed_to, $passed_subject, $passed_body);
   echo "<A HREF=\"right_main.php\">RETURN</A>";
?>