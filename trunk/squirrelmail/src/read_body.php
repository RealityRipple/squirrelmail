<?php
  /**
   ** read_body.php
   **
   **  Copyright (c) 1999-2000 The SquirrelMail development team
   **  Licensed under the GNU GPL. For full terms see the file COPYING.
   **
   **  This file is used for reading the msgs array and displaying
   **  the resulting emails in the right frame.
   **
   **  $Id$
   **/

   include("../src/validate.php");
   include('../functions/imap.php');
   include('../functions/mime.php');
   include('../functions/date.php');
   include('../functions/url_parser.php');
   
   $imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);
   sqimap_mailbox_select($imapConnection, $mailbox);
   do_hook('html_top');
   displayPageHeader($color, $mailbox);

   if (isset($view_hdr)) {
      fputs ($imapConnection, "a003 FETCH $passed_id BODY[HEADER]\r\n");
      $read = sqimap_read_data ($imapConnection, "a003", true, $a, $b); 
      
      echo '<br>';
      echo '<table width="100%" cellpadding="2" cellspacing="0" border="0" align="center">' . "\n";
      echo "   <TR><TD BGCOLOR=\"$color[9]\" WIDTH=100%><center><b>" . _("Viewing full header") . '</b> - ';
      if (isset($where) && isset($what)) {
         // Got here from a search
         echo "<a href=\"read_body.php?mailbox=".urlencode($mailbox)."&passed_id=$passed_id&where=".urlencode($where)."&what=".urlencode($what).'">';
      } else {
         echo "<a href=\"read_body.php?mailbox=".urlencode($mailbox)."&passed_id=$passed_id&startMessage=$startMessage&show_more=$show_more\">";
      }
      echo ''._("View message") . "</a></b></center></td></tr></table>\n";
      echo "<table width=99% cellpadding=2 cellspacing=0 border=0 align=center>\n";
      echo '<tr><td>';

      $cnum = 0;
      for ($i=1; $i < count($read); $i++) {
         $line = htmlspecialchars($read[$i]);
			if (eregi("^&gt;", $line)) {
				$second[$i] = $line;
				$first[$i] = '&nbsp;';
				$cnum++;
         } else if (eregi("^[ |\t]", $line)) {
            $second[$i] = $line;
            $first[$i] = '';
         } else if (eregi("^([^:]+):(.+)", $line, $regs)) {
            $first[$i] = $regs[1] . ':';
            $second[$i] = $regs[2];
				$cnum++;
         } else {
            $second[$i] = trim($line);
            $first[$i] = '';
         }
		}
		for ($i=0; $i < count($second); $i = $j) {
            if (isset($first[$i]))
			    $f = $first[$i];
            if (isset($second[$i]))
			    $s = nl2br($second[$i]);
			$j = $i + 1;
			while ($first[$j] == '' && $j < count($first)) {
				$s .= '&nbsp;&nbsp;&nbsp;&nbsp;' . nl2br($second[$j]);
				$j++;
			}
			parseEmail($s);
            if (isset($f)) echo "<nobr><tt><b>$f</b>$s</tt></nobr>";
      }
      echo "</td></tr></table>\n";
      echo '</body></html>';
      sqimap_logout($imapConnection);
      exit;
   }

   // given an IMAP message id number, this will look it up in the cached and sorted msgs array and
   //    return the index.  used for finding the next and previous messages

   // returns the index of the next valid message from the array
   function findNextMessage() {
      global $msort, $currentArrayIndex, $msgs, $sort;

      if ($sort == 6) {
         if ($currentArrayIndex != 1) {
            return $currentArrayIndex - 1;
         }
      } else {
         for (reset($msort); ($key = key($msort)), (isset($key)); next($msort)) { 
            if ($currentArrayIndex == $msgs[$key]['ID']) {
               next($msort); 
               $key = key($msort);
               if (isset($key)) 
                  return $msgs[$key]['ID'];
            }
         }
      }
      return -1;
   }

   // Removes just one address from the list of addresses
   function RemoveAddress(&$addr_list, $addr) {
       if ($addr == '')
          return;
       foreach (array_keys($addr_list, $addr) as $key_to_delete)
       {
           unset($addr_list[$key_to_delete]);
       }
   }      
   
   
   // returns the index of the previous message from the array
   function findPreviousMessage() {
      global $msort, $currentArrayIndex, $sort, $msgs, $imapConnection, 
         $mailbox, $data_dir, $username;
      if ($sort == 6) {
         $numMessages = sqimap_get_num_messages($imapConnection, $mailbox);
         if ($currentArrayIndex != $numMessages) {
            return $currentArrayIndex + 1; 
         }
      } else {
   		for (reset($msort); ($key = key($msort)), (isset($key)); next($msort)) { 
      	   if ($currentArrayIndex == $msgs[$key]['ID']) {
   				prev($msort);
   				$key = key($msort);
   				if (isset($key))
   					return $msgs[$key]['ID'];
   			}
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
		if ($msgs[$i]['ID'] == $passed_id)
			$msgs[$i]['FLAG_SEEN'] = true;
	}

   // $message contains all information about the message
   // including header and body
   $message = sqimap_get_message($imapConnection, $passed_id, $mailbox);
   
   /** translate the subject and mailbox into url-able text **/
   $url_subj = urlencode(trim($message->header->subject));
   $urlMailbox = urlencode($mailbox);
   $url_replyto = '';
   if (isset($message->header->replyto))
      $url_replyto = urlencode($message->header->replyto);

   $url_replytoall   = $url_replyto;

   // If we are replying to all, then find all other addresses and
   // add them to the list.  Remove duplicates.
   // This is somewhat messy, so I'll explain:
   // 1) Take all addresses (from, to, cc) (avoid nasty join errors here)
   $url_replytoall_extra_addrs = array_merge(array($message->header->from),
      $message->header->to, $message->header->cc);

   // 2) Make one big string out of them
   $url_replytoall_extra_addrs = join(';', $url_replytoall_extra_addrs);
   
   // 3) Parse that into an array of addresses
   $url_replytoall_extra_addrs = parseAddrs($url_replytoall_extra_addrs);
   
   // 4) Make them unique -- weed out duplicates
   // (Coded for PHP 4.0.0)
   $url_replytoall_extra_addrs =
      array_keys(array_flip($url_replytoall_extra_addrs));
   
   // 5) Remove the addresses we'll be sending the message 'to'
   $url_replytoall_avoid_addrs = '';
   if (isset($message->header->replyto))
      $url_replytoall_avoid_addrs = $message->header->replyto;
   $url_replytoall_avoid_addrs = parseAddrs($url_replytoall_avoid_addrs);
   foreach ($url_replytoall_avoid_addrs as $addr)
   {
       RemoveAddress($url_replytoall_extra_addrs, $addr);
   }
   
   // 6) Remove our identities from the CC list (they still can be in the
   // TO list) only if $include_self_reply_all isn't set or it is ''.
   if (getPref($data_dir, $username, 'include_self_reply_all') == '')
   {
       RemoveAddress($url_replytoall_extra_addrs, 
                     getPref($data_dir, $username, 'email_address'));
       $idents = getPref($data_dir, $username, 'identities');
       if ($idents != '' && $idents > 1)
       {
           for ($i = 1; $i < $idents; $i ++)
           {
               RemoveAddress($url_replytoall_extra_addrs, 
	                     getPref($data_dir, $username, 'email_address' .
			             $i));
           }
       }
   } 
   
   // 7) Smoosh back into one nice line
   $url_replytoallcc = getLineOfAddrs($url_replytoall_extra_addrs);
   
   // 8) urlencode() it
   $url_replytoallcc = urlencode($url_replytoallcc);

   $dateString = getLongDateString($message->header->date);
   
   // What do we reply to -- text only, if possible
   $ent_num = findDisplayEntity($message);

   /** TEXT STRINGS DEFINITIONS **/
   $echo_more = _("more");
   $echo_less = _("less");

   if (!isset($show_more_cc)) $show_more_cc = false;

   /** FORMAT THE TO STRING **/
   $i = 0;
   $to_string = '';
   $to_ary = $message->header->to;
   while ($i < count($to_ary)) {
      $to_ary[$i] = htmlspecialchars(decodeHeader($to_ary[$i]));

      if ($to_string)
         $to_string = "$to_string<BR>$to_ary[$i]";
      else
         $to_string = "$to_ary[$i]";

      $i++;
      if (count($to_ary) > 1) {
         if ($show_more == false) {
            if ($i == 1) {
               if (isset($where) && isset($what)) {
                  // from a search
                  $to_string = "$to_string&nbsp;(<A HREF=\"read_body.php?mailbox=$urlMailbox&passed_id=$passed_id&where=".urlencode($where)."&what=".urlencode($what)."&show_more=1&show_more_cc=$show_more_cc\">$echo_more</A>)";
               } else {
                  $to_string = "$to_string&nbsp;(<A HREF=\"read_body.php?mailbox=$urlMailbox&passed_id=$passed_id&sort=$sort&startMessage=$startMessage&show_more=1&show_more_cc=$show_more_cc\">$echo_more</A>)";
               }   
               $i = count($to_ary);
            }
         } else if ($i == 1) {
            if (isset($where) && isset($what)) {
               // from a search
               $to_string = "$to_string&nbsp;(<A HREF=\"read_body.php?mailbox=$urlMailbox&passed_id=$passed_id&where=".urlencode($where)."&what=".urlencode($what)."&show_more=0&show_more_cc=$show_more_cc\">$echo_less</A>)";
            } else {
               $to_string = "$to_string&nbsp;(<A HREF=\"read_body.php?mailbox=$urlMailbox&passed_id=$passed_id&sort=$sort&startMessage=$startMessage&show_more=0&show_more_cc=$show_more_cc\">$echo_less</A>)";
            }
         }
      }
   }

   /** FORMAT THE CC STRING **/
   $i = 0;
   if (isset ($message->header->cc[0]) && trim($message->header->cc[0])){
      $cc_string = "";
      $cc_ary = $message->header->cc;
      while ($i < count(decodeHeader($cc_ary))) {
         $cc_ary[$i] = htmlspecialchars($cc_ary[$i]);
         if ($cc_string)
            $cc_string = "$cc_string<BR>$cc_ary[$i]";
         else
            $cc_string = "$cc_ary[$i]";
   
         $i++;
         if (count($cc_ary) > 1) {
            if ($show_more_cc == false) {
               if ($i == 1) {
                  if (isset($where) && isset($what)) {
                     // from a search
                     $cc_string = "$cc_string&nbsp;(<A HREF=\"read_body.php?mailbox=$urlMailbox&passed_id=$passed_id&what=".urlencode($what)."&where=".urlencode($where)."&show_more_cc=1&show_more=$show_more\">$echo_more</A>)";
                  } else {
                     $cc_string = "$cc_string&nbsp;(<A HREF=\"read_body.php?mailbox=$urlMailbox&passed_id=$passed_id&sort=$sort&startMessage=$startMessage&show_more_cc=1&show_more=$show_more\">$echo_more</A>)";
                  }   
                  $i = count($cc_ary);
               }
            } else if ($i == 1) {
               if (isset($where) && isset($what)) {
                  // from a search
                  $cc_string = "$cc_string&nbsp;(<A HREF=\"read_body.php?mailbox=$urlMailbox&passed_id=$passed_id&what=".urlencode($what)."&where=".urlencode($where)."&show_more_cc=0&show_more=$show_more\">$echo_less</A>)";
               } else {
                  $cc_string = "$cc_string&nbsp;(<A HREF=\"read_body.php?mailbox=$urlMailbox&passed_id=$passed_id&sort=$sort&startMessage=$startMessage&show_more_cc=0&show_more=$show_more\">$echo_less</A>)";
               }   
            }
         }
      }
   }
   /** make sure everything will display in HTML format **/
   $from_name = decodeHeader(htmlspecialchars($message->header->from));
   $subject = decodeHeader(htmlspecialchars($message->header->subject));

   do_hook('read_body_top');
   echo '<BR>';

   echo '<TABLE CELLSPACING="0" WIDTH="100%" BORDER="0" ALIGN="CENTER" CELLPADDING="0">' . "\n";
   echo '   <TR><TD BGCOLOR="' . $color[9] . '" WIDTH="100%">';
   echo '      <TABLE WIDTH="100%" CELLSPACING="0" BORDER="0" CELLPADDING="3">';
   echo '         <TR>';
   echo '            <TD ALIGN="LEFT" WIDTH="33%">';
   echo '               <SMALL>';
   if ($where && $what) {
      echo "               <A HREF=\"search.php?where=".urlencode($where)."&what=".urlencode($what)."&mailbox=$urlMailbox\">";
   } else {
      echo "               <A HREF=\"right_main.php?use_mailbox_cache=1&sort=$sort&startMessage=$startMessage&mailbox=$urlMailbox\">";
   }
   echo _("Message List");
   echo '</A>&nbsp;|&nbsp;';
   if ($where && $what) {
      echo "               <A HREF=\"delete_message.php?mailbox=$urlMailbox&message=$passed_id&where=".urlencode($where)."&what=".urlencode($what).'">';
   } else {
      echo "               <A HREF=\"delete_message.php?mailbox=$urlMailbox&message=$passed_id&sort=$sort&startMessage=$startMessage\">";
   }
   echo _("Delete");
   echo '</A>&nbsp;&nbsp;';
   echo '               </SMALL>';
   echo '            </TD><TD WIDTH="33%" ALIGN="CENTER">';
   echo '               <SMALL>' . "\n";
   if ($where && $what) {
   } else {
      if ($currentArrayIndex == -1) {
         echo 'Previous&nbsp;|&nbsp;Next';
      } else {
         $prev = findPreviousMessage();
         $next = findNextMessage();
         if ($prev != -1)
            echo "<a href=\"read_body.php?passed_id=$prev&mailbox=$urlMailbox&sort=$sort&startMessage=$startMessage&show_more=0\">" . _("Previous") . "</A>&nbsp;|&nbsp;";
         else
            echo _("Previous") . '&nbsp;|&nbsp;';
         if ($next != -1)
            echo "<a href=\"read_body.php?passed_id=$next&mailbox=$urlMailbox&sort=$sort&startMessage=$startMessage&show_more=0\">" . _("Next") . "</A>";
         else
            echo _("Next");
      }
   }   
   echo '               </SMALL>' . "\n";
   echo '            </TD><TD WIDTH="33%" ALIGN="RIGHT">';
   echo '               <SMALL>';
   echo "               <A HREF=\"compose.php?forward_id=$passed_id&forward_subj=$url_subj&mailbox=$urlMailbox&ent_num=$ent_num\">";
   echo _("Forward");
   echo '</A>&nbsp;|&nbsp;';
   echo "               <A HREF=\"compose.php?send_to=$url_replyto&reply_subj=$url_subj&reply_id=$passed_id&mailbox=$urlMailbox&ent_num=$ent_num\">";
   echo _("Reply");
   echo '</A>&nbsp;|&nbsp;';
   echo "               <A HREF=\"compose.php?send_to=$url_replytoall&send_to_cc=$url_replytoallcc&reply_subj=$url_subj&reply_id=$passed_id&mailbox=$urlMailbox&ent_num=$ent_num\">";
   echo _("Reply All");
   echo '</A>&nbsp;&nbsp;';
   echo '               </SMALL>';
   echo '            </TD>';
   echo '         </TR>';
   echo '      </TABLE>';
   echo '   </TD></TR>';
   echo '   <TR><TD CELLSPACING="0" WIDTH="100%">';
   echo '   <TABLE WIDTH="100%" BORDER="0" CELLSPACING="0" CELLPADDING="3">' . "\n";
   echo '      <TR>' . "\n";
   /** subject **/
   echo "         <TD BGCOLOR=\"$color[0]\" WIDTH=\"10%\" ALIGN=\"right\" VALIGN=\"top\">\n";
   echo _("Subject:");
   echo "         </TD><TD BGCOLOR=\"$color[0]\" WIDTH=\"80%\" VALIGN=\"top\">\n";
   echo "            <B>$subject</B>&nbsp;\n";
   echo "         </TD>\n";
   echo '         <TD ROWSPAN="4" width=10% BGCOLOR="'.$color[0].'" ALIGN=right VALIGN=top NOWRAP><small>' . "\n";
   if ($where && $what) {
      // Got here from a search
      echo "<a href=\"read_body.php?mailbox=$urlMailbox&passed_id=$passed_id&where=".urlencode($where)."&what=".urlencode($what)."&view_hdr=1\">" . _("View full header") . "</a>\n";
   } else {
      echo "<a href=\"read_body.php?mailbox=$urlMailbox&passed_id=$passed_id&startMessage=$startMessage&show_more=$show_more&view_hdr=1\">" . _("View full header") . "</a>\n";
   }
      do_hook("read_body_header_right");
   echo '</small></TD>' . "\n";
   echo ' </TR>' ."\n";
   /** from **/
   echo '      <TR>' . "\n";
   echo '         <TD BGCOLOR="' . $color[0] . '" ALIGN="RIGHT">' . "\n";
   echo _("From:");
   echo '         </TD><TD BGCOLOR="' . $color[0] . '">' . "\n";
   echo "            <B>$from_name</B>&nbsp;\n";
   echo '         </TD>' . "\n";
   echo '      </TR>' . "\n";
   /** date **/
   echo '      <TR>' . "\n";
   echo '         <TD BGCOLOR="' . $color[0] . '" ALIGN="RIGHT">' . "\n";
   echo _("Date:");
   echo "         </TD><TD BGCOLOR=\"$color[0]\">\n";
   echo "            <B>$dateString</B>&nbsp;\n";
   echo '         </TD>' . "\n";
   echo '      </TR>' . "\n";
   /** to **/
   echo "      <TR>\n";
   echo "         <TD BGCOLOR=\"$color[0]\" ALIGN=RIGHT VALIGN=TOP>\n";
   echo _("To:");
   echo '         </TD><TD BGCOLOR="' . $color[0] . '" VALIGN="TOP">' . "\n";
   echo "            <B>$to_string</B>&nbsp;\n";
   echo '         </TD>' . "\n";
   echo '      </TR>' . "\n";
   /** cc **/
   if (isset($cc_string)) {
      echo "      <TR>\n";
      echo "         <TD BGCOLOR=\"$color[0]\" ALIGN=RIGHT VALIGN=TOP>\n";
      echo '            Cc:' . "\n";
      echo "         </TD><TD BGCOLOR=\"$color[0]\" VALIGN=TOP colspan=2>\n";
      echo "            <B>$cc_string</B>&nbsp;\n";
      echo '         </TD>' . "\n";
      echo '      </TR>' . "\n";
   }
   do_hook("read_body_header");
   echo '</TABLE>';
   echo '   </TD></TR>';
   echo '</table>';
   echo "<TABLE CELLSPACING=0 WIDTH=97% BORDER=0 ALIGN=CENTER CELLPADDING=0>\n";

   echo "   <TR><TD BGCOLOR=\"$color[4]\" WIDTH=100%>\n";
   echo '<BR>';
   
   $body = formatBody($imapConnection, $message, $color, $wrap_at);

   $body = str_replace("&amp;", "&", $body);

   echo $body;
   
   echo '</TABLE>';
   echo '<TABLE CELLSPACING="0" WIDTH="100%" BORDER="0" ALIGN="CENTER" CELLPADDING="0">' . "\n";
   echo "   <TR><TD BGCOLOR=\"$color[9]\">&nbsp;</TD></TR>";
   echo '</TABLE>' . "\n";

   do_hook('read_body_bottom');
   do_hook('html_bottom');
   sqimap_logout($imapConnection);
?>
