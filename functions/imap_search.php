<?php     
/******************************************************************
 ** IMAP SEARCH ROUTIES
 *****************************************************************/
   if (!isset($imap_php))
      include("../functions/imap.php");
   if (!isset($date_php))
      include("../functions/date.php");
   if (!isset($array_php))
      include("../functions/array.php");
   if (!isset($mailbox_display_php))
      include("../functions/mailbox_display.php");
   if (!isset($mime_php))
      include("../functions/mime.php");

   $imap_search_php = true;

function sqimap_search($imapConnection,$search_where,$search_what,$mailbox,$color) {
   global $msgs, $message_highlight_list, $squirrelmail_language, $languages, $index_order;
   $urlMailbox = urlencode($mailbox);
   
   # Construct the Search QuERY
   
   if ($languages[$squirrelmail_language]["CHARSET"]) {
      $ss = "a001 SEARCH CHARSET ".$languages[$squirrelmail_language]["CHARSET"]." ALL $search_where \"$search_what\"\r\n";
   } else {
      $ss = "a001 SEARCH ALL $search_where \"$search_what\"\r\n";
   }
   fputs($imapConnection,$ss);

   # Read Data Back From IMAP
   $readin = sqimap_read_data ($imapConnection, "a001", true, $result, $message);
   unset($messagelist); $msgs=""; $c = 0;

   #Keep going till we find the SEARCH responce
   while ($c < count($readin)) {

      #Check to see if a SEARCH Responce was recived
      if (substr($readin[$c],0,9) == "* SEARCH ")
         $messagelist = explode(" ",substr($readin[$c],9));
      else
         $errors = $errors.$readin[$c];
      $c++;
   }

   #If nothing is found * SEARCH should be the first error else echo errors
   if (strstr($errors,"* SEARCH")) {
      echo "<br><CENTER>No Messages Found</CENTER>";
      return;
   } else {
      echo "<!-- ".$errors." -->";
   }

   # HACKED CODED FROM ANOTHER FUNCTION, Could Probably dump this and mondify 
   # exsitising code with a search true/false varible.

   global $sent_folder;
   for ($q = 0; $q < count($messagelist); $q++) {
      $messagelist[$q] = trim($messagelist[$q]);
      if ($mailbox == $sent_folder)
         $hdr = sqimap_get_small_header ($imapConnection, $messagelist[$q], true);
      else
         $hdr = sqimap_get_small_header ($imapConnection, $messagelist[$q], false);
						
			$from[$q] = $hdr->from;
			$date[$q] = $hdr->date;
			$subject[$q] = $hdr->subject;
         $to[$q] = $hdr->to;
         $priority[$q] = $hdr->priority;
         $cc[$q] = $hdr->cc;
         $id[$q] = $messagelist[$q];

         $flags[$q] = sqimap_get_flags ($imapConnection, $messagelist[$q]);
      }

      $j = 0;
      while ($j < count($messagelist)) {
         $date[$j] = ereg_replace("  ", " ", $date[$j]);
         $tmpdate = explode(" ", trim($date[$j]));

         $messages[$j]["TIME_STAMP"] = getTimeStamp($tmpdate);
         $messages[$j]["DATE_STRING"] = getDateString($messages[$j]["TIME_STAMP"]);
         $messages[$j]["ID"] = $id[$j];
         $messages[$j]["FROM"] = decodeHeader($from[$j]);
         $messages[$j]["FROM-SORT"] = strtolower(sqimap_find_displayable_name(decodeHeader($from[$j])));
         $messages[$j]["SUBJECT"] = decodeHeader($subject[$j]);
         $messages[$j]["SUBJECT-SORT"] = strtolower(decodeHeader($subject[$j]));
         $messages[$j]["TO"] = decodeHeader($to[$j]);
			$messages[$j]["PRIORITY"] = $priority[$j];
         $messages[$j]["CC"] = $cc[$j];

         $num = 0;
         while ($num < count($flags[$j])) {
            if ($flags[$j][$num] == "Deleted") {
               $messages[$j]["FLAG_DELETED"] = true;
            }
            else if ($flags[$j][$num] == "Answered") {
               $messages[$j]["FLAG_ANSWERED"] = true;
            }
            else if ($flags[$j][$num] == "Seen") {
               $messages[$j]["FLAG_SEEN"] = true;
            }
            else if ($flags[$j][$num] == "Flagged") {
               $messages[$j]["FLAG_FLAGGED"] = true;
            }
            $num++;
         }
         $j++;
      }

      /** Find and remove the ones that are deleted */
      $i = 0;
      $j = 0;
      while ($j < count($messagelist)) {
         if ($messages[$j]["FLAG_DELETED"] == true) {
            $j++;
            continue;
         }
         $msgs[$i] = $messages[$j];

         $i++;
         $j++;
      }
      $numMessages = $i;

      // There's gotta be messages in the array for it to sort them.

      if (count($messagelist) > 0) {
         $j=0;
         echo "<center><b>" . _("Found") . " " . count($messagelist) . " " . _("messages") . "</b></center>\n";
         echo "<br>\n";
         echo "<TABLE WIDTH=100% BORDER=0 CELLPADDING=2 CELLSPACING=0>";
         echo "<TR><TD BGCOLOR=\"$color[0]\">";
   
         echo "\n\n\n<FORM name=messageList method=post action=\"move_messages.php?msg=$msg&mailbox=$urlMailbox&where=".urlencode($search_where)."&what=".urlencode($search_what)."\">";
         echo "<TABLE BGCOLOR=\"$color[0]\" COLS=2 BORDER=0 cellpadding=0 cellspacing=0 width=\"100%\">\n";
         echo "   <TR>\n";
         echo "      <TD WIDTH=60% ALIGN=LEFT VALIGN=CENTER>\n";
         echo "         <NOBR><SMALL>". _("Move selected to:") ."</SMALL>";
         echo "         <TT><SMALL><SELECT NAME=\"targetMailbox\">";
   
         $boxes = sqimap_mailbox_list($imapConnection);
         for ($i = 0; $i < count($boxes); $i++) {
            if ($boxes[$i]["flags"][0] != "noselect" && $boxes[$i]["flags"][1] != "noselect" && $boxes[$i]["flags"][2] != "noselect") {
               $box = $boxes[$i]["unformatted"];
               $box2 = replace_spaces($boxes[$i]["formatted"]);
               echo "         <OPTION VALUE=\"$box\">$box2\n";
            }
         }
         echo "         </SELECT></SMALL></TT>";
         echo "         <SMALL><INPUT TYPE=SUBMIT NAME=\"moveButton\" VALUE=\"". _("Move") ."\"></SMALL></NOBR>\n";
   
         echo "      </TD>\n";
         echo "      <TD WIDTH=40% ALIGN=RIGHT>\n";
         echo "         <NOBR><SMALL><INPUT TYPE=SUBMIT VALUE=\"". _("Delete") ."\">&nbsp;". _("checked messages") ."</SMALL></NOBR>\n";
         echo "      </TD>";
         echo "   </TR>\n";
         echo "</TABLE>\n";
         do_hook("mailbox_form_before");
         echo "</TD></TR>";
         echo "<TR><TD BGCOLOR=\"$color[0]\">";
         echo "<TABLE WIDTH=100% BORDER=0 CELLPADDING=2 CELLSPACING=1 BGCOLOR=\"$color[0]\">";
         echo "<TR BGCOLOR=\"$color[5]\" ALIGN=\"center\">";

         for ($i=1; $i <= count($index_order); $i++) {
            switch ($index_order[$i]) {
               case 1: # checkbox
                  echo "   <TD WIDTH=1%><B>&nbsp;</B></TD>";
                  break;
               case 2: # from
                  if ($mailbox == $sent_folder)
               	     echo "   <TD WIDTH=30%><B>". _("To") ."</B>";
                  else
               	     echo "   <TD WIDTH=30%><B>". _("From") ."</B>";
         
                  if ($sort == 2)
                     echo "   <A HREF=\"right_main.php?newsort=3&startMessage=1&mailbox=$urlMailbox\" TARGET=\"right\"><IMG SRC=\"../images/up_pointer.gif\" BORDER=0></A></TD>\n";
                  else if ($sort == 3)
                     echo "   <A HREF=\"right_main.php?newsort=2&startMessage=1&mailbox=$urlMailbox\" TARGET=\"right\"><IMG SRC=\"../images/down_pointer.gif\" BORDER=0></A></TD>\n";
                  else
                     echo "   <A HREF=\"right_main.php?newsort=3&startMessage=1&mailbox=$urlMailbox\" TARGET=\"right\"><IMG SRC=\"../images/sort_none.gif\" BORDER=0></A></TD>\n";
                  break;
               case 3: # date
                  echo "   <TD nowrap WIDTH=1%><B>". _("Date") ."</B>";
                  if ($sort == 0)
                     echo "   <A HREF=\"right_main.php?newsort=1&startMessage=1&mailbox=$urlMailbox\" TARGET=\"right\"><IMG SRC=\"../images/up_pointer.gif\" BORDER=0></A></TD>\n";
                  else if ($sort == 1)
                     echo "   <A HREF=\"right_main.php?newsort=0&startMessage=1&mailbox=$urlMailbox\" TARGET=\"right\"><IMG SRC=\"../images/down_pointer.gif\" BORDER=0></A></TD>\n";
                  else
                     echo "   <A HREF=\"right_main.php?newsort=0&startMessage=1&mailbox=$urlMailbox\" TARGET=\"right\"><IMG SRC=\"../images/sort_none.gif\" BORDER=0></A></TD>\n";
                  break;
               case 4: # subject
                  echo "   <TD WIDTH=%><B>". _("Subject") ."</B>\n";
                  if ($sort == 4)
                     echo "   <A HREF=\"right_main.php?newsort=5&startMessage=1&mailbox=$urlMailbox\" TARGET=\"right\"><IMG SRC=\"../images/up_pointer.gif\" BORDER=0></A></TD>\n";
                  else if ($sort == 5)
                     echo "   <A HREF=\"right_main.php?newsort=4&startMessage=1&mailbox=$urlMailbox\" TARGET=\"right\"><IMG SRC=\"../images/down_pointer.gif\" BORDER=0></A></TD>\n";
                  else
                     echo "   <A HREF=\"right_main.php?newsort=5&startMessage=1&mailbox=$urlMailbox\" TARGET=\"right\"><IMG SRC=\"../images/sort_none.gif\" BORDER=0></A></TD>\n";
                  break;
               case 5: # flags
                  echo "   <TD WIDTH=1%>&nbsp;</TD>\n";
                  break;
               case 6: # size   
                  echo "   <TD WIDTH=1%><b>" . _("Size")."</b></TD>\n";
                  break;
            }
         }
         echo "</TR>";
 
         while ($j < count($msgs)) {
            printMessageInfo($imapConnection, $msgs[$j]["ID"], 0, $j, $mailbox, "", 0, $search_where, $search_what);
            $j++;
         }
         echo "</table>";
         echo "</tr></td></table>";
      }
   }

?>
