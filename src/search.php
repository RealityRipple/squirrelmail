<?php
   session_start();

   if(!isset($logged_in)) {
      set_up_language($squirrelmail_language, true);
      echo _("You must login first.");
      exit;
   }
   if(!isset($username) || !isset($key)) {
      include ("../themes/default_theme.php");
      include ("../functions/display_messages.php");
      printf('<html><BODY TEXT="%s" BGCOLOR="%s" LINK="%s" VLINK="%s" ALINK="%s">',
              $color[8], $color[4], $color[7], $color[7], $color[7]);
      plain_error_message(_("You need a valid user and password to access this page!")
                          . "<br><a href=\"../src/login.php\">"
                          . _("Click here to log back in.") . "</a>.", $color);
      echo "</body></html>";
      exit;
   }

   if (!isset($i18n_php))
      include("../functions/i18n.php");
   if (!isset($config_php))
      include("../config/config.php");
   if (!isset($strings_php))
      include("../functions/strings.php");
   if (!isset($page_header_php))
      include("../functions/page_header.php");
   if (!isset($imap_php))
      include("../functions/imap.php");
   if (!isset($imap_search_php))
      include("../functions/imap_search.php");
   if (!isset($array_php))
      include("../functions/array.php");

   include("../src/load_prefs.php");

   displayPageHeader($color, $mailbox);
   $imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);

   do_hook("search_before_form");
   echo "<br>\n";
   echo "      <table width=95% align=center cellpadding=2 cellspacing=0 border=0>\n";
   echo "      <tr><td bgcolor=\"$color[0]\">\n";
   echo "          <center><b>"._("Search")."</b></center>\n";
   echo "      </td></tr>\n";
   echo "      <tr><td align=center>";

   echo "<FORM ACTION=\"search.php\" NAME=s>\n";
   echo "   <TABLE WIDTH=75%>\n";
   echo "     <TR>\n";
   echo "       <TD WIDTH=33%>\n";
   echo "         <TT><SMALL><SELECT NAME=\"mailbox\">";

   $boxes = sqimap_mailbox_list($imapConnection);
   for ($i = 0; $i < count($boxes); $i++) {
      if ($boxes[$i]["flags"][0] != "noselect" && $boxes[$i]["flags"][1] != "noselect" && $boxes[$i]["flags"][2] != "noselect") {
         $box = $boxes[$i]["unformatted"];
         $box2 = replace_spaces($boxes[$i]["formatted"]);
         if ($mailbox == $box)
            echo "         <OPTION VALUE=\"$box\" SELECTED>$box2\n";
         else
            echo "         <OPTION VALUE=\"$box\">$box2\n";
      }
   }
   echo "         </SELECT></SMALL></TT>";
   echo "       </TD>\n";
   echo "        <TD ALIGN=\"CENTER\" WIDTH=33%>\n";
   $what_disp = ereg_replace(",", " ", $what);
   $what_disp = str_replace("\\\\", "\\", $what_disp);
   $what_disp = str_replace("\\\"", "\"", $what_disp);
   $what_disp = str_replace("\"", "&quot;", $what_disp);
   echo "          <INPUT TYPE=\"TEXT\" SIZE=\"20\" NAME=\"what\" VALUE=\"$what_disp\">\n";
   echo "        </TD>";
   echo "       <TD ALIGN=\"RIGHT\" WIDTH=33%>\n";
   echo "         <SELECT NAME=\"where\">";
   
   if ($where == "BODY") echo "           <OPTION VALUE=\"BODY\" SELECTED>"._("Body")."\n";
   else echo "           <OPTION VALUE=\"BODY\">"._("Body")."\n";
   
   if ($where == "TEXT") echo "           <OPTION VALUE=\"TEXT\" SELECTED>"._("Everywhere")."\n";
   else echo "           <OPTION VALUE=\"TEXT\">"._("Everywhere")."\n";
   
   if ($where == "SUBJECT") echo "           <OPTION VALUE=\"SUBJECT\" SELECTED>"._("Subject")."\n";
   else echo "           <OPTION VALUE=\"SUBJECT\">"._("Subject")."\n";
   
   if ($where == "FROM") echo "           <OPTION VALUE=\"FROM\" SELECTED>"._("From")."\n";
   else echo "           <OPTION VALUE=\"FROM\">"._("From")."\n";
   
   if ($where == "CC") echo "           <OPTION VALUE=\"Cc\" SELECTED>"._("Cc")."\n";
   else echo "           <OPTION VALUE=\"CC\">"._("Cc")."\n";
   
   if ($where == "TO") echo "           <OPTION VALUE=\"TO\" SELECTED>"._("To")."\n";
   else echo "           <OPTION VALUE=\"TO\">"._("To")."\n";
   
   echo "         </SELECT>\n";
   echo "        </TD>\n";
   echo "       <TD COLSPAN=\"3\" ALIGN=\"CENTER\">\n";
   echo "         <INPUT TYPE=\"submit\" VALUE=\""._("Search")."\">\n";
   echo "       </TD>\n";
   echo "     </TR>\n";
   echo "   </TABLE>\n"; 
   echo "</FORM>";
   echo "</td></tr></table>";
   do_hook("search_after_form");
   if ($where && $what) {   
      sqimap_mailbox_select($imapConnection, $mailbox);
      sqimap_search($imapConnection, $where, $what, $mailbox, $color);
   }
   do_hook("search_bottom");
   sqimap_logout ($imapConnection);
?>
</body></html>
