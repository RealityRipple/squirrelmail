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

   function formatMailboxName($imapConnection, $mailbox, $delimeter) {
      require ("../config/config.php");

      $mailboxURL = urlencode($mailbox);
      selectMailbox($imapConnection, $mailbox, $numNessages);
      $unseen = unseenMessages($imapConnection, $numUnseen);

      if ($unseen)
         $line .= "<B>";

      $line .= "<a href=\"right_main.php?sort=0&startMessage=1&mailbox=$mailboxURL\" target=\"right\" style=\"text-decoration:none\"><FONT FACE=\"Arial,Helvetica\">";
      $line .= readShortMailboxName($mailbox, $delimeter);
      if (($move_to_trash == true) && (trim($mailbox) == $trash_folder)) {
         $urlMailbox = urlencode($mailbox);
         $line .= "</A>&nbsp;&nbsp;&nbsp;&nbsp;(<B><A HREF=\"empty_trash.php?numMessages=$numMessages&mailbox=$urlMailbox\" TARGET=right style=\"text-decoration:none\">empty</A></B>)";
      }
      $line .= "</FONT></a>\n";
      if ($numUnseen > 0) {
         $line .= "</B>&nbsp;</FONT><FONT FACE=\"Arial,Helvetica\" SIZE=2>($numUnseen)</FONT>";
      }
      return $line;
   }

   echo "<BODY BGCOLOR=\"$color[3]\" TEXT=\"$color[6]\" LINK=\"$color[6]\" VLINK=\"$color[6]\" ALINK=\"$color[6]\">";
   echo "<FONT FACE=\"Arial,Helvetica\">";
   // open a connection on the imap port (143)
   $imapConnection = loginToImapServer($username, $key, $imapServerAddress, 10); // the 10 is to hide the output

   fputs($imapConnection, "1 list \"\" *\n");
   $str = imapReadData($imapConnection, "1", true, $response, $message);

   echo "<FONT FACE=\"Arial,Helvetica\" SIZE=4><B><CENTER>";
   echo "Folders</B><BR></FONT>";
   echo "<FONT FACE=\"Arial,Helvetica\" SIZE=2>(<A HREF=\"../src/left_main.php\" TARGET=left>refresh folder list</A>)</FONT></CENTER><BR>";
   echo "<FONT FACE=\"Arial,Helvetica\">\n";
   $delimeter = findMailboxDelimeter($imapConnection);
   for ($i = 0;$i < count($str); $i++) {
      $mailbox = Chop($str[$i]);
      $boxFlags = getMailboxFlags($mailbox);
      $mailbox = findMailboxName($mailbox);

      $boxCount = countCharInString($mailbox, $delimeter);

      $line = "";
      // indent the correct number of spaces.
      for ($j = 0;$j < $boxCount;$j++)
         $line .= "&nbsp;&nbsp;";

      if (trim($boxFlags[0]) != "") {
         for ($h = 0; $h < count($boxFlags); $h++) {
            if (strtolower($boxFlags[$h]) == "noselect") {
               $line .= "<FONT COLOR=\"$color[10]\" FACE=\"Arial,Helvetica\">";
               $line .= readShortMailboxName($mailbox, $delimeter);
               $line .= "</FONT><FONT FACE=\"Arial,Helvetica\">";
            } else {
               $line .= formatMailboxName($imapConnection, $mailbox, $delimeter);
            }
         }
      } else {
         $line .= formatMailboxName($imapConnection, $mailbox, $delimeter);
      }
      $folder_list[$i]["FORMATTED"] = trim($line);
      $folder_list[$i]["PLAIN"] = trim($mailbox);
      $folder_list[$i]["ID"] = $i;
   }

   /** Alphebetize the folders */
   $original = $folder_list;

   for ($i = 0; $i < count($original); $i++) {
      $folder_list[$i]["PLAIN"] = strtolower($folder_list[$i]["PLAIN"]);
   }

   $folder_list = ary_sort($folder_list, "PLAIN", 1);

   for ($i = 0; $i < count($original); $i++) {
      for ($j = 0; $j < count($original); $j++) {
         if ($folder_list[$i]["ID"] == $original[$j]["ID"]) {
            $folder_list[$i]["PLAIN"] = $original[$j]["PLAIN"];
            $folder_list[$i]["FORMATTED"] = $original[$j]["FORMATTED"];
         }
      }
   }

   /** If it is the inbox, list it first **/
   for ($i = 0; $i < count($folder_list); $i++) {
      if ($folder_list[$i]["PLAIN"] == $special_folders[0]) {
         echo "<FONT FACE=\"Arial,Helvetica\">";
         echo trim($folder_list[$i]["FORMATTED"]);
         echo "</FONT><BR>";
         $folder_list[$i]["USED"] = true;
      }
   }
   /** Now the other special folders **/
   if ($list_special_folders_first == true) {
      for ($i = 0; $i < count($folder_list); $i++) {
         for ($j = 1; $j < count($special_folders); $j++) {
            if (substr($folder_list[$i]["PLAIN"], 0, strlen($special_folders[$j])) == $special_folders[$j]) {
               echo "<FONT FACE=\"Arial,Helvetica\">";
               echo trim($folder_list[$i]["FORMATTED"]);
               echo "</FONT><BR>";
               $folder_list[$i]["USED"] = true;
            }
         }
      }
   }
   /** Then list all the other ones  (not equal to INBOX)         **/
   /**   NOTE:  .mailboxlist is a netscape thing.. just ignore it **/
   for ($i = 0; $i < count($folder_list); $i++) {
      if (($folder_list[$i]["PLAIN"] != $special_folders[0]) &&
          ($folder_list[$i]["PLAIN"] != ".mailboxlist") &&
          ($folder_list[$i]["USED"] == false))  {
         echo "<FONT FACE=\"Arial,Helvetica\">";
         echo trim($folder_list[$i]["FORMATTED"]);
         echo "</FONT><BR>";
      }
   }

   echo "</FONT>";

   fclose($imapConnection);
                                  
?>
</FONT></BODY></HTML>
