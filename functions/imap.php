<?
   /**
    **  imap.php
    **
    **  Functions for the IMAP connection
    **
    **/

   /** Read from the connection until we get either an OK or BAD message. **/
   function imapReadData($connection, $pre, $handle_errors, &$response, &$message) {
      require ("../config/config.php");

      $read = fgets($connection, 1024);
      $counter = 0;
      while ((substr($read, 0, strlen("$pre OK")) != "$pre OK") &&
             (substr($read, 0, strlen("$pre BAD")) != "$pre BAD") &&
             (substr($read, 0, strlen("$pre NO")) != "$pre NO")) {
         $data[$counter] = $read;
         $read = fgets($connection, 1024);
         $counter++;
      }
      if (substr($read, 0, strlen("$pre OK")) == "$pre OK") {
         $response = "OK";
         $message = trim(substr($read, strlen("$pre OK"), strlen($read)));
      } else if (substr($read, 0, strlen("$pre BAD")) == "$pre BAD") {
         $response = "BAD";
         $message = trim(substr($read, strlen("$pre BAD"), strlen($read)));
      } else {
         $response = "NO";
         $message = trim(substr($read, strlen("$pre NO"), strlen($read)));
      }

      if ($handle_errors == true) {
         if ($response == "NO") {
            echo "<BR><B><FONT FACE=\"Arial,Helvetica\" COLOR=FF0000>ERROR</FONT FACE=\"Arial,Helvetica\"><FONT FACE=\"Arial,Helvetica\" COLOR=CC0000>:  Could not complete request.</B> </FONT FACE=\"Arial,Helvetica\"><BR><FONT FACE=\"Arial,Helvetica\" COLOR=CC0000>&nbsp;&nbsp;<B>Reason given:</B> $message</FONT FACE=\"Arial,Helvetica\"><BR><BR>";
            exit;
         } else if ($response == "BAD") {
            echo "<BR><B><FONT FACE=\"Arial,Helvetica\" COLOR=FF0000>ERROR</FONT FACE=\"Arial,Helvetica\"><FONT FACE=\"Arial,Helvetica\" COLOR=CC0000>:  Bad or malformed request.</B></FONT FACE=\"Arial,Helvetica\"><BR><FONT FACE=\"Arial,Helvetica\" COLOR=CC0000>&nbsp;&nbsp;<B>Server responded:</B> $message</FONT FACE=\"Arial,Helvetica\"><BR><BR>";
            exit;
         }
      }

      return $data;
   }

   /** Parse the incoming mailbox name and return a string that is the FOLDER.MAILBOX **/
   function findMailboxName($mailbox) {
      $mailbox = trim($mailbox);
      if (substr($mailbox,  strlen($mailbox)-1, strlen($mailbox)) == "\"") {
         $mailbox = substr($mailbox, 0, strlen($mailbox) - 1);
         $pos = strrpos($mailbox, "\"") + 1;
         $box = substr($mailbox, $pos, strlen($mailbox));
      } else {
         $box = substr($mailbox, strrpos($mailbox, " ")+1, strlen($mailbox));
      }
      return $box;
   }

   /** Finds the delimeter between mailboxes **/
   function findMailboxDelimeter($imapConnection) {
      fputs($imapConnection, ". list \"\" \"\"\n");
      $read = fgets($imapConnection, 1024);

      $pos = strrpos($read, "\"");
      $read = substr($read, 0, $pos);

      $pos = strrpos($read, "\"");
      $read = substr($read, 0, $pos);

      $pos = strrpos($read, "\"");
      $read = substr($read, 0, $pos);

      $pos = strrpos($read, "\"");
      $read = substr($read, $pos+1, strlen($read));

      $tmp = fgets($imapConnection, 1024);
      return $read;
   }

   function getMailboxFlags($mailbox) {
      $mailbox = trim($mailbox);
      $mailbox = substr($mailbox, strpos($mailbox, "(")+1, strlen($mailbox));
      $mailbox = substr($mailbox, 0, strpos($mailbox, ")"));
      $mailbox = str_replace("\\", "", $mailbox);
      $mailbox = strtolower($mailbox);
      $mailbox = explode(" ", $mailbox);
      return $mailbox;
   }

   // handles logging onto an imap server.
   function loginToImapServer($username, $key, $imapServerAddress, $hide) {
      require("../config/config.php");

      $imapConnection = fsockopen($imapServerAddress, 143, &$errorNumber, &$errorString);
      if (!$imapConnection) {
         echo "Error connecting to IMAP Server.<br>";
         echo "$errorNumber : $errorString<br>";
         exit;
      }
      $serverInfo = fgets($imapConnection, 256);
 
      // login
      fputs($imapConnection, "a001 LOGIN $username $key\n");
      $read = fgets($imapConnection, 1024);
      if ($debug_login == true) {
         echo "SERVER SAYS: $read<BR>";
      }

      if (substr($read, 0, 7) != "a001 OK") {
         if (!$hide) {
            if (substr($read, 0, 8) == "a001 BAD") {
               echo "Bad request: $read<BR>";
               exit;
            }
            else if (substr($read, 0, 7) == "a001 NO") {
               echo "<BR>";
               echo "<TABLE COLS=1 WIDTH=70% NOBORDER BGCOLOR=FFFFFF ALIGN=CENTER>";
               echo "   <TR>";
               echo "      <TD BGCOLOR=\"DCDCDC\">";
               echo "         <FONT FACE=\"Arial,Helvetica\" COLOR=CC0000><B><CENTER>ERROR</CENTER></B></FONT>";
               echo "   </TD></TR><TR><TD>";
               echo "      <CENTER><FONT FACE=\"Arial,Helvetica\"><BR>Unknown user or password incorrect.<BR><A HREF=\"login.php\" TARGET=_top>Click here to try again</A>.</FONT></CENTER>";
               echo "   </TD></TR>";
               echo "</TABLE>";
               echo "</BODY></HTML>";
               exit;
            }
            else {
               echo "Unknown error: $read<BR>";
               exit;
            }
         } else {
            exit;
         }
      }

      return $imapConnection;
   }

   /** must be sent in the form:  user.<USER>.<FOLDER> **/
   function createFolder($imapConnection, $folder, $type) {
      require ("../config/config.php");

      if (strtolower($type) == "noselect") {
         $dm = findMailboxDelimeter($imapConnection);
         $folder = "$folder$dm";
      } else {
         $folder = "$folder";
      }
      fputs($imapConnection, "1 create \"$folder\"\n");
      $data = imapReadData($imapConnection, "1", false, $response, $message);

      if ($response == "NO") {
         echo "<BR><B><FONT FACE=\"Arial,Helvetica\" COLOR=FF0000>ERROR</FONT FACE=\"Arial,Helvetica\"><FONT FACE=\"Arial,Helvetica\" COLOR=CC0000>:  Could not complete request.</B> </FONT FACE=\"Arial,Helvetica\"><BR><FONT FACE=\"Arial,Helvetica\" COLOR=CC0000>&nbsp;&nbsp;<B>Reason given:</B> $message</FONT FACE=\"Arial,Helvetica\"><BR><BR>";
         echo "<FONT FACE=\"Arial,Helvetica\">Possible solutions:<BR><LI>You may need to specify that the folder is a subfolder of INBOX</LI>";
         echo "<LI>Try renaming the folder to something different.</LI>";
         exit;
      } else if ($response == "BAD") {
         echo "<B><FONT FACE=\"Arial,Helvetica\" COLOR=FF0000>ERROR</FONT FACE=\"Arial,Helvetica\"><FONT FACE=\"Arial,Helvetica\" COLOR=CC0000>:  Bad or malformed request.</B></FONT FACE=\"Arial,Helvetica\"><BR><FONT FACE=\"Arial,Helvetica\" COLOR=CC0000>&nbsp;&nbsp;<B>Server responded:</B> $message</FONT FACE=\"Arial,Helvetica\"><BR><BR>";
         exit;
      }
   }

   function removeFolder($imapConnection, $folder) {
      fputs($imapConnection, "1 delete \"$folder\"\n");
      $data = imapReadData($imapConnection, "1", false, $response, $message);
      if ($response == "NO") {
         echo "<FONT FACE=\"Arial,Helvetica\" COLOR=FF0000><B>ERROR</B>:  Could not delete the folder $folder.</FONT>";
         echo "<FONT FACE=\"Arial,Helvetica\" COLOR=\"$color[8]\">Probable causes:</FONT><BR>";
         echo "<FONT FACE=\"Arial,Helvetica\" COLOR=\"$color[8]\"><LI>This folder may contain subfolders.  Delete all subfolders first</LI></FONT>";
         exit;
      } else if ($response == "BAD") {
         echo "<B><FONT COLOR=FF0000>ERROR</FONT><FONT COLOR=CC0000>:  Bad or malformed request.</B></FONT><BR><FONT COLOR=CC0000>&nbsp;&nbsp;<B>Server responded:</B> $message</FONT><BR><BR>";
         exit;
      }
   }

   /** Sends back two arrays, boxesFormatted and boxesUnformatted **/
   function getFolderList($imapConnection, &$boxes) {
      require ("../config/config.php");

      fputs($imapConnection, "1 list \"\" *\n");
      $str = imapReadData($imapConnection, "1", true, $response, $message);

      $dm = findMailboxDelimeter($imapConnection);
      $g = 0;
      for ($i = 0;$i < count($str); $i++) {
         $mailbox = chop($str[$i]);
         if (substr(findMailboxName($mailbox), 0, 1) != ".") {
            $boxes[$g]["RAW"] = $mailbox;

            $mailbox = findMailboxName($mailbox);
            $periodCount = countCharInString($mailbox, $dm);

            // indent the correct number of spaces.
            for ($j = 0;$j < $periodCount;$j++)
               $boxes[$g]["FORMATTED"] = $boxes[$g]["FORMATTED"] . "&nbsp;&nbsp;";

            $boxes[$g]["FORMATTED"] = $boxes[$g]["FORMATTED"] . readShortMailboxName($mailbox, $dm);
            $boxes[$g]["UNFORMATTED"] = $mailbox;
            $boxes[$g]["ID"] = $g;
            $g++;
         }
      }

      $original = $boxes;

      for ($i = 0; $i < count($original); $i++) {
         $boxes[$i]["UNFORMATTED"] = strtolower($boxes[$i]["UNFORMATTED"]);
      }

      $boxes = ary_sort($boxes, "UNFORMATTED", 1);

      for ($i = 0; $i < count($original); $i++) {
         for ($j = 0; $j < count($original); $j++) {
            if ($boxes[$i]["ID"] == $original[$j]["ID"]) {
               $boxes[$i]["UNFORMATTED"] = $original[$j]["UNFORMATTED"];
               $boxes[$i]["FORMATTED"] = $original[$j]["FORMATTED"];
               $boxes[$i]["RAW"] = $original[$j]["RAW"];
            }
         }
      }

      for ($i = 0; $i < count($boxes); $i++) {
         if ($boxes[$i]["UNFORMATTED"] == $special_folders[0]) {
            $boxesnew[0]["FORMATTED"] = $boxes[$i]["FORMATTED"];
            $boxesnew[0]["UNFORMATTED"] = trim($boxes[$i]["UNFORMATTED"]);
            $boxesnew[0]["RAW"] = trim($boxes[$i]["RAW"]);
            $boxes[$i]["USED"] = true;
         }
      }
      if ($list_special_folders_first == true) {
         for ($i = 0; $i < count($boxes); $i++) {
            for ($j = 1; $j < count($special_folders); $j++) {
               if (substr($boxes[$i]["UNFORMATTED"], 0, strlen($special_folders[$j])) == $special_folders[$j]) {
                  $pos = count($boxesnew);
                  $boxesnew[$pos]["FORMATTED"] = $boxes[$i]["FORMATTED"];
                  $boxesnew[$pos]["RAW"] = trim($boxes[$i]["RAW"]);
                  $boxesnew[$pos]["UNFORMATTED"] = trim($boxes[$i]["UNFORMATTED"]);
                  $boxes[$i]["USED"] = true;
               }
            }
         }
      }
      for ($i = 0; $i < count($boxes); $i++) {
         if (($boxes[$i]["UNFORMATTED"] != $special_folders[0]) &&
             ($boxes[$i]["UNFORMATTED"] != ".mailboxlist") &&
             ($boxes[$i]["USED"] == false))  {
            $pos = count($boxesnew);
            $boxesnew[$pos]["FORMATTED"] = $boxes[$i]["FORMATTED"];
            $boxesnew[$pos]["RAW"] = trim($boxes[$i]["RAW"]);
            $boxesnew[$pos]["UNFORMATTED"] = trim($boxes[$i]["UNFORMATTED"]);
            $boxes[$i]["USED"] = true;
         }
      }

      $boxes = $boxesnew;
   }

   function deleteMessages($imapConnection, $a, $b, $numMessages, $trash_folder, $move_to_trash, $auto_expunge, $mailbox) {
      /** check if they would like to move it to the trash folder or not */
      if ($move_to_trash == true) {
         $success = copyMessages($imapConnection, $a, $b, $trash_folder);
         if ($success == true)
            setMessageFlag($imapConnection, $a, $b, "Deleted");
      } else {
         setMessageFlag($imapConnection, $a, $b, "Deleted");
      }
   }
   function stripComments($line) {
      if (strpos($line, ";")) {
         $line = substr($line, 0, strpos($line, ";"));
      }

      if (strpos($line, "(") && strpos($line, ")")) {
         $full_line = $full_line . substr($line, 0, strpos($line, "("));
         $full_line = $full_line . substr($line, strpos($line, ")")+1, strlen($line) - strpos($line, ")"));
      } else {
         $full_line = $line;
      }
      return $full_line;
   }
?>
