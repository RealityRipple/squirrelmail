<?php

   /* $Id$ */

   include('../src/validate.php');
   include('../functions/page_header.php');
   include('../functions/imap.php');
   include('../functions/imap_search.php');
   include('../functions/array.php');
   include('../src/load_prefs.php');

   displayPageHeader($color, $mailbox);
   $imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);

   do_hook('search_before_form');
   echo "<br>\n";
   echo "      <table width=95% align=center cellpadding=2 cellspacing=0 border=0>\n";
   echo "      <tr><td bgcolor=\"$color[0]\">\n";
   echo "          <center><b>"._("Search")."</b></center>\n";
   echo "      </td></tr>\n";
   echo '      <tr><td align=center>';

   echo "<FORM ACTION=\"search.php\" NAME=s>\n";
   echo "   <TABLE WIDTH=75%>\n";
   echo "     <TR>\n";
   echo "       <TD WIDTH=33%>\n";
   echo '         <TT><SMALL><SELECT NAME="mailbox">';

   $boxes = sqimap_mailbox_list($imapConnection);
   for ($i = 0; $i < count($boxes); $i++) {
	  if (!in_array('noselect', $boxes[$i]['flags'])) {
         $box = $boxes[$i]['unformatted'];
	 $box2 = str_replace(' ', '&nbsp;', $boxes[$i]['unformatted-disp']);
         if ($mailbox == $box)
            echo "         <OPTION VALUE=\"$box\" SELECTED>$box2\n";
         else
            echo "         <OPTION VALUE=\"$box\">$box2\n";
      }
   }
   echo '         </SELECT></SMALL></TT>';
   echo "       </TD>\n";
   echo "        <TD ALIGN=\"CENTER\" WIDTH=33%>\n";
   if (!isset($what))
       $what = '';
   $what_disp = ereg_replace(',', ' ', $what);
   $what_disp = str_replace('\\\\', '\\', $what_disp);
   $what_disp = str_replace('\\"', '"', $what_disp);
   $what_disp = str_replace('"', '&quot;', $what_disp);
   echo "          <INPUT TYPE=\"TEXT\" SIZE=\"20\" NAME=\"what\" VALUE=\"$what_disp\">\n";
   echo '        </TD>';
   echo "       <TD ALIGN=\"RIGHT\" WIDTH=33%>\n";
   echo '         <SELECT NAME="where">';
   
   if (isset($where) && $where == 'BODY') echo '           <OPTION VALUE="BODY" SELECTED>'._("Body")."\n";
   else echo '           <OPTION VALUE="BODY">'._("Body")."\n";
   
   if (isset($where) && $where == 'TEXT') echo '           <OPTION VALUE="TEXT" SELECTED>'._("Everywhere")."\n";
   else echo '           <OPTION VALUE="TEXT">'._("Everywhere")."\n";
   
   if (isset($where) && $where == 'SUBJECT') echo '           <OPTION VALUE="SUBJECT" SELECTED>'._("Subject")."\n";
   else echo '           <OPTION VALUE="SUBJECT">'._("Subject")."\n";
   
   if (isset($where) && $where == 'FROM') echo '           <OPTION VALUE="FROM" SELECTED>'._("From")."\n";
   else echo '           <OPTION VALUE="FROM">'._("From")."\n";
   
   if (isset($where) && $where == 'CC') echo '           <OPTION VALUE="Cc" SELECTED>'._("Cc")."\n";
   else echo '           <OPTION VALUE="CC">'._("Cc")."\n";
   
   if (isset($where) && $where == 'TO') echo '           <OPTION VALUE="TO" SELECTED>'._("To")."\n";
   else echo '           <OPTION VALUE="TO">'._("To")."\n";
   
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
   if (isset($where) && $where && isset($what) && $what) {   
      sqimap_mailbox_select($imapConnection, $mailbox);
      sqimap_search($imapConnection, $where, $what, $mailbox, $color);
   }
   do_hook("search_bottom");
   sqimap_logout ($imapConnection);
?>
</body></html>
