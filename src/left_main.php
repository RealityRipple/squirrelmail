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
<BODY BGCOLOR=A0B8C8 TEXT="#000000" LINK="#000000" VLINK="#000000" ALINK="#000000" onUnLoad="unSetCookies()">
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

   echo "<FONT FACE=\"Arial,Helvetica\" COLOR=000000 SIZE=4><B><CENTER>";
   echo "Folders</B><BR></FONT>";
   echo "<FONT FACE=\"Arial,Helvetica\" COLOR=000000 SIZE=2>(<A HREF=\"../src/left_main.php\" TARGET=left>refresh folder list</A>)</FONT></CENTER><BR>";
   echo "<FONT FACE=\"Arial,Helvetica\">\n";
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
      selectMailbox($imapConnection, $mailbox, $numNessages);
      $unseen = unseenMessages($imapConnection, $numUnseen);
      if ($unseen)
         echo "<B>";
      echo "<a href=\"right_main.php?sort=0&startMessage=1&mailbox=$mailboxURL\" target=\"right\" style=\"text-decoration:none\"><FONT FACE=\"Arial,Helvetica\">";
      echo readShortMailboxName($mailbox, ".");
      if (($move_to_trash == true) && ($mailbox == $trash_folder)) {
         $urlMailbox = urlencode($mailbox);
         echo "</A>&nbsp;&nbsp;&nbsp;&nbsp;(<B><A HREF=\"empty_trash.php?numMessages=$numMessages&mailbox=$urlMailbox\" TARGET=right style=\"text-decoration:none\">empty</A></B>)";
      }
      echo "</FONT></a>\n";
      if ($numUnseen > 0) {
         echo "</B>&nbsp;</FONT><FONT FACE=\"Arial,Helvetica\" SIZE=2>($numUnseen)</FONT>";
      }
      echo "<BR>\n";
   }
   echo "</FONT>";

   fclose($imapConnection);
                                  
?>
</FONT></BODY></HTML>
