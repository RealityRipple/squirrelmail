<?
   include("../config/config.php");
   include("../functions/mailbox.php");
   include("../functions/strings.php");
   include("../functions/page_header.php");
   include("../functions/display_messages.php");
   include("../functions/imap.php");
   include("../functions/array.php");

   include("../src/load_prefs.php");


   echo "<HTML><BODY TEXT=\"$color[8]\" BGCOLOR=\"$color[4]\" LINK=\"$color[7]\" VLINK=\"$color[7]\" ALINK=\"$color[7]\">\n";
   displayPageHeader($color, "None");

   /** load up some of the values from the pref file **/
   $fullname = getPref($data_dir, $username, "full_name");
   $replyto  = getPref($data_dir, $username, "reply_to");
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
   echo "      <TD WIDTH=20% ALIGN=RIGHT>";
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
   echo "      <TD WIDTH=20% ALIGN=RIGHT>";
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
   // THEME
   echo "   <TR>";
   echo "      <TD WIDTH=20% ALIGN=RIGHT>";
   echo "         <FONT FACE=\"Arial,Helvetica\">";
   echo "         Theme:";
   echo "         </FONT>";
   echo "      </TD>";
   echo "      <TD WIDTH=80% ALIGN=LEFT>";

   echo "         <TT><SELECT NAME=chosentheme>\n";
   for ($i = 0; $i < count($theme); $i++) {
      if ($theme[$i]["PATH"] == $chosen_theme)
         echo "         <OPTION SELECTED VALUE=\"".$theme[$i]["PATH"]."\">".$theme[$i]["NAME"]."\n";
      else
         echo "         <OPTION VALUE=\"".$theme[$i]["PATH"]."\">".$theme[$i]["NAME"]."\n";
   }
   echo "         </SELECT></TT>";
   echo "      </TD>";
   echo "   </TR>";

   echo "</SELECT></TT>\n";


   // SUBMIT BUTTON
   echo "   <TR>";
   echo "      <TD WIDTH=20%>";
   echo "      </TD>";
   echo "      <TD WIDTH=80% ALIGN=LEFT>";
   echo "         <BR><INPUT TYPE=SUBMIT VALUE=\"Submit\">\n";
   echo "      </TD>";
   echo "   </TR>";

   echo "</TABLE>\n";
   echo "</FORM>";

   echo "</BODY></HTML>";
?>