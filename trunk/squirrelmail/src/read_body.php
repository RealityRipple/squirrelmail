<?php

/**
 * read_body.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This file is used for reading the msgs array and displaying
 * the resulting emails in the right frame.
 *
 * $Id$
 /

/*****************************************************************/
/*** THIS FILE NEEDS TO HAVE ITS FORMATTING FIXED!!!           ***/
/*** PLEASE DO SO AND REMOVE THIS COMMENT SECTION.             ***/
/***    + Base level indent should begin at left margin, as    ***/
/***      the require_once below looks.                        ***/
/***    + All identation should consist of four space blocks   ***/
/***    + Tab characters are evil.                             ***/
/***    + all comments should use "slash-star ... star-slash"  ***/
/***      style -- no pound characters, no slash-slash style   ***/
/***    + FLOW CONTROL STATEMENTS (if, while, etc) SHOULD      ***/
/***      ALWAYS USE { AND } CHARACTERS!!!                     ***/
/***    + Please use ' instead of ", when possible. Note "     ***/
/***      should always be used in _( ) function calls.        ***/
/*** Thank you for your help making the SM code more readable. ***/
/*****************************************************************/

require_once('../src/validate.php');
require_once('../functions/imap.php');
require_once('../functions/mime.php');
require_once('../functions/date.php');
require_once('../functions/url_parser.php');
   
    /**
     * Given an IMAP message id number, this will look it up in the cached
     * and sorted msgs array and return the index. Used for finding the next
     * and previous messages.
     *
     * returns the index of the next valid message from the array
     */
    function findNextMessage() {
        global $msort, $currentArrayIndex, $msgs, $sort;
        $result = -1;

        if ($sort == 6) {
            if ($currentArrayIndex != 1) {
                $result = $currentArrayIndex - 1;
            }
        } else {
            for (reset($msort); ($key = key($msort)), (isset($key)); next($msort)) { 
                if ($currentArrayIndex == $msgs[$key]['ID']) {
                    next($msort); 
                    $key = key($msort);
                    if (isset($key)) 
                        $result = $msgs[$key]['ID'];
                        break;
                }
            }
        }
        return ($result);
    }

    /** Removes just one address from the list of addresses. */
    function RemoveAddress(&$addr_list, $addr) {
        if ($addr != '') {
            foreach (array_keys($addr_list, $addr) as $key_to_delete) {
                unset($addr_list[$key_to_delete]);
            }
        }
    }      
   
    /** returns the index of the previous message from the array. */
    function findPreviousMessage() {
        global $msort, $currentArrayIndex, $sort, $msgs, $imapConnection;
        global $mailbox, $data_dir, $username;
        $result = -1;

        if ($sort == 6) {
            $numMessages = sqimap_get_num_messages($imapConnection, $mailbox);
            if ($currentArrayIndex != $numMessages) {
                $result = $currentArrayIndex + 1; 
            }
        } else {
            for (reset($msort); ($key = key($msort)), (isset($key)); next($msort)) { 
                if ($currentArrayIndex == $msgs[$key]['ID']) {
                    prev($msort);
                    $key = key($msort);
                    if (isset($key)) {
                        $result = $msgs[$key]['ID'];
                        break;
                    }
                }
            }
        }   
        return ($result);
    }

    /**
     * Displays a link to a page where the message is displayed more
     * "printer friendly".
     */
    function printer_friendly_link() {
        global $passed_id, $mailbox, $ent_num, $color;
        global $pf_subtle_link;
        global $javascript_on;

        if (strlen(trim($mailbox)) < 1) {
            $mailbox = 'INBOX';
        }

        $params = '?passed_ent_id=' . $ent_num;
        $params .= '&mailbox=' . urlencode($mailbox);
        $params .= '&passed_id=' . $passed_id;

        $print_text = _("View Printable Version");

        if (!$pf_subtle_link) {
            /* The link is large, on the bottom of the header panel. */
            $result = '      <tr bgcolor="' . $color[0] . '">' . "\n" .
                      '        <td class="medText" align="right" valign="top">' . "\n" .
                      '          &nbsp;' . "\n" .
                      '        </td><td class="medText" valign="top" colspan="2">'."\n";
        } else {
            /* The link is subtle, below "view full header". */
            $result = "<BR>\n";
        }

        /* Output the link. */
        if ($javascript_on) {
            $result .= '<script language="javascript">' . "\n" .
                       '<!--' . "\n" .
                       "  function printFormat() {\n" .
                       '    window.open("../src/printer_friendly_main.php' .
                              $params . '","Print","width=800,height=600");' . "\n".
                       "  }\n" .
                       "// -->\n" .
                       "</script>\n" .
                       "<A HREF=\"javascript:printFormat();\">$print_text</A>\n";
        } else {
            $result .= '<A TARGET="_blank" HREF="../src/printer_friendly_bottom.php' .
                       "$params\">$print_text</A>\n";
        }

        if (!$pf_subtle_link) {
             /* The link is large, on the bottom of the header panel. */
             $result .= '        </td>' . "\n" .
                        '      </tr>' . "\n";
        }

        return ($result);
    }

    /*****************************/
    /*** Main of read_boby.php ***/
    /*****************************/

    $imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);
    sqimap_mailbox_select($imapConnection, $mailbox);
    do_hook('html_top');
    displayPageHeader($color, $mailbox);

    if (isset($view_hdr)) {
        fputs ($imapConnection, sqimap_session_id() . " FETCH $passed_id BODY[HEADER]\r\n");
        $read = sqimap_read_data ($imapConnection, sqimap_session_id(), true, $a, $b);

        echo '<BR>' .
             '<TABLE WIDTH="100%" CELLPADDING="2" CELLSPACING="0" BORDER="0" ALIGN="CENTER">' . "\n" .
             "   <TR><TD BGCOLOR=\"$color[9]\" WIDTH=\"100%\"><CENTER><B>" . _("Viewing Full Header") . '</B> - ';
        if (isset($where) && isset($what)) {
            // Got here from a search
            echo "<a href=\"read_body.php?mailbox=".urlencode($mailbox)."&passed_id=$passed_id&where=".urlencode($where)."&what=".urlencode($what).'">';
        } else {
            echo "<a href=\"read_body.php?mailbox=".urlencode($mailbox)."&passed_id=$passed_id&startMessage=$startMessage&show_more=$show_more\">";
        }
        echo ''._("View message") . "</a></b></center></td></tr></table>\n" .
             "<table width=\"99%\" cellpadding=2 cellspacing=0 border=0 align=center>\n" .
             '<tr><td>';

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
            if (isset($first[$i])) {
                $f = $first[$i];
            }
            if (isset($second[$i])) {
                $s = nl2br($second[$i]);
            }
            $j = $i + 1;
            while (($first[$j] == '') && ($j < count($first))) {
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

    if (isset($msgs)) {
        $currentArrayIndex = $passed_id;
    } else {
        $currentArrayIndex = -1;
    }

    for ($i = 0; $i < count($msgs); $i++) {
        if ($msgs[$i]['ID'] == $passed_id) {
            $msgs[$i]['FLAG_SEEN'] = true;
        }
    }

    // $message contains all information about the message
    // including header and body
    $message = sqimap_get_message($imapConnection, $passed_id, $mailbox);

    /** translate the subject and mailbox into url-able text **/
    $url_subj = urlencode(trim($message->header->subject));
    $urlMailbox = urlencode($mailbox);
    $url_replyto = '';
    if (isset($message->header->replyto)) {
        $url_replyto = urlencode($message->header->replyto);
    }

    $url_replytoall   = $url_replyto;

    // If we are replying to all, then find all other addresses and
    // add them to the list.  Remove duplicates.
    // This is somewhat messy, so I'll explain:
    // 1) Take all addresses (from, to, cc) (avoid nasty join errors here)
    $url_replytoall_extra_addrs = array_merge(
        array($message->header->from),
        $message->header->to,
        $message->header->cc
    );

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
    if (isset($message->header->replyto)) {
        $url_replytoall_avoid_addrs = $message->header->replyto;
    }

    $url_replytoall_avoid_addrs = parseAddrs($url_replytoall_avoid_addrs);
    foreach ($url_replytoall_avoid_addrs as $addr) {
        RemoveAddress($url_replytoall_extra_addrs, $addr);
    }
   
    // 6) Remove our identities from the CC list (they still can be in the
    // TO list) only if $include_self_reply_all is turned off
    if (!$include_self_reply_all) {
        RemoveAddress($url_replytoall_extra_addrs, 
                      getPref($data_dir, $username, 'email_address'));
        $idents = getPref($data_dir, $username, 'identities');
        if ($idents != '' && $idents > 1) {
            for ($i = 1; $i < $idents; $i ++) {
                $cur_email_address = getPref($data_dir, $username, 'email_address' . $i);
                RemoveAddress($url_replytoall_extra_addrs, $cur_email_address);
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

        if ($to_string) {
            $to_string = "$to_string<BR>$to_ary[$i]";
        } else {
            $to_string = "$to_ary[$i]";
        }

        $i++;
        if (count($to_ary) > 1) {
            if ($show_more == false) {
                if ($i == 1) {
                    /* From a search... */
                    if (isset($where) && isset($what)) {
                        $to_string = "$to_string&nbsp;(<A HREF=\"read_body.php?mailbox=$urlMailbox&passed_id=$passed_id&where=".urlencode($where)."&what=".urlencode($what)."&show_more=1&show_more_cc=$show_more_cc\">$echo_more</A>)";
                    } else {
                        $to_string = "$to_string&nbsp;(<A HREF=\"read_body.php?mailbox=$urlMailbox&passed_id=$passed_id&sort=$sort&startMessage=$startMessage&show_more=1&show_more_cc=$show_more_cc\">$echo_more</A>)";
                    }
                    $i = count($to_ary);
                }
            } else if ($i == 1) {
                /* From a search... */
                if (isset($where) && isset($what)) {
                    $to_string = "$to_string&nbsp;(<A HREF=\"read_body.php?mailbox=$urlMailbox&passed_id=$passed_id&where=".urlencode($where)."&what=".urlencode($what)."&show_more=0&show_more_cc=$show_more_cc\">$echo_less</A>)";
                } else {
                    $to_string = "$to_string&nbsp;(<A HREF=\"read_body.php?mailbox=$urlMailbox&passed_id=$passed_id&sort=$sort&startMessage=$startMessage&show_more=0&show_more_cc=$show_more_cc\">$echo_less</A>)";
                }
            }
        }
    }

    /** FORMAT THE CC STRING **/
    $i = 0;
    if (isset ($message->header->cc[0]) && trim($message->header->cc[0])) {
        $cc_string = "";
        $cc_ary = $message->header->cc;
        while ($i < count(decodeHeader($cc_ary))) {
            $cc_ary[$i] = htmlspecialchars($cc_ary[$i]);
            if ($cc_string) {
                $cc_string = "$cc_string<BR>$cc_ary[$i]";
            } else {
                $cc_string = "$cc_ary[$i]";
            }
   
            $i++;
            if (count($cc_ary) > 1) {
                if ($show_more_cc == false) {
                    if ($i == 1) {
                        /* From a search... */
                        $cc_string = "$cc_string&nbsp;(<A HREF=\"read_body.php?mailbox=$urlMailbox&passed_id=$passed_id";
                        if (isset($where) && isset($what)) {
                            $cc_string .= "&what=".urlencode($what)."&where=".urlencode($where)."&show_more_cc=1&show_more=$show_more\">$echo_more</A>)";
                        } else {
                            $cc_string = "&sort=$sort&startMessage=$startMessage&show_more_cc=1&show_more=$show_more\">$echo_more</A>)";
                        }   
                        $i = count($cc_ary);
                    }
                } else if ($i == 1) {
                    /* From a search... */
                    if (isset($where) && isset($what)) {
                        $cc_string .= "&nbsp;(<A HREF=\"read_body.php?mailbox=$urlMailbox&passed_id=$passed_id&what=".urlencode($what)."&where=".urlencode($where)."&show_more_cc=0&show_more=$show_more\">$echo_less</A>)";
                    } else {
                        $cc_string .= "&nbsp;(<A HREF=\"read_body.php?mailbox=$urlMailbox&passed_id=$passed_id&sort=$sort&startMessage=$startMessage&show_more_cc=0&show_more=$show_more\">$echo_less</A>)";
                    }   
                }
            }
        }
    }

    /** FORMAT THE BCC STRING **/
    $i = 0;
    if (isset ($message->header->bcc[0]) && trim($message->header->bcc[0])){
        $bcc_string = "";
        $bcc_ary = $message->header->bcc;
        while ($i < count(decodeHeader($bcc_ary))) {
            $bcc_ary[$i] = htmlspecialchars($bcc_ary[$i]);
            if ($bcc_string) {
                $bcc_string = "$bcc_string<BR>$bcc_ary[$i]";
            } else {
                $bcc_string = "$bcc_ary[$i]";
            }

            $i++;
            if (count($bcc_ary) > 1) {
                if ($show_more_cc == false) {
                    if ($i == 1) {
                        /* From a search... */
                        if (isset($where) && isset($what)) {
                            $bcc_string = "$bcc_string&nbsp;(<A HREF=\"read_body.php?mailbox=$urlMailbox&passed_id=$passed_id&what=".urlencode($what)."&where=".urlencode($where)."&show_more_cc=1&show_more=$show_more\">$echo_more</A>)";
                        } else {
                            $bcc_string = "$bcc_string&nbsp;(<A HREF=\"read_body.php?mailbox=$urlMailbox&passed_id=$passed_id&sort=$sort&startMessage=$startMessage&show_more_cc=1&show_more=$show_more\">$echo_more</A>)";
                        }   
                        $i = count($bcc_ary);
                    }
                } else if ($i == 1) {
                    /* From a search... */
                    if (isset($where) && isset($what)) {
                       $bcc_string = "$bcc_string&nbsp;(<A HREF=\"read_body.php?mailbox=$urlMailbox&passed_id=$passed_id&what=".urlencode($what)."&where=".urlencode($where)."&show_more_cc=0&show_more=$show_more\">$echo_less</A>)";
                    } else {
                       $bcc_string = "$bcc_string&nbsp;(<A HREF=\"read_body.php?mailbox=$urlMailbox&passed_id=$passed_id&sort=$sort&startMessage=$startMessage&show_more_cc=0&show_more=$show_more\">$echo_less</A>)";
                    }   
                }
            }
        }
    }

    if ($default_use_priority) {
        switch(substr($message->header->priority,0,1)) {
            /* First, check for a higher then normal priority. */
            case "1":
            case "2": $priority_string = _("High"); break;

            /* Second, check for a normal priority. */
            case "3": $priority_string = _("Normal"); break;

            /* Last, check for a lower then normal priority. */
            case "4":
            case "5": $priority_string = _("Low"); break;
        }
    }

    /** make sure everything will display in HTML format **/
    $from_name = decodeHeader(htmlspecialchars($message->header->from));
    $subject = decodeHeader(htmlspecialchars($message->header->subject));

    do_hook('read_body_top');
    echo '<BR>' .
         '<TABLE CELLSPACING="0" WIDTH="100%" BORDER="0" ALIGN="CENTER" CELLPADDING="0">' . "\n" .
         '   <TR><TD BGCOLOR="' . $color[9] . '" WIDTH="100%">' . "\n" .
         '      <TABLE WIDTH="100%" CELLSPACING="0" BORDER="0" CELLPADDING="3">' . "\n" .
         '         <TR>' . "\n" .
         '            <TD ALIGN="LEFT" WIDTH="33%">' . "\n" .
         '               <SMALL>' . "\n";
         
    if ($where && $what) {
        if( $pos == '' ) {
            $pos = 0;
        }
        echo "               <A HREF=\"search.php?where$pos=".urlencode($where)."&pos=$pos&what$pos=".urlencode($what)."&mailbox=$urlMailbox\">";
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
    echo _("Delete") . '</A>&nbsp;';
    if (($mailbox == $draft_folder) && ($save_as_draft)) {
        echo '|&nbsp;';
        echo "               <A HREF=\"compose.php?mailbox=$mailbox&send_to=$to_string&send_to_cc=$cc_string&send_to_bcc=$bcc_string&subject=$url_subj&draft_id=$passed_id&ent_num=$ent_num\">";
        echo _("Resume Draft") . '</a>';
    }

    echo '&nbsp;&nbsp;' .
         '               </SMALL>' . "\n" .
         '            </TD>' . "\n" .
         '            <TD WIDTH="33%" ALIGN="CENTER">' . "\n" .
         '               <SMALL>' . "\n";

    if ($where && $what) {
    } else {
        if ($currentArrayIndex == -1) {
            echo 'Previous&nbsp;|&nbsp;Next';
        } else {
            $prev = findPreviousMessage();
            $next = findNextMessage();

            if ($prev != -1) {
                echo "<a href=\"read_body.php?passed_id=$prev&mailbox=$urlMailbox&sort=$sort&startMessage=$startMessage&show_more=0\">" . _("Previous") . "</A>&nbsp;|&nbsp;";
            } else {
                echo _("Previous") . '&nbsp;|&nbsp;';
            }

            if ($next != -1) {
                echo "<a href=\"read_body.php?passed_id=$next&mailbox=$urlMailbox&sort=$sort&startMessage=$startMessage&show_more=0\">" . _("Next") . "</A>";
            } else {
                echo _("Next");
            }
        }
    }

    echo '               </SMALL>' . "\n" .
         '            </TD><TD WIDTH="33%" ALIGN="RIGHT">' .
         '               <SMALL>' .
         "               <A HREF=\"compose.php?forward_id=$passed_id&forward_subj=$url_subj&mailbox=$urlMailbox&ent_num=$ent_num\">" .
         _("Forward") .
         '</A>&nbsp;|&nbsp;' .
         "               <A HREF=\"compose.php?send_to=$url_replyto&reply_subj=$url_subj&reply_id=$passed_id&mailbox=$urlMailbox&ent_num=$ent_num\">" .
         _("Reply") .
         '</A>&nbsp;|&nbsp;' .
         "               <A HREF=\"compose.php?send_to=$url_replytoall&send_to_cc=$url_replytoallcc&reply_subj=$url_subj&reply_id=$passed_id&mailbox=$urlMailbox&ent_num=$ent_num\">" .
         _("Reply All") .
         '</A>&nbsp;&nbsp;' .
         '               </SMALL>' .
         '            </TD>' .
         '         </TR>' .
         '      </TABLE>' .
         '   </TD></TR>' .
         '   <TR><TD CELLSPACING="0" WIDTH="100%">' .
         '   <TABLE WIDTH="100%" BORDER="0" CELLSPACING="0" CELLPADDING="3">' . "\n" .
         '      <TR>' . "\n";

    /** subject **/
    echo "         <TD BGCOLOR=\"$color[0]\" WIDTH=\"10%\" ALIGN=\"right\" VALIGN=\"top\">\n" .
         _("Subject:") .
         "         </TD><TD BGCOLOR=\"$color[0]\" WIDTH=\"80%\" VALIGN=\"top\">\n" .
         "            <B>$subject</B>&nbsp;\n" .
         "         </TD>\n" .
         '         <TD ROWSPAN="4" width="10%" BGCOLOR="'.$color[0].'" ALIGN=right VALIGN=top NOWRAP><small>' . "\n";

    /* From a search... */
    if ($where && $what) {
        echo "<A HREF=\"read_body.php?mailbox=$urlMailbox&passed_id=$passed_id&where=".urlencode($where)."&what=".urlencode($what)."&view_hdr=1\">" . _("View Full Header") . "</A>\n";
    } else {
        echo "<A HREF=\"read_body.php?mailbox=$urlMailbox&passed_id=$passed_id&startMessage=$startMessage&show_more=$show_more&view_hdr=1\">" . _("View Full Header") . "</A>\n";
    }

    /* Output the printer friendly link if we are in subtle mode. */
    if ($pf_subtle_link) {
        echo printer_friendly_link(true);
    }

    do_hook("read_body_header_right");
    echo '</small></TD>' . "\n" .
         ' </TR>' ."\n";

    /** from **/
    echo '      <TR>' . "\n" .
         '         <TD BGCOLOR="' . $color[0] . '" ALIGN="RIGHT">' . "\n" .
         _("From:") .
         '         </TD><TD BGCOLOR="' . $color[0] . '">' . "\n" .
         "            <B>$from_name</B>&nbsp;\n" .
         '         </TD>' . "\n" .
         '      </TR>' . "\n";
    /** date **/
    echo '      <TR>' . "\n" .
         '         <TD BGCOLOR="' . $color[0] . '" ALIGN="RIGHT">' . "\n" .
         _("Date:") .
         "         </TD><TD BGCOLOR=\"$color[0]\">\n" .
         "            <B>$dateString</B>&nbsp;\n" .
         '         </TD>' . "\n" .
         '      </TR>' . "\n";

    /** to **/
    echo "      <TR>\n" .
         "         <TD BGCOLOR=\"$color[0]\" ALIGN=RIGHT VALIGN=TOP>\n" .
         _("To:") .
         '         </TD><TD BGCOLOR="' . $color[0] . '" VALIGN="TOP">' . "\n" .
         "            <B>$to_string</B>&nbsp;\n" .
         '         </TD>' . "\n" .
         '      </TR>' . "\n";
    /** cc **/
    if (isset($cc_string)) {
        echo "      <TR>\n" .
             "         <TD BGCOLOR=\"$color[0]\" ALIGN=RIGHT VALIGN=TOP>\n" .
             '            Cc:' . "\n" .
             "         </TD><TD BGCOLOR=\"$color[0]\" VALIGN=TOP colspan=2>\n" .
             "            <B>$cc_string</B>&nbsp;\n" .
             '         </TD>' . "\n" .
             '      </TR>' . "\n";
    }

    /** bcc **/
    if (isset($bcc_string)) {
        echo "      <TR>\n" .
             "         <TD BGCOLOR=\"$color[0]\" ALIGN=RIGHT VALIGN=TOP>\n" .
             '            Bcc:' . "\n" .
             "         </TD><TD BGCOLOR=\"$color[0]\" VALIGN=TOP colspan=2>\n" .
             "            <B>$bcc_string</B>&nbsp;\n" .
             '         </TD>' . "\n" .
             '      </TR>' . "\n";
    }
    if ($default_use_priority) {
        if (isset($priority_string)) {
            echo "      <TR>\n" .
                 "         <TD BGCOLOR=\"$color[0]\" ALIGN=RIGHT VALIGN=TOP>\n" .
                 "            "._("Priority").": \n".
                 "         </TD><TD BGCOLOR=\"$color[0]\" VALIGN=TOP colspan=2>\n" . 
                 "            <B>$priority_string</B>&nbsp;\n" .
                 "         </TD>" . "\n" .
                 "      </TR>" . "\n";
        }
    }

    if ($show_xmailer_default) {
        fputs ($imapConnection, sqimap_session_id() .
               " FETCH $passed_id BODY.PEEK[HEADER.FIELDS (X-Mailer User-Agent)]\r\n");
        $read = sqimap_read_data ($imapConnection, sqimap_session_id(), true, 
                                  $response, $readmessage);
        $mailer = substr($read[1], strpos($read[1], " "));
        if (trim($mailer)) {
            echo "      <TR>\n" .
                 "         <TD BGCOLOR=\"$color[0]\" ALIGN=RIGHT VALIGN=TOP>\n" .
                 "            "._("Mailer").": \n".
                 "         </TD><TD BGCOLOR=\"$color[0]\" VALIGN=TOP colspan=2>\n" .
                 "            <B>$mailer</B>&nbsp;\n" .
                 "         </TD>" . "\n" .
                 "      </TR>" . "\n";
        }
    }

    /* Output the printer friendly link if we are not in subtle mode. */
    if (!$pf_subtle_link) {
        echo printer_friendly_link(true);
    }

    do_hook("read_body_header");
    echo '</TABLE>' .
         '   </TD></TR>' .
         '</TABLE>';
    flush();        
    echo "<TABLE CELLSPACING=0 WIDTH=\"97%\" BORDER=0 ALIGN=CENTER CELLPADDING=0>\n" .
         "   <TR><TD BGCOLOR=\"$color[4]\" WIDTH=\"100%\">\n" .
         '<BR>';

    $body = formatBody($imapConnection, $message, $color, $wrap_at);

    echo $body .
         '</TABLE>' .
         '<TABLE CELLSPACING="0" WIDTH="100%" BORDER="0" ALIGN="CENTER" CELLPADDING="0">' . "\n" .
         "   <TR><TD BGCOLOR=\"$color[9]\">&nbsp;</TD></TR>" .
         '</TABLE>' . "\n";

    /* show attached images inline -- if pref'fed so */
    if (($attachment_common_show_images) and
        is_array($attachment_common_show_images_list)) {
        foreach ($attachment_common_show_images_list as $img) {
            echo "<TABLE BORDER=0 CELLSPACING=0 CELLPADDING=2 ALIGN=CENTER>\n" .
                 "  <TR>\n" .
                 "    <TD>\n" .
                 '      <img src="../src/download.php' .
                     '?passed_id='     . urlencode($img['passed_id']) .
                     '&mailbox='       . urlencode($img['mailbox']) .
                     '&passed_ent_id=' . urlencode($img['ent_id']) .
                     '&absolute_dl=true">' . "\n" .
                 "    </TD>\n" .
                 "  </TR>\n" .
                 "</TABLE>\n";
        }
    }

    do_hook('read_body_bottom');
    do_hook('html_bottom');
    sqimap_logout($imapConnection);
?>