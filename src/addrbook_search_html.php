<?php
   /**
    **  addrbook_search.php
    **
    **  Copyright (c) 1999-2000 The SquirrelMail development team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    **  Handle addressbook searching with pure html. 
    ** 
    **  This file is included from compose.php 
    **
    **  NOTE: A lot of this code is similar to the code in
    **        addrbook_search.html -- If you change one, change
    **        the other one too!
    **
    **  $Id$
    **/

   include('../src/validate.php');
   include('../functions/page_header.php');
   include('../functions/date.php');
   include('../functions/smtp.php');
   include('../functions/display_messages.php');
   include('../functions/addressbook.php');
   include('../functions/plugin.php');
   include('../src/load_prefs.php');

   // Insert hidden data
   function addr_insert_hidden() {
      global $body, $subject, $send_to, $send_to_cc, $send_to_bcc, $mailbox,
         $identity;
      
      echo '<input type=hidden value="';
      if (substr($body, 0, 1) == "\r")
          echo "\n";
      echo htmlspecialchars($body) . '" name=body>' . "\n";
      echo '<input type=hidden value="' . htmlspecialchars($subject)
          . '" name=subject>' . "\n";
      echo '<input type=hidden value="' . htmlspecialchars($send_to)
          . '" name=send_to>' . "\n";
      echo "<input type=hidden value=\"" . htmlspecialchars($send_to_cc)
          . '" name=send_to_cc>' . "\n";
      echo "<input type=hidden value=\"" . htmlspecialchars($send_to_bcc)
          . '" name=send_to_bcc>' . "\n";
      echo "<input type=hidden value=\"" . htmlspecialchars($identity)
          . '" name=identity>' . "\n";
      echo "<input type=hidden name=mailbox value=\"" .
          htmlspecialchars($mailbox) . "\">\n";
      echo "<input type=hidden value=\"true\" name=from_htmladdr_search>\n";
   }


   // List search results
   function addr_display_result($res, $includesource = true) {
      global $color, $PHP_SELF;

      if(sizeof($res) <= 0) return;

      echo '<form method=post action="' . $PHP_SELF . "\">\n";
      echo '<input type=hidden name="html_addr_search_done" value="true">';
      echo "\n";
      addr_insert_hidden();
      $line = 0;

      print "<TABLE BORDER=0 WIDTH=\"98%\" ALIGN=center>";
      printf("<TR BGCOLOR=\"$color[9]\"><TH ALIGN=left>&nbsp;".
             "<TH ALIGN=left>&nbsp;%s<TH ALIGN=left>&nbsp;%s".
             "<TH ALIGN=left>&nbsp;%s",
             _("Name"), _("E-mail"), _("Info"));

      if($includesource)
         printf("<TH ALIGN=left WIDTH=\"10%%\">&nbsp;%s", _("Source"));

      print "</TR>\n";
      
      foreach ($res as $row) {
         echo '<tr';
	 if ($line % 2) echo ' bgcolor="' . $color[0] . '"';
	 echo ' nowrap><td nowrap align=center width="5%">';
	 echo '<input type=checkbox name="send_to_search[T' . $line . ']" value = "' .
	    htmlspecialchars($row['email']) . '">&nbsp;To&nbsp;';
	 echo '<input type=checkbox name="send_to_search[C' . $line . ']" value = "' .
	    htmlspecialchars($row['email']) . '">&nbsp;Cc&nbsp;';
	 echo '<input type=checkbox name="send_to_search[B' . $line . ']" value = "' .
	    htmlspecialchars($row['email']) . '">&nbsp;Bcc&nbsp;';
         echo '</td><td nowrap>&nbsp;' . $row['name'] . '&nbsp;</td>';
	 echo '<td nowrap>&nbsp;' . $row['email'] . '&nbsp;</td>';
	 echo '<td nowrap>&nbsp;' . $row['label'] . '&nbsp;</td>';
         if($includesource)
	    echo '<td nowrap>&nbsp;' . $row['source'] . '&nbsp;</td>';
	 echo "</tr>\n";
	 $line ++;
      }
      printf('<TR><TD ALIGN=center COLSPAN=%d><INPUT TYPE=submit '.
             'NAME="addr_search_done" VALUE="%s"></TD></TR>',
             4 + ($includesource ? 1 : 0), 
             _("Use Addresses"));
      print '</TABLE>';
      print '<INPUT TYPE=hidden VALUE=1 NAME="html_addr_search_done">';
      print '</FORM>';
   }

   // --- End functions ---

   global $mailbox;
   displayPageHeader($color, $mailbox);
   
   // Initialize addressbook
   $abook = addressbook_init();

