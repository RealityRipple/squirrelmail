<?php
   session_start();

   if (!isset($config_php))
      include("../config/config.php");
   if (!isset($strings_php))
      include("../functions/strings.php");
   if (!isset($page_header_php))
      include("../functions/page_header.php");
   if (!isset($dipslay_messages_php))
      include("../functions/display_messages.php");
   if (!isset($imap_php))
      include("../functions/imap.php");
   if (!isset($array_php))
      include("../functions/array.php");

   include("../src/load_prefs.php");


   setPref($data_dir, $username, "full_name", stripslashes($full_name));
   setPref($data_dir, $username, "email_address", stripslashes($email_address));
   setPref($data_dir, $username, "reply_to", stripslashes($reply_to));
   setPref($data_dir, $username, "chosen_theme", $chosentheme);
   setPref($data_dir, $username, "show_num", $shownum);
   setPref($data_dir, $username, "wrap_at", $wrapat);
   setPref($data_dir, $username, "editor_size", $editorsize);
   setPref($data_dir, $username, "use_signature", $usesignature);
   setPref($data_dir, $username, "left_refresh", $leftrefresh);
   setPref($data_dir, $username, "language", $language);
   setPref($data_dir, $username, "left_size", $leftsize);
   setPref($data_dir, $username, "folder_prefix", $folderprefix);

	if ($trash != "none") {
   	setPref($data_dir, $username, "move_to_trash", true);
		setPref($data_dir, $username, "trash_folder", $trash);
	} else {
   	setPref($data_dir, $username, "move_to_trash", false);
		setPref($data_dir, $username, "trash_folder", "");
	}
   
	if ($sent != "none") {
   	setPref($data_dir, $username, "move_to_sent", true);
		setPref($data_dir, $username, "sent_folder", $sent);
	} else {
   	setPref($data_dir, $username, "move_to_sent", false);
		setPref($data_dir, $username, "sent_folder", "");
	}
   
   setSig($data_dir, $username, stripslashes($signature_edit));

   setcookie("squirrelmail_language", $language, time()+2592000);
   $squirrelmail_language = $language;

   echo "<HTML>";
   echo "<BODY TEXT=\"$color[8]\" BGCOLOR=\"$color[4]\" LINK=\"$color[7]\" VLINK=\"$color[7]\" ALINK=\"$color[7]\">\n";
   displayPageHeader($color, "None");
   echo "<BR><BR><BR><CENTER><B>";
   echo _("Options Saved!");
   echo "</B><BR><BR>";
   echo _("Your options have been saved.");
   echo "<BR><A HREF=\"webmail.php\" TARGET=_top>";
   echo _("Click here");
   echo "</A> ";
   echo _("to continue.");
   echo "</CENTER>";
   echo "</BODY></HTML>";
?>
