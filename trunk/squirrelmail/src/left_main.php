<?
   /**
    **  left_main.php
    **
    **  This is the code for the left bar.  The left bar shows the folders
    **  available, and has cookie information.
    **
    **/

   if(!isset($username)) {
      echo "You need a valid user and password to access this page!";
      exit;
   }

   if (!isset($config_php))
      include("../config/config.php");
   if (!isset($array_php))
      include("../functions/array.php");
   if (!isset($strings_php))
      include("../functions/strings.php");
   if (!isset($imap_php))
      include("../functions/imap.php");
   if (!isset($page_header_php))
      include("../functions/page_header.php");

   echo "<HTML>";

   function formatMailboxName($imapConnection, $mailbox, $real_box, $delimeter, $color, $move_to_trash) {
      require ("../config/config.php");

      $mailboxURL = urlencode($real_box);
      sqimap_mailbox_select ($imapConnection, $real_box);
      $unseen = sqimap_unseen_messages($imapConnection, $numUnseen);

      echo "<NOBR>";
      if ($unseen)
         $line .= "<B>";

      $special_color = false;
      for ($i = 0; $i < count($special_folders); $i++) {
         if (($special_folders[$i] == $real_box) && ($use_special_folder_color == true))
            $special_color = true;
      }

      if ($special_color == true) {
         $line .= "<a href=\"right_main.php?sort=0&startMessage=1&mailbox=$mailboxURL\" target=\"right\" style=\"text-decoration:none\"><FONT COLOR=\"$color[11]\">";
         $line .= replace_spaces($mailbox);
         $line .= "</font></a>";
      } else {
         $line .= "<a href=\"right_main.php?sort=0&startMessage=1&mailbox=$mailboxURL\" target=\"right\" style=\"text-decoration:none\">";
         $line .= replace_spaces($mailbox);
         $line .= "</font></a>";
      }

      if ($unseen)
         $line .= "</B>";

      if ($numUnseen > 0) {
         $line .= "&nbsp;<small>($numUnseen)</small>";
      }

      if (($move_to_trash == true) && (trim($real_box) == $trash_folder)) {
         $urlMailbox = urlencode($real_box);
         $line .= "<small>";
         $line .= "&nbsp;&nbsp;&nbsp;&nbsp;(<B><A HREF=\"empty_trash.php?numMessages=$numMessages&mailbox=$urlMailbox\" TARGET=right style=\"text-decoration:none\">"._("purge")."</A></B>)";
         $line .= "</small></a>\n";
      }

      echo "</NOBR>";
      return $line;
   }

   // open a connection on the imap port (143)
   $imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 10); // the 10 is to hide the output

   /** If it was a successful login, lets load their preferences **/
   include("../src/load_prefs.php");
   if (isset($left_refresh) && ($left_refresh != "None") && ($left_refresh != "")) {
      echo "<META HTTP-EQUIV=\"Expires\" CONTENT=\"Thu, 01 Dec 1994 16:00:00 GMT\">";
      echo "<META HTTP-EQUIV=\"Pragma\" CONTENT=\"no-cache\">"; 
      echo "<META HTTP-EQUIV=\"REFRESH\" CONTENT=\"$left_refresh;URL=left_main.php\">";
   }
   
   echo "<BODY BGCOLOR=\"$color[3]\" TEXT=\"$color[6]\" LINK=\"$color[6]\" VLINK=\"$color[6]\" ALINK=\"$color[6]\">";

   $boxes = sqimap_mailbox_list($imapConnection);

   echo "<FONT SIZE=4><B><CENTER>";
   echo _("Folders") . "</B><BR></FONT>";

   echo "<small>(<A HREF=\"../src/left_main.php\" TARGET=\"left\">";
   echo _("refresh folder list");
   echo "</A>)</small></CENTER><BR>";
   $delimeter = sqimap_get_delimiter($imapConnection);

   for ($i = 0;$i < count($boxes); $i++) {
      $line = "";
      $mailbox = $boxes[$i]["formatted"];

      if ($boxes[$i]["flags"]) {
         $noselect = false;
         for ($h = 0; $h < count($boxes[$i]["flags"]); $h++) {
            if (strtolower($boxes[$i]["flags"][$h]) == "noselect")
               $noselect = true;
         }
         if ($noselect == true) {
            $line .= "<FONT COLOR=\"$color[10]\">";
            $line .= replace_spaces(readShortMailboxName($mailbox, $delimeter));
            $line .= "</FONT>";
         } else {
            $line .= formatMailboxName($imapConnection, $mailbox, $boxes[$i]["unformatted"], $delimeter, $color, $move_to_trash);
         }
      } else {
         $line .= formatMailboxName($imapConnection, $mailbox, $boxes[$i]["unformatted"], $delimeter, $color, $move_to_trash);
      }
      echo "$line<BR>";
   }


   fclose($imapConnection);
                                  
?>
</BODY></HTML>
