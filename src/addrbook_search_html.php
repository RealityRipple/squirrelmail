<?php
   /**
    **  addrbook_search.php
    **
    **  Handle addressbook searching with pure html.  This file is included from compose.php 
    **
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
   if (!isset($date_php))
      include("../functions/date.php");
   if (!isset($mime_php))
      include("../functions/mime.php");
   if (!isset($smtp_php))
      include("../functions/smtp.php");
   if (!isset($display_messages_php))
      include("../functions/display_messages.php");
   if (!isset($addressbook_php))
      include("../functions/addressbook.php");

   include("../src/load_prefs.php");


   echo "<HTML><BODY TEXT=\"$color[8]\" BGCOLOR=\"$color[4]\" LINK=\"$color[7]\" VLINK=\"$color[7]\" ALINK=\"$color[7]\">\n";
   displayPageHeader($color, "None");
   //<form method=post action="compose.php?html_addr_search=true">

   
   $body = stripslashes($body);
   $send_to = stripslashes($send_to);
   $send_to_cc = stripslashes($send_to_cc);
   $send_to_bcc = stripslashes($send_to_bcc);
   $subject = stripslashes($subject);

   echo "<center>";
   echo "<form method=post action=\"compose.php?html_addr_search=true\">";
   echo "   <input type=text value=\"$query\"name=query>";
   echo "   <input type=submit value=Submit>";
   echo "   <input type=hidden value=\"$body\" name=body>";
   echo "   <input type=hidden value=\"$subject\" name=subject>";
   echo "   <input type=hidden value=\"$send_to\" name=send_to>";
   echo "   <input type=hidden value=\"$send_to_cc\" name=send_to_cc>";
   echo "   <input type=hidden value=\"$send_to_bcc\" name=send_to_bcc>";
   echo "</form>";
   echo "</center>";

   echo "<tt>".nl2br($body)."</tt>";

   if(!empty($query)) {
      $abook = addressbook_init();
      $res = $abook->s_search($query);

      if(!is_array($res)) {
         printf("<P ALIGN=center><BR>%s:<br>%s</P>\n</BODY></HTML>\n",
                _("Your search failed with the following error(s)"),
                $abook->error);
         exit;
      }

      if(sizeof($res) == 0) {
         printf("<P ALIGN=center><BR>%s.</P>\n</BODY></HTML>\n",
                _("No persons matching your search was found"));
         exit;
      }

      // List search results
      $line = 0;
      print "<table border=0 width=\"98%\" align=center>";
      printf("<tr bgcolor=\"$color[9]\"><TH align=left>&nbsp;".
             "<TH align=left>&nbsp;%s<TH align=left>&nbsp;%s".
             "<TH align=left>&nbsp;%s<TH align=left width=\"10%%\">".
             "&nbsp;%s</tr>\n",
             _("Name"), _("E-mail"), _("Info"), _("Source"));

      ?> 
         <form method=post action"compose.php?html_addr_search_done=true"> 
      <?
   echo "   <input type=hidden value=\"$body\" name=body>";
   echo "   <input type=hidden value=\"$subject\" name=subject>";
   echo "   <input type=hidden value=\"$send_to\" name=send_to>";
   echo "   <input type=hidden value=\"$send_to_cc\" name=send_to_cc>";
   echo "   <input type=hidden value=\"$send_to_bcc\" name=send_to_bcc>";
      
      while(list($key, $row) = each($res)) {
         printf("<tr%s nowrap><td nowrap align=center width=\"5%%\">".
                "<input type=checkbox name=send_to_search[] value=\"%s\">&nbsp;To".
                "<input type=checkbox name=send_to_cc_search[] value=\"%s\">&nbsp;Cc&nbsp;".
                "<td nowrap>&nbsp;%s&nbsp;<td nowrap>&nbsp;".
                "<a href=\"compose.php?send_to_search=%s\">%s</A>&nbsp;".
                "<td nowrap>&nbsp;%s&nbsp;<td nowrap>&nbsp;%s</tr>\n",
                ($line % 2) ? " bgcolor=\"$color[0]\"" : "", $row["email"],
                $row["email"], $row["name"], $row["email"], $row["email"],
                $row["label"], $row["source"]);
         $line++;
      }
      print "</TABLE>";
      echo "<input type=hidden value=1 name=html_addr_search_done>";
      echo "<center><input type=submit value=addr_search_done name=\"Use Addresses\"></center>";
      echo "</form>";
   }

?>
