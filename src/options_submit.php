<?
   include("../config/config.php");
   include("../functions/mailbox.php");
   include("../functions/strings.php");
   include("../functions/page_header.php");
   include("../functions/display_messages.php");
   include("../functions/imap.php");
   include("../functions/array.php");

   include("../src/load_prefs.php");

   echo "<HTML>";
   echo "<BODY TEXT=\"$color[8]\" BGCOLOR=\"$color[4]\" LINK=\"$color[7]\" VLINK=\"$color[7]\" ALINK=\"$color[7]\">\n";
   displayPageHeader($color, "None");

   setPref($data_dir, $username, "full_name", $full_name);
   setPref($data_dir, $username, "reply_to", $reply_to);
   setPref($data_dir, $username, "chosen_theme", $chosentheme);

   echo "<FONT FACE=\"Arial,Helvetica\">";
   echo "<BR><BR><BR><CENTER><B>Options Saved!</B><BR><BR>";
   echo "Your options have been saved.<BR><A HREF=\"webmail.php\" TARGET=_top>Click here</A> to continue.";
   echo "</CENTER></FONT>";
   echo "</BODY></HTML>";
?>