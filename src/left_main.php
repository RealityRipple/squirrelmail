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
?>
<HTML>
<?
   include("../config/config.php");
   include("../functions/array.php");
   include("../functions/strings.php");
   include("../functions/imap.php");
   include("../functions/mailbox.php");

   include("../src/load_prefs.php");

   function formatMailboxName($imapConnection, $mailbox, $delimeter, $color) {
      require ("../config/config.php");

      $mailboxURL = urlencode($mailbox);
      selectMailbox($imapConnection, $mailbox, $numNessages);
      $unseen = unseenMessages($imapConnection, $numUnseen);

      echo "<NOBR>";
      if ($unseen)
         $line .= "<B>";

      $special_color = false;
      for ($i = 0; $i < count($special_folders); $i++) {
         if (($special_folders[$i] == $mailbox) && ($use_special_folder_color == true))
            $special_color = true;
      }

      if ($special_color == true) {
         $line .= "<a href=\"right_main.php?sort=0&startMessage=1&mailbox=$mailboxURL\" target=\"right\" style=\"text-decoration:none\"><FONT FACE=\"Arial,Helvetica\"  COLOR=\"$color[11]\">";
         $line .= readShortMailboxName($mailbox, $delimeter);
         $line .= "</font></a>";
      } else {
         $line .= "<a href=\"right_main.php?sort=0&startMessage=1&mailbox=$mailboxURL\" target=\"right\" style=\"text-decoration:none\"><FONT FACE=\"Arial,Helvetica\">";
         $line .= readShortMailboxName($mailbox, $delimeter);
         $line .= "</font></a>";
      }

      if ($unseen)
         $line .= "</B>";

      if ($numUnseen > 0) {
         $line .= "&nbsp;<FONT FACE=\"Arial,Helvetica\" SIZE=2>($numUnseen)</FONT>";
      }

      if (($move_to_trash == true) && (trim($mailbox) == $trash_folder)) {
         $urlMailbox = urlencode($mailbox);
         $line .= "<FONT FACE=\"Arial,Helvetica\" SIZE=2>";
         $line .= "&nbsp;&nbsp;&nbsp;&nbsp;(<B><A HREF=\"empty_trash.php?numMessages=$numMessages&mailbox=$urlMailbox\" TARGET=right style=\"text-decoration:none\">empty</A></B>)";
         $line .= "</FONT></a>\n";
      }

      echo "</NOBR>";
      return $line;
   }

   echo "<BODY BGCOLOR=\"$color[3]\" TEXT=\"$color[6]\" LINK=\"$color[6]\" VLINK=\"$color[6]\" ALINK=\"$color[6]\">";
   echo "<FONT FACE=\"Arial,Helvetica\">";
   // open a connection on the imap port (143)
   $imapConnection = loginToImapServer($username, $key, $imapServerAddress, 10); // the 10 is to hide the output

   getFolderList($imapConnection, $boxes);

   echo "<FONT FACE=\"Arial,Helvetica\" SIZE=4><B><CENTER>";
   echo "Folders</B><BR></FONT>";

   echo "<FONT FACE=\"Arial,Helvetica\" SIZE=2>(<A HREF=\"../src/left_main.php\" TARGET=left>refresh folder list</A>)</FONT></CENTER><BR>";
   echo "<FONT FACE=\"Arial,Helvetica\">\n";
   $delimeter = findMailboxDelimeter($imapConnection);
   for ($i = 0;$i < count($boxes); $i++) {
      $mailbox = $boxes[$i]["UNFORMATTED"];
      $boxFlags = getMailboxFlags($boxes[$i]["RAW"]);

      $boxCount = countCharInString($mailbox, $delimeter);

      $line = "";
      // indent the correct number of spaces.
      for ($j = 0;$j < $boxCount;$j++)
         $line .= "&nbsp;&nbsp;";

      if (trim($boxFlags[0]) != "") {
         $noselect = false;
         for ($h = 0; $h < count($boxFlags); $h++) {
            if (strtolower($boxFlags[$h]) == "noselect")
               $noselect = true;
         }

         if ($noselect == true) {
            $line .= "<FONT COLOR=\"$color[10]\" FACE=\"Arial,Helvetica\">";
            $line .= readShortMailboxName($mailbox, $delimeter);
            $line .= "</FONT><FONT FACE=\"Arial,Helvetica\">";
         } else {
            $line .= formatMailboxName($imapConnection, $mailbox, $delimeter, $color);
         }
      } else {
         $line .= formatMailboxName($imapConnection, $mailbox, $delimeter, $color);
      }
      echo "$line<BR>";
   }

   echo "</FONT>";

   fclose($imapConnection);
                                  
?>
</FONT></BODY></HTML>
