<?
   include("../config/config.php");
   include("../functions/strings.php");
   include("../functions/page_header.php");
   include("../functions/imap.php");

   $imapConnection = fsockopen($imapServerAddress, 143, &$errorNumber, &$errorString);
   if (!$imapConnection) {
      echo "Error connecting to IMAP Server.<br>";
      echo "$errorNumber : $errorString<br>";
      exit;
   }
   $serverInfo = fgets($imapConnection, 256);

   fputs($imapConnection, "1 login $username $key\n");
   $read = fgets($imapConnection, 1024);
   echo $read;

   if ($subfolder == "INBOX")
      fputs($imapConnection, "1 create \"user.$username.$folder_name\"\n");
   else
      fputs($imapConnection, "1 create \"user.$username.$subfolder.$folder_name\"\n");

   fputs($imapConnection, "1 logout\n");

   echo "<BR><BR><A HREF=\"folders.php\">Return</A>";
?>


