<?php
   /**
    **  options_submit.php
    **
    **  Copyright (c) 1999-2000 The SquirrelMail development team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    **  This script saves all the options to the proper file when the 
    **  submit button is pressed. Also displays conformation message.
    **/


   session_start();

   if (!isset($config_php))
      include("../config/config.php");
   if (!isset($strings_php))
      include("../functions/strings.php");
   if (!isset($page_header_php))
      include("../functions/page_header.php");
   if (!isset($dipslay_messages_php))
      include("../functions/display_messages.php");
   if (!isset($array_php))
      include("../functions/array.php");
   if (!isset($auth_php))
      include ("../functions/auth.php");

   include("../src/load_prefs.php");

   is_logged_in();

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
   setPref($data_dir, $username, "use_javascript_addr_book", $javascript_abook);

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
