<?
   /** This code sends a mail.
    **
    ** There are 3 modes of operation:
    **  - Start new mail
    **  - Add an attachment
    **  - Send mail
    **/

   include("../config/config.php");
   include("../functions/strings.php");
   include("../functions/page_header.php");
   include("../functions/imap.php");
   include("../functions/date.php");
   include("../functions/mime.php");
   include("../functions/smtp.php");
   include("../functions/display_messages.php");

   include("../src/load_prefs.php");

   echo "<HTML><BODY TEXT=\"$color[8]\" BGCOLOR=\"$color[4]\" LINK=\"$color[7]\" VLINK=\"$color[7]\" ALINK=\"$color[7]\">\n";
   $imapConnection = sqimap_login($username, $key, $imapServerAddress, 0);
   displayPageHeader($color, "None");

   // This function is used when not sending or adding attachments
   function newMail () {
      global $forward_id, $imapConnection, $msg, $ent_num, $body_ary, $body,
         $reply_id, $send_to, $send_to_cc, $mailbox;

      $send_to = decodeHeader($send_to);
      $send_to_cc = decodeHeader($send_to_cc);

      if ($forward_id) {
         sqimap_mailbox_select($imapConnection, $mailbox);
         $msg = sqimap_get_message($imapConnection, $forward_id, $mailbox);
         
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
               $body = _("No Message");
            }
         }
         
         $type1 = $msg["ENTITIES"][$ent_num]["TYPE1"];
         
         $tmp = _("-------- Original Message ---------\n");
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
         sqimap_mailbox_select($imapConnection, $mailbox);
         $msg = sqimap_get_message($imapConnection, $reply_id, $mailbox);
         
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
               $body = _("No Message");
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
      
      $send_to = sqimap_find_email($send_to);
      
      $send_to = ereg_replace("\"", "", $send_to);
      $send_to = stripslashes($send_to);
      
      /** This formats a CC string if they hit "reply all" **/
      if ($send_to_cc != "") {
         $send_to_cc = ereg_replace(";", ",", $send_to_cc);
         $sendcc = explode(",", $send_to_cc);
         $send_to_cc = "";
         
         for ($i = 0; $i < count($sendcc); $i++) {
            $sendcc[$i] = trim($sendcc[$i]);
            if ($sendcc[$i] == "")
               continue;
            
            $sendcc[$i] = sqimap_find_email($sendcc[$i]);
            $whofrom = sqimap_find_displayable_name($msg["HEADER"]["FROM"]);
            $whoreplyto = sqimap_find_email($msg["HEADER"]["REPLYTO"]);
         
            if ((strtolower(trim($sendcc[$i])) != strtolower(trim($whofrom))) &&
                (strtolower(trim($sendcc[$i])) != strtolower(trim($whoreplyto))) &&
                (trim($sendcc[$i]) != "")) {
               $send_to_cc .= trim($sendcc[$i]) . ", ";
            }
         }
         $send_to_cc = trim($send_to_cc);
         if (substr($send_to_cc, -1) == ",") {
            $send_to_cc = substr($send_to_cc, 0, strlen($send_to_cc) - 1);
         }
      }
   } // function newMail()

   function showInputForm () {
      global $send_to, $send_to_cc, $reply_subj, $forward_subj, $body,
         $passed_body, $color, $use_signature, $signature, $editor_size,
         $attachments, $subject, $newmail;

      $subject = decodeHeader($subject);
      $reply_subj = decodeHeader($reply_subj);
      $forward_subj = decodeHeader($forward_subj);

      echo "\n<FORM action=\"compose.php\" METHOD=POST\n";
      echo "ENCTYPE=\"multipart/form-data\">\n";
      echo "<TABLE COLS=2 WIDTH=50 ALIGN=center CELLSPACING=0 BORDER=0>\n";
      echo "   <TR>\n";
      echo "      <TD WIDTH=50 BGCOLOR=\"$color[4]\" ALIGN=RIGHT>\n";
      echo "         <FONT FACE=\"Arial,Helvetica\">";
      echo _("To:");
      echo " </FONT>\n";
      echo "      </TD><TD WIDTH=% BGCOLOR=\"$color[4]\" ALIGN=LEFT>\n";
      if ($send_to)
         echo "         <INPUT TYPE=TEXT NAME=send_to VALUE=\"$send_to\" SIZE=60><BR>";
      else
         echo "         <INPUT TYPE=TEXT NAME=send_to SIZE=60><BR>";
      echo "      </TD>\n";
      echo "   </TR>\n";
      echo "   <TR>\n";
      echo "      <TD WIDTH=50 BGCOLOR=\"$color[4]\" ALIGN=RIGHT>\n";
      echo "         <FONT FACE=\"Arial,Helvetica\">"._("CC").":</FONT>\n";
      echo "      </TD><TD WIDTH=% BGCOLOR=\"$color[4]\" ALIGN=LEFT>\n";
      if ($send_to_cc)
         echo "         <INPUT TYPE=TEXT NAME=send_to_cc SIZE=60 VALUE=\"$send_to_cc\"><BR>";
      else
         echo "         <INPUT TYPE=TEXT NAME=send_to_cc SIZE=60><BR>";
      echo "      </TD>\n";
      echo "   </TR>\n";
      echo "   <TR>\n";
      echo "      <TD WIDTH=50 BGCOLOR=\"$color[4]\" ALIGN=RIGHT>\n";
      echo "         <FONT FACE=\"Arial,Helvetica\">BCC:</FONT>\n";
      echo "      </TD><TD WIDTH=% BGCOLOR=\"$color[4]\" ALIGN=LEFT>\n";
      if ($send_to_bcc)
         echo "         <INPUT TYPE=TEXT NAME=send_to_bcc VALUE=\"$send_to_bcc\" SIZE=60><BR>";
      else
         echo "         <INPUT TYPE=TEXT NAME=send_to_bcc SIZE=60><BR>";
      echo "      </TD>\n";
      echo "   </TR>\n";
      echo "   <TR>\n";
      echo "      <TD WIDTH=50 BGCOLOR=\"$color[4]\" ALIGN=RIGHT>\n";
      echo "         <FONT FACE=\"Arial,Helvetica\">";
      echo _("Subject:");
      echo " </FONT>\n";
      echo "      </TD><TD WIDTH=% BGCOLOR=\"$color[4]\" ALIGN=LEFT>\n";
      if ($reply_subj) {
         $reply_subj = str_replace("\"", "'", $reply_subj);
         $reply_subj = stripslashes($reply_subj);
         $reply_subj = trim($reply_subj);
         if (substr(strtolower($reply_subj), 0, 3) != "re:")
            $reply_subj = "Re: $reply_subj";
         echo "         <INPUT TYPE=TEXT NAME=subject SIZE=60 VALUE=\"$reply_subj\">";
      } else if ($forward_subj) {
         $forward_subj = str_replace("\"", "'", $forward_subj);
         $forward_subj = stripslashes($forward_subj);
         $forward_subj = trim($forward_subj);
         if ((substr(strtolower($forward_subj), 0, 4) != "fwd:") &&
             (substr(strtolower($forward_subj), 0, 5) != "[fwd:") &&
             (substr(strtolower($forward_subj), 0, 6) != "[ fwd:"))
            $forward_subj = "[Fwd: $forward_subj]";
         echo "         <INPUT TYPE=TEXT NAME=subject SIZE=50 VALUE=\"$forward_subj\">";
      } else {
         echo "         <INPUT TYPE=TEXT NAME=subject VALUE=\"$subject\" SIZE=50>";
      }
      echo "&nbsp;&nbsp;<INPUT TYPE=SUBMIT NAME=send VALUE=\"". _("Send") . "\">";
      echo "      </TD>\n";
      echo "   </TR>\n";

      echo "   <TR>\n";
      echo "      <TD BGCOLOR=\"$color[4]\" COLSPAN=2>\n";
      if ($use_signature == true && $newmail == true)
         echo "         &nbsp;&nbsp;<TEXTAREA NAME=body ROWS=20 COLS=\"$editor_size\" WRAP=HARD>". $body . "\n\n-- \n".$signature."</TEXTAREA><BR>";
      else
         echo "         &nbsp;&nbsp;<TEXTAREA NAME=body ROWS=20 COLS=\"$editor_size\" WRAP=HARD>".$body."</TEXTAREA><BR>\n";
      echo "      </TD>\n";
      echo "   </TR>\n";
      echo "   <TR><TD COLSPAN=2 ALIGN=CENTER><INPUT TYPE=SUBMIT NAME=send VALUE=\"";
      echo _("Send");
      echo "\"></TD></TR>\n";
      
      // This code is for attachments
      echo "   <tr>\n";
      echo "     <TD WIDTH=50 BGCOLOR=\"$color[0]\" VALIGN=TOP ALIGN=RIGHT>\n";
      echo "      <FONT FACE=\"Arial,Helvetica\">";
      echo "      <SMALL><BR></SMALL>"._("Attach:")."</FONT>\n";
      echo "      </td><td width=% ALIGN=left BGCOLOR=\"$color[0]\">\n";
      //      echo "      <INPUT TYPE=\"hidden\" name=\"MAX_FILE_SIZE\"\n";
      //      echo "      value=\"10000\">\n";
      echo "      <INPUT NAME=\"attachfile\" TYPE=\"file\">\n";
      echo "      &nbsp;&nbsp;<input type=\"submit\" name=\"attach\"\n";
      echo "      value=\"" . _("Add") ."\">\n";
      echo "     </td>\n";
      echo "     </font>\n";
      echo "   </tr>\n";
      if (isset($attachments) && count($attachments)>0) {
         echo "</tr><tr><td width=50 bgcolor=\"$color[0]\" align=right>\n";
         echo "&nbsp;";
         echo "</td><td width=% align=left bgcolor=\"$color[0]\">";
         while (list($localname, $remotename) = each($attachments)) {
            echo "<input type=\"checkbox\" name=\"delete[]\" value=\"$localname\">\n";
            echo "$remotename <input type=\"hidden\" name=\"attachments[$localname]\" value=\"$remotename\"><br>\n";
         }
         
         echo "<input type=\"submit\" name=\"do_delete\" value=\""._("Delete selected attachments")."\">\n";
         echo "</td></tr>";
      }
      // End of attachment code

      echo "</TABLE>\n";
      echo "</FORM>";
   }

   function showSentForm () {
      echo "<FONT FACE=\"Arial,Helvetica\">";
      echo "<BR><BR><BR><CENTER><B>Message Sent!</B><BR><BR>";
      echo "You will be automatically forwarded.<BR>If not, <A HREF=\"right_main.php\">click here</A>";
      echo "</CENTER></FONT>";
   }

   function checkInput () {
      global $body, $send_to, $subject;

      if ($body == "") {
         plain_error_message(_("You have not entered a message body."), $color);
         return false;
      } else if ($send_to == "") {
         displayPageHeader($color, "None");
         plain_error_message(_("You have not filled in the \"To:\" field."), $color);
         return false;
      } else if ($subject == "") {
         plain_error_message(_("You have not entered a subject."), $color);
         return false;
      }
      return true;
   } // function checkInput()

   if(isset($send)) {
      if (checkInput()) {
         sendMessage($send_to, $send_to_cc, $send_to_bcc, $subject, $body);
         showSentForm();
      } else {
         showInputForm();
      }
   } else if (isset($attach)) {
      $localfilename = md5("$attachfile, $attachfile_name, $REMOTE_IP, $REMOTE_PORT, $UNIQUE_ID, and everything else that may add entropy");
      $localfilename = $localfilename;
      
      // Put the file in a better place
      error_reporting(0); // Rename will produce error output if it fails
      if (!rename($attachfile, $attachment_dir.$localfilename)) {
         if (!copy($attachfile, $attachment_dir.$localfilename)) {
            plain_error_message(_("Could not move/copy file. File not attached"));
            $failed = true;
         }
      }
      // If it still exists, PHP will remove the original file

      if (!$failed) {
         // Write information about the file
         $fp = fopen ($attachment_dir.$localfilename.".info", "w");
         fputs ($fp, "$attachfile_type\n$attachfile_name\n");
         fclose ($fp);

         $attachments[$localfilename] = $attachfile_name;
      }
      
      showInputForm();
   } else if (isset($do_delete)) {
      while (list($key, $localname) = each($delete)) {
         array_splice ($attachments, $key, 1);
         unlink ($attachment_dir.$localname);
         unlink ($attachment_dir.$localname.".info");
      }

      showInputForm();
   } else {
      $newmail = true;
      newMail();
      showInputForm();
   }
?>
