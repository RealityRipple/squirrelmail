<?php
   /**
    **  folders_delete.php
    **
    **  Copyright (c) 1999-2000 The SquirrelMail development team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    **  Deltes folders from the IMAP server. 
    **  Called from the folders.php
    **
    **  $Id$
    **/

   include('../src/validate.php');
   include("../functions/imap.php");
   include("../functions/array.php");
   include("../functions/tree.php");

   /*
   *  Incoming values:
   *     $mailbox - selected mailbox from the form
   */
   
   $imap_stream = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);
   $boxes = sqimap_mailbox_list ($imap_stream);
   $dm = sqimap_get_delimiter($imap_stream);
   
   if (substr($mailbox, -1) == $dm)
      $mailbox_no_dm = substr($mailbox, 0, strlen($mailbox) - 1); 
   else
      $mailbox_no_dm = $mailbox;

   /** lets see if we CAN move folders to the trash.. otherwise, 
    ** just delete them **/

   // Courier IMAP doesn't like subfolders of Trash
   if (strtolower($imap_server_type) == "courier") {
      $can_move_to_trash = false;
   } 

   // If it's already a subfolder of trash, we'll have to delete it
   else if(eregi("^".$trash_folder.".+", $mailbox)) {

      $can_move_to_trash = false;

   }

   // Otherwise, check if trash folder exits and support sub-folders
   else {
      for ($i = 0; $i < count($boxes); $i++) {
         if ($boxes[$i]["unformatted"] == $trash_folder) {
            $can_move_to_trash = !in_array('noinferiors', $boxes[$i]['flags']);
         }
      }
   }

   /** First create the top node in the tree **/
   for ($i = 0;$i < count($boxes);$i++) {
      if (($boxes[$i]["unformatted-dm"] == $mailbox) && (strlen($boxes[$i]["unformatted-dm"]) == strlen($mailbox))) {
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
      if (substr($boxes[$i]["unformatted"], 0, strlen($mailbox_no_dm . $dm)) == ($mailbox_no_dm . $dm)) {
         addChildNodeToTree($boxes[$i]["unformatted"], $boxes[$i]["unformatted-dm"], $foldersTree);
      }
   }
//   simpleWalkTreePre(0, $foldersTree);

   /** Lets start removing the folders and messages **/
   if (($move_to_trash == true) && ($can_move_to_trash == true)) { /** if they wish to move messages to the trash **/
      walkTreeInPostOrderCreatingFoldersUnderTrash(0, $imap_stream, $foldersTree, $dm, $mailbox);
      walkTreeInPreOrderDeleteFolders(0, $imap_stream, $foldersTree);
   } else { /** if they do NOT wish to move messages to the trash (or cannot)**/
      walkTreeInPreOrderDeleteFolders(0, $imap_stream, $foldersTree);
   }

   /** Log out this session **/
   sqimap_logout($imap_stream);

   $location = get_location();
   header ("Location: $location/folders.php?success=delete");
   /*
   echo "<BR><BR><BR><CENTER><B>";
   echo _("Folder Deleted!");
   echo "</B><BR><BR>";
   echo _("The folder has been successfully deleted.");
   echo "<BR><A HREF=\"webmail.php?right_frame=folders.php\" TARGET=_top>";
   echo _("Click here");
   echo "</A> ";
   echo _("to continue.");
   echo "</CENTER>"; 
   
   echo "</BODY></HTML>";
   */
?>
