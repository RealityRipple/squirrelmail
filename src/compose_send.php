<HTML>
<?
   include("../config/config.php");
   include("../functions/strings.php");
   include("../functions/page_header.php");
   include("../functions/mailbox.php");
   include("../functions/smtp.php");
   include("../functions/display_messages.php");

   include("../src/load_prefs.php");

   if ($passed_body == "") {
      echo "<BODY TEXT=\"$color[8]\" BGCOLOR=\"$color[4]\" LINK=\"$color[7]\" VLINK=\"$color[7]\" ALINK=\"$color[7]\">\n";
      displayPageHeader($color, "None");
      plain_error_message("You have not entered a message body.", $color);
      exit;
   } else if ($passed_to == "") {
      echo "<BODY TEXT=\"$color[8]\" BGCOLOR=\"$color[4]\" LINK=\"$color[7]\" VLINK=\"$color[7]\" ALINK=\"$color[7]\">\n";
      displayPageHeader($color, "None");
      plain_error_message("You have not filled in the \"To:\" field.", $color);
      echo "<FORM action=\"compose_send.php\" METHOD=POST>\n";

      echo "<INPUT TYPE=HIDDEN VALUE=\"$passed_subject\" NAME=passed_subject><BR>";
      echo "<INPUT TYPE=HIDDEN VALUE=\"$passed_cc\" NAME=passed_cc><BR>";
      echo "<INPUT TYPE=HIDDEN VALUE=\"$passed_bcc\" NAME=passed_bcc><BR>";
      echo "<CENTER><FONT FACE=\"Arial,Helvetica\">To: </FONT><INPUT TYPE=TEXT NAME=passed_to SIZE=60><BR>";
      echo "<INPUT TYPE=HIDDEN VALUE=\"$passed_body\" NAME=passed_body><BR>";
      echo "<INPUT TYPE=SUBMIT VALUE=\"Send\">";
      echo "</CENTER></FORM>\n";

      exit;
   } else if ($passed_subject == "") {
      echo "<BODY TEXT=\"$color[8]\" BGCOLOR=\"$color[4]\" LINK=\"$color[7]\" VLINK=\"$color[7]\" ALINK=\"$color[7]\">\n";
      displayPageHeader($color, "None");
      plain_error_message("You have not entered a subject.", $color);
      echo "<FORM action=\"compose_send.php\" METHOD=POST>\n";
      echo "<INPUT TYPE=HIDDEN VALUE=\"$passed_to\" NAME=passed_to><BR>";
      echo "<INPUT TYPE=HIDDEN VALUE=\"$passed_cc\" NAME=passed_cc><BR>";
      echo "<INPUT TYPE=HIDDEN VALUE=\"$passed_bcc\" NAME=passed_bcc><BR>";
      echo "<CENTER><FONT FACE=\"Arial,Helvetica\">Subject: </FONT><INPUT TYPE=TEXT NAME=passed_subject SIZE=60 VALUE=\"(no subject)\"><BR>";
      echo "<INPUT TYPE=HIDDEN VALUE=\"$passed_body\" NAME=passed_body><BR>";
      echo "<INPUT TYPE=SUBMIT VALUE=\"Send\">";
      echo "</CENTER></FORM>\n";

      exit;
   }

   $passed_body = stripslashes($passed_body);
   $passed_body = stripslashes($passed_body); // Apparently I have to remove them twice.. dunno why.
   $passed_to = stripslashes($passed_to);
   $passed_cc = stripslashes($passed_cc);
   $passed_bcc = stripslashes($passed_bcc);
   $passed_subject = stripslashes($passed_subject);

   sendMessage($username, $domain, $passed_to, $passed_cc, $passed_bcc, $passed_subject, $passed_body, $version);

   if ($auto_forward == true)
      echo "<META HTTP-EQUIV=\"REFRESH\" CONTENT=\"0;URL=right_main.php\">";
   echo "<BODY TEXT=\"$color[8]\" BGCOLOR=\"$color[4]\" LINK=\"$color[7]\" VLINK=\"$color[7]\" ALINK=\"$color[7]\">\n";

   displayPageHeader($color, "None");
   echo "<FONT FACE=\"Arial,Helvetica\">";
   echo "<BR><BR><BR><CENTER><B>Message Sent!</B><BR><BR>";
   echo "You will be automatically forwarded.<BR>If not, <A HREF=\"right_main.php\">click here</A>";
   echo "</CENTER></FONT>";
?>
</BODY></HTML>
