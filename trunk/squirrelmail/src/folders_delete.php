<?
   /*
   *  Incoming values:
   *     $mailbox - selected mailbox from the form
   */
   
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
   if (!isset($tree_php))
      include("../functions/tree.php");

   include("../src/load_prefs.php");

   echo "<HTML>";
   echo "<BODY TEXT=\"$color[8]\" BGCOLOR=\"$color[4]\" LINK=\"$color[7]\" VLINK=\"$color[7]\" ALINK=\"$color[7]\">\n";
   displayPageHeader($color, "None");  

   
   $imap_stream = sqimap_login($username, $key, $imapServerAddress, 0);
   $boxes = sqimap_mailbox_list ($imap_stream);
   $dm = sqimap_get_delimiter($imap_stream);

   /** lets see if we CAN move folders to the trash.. otherwise, just delete them **/
   for ($i = 0; $i < count($boxes); $i++) {
      if ($boxes[$i]["unformatted"] == $trash_folder) {
         $can_move_to_trash = true;
         for ($j = 0; $j < count($boxes[$i]["flags"]); $j++) {
            if (strtolower($boxes[$i]["flags"][$j]) == "noinferiors")
               $can_move_to_trash = false;
         }
      }
   }


   /** First create the top node in the tree **/
   for ($i = 0;$i < count($boxes);$i++) {
      if (($boxes[$i]["unformatted"] == $mailbox) && (strlen($boxes[$i]["unformatted"]) == strlen($mailbox))) {
         $foldersTree[0]["value"] = $mailbox;
         $foldersTree[0]["doIHaveChildren"] = false;
         continue;
      }
   }
   // Now create the nodes for subfolders of the parent folder 
   // You can tell that it is a subfolder by tacking the mailbox delimiter
   //    on the end of the $mailbox string, and compare to that.
   $j = 0;
   for ($i = 0;$i < count($boxes);$i++) {
      if (substr($boxes[$i]["unformatted"], 0, strlen($mailbox . $dm)) == ($mailbox . $dm)) {
         addChildNodeToTree($boxes[$i]["unformatted"], $foldersTree);
      }
   }

   /** Lets start removing the folders and messages **/
   if (($move_to_trash == true) && ($can_move_to_trash == true)) { /** if they wish to move messages to the trash **/
      walkTreeInPostOrderCreatingFoldersUnderTrash(0, $imap_stream, $foldersTree, $dm, $mailbox);
      walkTreeInPreOrderDeleteFolders(0, $imap_stream, $foldersTree);
   } else { /** if they do NOT wish to move messages to the trash (or cannot)**/
      walkTreeInPreOrderDeleteFolders(0, $imap_stream, $foldersTree);
   }

   /** Log out this session **/
   sqimap_logout($imap_stream);

   echo "<FONT FACE=\"Arial,Helvetica\">";
   echo "<BR><BR><BR><CENTER><B>";
   echo _("Folder Deleted!");
   echo "</B><BR><BR>";
   echo _("The folder has been successfully deleted.");
   echo "<BR><A HREF=\"webmail.php?right_frame=folders.php\" TARGET=_top>";
   echo _("Click here");
   echo "</A> ";
   echo _("to continue.");
   echo "</CENTER></FONT>"; 
   
   echo "</BODY></HTML>";
?>
