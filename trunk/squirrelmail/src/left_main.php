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
   include("../config/config.php");
   include("../functions/strings.php");
   include("../functions/imap.php");
   include("../functions/mailbox.php");

   // open a connection on the imap port (143)
   $imapConnection = loginToImapServer($username, $key, $imapServerAddress);

   fputs($imapConnection, "1 list \"\" *\n");
   $str = imapReadData($imapConnection);

   echo "<FONT FACE=\"Arial,Helvetica\"><B>";
   echo "<CENTER>$org_name</B><BR>";
   echo "Folders</CENTER>";
   echo "</B><BR></FONT>";
   echo "<code><FONT FACE=\"Arial,Helvetica\">\n";
   for ($i = 0;$i < count($str); $i++) {
      $mailbox = Chop($str[$i]);
      $mailbox = findMailboxName($mailbox);

      // find the quote at the begining of the mailbox name.
      //    i subtract 1 from the strlen so it doesn't find the quote at the end of the mailbox name.
      $periodCount = countCharInString($mailbox, ".");
      
      // indent the correct number of spaces.
      for ($j = 0;$j < $periodCount;$j++)
         echo "&nbsp;&nbsp;";
      
      $mailboxURL = urlencode($mailbox);
      echo "<a href=\"right_main.php?sort=0&startMessage=1&mailbox=$mailboxURL\" target=\"right\" style=\"text-decoration:none\"><FONT FACE=\"Arial,Helvetica\">";
      if ($doBold == true)
         echo "<B>";
      echo readShortMailboxName($mailbox, ".");
      if ($doBold == true)
         echo "</B>";
      echo "</FONT></a><br>\n";
   }
   echo "</code></FONT>";

   fclose($imapConnection);
                                  
?>
</FONT></BODY></HTML>
