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
      fputs ($imap_stream, "a001 EXPUNGE\r\n");
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
      fputs ($imap_stream, "a001 SELECT \"$mailbox\"\r\n");
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
      fputs ($imap_stream, "a001 CREATE \"$mailbox\"\r\n");
      $read_ary = sqimap_read_data($imap_stream, "a001", true, $response, $message);

      sqimap_subscribe ($imap_stream, $mailbox);
   }



   /******************************************************************************
    **  Subscribes to an existing folder 
    ******************************************************************************/
   function sqimap_subscribe ($imap_stream, $mailbox) {
      fputs ($imap_stream, "a001 SUBSCRIBE \"$mailbox\"\r\n");
      $read_ary = sqimap_read_data($imap_stream, "a001", true, $response, $message);
   }




   /******************************************************************************
    **  Unsubscribes to an existing folder 
    ******************************************************************************/
   function sqimap_unsubscribe ($imap_stream, $mailbox) {
      fputs ($imap_stream, "a001 UNSUBSCRIBE \"$mailbox\"\r\n");
      $read_ary = sqimap_read_data($imap_stream, "a001", true, $response, $message);
   }



   
   /******************************************************************************
    **  This function simply deletes the given folder
    ******************************************************************************/
   function sqimap_mailbox_delete ($imap_stream, $mailbox) {
      fputs ($imap_stream, "a001 DELETE \"$mailbox\"\r\n");
      $read_ary = sqimap_read_data($imap_stream, "a001", true, $response, $message);
      sqimap_unsubscribe ($imap_stream, $mailbox);
   }



   /******************************************************************************
    **  Returns sorted mailbox lists in several different ways.
    **  The array returned looks like this:
    ******************************************************************************/
   function sqimap_mailbox_list ($imap_stream) {
      global $load_prefs_php, $prefs_php, $config_php, $data_dir, $username;
      if (!isset($load_prefs_php))
         include "../src/load_prefs.php";
      else
         global $folder_prefix;
      global $special_folders, $list_special_folders_first, $default_folder_prefix;
      
      if (!function_exists ("ary_sort"))
         include ("../functions/array.php");
      
      $dm = sqimap_get_delimiter ($imap_stream);

      fputs ($imap_stream, "a001 LIST \"\" INBOX\r\n");
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
               
            $boxes[$g]["unformatted-dm"] = $mailbox;
            if (substr($mailbox, -1) == $dm)
               $mailbox = substr($mailbox, 0, strlen($mailbox) - 1);
            $boxes[$g]["unformatted"] = $mailbox;
            $boxes[$g]["id"] = $g;

            /** Now lets get the flags for this mailbox **/
            fputs ($imap_stream, "a002 LIST \"\" \"$mailbox\"\r\n"); 
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
               if ($folder_prefix && (substr($folder_prefix, -1) != $dm))
                  $folder_prefix = $folder_prefix . $dm;
                           
               fputs ($imap_stream, "a001 LSUB \"$folder_prefix\" *\r\n");
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

   
   /******************************************************************************
    **  Returns a list of all folders, subscribed or not
    ******************************************************************************/
   function sqimap_mailbox_list_all ($imap_stream) {
      global $special_folders, $list_special_folders_first, $folder_prefix;
      
      if (!function_exists ("ary_sort"))
         include ("../functions/array.php");
      
      $dm = sqimap_get_delimiter ($imap_stream);

      fputs ($imap_stream, "a001 LIST \"$folder_prefix\" *\r\n");
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
               
            $boxes[$g]["unformatted-dm"] = $mailbox;
            if (substr($mailbox, -1) == $dm)
               $mailbox = substr($mailbox, 0, strlen($mailbox) - 1);
            $boxes[$g]["unformatted"] = $mailbox;
            $boxes[$g]["id"] = $g;

            /** Now lets get the flags for this mailbox **/
            fputs ($imap_stream, "a002 LIST \"\" \"$mailbox\"\r\n"); 
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
      }
      $boxes = ary_sort ($boxes, "unformatted", 1);
      return $boxes;
   }
   
?>
