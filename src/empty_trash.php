<?
   include("../config/config.php");
   include("../functions/strings.php");
   include("../functions/page_header.php");
   include("../functions/display_messages.php");
   include("../functions/imap.php");
   include("../functions/array.php");

   if (!isset($tree_php))
      include("../functions/tree.php");

   include("../src/load_prefs.php");

   $imap_stream = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);

   sqimap_mailbox_list($imap_stream, $boxes);

   $mailbox = $trash_folder;
   $boxes = sqimap_mailbox_list($imap_stream);
   $dm = sqimap_get_delimiter($imap_stream);
   
   // According to RFC2060, a DELETE command should NOT remove inferiors (sub folders)
   //    so lets go through the list of subfolders and remove them before removing the
   //    parent.

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
         addChildNodeToTree($boxes[$i]["unformatted"], $boxes[$i]["unformatted-dm"], $foldersTree);
      }
   }
   
   // now lets go through the tree and delete the folders
   walkTreeInPreOrderEmptyTrash(0, $imap_stream, $foldersTree);

   sqimap_mailbox_select($imap_stream, $trash_folder, $numMessages);
   echo "<HTML><BODY TEXT=\"$color[8]\" BGCOLOR=\"$color[4]\" LINK=\"$color[7]\" VLINK=\"$color[7]\" ALINK=\"$color[7]\">\n";
   displayPageHeader($color, $mailbox);
   messages_deleted_message($trash_folder, $sort, $startMessage, $color);
   sqimap_logout($imap_stream);
?>
