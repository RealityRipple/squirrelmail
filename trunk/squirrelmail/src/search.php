<?php
   session_start();

   if(!isset($logged_in)) {
      echo _("You must login first.");
      exit;
   }
   if(!isset($username) || !isset($key)) {
      echo _("You need a valid user and password to access this page!");
      exit;
   }

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

if (empty($mailbox) || empty($what) || empty($where)) {

echo "<br>
<table width=95% align=center cellpadding=2 cellspacing=2 border=0>
<tr><td bgcolor=\"$color[0]\">
   <center><b>"._("Search")."</b></center>
</td></tr></table><br>";
		echo "<FORM ACTION=\"search.php\">\n";
		echo " <CENTER>\n";
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
      echo "          <INPUT TYPE=\"TEXT\" SIZE=\"20\" NAME=\"what\">\n";
      echo "        </TD>";
		echo "       <TD ALIGN=\"RIGHT\" WIDTH=33%>\n";
		echo "         <SELECT NAME=\"where\">";
		echo "           <OPTION VALUE=\"TEXT\">"._("Everywhere")."\n";
		echo "           <OPTION VALUE=\"SUBJECT\">"._("Subject")."\n";
		echo "           <OPTION VALUE=\"FROM\">"._("From")."\n";
		echo "           <OPTION VALUE=\"TO\">"._("To")."\n";
		echo "         </SELECT>\n";
		echo "        </TD>\n";
		echo "     </TR>\n";
		echo "     <TR>\n";
		echo "       <TD COLSPAN=\"3\" ALIGN=\"CENTER\">\n";
		echo "         <INPUT TYPE=\"submit\" VALUE=\""._("Search")."\">\n";
		echo "       </TD>\n";
		echo "     </TR>\n";
		echo "   </TABLE>\n"; 
		echo "  </CENTER>\n";
		echo "</FORM>";
} else {

sqimap_mailbox_select($imapConnection, $mailbox);
	sqimap_search($imapConnection, $where, $what, $mailbox, $color);

}
sqimap_logout ($imapConnection);
?>
</body></html>
