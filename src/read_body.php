<?php
  /**
   ** read_body.php
   **
   **  Copyright (c) 1999-2000 The SquirrelMail development team
   **  Licensed under the GNU GPL. For full terms see the file COPYING.
   **
   **  This file is used for reading the msgs array and displaying
   **  the resulting emails in the right frame.
   **/

   session_start();

   if (!isset($config_php))
      include("../config/config.php");
   if (!isset($strings_php))
      include("../functions/strings.php");
   if (!isset($page_header_php))
      include("../functions/page_header.php");
   if (!isset($imap_php))
      include("../functions/imap.php");
   if (!isset($mime_php))
      include("../functions/mime.php");
   if (!isset($date_php))
      include("../functions/date.php");

   include("../src/load_prefs.php");
   $imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);
   sqimap_mailbox_select($imapConnection, $mailbox);
   displayPageHeader($color, $mailbox);

   if ($view_hdr) {
      fputs ($imapConnection, "a003 FETCH $passed_id BODY[HEADER]\r\n");
      $read = sqimap_read_data ($imapConnection, "a003", true, $a, $b); 
      
      echo "<br>";
      echo "<table width=100% cellpadding=2 cellspacing=0 border=0 align=center>\n";
      echo "   <TR><TD BGCOLOR=\"$color[9]\" WIDTH=100%><center><b>" . _("Viewing full header") . "</b> - ";
      echo "<a href=\"read_body.php?mailbox=".urlencode($mailbox)."&passed_id=$passed_id&startMessage=$startMessage&show_more=$show_more\">";
      echo ""._("View message") . "</a></b></center></td></tr></table>\n";
      echo "<table width=99% cellpadding=2 cellspacing=0 border=0 align=center>\n";
      echo "<tr><td><small><pre>";
      for ($i=1; $i < count($read)-1; $i++) {
         $read[$i] = htmlspecialchars($read[$i]);
         if (substr($read[$i], 0, 1) != "\t" && 
             substr($read[$i], 0, 1) != " " && 
             substr($read[$i], 0, 1) != "&" && 
             trim($read[$i])) {
            $pre = substr($read[$i], 0, strpos($read[$i], ":"));
            $read[$i] = str_replace("$pre", "<b>$pre</b>", $read[$i]);
         }
         echo "$read[$i]";
      }
      echo "</pre></small></td></tr></table>\n";
      echo "</body></html>";
      exit;
   }

   // given an IMAP message id number, this will look it up in the cached and sorted msgs array and
   //    return the index.  used for finding the next and previous messages

   // returns the index of the next valid message from the array
   function findNextMessage() {
      global $msort, $currentArrayIndex, $msgs;
		for (reset($msort); ($key = key($msort)), (isset($key)); next($msort)) { 
   	   if ($currentArrayIndex == $msgs[$key]["ID"]) {
				next($msort); 
				$key = key($msort);
				if (isset($key)) 
					return $msgs[$key]["ID"];
			}
		}
      return -1;
   }

   // returns the index of the previous message from the array
   function findPreviousMessage() {
      global $msort, $currentArrayIndex, $msgs;
		for (reset($msort); ($key = key($msort)), (isset($key)); next($msort)) { 
   	   if ($currentArrayIndex == $msgs[$key]["ID"]) {
				prev($msort);
				$key = key($msort);
				if (isset($key))
					return $msgs[$key]["ID"];
			}
		}
      return -1;
   }

   if (isset($msgs)) {
		$currentArrayIndex = $passed_id;
		/*
      for ($i=0; $i < count($msgs); $i++) {
         if ($msgs[$i]["ID"] == $passed_id) {
            $currentArrayIndex = $i;
            break;
         }
      }
		*/
   } else {
      $currentArrayIndex = -1;
   }

	for ($i = 0; $i < count($msgs); $i++) {
		if ($msgs[$i]["ID"] == $passed_id)
			$msgs[$i]["FLAG_SEEN"] = true;
	}

   // $message contains all information about the message
   // including header and body
   $message = sqimap_get_message($imapConnection, $passed_id, $mailbox);

   /** translate the subject and mailbox into url-able text **/
   $url_subj = urlencode(trim(stripslashes($message->header->subject)));
   $urlMailbox = urlencode($mailbox);
   $url_replyto = urlencode($message->header->replyto);

   $url_replytoall   = urlencode($message->header->replyto);
   $url_replytoallcc = urlencode(getLineOfAddrs($message->header->to) . ", " . getLineOfAddrs($message->header->cc));

   $dateString = getLongDateString($message->header->date);
   $ent_num = findDisplayEntity($message);

   /** TEXT STRINGS DEFINITIONS **/
   $echo_more = _("more");
   $echo_less = _("less");

   /** FORMAT THE TO STRING **/
   $i = 0;
   $to_string = "";
   $to_ary = $message->header->to;
   while ($i < count($to_ary)) {
      $to_ary[$i] = htmlspecialchars($to_ary[$i]);

      if ($to_string)
         $to_string = "$to_string<BR>$to_ary[$i]";
      else
         $to_string = "$to_ary[$i]";

      $i++;
      if (count($to_ary) > 1) {
         if ($show_more == false) {
            if ($i == 1) {
               $to_string = "$to_string&nbsp;(<A HREF=\"read_body.php?mailbox=$urlMailbox&passed_id=$passed_id&sort=$sort&startMessage=$startMessage&show_more=1&show_more_cc=$show_more_cc\">$echo_more</A>)";
               $i = count($to_ary);
            }
         } else if ($i == 1) {
            $to_string = "$to_string&nbsp;(<A HREF=\"read_body.php?mailbox=$urlMailbox&passed_id=$passed_id&sort=$sort&startMessage=$startMessage&show_more=0&show_more_cc=$show_more_cc\">$echo_less</A>)";
         }
      }
   }

   /** FORMAT THE CC STRING **/
   $i = 0;
   $cc_string = "";
   $cc_ary = $message->header->cc;
   while ($i < count($cc_ary)) {
      $cc_ary[$i] = htmlspecialchars($cc_ary[$i]);
      if ($cc_string)
         $cc_string = "$cc_string<BR>$cc_ary[$i]";
      else
         $cc_string = "$cc_ary[$i]";

      $i++;
      if (count($cc_ary) > 1) {
         if ($show_more_cc == false) {
            if ($i == 1) {
               $cc_string = "$cc_string&nbsp;(<A HREF=\"read_body.php?mailbox=$urlMailbox&passed_id=$passed_id&sort=$sort&startMessage=$startMessage&show_more_cc=1&show_more=$show_more\">$echo_more</A>)";
               $i = count($cc_ary);
            }
         } else if ($i == 1) {
            $cc_string = "$cc_string&nbsp;(<A HREF=\"read_body.php?mailbox=$urlMailbox&passed_id=$passed_id&sort=$sort&startMessage=$startMessage&show_more_cc=0&show_more=$show_more\">$echo_less</A>)";
         }
      }
   }

   /** make sure everything will display in HTML format **/
   $from_name = decodeHeader(htmlspecialchars($message->header->from));
   $subject = decodeHeader(htmlspecialchars(stripslashes($message->header->subject)));

   echo "<BR>";
   echo "<TABLE COLS=1 CELLSPACING=0 WIDTH=98% BORDER=0 ALIGN=CENTER CELLPADDING=0>\n";
   echo "   <TR><TD BGCOLOR=\"$color[9]\" WIDTH=100%>";
   echo "      <TABLE WIDTH=100% CELLSPACING=0 BORDER=0 COLS=2 CELLPADDING=3>";
   echo "         <TR>";
   echo "            <TD ALIGN=LEFT WIDTH=33%>";
   echo "               <SMALL>";
   echo "               <A HREF=\"right_main.php?use_mailbox_cache=1&sort=$sort&startMessage=$startMessage&mailbox=$urlMailbox\">";
   echo _("Message List");
   echo "</A>&nbsp;|&nbsp;";
   echo "               <A HREF=\"delete_message.php?mailbox=$urlMailbox&message=$passed_id&sort=$sort&startMessage=1\">";
   echo _("Delete");
   echo "</A>&nbsp;&nbsp;";
   echo "               </SMALL>";
   echo "            </TD><TD WIDTH=33% ALIGN=CENTER>";
   echo "               <SMALL>\n";
   if ($currentArrayIndex == -1) {
      echo "Previous&nbsp;|&nbsp;Next";
   } else {
      $prev = findPreviousMessage();
      $next = findNextMessage();
      if ($prev != -1)
         echo "<a href=\"read_body.php?passed_id=$prev&mailbox=$mailbox&sort=$sort&startMessage=$startMessage&show_more=0\">" . _("Previous") . "</A>&nbsp;|&nbsp;";
      else
         echo _("Previous") . "&nbsp;|&nbsp;";
      if ($next != -1)
         echo "<a href=\"read_body.php?passed_id=$next&mailbox=$mailbox&sort=$sort&startMessage=$startMessage&show_more=0\">" . _("Next") . "</A>";
      else
         echo _("Next");
   }
   echo "               </SMALL>\n";
   echo "            </TD><TD WIDTH=33% ALIGN=RIGHT>";
   echo "               <SMALL>";
   echo "               <A HREF=\"compose.php?forward_id=$passed_id&forward_subj=$url_subj&mailbox=$urlMailbox&ent_num=$ent_num\">";
   echo _("Forward");
   echo "</A>&nbsp;|&nbsp;";
   echo "               <A HREF=\"compose.php?send_to=$url_replyto&reply_subj=$url_subj&reply_id=$passed_id&mailbox=$urlMailbox&ent_num=$ent_num\">";
   echo _("Reply");
   echo "</A>&nbsp;|&nbsp;";
   echo "               <A HREF=\"compose.php?send_to=$url_replytoall&send_to_cc=$url_replytoallcc&reply_subj=$url_subj&reply_id=$passed_id&mailbox=$urlMailbox&ent_num=$ent_num\">";
   echo _("Reply All");
   echo "</A>&nbsp;&nbsp;";
   echo "               </SMALL>";
   echo "            </TD>";
   echo "         </TR>";
   echo "      </TABLE>";
   echo "   </TD></TR>";
   echo "   <TR><TD CELLSPACING=0 WIDTH=100%>";
   echo "   <TABLE COLS=2 WIDTH=100% BORDER=0 CELLSPACING=0 CELLPADDING=3>\n";
   echo "      <TR>\n";
   /** subject **/
   echo "         <TD BGCOLOR=\"$color[0]\" WIDTH=15% ALIGN=RIGHT>\n";
   echo _("Subject:");
   echo "         </TD><TD BGCOLOR=\"$color[0]\" WIDTH=84%>\n";
   echo "            <B>$subject</B>\n";
   echo "         </TD>\n";
   echo "         <TD WIDTH=1% bgcolor=\"$color[0]\" nowrap align=right><small><a href=\"read_body.php?mailbox=$urlMailbox&passed_id=$passed_id&startMessage=$startMessage&show_more=$show_more&view_hdr=1\">" . _("View full header") . "</a></small>&nbsp;&nbsp;</td>";
   echo "      </TR>\n";
   /** from **/
   echo "      <TR>\n";
   echo "         <TD BGCOLOR=\"$color[0]\" WIDTH=15% ALIGN=RIGHT>\n";
   echo _("From:");
   echo "         </TD><TD BGCOLOR=\"$color[0]\" WIDTH=85% colspan=2>\n";
   echo "            <B>$from_name</B>\n";
   echo "         </TD>\n";
   echo "      </TR>\n";
   /** date **/
   echo "      <TR>\n";
   echo "         <TD BGCOLOR=\"$color[0]\" WIDTH=15% ALIGN=RIGHT>\n";
   echo _("Date:");
   echo "         </TD><TD BGCOLOR=\"$color[0]\" WIDTH=85% colspan=2>\n";
   echo "            <B>$dateString</B>\n";
   echo "         </TD>\n";
   echo "      </TR>\n";
   /** to **/
   echo "      <TR>\n";
   echo "         <TD BGCOLOR=\"$color[0]\" WIDTH=15% ALIGN=RIGHT VALIGN=TOP>\n";
   echo _("To:");
   echo "         </TD><TD BGCOLOR=\"$color[0]\" WIDTH=85% VALIGN=TOP colspan=2>\n";
   echo "            <B>$to_string</B>\n";
   echo "         </TD>\n";
   echo "      </TR>\n";
   /** cc **/
   if ($message->header->cc) {
      echo "      <TR>\n";
      echo "         <TD BGCOLOR=\"$color[0]\" WIDTH=15% ALIGN=RIGHT VALIGN=TOP>\n";
      echo "            Cc:\n";
      echo "         </TD><TD BGCOLOR=\"$color[0]\" WIDTH=85% VALIGN=TOP colspan=2>\n";
      echo "            <B>$cc_string</B>\n";
      echo "         </TD>\n";
      echo "      </TR>\n";
   }
   echo "</TABLE>";
   echo "   </TD></TR>";

   echo "   <TR><TD BGCOLOR=\"$color[4]\" WIDTH=100%>\n";
   $body = formatBody($imapConnection, $message, $color, $wrap_at);
   echo "<BR>";

   echo "$body";

   echo "   </TD></TR>\n";
   echo "   <TR><TD BGCOLOR=\"$color[9]\">&nbsp;</TD></TR>";
   echo "</TABLE>\n";

   sqimap_logout($imapConnection);
?>
