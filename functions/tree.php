<?
   $tree_php = true;

   if (!isset($imap_php))
      include("../functions/imap.php");

   function findParentForChild($value, $treeIndexToStart, $tree) {
      if ((isset($tree[$treeIndexToStart])) && (strstr($value, $tree[$treeIndexToStart]["value"]))) {
         if ($tree[$treeIndexToStart]["doIHaveChildren"]) {
            for ($i=0;$i< count($tree[$treeIndexToStart]["subNodes"]);$i++) {
               $result = findParentForChild($value, $tree[$treeIndexToStart]["subNodes"][$i]);
               if ($result > -1)
                  return $result;
            }
            return $treeIndexToStart;
         } else
            return $treeIndexToStart;
      } else {
         return -1;
      }
   }  

   function addChildNodeToTree($value, &$tree) {
      $parentNode = findParentForChild($value, 0, $tree);

      // create a new subNode
      $newNodeIndex = count($tree) + 1;
      $tree[$newNodeIndex]["value"] = $value;
      $tree[$newNodeIndex]["doIHaveChildren"] = false;

      if ($tree[$parentNode]["doIHaveChildren"] == false) {
         // make sure the parent knows it has children
         $tree[$parentNode]["subNodes"][0] = $newNodeIndex;
         $tree[$parentNode]["doIHaveChildren"] = true;
      } else {
         $nextSubNode = count($tree[$parentNode]["subNodes"]);
         // make sure the parent knows it has children
         $tree[$parentNode]["subNodes"][$nextSubNode] = $newNodeIndex;
      }
   }

   function walkTreeInPreOrderDeleteFolders($index, $imap_stream, $tree) {
      if ($tree[$index]["doIHaveChildren"]) {
         for ($j = 0; $j < count($tree[$index]["subNodes"]); $j++) {
            walkTreeInPreOrderDeleteFolders($tree[$index]["subNodes"][$j], $imap_stream, $tree);
         }
         sqimap_mailbox_delete($imap_stream, $tree[$index]["value"]);
      } else {
         sqimap_mailbox_delete($imap_stream, $tree[$index]["value"]);
      }
   }

   function walkTreeInPostOrderCreatingFoldersUnderTrash($index, $imap_stream, $tree, $dm, $topFolderName) {
      global $trash_folder;

      $position = strrpos($topFolderName, $dm) + 1;
      $subFolderName = substr($tree[$index]["value"], $position);

      if ($tree[$index]["doIHaveChildren"]) {
         sqimap_mailbox_create($imap_stream, $trash_folder . $dm . $subFolderName, "");
         sqimap_mailbox_select($imap_stream, $tree[$index]["value"]);
        
         $messageCount = sqimap_get_num_messages($imap_stream, $tree[$index]["value"]);
         if ($messageCount > 0)
            sqimap_messages_copy($imap_stream, 1, $messageCount, $trash_folder . $dm . $subFolderName);
         
         for ($j = 0;$j < count($tree[$index]["subNodes"]); $j++)
            walkTreeInPostOrderCreatingFoldersUnderTrash($tree[$index]["subNodes"][$j], $imap_stream, $tree, $dm, $topFolderName);
      } else {
         sqimap_mailbox_create($imap_stream, $trash_folder . $dm . $subFolderName, "");
         sqimap_mailbox_select($imap_stream, $tree[$index]["value"]);
         
         $messageCount = sqimap_get_num_messages($imap_stream, $tree[$index]["value"]);
         if ($messageCount > 0)
            sqimap_messages_copy($imap_stream, 1, $messageCount, $trash_folder . $dm . $subFolderName);
      }
   }
?>
