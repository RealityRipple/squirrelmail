<?
   session_start();

   if (!isset($config_php))
      include("../config/config.php");
   if (!isset($strings_php))
      include("../functions/strings.php");
   if (!isset($page_header_php))
      include("../functions/page_header.php");
   if (!isset($display_messages_php))
      include("../functions/display_messages.php");
   if (!isset($imap_php))
      include("../functions/imap.php");
   if (!isset($array_php))
      include("../functions/array.php");
   if (!isset($i18n_php))
      include("../functions/i18n.php");

   include("../src/load_prefs.php");


   $imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort);
   $boxes = sqimap_mailbox_list($imapConnection, $boxes);
   fputs($imapConnection, "1 logout\n");

   echo "<HTML><BODY TEXT=\"$color[8]\" BGCOLOR=\"$color[4]\" LINK=\"$color[7]\" VLINK=\"$color[7]\" ALINK=\"$color[7]\">\n";
   displayPageHeader($color, "None");

   /** load up some of the values from the pref file **/
   $fullname = getPref($data_dir, $username, "full_name");
   $replyto = getPref($data_dir, $username, "reply_to");
   $email_address  = getPref($data_dir, $username, "email_address");
   $chosen_language = getPref($data_dir, $username, "language");

   echo "<TABLE WIDTH=100% COLS=1 ALIGN=CENTER>\n";
   echo "   <TR><TD BGCOLOR=\"$color[0]\" ALIGN=CENTER>\n";
   echo _("Options");
   echo "   </TD></TR>\n";
   echo "</TABLE>\n";

   echo "<FORM action=\"options_submit.php?PHPSESSID=$PHPSESSID\" METHOD=POST>\n";
   echo "<TABLE WIDTH=100% COLS=2 ALIGN=CENTER>\n";
   // FULL NAME
   echo "   <TR>";
   echo "      <TD WIDTH=20% ALIGN=RIGHT>";
   echo           _("Full Name:");
   echo "      </TD>";
   echo "      <TD WIDTH=80% ALIGN=LEFT>";
   echo "         <INPUT TYPE=TEXT NAME=full_name VALUE=\"$fullname\" SIZE=50><BR>";
   echo "      </TD>";
   echo "   </TR>";
   // FROM-ADDRESS
   echo "   <TR>";
   echo "      <TD WIDTH=20% ALIGN=RIGHT>";
   echo           _("E-mail address:");
   echo "      </TD>";
   echo "      <TD WIDTH=80% ALIGN=LEFT>";
   echo "         <INPUT TYPE=TEXT NAME=email_address VALUE=\"$email_address\" SIZE=50><BR>";
   echo "      </TD>";
   echo "   </TR>";
   // REPLY-TO
   echo "   <TR>";
   echo "      <TD WIDTH=20% ALIGN=RIGHT>";
   echo           _("Reply-to:");
   echo "      </TD>";
   echo "      <TD WIDTH=80% ALIGN=LEFT>";
   echo "         <INPUT TYPE=TEXT NAME=reply_to VALUE=\"$replyto\" SIZE=50><BR>";
   echo "      </TD>";
   echo "   </TR>";
   // DEFAULT FOLDERS 
	if ($show_prefix_option == true) {
      echo "   <TR>";
	   echo "      <TD WIDTH=20% ALIGN=RIGHT>";
	   echo           _("Folder path:");
	   echo "      </TD>";
	   echo "      <TD WIDTH=80% ALIGN=LEFT>";
	   if (isset ($folder_prefix))
	      echo "         <INPUT TYPE=TEXT NAME=folderprefix VALUE=\"$folder_prefix\" SIZE=50><BR>";
	   else   
	      echo "         <INPUT TYPE=TEXT NAME=folderprefix VALUE=\"$default_folder_prefix\" SIZE=50><BR>";
	   echo "      </TD>";
	   echo "   </TR>";
   }   
   // THEME
   echo "   <TR>";
   echo "      <TD WIDTH=20% ALIGN=RIGHT>";
   echo           _("Theme:");
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
   // LANGUAGE
   echo "   <TR>";
   echo "      <TD WIDTH=20% ALIGN=RIGHT>";
   echo           _("Language:");
   echo "      </TD>";
   echo "      <TD WIDTH=80% ALIGN=LEFT>";

   echo "         <TT><SELECT NAME=language>\n";
   reset ($languages);
   while (list($code, $name)=each($languages)) {
      if ($code==$chosen_language)
         echo "         <OPTION SELECTED VALUE=\"".$code."\">".$languages[$code]["NAME"]."\n";
      else
         echo "         <OPTION VALUE=\"".$code."\">".$languages[$code]["NAME"]."\n";

   }
   echo "         </SELECT></TT>";

   echo "      </TD>";
   echo "   </TR>";
   echo "</TABLE>";



   echo "<TABLE WIDTH=100% COLS=2 ALIGN=CENTER>\n";
   // MOVE_TO_TRASH
   echo "   <TR>";
   echo "      <TD WIDTH=60% ALIGN=RIGHT>";
   echo           _("Move deleted messages to ");
   echo "\"$trash_folder\"?";
   echo "      </TD>";
   echo "      <TD WIDTH=40% ALIGN=LEFT>";
   if ($move_to_trash == true)
      echo "         <INPUT TYPE=RADIO NAME=movetotrash VALUE=1 CHECKED>&nbsp;True<BR>";
   else
      echo "         <INPUT TYPE=RADIO NAME=movetotrash VALUE=1>&nbsp;True<BR>";

   if ($move_to_trash == false)
      echo "         <INPUT TYPE=RADIO NAME=movetotrash VALUE=0 CHECKED>&nbsp;False";
   else
      echo "         <INPUT TYPE=RADIO NAME=movetotrash VALUE=0>&nbsp;False";

   echo "      </TD>";
   echo "   </TR>";

   // WRAP_AT
   echo "   <TR>";
   echo "      <TD WIDTH=60% ALIGN=RIGHT>";
   echo           _("Wrap incoming text at:");
   echo "      </TD>";
   echo "      <TD WIDTH=40% ALIGN=LEFT>";
   if (isset($wrap_at))
      echo "         <TT><INPUT TYPE=TEXT SIZE=5 NAME=wrapat VALUE=\"$wrap_at\"></TT><BR>";
   else
      echo "         <TT><INPUT TYPE=TEXT SIZE=5 NAME=wrapat VALUE=\"86\"></TT><BR>";
   echo "      </TD>";
   echo "   </TR>";

   // EDITOR_SIZE
   echo "   <TR>";
   echo "      <TD WIDTH=60% ALIGN=RIGHT>";
   echo           _("Size of editor window (in characters):");
   echo "      </TD>";
   echo "      <TD WIDTH=40% ALIGN=LEFT>";
   if ($editor_size >= 5)
      echo "         <TT><INPUT TYPE=TEXT SIZE=5 NAME=editorsize VALUE=\"$editor_size\"></TT><BR>";
   else
      echo "         <TT><INPUT TYPE=TEXT SIZE=5 NAME=editorsize VALUE=\"76\"></TT><BR>";
   echo "      </TD>";
   echo "   </TR>";

   // LEFT_SIZE
   echo "   <TR>";
   echo "      <td width=60% align=right>";
   echo _("Width of left folder list:");
   echo "      </td>";
   echo "      <td width=60% align=left>\n";
   echo "         <select name=leftsize>\n";
   if ($left_size == 100)
      echo "<option value=100 selected>100 pixels\n";
   else
      echo "<option value=100>100 pixels\n";
   
   if ($left_size == 125)
      echo "<option value=125 selected>125 pixels\n";
   else
      echo "<option value=125>125 pixels\n";
   
   if ($left_size == 150)
      echo "<option value=150 selected>150 pixels\n";
   else
      echo "<option value=150>150 pixels\n";
   
   if ($left_size == 175)
      echo "<option value=175 selected>175 pixels\n";
   else
      echo "<option value=175>175 pixels\n";
      
   if (($left_size == 200) || ($left_size == ""))
      echo "<option value=200 selected>200 pixels\n";
   else
      echo "<option value=200>200 pixels\n";
   
   if (($left_size == 225))
      echo "<option value=225 selected>225 pixels\n";
   else
      echo "<option value=225>225 pixels\n";
   
   if (($left_size == 250))
      echo "<option value=250 selected>250 pixels\n";
   else
      echo "<option value=250>250 pixels\n";
   
   if ($left_size == 275)
      echo "<option value=275 selected>275 pixels\n";
   else
      echo "<option value=275>275 pixels\n";
      
   if (($left_size == 300))
      echo "<option value=300 selected>300 pixels\n";
   else
      echo "<option value=300>300 pixels\n";

   echo "         </select>";
   echo "      </td>";
   echo "   </TR>";
   
   
   // LEFT_REFRESH
   echo "   <TR>";
   echo "      <TD WIDTH=60% ALIGN=RIGHT>";
   echo           _("Time between auto refresh of folder list:");
   echo "      </TD>";
   echo "      <TD WIDTH=40% ALIGN=LEFT>";
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
      echo "      </TD>";
      echo "   </TR>";
      echo "</TABLE>";

   // SIGNATURE
   echo "<CENTER>";
   if ($use_signature == true)
      echo "<INPUT TYPE=CHECKBOX VALUE=\"1\" NAME=usesignature CHECKED>&nbsp;&nbsp;Use a signature?<BR>";
   else {
      echo "<INPUT TYPE=CHECKBOX VALUE=\"1\" NAME=usesignature>&nbsp;&nbsp;";
      echo _("Use a signature?");
      echo "<BR>";
   }

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
