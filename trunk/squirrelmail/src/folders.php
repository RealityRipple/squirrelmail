<HTML><BODY TEXT="#000000" BGCOLOR="#FFFFFF" LINK="#0000EE" VLINK="#0000EE" ALINK="#0000EE">
<?
   include("../config/config.php");
   include("../functions/strings.php");
   include("../functions/page_header.php");
   include("../functions/imap.php");
   include("../functions/mailbox.php");

   displayPageHeader("None");

   echo "<TABLE WIDTH=100% COLS=1 ALIGN=CENTER>\n";
   echo "   <TR><TD BGCOLOR=DCDCDC ALIGN=CENTER>\n";
   echo "      <FONT FACE=\"Arial,Helvetica\">Folders</FONT>\n";
   echo "   </TD></TR>\n";
   echo "</TABLE>\n";

   $imapConnection = loginToImapServer($username, $key, $imapServerAddress);
   getFolderList($imapConnection, $boxesFormatted, $boxesUnformatted);

   /** DELETING FOLDERS **/
   echo "<FORM ACTION=folders_delete.php METHOD=POST>\n";
   echo "<SELECT NAME=mailbox><FONT FACE=\"Arial,Helvetica\">\n";
   for ($i = 0; $i < count($boxesUnformatted); $i++) {
      $use_folder = true;
      for ($p = 0; $p < count($special_folders); $p++) {
         if ($special_folders[$p] == $boxesUnformatted[$i])
            $use_folder = false;
      }
      if ($use_folder == true)
         echo "   <OPTION>$boxesUnformatted[$i]\n";
   }
   echo "</SELECT>\n";
   echo "<INPUT TYPE=SUBMIT VALUE=Delete>\n";
   echo "</FORM><BR>\n";

   /** CREATING FOLDERS **/
   echo "<FORM ACTION=folders_create.php METHOD=POST>\n";
   echo "<INPUT TYPE=TEXT SIZE=25 NAME=folder_name>\n";
   echo "&nbsp;&nbsp;as a subfolder of&nbsp;&nbsp;";
   echo "<SELECT NAME=subfolder><FONT FACE=\"Arial,Helvetica\">\n";
   for ($i = 0;$i < count($boxesUnformatted); $i++) {
      echo "<OPTION>$boxesUnformatted[$i]\n";
   }
   echo "</SELECT>\n";
   echo "<INPUT TYPE=SUBMIT VALUE=Create>\n";
   echo "</FORM><BR>\n";

   /** RENAMING FOLDERS **/
   echo "<FORM ACTION=folders_rename.php METHOD=POST>\n";
   echo "<SELECT NAME=folder_list><FONT FACE=\"Arial,Helvetica\">\n";
   for ($i = 0; $i < count($boxesUnformatted); $i++) {
      $use_folder = true;
      for ($p = 0; $p < count($special_folders); $p++) {
         if ($special_folders[$p] == $long_name_boxes[$i])
            $use_folder = false;
      }
      if ($use_folder == true)
         echo "   <OPTION>$boxesUnformatted[$i]\n";
   }
   echo "</SELECT>\n";
   echo "<INPUT TYPE=TEXT SIZE=25 NAME=new_folder_name>\n";
   echo "<INPUT TYPE=SUBMIT VALUE=Rename>\n";
   echo "</FORM><BR>\n";

?>
</BODY></HTML>
