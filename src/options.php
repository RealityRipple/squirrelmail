<?
   include("../config/config.php");
   include("../functions/mailbox.php");
   include("../functions/strings.php");
   include("../functions/page_header.php");
   include("../functions/display_messages.php");
   include("../functions/imap.php");
   include("../functions/array.php");

   include("../src/load_prefs.php");


   $imapConnection = loginToImapServer($username, $key, $imapServerAddress);
   getFolderList($imapConnection, $boxes);
   fputs($imapConnection, "1 logout\n");

   echo "<HTML><BODY TEXT=\"$color[8]\" BGCOLOR=\"$color[4]\" LINK=\"$color[7]\" VLINK=\"$color[7]\" ALINK=\"$color[7]\">\n";
   displayPageHeader($color, "None");

   /** load up some of the values from the pref file **/
   $fullname = getPref($data_dir, $username, "full_name");
   $replyto  = getPref($data_dir, $username, "reply_to");

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
   echo "         <INPUT TYPE=TEXT NAME=full_name VALUE=\"$fullname\" SIZE=50><BR>";
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
   echo "         <INPUT TYPE=TEXT NAME=reply_to VALUE=\"$replyto\" SIZE=50><BR>";
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
   echo "</TABLE>";



   echo "<TABLE WIDTH=100% COLS=2 ALIGN=CENTER>\n";
   // MOVE_TO_TRASH
   echo "   <TR>";
   echo "      <TD WIDTH=60% ALIGN=RIGHT>";
   echo "         <FONT FACE=\"Arial,Helvetica\">";
   echo "         Move deleted messages to \"$trash_folder\"?";
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
   echo "         Wrap incoming text at:";
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
   echo "         Size of editor window (in characters):";
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
   echo "</TABLE>";

   // SIGNATURE
   echo "<CENTER>";
   if ($use_signature == true)
      echo "<INPUT TYPE=CHECKBOX VALUE=\"1\" NAME=usesignature CHECKED>&nbsp;&nbsp;Use a signature?<BR>";
   else
      echo "<INPUT TYPE=CHECKBOX VALUE=\"1\" NAME=usesignature>&nbsp;&nbsp;Use a signature?<BR>";

   if ($editor_size < 5)
      $sig_size = 76;
   else
      $sig_size = $editor_size;

   echo "<BR>Signature:<BR><TEXTAREA NAME=signature_edit ROWS=5 COLS=\"$sig_size\" WRAP=HARD>$signature</TEXTAREA><BR>";
   echo "</CENTER>";


   // SUBMIT BUTTON
   echo "<BR><CENTER><INPUT TYPE=SUBMIT VALUE=\"Submit\"></CENTER>\n";
   echo "</FORM>";

   echo "</BODY></HTML>";
?>