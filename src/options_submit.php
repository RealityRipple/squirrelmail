<?
   include("../config/config.php");
   include("../functions/mailbox.php");
   include("../functions/strings.php");
   include("../functions/page_header.php");
   include("../functions/display_messages.php");
   include("../functions/imap.php");
   include("../functions/array.php");
   include("../functions/prefs.php");

   echo "<HTML>";
   if ($auto_forward == true)
      echo "<META HTTP-EQUIV=\"REFRESH\" CONTENT=\"0;URL=right_main.php\">";
   echo "<BODY TEXT=\"$color[8]\" BGCOLOR=\"$color[4]\" LINK=\"$color[7]\" VLINK=\"$color[7]\" ALINK=\"$color[7]\">\n";
   displayPageHeader($color, "None");

   setPref($username, "full_name", $full_name);
   setPref($username, "reply_to", $reply_to);

   echo "<FONT FACE=\"Arial,Helvetica\">";
   echo "<BR><BR><BR><CENTER><B>Options Saved!</B><BR><BR>";
   echo "You will be automatically forwarded.<BR>If not, <A HREF=\"right_main.php\">click here</A>";
   echo "</CENTER></FONT>";
   echo "</BODY></HTML>";
?>