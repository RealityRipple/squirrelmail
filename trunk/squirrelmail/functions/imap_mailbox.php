<?php
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
      sqimap_mailbox_close ($imap_stream);
   }


   /******************************************************************************
    **  Checks whether or not the specified mailbox exists 
    ******************************************************************************/
   function sqimap_mailbox_exists ($imap_stream, $mailbox) {
      fputs ($imap_stream, "a001 LIST \"\" \"$mailbox\"\r\n");
      $mbx = sqimap_read_data($imap_stream, "a001", true, $response, $message);
      if ($mailbox) {
         if (ereg ("$mailbox", $mbx[0])) {
            return true;
         } else {
            return false;
         }
      }
   }

   /******************************************************************************
    **  Closes an open mailbox 
    ******************************************************************************/
   function sqimap_mailbox_close ($imap_stream) {
      fputs ($imap_stream, "a001 CLOSE\r\n");
      $tmp = sqimap_read_data($imap_stream, "a001", false, $response, $message);
   }

   /******************************************************************************
    **  Selects a mailbox
    ******************************************************************************/
   function sqimap_mailbox_select ($imap_stream, $mailbox, $hide=true, $recent=false) {
      fputs ($imap_stream, "a001 SELECT \"$mailbox\"\r\n");
     	$read = sqimap_read_data($imap_stream, "a001", true, $response, $message);
      if ($recent) {
         for ($i=0; $i<count($read); $i++) {
            if (strpos(strtolower($read[$i]), "recent")) {
               $r = explode(" ", $read[$i]);
            }
         }
         return $r[1];
      }
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
		global $imap_server_type;

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
    **  Formats a mailbox into 4 parts for the $boxes array
    ******************************************************************************/
   function sqimap_mailbox_parse ($line, $line_lsub, $dm) {
		global $folder_prefix;
      for ($g=0; $g < count($line); $g++) {
         $boxes[$g]["raw"] = $line[$g];
            
         $mailbox = $line_lsub[$g];
         $dm_count = countCharInString($mailbox, $dm);
         if (substr($mailbox, -1) == $dm)
            $dm_count--;
            
         for ($j = 0; $j < $dm_count - (countCharInString($folder_prefix, $dm)); $j++)
            $boxes[$g]["formatted"] = $boxes[$g]["formatted"] . "&nbsp;&nbsp;";
         $boxes[$g]["formatted"] .= readShortMailboxName($mailbox, $dm);
            
         $boxes[$g]["unformatted-dm"] = $mailbox;
         if (substr($mailbox, -1) == $dm)
            $mailbox = substr($mailbox, 0, strlen($mailbox) - 1);
         $boxes[$g]["unformatted"] = $mailbox;
         $boxes[$g]["id"] = $g;

         $flags = substr($line[$g], strpos($line[$g], "(")+1);
         $flags = substr($flags, 0, strpos($flags, ")"));
         $flags = str_replace("\\", "", $flags);
         $flags = trim(strtolower($flags));
         if ($flags) {
            $boxes[$g]["flags"] = explode(" ", $flags);
         }
      }
      return $boxes;
   }
   
   /******************************************************************************
    **  Returns sorted mailbox lists in several different ways.
    **  The array returned looks like this:
    ******************************************************************************/
   function sqimap_mailbox_list ($imap_stream) {
      global $load_prefs_php, $prefs_php, $config_php, $data_dir, $username, $list_special_folders_first;
		global $trash_folder, $sent_folder;
		global $move_to_trash, $move_to_sent;

      $inbox_in_list = false;
      $inbox_subscribed = false;

      if (!isset($load_prefs_php)) include "../src/load_prefs.php";
      else global $folder_prefix;
      if (!function_exists ("ary_sort")) include "../functions/array.php";

      $dm = sqimap_get_delimiter ($imap_stream);

      /** LSUB array **/
      $inbox_subscribed = false;
      fputs ($imap_stream, "a001 LSUB \"\" \"*\"\r\n");
      $lsub_ary = sqimap_read_data ($imap_stream, "a001", true, $response, $message);
   
      for ($i=0;$i < count($lsub_ary); $i++) {
         $sorted_lsub_ary[$i] = find_mailbox_name($lsub_ary[$i]);
         if ($sorted_lsub_ary[$i] == "INBOX")
            $inbox_subscribed = true;
      }
      $new_ary = array();
      for ($i=0; $i < count($sorted_lsub_ary); $i++) {
         if (!in_array($sorted_lsub_ary[$i], $new_ary)) {
            $new_ary[] = $sorted_lsub_ary[$i];
         }
      }
      $sorted_lsub_ary = $new_ary;
      if (isset($sorted_lsub_ary)) {
         sort($sorted_lsub_ary);
      }   

      /** LIST array **/
		for ($i=0; $i < count($sorted_lsub_ary); $i++) {
			if (substr($sorted_lsub_ary[$i], -1) == $dm)
				$mbx = substr($sorted_lsub_ary[$i], 0, strlen($sorted_lsub_ary[$i])-1);
			else
				$mbx = $sorted_lsub_ary[$i];

			fputs ($imap_stream, "a001 LIST \"\" \"$mbx\"\r\n");
			$read = sqimap_read_data ($imap_stream, "a001", true, $response, $message);
			$sorted_list_ary[$i] = $read[0];
         if (find_mailbox_name($sorted_list_ary[$i]) == "INBOX")
            $inbox_in_list = true;
		}
		
      /** Just in case they're not subscribed to their inbox, we'll get it for them anyway **/
      if ($inbox_subscribed == false || $inbox_in_list == false) {
         fputs ($imap_stream, "a001 LIST \"\" \"INBOX\"\r\n");
         $inbox_ary = sqimap_read_data ($imap_stream, "a001", true, $response, $message);

         $pos = count($sorted_list_ary);
         $sorted_list_ary[$pos] = $inbox_ary[0];

         $pos = count($sorted_lsub_ary);
         $sorted_lsub_ary[$pos] = find_mailbox_name($inbox_ary[0]);
      }

		$boxes = sqimap_mailbox_parse ($sorted_list_ary, $sorted_lsub_ary, $dm);
		
		/** Now, lets sort for special folders **/
      for ($i = 0; $i < count($boxes); $i++) {
         if (strtolower($boxes[$i]["unformatted"]) == "inbox") {
            $boxesnew[0] = $boxes[$i];
            $boxes[$i]["used"] = true;
				$i = count($boxes);
         }
      }

      if ($list_special_folders_first == true) {
         for ($i = count($boxes)-1; $i >= 0 ; $i--) {
				if (($boxes[$i]["unformatted"] == $trash_folder) && ($move_to_trash)) {	
               $pos = count($boxesnew);
               $boxesnew[$pos] = $boxes[$i];
               $boxes[$i]["used"] = true;
					$trash_found = true;
            }
				else if (($boxes[$i]["unformatted"] == $sent_folder) && ($move_to_sent)) {	
               $pos = count($boxesnew);
               $boxesnew[$pos] = $boxes[$i];
               $boxes[$i]["used"] = true;
					$sent_found = true;
            }

				if (($sent_found && $trash_found) || ($sent_found && !$move_to_trash) || ($trash_found && !$move_to_sent) || (!$move_to_sent && !$move_to_trash))
					$i = -1;
         }
      }

      for ($i = 0; $i < count($boxes); $i++) {
         if ((strtolower($boxes[$i]["unformatted"]) != "inbox") &&
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
      global $list_special_folders_first, $folder_prefix;
      
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
      if ($boxes) {
         $boxes = ary_sort ($boxes, "unformatted", 1);
      }
      return $boxes;
   }
   
?>
