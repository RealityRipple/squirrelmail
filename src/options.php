<?
   include("../config/config.php");
   include("../functions/strings.php");
   include("../functions/page_header.php");
   include("../functions/display_messages.php");
   include("../functions/imap.php");
   include("../functions/array.php");

   include("../src/load_prefs.php");


   $imapConnection = sqimap_login($username, $key, $imapServerAddress);
   $boxes = sqimap_mailbox_list($imapConnection, $boxes);
   fputs($imapConnection, "1 logout\n");

   echo "<HTML><BODY TEXT=\"$color[8]\" BGCOLOR=\"$color[4]\" LINK=\"$color[7]\" VLINK=\"$color[7]\" ALINK=\"$color[7]\">\n";
   displayPageHeader($color, "None");

   /** load up some of the values from the pref file **/
   $fullname = getPref($data_dir, $username, "full_name");
   $replyto  = getPref($data_dir, $username, "reply_to");

   echo "<TABLE WIDTH=100% COLS=1 ALIGN=CENTER>\n";
   echo "   <TR><TD BGCOLOR=\"$color[0]\" ALIGN=CENTER>\n";
   echo "      <FONT FACE=\"Arial,Helvetica\">";
   echo _("Options");
   echo "</FONT>\n";
   echo "   </TD></TR>\n";
   echo "</TABLE>\n";

   echo "<FORM action=\"options_submit.php\" METHOD=POST>\n";
   echo "<TABLE WIDTH=100% COLS=2 ALIGN=CENTER>\n";
   // FULL NAME
   echo "   <TR>";
   echo "      <TD WIDTH=20% ALIGN=RIGHT>";
   echo "         <FONT FACE=\"Arial,Helvetica\">";
   echo _("         Full Name:");
   echo "         </FONT>";
   echo "      </TD>";
   echo "      <TD WIDTH=80% ALIGN=LEFT>";
   echo "         <FONT FACE=\"Arial,Helvetica\">";
   echo "         <INPUT TYPE=TEXT NAME=full_name VALUE=\"$fullname\" SIZE=50><BR>";
   echo "         </FONT>";
   echo "      </TD>";
   echo "   </TR>";
   // REPLY-TO
   echo "   <TR>";
   echo "      <TD WIDTH=20% ALIGN=RIGHT>";
   echo "         <FONT FACE=\"Arial,Helvetica\">";
   echo _("         Reply-to:");
   echo "         </FONT>";
   echo "      </TD>";
   echo "      <TD WIDTH=80% ALIGN=LEFT>";
   echo "         <FONT FACE=\"Arial,Helvetica\">";
   echo "         <INPUT TYPE=TEXT NAME=reply_to VALUE=\"$replyto\" SIZE=50><BR>";
   echo "         </FONT>";
   echo "      </TD>";
   echo "   </TR>";
   // THEME
   echo "   <TR>";
   echo "      <TD WIDTH=20% ALIGN=RIGHT>";
   echo "         <FONT FACE=\"Arial,Helvetica\">";
   echo _("         Theme:");
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
   echo "</TABLE>";



   echo "<TABLE WIDTH=100% COLS=2 ALIGN=CENTER>\n";
   // MOVE_TO_TRASH
   echo "   <TR>";
   echo "      <TD WIDTH=60% ALIGN=RIGHT>";
   echo "         <FONT FACE=\"Arial,Helvetica\">";
   echo _("         Move deleted messages to ");
   echo "\"$trash_folder\"?";
   echo "         </FONT>";
   echo "      </TD>";
   echo "      <TD WIDTH=40% ALIGN=LEFT>";
   echo "         <FONT FACE=\"Arial,Helvetica\">";
   if ($move_to_trash == true)
      echo "         <INPUT TYPE=RADIO NAME=movetotrash VALUE=1 CHECKED>&nbsp;True<BR>";
   else
      echo "         <INPUT TYPE=RADIO NAME=movetotrash VALUE=1>&nbsp;True<BR>";

   if ($move_to_trash == false)
      echo "         <INPUT TYPE=RADIO NAME=movetotrash VALUE=0 CHECKED>&nbsp;False";
   else
      echo "         <INPUT TYPE=RADIO NAME=movetotrash VALUE=0>&nbsp;False";

   echo "         </FONT>";
   echo "      </TD>";
   echo "   </TR>";

   // WRAP_AT
   echo "   <TR>";
   echo "      <TD WIDTH=60% ALIGN=RIGHT>";
   echo "         <FONT FACE=\"Arial,Helvetica\">";
   echo _("         Wrap incoming text at:");
   echo "         </FONT>";
   echo "      </TD>";
   echo "      <TD WIDTH=40% ALIGN=LEFT>";
   echo "         <FONT FACE=\"Arial,Helvetica\">";
   if (isset($wrap_at))
      echo "         <TT><INPUT TYPE=TEXT SIZE=5 NAME=wrapat VALUE=\"$wrap_at\"></TT><BR>";
   else
      echo "         <TT><INPUT TYPE=TEXT SIZE=5 NAME=wrapat VALUE=\"86\"></TT><BR>";
   echo "         </FONT>";
   echo "      </TD>";
   echo "   </TR>";

   // EDITOR_SIZE
   echo "   <TR>";
   echo "      <TD WIDTH=60% ALIGN=RIGHT>";
   echo "         <FONT FACE=\"Arial,Helvetica\">";
   echo _("         Size of editor window (in characters):");
   echo "         </FONT>";
   echo "      </TD>";
   echo "      <TD WIDTH=40% ALIGN=LEFT>";
   echo "         <FONT FACE=\"Arial,Helvetica\">";
   if ($editor_size >= 5)
      echo "         <TT><INPUT TYPE=TEXT SIZE=5 NAME=editorsize VALUE=\"$editor_size\"></TT><BR>";
   else
      echo "         <TT><INPUT TYPE=TEXT SIZE=5 NAME=editorsize VALUE=\"76\"></TT><BR>";
   echo "         </FONT>";
   echo "      </TD>";
   echo "   </TR>";

   // LEFT_REFRESH
   echo "   <TR>";
   echo "      <TD WIDTH=60% ALIGN=RIGHT>";
   echo "         <FONT FACE=\"Arial,Helvetica\">";
   echo _("         Time between auto refresh of folder list:");
   echo "         </FONT>";
   echo "      </TD>";
   echo "      <TD WIDTH=40% ALIGN=LEFT>";
   echo "         <FONT FACE=\"Arial,Helvetica\">";
   echo "               <SELECT name=leftrefresh>";
   if (($left_refresh == "None") || ($left_refresh == "")) 
      echo "                  <OPTION VALUE=None SELECTED>None";
   else   
      echo "                  <OPTION VALUE=None>None";
   
   if (($left_refresh == "10")) 
      echo "                  <OPTION VALUE=10 SELECTED>10 Seconds";
   else   
      echo "                  <OPTION VALUE=10>10 Seconds";
   
   if (($left_refresh == "20")) 
      echo "                  <OPTION VALUE=20 SELECTED>20 Seconds";
   else   
      echo "                  <OPTION VALUE=20>20 Seconds";
   
   if (($left_refresh == "30")) 
      echo "                  <OPTION VALUE=30 SELECTED>30 Seconds";
   else   
      echo "                  <OPTION VALUE=30>30 Seconds";
   
   if (($left_refresh == "60")) 
      echo "                  <OPTION VALUE=60 SELECTED>1 Minute";
   else   
      echo "                  <OPTION VALUE=60>1 Minute";
   
   if (($left_refresh == "120")) 
      echo "                  <OPTION VALUE=120 SELECTED>2 Minutes";
   else   
      echo "                  <OPTION VALUE=120>2 Minutes";
   
   if (($left_refresh == "180")) 
      echo "                  <OPTION VALUE=180 SELECTED>3 Minutes";
   else   
      echo "                  <OPTION VALUE=180>3 Minutes";
   
   if (($left_refresh == "240")) 
      echo "                  <OPTION VALUE=240 SELECTED>4 Minutes";
   else   
      echo "                  <OPTION VALUE=240>4 Minutes";
   
   if (($left_refresh == "300")) 
      echo "                  <OPTION VALUE=300 SELECTED>5 Minutes";
   else   
      echo "                  <OPTION VALUE=300>5 Minutes";
   
   if (($left_refresh == "420")) 
      echo "                  <OPTION VALUE=420 SELECTED>7 Minutes";
   else   
      echo "                  <OPTION VALUE=420>7 Minutes";
   
   if (($left_refresh == "600")) 
      echo "                  <OPTION VALUE=600 SELECTED>10 Minutes";
   else   
      echo "                  <OPTION VALUE=600>10 Minutes";
   
   if (($left_refresh == "720")) 
      echo "                  <OPTION VALUE=720 SELECTED>12 Minutes";
   else   
      echo "                  <OPTION VALUE=720>12 Minutes";
   
   if (($left_refresh == "900")) 
      echo "                  <OPTION VALUE=900 SELECTED>15 Minutes";
   else   
      echo "                  <OPTION VALUE=900>15 Minutes";
   
   if (($left_refresh == "1200")) 
      echo "                  <OPTION VALUE=1200 SELECTED>20 Minutes";
   else   
      echo "                  <OPTION VALUE=1200>20 Minutes";
   
   if (($left_refresh == "1500")) 
      echo "                  <OPTION VALUE=1500 SELECTED>25 Minutes";
   else   
      echo "                  <OPTION VALUE=1500>25 Minutes";
   
   if (($left_refresh == "1800")) 
      echo "                  <OPTION VALUE=1800 SELECTED>30 Minutes";
   else   
      echo "                  <OPTION VALUE=1800>30 Minutes";
   
      echo "               </SELECT>";
      echo "         </FONT>";
      echo "      </TD>";
      echo "   </TR>";
      echo "</TABLE>";

   // SIGNATURE
   echo "<CENTER>";
   if ($use_signature == true)
      echo "<INPUT TYPE=CHECKBOX VALUE=\"1\" NAME=usesignature CHECKED>&nbsp;&nbsp;Use a signature?<BR>";
   else
      echo "<INPUT TYPE=CHECKBOX VALUE=\"1\" NAME=usesignature>&nbsp;&nbsp;";
      echo _("Use a signature?");
      echo "<BR>";

   if ($editor_size < 5)
      $sig_size = 76;
   else
      $sig_size = $editor_size;

   echo "<BR>Signature:<BR><TEXTAREA NAME=signature_edit ROWS=5 COLS=\"$sig_size\">$signature</TEXTAREA><BR>";
   echo "</CENTER>";


   // SUBMIT BUTTON
   echo "<BR><CENTER><INPUT TYPE=SUBMIT VALUE=\"";
   echo _("Submit");
   echo "\"></CENTER>\n";
   echo "</FORM>";

   echo "</BODY></HTML>";
?>
