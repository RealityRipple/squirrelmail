<?php
   /**
    **  folders.php
    **
    **  Copyright (c) 1999-2000 The SquirrelMail development team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    **  Handles all interaction between the user and the other folder
    **  scripts which do most of the work. Also handles the Special
    **  Folders.
    **/

   session_start();

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

   displayPageHeader($color, "None");

   echo "<TABLE WIDTH=100% COLS=1 ALIGN=CENTER>\n";
   echo "   <TR><TD BGCOLOR=\"$color[0]\" ALIGN=CENTER>\n";
   echo _("Folders");
   echo "   </TD></TR>\n";
   echo "</TABLE>\n";

   $imapConnection = sqimap_login ($username, $key, $imapServerAddress, $imapPort, 0);
   $boxes = sqimap_mailbox_list($imapConnection);

  /** Cyrus Folder Options**/
//Creates the Sent and Trash folder
   if (($sent_create == "true") || ($trash_create == "true")) {
      if ($sent_create == "true") {
         sqimap_mailbox_create ($imapConnection, $sent_folder, "");  
      }
   if ($trash_create == "true") {
      sqimap_mailbox_create ($imapConnection, $trash_folder, "");
   }
   echo "<BR><BR><CENTER><b>";
   echo _("Mailboxes Created Successfully!");
   echo "<BR><A HREF=\"webmail.php?right_frame=folders.php\" TARGET=_top>";
   echo _("Click here");
   echo "</A> ";
   echo _("to continue.");
   echo "</CENTER>";
   echo "</BODY></HTML>";
	
   exit;
   }

