<?
   if(!isset($username)) {
      echo "You need a valid user and password to access this page!";
      exit;
   }
?>
<HTML>
<HEAD>
   <SCRIPT LANGUAGE="JavaScript">
      function DeleteCookie (name) {
         var exp = new Date();  
         exp.setTime (exp.getTime() - 1);  
         // This cookie is history  
         var cval = GetCookie (name);  
         document.cookie = name + "=" + cval + "; expires=" + exp.toGMTString();
      }

      function unSetCookies() {
         DeleteCookie('username');
         DeleteCookie('key');
         DeleteCookie('logged_in');
         alert(document.cookie);
      }
   </SCRIPT>
</HEAD>
<BODY BGCOLOR=A0B8C8 TEXT="#000000" LINK="#0000EE" VLINK="#0000EE" ALINK="#0000EE" onUnLoad="unSetCookies()">
<FONT FACE="Arial,Helvetica">
<?
   include("config/config.php3");
   include("functions/strings.php3");
   include("functions/imap.php3");

   // *****************************************
   //    Parse the incoming mailbox name and return a string that is the FOLDER.MAILBOX
   // *****************************************
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

   // open a connection on the imap port (143)
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

   echo "<FONT FACE=\"Arial,Helvetica\"><B>";
   echo "<CENTER>$org_name</B><BR>";
   echo "Folders</CENTER>";
   echo "</B><BR></FONT>";
   echo "<code><FONT FACE=\"Arial,Helvetica\">\n";
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
      echo "<a href=\"mailboxMessageList.php3?sort=0&startMessage=0&mailbox=$mailboxURL\" target=\"right\" style=\"text-decoration:none\"><FONT FACE=\"Arial,Helvetica\">";
      echo readShortMailboxName($mailbox, ".");
      echo "</FONT></a><br>\n";
   }
   echo "</code></FONT>";

   fclose($imapConnection);
                                  
?>
</FONT></BODY></HTML>
