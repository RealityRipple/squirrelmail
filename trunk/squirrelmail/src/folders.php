<?
   if (!isset($config_php))
      include("../config/config.php");
   if (!isset($strings_php))
      include("../functions/strings.php");
   if (!isset($page_header_php))
      include("../functions/page_header.php");
   if (!isset($imap_php))
      include("../functions/imap.php");
   if (!isset($array_php))
      include("../functions/array.php");

   include("../src/load_prefs.php");

   echo "<HTML><BODY TEXT=\"$color[8]\" BGCOLOR=\"$color[4]\" LINK=\"$color[7]\" VLINK=\"$color[7]\" ALINK=\"$color[7]\">\n";

   displayPageHeader($color, "None");

   echo "<TABLE WIDTH=100% COLS=1 ALIGN=CENTER>\n";
   echo "   <TR><TD BGCOLOR=\"$color[0]\" ALIGN=CENTER>\n";
   echo _("Folders");
   echo "   </TD></TR>\n";
   echo "</TABLE>\n";

   $imapConnection = sqimap_login ($username, $key, $imapServerAddress, $imapPort, 0);
   $boxes = sqimap_mailbox_list($imapConnection);

   /** DELETING FOLDERS **/
   echo "<TABLE WIDTH=70% COLS=1 ALIGN=CENTER>\n";
   echo "<TR><TD BGCOLOR=\"$color[0]\" ALIGN=CENTER><B>";
   echo _("Delete Folder");
   echo "</B></TD></TR>";
   echo "<TR><TD BGCOLOR=\"$color[4]\" ALIGN=CENTER>";
   $count_special_folders = 0;
   for ($i = 0; $i < count($special_folders); $i++) {
      for ($p = 0; $p < count($special_folders); $p++) {
         if ($boxes[$i]["unformatted"] == $special_folders[$p]) {
            $count_special_folders++;
         }
      }   
   }

   if ($count_special_folders < count($boxes)) {
      echo "<FORM ACTION=folders_delete.php METHOD=SUBMIT>\n";
      echo "<TT><SELECT NAME=mailbox>\n";
      for ($i = 0; $i < count($boxes); $i++) {
         $use_folder = true;
         for ($p = 0; $p < count($special_folders); $p++) {
            if ($boxes[$i]["unformatted"] == $special_folders[$p]) {
               $use_folder = false;
            } else if (substr($boxes[$i]["unformatted"], 0, strlen($trash_folder)) == $trash_folder) {
               $use_folder = false;
            }
         }
         if ($use_folder == true) {
            $box = $boxes[$i]["unformatted-dm"];
            $box2 = replace_spaces($boxes[$i]["formatted"]);
            echo "         <OPTION VALUE=\"$box\">$box2\n";
         }
      }
      echo "</SELECT></TT>\n";
      echo "<INPUT TYPE=SUBMIT VALUE=\"";
      echo _("Delete");
      echo "\">\n";
      echo "</FORM><BR></TD></TR>\n";
   } else {
      echo _("No mailboxes found") . "<br><br></td><tr>";
   }


   /** CREATING FOLDERS **/
   echo "<TR><TD BGCOLOR=\"$color[0]\" ALIGN=CENTER><B>";
   echo _("Create Folder");
   echo "</B></TD></TR>";
   echo "<TR><TD BGCOLOR=\"$color[4]\" ALIGN=CENTER>";
   echo "<FORM ACTION=folders_create.php METHOD=POST>\n";
   echo "<INPUT TYPE=TEXT SIZE=25 NAME=folder_name><BR>\n";
   echo _("as a subfolder of");
   echo "<BR>";
   echo "<TT><SELECT NAME=subfolder>\n";
   if ($default_sub_of_inbox == false)
      echo "<OPTION SELECTED>[ None ]\n";
   else
      echo "<OPTION>[ None ]\n";

   for ($i = 0; $i < count($boxes); $i++) {
      if (count($boxes[$i]["flags"]) > 0) {
         for ($j = 0; $j < count($boxes[$i]["flags"]); $j++) {
            if ($boxes[$i]["flags"][$j] != "noinferiors") {
               if (($boxes[$i]["unformatted"] == $special_folders[0]) && ($default_sub_of_inbox == true)) {
                  $box = $boxes[$i]["unformatted"];
                  $box2 = replace_spaces($boxes[$i]["formatted"]);
                  echo "<OPTION SELECTED VALUE=\"$box\">$box2\n";
               } else {
                  $box = $boxes[$i]["unformatted"];
                  $box2 = replace_spaces($boxes[$i]["formatted"]);
                  echo "<OPTION VALUE=\"$box\">$box2\n";
               }
            }   
         }    
      } else {
         if (($boxes[$i]["unformatted"] == $special_folders[0]) && ($default_sub_of_inbox == true)) {
            $box = $boxes[$i]["unformatted"];
            $box2 = replace_spaces($boxes[$i]["formatted"]);
            echo "<OPTION SELECTED VALUE=\"$box\">$box2\n";
         } else {
            $box = $boxes[$i]["unformatted"];
            $box2 = replace_spaces($boxes[$i]["formatted"]);
            echo "<OPTION VALUE=\"$box\">$box2\n";
         }
      }
   }
   echo "</SELECT></TT><BR>\n";
   if ($show_contain_subfolders_option) {
      echo "<INPUT TYPE=CHECKBOX NAME=\"contain_subs\"> &nbsp;";
      echo _("Let this folder contain subfolders");
      echo "<BR>";
   }   
   echo "<INPUT TYPE=SUBMIT VALUE=Create>\n";
   echo "</FORM><BR></TD></TR><BR>\n";

   /** RENAMING FOLDERS **/
   echo "<TR><TD BGCOLOR=\"$color[0]\" ALIGN=CENTER><B>";
   echo _("Rename a Folder");
   echo "</B></TD></TR>";
   echo "<TR><TD BGCOLOR=\"$color[4]\" ALIGN=CENTER>";
   if ($count_special_folders < count($boxes)) {
      echo "<FORM ACTION=folders_rename_getname.php METHOD=POST>\n";
      echo "<TT><SELECT NAME=old>\n";
      for ($i = 0; $i < count($boxes); $i++) {
         $use_folder = true;
         for ($p = 0; $p < count($special_folders); $p++) {
            if ($boxes[$i]["unformatted"] == $special_folders[$p]) {
               $use_folder = false;
            } else if (substr($boxes[$i]["unformatted"], 0, strlen($trash_folder)) == $trash_folder) {
               $use_folder = false;
            }
         }
         if ($use_folder == true) {
            $box = $boxes[$i]["unformatted-dm"];
            $box2 = replace_spaces($boxes[$i]["formatted"]);
            echo "         <OPTION VALUE=\"$box\">$box2\n";
         }
      }
      echo "</SELECT></TT>\n";
      echo "<INPUT TYPE=SUBMIT VALUE=\"";
      echo _("Rename");
      echo "\">\n";
      echo "</FORM></TD></TR></TABLE><BR>\n";
   } else {
      echo _("No mailboxes found") . "<br><br></td></tr></table>";
   }

?>
</BODY></HTML>
