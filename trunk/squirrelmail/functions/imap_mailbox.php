<?
   /**
    **  imap_mailbox.php
    **
    **  This impliments all functions that manipulate mailboxes
    **/

   /******************************************************************************
    **  Expunges a mailbox 
    ******************************************************************************/
   function sqimap_mailbox_expunge ($imap_stream, $mailbox) {
      sqimap_mailbox_select ($imap_stream, $mailbox);
      fputs ($imap_stream, "a001 EXPUNGE\n");
      $read = sqimap_read_data($imap_stream, "a001", true, $response, $message);
   }


   /******************************************************************************
    **  Checks whether or not the specified mailbox exists 
    ******************************************************************************/
   function sqimap_mailbox_exists ($imap_stream, $mailbox) {
      $boxes = sqimap_mailbox_list ($imap_stream);
      $found = false;
      for ($i = 0; $i < count ($boxes); $i++) {
         if ($boxes[$i]["unformatted"] == $mailbox)
            $found = true;
      }
      return $found;
   }


   
   /******************************************************************************
    **  Selects a mailbox
    ******************************************************************************/
   function sqimap_mailbox_select ($imap_stream, $mailbox) {
      fputs ($imap_stream, "a001 SELECT \"$mailbox\"\n");
      $read = sqimap_read_data($imap_stream, "a001", true, $response, $message);
   }

   

   /******************************************************************************
    **  Creates a folder 
    ******************************************************************************/
   function sqimap_mailbox_create ($imap_stream, $mailbox, $type) {
      if (strtolower($type) == "noselect") {
         $dm = sqimap_get_delimiter($imap_stream);
         $mailbox = $mailbox.$dm;
      }
      fputs ($imap_stream, "a001 CREATE \"$mailbox\"\n");
      $read_ary = sqimap_read_data($imap_stream, "a001", true, $response, $message);

      sqimap_subscribe ($imap_stream, $mailbox);
   }



   /******************************************************************************
    **  Subscribes to an existing folder 
    ******************************************************************************/
   function sqimap_subscribe ($imap_stream, $mailbox) {
      fputs ($imap_stream, "a001 SUBSCRIBE \"$mailbox\"\n");
      $read_ary = sqimap_read_data($imap_stream, "a001", true, $response, $message);
   }




   /******************************************************************************
    **  Unsubscribes to an existing folder 
    ******************************************************************************/
   function sqimap_unsubscribe ($imap_stream, $mailbox) {
      fputs ($imap_stream, "a001 UNSUBSCRIBE \"$mailbox\"\n");
      $read_ary = sqimap_read_data($imap_stream, "a001", true, $response, $message);
   }



   
   /******************************************************************************
    **  This is a recursive function that checks to see if the folder has any 
    **  subfolders, and if so it calls itself on the subfolders first, then 
    **  removes the parent folder.
    ******************************************************************************/
   function sqimap_mailbox_delete ($imap_stream, $mailbox) {
      global $boxes;

      $dm = sqimap_get_delimiter($imap_stream);
      for ($i = 0; $i < count($boxes); $i++) {
         if (strstr($boxes[$i]["unformatted"], $mailbox . $dm)) {
            $new_delete = $boxes[$i]["unformatted"];
            $boxes = removeElement($boxes, $i);
//            sqimap_mailbox_delete ($imap_stream, $new_delete);
         }
      }
      sqimap_unsubscribe ($imap_stream, $mailbox);
      fputs ($imap_stream, "a001 DELETE \"$mailbox\"\n");
      $read_ary = sqimap_read_data($imap_stream, "a001", true, $response, $message);
   }



   /******************************************************************************
    **  Returns sorted mailbox lists in several different ways.
    **  The array returned looks like this:
    ******************************************************************************/
   function sqimap_mailbox_list ($imap_stream) {
      global $special_folders, $list_special_folders_first;
      
      if (!function_exists ("ary_sort"))
         include ("../functions/array.php");
      
      $dm = sqimap_get_delimiter ($imap_stream);

      fputs ($imap_stream, "a001 LIST \"\" INBOX\n");
      $read_ary = sqimap_read_data ($imap_stream, "a001", true, $response, $message);
      $g = 0;
      $phase = "inbox"; 
      for ($i = 0; $i < count($read_ary); $i++) {
         if (substr ($read_ary[$i], 0, 4) != "a001") {
            $boxes[$g]["raw"] = $read_ary[$i];

            $mailbox = find_mailbox_name($read_ary[$i]);
            $dm_count = countCharInString($mailbox, $dm);
            if (substr($mailbox, -1) == $dm)
               $dm_count--;
               
            for ($j = 0; $j < $dm_count; $j++)
               $boxes[$g]["formatted"] = $boxes[$g]["formatted"] . "  ";
            $boxes[$g]["formatted"] .= readShortMailboxName($mailbox, $dm);
               
            if (substr($mailbox, -1) == $dm)
               $mailbox = substr($mailbox, 0, strlen($mailbox) - 1);
            $boxes[$g]["unformatted"] = $mailbox;
            $boxes[$g]["id"] = $g;

            /** Now lets get the flags for this mailbox **/
            fputs ($imap_stream, "a002 LIST \"\" \"$mailbox\"\n"); 
            $read_mlbx = sqimap_read_data ($imap_stream, "a002", true, $response, $message);

            $flags = substr($read_mlbx[0], strpos($read_mlbx[0], "(")+1);
            $flags = substr($flags, 0, strpos($flags, ")"));
            $flags = str_replace("\\", "", $flags);
            $flags = trim(strtolower($flags));
            if ($flags) {
               $boxes[$g]["flags"] = explode(" ", $flags);
            }
         }
         $g++;

         if (!$read_ary[$i+1]) {
            if ($phase == "inbox") {
               fputs ($imap_stream, "a001 LSUB \"\" *\n");
               $read_ary = sqimap_read_data ($imap_stream, "a001", true, $response, $message);
               $phase = "lsub";
               $i--;
            }
         }
      }

      $original = $boxes;

      /** Get the folders into lower case so sorting is not case sensative */
      for ($i = 0; $i < count($original); $i++) {
         $boxes[$i]["unformatted"] = strtolower($boxes[$i]["unformatted"]);
      }

      /** Sort them **/
      $boxes = ary_sort($boxes, "unformatted", 1);

      /** Get them back from the original array, still sorted by the id **/
      for ($i = 0; $i < count($boxes); $i++) {
         for ($j = 0; $j < count($original); $j++) {
            if ($boxes[$i]["id"] == $original[$j]["id"]) {
               $boxes[$i] = $original[$j];
            }
         }
      }     
 
      
      for ($i = 0; $i < count($boxes); $i++) {
         if ($boxes[$i]["unformatted"] == $special_folders[0]) {
            $boxesnew[0] = $boxes[$i];
            $boxes[$i]["used"] = true;
         }
      }
      
      if ($list_special_folders_first == true) {
         for ($i = 0; $i < count($boxes); $i++) {
            for ($j = 1; $j < count($special_folders); $j++) {
               if (substr($boxes[$i]["unformatted"], 0, strlen($special_folders[$j])) == $special_folders[$j]) {
                  $pos = count($boxesnew);
                  $boxesnew[$pos] = $boxes[$i];
                  $boxes[$i]["used"] = true;
               }
            }
         }
      }
      
      for ($i = 0; $i < count($boxes); $i++) {
         if (($boxes[$i]["unformatted"] != $special_folders[0]) &&
             ($boxes[$i]["used"] == false))  {
            $pos = count($boxesnew);
            $boxesnew[$pos] = $boxes[$i];
            $boxes[$i]["used"] = true;
         }
      }

      return $boxesnew;
   }
   
?>