//display form option for creating Sent and Trash folder
    if ($imap_server_type == "cyrus") {
       if ((!sqimap_mailbox_exists ($imapConnection, $sent_folder)) || (!sqimap_mailbox_exists ($imapConnection, $trash_folder))) {
       echo "<TABLE WIDTH=70% COLS=1 ALIGN=CENTER>\n";
       echo "<TR><TD BGCOLOR=\"$color[0]\" ALIGN=CENTER><B>";
       echo _("Special Folder Options");
       echo "</B></TD></TR>";
       echo "<TR><TD BGCOLOR=\"$color[4]\" ALIGN=CENTER>";
       echo _("In order for SquirrelMail to provide the full set of options you need to create the special folders listed below.  Just click the check box and hit the create button.");
       echo "<FORM ACTION=\"folders.php\" METHOD=\"POST\">\n";
           if (!sqimap_mailbox_exists ($imapConnection, $sent_folder)) {
              echo _("Create Sent") . "<INPUT TYPE=checkbox NAME=sent_create value=true><br>\n";
           }
           if (!sqimap_mailbox_exists ($imapConnection, $trash_folder)){
           echo _("Create Trash") . "<INPUT TYPE=checkbox NAME=trash_create value=true><br>\n";
           echo "<INPUT TYPE=submit VALUE=Create>";
           echo "</FORM></TD></TR>\n";
           }
       }
   }

   /** DELETING FOLDERS **/
   echo "<TABLE WIDTH=70% COLS=1 ALIGN=CENTER>\n";
   echo "<TR><TD BGCOLOR=\"$color[0]\" ALIGN=CENTER><B>";
   echo _("Delete Folder");
   echo "</B></TD></TR>";
   echo "<TR><TD BGCOLOR=\"$color[4]\" ALIGN=CENTER>";

   $count_special_folders = 0;
	$num_max = 1;
	if ($move_to_trash)
		$num_max++;
	if ($move_to_sent)
		$num_max++;

   for ($p = 0; $p < count($boxes) && $count_special_folders < $num_max; $p++) {
      if (strtolower($boxes[$p]["unformatted"]) == "inbox")
         $count_special_folders++;
      else if ($boxes[$p]["unformatted"] == $trash_folder && $trash_folder)
         $count_special_folders++;
      else if ($boxes[$p]["unformatted"] == $sent_folder && $sent_folder)
         $count_special_folders++;
   }   

   if ($count_special_folders < count($boxes)) {
      echo "<FORM ACTION=\"folders_delete.php\" METHOD=\"POST\">\n";
      echo "<TT><SELECT NAME=mailbox>\n";
      for ($i = 0; $i < count($boxes); $i++) {
         $use_folder = true;
			if ((strtolower($boxes[$i]["unformatted"]) != "inbox") && 
			    ($boxes[$i]["unformatted"] != $trash_folder) && 
			    ($boxes[$i]["unformatted"] != $sent_folder)) 
			{	
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
   echo "<FORM ACTION=\"folders_create.php\" METHOD=\"POST\">\n";
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
               if ((strtolower($boxes[$i]["unformatted"]) == "inbox") && ($default_sub_of_inbox == true)) {
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
         if ((strtolower($boxes[$i]["unformatted"]) == "inbox") && ($default_sub_of_inbox == true)) {
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
      echo "<FORM ACTION=\"folders_rename_getname.php\" METHOD=\"POST\">\n";
      echo "<TT><SELECT NAME=old>\n";
      for ($i = 0; $i < count($boxes); $i++) {
         $use_folder = true;

			if ((strtolower($boxes[$i]["unformatted"]) != "inbox") && 
			    ($boxes[$i]["unformatted"] != $trash_folder)  &&
			    ($boxes[$i]["unformatted"] != $sent_folder)) 
			{	
            $box = $boxes[$i]["unformatted-dm"];
            $box2 = replace_spaces($boxes[$i]["formatted"]);
            echo "         <OPTION VALUE=\"$box\">$box2\n";
         }
      }
      echo "</SELECT></TT>\n";
      echo "<INPUT TYPE=SUBMIT VALUE=\"";
      echo _("Rename");
      echo "\">\n";
      echo "</FORM></TD></TR>\n";
   } else {
      echo _("No mailboxes found") . "<br><br></td></tr>";
   }
   $boxes_sub = $boxes;
   
   /** UNSUBSCRIBE FOLDERS **/
   echo "<TR><TD BGCOLOR=\"$color[0]\" ALIGN=CENTER><B>";
   echo _("Unsubscribe/Subscribe");
   echo "</B></TD></TR>";
   echo "<TR><TD BGCOLOR=\"$color[4]\" ALIGN=CENTER>";
   if ($count_special_folders < count($boxes)) {
      echo "<FORM ACTION=\"folders_subscribe.php?method=unsub\" METHOD=\"POST\">\n";
      echo "<TT><SELECT NAME=mailbox>\n";
      for ($i = 0; $i < count($boxes); $i++) {
         $use_folder = true;
			if ((strtolower($boxes[$i]["unformatted"]) != "inbox") &&
			    ($boxes[$i]["unformatted"] != $trash_folder) &&
			    ($boxes[$i]["unformatted"] != $sent_folder)) 
			{	
            $box = $boxes[$i]["unformatted-dm"];
            $box2 = replace_spaces($boxes[$i]["formatted"]);
            echo "         <OPTION VALUE=\"$box\">$box2\n";
         }
      }
      echo "</SELECT></TT>\n";
      echo "<INPUT TYPE=SUBMIT VALUE=\"";
      echo _("Unsubscribe");
      echo "\">\n";
      echo "</FORM></TD></TR>\n";
   } else {
      echo _("No mailboxes found") . "<br><br></td></tr>";
   }
   $boxes_sub = $boxes;
   
   /** SUBSCRIBE TO FOLDERS **/

   echo "<TR><TD BGCOLOR=\"$color[4]\" ALIGN=CENTER>";
   if ($count_special_folders <= count($boxes)) {
      $imap_stream = sqimap_login ($username, $key, $imapServerAddress, $imapPort, 1);
      $boxes_all = sqimap_mailbox_list_all ($imap_stream);
      
      echo "<FORM ACTION=\"folders_subscribe.php?method=sub\" METHOD=\"POST\">\n";
      echo "<tt><select name=mailbox>";
      for ($i = 0; $i < count($boxes_all); $i++) {
         $use_folder = true;
			for ($p = 0; $p < count ($boxes); $p++) {
				if ($boxes_all[$i]["unformatted"] == $boxes[$p]["unformatted"]) {
					$use_folder = false;
					continue;
				} else if ($boxes_all[$i]["unformatted-dm"] == $folder_prefix) {
					$use_folder = false;
				}
			}
			if ($use_folder == true) {	
            $box = $boxes_all[$i]["unformatted-dm"];
            $box2 = replace_spaces($boxes_all[$i]["formatted"]);
            echo "         <OPTION VALUE=\"$box\">$box2\n";
         }
      }
      echo "</select></tt>";
      echo "<INPUT TYPE=SUBMIT VALUE=\"";
      echo _("Subscribe");
      echo "\">\n";
      echo "</FORM></TD></TR></TABLE><BR>\n";
   } else {
      echo _("No mailboxes found") . "<br><br></td></tr></table>";
   }

?>
</BODY></HTML>
