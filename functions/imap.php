<?
   /**
    **  imap.php
    **
    **  Functions for the IMAP connection
    **
    **/

   /** Read from the connection until we get either an OK or BAD message. **/
   function imapReadData($connection) {
      $read = fgets($connection, 1024);
      $counter = 0;
      while ((substr($read, strpos($read, " ") + 1, 2) != "OK") && (substr($read, strpos($read, " ") + 1, 3) != "BAD")) {
         $data[$counter] = $read;
         $read = fgets($connection, 1024);
         $counter++;
      }
      return $data;
   }

   /** Parse the incoming mailbox name and return a string that is the FOLDER.MAILBOX **/
   function findMailboxName($mailbox) {
      // start at -2 so that we skip the initial quote at the end of the mailbox name
      $i = -2;
      $char = substr($mailbox, $i, 1);
      while ($char != "\"") {
         $i--;
         $temp .= $char;
         $char = substr($mailbox, $i, 1);
      }
      return strrev($temp);
   }

   // handles logging onto an imap server.
   function loginToImapServer($username, $key, $imapServerAddress) {
      $imapConnection = fsockopen($imapServerAddress, 143, &$errorNumber, &$errorString);
      if (!$imapConnection) {
         echo "Error connecting to IMAP Server.<br>";
         echo "$errorNumber : $errorString<br>";
         exit;
      }
      $serverInfo = fgets($imapConnection, 256);
 
      // login
      fputs($imapConnection, "1 login $username $key\n");
      $read = fgets($imapConnection, 1024);
 
      if (strpos($read, "NO")) {
         error_username_password_incorrect();
         exit;
      }
      
      return $imapConnection;
   }

   /** must be sent in the form:  user.<USER>.<FOLDER> **/
   function createFolder($imapConnection, $folder) {
      fputs($imapConnection, "1 create \"$folder\"\n");
   }

   /** must be sent in the form:  user.<USER>.<FOLDER> **/
   function deleteFolder($imapConnection, $folder) {
      fputs($imapConnection, "1 delete \"$folder\"\n");
   }
?>
