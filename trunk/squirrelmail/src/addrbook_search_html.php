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

   echo "<center>";
   echo "<form method=post action=\"addrbook_search_html.php\">";
   echo "   <input type=text value=\"$query\"name=query>";
   echo "   <input type=submit value=Submit>";
   echo "</form>";
   echo "</center>";

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

      while(list($key, $row) = each($res)) {
         printf("<tr%s nowrap><td nowrap align=center width=\"5%%\">".
                "<a href=\"compose.php?send_to=%s\">To</A> | ".
                "<a href=\"compose.php?send_to_cc=%s\">Cc</A>".
                "<td nowrap>&nbsp;%s&nbsp;<td nowrap>&nbsp;".
                "<a href=\"compose.php?send_to=%s\">%s</A>&nbsp;".
                "<td nowrap>&nbsp;%s&nbsp;<td nowrap>&nbsp;%s</tr>\n",
                ($line % 2) ? " bgcolor=\"$color[0]\"" : "", $row["email"],
                $row["email"], $row["name"], $row["email"], $row["email"],
                $row["label"], $row["source"]);
         $line++;
      }
      print "</TABLE>";
   }

?>
