<?
   include("../config/config.php");
   include("../functions/strings.php");
   include("../functions/page_header.php");
   include("../functions/imap.php");
   include("../functions/mailbox.php");
   include("../functions/date.php");
   include("../functions/mime.php");


   echo "<HTML><BODY TEXT=\"$color[8]\" BGCOLOR=\"$color[4]\" LINK=\"$color[7]\" VLINK=\"$color[7]\" ALINK=\"$color[7]\">\n";
   $imapConnection = loginToImapServer($username, $key, $imapServerAddress);
   displayPageHeader($color, "None");

   if ($forward_id) {
      selectMailbox($imapConnection, $mailbox, $numMessages);
      $msg = fetchMessage($imapConnection, $forward_id, $mailbox);

      if (containsType($msg, "text", "html", $ent_num)) {
         $body = decodeBody($msg["ENTITIES"][$ent_num]["BODY"], $msg["ENTITIES"][$ent_num]["ENCODING"]);
      } else if (containsType($msg, "text", "plain", $ent_num)) {
         $body = decodeBody($msg["ENTITIES"][$ent_num]["BODY"], $msg["ENTITIES"][$ent_num]["ENCODING"]);
      }
      // add other primary displaying msg types here
      else {
         // find any type that's displayable
         if (containsType($msg, "text", "any_type", $ent_num)) {
            $body = decodeBody($msg["ENTITIES"][$ent_num]["BODY"], $msg["ENTITIES"][$ent_num]["ENCODING"]);
         } else if (containsType($msg, "msg", "any_type", $ent_num)) {
            $body = decodeBody($msg["ENTITIES"][$ent_num]["BODY"], $msg["ENTITIES"][$ent_num]["ENCODING"]);
         } else {
            $body = "No Message";
         }
      }

      $type1 = $msg["ENTITIES"][$ent_num]["TYPE1"];

      $tmp = "-------- Original Message ---------\n";
      $body_ary = explode("\n", $body);
      $body = "";
      for ($i=0;$i < count($body_ary);$i++) {
         if ($type1 == "html")
            $tmp .= strip_tags($body_ary[$i]);
         else
            $tmp .= $body_ary[$i];
         $body = "$body$tmp\n";
         $tmp = "";
      }
   }

   if ($reply_id) {
      selectMailbox($imapConnection, $mailbox, $numMessages);
      $msg = fetchMessage($imapConnection, $reply_id, $mailbox);

      if (containsType($msg, "text", "html", $ent_num)) {
         $body = decodeBody($msg["ENTITIES"][$ent_num]["BODY"], $msg["ENTITIES"][$ent_num]["ENCODING"], false);
      } else if (containsType($msg, "text", "plain", $ent_num)) {
         $body = decodeBody($msg["ENTITIES"][$ent_num]["BODY"], $msg["ENTITIES"][$ent_num]["ENCODING"], false);
      }
      // add other primary displaying msg types here
      else {
         // find any type that's displayable
         if (containsType($msg, "text", "any_type", $ent_num)) {
            $body = decodeBody($msg["ENTITIES"][$ent_num]["BODY"], $msg["ENTITIES"][$ent_num]["ENCODING"], false);
         } else if (containsType($msg, "msg", "any_type", $ent_num)) {
            $body = decodeBody($msg["ENTITIES"][$ent_num]["BODY"], $msg["ENTITIES"][$ent_num]["ENCODING"], false);
         } else {
            $body = "No Message";
         }
      }

      $type1 = $msg["ENTITIES"][$ent_num]["TYPE1"];

      $body_ary = explode("\n", $body);
      $body = "";
      for ($i=0;$i < count($body_ary);$i++) {
         if ($type1 == "html")
            $tmp = strip_tags($body_ary[$i]);
         else
            $tmp = $body_ary[$i];
         $body = "$body> $tmp\n";
      }
   }

   // Add some decoding information
   $send_to = encodeEmailAddr($send_to);
   // parses the field and returns only the email address
   $send_to = decodeEmailAddr($send_to);

   $send_to = strtolower($send_to);
   $send_to = ereg_replace("\"", "", $send_to);
   $send_to = stripslashes($send_to);

   echo "<FORM action=\"compose_send.php\" METHOD=POST>\n";
   echo "<TABLE COLS=2 WIDTH=50 ALIGN=CENTER CELLSPACING=0 BORDER=0>\n";
   echo "   <TR>\n";
   echo "      <TD WIDTH=50 BGCOLOR=\"$color[4]\" ALIGN=RIGHT>\n";
   echo "         <FONT FACE=\"Arial,Helvetica\">To: </FONT>\n";
   echo "      </TD><TD WIDTH=% \"$color[4]\" ALIGN=LEFT>\n";
   if ($send_to)
      echo "         <INPUT TYPE=TEXT NAME=passed_to VALUE=\"$send_to\" SIZE=60><BR>";
   else
      echo "         <INPUT TYPE=TEXT NAME=passed_to SIZE=60><BR>";
   echo "      </TD>\n";
   echo "   </TR>\n";
   echo "   <TR>\n";
   echo "      <TD WIDTH=50 BGCOLOR=\"$color[4]\" ALIGN=RIGHT>\n";
   echo "         <FONT FACE=\"Arial,Helvetica\">CC:</FONT>\n";
   echo "      </TD><TD WIDTH=% BGCOLOR=\"$color[4]\" ALIGN=LEFT>\n";
   echo "         <INPUT TYPE=TEXT NAME=passed_cc SIZE=60><BR>";
   echo "      </TD>\n";
   echo "   </TR>\n";
   echo "   <TR>\n";
   echo "      <TD WIDTH=50 BGCOLOR=\"$color[4]\" ALIGN=RIGHT>\n";
   echo "         <FONT FACE=\"Arial,Helvetica\">BCC:</FONT>\n";
   echo "      </TD><TD WIDTH=% BGCOLOR=\"$color[4]\" ALIGN=LEFT>\n";
   echo "         <INPUT TYPE=TEXT NAME=passed_bcc SIZE=60><BR>";
   echo "      </TD>\n";
   echo "   </TR>\n";

   echo "   <TR>\n";
   echo "      <TD WIDTH=50 BGCOLOR=\"$color[4]\" ALIGN=RIGHT>\n";
   echo "         <FONT FACE=\"Arial,Helvetica\">Subject:</FONT>\n";
   echo "      </TD><TD WIDTH=% BGCOLOR=\"$color[4]\" ALIGN=LEFT>\n";
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
   echo "      <TD BGCOLOR=\"$color[4]\" COLSPAN=2>\n";
   echo "         &nbsp;&nbsp;<TEXTAREA NAME=passed_body ROWS=20 COLS=76 WRAP=HARD>$body</TEXTAREA><BR>";
   echo "      </TD>";
   echo "   </TR>\n";
   echo "</TABLE>\n";
   echo "<CENTER><INPUT TYPE=SUBMIT VALUE=\"Send\"></CENTER>";
   echo "</FORM>";
?>