<?php
   /** 
    ** compose.php
    **
    **  Copyright (c) 1999-2000 The SquirrelMail development team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    ** This code sends a mail.
    **
    ** There are 3 modes of operation:
    **  - Start new mail
    **  - Add an attachment
    **  - Send mail
    **
    ** $Id$
    **/

   session_start();

   if (!isset($strings_php))
      include("../functions/strings.php");
   if (!isset($config_php))
      include("../config/config.php");
   if (!isset($page_header_php))
      include("../functions/page_header.php");
   if (!isset($imap_php))
      include("../functions/imap.php");
   if (!isset($date_php))
      include("../functions/date.php");
   if (!isset($mime_php))
      include("../functions/mime.php");
   if (!isset($smtp_php))
      include("../functions/smtp.php");
   if (!isset($display_messages_php))
      include("../functions/display_messages.php");
   if (!isset($auth_php))
      include ("../functions/auth.php");
   if (!isset($plugin_php))
      include ("../functions/plugin.php");

   include("../src/load_prefs.php");

   // This function is used when not sending or adding attachments
   function newMail () {
      global $forward_id, $imapConnection, $msg, $ent_num, $body_ary, $body,
         $reply_id, $send_to, $send_to_cc, $mailbox, $send_to_bcc, $editor_size;

      $send_to = sqStripSlashes(decodeHeader($send_to));
      $send_to_cc = sqStripSlashes(decodeHeader($send_to_cc));
      $send_to_bcc = sqStripSlashes(decodeHeader($send_to_bcc));

      if ($forward_id)
         $id = $forward_id;
      elseif ($reply_id)
         $id = $reply_id;


      if (isset($id)) {
         sqimap_mailbox_select($imapConnection, $mailbox);
         $message = sqimap_get_message($imapConnection, $id, $mailbox);
         $orig_header = $message->header;
         if ($ent_num)
            $message = getEntity($message, $ent_num);

         if ($message->header->type0 == "text" || $message->header->type1 == "message") {
            if ($ent_num)
               $body = decodeBody(mime_fetch_body($imapConnection, $id, $ent_num), $message->header->encoding);
            else
               $body = decodeBody(mime_fetch_body($imapConnection, $id, 1), $message->header->encoding);
         } else {
            $body = "";
         }
         
         if ($message->header->type1 == "html")
            $body = strip_tags($body);
         
         sqUnWordWrap($body);   
         $body_ary = explode("\n", $body);
         $i = count($body_ary) - 1;
         while (isset($body_ary[$i]) && ereg("^[>\s]*$", $body_ary[$i])) {
            unset($body_ary[$i]);
            $i --;
         }
         $body = "";
         for ($i=0; $i < count($body_ary); $i++) {
            if (! $forward_id)
            {
                if (ereg('^[\s>]+', $body_ary[$i]))
                {
                    $body_ary[$i] = '>' . $body_ary[$i];
                }
                else
                {
                    $body_ary[$i] = '> ' . $body_ary[$i];
                }
            }
            sqWordWrap($body_ary[$i], $editor_size - 1);
            $body .= $body_ary[$i] . "\n";
            $body_ary[$i] = '';
         }
         if ($forward_id)
         {
             $bodyTop =  "-------- " . _("Original Message") . " --------\n";
             $bodyTop .= _("Subject") . ": " . $orig_header->subject . "\n"; 
             $bodyTop .= _("From") . ": " . $orig_header->from . "\n"; 
             $bodyTop .= _("To") . ": " . $orig_header->to[0] . "\n"; 
             if (count($orig_header->to) > 1) {
                 for ($x=1; $x < count($orig_header->to); $x++) {
                     $bodyTop .= "         " . $orig_header->to[$x] . "\n";
                 }
             }
             $bodyTop .= "\n";
             $body = $bodyTop . $body;
         }
         
         $body = ereg_replace('\\\\', '\\\\', $body);

         return;
      }

      if (!$send_to) {
         $send_to = sqimap_find_email($send_to);
      }

      /** This formats a CC string if they hit "reply all" **/
      if ($send_to_cc != "") {
         $send_to_cc = ereg_replace( '"[^"]*"', "", $send_to_cc);
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

   function getAttachments($message) {
      global $mailbox, $attachments, $attachment_dir, $imapConnection,
             $ent_num, $forward_id;
      
      if (!$message) {
           sqimap_mailbox_select($imapConnection, $mailbox);
           $message = sqimap_get_message($imapConnection, $forward_id, $mailbox); }
      
      if (!$message->entities) {
      if ($message->header->entity_id != $ent_num) {
      $filename = decodeHeader($message->header->filename);
      
      if ($filename == "")
              $filename = "untitled-".$message->header->entity_id;
      
      $localfilename = md5($filename.", $REMOTE_IP, REMOTE_PORT, $UNIQUE_ID, extra-stuff here");
      
        // Write File Info
        $fp = fopen ($attachment_dir.$localfilename.".info", "w");
        fputs ($fp, strtolower($message->header->type0)."/".strtolower($message->header->type1)."\n".$filename."\n");
        fclose ($fp);

        // Write Attachment to file
        $fp = fopen ($attachment_dir.$localfilename, "w");
      fputs ($fp, decodeBody(mime_fetch_body($imapConnection, $forward_id, $message->header->entity_id), $message->header->encoding));
      fclose ($fp);
      
      $attachments[$localfilename] = $filename;
      
      }
      } else {
              for ($i = 0; $i < count($message->entities); $i++) {
              getAttachments($message->entities[$i]);
              }       
      }
      return;
      }       

   function showInputForm () {
      global $send_to, $send_to_cc, $reply_subj, $forward_subj, $body,
         $passed_body, $color, $use_signature, $signature, $editor_size,
         $attachments, $subject, $newmail, $use_javascript_addr_book,
         $send_to_bcc, $reply_id, $mailbox, $from_htmladdr_search,
         $location_of_buttons;

      $subject = sqStripSlashes(decodeHeader($subject));
      $reply_subj = decodeHeader($reply_subj);
      $forward_subj = decodeHeader($forward_subj);
      $body = sqStripSlashes($body);
      
      if ($use_javascript_addr_book) {
         echo "\n<SCRIPT LANGUAGE=JavaScript><!--\n";
         echo "function open_abook() { \n";
         echo "  var nwin = window.open(\"addrbook_popup.php\",\"abookpopup\",";
         echo "\"width=670,height=300,resizable=yes,scrollbars=yes\");\n";
         echo "  if((!nwin.opener) && (document.windows != null))\n";
         echo "    nwin.opener = document.windows;\n";
         echo "}\n";
         echo "// --></SCRIPT>\n\n";
      }

      echo "\n<FORM name=compose action=\"compose.php\" METHOD=POST ENCTYPE=\"multipart/form-data\"";
      do_hook("compose_form");
	  echo ">\n";
      if ($reply_id) {
         echo "<input type=hidden name=reply_id value=$reply_id>\n";
      }                 
      printf("<INPUT TYPE=hidden NAME=mailbox VALUE=\"%s\">\n", htmlspecialchars($mailbox));
      echo "<TABLE WIDTH=\"100%\" ALIGN=center CELLSPACING=0 BORDER=0>\n";

      if ($location_of_buttons == 'top') showComposeButtonRow();

      echo "   <TR>\n";
      echo "      <TD BGCOLOR=\"$color[4]\" ALIGN=RIGHT>\n";
      echo _("To:");
      echo "      </TD><TD BGCOLOR=\"$color[4]\">\n";
      printf("         <INPUT TYPE=text NAME=\"send_to\" VALUE=\"%s\" SIZE=60><BR>\n",
             htmlspecialchars($send_to));
      echo "      </TD>\n";
      echo "   </TR>\n";
      echo "   <TR>\n";
      echo "      <TD BGCOLOR=\"$color[4]\" ALIGN=RIGHT>\n";
      echo _("CC:");
      echo "      </TD><TD BGCOLOR=\"$color[4]\" ALIGN=LEFT>\n";
      printf("         <INPUT TYPE=text NAME=\"send_to_cc\" SIZE=60 VALUE=\"%s\"><BR>\n",
             htmlspecialchars($send_to_cc));
      echo "      </TD>\n";
      echo "   </TR>\n";
      echo "   <TR>\n";
      echo "      <TD BGCOLOR=\"$color[4]\" ALIGN=RIGHT>\n";
      echo _("BCC:");
      echo "      </TD><TD BGCOLOR=\"$color[4]\" ALIGN=LEFT>\n";
      printf("         <INPUT TYPE=text NAME=\"send_to_bcc\" VALUE=\"%s\" SIZE=60><BR>\n",
             htmlspecialchars($send_to_bcc));
      echo "</TD></TR>\n";

      echo "   <TR>\n";
      echo "      <TD BGCOLOR=\"$color[4]\" ALIGN=RIGHT>\n";
      echo _("Subject:");
      echo "      </TD><TD BGCOLOR=\"$color[4]\" ALIGN=LEFT>\n";
      if ($reply_subj) {
         $reply_subj = str_replace("\"", "'", $reply_subj);
         $reply_subj = sqStripSlashes($reply_subj);
         $reply_subj = trim($reply_subj);
         if (substr(strtolower($reply_subj), 0, 3) != "re:")
            $reply_subj = "Re: $reply_subj";
         printf("         <INPUT TYPE=text NAME=subject SIZE=60 VALUE=\"%s\">",
                htmlspecialchars($reply_subj));
      } else if ($forward_subj) {
         $forward_subj = str_replace("\"", "'", $forward_subj);
         $forward_subj = sqStripSlashes($forward_subj);
         $forward_subj = trim($forward_subj);
         if ((substr(strtolower($forward_subj), 0, 4) != "fwd:") &&
             (substr(strtolower($forward_subj), 0, 5) != "[fwd:") &&
             (substr(strtolower($forward_subj), 0, 6) != "[ fwd:"))
            $forward_subj = "[Fwd: $forward_subj]";
         printf("         <INPUT TYPE=text NAME=subject SIZE=60 VALUE=\"%s\">",
                htmlspecialchars($forward_subj));
      } else {
          printf("         <INPUT TYPE=text NAME=subject SIZE=60 VALUE=\"%s\">",
                htmlspecialchars($subject));
      }
      echo "</td></tr>\n\n";

      if ($location_of_buttons == 'between') showComposeButtonRow();

      echo "   <TR>\n";
      echo "      <TD BGCOLOR=\"$color[4]\" COLSPAN=2>\n";
      echo "         &nbsp;&nbsp;<TEXTAREA NAME=body ROWS=20 COLS=\"$editor_size\" WRAP=HARD>";
      echo htmlspecialchars($body);
      if ($use_signature == true && $newmail == true && !isset($from_htmladdr_search)) {
         echo "\n\n-- \n" . htmlspecialchars($signature);
      }
      echo "</TEXTAREA><BR>\n";
      echo "      </TD>\n";
      echo "   </TR>\n";

      if ($location_of_buttons == 'bottom') 
         showComposeButtonRow();
      else {
         echo "   <TR><TD>&nbsp;</TD><TD ALIGN=LEFT><INPUT TYPE=SUBMIT NAME=send VALUE=\""._("Send")."\"></TD></TR>\n";
      }
      
      // This code is for attachments
      echo "   <tr>\n";
      echo "     <TD BGCOLOR=\"$color[0]\" VALIGN=TOP ALIGN=RIGHT>\n";
      echo "      <SMALL><BR></SMALL>"._("Attach:");
      echo "      </td><td ALIGN=left BGCOLOR=\"$color[0]\">\n";
      //      echo "      <INPUT TYPE=\"hidden\" name=\"MAX_FILE_SIZE\"\n";
      //      echo "      value=\"10000\">\n";
      echo "      <INPUT NAME=\"attachfile\" SIZE=48 TYPE=\"file\">\n";
      echo "      &nbsp;&nbsp;<input type=\"submit\" name=\"attach\"";
      echo " value=\"" . _("Add") ."\">\n";
      echo "     </td>\n";
      echo "   </tr>\n";
      if (isset($attachments) && count($attachments)>0) {
         echo "<tr><td bgcolor=\"$color[0]\" align=right>\n";
         echo "&nbsp;";
         echo "</td><td align=left bgcolor=\"$color[0]\">";
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
      do_hook("compose_bottom");
   }
   
   function showComposeButtonRow() {
      global $use_javascript_addr_book;
      echo "   <TR><td>\n   </td><td>\n";
      if ($use_javascript_addr_book) {
         echo "      <SCRIPT LANGUAGE=JavaScript><!--\n document.write(\"";
         echo "         <input type=button value=\\\""._("Addresses")."\\\" onclick='javascript:open_abook();'>\");";
         echo "         // --></SCRIPT><NOSCRIPT>\n";
         echo "         <input type=submit name=\"html_addr_search\" value=\""._("Addresses")."\">";
         echo "      </NOSCRIPT>\n";
      } else {  
         echo "      <input type=submit name=\"html_addr_search\" value=\""._("Addresses")."\">";
      }   
      echo "\n    <INPUT TYPE=SUBMIT NAME=send VALUE=\"". _("Send") . "\">\n";
      
      do_hook("compose_button_row");

      echo "   </TD>\n";
      echo "   </TR>\n\n";
   }

   function showSentForm () {
      echo "<BR><BR><BR><CENTER><B>Message Sent!</B><BR><BR>";
      echo "You will be automatically forwarded.<BR>If not, <A HREF=\"right_main.php\">click here</A>";
      echo "</CENTER>";
   }

   function checkInput ($show) {
      /** I implemented the $show variable because the error messages
          were getting sent before the page header.  So, I check once
          using $show=false, and then when i'm ready to display the
          error message, show=true **/
      global $body, $send_to, $subject, $color;

      if ($send_to == "") {
         if ($show)
            plain_error_message(_("You have not filled in the \"To:\" field."), $color);
         return false;
      }
      return true;
   } // function checkInput()


   // True if FAILURE
   function saveAttachedFiles() {
      global $HTTP_POST_FILES, $attachment_dir, $attachments;
      
      is_logged_in();
      $localfilename = GenerateRandomString(32, '', 7);
      
      if (!@rename($HTTP_POST_FILES['attachfile']['tmp_name'], $attachment_dir.$localfilename)) {
         if (!@copy($HTTP_POST_FILES['attachfile']['tmp_name'], $attachment_dir.$localfilename)) {
            return true;
         }
      }
      
      if (!isset($failed) || !$failed) {
         // Write information about the file
         $fp = fopen ($attachment_dir.$localfilename.".info", "w");
         fputs ($fp, $HTTP_POST_FILES['attachfile']['type']."\n".$HTTP_POST_FILES['attachfile']['name']."\n");
         fclose ($fp);

         $attachments[$localfilename] = $HTTP_POST_FILES['attachfile']['name'];
      }
    }

   if (!isset($mailbox) || $mailbox == "" || ($mailbox == "None"))
      $mailbox = "INBOX";

   if(isset($send)) {
      if (isset($HTTP_POST_FILES['attachfile']) &&
          $HTTP_POST_FILES['attachfile']['tmp_name'] &&
          $HTTP_POST_FILES['attachfile']['tmp_name'] != 'none')
          $AttachFailure = saveAttachedFiles();
      if (checkInput(false) && ! isset($AttachFailure)) {
         $urlMailbox = urlencode ($mailbox);
	 if (! isset($reply_id))
	     $reply_id = 0;
         sendMessage($send_to, $send_to_cc, $send_to_bcc, $subject, $body, $reply_id);
         header ("Location: right_main.php?mailbox=$urlMailbox&sort=$sort&startMessage=1");
      } else {
         //$imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);
         displayPageHeader($color, $mailbox);
         
         if ($AttachFailure)
             plain_error_message(_("Could not move/copy file. File not attached"), $color);

         checkInput(true);
         
         showInputForm();
         //sqimap_logout($imapConnection);
      }
   } else if (isset($html_addr_search_done)) {
      is_logged_in();
      displayPageHeader($color, $mailbox);

      $send_to = sqStripSlashes($send_to);
      $send_to_cc = sqStripSlashes($send_to_cc);
      $send_to_bcc = sqStripSlashes($send_to_bcc);
      
      for ($i=0; $i < count($send_to_search); $i++) {
         if ($send_to)
            $send_to .= ", ";
         $send_to .= $send_to_search[$i];   
      }
      
      for ($i=0; $i < count($send_to_cc_search); $i++) {
         if ($send_to_cc)
            $send_to_cc .= ", ";
         $send_to_cc .= $send_to_cc_search[$i];   
      }
      
      showInputForm();
   } else if (isset($html_addr_search)) {
      // I am using an include so as to elminiate an extra unnecessary click.  If you
      // can think of a better way, please implement it.
      include ("./addrbook_search_html.php");
   } else if (isset($attach)) {
      if (saveAttachedFiles())
            plain_error_message(_("Could not move/copy file. File not attached"), $color);
      displayPageHeader($color, $mailbox);
      showInputForm();
   } else if (isset($do_delete)) {
      is_logged_in();
      displayPageHeader($color, $mailbox);

      while (list($lkey, $localname) = each($delete)) {
         unset ($attachments[$localname]);
         unlink ($attachment_dir.$localname);
         unlink ($attachment_dir.$localname.".info");
      }

      showInputForm();
	} else if (isset($smtpErrors)) {
      $imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);
      displayPageHeader($color, $mailbox);

      $newmail = true;
      if ($forward_id && $ent_num)  getAttachments(0);
              
      newMail();
      showInputForm();
      sqimap_logout($imapConnection);
   } else {
      $imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);
      displayPageHeader($color, $mailbox);

      $newmail = true;
		
      if (isset($forward_id) && isset($ent_num))  getAttachments(0);
              
      newMail();
      showInputForm();
      sqimap_logout($imapConnection);
   }
?>
 

