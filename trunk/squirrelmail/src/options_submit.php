<?
   include("../config/config.php");
   include("../functions/strings.php");
   include("../functions/page_header.php");
   include("../functions/display_messages.php");
   include("../functions/imap.php");
   include("../functions/array.php");

   include("../src/load_prefs.php");


   setPref($data_dir, $username, "full_name", stripslashes($full_name));
   setPref($data_dir, $username, "reply_to", stripslashes($reply_to));
   setPref($data_dir, $username, "chosen_theme", $chosentheme);
   setPref($data_dir, $username, "move_to_trash", $movetotrash);
   setPref($data_dir, $username, "wrap_at", $wrapat);
   setPref($data_dir, $username, "editor_size", $editorsize);
   setPref($data_dir, $username, "use_signature", $usesignature);
   setPref($data_dir, $username, "left_refresh", $leftrefresh);

   setSig($data_dir, $username, stripslashes($signature_edit));

   echo "<HTML>";
   echo "<BODY TEXT=\"$color[8]\" BGCOLOR=\"$color[4]\" LINK=\"$color[7]\" VLINK=\"$color[7]\" ALINK=\"$color[7]\">\n";
   displayPageHeader($color, "None");
   echo "<FONT FACE=\"Arial,Helvetica\">";
   echo "<BR><BR><BR><CENTER><B>";
   echo _("Options Saved!");
   echo "</B><BR><BR>";
   echo _("Your options have been saved.");
   echo "<BR><A HREF=\"webmail.php\" TARGET=_top>";
   echo _("Click here");
   echo "</A> ";
   echo _("to continue.");
   echo "</CENTER></FONT>";
   echo "</BODY></HTML>";
?>
