<?
   include("../config/config.php");
   include("../functions/mailbox.php");
   include("../functions/strings.php");
   include("../functions/page_header.php");
   include("../functions/display_messages.php");
   include("../functions/imap.php");
   include("../functions/array.php");
   include("../functions/prefs.php");

   echo "<HTML><BODY TEXT=\"$color[8]\" BGCOLOR=\"$color[4]\" LINK=\"$color[7]\" VLINK=\"$color[7]\" ALINK=\"$color[7]\">\n";
   displayPageHeader($color, "None");

   /** load up some of the values from the pref file **/
   $fullname = getPref($username, "full_name");
   $replyto  = getPref($username, "reply_to");
   if ($replyto == "")
      $replyto = "$username@$domain";

   echo "<TABLE WIDTH=100% COLS=1 ALIGN=CENTER>\n";
   echo "   <TR><TD BGCOLOR=\"$color[0]\" ALIGN=CENTER>\n";
   echo "      <FONT FACE=\"Arial,Helvetica\">Options</FONT>\n";
   echo "   </TD></TR>\n";
   echo "</TABLE>\n";

   echo "<FORM action=\"options_submit.php\" METHOD=POST>\n";
   echo "<TABLE WIDTH=100% COLS=2 ALIGN=CENTER>\n";
   // FULL NAME
   echo "   <TR>";
   echo "      <TD WIDTH=20% ALIGN=RIGHT BGCOLOR=\"$color[0]\">";
   echo "         <FONT FACE=\"Arial,Helvetica\">";
   echo "         Full Name:";
   echo "         </FONT>";
   echo "      </TD>";
   echo "      <TD WIDTH=80% ALIGN=LEFT>";
   echo "         <FONT FACE=\"Arial,Helvetica\">";
   echo "         <INPUT TYPE=TEXT NAME=full_name VALUE=\"$fullname\" SIZE=50>";
   echo "         </FONT>";
   echo "      </TD>";
   echo "   </TR>";
   // REPLY-TO
   echo "   <TR>";
   echo "      <TD WIDTH=20% ALIGN=RIGHT BGCOLOR=\"$color[0]\">";
   echo "         <FONT FACE=\"Arial,Helvetica\">";
   echo "         Reply-to:";
   echo "         </FONT>";
   echo "      </TD>";
   echo "      <TD WIDTH=80% ALIGN=LEFT>";
   echo "         <FONT FACE=\"Arial,Helvetica\">";
   echo "         <INPUT TYPE=TEXT NAME=reply_to VALUE=\"$replyto\" SIZE=50>";
   echo "         </FONT>";
   echo "      </TD>";
   echo "   </TR>";
   // SUBMIT BUTTON
   echo "   <TR>";
   echo "      <TD WIDTH=20%>";
   echo "      </TD>";
   echo "      <TD WIDTH=80% ALIGN=LEFT>";
   echo "         <INPUT TYPE=SUBMIT VALUE=\"Submit\">\n";
   echo "      </TD>";
   echo "   </TR>";

   echo "</TABLE>\n";
   echo "</FORM>";

   echo "</BODY></HTML>";
?>