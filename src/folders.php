<HTML><BODY TEXT="#000000" BGCOLOR="#FFFFFF" LINK="#0000EE" VLINK="#0000EE" ALINK="#0000EE">
<?
   include("../config/config.php");
   include("../functions/strings.php");
   include("../functions/page_header.php");
   include("../functions/imap.php");

   displayPageHeader("None");

   echo "<TABLE WIDTH=100% COLS=1 ALIGN=CENTER>\n";
   echo "   <TR><TD BGCOLOR=DCDCDC ALIGN=CENTER>\n";
   echo "      <FONT FACE=\"Arial,Helvetica\">Folders</FONT>\n";
   echo "   </TD></TR>\n";
   echo "</TABLE>\n";

   $imapConnection = fsockopen($imapServerAddress, 143, &$errorNumber, &$errorString);
   if (!$imapConnection) {
      echo "Error connecting to IMAP Server.<br>";
      echo "$errorNumber : $errorString<br>";
      exit;
   }
   $serverInfo = fgets($imapConnection, 256);

   fputs($imapConnection, "1 login $username $key\n");
   $read = fgets($imapConnection, 1024);

   fputs($imapConnection, "1 list \"\" *\n");
   $str = imapReadData($imapConnection);

   for ($i = 0;$i < count($str); $i++) {
      $mailbox = Chop($str[$i]);
      // find the quote at the begining of the mailbox name.
      //    i subtract 1 from the strlen so it doesn't find the quote at the end of the mailbox name.
      $mailbox = findMailboxName($mailbox);
      $periodCount = countCharInString($mailbox, ".");

      // indent the correct number of spaces.
      for ($j = 0;$j < $periodCount;$j++)
         echo "&nbsp;&nbsp;";

      $mailboxURL = urlencode($mailbox);
      echo "<FONT FACE=\"Arial,Helvetica\">\n";
      echo readShortMailboxName($mailbox, ".");
      echo "</FONT><BR>\n";
   }
?>
</BODY></HTML>