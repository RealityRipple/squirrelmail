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
    **/

   session_start();

   if (!isset($config_php))
      include("../config/config.php");
   if (!isset($strings_php))
      include("../functions/strings.php");
   if (!isset($auth_php))
      include("../functions/auth.php");
   if (!isset($page_header_php))
      include("../functions/page_header.php");
   if (!isset($date_php))
      include("../functions/date.php");
   if (!isset($smtp_php))
      include("../functions/smtp.php");
   if (!isset($display_messages_php))
      include("../functions/display_messages.php");
   if (!isset($addressbook_php))
      include("../functions/addressbook.php");
   if (!isset($plugin_php))
      include("../functions/plugin.php");

   include("../src/load_prefs.php");

   // Insert hidden data
   function addr_insert_hidden() {
      global $body, $subject, $send_to, $send_to_cc, $send_to_bcc;
      printf("<input type=hidden value=\"%s\" name=body>\n", 
	     htmlspecialchars($body));
      printf("<input type=hidden value=\"%s\" name=subject>\n", 
	     htmlspecialchars($subject));
      printf("<input type=hidden value=\"%s\" name=send_to>\n", 
	     htmlspecialchars($send_to));
      printf("<input type=hidden value=\"%s\" name=send_to_cc>\n", 
	     htmlspecialchars($send_to_cc));
      printf("<input type=hidden value=\"%s\" name=send_to_bcc>\n", 
	     htmlspecialchars($send_to_bcc));     
   }


   // List search results
   function addr_display_result($res, $includesource = true) {
      global $color, $PHP_SELF;

      if(sizeof($res) <= 0) return;

      printf('<FORM METHOD=post ACTION="%s?html_addr_search_done=true">'."\n",
	     $PHP_SELF);
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
      
      while(list($undef, $row) = each($res)) {
         printf("<tr%s nowrap><td nowrap align=center width=\"5%%\">".
                "<input type=checkbox name=\"send_to_search[]\" value=\"%s\">&nbsp;To".
                "<input type=checkbox name=\"send_to_cc_search[]\" value=\"%s\">&nbsp;Cc&nbsp;".
                "<td nowrap>&nbsp;%s&nbsp;<td nowrap>&nbsp;".
                "%s".
                "<td nowrap>&nbsp;%s&nbsp;",
                ($line % 2) ? " bgcolor=\"$color[0]\"" : "", 
		htmlspecialchars($row["email"]), htmlspecialchars($row["email"]), 
		$row["name"], $row["email"], $row["label"]);
	 if($includesource)
	    printf("<td nowrap>&nbsp;%s", $row["source"]);
	 
	 print "</TR>\n";
         $line++;
      }
      printf('<TR><TD ALIGN=center COLSPAN=%d><INPUT TYPE=submit '.
	     'NAME="addr_search_done" VALUE="%s"></TD></TR>',
	     4 + ($includesource ? 1 : 0), 
	     _("Use Addresses"));
      print "</TABLE>";
      print '<INPUT TYPE=hidden VALUE=1 NAME="html_addr_search_done">';
      print "</FORM>";
   }

   // --- End functions ---

   displayPageHeader($color, "None");

   // Initialize addressbook
   $abook = addressbook_init();

   $body = stripslashes($body);
   $send_to = stripslashes($send_to);
   $send_to_cc = stripslashes($send_to_cc);
   $send_to_bcc = stripslashes($send_to_bcc);
   $subject = stripslashes($subject);


   // Header
   print  "<TABLE BORDER=0 WIDTH=100% COLS=1 ALIGN=CENTER>\n";
   printf('<TR><TD BGCOLOR="%s" ALIGN=CENTER><STRONG>%s</STRONG></TD></TR>', 
	  $color[0], _("Address Book Search"));
   print  "</TABLE>\n";

   // Search form
   print "<CENTER>\n";
   printf('<FORM METHOD=post NAME=f ACTION="%s?html_addr_search=true">'."\n",
	  $PHP_SELF);
   print "<TABLE BORDER=0>\n";
   printf("<TR><TD NOWRAP VALIGN=middle>\n");
   printf("  <STRONG>%s</STRONG>\n", _("Search for"));
   printf("  <INPUT TYPE=text NAME=addrquery VALUE=\"%s\" SIZE=26>\n",
	  htmlspecialchars($addrquery));

   // List all backends to allow the user to choose where to search
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
      printf("</SELECT>\n");
   } else {
      printf("<INPUT TYPE=hidden NAME=backend VALUE=-1>\n");
   }
   printf("<INPUT TYPE=submit VALUE=\"%s\">",
	  _("Search"));
   printf("&nbsp;|&nbsp;<INPUT TYPE=submit VALUE=\"%s\" NAME=listall>\n",
	  _("List all"));
   printf("</TD></TR></TABLE>\n");
   addr_insert_hidden();
   print "</FORM>";
   print "</CENTER>";
   do_hook("addrbook_html_search_below");
   // End search form

   // Show personal addressbook
   if(!isset($addrquery) || !empty($listall)) {

      if($backend != -1 || !isset($addrquery)) {
	 if(!isset($addrquery)) 
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
	 exit;
      }

      if(sizeof($res) == 0) {
	 printf("<P ALIGN=center><BR><B>%s.</B></P>\n</BODY></HTML>\n",
		_("No persons matching your search was found"));
	 exit;
      }

      addr_display_result($res);
   }

?>
</body></html>
