<?
   include("../config/config.php");
   include("../functions/strings.php");
   include("../functions/page_header.php");
   include("../functions/imap.php");
   include("../functions/mailbox.php");

   echo "<HTML><BODY TEXT=\"$color[8]\" BGCOLOR=\"$color[4]\" LINK=\"$color[7]\" VLINK=\"$color[7]\" ALINK=\"$color[7]\">\n";

   displayPageHeader($color, "None");

   echo "<TABLE WIDTH=100% COLS=1 ALIGN=CENTER>\n";
   echo "   <TR><TD BGCOLOR=\"$color[0]\" ALIGN=CENTER>\n";
   echo "      <FONT FACE=\"Arial,Helvetica\">Folders</FONT>\n";
   echo "   </TD></TR>\n";
   echo "</TABLE>\n";

   $imapConnection = loginToImapServer($username, $key, $imapServerAddress);
   getFolderList($imapConnection, $boxesFormatted, $boxesUnformatted, $boxesRaw);

   /** DELETING FOLDERS **/
   echo "<TABLE WIDTH=70% COLS=1 ALIGN=CENTER>\n";
   echo "<TR><TD BGCOLOR=\"$color[0]\" ALIGN=CENTER><FONT FACE=\"Arial,Helvetica\"><B>Delete Folder</B></FONT></TD></TR>";
   echo "<TR><TD BGCOLOR=\"$color[4]\" ALIGN=CENTER>";
   echo "<FORM ACTION=folders_delete.php METHOD=SUBMIT>\n";
   echo "<SELECT NAME=mailbox><FONT FACE=\"Arial,Helvetica\">\n";
   for ($i = 0; $i < count($boxesUnformatted); $i++) {
      $use_folder = true;
      for ($p = 0; $p < count($special_folders); $p++) {
         if ($boxesUnformatted[$i] == $special_folders[$p]) {
            $use_folder = false;
         } else if (substr($boxesUnformatted[$i], 0, strlen($trash_folder)) == $trash_folder) {
            $use_folder = false;
         }
      }

      if ($use_folder == true)
         echo "<OPTION>$boxesUnformatted[$i]\n";
   }
   echo "</SELECT>\n";
   echo "<INPUT TYPE=SUBMIT VALUE=Delete>\n";
   echo "</FORM><BR></TD></TR><BR>\n";

   /** CREATING FOLDERS **/
   echo "<TR><TD BGCOLOR=\"$color[0]\" ALIGN=CENTER><FONT FACE=\"Arial,Helvetica\"><B>Create Folder</B></FONT></TD></TR>";
   echo "<TR><TD BGCOLOR=\"$color[4]\" ALIGN=CENTER>";
   echo "<FORM ACTION=folders_create.php METHOD=POST>\n";
   echo "<INPUT TYPE=TEXT SIZE=25 NAME=folder_name><BR>\n";
   echo "&nbsp;&nbsp;as a subfolder of<BR>";
   echo "<SELECT NAME=subfolder><FONT FACE=\"Arial,Helvetica\">\n";
   if ($default_sub_of_inbox == false)
      echo "<OPTION SELECTED>[ None ]\n";
   else
      echo "<OPTION>[ None ]\n";

   for ($i = 0;$i < count($boxesUnformatted); $i++) {
      if (($boxesUnformatted[$i] == $special_folders[0]) && ($default_sub_of_inbox == true))
         echo "<OPTION SELECTED>$boxesUnformatted[$i]\n";
      else
         echo "<OPTION>$boxesUnformatted[$i]\n";
   }
   echo "</SELECT><BR>\n";
   echo "<INPUT TYPE=SUBMIT VALUE=Create>\n";
   echo "</FORM><BR></TD></TR><BR>\n";

   /** RENAMING FOLDERS **/
   echo "<TR><TD BGCOLOR=\"$color[0]\" ALIGN=CENTER><FONT FACE=\"Arial,Helvetica\"><B>Rename a Folder</B></FONT></TD></TR>";
   echo "<TR><TD BGCOLOR=\"$color[4]\" ALIGN=CENTER>";
   echo "<FORM ACTION=folders_rename_getname.php METHOD=POST>\n";
   echo "<SELECT NAME=old><FONT FACE=\"Arial,Helvetica\">\n";
   for ($i = 0; $i < count($boxesUnformatted); $i++) {
      $use_folder = true;
      for ($p = 0; $p < count($special_folders); $p++) {
         if ($boxesUnformatted[$i] == $special_folders[$p]) {
            $use_folder = false;
         }
      }
      if ($use_folder == true)
         echo "   <OPTION>$boxesUnformatted[$i]\n";
   }
   echo "</SELECT>\n";
   echo "<INPUT TYPE=SUBMIT VALUE=\"Rename\">\n";
   echo "</FORM></TD></TR></TABLE><BR>\n";

?>
</BODY></HTML>
