<?php
   /**
    **  imap_mailbox.php
    **
    **  This impliments all functions that manipulate mailboxes
    **
    **  $Id$
    **/

   /******************************************************************************
    **  Expunges a mailbox 
    ******************************************************************************/
   function sqimap_mailbox_expunge ($imap_stream, $mailbox,$handle_errors = true) {
      sqimap_mailbox_select ($imap_stream, $mailbox);
      fputs ($imap_stream, "a001 EXPUNGE\r\n");
      $read = sqimap_read_data($imap_stream, "a001", $handle_errors, $response, $message);
   }


   /******************************************************************************
    **  Checks whether or not the specified mailbox exists 
    ******************************************************************************/
   function sqimap_mailbox_exists ($imap_stream, $mailbox) {
      if (! isset($mailbox))
          return false;
      fputs ($imap_stream, "a001 LIST \"\" \"$mailbox\"\r\n");
      $mbx = sqimap_read_data($imap_stream, "a001", true, $response, $message);
      return isset($mbx[0]);
   }

   /******************************************************************************
    **  Selects a mailbox
    ******************************************************************************/
   function sqimap_mailbox_select ($imap_stream, $mailbox, $hide=true, $recent=false) {
      global $auto_expunge;
      
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
      if ($auto_expunge) {
         fputs ($imap_stream, "a001 EXPUNGE\r\n");
         $tmp = sqimap_read_data($imap_stream, "a001", false, $a, $b);
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
    **
    **  The four parts are:
    **
    **    raw            - Raw LIST/LSUB response from the IMAP server
    **    formatted      - nicely formatted folder name
    **    unformatted    - unformatted, but with delimiter at end removed
    **    unformatted-dm - folder name as it appears in raw response
    **    unformatted-disp - unformatted without $folder_prefix
    **
    ******************************************************************************/
   function sqimap_mailbox_parse ($line, $line_lsub, $dm) {
      global $folder_prefix;
     
      // Process each folder line
      for ($g=0; $g < count($line); $g++) {

         // Store the raw IMAP reply
         if (isset($line[$g]))
            $boxes[$g]["raw"] = $line[$g];
         else
            $boxes[$g]["raw"] = "";


         // Count number of delimiters ($dm) in folder name
         $mailbox = trim($line_lsub[$g]);
         $dm_count = countCharInString($mailbox, $dm);
         if (substr($mailbox, -1) == $dm)
            $dm_count--;  // If name ends in delimiter - decrement count by one

         // Format folder name, but only if it's a INBOX.* or have
         // a parent.
         $boxesbyname[$mailbox] = $g;
         $parentfolder = readMailboxParent($mailbox, $dm);
         if((strtolower(substr($mailbox, 0, 5)) == "inbox") ||
            (substr($mailbox, 0, strlen($folder_prefix)) == $folder_prefix) ||
            (isset($boxesbyname[$parentfolder]) && (strlen($parentfolder) > 0) ) ) {
            $indent = $dm_count - (countCharInString($folder_prefix, $dm));
            if ($indent > 0)
                $boxes[$g]["formatted"]  = str_repeat("&nbsp;&nbsp;", $indent);
            else
                $boxes[$g]["formatted"] = '';
            $boxes[$g]["formatted"] .= readShortMailboxName($mailbox, $dm);
         } else {
            $boxes[$g]["formatted"]  = $mailbox;
         }
            
         $boxes[$g]['unformatted-dm'] = $mailbox;
         if (substr($mailbox, -1) == $dm)
            $mailbox = substr($mailbox, 0, strlen($mailbox) - 1);
         $boxes[$g]['unformatted'] = $mailbox;
         //$boxes[$g]['unformatted-disp'] = ereg_replace('^' . $folder_prefix, '', $mailbox);
         if (substr($mailbox,0,strlen($folder_prefix))==$folder_prefix) { 
            $boxes[$g]['unformatted-disp'] = substr($mailbox, strlen($folder_prefix));
         }
         $boxes[$g]['id'] = $g;

         if (isset($line[$g]))
            ereg("\(([^)]*)\)",$line[$g],$regs);
         $flags = trim(strtolower(str_replace('\\', '',$regs[1])));
         if ($flags) {
            $boxes[$g]['flags'] = explode(' ', $flags);
         }
	 else
	     $boxes[$g]['flags'] = array();
      }

      return $boxes;
   }
   
   /* Apparently you must call a user function with usort instead
    * of calling a built-in directly.  Stupid.
    * Patch from dave_michmerhuizen@yahoo.com
    * Allows case insensitivity when sorting folders
    */
   function user_strcasecmp($a, $b)
   {
       return strcasecmp($a, $b);
   }


   /******************************************************************************
    **  Returns sorted mailbox lists in several different ways.
    **  See comment on sqimap_mailbox_parse() for info about the returned array.
    ******************************************************************************/
   function sqimap_mailbox_list ($imap_stream) {
      global $load_prefs_php, $prefs_php, $config_php;
      global $data_dir, $username, $list_special_folders_first;
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

      /** OS: we don't want to parse last element of array, 'cause it is OK command, so we unset it **/
      /** LUKE:  This introduced errors.. do a check first **/
      if (substr($lsub_ary[count($lsub_ary)-1], 0, 4) == "* OK") {
        unset($lsub_ary[count($lsub_ary)-1]);
      }

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
         usort($sorted_lsub_ary, "user_strcasecmp");
         //sort($sorted_lsub_ary);
      }   

      /** LIST array **/
      for ($i=0; $i < count($sorted_lsub_ary); $i++) {
         if (substr($sorted_lsub_ary[$i], -1) == $dm)
            $mbx = substr($sorted_lsub_ary[$i], 0, strlen($sorted_lsub_ary[$i])-1);
         else
            $mbx = $sorted_lsub_ary[$i];

         fputs ($imap_stream, "a001 LIST \"\" \"$mbx\"\r\n");
         $read = sqimap_read_data ($imap_stream, "a001", true, $response, $message);
         if (isset($sorted_list_ary[$i]))
            $sorted_list_ary[$i] = "";
         if (isset($read[0]))
         $sorted_list_ary[$i] = $read[0];
         else
         $sorget_list_ary[$i] = "";
         if (isset($sorted_list_ary[$i]) && find_mailbox_name($sorted_list_ary[$i]) == "INBOX")
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

      $boxesnew = Array();

      // Find INBOX
      for ($i = 0; $i < count($boxes); $i++) {
         if (strtolower($boxes[$i]["unformatted"]) == "inbox") {
            $boxesnew[] = $boxes[$i];
            $used[$i] = true;
            $i = count($boxes);
         }
      }

      if ($list_special_folders_first == true) {

         // Then list special folders and their subfolders
         for ($i = 0 ; $i < count($boxes) ; $i++) {
            if ($move_to_trash &&
                eregi('^' . quotemeta($trash_folder) . '(' .
                quotemeta($dm) . '.*)?$', $boxes[$i]["unformatted"])) {
               $boxesnew[] = $boxes[$i];
               $used[$i] = true;
            }
            elseif ($move_to_sent &&
                eregi('^' . quotemeta($sent_folder) . '(' .
                quotemeta($dm) . '.*)?$', $boxes[$i]["unformatted"])) {
               $boxesnew[] = $boxes[$i];
               $used[$i] = true;
            }
         }

         // Put INBOX.* folders ahead of the rest
         for ($i = 0; $i < count($boxes); $i++) {
            if (eregi('^inbox\\.', $boxes[$i]["unformatted"]) &&
                (!isset($used[$i]) || $used[$i] == false)) {
               $boxesnew[] = $boxes[$i];
               $used[$i] = true;
            }
         }

      }

      // Rest of the folders
      for ($i = 0; $i < count($boxes); $i++) {
         if ((strtolower($boxes[$i]["unformatted"]) != "inbox") &&
             (!isset($used[$i]) || $used[$i] == false))  {
            $boxesnew[] = $boxes[$i];
            $used[$i] = true;
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

            // Store the raw IMAP reply
            $boxes[$g]["raw"] = $read_ary[$i];

            // Count number of delimiters ($dm) in folder name
            $mailbox = find_mailbox_name($read_ary[$i]);
            $dm_count = countCharInString($mailbox, $dm);
            if (substr($mailbox, -1) == $dm)
               $dm_count--;  // If name ends in delimiter - decrement count by one
            
            // Format folder name, but only if it's a INBOX.* or have
            // a parent.
            $boxesbyname[$mailbox] = $g;
            $parentfolder = readMailboxParent($mailbox, $dm);
            if((eregi('^inbox'.quotemeta($dm), $mailbox)) || 
               (ereg('^'.$folder_prefix, $mailbox)) ||
               ( isset($boxesbyname[$parentfolder]) && (strlen($parentfolder) > 0) ) ) {
               if ($dm_count)
                   $boxes[$g]["formatted"]  = str_repeat("&nbsp;&nbsp;", $dm_count);
               else
                   $boxes[$g]["formatted"] = '';
               $boxes[$g]["formatted"] .= readShortMailboxName($mailbox, $dm);
            } else {
               $boxes[$g]["formatted"]  = $mailbox;
            }
               
            $boxes[$g]["unformatted-dm"] = $mailbox;
            if (substr($mailbox, -1) == $dm)
               $mailbox = substr($mailbox, 0, strlen($mailbox) - 1);
            $boxes[$g]["unformatted"] = $mailbox;
            $boxes[$g]["unformatted-disp"] = ereg_replace('^' . $folder_prefix, '', $mailbox);
            $boxes[$g]["id"] = $g;

            /** Now lets get the flags for this mailbox **/
            fputs ($imap_stream, "a002 LIST \"\" \"$mailbox\"\r\n"); 
            $read_mlbx = sqimap_read_data ($imap_stream, "a002", true, $response, $message);

            $flags = substr($read_mlbx[0], strpos($read_mlbx[0], "(")+1);
            $flags = substr($flags, 0, strpos($flags, ")"));
            $flags = str_replace('\\', '', $flags);
            $flags = trim(strtolower($flags));
            if ($flags) {
               $boxes[$g]['flags'] = explode(" ", $flags);
            }
	    else
	    {
	       $boxes[$g]['flags'] = array();
	    }
         }
         $g++;
      }
      if(is_array($boxes)) {
         $boxes = ary_sort ($boxes, "unformatted", 1);
      }

      return $boxes;
   }
   
?>
