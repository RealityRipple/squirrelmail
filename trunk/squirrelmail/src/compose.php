<?
   include("../config/config.php");
   include("../functions/strings.php");
   include("../functions/page_header.php");
   include("../functions/imap.php");
   include("../functions/mailbox.php");
   include("../functions/date.php");
   include("../functions/mime.php");


   echo "<HTML><BODY TEXT=\"#000000\" BGCOLOR=\"#FFFFFF\" LINK=\"#0000EE\" VLINK=\"#0000EE\" ALINK=\"#0000EE\">\n";
   $imapConnection = loginToImapServer($username, $key, $imapServerAddress);
   displayPageHeader("None");

   if ($reply_id || $forward_id) {
      selectMailbox($imapConnection, $mailbox, $numMessages);
      if ($reply_id)
         $msg = fetchMessage($imapConnection, $reply_id);
      else
         $msg = fetchMessage($imapConnection, $forward_id);

      $body_ary = formatBody($msg);
      for ($i=0;$i < count($body_ary);$i++) {
         $tmp = strip_tags($body_ary[$i]);
         $tmp = substr($tmp, 0, strlen($tmp) -1);
         $body = "$body> $tmp";
      }
   }

   echo "<FORM action=\"compose_send.php\" METHOD=POST>\n";
   echo "<TABLE COLS=2 WIDTH=50 ALIGN=CENTER CELLSPACING=0 BORDER=0>\n";
   echo "   <TR>\n";
   echo "      <TD WIDTH=50 BGCOLOR=FFFFFF ALIGN=RIGHT>\n";
   echo "         <FONT FACE=\"Arial,Helvetica\">To: </FONT>\n";
   echo "      </TD><TD WIDTH=% BGCOLOR=FFFFFF ALIGN=LEFT>\n";
   if ($send_to)
      echo "         <INPUT TYPE=TEXT NAME=passed_to VALUE=\"$send_to\" SIZE=60><BR>";
   else
      echo "         <INPUT TYPE=TEXT NAME=passed_to SIZE=60><BR>";
   echo "      </TD>\n";
   echo "   </TR>\n";
   echo "   <TR>\n";
   echo "      <TD WIDTH=50 BGCOLOR=FFFFFF ALIGN=RIGHT>\n";
   echo "         <FONT FACE=\"Arial,Helvetica\">CC:</FONT>\n";
   echo "      </TD><TD WIDTH=% BGCOLOR=FFFFFF ALIGN=LEFT>\n";
   echo "         <INPUT TYPE=TEXT NAME=passed_cc SIZE=60><BR>";
   echo "      </TD>\n";
   echo "   </TR>\n";
   echo "   <TR>\n";
   echo "      <TD WIDTH=50 BGCOLOR=FFFFFF ALIGN=RIGHT>\n";
   echo "         <FONT FACE=\"Arial,Helvetica\">BCC:</FONT>\n";
   echo "      </TD><TD WIDTH=% BGCOLOR=FFFFFF ALIGN=LEFT>\n";
   echo "         <INPUT TYPE=TEXT NAME=passed_bcc SIZE=60><BR>";
   echo "      </TD>\n";
   echo "   </TR>\n";

   echo "   <TR>\n";
   echo "      <TD WIDTH=50 BGCOLOR=FFFFFF ALIGN=RIGHT>\n";
   echo "         <FONT FACE=\"Arial,Helvetica\">Subject:</FONT>\n";
   echo "      </TD><TD WIDTH=% BGCOLOR=FFFFFF ALIGN=LEFT>\n";
   if ($reply_subj) {
      $reply_subj = str_replace("\"", "'", $reply_subj);
      $reply_subj = stripslashes($reply_subj);
      $reply_subj = trim($reply_subj);
      if (substr(strtolower($reply_subj), 0, 3) != "re:")
         $reply_subj = "Re: $reply_subj";
      echo "         <INPUT TYPE=TEXT NAME=passed_subject SIZE=60 VALUE=\"$reply_subj\">";
   } else if ($forward_subj) {
      $forward_subj = str_replace("\"", "'", $forward_subj);
      $forward_subj = stripslashes($forward_subj);
      $forward_subj = trim($forward_subj);
      if ((substr(strtolower($forward_subj), 0, 4) != "fwd:") &&
          (substr(strtolower($forward_subj), 0, 5) != "[fwd:") &&
          (substr(strtolower($forward_subj), 0, 6) != "[ fwd:"))
         $forward_subj = "[Fwd: $forward_subj]";
      echo "         <INPUT TYPE=TEXT NAME=passed_subject SIZE=60 VALUE=\"$forward_subj\">";
   } else {
      echo "         <INPUT TYPE=TEXT NAME=passed_subject SIZE=60>";
   }
   echo "&nbsp;&nbsp;<INPUT TYPE=SUBMIT VALUE=\"Send\"><BR>";
   echo "      </TD>\n";
   echo "   </TR>\n";
   echo "   <TR>\n";
   echo "      <TD BGCOLOR=FFFFFF COLSPAN=2>\n";
   echo "         &nbsp;&nbsp;<TEXTAREA NAME=passed_body ROWS=20 COLS=76 WRAP=HARD>$body</TEXTAREA><BR>";
   echo "      </TD>";
   echo "   </TR>\n";
   echo "</TABLE>\n";
   echo "<CENTER><INPUT TYPE=SUBMIT VALUE=\"Send\"></CENTER>";
   echo "</FORM>";
?>