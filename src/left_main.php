<?php
   /**
    **  left_main.php
    **  Copyright (c) 1999-2000 The SquirrelMail development team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    **  This is the code for the left bar.  The left bar shows the folders
    **  available, and has cookie information.
    **
    **  $Id$
    **/

   session_start();

   if (!isset($i18n_php))
      include ("../functions/i18n.php");

   if(!isset($username)) {
      set_up_language($squirrelmail_language, true);
	  include ("../themes/default_theme.php");
	  printf('<html><BODY TEXT="%s" BGCOLOR="%s" LINK="%s" VLINK="%s" ALINK="%s">',
			  $color[8], $color[4], $color[7], $color[7], $color[7]);
	  echo "</body></html>";
      exit;
   }


   if (!isset($strings_php))
      include("../functions/strings.php");
   if (!isset($config_php))
      include("../config/config.php");
   if (!isset($array_php))
      include("../functions/array.php");
   if (!isset($imap_php))
      include("../functions/imap.php");
   if (!isset($page_header_php))
      include("../functions/page_header.php");
   if (!isset($i18n_php))
      include("../functions/i18n.php");
   if (!isset($plugin_php))
      include("../functions/plugin.php");

   // open a connection on the imap port (143)
   $imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 10); // the 10 is to hide the output

   /** If it was a successful login, lets load their preferences **/
   include("../src/load_prefs.php");

   displayHtmlHeader();

   function formatMailboxName($imapConnection, $mailbox, $real_box, $delimeter, $unseen) {
      global $folder_prefix, $trash_folder, $sent_folder;
      global $color, $move_to_sent, $move_to_trash;
      global $unseen_notify, $unseen_type;
      
      $mailboxURL = urlencode($real_box);
      
      if ($unseen_notify == 2 && $real_box == "INBOX") {
	 $unseen = sqimap_unseen_messages($imapConnection, $numUnseen, $real_box);
         if ($unseen_type == 1 && $unseen > 0) {
            $unseen_string = "($unseen)";
            $unseen_found = true;
         } else if ($unseen_type == 2) {
            $numMessages = sqimap_get_num_messages($imapConnection, $real_box);
            $unseen_string = "<font color=\"$color[11]\">($unseen/$numMessages)</font>";
            $unseen_found = true;
         }
      } else if ($unseen_notify == 3) {
	 $unseen = sqimap_unseen_messages($imapConnection, $numUnseen, $real_box);
         if ($unseen_type == 1 && $unseen > 0) {
            $unseen_string = "($unseen)";
            $unseen_found = true;
         } else if ($unseen_type == 2) {
            $numMessages = sqimap_get_num_messages($imapConnection, $real_box);
            $unseen_string = "<font color=\"$color[11]\">($unseen/$numMessages)</font>";
            $unseen_found = true;
         }
      }
      
      $line = "<NOBR>";
      if ($unseen > 0)
         $line .= "<B>";

      $special_color = false;
      if ((strtolower($real_box) == "inbox") ||
	  (($real_box == $trash_folder) && ($move_to_trash)) ||
	  (($real_box == $sent_folder) && ($move_to_sent)))
	 $special_color = true;
      
      if ($special_color == true) {
         $line .= "<a href=\"right_main.php?sort=0&startMessage=1&mailbox=$mailboxURL\" target=\"right\" style=\"text-decoration:none\"><FONT COLOR=\"$color[11]\">";
         $line .= replace_spaces($mailbox);
         $line .= "</font></a>";
      } else {
         $line .= "<a href=\"right_main.php?sort=0&startMessage=1&mailbox=$mailboxURL\" target=\"right\" style=\"text-decoration:none\">";
         $line .= replace_spaces($mailbox);
         $line .= "</a>";
      }

      if ($unseen > 0)
         $line .= "</B>";
      
      if (isset($unseen_found) && $unseen_found) {
         $line .= "&nbsp;<small>$unseen_string</small>";
      }

      if (($move_to_trash == true) && ($real_box == $trash_folder)) {
         $urlMailbox = urlencode($real_box);
         $line .= "\n<small>\n";
         $line .= "  &nbsp;&nbsp;(<B><A HREF=\"empty_trash.php\" style=\"text-decoration:none\">"._("purge")."</A></B>)";
         $line .= "\n</small>\n";
      }
      $line .= "</NOBR>";
      return $line;
   }

   if (isset($left_refresh) && ($left_refresh != "None") && ($left_refresh != "")) {
      echo "<META HTTP-EQUIV=\"Expires\" CONTENT=\"Thu, 01 Dec 1994 16:00:00 GMT\">\n";
      echo "<META HTTP-EQUIV=\"Pragma\" CONTENT=\"no-cache\">\n"; 
      echo "<META HTTP-EQUIV=\"REFRESH\" CONTENT=\"$left_refresh;URL=left_main.php\">\n";
   }
   
   echo "\n<BODY BGCOLOR=\"$color[3]\" TEXT=\"$color[6]\" LINK=\"$color[6]\" VLINK=\"$color[6]\" ALINK=\"$color[6]\">\n";

   do_hook("left_main_before");

   $boxes = sqimap_mailbox_list($imapConnection);

   echo "<CENTER><FONT SIZE=4><B>";
   echo _("Folders") . "</B><BR></FONT>\n\n";

   echo "<small>(<A HREF=\"../src/left_main.php\" TARGET=\"left\">";
   echo _("refresh folder list");
   echo "</A>)</small></CENTER><BR>";
   $delimeter = sqimap_get_delimiter($imapConnection);

   for ($i = 0;$i < count($boxes); $i++) {
      $line = "";
      $mailbox = $boxes[$i]["formatted"];
      
      if (count($boxes[$i]["flags"])) {
         $noselect = false;
         for ($h = 0; $h < count($boxes[$i]["flags"]); $h++) {
            if (strtolower($boxes[$i]["flags"][$h]) == "noselect")
               $noselect = true;
         }
         if ($noselect == true) {
            $line .= "<FONT COLOR=\"$color[10]\">";
            $line .= replace_spaces($mailbox);
            $line .= "</FONT>";
         } else {
	    if (! isset($boxes[$i]["unseen"])) 
	        $boxes[$i]["unseen"] = 0;
            $line .= formatMailboxName($imapConnection, $mailbox, $boxes[$i]["unformatted"], $delimeter, $boxes[$i]["unseen"]);
         }
      } else {
        if (!isset($boxes[$i]["unseen"])) 
            $boxes[$i]["unseen"] = "";
         $line .= formatMailboxName($imapConnection, $mailbox, $boxes[$i]["unformatted"], $delimeter, $boxes[$i]["unseen"]);
      }
      echo "$line<BR>\n";
   }
   sqimap_logout($imapConnection);
   do_hook("left_main_after");
?>
</BODY></HTML>