?>

<br>
<table width=95% align=center cellpadding=2 cellspacing=2 border=0>
<tr><td bgcolor="<?php echo $color[0] ?>">
   <center><b><?php echo _("Address Book Search") ?></b></center>
</td></tr></table>

<?php
   // Search form
   print "<CENTER>\n";
   print "<TABLE BORDER=0>\n";
   print "<TR><TD NOWRAP VALIGN=middle>\n";
   printf('<FORM METHOD=post NAME=f ACTION="%s?html_addr_search=true">'."\n", $PHP_SELF);
   print "<CENTER>\n";
   printf("  <nobr><STRONG>%s</STRONG>\n", _("Search for"));
   addr_insert_hidden();
   if (! isset($addrquery))
       $addrquery = '';
   printf("  <INPUT TYPE=text NAME=addrquery VALUE=\"%s\" SIZE=26>\n",
          htmlspecialchars($addrquery));

   // List all backends to allow the user to choose where to search
   if(!isset($backend)) $backend = "";
   if($abook->numbackends > 1) {
      printf("<STRONG>%s</STRONG>&nbsp;<SELECT NAME=backend>\n", 
             _("in"));
      printf("<OPTION VALUE=-1 %s>%s\n", 
             ($backend == -1) ? "SELECTED" : "",
             _("All address books"));
      $ret = $abook->get_backend_list();
      while(list($undef,$v) = each($ret)) 
         printf("<OPTION VALUE=%d %s>%s\n", 
                $v->bnum, 
                ($backend == $v->bnum) ? "SELECTED" : "",
                $v->sname);
      print "</SELECT>\n";
   } else {
      print "<INPUT TYPE=hidden NAME=backend VALUE=-1>\n";
   }
   printf("<INPUT TYPE=submit VALUE=\"%s\">",
          _("Search"));
   printf("&nbsp;|&nbsp;<INPUT TYPE=submit VALUE=\"%s\" NAME=listall>\n",
          _("List all"));
   print '</FORM></center>';

   print "</TD></TR></TABLE>\n";
   addr_insert_hidden();
   print "</CENTER>";
   do_hook('addrbook_html_search_below');
   // End search form

   // Show personal addressbook
   if($addrquery == '' || !empty($listall)) {

      if(! isset($backend) || $backend != -1 || $addrquery == '') {
         if($addrquery == '')
            $backend = $abook->localbackend;

         //printf("<H3 ALIGN=center>%s</H3>\n", $abook->backends[$backend]->sname);

         $res = $abook->list_addr($backend);

         if(is_array($res)) {
            addr_display_result($res, false);
         } else {
            printf("<P ALIGN=center><STRONG>"._("Unable to list addresses from %s").
                   "</STRONG></P>\n", $abook->backends[$backend]->sname);
         }

      } else {
         $res = $abook->list_addr();
         addr_display_result($res, true);
      }
      exit;

   } else

   // Do the search
   if(!empty($addrquery) && empty($listall)) {

      if($backend == -1) {
         $res = $abook->s_search($addrquery);
      } else {
         $res = $abook->s_search($addrquery, $backend);
      }

      if(!is_array($res)) {
         printf("<P ALIGN=center><B><BR>%s:<br>%s</B></P>\n</BODY></HTML>\n",
                _("Your search failed with the following error(s)"),
                $abook->error);
      } else if(sizeof($res) == 0) {
         printf("<P ALIGN=center><BR><B>%s.</B></P>\n</BODY></HTML>\n",
                _("No persons matching your search was found"));
      } else {
         addr_display_result($res);
      }
   }

   if ($addrquery == '' || sizeof($res) == 0) {  
      printf('<center><FORM METHOD=post NAME=k ACTION="compose.php">'."\n", $PHP_SELF);
      addr_insert_hidden();
      printf("<INPUT TYPE=submit VALUE=\"%s\" NAME=return>\n", _("Return"));
      print '</form>';
      print '</center></nobr>';
   }   

?>
</body></html>
