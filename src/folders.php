<HTML><BODY TEXT="#000000" BGCOLOR="#FFFFFF" LINK="#0000EE" VLINK="#0000EE" ALINK="#0000EE">
<?
   include("../config/config.php");
   include("../functions/strings.php");
   include("../functions/page_header.php");
   include("../functions/imap.php");

   displayPageHeader("None");

   echo "<TABLE WIDTH=100% COLS=1 ALIGN=CENTER>\n";
   echo "   <TR><TD BGCOLOR=DCDCDC ALIGN=CENTER>\n";
   echo "      <FONT FACE=\"Arial,Helvetica\">Folders</FONT>\n";
   echo "   </TD></TR>\n";
   echo "</TABLE>\n";

   $imapConnection = loginToImapServer($username, $key, $imapServerAddress);

   fputs($imapConnection, "1 list \"\" *\n");
   $str = imapReadData($imapConnection);

   for ($i = 0;$i < count($str); $i++) {
      $mailbox = Chop($str[$i]);
      // find the quote at the begining of the mailbox name.
      //    i subtract 1 from the strlen so it doesn't find the quote at the end of the mailbox name.
      $mailbox = findMailboxName($mailbox);
      $periodCount = countCharInString($mailbox, ".");

      // indent the correct number of spaces.
      for ($j = 0;$j < $periodCount;$j++)
         $boxes[$i] = "$boxes[$i]&nbsp;&nbsp;&nbsp;";

      $boxes[$i] = $boxes[$i] . readShortMailboxName($mailbox, ".");
      $long_name_boxes[$i] = $mailbox;
   }

   /** DELETING FOLDERS **/
   echo "<FORM ACTION=folders_delete.php METHOD=POST>\n";
   echo "<SELECT NAME=folder_list><FONT FACE=\"Arial,Helvetica\">\n";
   for ($i = 0; $i < count($str); $i++) {
      $use_folder = true;
      for ($p = 0; $p < count($special_folders); $p++) {
         if ($special_folders[$p] == $long_name_boxes[$i])
            $use_folder = false;
      }
      if ($use_folder == true)
         echo "   <OPTION>$boxes[$i]\n";
   }
   echo "</SELECT>\n";
   echo "<INPUT TYPE=SUBMIT VALUE=Delete>\n";
   echo "</FORM><BR>\n";

   /** CREATING FOLDERS **/
   echo "<FORM ACTION=folders_create.php METHOD=POST>\n";
   echo "<INPUT TYPE=TEXT SIZE=25 NAME=folder_name>\n";
   echo "&nbsp;&nbsp;as a subfolder of&nbsp;&nbsp;";
   echo "<SELECT NAME=subfolder><FONT FACE=\"Arial,Helvetica\">\n";
   for ($i = 0;$i < count($str); $i++) {
      $thisbox = Chop($str[$i]);
      $thisbox = findMailboxName($thisbox);
      $thisbox = getBoxForCreate($thisbox);
      echo "<OPTION>$thisbox\n";
   }
   echo "</SELECT>\n";
   echo "<INPUT TYPE=SUBMIT VALUE=Create>\n";
   echo "</FORM><BR>\n";

   /** RENAMING FOLDERS **/
   echo "<FORM ACTION=folders_rename.php METHOD=POST>\n";
   echo "<SELECT NAME=folder_list><FONT FACE=\"Arial,Helvetica\">\n";
   for ($i = 0; $i < count($str); $i++) {
      $use_folder = true;
      for ($p = 0; $p < count($special_folders); $p++) {
         if ($special_folders[$p] == $long_name_boxes[$i])
            $use_folder = false;
      }
      if ($use_folder == true)
         echo "   <OPTION>$boxes[$i]\n";
   }
   echo "</SELECT>\n";
   echo "<INPUT TYPE=TEXT SIZE=25 NAME=new_folder_name>\n";
   echo "<INPUT TYPE=SUBMIT VALUE=Rename>\n";
   echo "</FORM><BR>\n";

?>
</BODY></HTML>
